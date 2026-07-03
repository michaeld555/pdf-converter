# Como contribuir

## Ambiente

- PHP 8.2 ou superior.
- Composer 2.
- LibreOffice e `pdfinfo` (`poppler-utils`) para executar os testes de integração.

Instale as dependências:

```bash
composer install
```

## Verificações

Antes de enviar uma alteração, execute:

```bash
composer check
```

Para trabalhar separadamente:

```bash
composer test:unit
composer test:integration
composer analyse
composer cs:check
composer cs:fix
composer security:audit
composer qa:validate
```

Os testes de integração são ignorados quando o LibreOffice não é encontrado.
Use `LIBREOFFICE_BINARY` para indicar um executável fora do `PATH`.

## Padrões

- Todo arquivo PHP deve usar `declare(strict_types=1)`.
- Código novo deve possuir tipos de parâmetros e retorno.
- Alterações de comportamento devem incluir testes.
- Alterações públicas devem ser registradas no `CHANGELOG.md`.
- Não adicione serviços de conversão externos sem autenticação, contrato público e
  documentação explícita sobre privacidade.
