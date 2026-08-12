# Mini Help Desk

Mini Help Desk em PHP para atendimento de solicitações de suporte, criado para ser integrado em um site existente.

## O que o sistema faz até agora

- Recebe solicitações via formulário público em `index.php`
- Guarda tickets em um banco de dados MySQL
- Permite anexar imagens e PDFs a uma solicitação
- Mostra os tickets do usuário logado na área do cliente
- Pré-preenche nome e email do usuário autenticado no envio
- Painel administrativo unificado em `admin.php` com abas de tickets e usuários
- Permite ao admin:
  - alterar status do ticket (`Pendente`, `Em andamento`, `Resolvido`)
  - alterar responsável do ticket (`Não sabe`, `Renata`, `Celeste`)
  - responder ao usuário e enviar essa mensagem por email automaticamente
- Envia notificações por email quando:
  - um novo ticket é criado
  - o status do ticket é atualizado
  - o admin responde a um ticket
- Suporte a envio de email via SMTP com depuração em `smtp_debug.php`
- Função de importação POP3 existe em `config.php`, mas não há script público separado para acionamento atualmente
- Registra logs de email em `logs/mail.log`

## Arquivos principais

- `index.php` - formulário de envio de ticket, upload de anexo e lista de tickets do usuário
- `login_fun.php` - autenticação de usuário por email/senha
- `logout.php` - encerra a sessão do usuário
- `admin.php` - painel administrativo com gestão de tickets e usuários
- `config.php` - configurações do banco, criação de esquema, email SMTP/POP3 e funções auxiliares
- `smtp_debug.php` - script para testar e depurar conexão SMTP
- `smtp_test.php` - script para testar envio de email para qualquer endereço
- `README.md` - documentação do projeto
- `helpdesk.sql` - script SQL para criar as tabelas e dados iniciais
- `styles.css` - estilo visual do projeto
- `uploads/` - pasta de armazenamento de anexos
- `logs/` - pasta de logs de envio de email

## Como instalar

1. Crie um banco de dados MySQL e um usuário com acesso a ele.
2. Faça upload da pasta do projeto para o servidor (por exemplo `public_html/helpdesk`).
3. Atualize `config.php` com as credenciais do banco:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
   - `ADMIN_PASSWORD`
4. Ajuste a configuração de email em `config.php`:
   - `MAIL_SMTP_HOST`
   - `MAIL_SMTP_PORT`
   - `MAIL_SMTP_USER`
   - `MAIL_SMTP_PASS`
   - `MAIL_POP3_HOST`
   - `MAIL_POP3_PORT`
   - `MAIL_POP3_USER`
   - `MAIL_POP3_PASS`
   - `MAIL_SMTP_SECURE`
   - `MAIL_POP3_SECURE`
5. Verifique se as pastas `uploads/` e `logs/` existem e têm permissão de escrita.
6. Abra o projeto no navegador, por exemplo `https://seusite.com/helpdesk/index.php`.
7. Acesse o painel administrativo em `https://seusite.com/helpdesk/admin.php`.

## Observações de uso

- O painel administrativo `admin.php` já reúne a gestão de tickets e usuários.
- A resposta ao usuário pode ser escrita no campo do ticket e salva junto com o status/responsável.
- Os emails de notificação usam SMTP configurado em `config.php`.
- Use `smtp_debug.php` e `smtp_test.php` para testar a conexão SMTP e o envio de email.
- Se o email não chegar, verifique `logs/mail.log` e as configurações SMTP.
- A função POP3 em `config.php` existe, mas não há interface pública para importação automática implementada neste momento.

## Sugestões de melhorias

- Adicionar suporte completo a respostas por email recebidas automaticamente
- Adicionar filtro de tickets por status ou responsável no painel admin
- Criar uma página de cadastro de usuário no site
- Melhorar a interface para dispositivos móveis
- Adicionar controle de permissões real para administradores e usuários
