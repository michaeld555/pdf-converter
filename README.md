# PDF Converter

Biblioteca PHP para converter arquivos DOCX em PDF usando uma instalação local do
LibreOffice.

A conversão é executada em modo headless, sem interface gráfica e sem enviar o
documento para serviços externos. Cada execução recebe um diretório temporário e
um perfil isolado do LibreOffice, permitindo conversões concorrentes com menor
risco de conflito.

## Conteúdo

- [Principais características](#principais-características)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Uso rápido](#uso-rápido)
- [Configuração](#configuração)
- [Tratamento de erros](#tratamento-de-erros)
- [Referência da API](#referência-da-api)
- [Uso em aplicações web](#uso-em-aplicações-web)
- [Drivers personalizados](#drivers-personalizados)
- [Compatibilidade com a API 1.x](#compatibilidade-com-a-api-1x)
- [Docker](#docker)
- [Testes e qualidade](#testes-e-qualidade)
- [Solução de problemas](#solução-de-problemas)
- [Segurança e privacidade](#segurança-e-privacidade)
- [Migração da versão 1.x](#migração-da-versão-1x)

## Principais características

- Conversão local de DOCX para PDF.
- Compatível com PHP 8.2, 8.3, 8.4 e 8.5.
- Timeout configurável para o processo.
- Perfil isolado do LibreOffice para cada conversão.
- Nomes de arquivo com espaços e caracteres especiais.
- Validação do cabeçalho do PDF gerado.
- Gravação por arquivo temporário no diretório de destino.
- Proteção contra sobrescrita acidental.
- Limpeza de arquivos temporários em sucesso ou erro.
- Interface para implementar outros mecanismos de conversão.
- Exceções com mensagens específicas para diagnóstico.

## Requisitos

### PHP

- PHP 8.2 ou superior.
- Composer 2.
- Função `proc_open` habilitada. Ela é utilizada pelo `symfony/process`.

### LibreOffice

O executável `libreoffice` ou `soffice` deve estar instalado no sistema. O
LibreOffice não é uma dependência Composer e precisa ser instalado separadamente.

Ubuntu ou Debian:

```bash
sudo apt-get update
sudo apt-get install --yes libreoffice-writer
```

Alpine Linux:

```bash
apk add --no-cache libreoffice
```

macOS com Homebrew:

```bash
brew install --cask libreoffice
```

Windows:

1. Instale o LibreOffice pelo site oficial.
2. Informe o caminho completo de `soffice.exe` ao configurar o driver.

Exemplo comum:

```text
C:\Program Files\LibreOffice\program\soffice.exe
```

Confirme a instalação:

```bash
libreoffice --version
```

## Instalação

Instale a biblioteca com Composer:

```bash
composer require michaeld555/pdf-converter
```

Para usar especificamente a nova API após a publicação da versão 2:

```bash
composer require michaeld555/pdf-converter:^2.0
```

Durante o desenvolvimento deste repositório:

```bash
git clone https://github.com/michaeld555/pdf-converter.git
cd pdf-converter
composer install
```

## Uso rápido

O primeiro argumento é o caminho de um arquivo DOCX local. O segundo é o caminho
completo do PDF de destino, incluindo a extensão `.pdf`.

```php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Michaeld555\PdfConverter\Converter;

$converter = new Converter();
$converter->convert(
    __DIR__.'/documents/contract.docx',
    __DIR__.'/documents/contract.pdf',
);
```

Por segurança, um arquivo de destino existente não é substituído por padrão.

## Configuração

Use `LibreOfficeConverter` quando precisar configurar executável, timeout,
sobrescrita ou diretório temporário:

```php
<?php

declare(strict_types=1);

use Michaeld555\PdfConverter\Converter;
use Michaeld555\PdfConverter\Driver\LibreOfficeConverter;

$driver = new LibreOfficeConverter(
    binary: '/usr/bin/libreoffice',
    timeout: 120.0,
    overwrite: false,
    temporaryDirectory: '/var/tmp/pdf-converter',
);

$converter = new Converter($driver);
$converter->convert('/data/input.docx', '/data/output.pdf');
```

### Opções do driver

| Opção | Tipo | Padrão | Descrição |
| --- | --- | --- | --- |
| `binary` | `string` | `libreoffice` | Nome ou caminho do executável. |
| `timeout` | `float` | `60.0` | Tempo máximo da conversão em segundos. |
| `overwrite` | `bool` | `false` | Permite substituir um PDF existente. |
| `processRunner` | `?ProcessRunner` | Runner Symfony | Integração avançada ou testes. |
| `temporaryDirectory` | `?string` | Diretório do sistema | Diretório base para arquivos isolados. |

O diretório temporário configurado precisa existir e ser gravável. A biblioteca
cria e remove somente seus subdiretórios com prefixo `pdf-converter-`.

### Sobrescrever arquivos

A sobrescrita precisa ser habilitada explicitamente:

```php
use Michaeld555\PdfConverter\Driver\LibreOfficeConverter;

$converter = new LibreOfficeConverter(overwrite: true);
$converter->convert('/data/input.docx', '/data/output.pdf');
```

### Executável fora do PATH

Linux:

```php
$converter = new LibreOfficeConverter(
    binary: '/opt/libreoffice/program/soffice',
);
```

Windows:

```php
$converter = new LibreOfficeConverter(
    binary: 'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
);
```

## Tratamento de erros

Todas as falhas operacionais são expostas como
`Michaeld555\PdfConverter\Exception\ConversionException`.

```php
use Michaeld555\PdfConverter\Converter;
use Michaeld555\PdfConverter\Exception\ConversionException;

try {
    (new Converter())->convert('/data/input.docx', '/data/output.pdf');
} catch (ConversionException $exception) {
    error_log($exception->getMessage());

    // Retorne uma resposta adequada para sua aplicação.
}
```

Situações verificadas:

- origem ausente, ilegível, remota ou sem extensão `.docx`;
- destino sem extensão `.pdf`;
- diretório de destino ausente ou sem permissão;
- destino já existente;
- diretório temporário inválido;
- LibreOffice ausente ou impossibilitado de iniciar;
- timeout;
- processo finalizado com código de erro;
- PDF não gerado, vazio ou com cabeçalho inválido;
- falha ao copiar ou mover o resultado.

As exceções lançadas pelo processo são mantidas em `getPrevious()` quando
disponíveis.

## Referência da API

### `Michaeld555\PdfConverter\Converter`

Fachada principal. Usa `LibreOfficeConverter` quando nenhum driver é fornecido.

```php
public function __construct(?DocumentConverter $driver = null)
public function convert(string $source, string $destination): void
```

### `Michaeld555\PdfConverter\Driver\LibreOfficeConverter`

Implementação local baseada no comando do LibreOffice.

```php
public function __construct(
    string $binary = 'libreoffice',
    float $timeout = 60.0,
    bool $overwrite = false,
    ?ProcessRunner $processRunner = null,
    ?string $temporaryDirectory = null,
)

public function convert(string $source, string $destination): void
```

### Regras dos caminhos

- A origem deve ser um arquivo local regular, legível e terminar em `.docx`.
- URLs como `https://...` não são aceitas.
- O destino deve incluir o nome completo e terminar em `.pdf`.
- O diretório pai do destino precisa existir e ser gravável.
- Diretórios de destino não são criados automaticamente.
- Caminhos relativos são aceitos e resolvidos pelo PHP a partir do diretório
  atual do processo.

## Uso em aplicações web

Arquivos enviados devem ser movidos para uma área controlada antes da conversão:

```php
use Michaeld555\PdfConverter\Converter;
use Michaeld555\PdfConverter\Exception\ConversionException;

$upload = $_FILES['document'] ?? null;

if (!is_array($upload) || UPLOAD_ERR_OK !== $upload['error']) {
    throw new RuntimeException('Upload inválido.');
}

$input = '/srv/app/storage/uploads/'.bin2hex(random_bytes(16)).'.docx';
$output = '/srv/app/storage/pdfs/'.bin2hex(random_bytes(16)).'.pdf';

if (!move_uploaded_file($upload['tmp_name'], $input)) {
    throw new RuntimeException('Não foi possível armazenar o upload.');
}

try {
    (new Converter())->convert($input, $output);
} catch (ConversionException $exception) {
    // Registre o erro sem expor caminhos internos para o usuário final.
    throw $exception;
} finally {
    @unlink($input);
}
```

A aplicação é responsável por:

- autenticar e autorizar o usuário;
- limitar tamanho e quantidade de uploads;
- validar o MIME type e, se necessário, analisar malware;
- gerar nomes de arquivo não controlados pelo usuário;
- aplicar rate limiting e limites de concorrência;
- definir prazo de retenção e remover documentos antigos.

### Arquivos remotos

Baixe o arquivo usando o cliente HTTP da sua aplicação, com timeout, limite de
tamanho, validação TLS e política de redirects. Em seguida, passe o arquivo local
para a biblioteca.

Essa separação evita que o conversor se torne um vetor de SSRF e deixa credenciais,
políticas de rede e autenticação sob controle da aplicação.

## Drivers personalizados

Implemente `DocumentConverter` para integrar outro mecanismo, como Microsoft
Graph, uma fila interna ou um serviço corporativo autenticado:

```php
<?php

declare(strict_types=1);

use Michaeld555\PdfConverter\Contract\DocumentConverter;
use Michaeld555\PdfConverter\Converter;

final class CompanyConverter implements DocumentConverter
{
    public function convert(string $source, string $destination): void
    {
        // Integração específica da aplicação.
    }
}

$converter = new Converter(new CompanyConverter());
$converter->convert('/data/input.docx', '/data/output.pdf');
```

Drivers externos devem documentar autenticação, timeout, privacidade, retenção dos
documentos e comportamento de sobrescrita.

## Compatibilidade com a API 1.x

A classe antiga permanece disponível temporariamente:

```php
use Michaeld555\Converter;

Converter::docx_to_pdf('/data/input.docx', '/data/output.pdf');
```

Ela está depreciada e emite `E_USER_DEPRECATED`. Também aceita um diretório como
segundo argumento e deriva o nome do PDF a partir da origem:

```php
Converter::docx_to_pdf('/data/report.docx', '/data/pdfs');
// Resultado: /data/pdfs/report.pdf
```

URLs remotas não são mais aceitas, inclusive pela classe legada. O adaptador será
removido em uma futura versão principal.

## Docker

Exemplo mínimo:

```dockerfile
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install --yes --no-install-recommends \
        git \
        libreoffice-writer \
        unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY . .

CMD ["php", "app.php"]
```

Em containers com filesystem somente leitura, monte diretórios graváveis para:

- os documentos de entrada;
- os PDFs de saída;
- o diretório temporário configurado.

## Testes e qualidade

Instale as dependências de desenvolvimento:

```bash
composer install
```

Execute todas as verificações:

```bash
composer check
```

Comandos individuais:

```bash
composer test
composer test:unit
composer test:integration
composer analyse
composer cs:check
composer cs:fix
composer security:audit
composer qa:validate
```

O teste de integração cria um DOCX com páginas portrait e landscape, realiza uma
conversão real e inspeciona as páginas com `pdfinfo` (`poppler-utils`). Ele é
ignorado quando LibreOffice, `pdfinfo` ou a extensão ZIP não estão disponíveis.

Para indicar outro executável durante o teste:

```bash
LIBREOFFICE_BINARY=/opt/libreoffice/program/soffice composer test:integration
```

O pipeline do GitHub Actions executa:

- testes unitários no PHP 8.2, 8.3, 8.4 e 8.5;
- PHPStan no nível máximo;
- verificação de estilo;
- auditoria de dependências;
- teste de integração com LibreOffice real.

## Solução de problemas

### `Unable to execute LibreOffice`

O executável não foi encontrado ou `proc_open` está desabilitado.

```bash
which libreoffice
php -r 'var_dump(function_exists("proc_open"));'
```

Configure `binary` com o caminho absoluto quando necessário.

### Timeout

Documentos grandes, fontes ausentes ou storage lento podem exigir um limite maior:

```php
$converter = new LibreOfficeConverter(timeout: 180.0);
```

Investigue o documento antes de aumentar o timeout indefinidamente.

### PDF com layout ou fonte diferente

O LibreOffice precisa ter acesso às mesmas fontes usadas no DOCX. Instale as fontes
no host ou container e atualize o cache:

```bash
fc-cache -f
```

A renderização do LibreOffice pode não ser pixel a pixel idêntica ao Microsoft
Word, especialmente em documentos que dependem de fontes proprietárias, macros ou
recursos específicos do Office.

### Permissão negada

Verifique os diretórios de origem, destino e temporário usando o mesmo usuário do
processo PHP:

```bash
sudo -u www-data test -r /data/input.docx
sudo -u www-data test -w /data/pdfs
sudo -u www-data test -w /var/tmp/pdf-converter
```

### Conversões concorrentes travando

A biblioteca já usa um perfil separado do LibreOffice para cada conversão. Ainda
assim, limite a concorrência conforme CPU e memória disponíveis. O LibreOffice é
um processo relativamente pesado.

### O PDF já existe

Escolha outro destino, remova o arquivo explicitamente ou habilite:

```php
new LibreOfficeConverter(overwrite: true);
```

## Segurança e privacidade

- A implementação padrão não envia documentos para terceiros.
- O antigo endpoint interno do Office Online foi removido.
- A biblioteca não baixa URLs.
- Argumentos do processo são passados como uma lista, sem montar comando de shell.
- O processo possui timeout.
- Arquivos temporários usam nomes aleatórios e permissões restritas.
- O resultado precisa começar com `%PDF-` antes de ser salvo.
- O destino não é sobrescrito sem configuração explícita.

Para documentos não confiáveis, considere executar a conversão em container,
usuário sem privilégios, fila dedicada e filesystem restrito. Timeout não substitui
limites de CPU, memória, disco e quantidade de processos.

Consulte também [SECURITY.md](SECURITY.md).

## Migração da versão 1.x

### Antes

```php
use Michaeld555\Converter;

Converter::docx_to_pdf(
    'https://example.com/document.docx',
    '/data/pdfs/',
);
```

### Depois

```php
use Michaeld555\PdfConverter\Converter;

$converter = new Converter();
$converter->convert(
    '/data/documents/document.docx',
    '/data/pdfs/document.pdf',
);
```

Mudanças importantes:

1. PHP mínimo passou de 8.0 para 8.2.
2. LibreOffice passou a ser requisito do ambiente.
3. A entrada deve ser um DOCX local.
4. O destino deve ser o caminho completo do PDF.
5. Arquivos existentes não são sobrescritos por padrão.
6. O namespace principal agora é `Michaeld555\PdfConverter`.
7. Falhas lançam `ConversionException`.
8. FPDI, TCPDF e o endpoint não documentado do Office foram removidos.

O adaptador `Michaeld555\Converter::docx_to_pdf()` facilita uma migração gradual,
mas o código novo deve usar a API orientada a instância.

## Estrutura do projeto

```text
src/
├── Contract/   Interfaces públicas
├── Driver/     Implementações de conversão
├── Exception/  Exceções da biblioteca
├── Legacy/     Compatibilidade temporária com a API 1.x
├── Process/    Execução isolada de processos
└── Converter.php

tests/
├── Integration/
├── Support/
└── Unit/
```

## Licença

Distribuído sob a licença MIT. Consulte [LICENSE](LICENSE).
