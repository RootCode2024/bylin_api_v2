# 🚀 API E-commerce (Version 2)

Bienvenue sur l'API backend de la plateforme e-commerce V2 de bylin. Cette API est construite avec **Laravel 12** et suit une architecture modulaire stricte pour garantir scalabilité et maintenabilité.

## 📋 Table des Matières

- [Architecture](#-architecture)
- [Modules](#-modules)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [API Documentation](#-api-documentation)
- [Authentification](#-authentification)
- [Tests](#-tests)

## 🏗 Architecture

Le projet utilise une architecture **Modulaire** (Domain Driven Design simplifié). Chaque fonctionnalité majeure est isolée dans son propre module sous `app/Modules/`.

Structure :
```
app/Modules/
├── Cart/           # Gestion du panier (Guest & Customer)
├── Catalogue/      # Produits, Catégories, Marques
├── Core/           # Classes de base, Traits partagés
├── Customer/       # Gestion des clients, Adresses
├── Notification/   # Emails, SMS, Notifications internes
├── Order/          # Commandes, Factures
├── Payment/        # Intégration FedaPay, Transactions
├── Reviews/        # Avis et Notes produits
├── Security/       # Login History, Device Detection
├── Shipping/       # Méthodes de livraison
└── User/           # Admin Users, Rôles & Permissions
```

## 📦 Modules

### 1. Catalogue
Gestion complète des produits avec variations (taille, couleur), gestion des stocks, catégories hiérarchiques et marques.
- Support des produits "Featured"
- Gestion des stocks et seuils d'alerte
- Système de précommande

### 2. Cart (Panier)
Système de panier hybride :
- **Guest Cart** : Basé sur session (Redis)
- **Customer Cart** : Persistant en base de données
- **Auto-merge** : Fusion automatique du panier invité lors de la connexion

### 3. Order (Commandes)
Flux de commande complet :
- Checkout avec validation de stock
- Support de multiples méthodes de paiement (FedaPay)
- Gestion des statuts de commande
- Annulation avec motif

### 4. Customer & Auth
- Authentification via **Sanctum** (SPA Cookie-based)
- **Google OAuth** intégré (Socialite)
- Gestion de profil et adresses multiples
- Historique de connexion et détection d'appareils suspects

### 5. Security
- Protection contre le Brute Force
- Détection de changement d'IP/Pays
- Logging complet des activités

## 🛠 Installation

### Prérequis
- PHP 8.2+
- Composer
- PostgreSQL
- Redis

### Étapes

1. **Cloner le projet**
   ```bash
   git clone <repo-url>
   cd api-version-deux
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configuration de l'environnement**
   ```bash
   cp .env.example .env
   # Configurer DB_*, REDIS_*, MAIL_*, GOOGLE_*, FEDAPAY_* dans .env
   ```

4. **Générer la clé d'application**
   ```bash
   php artisan key:generate
   ```

5. **Migration et Seed**
   ```bash
   php artisan migrate --seed
   ```

6. **Lancer le serveur**
   ```bash
   php artisan serve
   ```

## ⚙ Configuration

### Services Tiers
- **FedaPay** : Paiements (Sandbox/Live)
- **Google OAuth** : Connexion sociale
- **Redis** : Cache, Sessions, Queues
- **Mail** : SMTP (Gmail/Mailgun)

## 📚 API Documentation

La liste complète des endpoints est disponible dans [API_ROUTES.md](./API_ROUTES.md).

### Points clés :
- **Base URL** : `/api/v1`
- **Auth Admin** : `/api/v1/auth/admin/*`
- **Auth Customer** : `/api/v1/auth/customer/*`
- **Public** : `/api/v1/catalog/*`

## 🔐 Authentification

L'API utilise Laravel Sanctum en mode SPA (Stateful).

1. **CSRF Protection** : Appel initial à `/sanctum/csrf-cookie` requis.
2. **Login** : `POST /api/v1/auth/customer/login`
3. **Google** : `GET /api/v1/auth/customer/google/redirect`

## 🧪 Tests

```bash
php artisan test
```

---
*Développé avec ❤️ par Chrislain AVOCEGAN pour Bylin Style.*
