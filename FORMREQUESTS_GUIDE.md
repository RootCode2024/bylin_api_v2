# 📋 FormRequests - Guide d'Implémentation

> **Note** : Les FormRequests liés aux **produits, catégories, marques et attributs** sont gérés par un autre développeur et ne sont pas listés ici.

---

## ✅ FormRequests Créés et Prêts à Utiliser

### 🔐 **Module Customer (Authentication & Profile)**

| FormRequest               | Fichier                                              | Route                                    | Statut         |
| ------------------------- | ---------------------------------------------------- | ---------------------------------------- | -------------- |
| `RegisterCustomerRequest` | `Customer/Http/Requests/RegisterCustomerRequest.php` | `POST /auth/customer/register`           | ✅ **Nouveau** |
| `LoginCustomerRequest`    | `Customer/Http/Requests/LoginCustomerRequest.php`    | `POST /auth/customer/login`              | ✅ **Nouveau** |
| `UpdateProfileRequest`    | `Customer/Http/Requests/UpdateProfileRequest.php`    | `PUT /customer/profile`                  | ✅ Existe      |
| `ChangePasswordRequest`   | `Customer/Http/Requests/ChangePasswordRequest.php`   | `POST /customer/profile/change-password` | ✅ Existe      |

### 📍 **Module Customer (Addresses)**

| FormRequest            | Fichier                                           | Route                          | Statut         |
| ---------------------- | ------------------------------------------------- | ------------------------------ | -------------- |
| `StoreAddressRequest`  | `Customer/Http/Requests/StoreAddressRequest.php`  | `POST /customer/addresses`     | ✅ Existe      |
| `UpdateAddressRequest` | `Customer/Http/Requests/UpdateAddressRequest.php` | `PUT /customer/addresses/{id}` | ✅ **Nouveau** |

### 🛒 **Module Cart**

| FormRequest                   | Fichier                                              | Route                                 | Statut         |
| ----------------------------- | ---------------------------------------------------- | ------------------------------------- | -------------- |
| `AddToCartRequest`            | `Cart/Http/Requests/AddToCartRequest.php`            | `POST /customer/cart/items`           | ✅ Existe      |
| `UpdateCartItemRequest`       | `Cart/Http/Requests/UpdateCartItemRequest.php`       | `PUT /customer/cart/items/{id}`       | ✅ Existe      |
| `ApplyCouponRequest`          | `Cart/Http/Requests/ApplyCouponRequest.php`          | `POST /customer/cart/coupon`          | ✅ Existe      |
| `ConvertToGiftCartRequest`    | `Cart/Http/Requests/ConvertToGiftCartRequest.php`    | `POST /customer/cart/convert-to-gift` | ✅ **Nouveau** |
| `ContributeToGiftCartRequest` | `Cart/Http/Requests/ContributeToGiftCartRequest.php` | `POST /gift-carts/{token}/contribute` | ✅ **Nouveau** |

### 📦 **Module Order**

| FormRequest                | Fichier                                            | Route                               | Statut         |
| -------------------------- | -------------------------------------------------- | ----------------------------------- | -------------- |
| `StoreOrderRequest`        | `Order/Http/Requests/StoreOrderRequest.php`        | `POST /customer/orders`             | ✅ Existe      |
| `CancelOrderRequest`       | `Order/Http/Requests/CancelOrderRequest.php`       | `POST /customer/orders/{id}/cancel` | ✅ Existe      |
| `UpdateOrderStatusRequest` | `Order/Http/Requests/UpdateOrderStatusRequest.php` | `PUT /admin/orders/{id}/status`     | ✅ **Nouveau** |

### ⭐ **Module Reviews**

| FormRequest           | Fichier                                         | Route                        | Statut         |
| --------------------- | ----------------------------------------------- | ---------------------------- | -------------- |
| `StoreReviewRequest`  | `Reviews/Http/Requests/StoreReviewRequest.php`  | `POST /customer/reviews`     | ✅ Existe      |
| `UpdateReviewRequest` | `Reviews/Http/Requests/UpdateReviewRequest.php` | `PUT /customer/reviews/{id}` | ✅ **Nouveau** |

### 💝 **Module Wishlist**

| FormRequest            | Fichier                                           | Route                     | Statut    |
| ---------------------- | ------------------------------------------------- | ------------------------- | --------- |
| `AddToWishlistRequest` | `Customer/Http/Requests/AddToWishlistRequest.php` | `POST /customer/wishlist` | ✅ Existe |

### 🎁 **Module Promotion**

| FormRequest              | Fichier                                              | Route                        | Statut         |
| ------------------------ | ---------------------------------------------------- | ---------------------------- | -------------- |
| `StorePromotionRequest`  | `Promotion/Http/Requests/StorePromotionRequest.php`  | `POST /admin/promotions`     | ✅ Existe      |
| `UpdatePromotionRequest` | `Promotion/Http/Requests/UpdatePromotionRequest.php` | `PUT /admin/promotions/{id}` | ✅ **Nouveau** |

### 🚚 **Module Shipping**

| FormRequest                   | Fichier                                                  | Route                              | Statut         |
| ----------------------------- | -------------------------------------------------------- | ---------------------------------- | -------------- |
| `StoreShippingMethodRequest`  | `Shipping/Http/Requests/StoreShippingMethodRequest.php`  | `POST /admin/shipping-methods`     | ✅ Existe      |
| `UpdateShippingMethodRequest` | `Shipping/Http/Requests/UpdateShippingMethodRequest.php` | `PUT /admin/shipping-methods/{id}` | ✅ **Nouveau** |

### 💳 **Module Payment**

| FormRequest              | Fichier                                            | Route                            | Statut         |
| ------------------------ | -------------------------------------------------- | -------------------------------- | -------------- |
| `InitiatePaymentRequest` | `Payment/Http/Requests/InitiatePaymentRequest.php` | `POST /customer/orders/{id}/pay` | ✅ Existe      |
| `UpdatePaymentRequest`   | `Payment/Http/Requests/UpdatePaymentRequest.php`   | `PUT /admin/payments/{id}`       | ✅ **Nouveau** |

### 📊 **Module Customer (Admin)**

| FormRequest                       | Fichier                                                      | Route                               | Statut         |
| --------------------------------- | ------------------------------------------------------------ | ----------------------------------- | -------------- |
| `BulkUpdateCustomerStatusRequest` | `Customer/Http/Requests/BulkUpdateCustomerStatusRequest.php` | `POST /admin/customers/bulk/status` | ✅ **Nouveau** |
| `ExportCustomersRequest`          | `Customer/Http/Requests/ExportCustomersRequest.php`          | `POST /admin/customers/export`      | ✅ **Nouveau** |

### 📦 **Module Inventory**

| FormRequest          | Fichier                                          | Route                          | Statut    |
| -------------------- | ------------------------------------------------ | ------------------------------ | --------- |
| `AdjustStockRequest` | `Inventory/Http/Requests/AdjustStockRequest.php` | `POST /admin/inventory/adjust` | ✅ Existe |

---

## 🚀 **Plan d'Implémentation - Étapes Suivantes**

### **Étape 1 : Remplacer les validations inline**

Voici les controllers à modifier pour utiliser les FormRequests :

#### **A. CustomerAuthController.php**

```php
// AVANT
public function register(Request $request): JsonResponse
{
    $validated = $request->validate([...]);
    // ...
}

// APRÈS
use Modules\Customer\Http\Requests\RegisterCustomerRequest;

public function register(RegisterCustomerRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}
```

#### **B. CustomerAuthController.php - Login**

```php
// AVANT
public function login(Request $request): JsonResponse
{
    $validated = $request->validate([...]);
    // ...
}

// APRÈS
use Modules\Customer\Http\Requests\LoginCustomerRequest;

public function login(LoginCustomerRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}
```

#### **C. AddressController.php**

```php
// AVANT
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([...]);
    // ...
}

public function update(string $id, Request $request): JsonResponse
{
    $validated = $request->validate([...]);
    // ...
}

// APRÈS
use Modules\Customer\Http\Requests\StoreAddressRequest;
use Modules\Customer\Http\Requests\UpdateAddressRequest;

public function store(StoreAddressRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}

public function update(string $id, UpdateAddressRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}
```

#### **D. CartController.php**

```php
use Modules\Cart\Http\Requests\AddToCartRequest;
use Modules\Cart\Http\Requests\UpdateCartItemRequest;
use Modules\Cart\Http\Requests\ApplyCouponRequest;

public function addItem(AddToCartRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}

public function updateItem(string $itemId, UpdateCartItemRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}

public function applyCoupon(ApplyCouponRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}
```

#### **E. GiftCartController.php**

```php
use Modules\Cart\Http\Requests\ConvertToGiftCartRequest;
use Modules\Cart\Http\Requests\ContributeToGiftCartRequest;

public function convert(ConvertToGiftCartRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}

public function contribute(string $token, ContributeToGiftCartRequest $request): JsonResponse
{
    $validated = $request->validated();
    // ...
}
```

---

## 📝 **FormRequests Encore à Créer (Si besoin)**

### **Module Notification**

Si vous voulez permettre aux admins de créer des notifications manuelles :

-   `SendNotificationRequest` - Pour envoyer une notification manuelle

### **Module Shipping**

-   `CreateShipmentRequest` - Pour créer un envoi
-   `UpdateShipmentRequest` - Pour mettre à jour le tracking

### **Module Order**

-   `RefundOrderRequest` - Pour rembourser une commande

---

## ✅ **Checklist d'Implémentation**

### **Priorité 1 - Routes Publiques & Customer (Cette Semaine)**

-   [ ] Remplacer validation dans `CustomerAuthController::register()` par `RegisterCustomerRequest`
-   [ ] Remplacer validation dans `CustomerAuthController::login()` par `LoginCustomerRequest`
-   [ ] Remplacer validation dans `AddressController::store()` par `StoreAddressRequest`
-   [ ] Remplacer validation dans `AddressController::update()` par `UpdateAddressRequest`
-   [ ] Remplacer validation dans `CartController::addItem()` par `AddToCartRequest`
-   [ ] Remplacer validation dans `CartController::updateItem()` par `UpdateCartItemRequest`
-   [ ] Remplacer validation dans `CartController::applyCoupon()` par `ApplyCouponRequest`
-   [ ] Remplacer validation dans `GiftCartController::convert()` par `ConvertToGiftCartRequest`
-   [ ] Remplacer validation dans `GiftCartController::contribute()` par `ContributeToGiftCartRequest`

### **Priorité 2 - Routes Admin (Semaine Prochaine)**

-   [ ] Utiliser `UpdateOrderStatusRequest` dans `Admin/OrderController::updateStatus()`
-   [ ] Utiliser `BulkUpdateCustomerStatusRequest` dans `Admin/CustomerController::bulkUpdateStatus()`
-   [ ] Utiliser `ExportCustomersRequest` dans `Admin/CustomerController::export()`
-   [ ] Utiliser `UpdatePromotionRequest` dans `Admin/PromotionController::update()`
-   [ ] Utiliser `UpdateShippingMethodRequest` dans `Admin/ShippingMethodController::update()`

### **Priorité 3 - Reviews & Wishlist**

-   [ ] Utiliser `StoreReviewRequest` dans `ReviewController::store()`
-   [ ] Utiliser `UpdateReviewRequest` dans `ReviewController::update()`
-   [ ] Utiliser `AddToWishlistRequest` dans `WishlistController::add()`

---

## 💡 **Bonnes Pratiques**

### **1. Autorisation dans les FormRequests**

Si besoin de logique d'autorisation personnalisée :

```php
public function authorize(): bool
{
    // Example: User can only update their own review
    return $this->user()->id === $this->route('review')->customer_id;
}
```

### **2. Messages d'Erreur Personnalisés**

```php
public function messages(): array
{
    return [
        'email.unique' => 'Cet email est déjà utilisé.',
        'password.confirmed' => 'Les mots de passe ne correspondent pas.',
    ];
}
```

### **3. Attributs Lisibles**

```php
public function attributes(): array
{
    return [
        'first_name' => 'prénom',
        'last_name' => 'nom de famille',
    ];
}
```

---

## 📚 **Ressources**

-   [Laravel Form Request Documentation](https://laravel.com/docs/11.x/validation#form-request-validation)
-   [Laravel Validation Rules](https://laravel.com/docs/11.x/validation#available-validation-rules)

---

**Dernière mise à jour** : 22 Décembre 2025
