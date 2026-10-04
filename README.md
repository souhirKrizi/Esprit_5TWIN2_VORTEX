# HeatAlert

**HeatAlert** est un projet académique développé dans le cadre d’**ESPRIT**, consacré à la vigilance face aux canicules et à la prévention des risques liés à la chaleur en Tunisie.

Le projet vise à faciliter l’accès aux informations météorologiques, aux alertes et aux conseils de prévention, tout en favorisant les signalements citoyens et la participation à des campagnes de sensibilisation et de bénévolat.

## Équipe

HeatAlert est réalisé par une équipe de quatre membres :

- **Nassef Fadhlaoui**
- **Ayoub Belhameid**
- **Souhir Krizi**
- **Mustafa Charfedin Akkez**

Ce travail s’inscrit dans un cadre pédagogique à ESPRIT et permet à l’équipe de mettre en pratique le développement d’une application web avec Laravel.

## Espaces de l’application

L’application propose trois interfaces, chacune avec une navigation dédiée :

| Espace | Route | Objectif |
| --- | --- | --- |
| Citoyen | `/user` | Consulter les informations utiles et accéder aux rubriques de signalement et de participation. |
| Agent de campagne | `/agent-campagne` | Accéder aux rubriques des campagnes, des missions et des participations. |
| Administrateur | `/admin` | Accéder aux rubriques d’administration de la plateforme. |

Les interfaces disposent de sidebars adaptatives. Leurs zones principales sont actuellement vides ; les fonctionnalités métier des sous-menus restent à développer.

L’inscription et la connexion utilisent Laravel Fortify. Après connexion, l’utilisateur est redirigé vers l’espace correspondant à son rôle. Les erreurs de connexion apparaissent dans une popup, et le bouton de déconnexion invalide la session. Les routes des trois espaces restent accessibles directement à ce stade du projet.

## Technologies

- Laravel 12 et PHP 8.2 ou supérieur.
- Laravel Blade pour les vues.
- Laravel Fortify pour l’authentification.
- CSS, JavaScript et assets Tabler pour les interfaces.
- Vite et Tailwind CSS pour les assets du projet.

## Installation locale

Prérequis : PHP, Composer, Node.js, npm et une base de données configurée.

```bash
composer install
npm install
```

Copier `.env.example` vers `.env`, puis renseigner la configuration de la base de données.

```bash
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

L’application est ensuite disponible sur `http://127.0.0.1:8000`. La page de connexion se trouve à l’adresse `/signin` et celle d’inscription à `/signup`.

## Tests

```bash
php artisan test
```
