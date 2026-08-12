
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
=======
# 🛠️ Help Desk — Arruda Empresarial

Sistema de Help Desk (Central de Suporte) desenvolvido em **PHP** para gestão de chamados/solicitações de suporte técnico da **Arruda Empresarial**. O sistema foi projetado para ser integrado ao site existente da empresa e permite o atendimento completo do cliente ao administrador.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/Licen%C3%A7a-Pr%C3%B3pria-2b82b3?style=flat-square)

---

## 📌 Sobre o Projeto

O **Help Desk Arruda** é um portal de gerenciamento de chamados técnicos, com:

- **Área do Cliente**: abertura de chamados, upload de anexos (imagens e PDFs) e acompanhamento dos próprios tickets.
- **Painel Administrativo**: dashboard com métricas, gestão completa de tickets e usuários, com filtros, busca, paginação e histórico de respostas.
- **Notificações por E-mail**: envio automático via SMTP quando um chamado é aberto, atualizado ou respondido.

> 💡 O sistema já vem com **criação automática das tabelas** do banco de dados na primeira execução, mas também há um script SQL de referência (`helpdesk.sql`).

---

## ✨ Funcionalidades

### 👤 Área do Cliente
- Abertura de chamados com **nome, e-mail, assunto, responsável e mensagem**
- **Upload de anexos** (JPEG, PNG, GIF e PDF — até **20MB**)
- Listagem dos chamados do usuário autenticado (últimos 20)
- Visualização de detalhes do chamado com o anexo em modal
- Pré-preenchimento automático dos dados do usuário logado
- Login e cadastro de usuários por e-mail/senha

### 🔐 Painel Administrativo
- **Visão Geral**: KPIs com total de tickets, pendentes, em andamento, resolvidos e usuários cadastrados
- **Gerenciar Tickets**:
  - Busca livre (assunto, cliente, e-mail ou conteúdo)
  - Filtros por **status** (`Pendente`, `Em andamento`, `Resolvido`) e **responsável** (`Não sabe`, `Renata`, `Celeste`)
  - Paginação (10 tickets por página)
  - Atualização de status e responsável
  - **Resposta ao usuário** com envio automático por e-mail
  - Histórico de respostas do ticket (`ticket_responses`)
  - Exclusão permanente de tickets
- **Gerenciar Usuários**:
  - CRUD completo de **usuários** (clientes) com paginação
  - CRUD completo de **administradores**
  - Exclusão de usuários preserva os chamados (desvincula o `user_id`)

### 📧 Notificações por E-mail (SMTP)
- Confirmação ao cliente quando um **novo chamado** é aberto
- Aviso ao admin de **novo chamado** recebido
- Notificação ao cliente quando o **status** do chamado é atualizado
- **Resposta do admin** enviada automaticamente ao e-mail do cliente
- Logs de envio em `logs/mail.log`

### 📥 Importação POP3
- Função de importação de e-mails como tickets existe em `config.php` (`importPop3Emails()`)
- *Atualmente sem interface/script público de acionamento*

---

## 🧰 Stack Tecnológica

| Tecnologia | Uso |
|---|---|
| **PHP 8.x** | Linguagem principal (PDO com prepared statements) |
| **MySQL / MariaDB** | Banco de dados |
| **Bootstrap 5.3** | Interface responsiva |
| **Font Awesome 6** | Ícones |
| **SMTP (socket PHP)** | Envio de e-mails sem bibliotecas externas |
| **POP3 (socket PHP)** | Importação de tickets via e-mail |

---

## 📁 Estrutura do Projeto

| Arquivo / Pasta | Descrição |
|---|---|
| `index.php` | Área do cliente: abrir ticket, upload de anexo e listagem de chamados |
| `login_fun.php` | Página de login/cadastro de usuários (clientes) |
| `admin.php` | Painel administrativo: dashboard, tickets e usuários |
| `inicio.php` | Página inicial do portal (landing) |
| `header.php` | Navbar e cabeçalho compartilhado |
| `logout.php` | Encerra a sessão do usuário |
| `config.php` | Configurações do banco, e-mail SMTP/POP3 e funções auxiliares |
| `helpdesk.sql` | Script SQL de referência (tabelas e dados iniciais) |
| `styles.css` | Estilos visuais do projeto |
| `uploads/` | Pasta de armazenamento de anexos (imagens/PDFs) |
| `logs/` | Pasta de logs de envio de e-mail (`mail.log`) |
| `img/` | Imagens do site (banner, etc.) |

---

## ✅ Pré-requisitos

- **PHP 7.4+** (recomendado 8.x)
- **MySQL 5.7+** ou **MariaDB 10.4+**
- Servidor web com suporte a PHP (Apache/XAMPP, LAMP, WAMP, cPanel/KingHost, etc.)
- Permissão de escrita nas pastas `uploads/` e `logs/`
- Conta de e-mail com acesso **SMTP** para envio das notificações
- *(Opcional)* Conta de e-mail com acesso **POP3** para importação de tickets

---

## 🚀 Instalação

### 1. Clone ou envie os arquivos para o servidor

```bash
git clone https://github.com/seu-usuario/helpdesk.git
```

ou faça upload da pasta do projeto para o diretório do servidor, por exemplo:

```
public_html/helpdesk/
```

### 2. Crie o banco de dados

Crie um banco MySQL e um usuário com acesso a ele. Você pode importar o script:

```sql
-- Via phpMyAdmin: importe o arquivo helpdesk.sql
```

> ℹ️ **Opcional**: Se preferir, basta deixar o banco criado e vazio — o sistema **cria as tabelas automaticamente** na primeira execução do `config.php`.

### 3. Configure o `config.php`

Edite o arquivo `config.php` com as credenciais do banco e do e-mail:

```php
// Banco de dados
const DB_HOST = 'localhost';
const DB_NAME = 'helpdesk';
const DB_USER = 'seu_usuario';
const DB_PASS = 'sua_senha';
```

### 4. Configure o e-mail (SMTP/POP3)

```php
// Remetente das notificações
const MAIL_FROM_ADDRESS = 'helpdesk@seudominio.com.br';
const MAIL_FROM_NAME    = 'Help Desk Arruda';
const MAIL_ADMIN_ADDRESS = 'ti@seudominio.com.br';

// SMTP (envio)
const MAIL_SMTP_HOST = 'smtp.seudominio.com.br';
const MAIL_SMTP_PORT = 587;              // ou 465 (SSL)
const MAIL_SMTP_USER = 'helpdesk@seudominio.com.br';
const MAIL_SMTP_PASS = 'sua_senha_smtp';
const MAIL_SMTP_SECURE = 'tls';          // 'ssl', 'tls' ou '' (sem)

// POP3 (recebimento — opcional)
const MAIL_POP3_HOST = 'pop.seudominio.com.br';
const MAIL_POP3_PORT = 995;
const MAIL_POP3_USER = 'helpdesk@seudominio.com.br';
const MAIL_POP3_PASS = 'sua_senha_pop';
const MAIL_POP3_SECURE = 'ssl';
```

### 5. Permissões de pastas

Garanta que as pastas `uploads/` e `logs/` existam e tenham permissão de escrita:

```bash
chmod 755 uploads logs        # ou 775 conforme o servidor
```

### 6. Acesse o sistema

| Página | URL |
|---|---|
| Portal inicial | `https://seusite.com.br/helpdesk/inicio.php` |
| Área do cliente | `https://seusite.com.br/helpdesk/index.php` |
| Painel admin | `https://seusite.com.br/helpdesk/admin.php` |

---

## 🔧 Configuração Inicial — Acessos Padrão

Ao executar o sistema pela primeira vez, a tabela `admins` é populada automaticamente com **2 administradores iniciais** (a senha padrão é a mesma para ambos):

| Tipo | Dados |
|---|---|
| 👤 **Administradores iniciais** | 2 contas criadas automaticamente no `config.php` |
| 🔑 **Senha padrão** | `arruda@2026` |

> ⚠️ **IMPORTANTE**: Altere o e-mail e a senha dos administradores padrão logo após a primeira instalação, acessando o painel em **Admin → Usuários → Editar**.

Os usuários (clientes) podem ser criados:
- Diretamente na **página de cadastro** (`login_fun.php`), ou
- Pelo **administrador** no painel (**Usuários → Criar Novo Usuário**).

---

## 🧭 Como Usar

### Fluxo do Cliente
1. Acesse **Área do Cliente** e faça login (ou cadastre-se).
2. Clique em **Abrir Novo Ticket** e preencha assunto, responsável e mensagem.
3. Anexe uma imagem ou PDF (opcional, máx. 20MB) e envie.
4. Acompanhe seus chamados na lista **Meus Chamados** e veja os detalhes/status.

### Fluxo do Administrador
1. Acesse **Painel Administrativo** (`admin.php`) e faça login com o e-mail/senha de admin.
2. Na **Visão Geral**, acompanhe os KPIs e últimos chamados/usuários.
3. Na aba **Tickets**, busque, filtre e abra um chamado:
   - Atualize **status** e **responsável**.
   - Escreva uma **resposta** para o usuário (enviada por e-mail automaticamente).
   - Acompanhe o **histórico de respostas**.
4. Na aba **Usuários**, gerencie clientes e administradores (criar, editar, excluir).

---

## 🗂️ Banco de Dados

| Tabela | Descrição |
|---|---|
| `tickets` | Chamados/solicitações de suporte |
| `ticket_responses` | Histórico de respostas entre admin e usuário |
| `users` | Usuários (clientes) |
| `admins` | Administradores do sistema |

As tabelas são criadas/atualizadas automaticamente por `ensureDbSchema()` no `config.php` (migrações leves como adição das colunas `attachment`, `responsible` e `user_id` em `tickets`).

---

## 🗺️ Roadmap

- [ ] Interface para acionar a **importação POP3** automaticamente
- [ ] Respostas por e-mail recebidas convertidas em respostas do ticket
- [ ] Níveis de permissão refinados para admin/superadmin
- [ ] Melhorar a interface para dispositivos móveis
- [ ] Página pública de cadastro com validação aprimorada
- [ ] Relatórios e gráficos de atendimento

---

## 📄 Licença

Projeto de uso interno da **Arruda Empresarial**. Todos os direitos reservados.

© 2026 Arruda Empresarial — Help Desk. Desenvolvido para o suporte técnico interno.


