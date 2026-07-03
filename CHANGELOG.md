# Changelog

Todas as alterações relevantes deste projeto serão registradas neste arquivo.

O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o
projeto usa [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não lançado]

### Adicionado

- API orientada a instância com `Michaeld555\PdfConverter\Converter`.
- Driver local baseado no LibreOffice em modo headless.
- Contrato `DocumentConverter` para drivers personalizados.
- Timeout configurável, isolamento por processo e gravação segura do arquivo final.
- Exceções específicas de conversão com mensagens acionáveis.
- Testes unitários e teste de integração real com LibreOffice.
- PHPStan, PHP-CS-Fixer, PHPUnit e pipeline de CI para PHP 8.2 a 8.5.
- Documentação completa de instalação, configuração, erros e migração.

### Alterado

- A versão mínima do PHP passou de 8.0 para 8.2.
- O namespace principal passou a ser `Michaeld555\PdfConverter`.
- A entrada principal agora aceita arquivos DOCX locais.
- Arquivos existentes não são sobrescritos por padrão.

### Depreciado

- `Michaeld555\Converter::docx_to_pdf()`, mantido temporariamente como adaptador.

### Removido

- Conversão por endpoint interno e não documentado do Office Online.
- Dependências `setasign/fpdi-tcpdf`, `setasign/fpdi` e `tecnickcom/tcpdf`.
- Classe vazia `Michaeld555\Actions\ConvertFile`.
- Suporte a URLs remotas na API legada.

### Segurança

- Removidas as versões de FPDI e TCPDF afetadas por advisories conhecidos.
- Eliminado o envio implícito de URLs e documentos para infraestrutura externa.
- Adicionados timeout, validação do PDF gerado e limpeza garantida de temporários.
