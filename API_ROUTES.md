# 📚 Documentation API v1

Cette documentation liste les principaux endpoints disponibles dans l'API.

**Base URL:** `/api/v1`

---

## 🔐 Authentification & Headers

### Headers Requis
- `Accept: application/json`
- `Content-Type: application/json`

### Authentification Client
Pour les routes protégées (`/customer/*`) :
- `Authorization: Bearer <token>`

### Panier Invité
Pour gérer le panier sans être connecté :
- `X-Session-ID: <uuid>` (Généré automatiquement par le serveur lors du premier appel, à renvoyer ensuite)

---

## 🛒 Module Cart (Panier)

Le panier est persistant et gère automatiquement la fusion Invité -> Client lors de la connexion.

| Méthode | Endpoint | Description | Payload |
|:---|:---|:---|:---|
| `GET` | `/customer/cart` | Récupérer le panier actuel | - |
| `POST` | `/customer/cart/items` | Ajouter un article | `{ "product_id": "uuid", "quantity": 1, "variation_id": "opt-uuid" }` |
| `PUT` | `/customer/cart/items/{itemId}` | Modifier quantité | `{ "quantity": 3 }` |
| `DELETE` | `/customer/cart/items/{itemId}` | Retirer un article | - |
| `DELETE` | `/customer/cart` | Vider le panier | - |
| `POST` | `/customer/cart/coupon` | Appliquer un code promo | `{ "coupon_code": "WELCOME10" }` |
| `DELETE` | `/customer/cart/coupon` | Retirer le code promo | - |

---

## 📦 Module Order (Commandes)

### Client
| Méthode | Endpoint | Description | Payload |
|:---|:---|:---|:---|
| `GET` | `/customer/orders` | Liste des commandes | `?page=1&per_page=15` |
| `GET` | `/customer/orders/{id}` | Détails d'une commande | - |
| `POST` | `/customer/orders` | Créer une commande (Checkout) | *Voir ci-dessous* |
| `POST` | `/customer/orders/{id}/cancel` | Annuler une commande | `{ "reason": "Changed my mind" }` |

**Payload Création Commande :**
```json
{
  "shipping_address": {
    "first_name": "John",
    "last_name": "Doe",
    "address_line1": "123 Rue Cotonou",
    "city": "Cotonou",
    "phone": "+229 97000000"
  },
  "billing_address": { ... }, // Optionnel
  "payment_method": "fedapay",
  "customer_email": "john@example.com",
  "customer_phone": "+229 97000000",
  "customer_note": "Code porte 1234"
}
```

### Admin
| Méthode | Endpoint | Description |
|:---|:---|:---|
| `GET` | `/admin/orders` | Liste globale (filtres: status, search) |
| `GET` | `/admin/orders/{id}` | Détails complets |
| `PUT` | `/admin/orders/{id}/status` | Changer le statut |

---

## 💳 Module Payment (Paiement)

| Méthode | Endpoint | Description |
|:---|:---|:---|
| `POST` | `/webhooks/fedapay` | Webhook FedaPay (Public) |
| `GET` | `/admin/payments` | Liste des transactions (Admin) |
| `POST` | `/admin/payments/{id}/refund` | Effectuer un remboursement (Admin) |

---

## ⭐ Module Reviews (Avis)

### Client
| Méthode | Endpoint | Description | Payload |
|:---|:---|:---|:---|
| `GET` | `/customer/reviews` | Mes avis | - |
| `POST` | `/customer/reviews` | Poster un avis | `{ "product_id": "uuid", "rating": 5, "comment": "Super !" }` |
| `PUT` | `/customer/reviews/{id}` | Modifier un avis (si pending) | - |
| `DELETE` | `/customer/reviews/{id}` | Supprimer un avis | - |

---

## ❤️ Module Wishlist (Favoris)

| Méthode | Endpoint | Description | Payload |
|:---|:---|:---|:---|
| `GET` | `/customer/wishlist` | Liste des favoris | - |
| `POST` | `/customer/wishlist` | Ajouter aux favoris | `{ "product_id": "uuid" }` |
| `DELETE` | `/customer/wishlist/{productId}` | Retirer des favoris | - |

---

## 🔔 Module Notification

| Méthode | Endpoint | Description |
|:---|:---|:---|
| `GET` | `/customer/notifications` | Liste des notifications |
| `POST` | `/customer/notifications/{id}/read` | Marquer comme lu |
| `POST` | `/customer/notifications/read-all` | Tout marquer comme lu |

---

## 🏷️ Module Promotion (Admin)

| Méthode | Endpoint | Description |
|:---|:---|:---|
| `GET` | `/admin/promotions` | Liste des codes promo |
| `POST` | `/admin/promotions` | Créer un code promo |
| `PUT` | `/admin/promotions/{id}` | Modifier un code promo |
| `DELETE` | `/admin/promotions/{id}` | Supprimer un code promo |

---

## 🚚 Module Shipping & Inventory (Admin)

| Méthode | Endpoint | Description |
|:---|:---|:---|
| `GET` | `/admin/shipping-methods` | Méthodes de livraison |
| `GET` | `/admin/inventory` | État des stocks |
| `POST` | `/admin/inventory/adjust` | Ajustement manuel de stock |
