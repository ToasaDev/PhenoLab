# PhenoLab

A web application for managing plant observations and site layouts, built with Laravel and Vue.js.

---

## English

### Features

- Plant and species management via the GBIF API
- Site and layout management
- Phenological observation tracking
- RESTful API (Laravel Sanctum authentication)
- Reactive frontend with Vue.js and Vite
- MariaDB / MySQL database

### Tech Stack

| Layer      | Technology            |
|------------|-----------------------|
| Backend    | Laravel 11, PHP 8.2+  |
| Frontend   | Vue.js 3, Vite        |
| Database   | MariaDB / MySQL       |
| Auth       | Laravel Sanctum       |
| CSS        | Tailwind CSS          |
| Runtime    | Node.js 18+           |

### Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- MariaDB 10.6+ or MySQL 8+

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-username/phenolab.git
cd phenolab

# 2. Install PHP dependencies
composer install

# 3. Configure the environment
cp .env.example .env
php artisan key:generate

# 4. Edit .env and set your database credentials
#    DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Run migrations
php artisan migrate

# 6. Install JS dependencies
npm install

# 7. Start the dev server
npm run dev

# 8. Start the Laravel server
php artisan serve
```

The application will be available at `http://localhost:8000`.

### Production Build

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

---

## Français

### Fonctionnalités

- Gestion des plantes et des espèces via l'API GBIF
- Gestion des sites et des plans de site
- Suivi des observations phénologiques
- API RESTful (authentification Laravel Sanctum)
- Interface réactive avec Vue.js et Vite
- Base de données MariaDB / MySQL

### Stack technique

| Couche     | Technologie           |
|------------|-----------------------|
| Backend    | Laravel 11, PHP 8.2+  |
| Frontend   | Vue.js 3, Vite        |
| Base de données | MariaDB / MySQL  |
| Auth       | Laravel Sanctum       |
| CSS        | Tailwind CSS          |
| Runtime    | Node.js 18+           |

### Prérequis

- PHP 8.2+
- Composer
- Node.js 18+
- MariaDB 10.6+ ou MySQL 8+

### Installation

```bash
# 1. Cloner le dépôt
git clone https://github.com/your-username/phenolab.git
cd phenolab

# 2. Installer les dépendances PHP
composer install

# 3. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 4. Modifier .env et renseigner les identifiants de base de données
#    DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Exécuter les migrations
php artisan migrate

# 6. Installer les dépendances JS
npm install

# 7. Lancer le serveur de développement
npm run dev

# 8. Lancer le serveur Laravel
php artisan serve
```

L'application sera disponible sur `http://localhost:8000`.

### Build production

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Sécurité — honeypot login

Le formulaire de login affiche volontairement `admin / admin123` comme leurre.
Toute tentative utilisant un username ou un mot de passe figurant dans la liste
honeypot (`admin`, `root`, `test`, `demo`, `admin123`, `password`, `123456`...)
est :

- toujours rejetée (même si un user existe avec ces credentials),
- enregistrée dans la table `login_attempts` (IP, user-agent, raison),
- ralentie par un délai aléatoire pour décourager le bruteforce.

Au bout de 3 hits honeypot, l'IP est bloquée 24 h pour toute tentative de
connexion (`reason = blocked_ip`).

Le vrai compte admin doit être créé/rotaté via :

```bash
php artisan security:rotate-admin
# Renomme l'admin et génère un mot de passe aléatoire fort.
# Les credentials sont affichés UNE seule fois — note-les.
```

Pour consulter les tentatives :

```bash
# Les 30 dernières tentatives (toutes raisons confondues)
php artisan security:login-attempts

# Honeypot uniquement
php artisan security:login-attempts --honeypot

# Top des IPs les plus actives
php artisan security:login-attempts --top-ips

# Filtrer par IP
php artisan security:login-attempts --ip=192.168.1.42

# Sur une fenêtre temporelle
php artisan security:login-attempts --since="1 hour ago"
php artisan security:login-attempts --since="2026-04-01" --honeypot --limit=100
```

Ou via l'admin Filament : **/admin → Système → Tentatives de connexion**
(visible uniquement par les superusers, badge rouge = hits honeypot des 24 h).

> ⚠️ Derrière un reverse-proxy (nginx, Cloudflare), pense à configurer
> `app/Http/Middleware/TrustProxies.php` pour que `request()->ip()` retourne
> l'IP cliente réelle via `X-Forwarded-For` au lieu de `127.0.0.1`.

### Licence

Ce projet est distribué sous licence MIT. Voir [LICENSE](LICENSE) pour plus de détails.
