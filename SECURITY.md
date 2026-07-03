# Política de segurança

## Versões suportadas

Enquanto a próxima versão principal não for publicada, somente a branch principal
recebe correções de segurança.

## Como relatar

Não publique inicialmente detalhes de uma vulnerabilidade em uma issue pública.
Use o recurso **Security Advisories** do repositório:

<https://github.com/michaeld555/pdf-converter/security/advisories/new>

Inclua:

- versão ou commit afetado;
- forma de reproduzir;
- impacto observado;
- sugestão de correção, quando disponível.

## Modelo de segurança

- A conversão padrão é local e não envia o documento a terceiros.
- O processo do LibreOffice possui timeout configurável.
- Cada conversão utiliza diretórios e perfil do LibreOffice isolados.
- O PDF é validado antes de ser movido para o destino.
- A aplicação consumidora continua responsável por limitar tamanho, origem e
  quantidade dos documentos recebidos de usuários.
