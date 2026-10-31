# My Label Manager

Back-office SaaS pour labels de musique indépendants : catalogue centralisé, ventes Shopify, Bandcamp et distributeurs (digitaux et physiques) consolidées, relevés de royalties et de reversements automatiques, portail pour les partenaires (artistes, labels, autres fournisseurs).

## Documentation

- [Note de cadrage](docs/cadrage.md)
- [Spécifications du MVP](docs/specs-mvp.md)

## Suivi du projet

Le backlog est organisé en [jalons](https://github.com/cpuchalver/my-label-manager/milestones) (un par lot) et en [issues](https://github.com/cpuchalver/my-label-manager/issues) (une epic par module), et suivi sur le [tableau du projet](https://github.com/users/cpuchalver/projects/3).

## Stack

Basé sur le start-kit [`cpuchalver/filament-label-manager`](https://github.com/cpuchalver/filament-label-manager) :

- Laravel 13, Filament 5, Livewire 4, PHP 8.4
- Panel application à la racine `/`, panel administrateur plateforme sur `/admin` (réservé aux utilisateurs `is_admin`)
- Pest, Pint, CI GitHub Actions (tests et style)
- Laravel Boost et règles de code Spatie pour le développement assisté

## Installation locale

Prérequis : PHP 8.4, Composer, Node.js et npm.

```bash
git clone https://github.com/cpuchalver/my-label-manager.git
cd my-label-manager
composer run setup
php artisan db:seed
```

`composer run setup` installe les dépendances, crée le fichier `.env` et la clé, migre la base (SQLite par défaut) et compile les assets. Le seeder crée un administrateur `test@example.com` / `password`, à n'utiliser qu'en local.

## Développement

```bash
composer run dev              # serveur, files d'attente, logs et Vite
php artisan test --compact    # tests
vendor/bin/pint               # mise en forme du code
```
