# 🛍️ Workflow Frontend Nuxt (Customer App)

Ce document détaille les étapes séquentielles pour construire l'application frontend client avec **Nuxt 3**, **TailwindCSS** et **Nuxt UI**.

Chaque étape est conçue pour être réalisée de manière isolée et testable.

---

## 📑 Vue d'ensemble des étapes

| Étape | Titre | Description |
|:---|:---|:---|
| **Step 1** | **Setup & Architecture** | Initialisation, Config Nuxt, Stores Pinia, Types TypeScript, Client HTTP |
| **Step 2** | **Auth & User Flow** | Login, Register, Google OAuth, Middleware Auth, Profil Utilisateur |
| **Step 3** | **Catalogue & Navigation** | Home, Listing Produits, Filtres, Recherche, Page Détail Produit |
| **Step 4** | **Panier & Checkout** | Gestion Panier (Guest/Auth), Tunnel de commande, Paiement FedaPay |
| **Step 5** | **Compte Client** | Commandes, Adresses, Favoris, Avis, Notifications |
| **Step 6** | **Optimisation & SEO** | Meta tags, Sitemap, Performance, PWA, Error Handling |

---

## 🚀 Détail des Étapes

### [Step 1] Setup & Architecture
> **Objectif** : Avoir une base solide, typée et configurée pour communiquer avec l'API.
- Initialisation Nuxt 3 + Nuxt UI
- Configuration TailwindCSS (Design System)
- Setup Pinia (State Management)
- Configuration `$fetch` (Intercepteur API avec gestion des cookies)
- Définition des Interfaces TypeScript (Models)
- Layouts de base (Default, Auth)

### [Step 2] Auth & User Flow
> **Objectif** : Gérer l'identification sécurisée des utilisateurs.
- Store `useAuthStore`
- Pages Login / Register / Forgot Password
- Intégration Google OAuth (Redirection & Callback)
- Middleware `auth` pour routes protégées
- Gestion de la session (Persistance & Refresh)

### [Step 3] Catalogue & Navigation
> **Objectif** : Permettre aux utilisateurs de découvrir les produits.
- Components UI (ProductCard, PriceDisplay, Badge)
- Page d'accueil (Hero, Featured Products, Categories)
- Page Listing (Grid, Pagination, Filtres latéraux)
- Page Produit (Galerie images, Sélecteur variations, Description)
- Store `useCatalogStore`

### [Step 4] Panier & Checkout
> **Objectif** : Convertir les visiteurs en acheteurs.
- Store `useCartStore` (Sync API)
- Slideover Panier (Mini-cart)
- Page Panier complet
- Tunnel de commande (Adresse -> Livraison -> Paiement)
- Intégration Widget FedaPay
- Page de confirmation de commande

### [Step 5] Compte Client
> **Objectif** : Fidéliser et gérer l'après-vente.
- Dashboard Client
- Gestion des Adresses (CRUD)
- Historique des Commandes & Détails
- Wishlist (Favoris)
- Gestion des Avis (Reviews)

### [Step 6] Optimisation & SEO
> **Objectif** : Performance et visibilité.
- Configuration `useHead` & SEO dynamique
- Gestion des erreurs (404, 500)
- Transitions de page
- Lazy loading des images
- Vérification Mobile Responsiveness

---

*Pour commencer, référez-vous au fichier `FRONTEND_STEP_1.md` pour les instructions détaillées de la première étape.*
