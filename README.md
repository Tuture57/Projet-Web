# Alt Internships: internship search platform

Web application where students find internship offers and apply, while pilots (tutors) and administrators manage
companies, offers and accounts. Built with Symfony 7 and Doctrine.

Team project at CESI École d'Ingénieurs (web development, January–April 2025). The interface is in French.

![Offer page](docs/screenshots/offer.png)

| Students | Applications |
|---|---|
| ![Student list](docs/screenshots/students.png) | ![Applications filtered by status](docs/screenshots/applications.png) |

## Features

- **Offers**: search bar, filters and pagination, offer pages, activation and deactivation (only active offers are
  listed), wishlist.
- **Applications**: apply with a CV and a cover letter, follow the status, accept, refuse or cancel.
- **Companies**: create, edit, delete and rate companies.
- **Accounts and roles**: administrators, pilots and students, with role-based access (Symfony Security).
- Help page, terms of use and legal notice.

## Tech stack

PHP 8.2+, Symfony 7.0, Doctrine ORM 3 and migrations, MySQL, Twig, KnpPaginatorBundle, CSS and JavaScript.

## Getting started

Requirements: PHP 8.2+, Composer, MySQL or MariaDB, and optionally the Symfony CLI.

```bash
composer install
# put your own DATABASE_URL and APP_SECRET in a .env.local file, for example:
# DATABASE_URL="mysql://user:password@127.0.0.1:3306/internships?serverVersion=8.0.32&charset=utf8mb4"
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve            # or: php -S localhost:8000 -t public
```

Default values for every variable are in [`.env.dist`](.env.dist), which Symfony loads when no `.env` file exists.

## Project structure

```
src/Controller/   offers, applications, companies, students, pilots, administrators, wishlist, pages
src/Entity/       User, Role, Company, Offer, Application, Rating, Skill, Address
templates/        Twig views, one folder per section, shared partials
migrations/       database schema history
public/           CSS, JavaScript, images
```

## Team

[Tuture57](https://github.com/Tuture57), [sashidori](https://github.com/sashidori),
[trystanbouzonrueda](https://github.com/trystanbouzonrueda).
