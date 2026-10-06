<p align="center">
  <img src="public/assets/images/Logo_HeatAlert_avec_flamme_integree.png" alt="Logo HeatAlert avec flamme intégrée" width="360">
</p>

# HeatAlert

**HeatAlert** est une application web de vigilance citoyenne et de prévention des risques liés aux fortes chaleurs en Tunisie. Développée avec **Laravel 12** dans le cadre d’un projet académique à **ESPRIT**, elle rassemble une interface de sensibilisation, des ressources géolocalisées, des signalements citoyens, des conseils de prévention et des campagnes de bénévolat.

La plateforme organise la coopération entre trois profils : les citoyens signalent les difficultés rencontrées sur le terrain et participent aux missions ; les agents de campagne organisent les missions des campagnes qui leur sont attribuées ; les administrateurs gèrent les comptes, les campagnes, la modération et les contenus de prévention.

Le projet traite notamment des coupures d’électricité, des points d’eau hors service, des centres d’accueil fermés et de la protection des équipements sensibles à la chaleur ou aux coupures de courant.

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Espaces et accès](#espaces-et-accès)
- [Technologies](#technologies)
- [Installation locale](#installation-locale)
- [Données de démonstration](#données-de-démonstration)
- [Structure du projet](#structure-du-projet)
- [Tests et vérifications](#tests-et-vérifications)
- [État actuel du projet](#état-actuel-du-projet)
- [Équipe](#équipe)

## Fonctionnalités

### Accueil et sensibilisation

La page d’accueil présente HeatAlert et une interface de vigilance par gouvernorat : température, température ressentie, humidité, vent, indice UV et niveau d’alerte. **Les valeurs météorologiques affichées sont actuellement des données de démonstration définies dans la vue**, sans connexion à un service de météo en temps réel.

### Comptes et authentification

- Inscription avec nom, adresse e-mail, gouvernorat, mot de passe confirmé, acceptation des conditions et préférence pour les alertes par e-mail.
- Attribution automatique du rôle citoyen (`user`) lors de l’inscription.
- Connexion avec Laravel Fortify et redirection vers l’espace correspondant au rôle du compte.
- Mot de passe d’inscription d’au moins huit caractères, contenant une majuscule et un chiffre.
- Réinitialisation par un code à six chiffres envoyé par e-mail, valable quinze minutes, avec limitation des tentatives et délai entre deux envois.
- Administration des comptes : recherche, consultation, modification, changement de rôle et archivage ou désarchivage. Un administrateur ne peut pas archiver son propre compte ni retirer son propre rôle administrateur.

### Signalements citoyens et ressources utiles

Un citoyen peut déclarer un **incident** ou confirmer le bon fonctionnement d’une ressource (`ressource_ok`). Le signalement comprend une position géographique, un commentaire et, éventuellement, une ressource associée et des photos.

- Types d’incident : coupure d’électricité, point d’eau hors service, centre fermé ou autre incident.
- Coordonnées limitées à une latitude entre 30 et 38 et une longitude entre 7 et 12.
- Pour un incident, commentaire obligatoire de 10 à 1 000 caractères.
- Jusqu’à cinq images JPG, JPEG, PNG ou WEBP, de 5 Mo maximum chacune et d’au moins 200 × 200 pixels.
- Suivi des signalements personnels ; modification ou suppression par leur auteur tant qu’ils sont en attente.
- Modération par un administrateur : validation ou rejet avec motif ; statuts `en_attente`, `valide` et `rejete`.
- Consultation des signalements validés des autres citoyens depuis l’espace connecté.
- Limitation de la création à dix requêtes par minute.

La carte interactive utilise **Leaflet** et les fonds de carte **OpenStreetMap** pour localiser les points d’eau, espaces de fraîcheur et centres d’accueil. Les fiches des ressources présentent leur adresse, leur gouvernorat et l’historique des signalements validés.

L’indicateur d’une ressource est rouge si elle possède au moins un incident validé, sinon vert. Cet indicateur repose sur les signalements enregistrés ; il ne constitue pas une mesure en temps réel de sa disponibilité.

### Campagnes, missions et bénévolat

Les administrateurs créent, modifient et suppriment les campagnes, puis les attribuent à un agent de campagne. Une campagne comporte un titre, une description, un objectif, un lieu, des dates, un statut et une image facultative.

Les agents consultent les campagnes qui leur sont affectées et gèrent leurs missions : titre, description, lieu, dates, nombre de places et statut. Ils peuvent également consulter les participants et leurs informations de participation.

Les citoyens consultent les campagnes et leurs missions, s’inscrivent avec une motivation facultative et retrouvent leurs inscriptions dans « Mes participations ». L’inscription vérifie l’ouverture de la mission, le statut de la campagne, la date de fin et les places disponibles. Des transactions et des verrouillages encadrent les inscriptions pour éviter les doublons et les dépassements de capacité.

| Élément | Statuts pris en charge |
| --- | --- |
| Campagne | `brouillon`, `publiee`, `en_cours`, `terminee`, `annulee` |
| Mission | `ouverte`, `complete`, `en_cours`, `terminee`, `annulee` |

### Conseils de prévention

Les comptes connectés peuvent consulter des fiches publiées, rechercher un titre et filtrer par thématique. Chaque fiche peut préciser un niveau d’alerte, un type d’équipement concerné et des gestes clés ordonnés **avant, pendant et après** l’événement.

Les thématiques couvrent l’hydratation, l’économie d’énergie, les coupures de courant, les équipements sensibles, la fraîcheur du logement et les personnes fragiles.

- **Fiches et gestes clés** : les administrateurs gèrent les contenus, leur ordre et leur publication ; les brouillons ne sont pas visibles aux autres profils.
- **Questions citoyennes** : un compte connecté peut poser une question sur une fiche publiée, suivre ses questions et les modifier ou supprimer tant qu’elles n’ont pas reçu de réponse. Les administrateurs répondent ; les réponses sont affichées sur la fiche concernée.
- **Équipements sensibles** : chaque compte connecté peut gérer ses équipements personnels avec leur nom, type, sensibilité à la chaleur ou aux coupures, autonomie éventuelle en heures et description.

Les types d’équipement prévus sont les réfrigérateurs ou congélateurs, appareils médicaux, climatiseurs ou ventilateurs, équipements internet ou téléphoniques, et appareils informatiques ou téléviseurs.

### Météo et alertes canicule

La première version du module d’administration gère trois entités : les zones (un gouvernorat tunisien et sa géométrie GeoJSON), les relevés météo manuels (observation ou prévision) et les alertes manuelles (niveau, statut et période de validité). La carte Leaflet représente les limites des 24 gouvernorats ; une zone prend la couleur de l’alerte active la plus forte, si sa période de validité inclut l’heure actuelle. Les autres zones restent neutres.

- Carte : `/admin/canicule/carte`.
- Alertes : `/admin/canicule/alertes`.
- Relevés météo : `/admin/canicule/releves`.
- Zones : `/admin/canicule/zones`.

Ces pages sont réservées aux administrateurs. La collecte par API météo, les notifications automatiques et le déclenchement automatique d’alertes ne sont pas encore activés. Les limites administratives embarquées proviennent de [geoBoundaries ADM1 Tunisia](https://www.geoboundaries.org/api/current/gbOpen/TUN/ADM1/), source OpenStreetMap/Wambacher, licence ODbL 1.0 ; les fonds de carte sont fournis par OpenStreetMap.

## Espaces et accès

| Profil | Rôle technique | Point d’entrée | Fonctions principales |
| --- | --- | --- | --- |
| Citoyen | `user` | `/user` | Signalements, carte, ressources, campagnes, inscriptions et prévention. |
| Agent de campagne | `agent-campagne` | `/agent-campagne` | Campagnes attribuées, gestion des missions et consultation des participants. |
| Administrateur | `admin` | `/admin` | Comptes, campagnes, suivi des missions, modération et contenus de prévention. |

Ces points d’entrée nécessitent une connexion et redirigent vers l’espace personnel du compte, dont l’URL contient son identifiant. L’accès à cet espace vérifie l’identité et le rôle. Les contrôleurs métier et les politiques d’autorisation contrôlent les rôles et la propriété des données selon le module.

Quelques routes utiles :

| Route | Usage |
| --- | --- |
| `/` | Accueil public. |
| `/signin`, `/signup` | Connexion et inscription. |
| `/forgot-password`, `/reset-password` | Réinitialisation du mot de passe. |
| `/user/signalements`, `/user/signalements/carte` | Signalements personnels et carte citoyenne. |
| `/user/signalements/tous` | Signalements validés des autres citoyens. |
| `/user/campagnes`, `/user/participations` | Campagnes et participations du citoyen. |
| `/agent-campagne/campagnes`, `/agent-campagne/missions` | Campagnes et missions de l’agent. |
| `/user/conseils`, `/user/mes-questions`, `/user/mes-equipements` | Prévention pour les comptes connectés. |
| `/admin/users`, `/admin/campagne`, `/admin/missions` | Administration des comptes et campagnes, suivi des missions. |
| `/admin/signalements`, `/admin/signalements/carte` | Modération et carte administrateur. |
| `/admin/canicule/carte`, `/admin/canicule/alertes`, `/admin/canicule/releves`, `/admin/canicule/zones` | Carte colorée et gestion météo/canicule. |
| `/admin/conseils`, `/admin/questions` | Administration de la prévention. |

## Technologies

| Couche | Technologies |
| --- | --- |
| Serveur | PHP 8.2 ou supérieur, Laravel 12. |
| Authentification | Laravel Fortify. |
| Vues et interfaces | Blade, CSS, JavaScript et assets Tabler. |
| Compilation des assets | Vite 7, Tailwind CSS 4, plugin Laravel Vite. |
| Cartographie | Leaflet 1.9.4 et OpenStreetMap. |
| Persistance | Eloquent, migrations Laravel ; SQLite par défaut dans `.env.example`. |
| Tests et qualité | PHPUnit 11 et Laravel Pint. |

## Installation locale

### Prérequis

- PHP 8.2 ou supérieur et Composer, avec les extensions requises par les dépendances ; pour SQLite, activer notamment `pdo_sqlite` et `sqlite3`.
- Node.js compatible avec Vite 7, par exemple Node.js 22.12 ou supérieur, et npm.
- Une base SQLite locale ou une autre base configurée dans `.env`.

### Installation et configuration

Depuis le dossier du projet :

```bash
composer install
npm install
```

Créer `.env` à partir du fichier d’exemple si ce fichier n’existe pas encore. Sous PowerShell :

```powershell
Copy-Item .env.example .env
```

Sous Linux ou macOS :

```bash
cp .env.example .env
```

Adapter les paramètres de l’application :

```dotenv
APP_NAME=HeatAlert
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
```

Avec SQLite, s’assurer que `database/database.sqlite` existe. Cette commande crée le fichier seulement s’il est absent :

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

Pour utiliser MySQL, remplacer la connexion par `DB_CONNECTION=mysql`, renseigner `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` et `DB_PASSWORD`, puis créer la base correspondante.

Préparer l’application et compiler les assets :

```bash
php artisan key:generate
php artisan migrate
php artisan storage:link
npm run build
php artisan serve
```

Le lien de stockage permet d’afficher les images des campagnes et les pièces jointes des signalements. L’application est accessible sur **http://127.0.0.1:8000**.

### Développement et e-mails

Pour travailler avec le rechargement des assets, lancer `php artisan serve` et `npm run dev` dans deux terminaux. Le script `composer dev` est également fourni pour lancer le serveur, le traitement des files d’attente, les logs avec Pail et Vite ; Pail nécessite l’extension PHP `pcntl`, généralement indisponible sous Windows natif.

Par défaut, `.env.example` utilise `MAIL_MAILER=log` : les e-mails, dont les codes de réinitialisation, sont écrits dans les logs de l’application, généralement `storage/logs/laravel.log`. Pour recevoir les messages dans une boîte e-mail, configurer un transport SMTP et ses paramètres `MAIL_*` dans `.env`.

## Données de démonstration

Après les migrations, deux seeders peuvent alimenter les modules :

```bash
php artisan db:seed --class=SignalementsSeeder
php artisan db:seed --class=PreventionSeeder
```

- `SignalementsSeeder` ajoute des ressources géolocalisées et des signalements d’exemple. Il nécessite au moins un compte de rôle `user` ; sans citoyen, il s’arrête sans créer de données.
- `PreventionSeeder` ajoute des fiches et gestes clés, puis des équipements et questions d’exemple lorsqu’un citoyen existe.
- `ZonesSeeder` ajoute ou actualise les géométries GeoJSON des 24 gouvernorats ; il est également appelé par le seeder général.

Pour installer ou actualiser les zones seules :

```bash
php artisan db:seed --class=ZonesSeeder
```

La commande générale `php artisan db:seed` crée un utilisateur de test et appelle `SignalementsSeeder`. Elle **n’appelle pas** `PreventionSeeder` et ne crée pas de compte administrateur ni d’agent de campagne. Elle n’est pas prévue pour être relancée telle quelle, car elle recrée la même adresse e-mail de test.

Pour essayer les espaces de gestion en local, créer des comptes depuis `/signup`, puis attribuer les rôles nécessaires avec Tinker :

```bash
php artisan tinker
```

Dans la console Tinker, remplacer les adresses ci-dessous par celles des comptes créés :

```php
App\Models\User::where('email', 'admin@example.com')->firstOrFail()->update(['role' => 'admin']);
App\Models\User::where('email', 'agent@example.com')->firstOrFail()->update(['role' => 'agent-campagne']);
```

L’administrateur peut ensuite gérer les rôles depuis `/admin/users`, créer une campagne et l’attribuer à l’agent.

## Structure du projet

```text
app/
├── Actions/Fortify/       Validation et création des comptes
├── Http/Controllers/     Contrôleurs des espaces et modules métier
├── Http/Requests/        Validation des signalements et de la prévention
├── Models/               Modèles Eloquent et relations
└── Policies/             Autorisations sur les signalements
database/
├── migrations/           Schéma de la base de données
├── factories/            Données pour les tests
└── seeders/              Données de démonstration
public/assets/            Logo, styles, scripts et assets d’interface
resources/views/          Vues Blade publiques, citoyennes, agents et administrateur
routes/web.php            Routes web de l’application
tests/Feature/            Tests fonctionnels, dont le module de prévention
tests/Unit/               Tests unitaires
```

Les principales relations relient une campagne à son organisateur et à ses missions, une mission à ses participations, un signalement à son auteur, à une ressource facultative et à ses pièces jointes, et une fiche de prévention à ses gestes clés et questions.

## Tests et vérifications

Lancer la suite de tests :

```bash
php artisan test
```

Le script `composer test` efface également le cache de configuration avant d’exécuter les tests. La configuration PHPUnit utilise une base SQLite en mémoire, un transport e-mail de test et des sessions et caches en mémoire.

Les tests fonctionnels présents couvrent notamment la connexion, la gestion des utilisateurs, les campagnes, les missions, les participations, les signalements, les ressources utiles, les fiches de prévention, les équipements sensibles et les questions citoyennes.

Autres commandes utiles :

```bash
php artisan route:list
npm run build
```

## État actuel du projet

Les modules métier décrits ci-dessus disposent de routes, contrôleurs, modèles et vues. Les pages d’entrée des espaces servent de structure de navigation ; les fonctionnalités se trouvent dans les rubriques dédiées.

La météo de l’accueil reste une démonstration statique. La préférence d’alertes par e-mail est enregistrée à l’inscription, mais aucun service d’envoi automatique d’alertes météorologiques n’est implémenté dans le code actuel. Les ressources et signalements fournis par les seeders sont des exemples pour le développement et la démonstration.

Les cartes nécessitent une connexion internet pour charger Leaflet depuis son CDN et les tuiles OpenStreetMap.

## Équipe

HeatAlert est réalisé par une équipe de quatre membres :

- **Nassef Fadhlaoui**
- **Ayoub Belhameid**
- **Souhir Krizi**
- **Mustafa Charfedin Akkez**

Ce projet pédagogique à **ESPRIT** permet de mettre en pratique la conception d’une application Laravel, la gestion des rôles, la modélisation des données, la validation des formulaires et les tests fonctionnels.
