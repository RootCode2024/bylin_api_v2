# 🏠 Intégration API Home Content

Ce document détaille comment consommer le nouvel endpoint unique pour la page d'accueil et la navigation globale.

## 📡 Endpoint

**POST** `GET /api/v1/content/home`

Cette route retourne toutes les données nécessaires pour :

1.  Le Header navigation (Menu principal, Mega Menus).
2.  Le contenu de la Page d'Accueil (Hero, Catégories, Collection, etc.).

## 📦 Types TypeScript

Ajoutez ces interfaces dans `types/home.ts` ou `types/api.ts` pour typer la réponse.

```typescript
export interface HomeContentResponse {
    success: boolean;
    data: {
        navigation: NavigationItem[];
        home_content: HomeContent;
    };
}

// --- Navigation ---

export interface NavigationItem {
    label: string;
    url: string;
    mega_menu: MegaMenuColumn[] | null;
}

export interface MegaMenuColumn {
    title: string;
    type: "links" | "image";
    links?: Array<{ label: string; url: string }>;
    bottom_link?: { label: string; url: string };
    // Si type === 'image'
    image_url?: string;
    image_label?: string;
}

// --- Home Content ---

export interface HomeContent {
    hero: HeroSection;
    categories_explorer: CategoriesExplorer;
    new_collection_scroll: CollectionScroll;
    shop_selection: ShopSelection;
    faq: FaqItem[];
}

export interface HeroSection {
    title_line_1: string;
    title_line_2: string;
    collection_tag: string;
    location: string;
    background_image: string;
}

export interface CategoriesExplorer {
    display_title: string;
    subtitle: string;
    items: Array<{
        name: string;
        image: string;
        url: string;
    }>;
}

export interface CollectionScroll {
    sidebar_text: string;
    intro: {
        title_line_1: string;
        title_line_2: string;
        description: string;
    };
    looks: ProductLook[];
    see_more_link: { label: string; url: string };
}

export interface ProductLook {
    id: number;
    name: string;
    price_formatted: string;
    material: string;
    image: string;
    tag?: string;
    url: string;
}

export interface ShopSelection {
    title: string;
    items: ProductLook[];
}

export interface FaqItem {
    q: string;
    a: string;
}
```

## 💻 Usage (Composable Example)

Dans votre composable `useLayoutData` ou directement dans `app.vue` / `pages/index.vue`.

```typescript
// app.vue ou layout/default.vue
<script setup lang="ts">
import type { HomeContentResponse } from '~/types/home'

const { data: homeData, error } = await useApi<HomeContentResponse>('/content/home')

// Extraire la navigation pour le Header
const navigation = computed(() => homeData.value?.data.navigation || [])
</script>
```

```typescript
// pages/index.vue
<script setup lang="ts">
definePageMeta({
  layout: 'default'
})

// Récupérer les données si elles ont déjà été fetchées au niveau root,
// sinon refetch (Nuxt useAsyncData dé-dupliquera automatiquement si la clé est la même)
const { data } = await useApi<HomeContentResponse>('/content/home')

const content = computed(() => data.value?.data.home_content)
</script>

<template>
  <div v-if="content">
    <HeroSection :data="content.hero" />
    <CategoryExplorer :data="content.categories_explorer" />
    <NewCollection :data="content.new_collection_scroll" />
    <ProductGrid :title="content.shop_selection.title" :items="content.shop_selection.items" />
    <FaqSection :items="content.faq" />
  </div>
</template>
```

## 🎨 Mapping Composants

Suggestions de découpage Vue :

1.  **`MainHeader.vue`**
    -   Iterate sur `navigation`.
    -   Si `item.mega_menu` existe, afficher un composant `MegaMenuOverlay`.
2.  **`HeroSection.vue`**
    -   Props: `hero` (Object).
    -   Affiche l'image de fond et les titres.
3.  **`CategoryExplorer.vue`**
    -   Props: `items` (Array).
    -   Une grille de 3 cartes verticales.
4.  **`LookScroll.vue`** (pour `new_collection_scroll`)
    -   Horizontal scroll container.
    -   Affiche les "looks" (produits simplifiés).
