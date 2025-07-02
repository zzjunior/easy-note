# 📋 Easy Note

Um sistema simples feito pra estudar PHP, Slim Framework e algumas coisas de frontend. Nada muito sério.

## O que tem aqui

- Boards e listas tipo Trello
- Cards que você pode arrastar e soltar
- Tags coloridas pros cards
- Um sisteminha de automações básico mas escalavél
- Interface responsiva que funciona no celular

## 🚀 Tecnologias

- **Backend**: PHP 8+ com Slim Framework 4
- **Database**: SQLite
- **Frontend**: HTML5, CSS3 (Tailwind), JavaScript Vanilla
- **Dependências**: Composer para gerenciamento de pacotes

## Como usar

1. Clona o repositório:
```bash
git clone https://github.com/zzjunior/easy-note.git
cd easy-note
```

2. Instala as dependências:
```bash
composer install
```

3. Roda o servidor:
```bash
php -S localhost:8000 -t public
```

4. Acessa no navegador: `http://localhost:8000`

O banco SQLite é criado automaticamente na pasta `db/`.

## Estrutura

```
├── public/           # Frontend (HTML, CSS, JS)
├── src/              # Backend PHP
├── db/               # Banco SQLite
└── sql/              # Scripts do banco
```

## Tech stack

- PHP com Slim Framework
- SQLite (banco simples)
- HTML/CSS/JS puro
- Tailwind pro CSS

## API

Se quiser mexer na API, tem estes endpoints:

**Boards:** GET/POST `/boards`
**Listas:** GET/POST/PUT/DELETE em `/lists`
**Cards:** GET/POST/PUT/DELETE em `/cards`
**Tags:** GET/POST/PUT/DELETE em `/tags`

## Sobre o projeto

É só um projeto pessoal pra estudar algumas coisas. Não é nada profissional, mas funciona.

Usei PHP porque quis praticar, SQLite porque é simples, e o frontend é bem básico mesmo.

Se quiser contribuir ou melhorar alguma coisa, fique à vontade!

## Como rodar

1. `git clone` do repositório
2. `composer install`
3. `php -S localhost:8000 -t public`
4. Acessa no navegador

Pronto!
