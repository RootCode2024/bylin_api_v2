# 🛠️ Frontend Step 1: Setup & Architecture

**Context** : Nous construisons une application e-commerce avec **Nuxt Js**, connectée à une API Laravel existante (`http://localhost:8000/api/v1`). L'API utilise Sanctum (Cookie-based auth).

**Objectif** : Initialiser le projet, configurer le design system, mettre en place le client HTTP et les types de base.

---

## 📋 Instructions pour l'IA (Prompt)

Voici les tâches précises à réaliser pour cette étape. Exécute-les dans l'ordre.

### 1. Initialisation du Projet
- verifie que le projet est bien initialisé.
- Verifie si installé, sinon Installer les modules essentiels :
  - `@nuxt/ui` (pour les composants et Tailwind)
  - `@pinia/nuxt` (pour le state management)
  - `@vueuse/nuxt` (utilitaires Vue)
  - `nuxt-icon` (icônes)

### 2. Configuration (`nuxt.config.ts`)
- Configurer le module `ui` avec le préfixe `UI` (optionnel, par défaut pas de préfixe).
- Configurer `runtimeConfig` :
  - `public.apiBase`: `http://localhost:8000/api/v1`
  - `public.appUrl`: `http://localhost:3000`
- Activer `ssr: true`.
- Configurer `devtools: { enabled: true }`.

### 3. Client HTTP (Composable `useApi`)
Créer un composable `composables/useApi.ts` qui encapsule `$fetch` :
- **Base URL** : Utiliser la config `apiBase`.
- **Credentials** : `credentials: 'include'` (CRITIQUE pour Sanctum).
- **Headers** : Ajouter `Accept: application/json`.
- **Intercepteurs** :
  - `onRequest` : Ajouter le token CSRF si nécessaire (Sanctum gère souvent via cookie auto, mais vérifier `X-XSRF-TOKEN`).
  - `onResponseError` : Gérer les erreurs 401 (Unauthorized) -> Redirection login ou refresh session.

### 4. Design System (Tailwind)
- Configurer `app.config.ts` pour définir les couleurs primaires (ex: une couleur "brand" personnalisée).
- Créer `assets/css/main.css` pour les styles globaux (fonts, reset).
- Choisir une police Google Font (ex: 'Inter' ou 'Poppins') et l'intégrer via `nuxt.config.ts`.

### 5. Types TypeScript (`types/`)
Créer les interfaces correspondant aux modèles API dans le dossier `types/` :
- `types/api.ts` : Interface générique pour les réponses API (`ApiResponse<T>`, `PaginatedResponse<T>`).
- `types/user.ts` : `User`, `Customer` (avec champs OAuth).
- `types/product.ts` : `Product`, `Category`, `Brand`, `Variation`.
- `types/cart.ts` : `Cart`, `CartItem`.

### 6. Stores Pinia (Base)
Initialiser Pinia et créer un store exemple :
- `stores/app.ts` : Pour gérer l'état global de l'UI (ex: loading, notifications toast, menu ouvert/fermé).

### 7. Layouts
Créer deux layouts :
- `layouts/default.vue` : Header (Logo, Nav, Cart Icon, User Icon) + Slot + Footer.
- `layouts/auth.vue` : Layout simplifié pour Login/Register (Centré, pas de header complexe).

---

## ✅ Critères de validation (Checklist)

- [ ] Le projet se lance avec `npm run dev` sans erreur.
- [ ] TailwindCSS est actif et les composants Nuxt UI fonctionnent.
- [ ] `useApi` permet de faire un appel GET vers l'API (ex: `/catalog/products`) et reçoit une réponse.
- [ ] Les cookies (XSRF-TOKEN, session) sont bien transmis lors des requêtes API.
- [ ] Les types TypeScript sont accessibles globalement ou importés correctement.
