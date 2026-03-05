# Store — Full-Stack E-Commerce Project

A complete, secure LAMP-stack e-commerce application built with PHP, MySQL, Bootstrap 5, and custom JavaScript.

---

## Project Structure

```
store/
├── .htaccess                  # Apache security rules
├── index.php                  # Landing page
├── config/
│   ├── app.php                # Session config, CSRF, helpers
│   ├── database.php           # PDO connection
│   └── schema.sql             # Database schema + seed data
├── includes/
│   ├── header.php             # Shared nav + HTML <head>
│   ├── footer.php             # Shared footer + scripts
│   ├── auth_helpers.php       # Register, login, logout
│   ├── cart_helpers.php       # Cart CRUD operations
│   └── product_helpers.php    # Product CRUD operations
├── pages/
│   ├── products.php           # Product catalogue
│   ├── product_detail.php     # Single product view
│   ├── cart.php               # Shopping cart
│   ├── cart_action.php        # Cart POST handler (AJAX + standard)
│   ├── about.php              # About Us page
│   ├── login.php              # Login form
│   ├── register.php           # Registration form
│   └── logout.php             # Session destroy
└── assets/
    ├── css/style.css          # Custom stylesheet
    ├── js/main.js             # AJAX cart + form validation
    └── images/
        └── placeholder.svg    # Fallback product image
```

---

## Database Schema (ER Overview)

```
users (id PK, username UNIQUE, email UNIQUE, password_hash, created_at)
    │
    └──< cart (id PK, user_id FK→users.id, created_at)
              │
              └──< cart_items (id PK, cart_id FK, product_id FK, quantity, added_at)

products (id PK, name, description, price, image, stock, created_at)
    │
    └──< cart_items.product_id FK
```

---

## Setup Instructions

### 1. Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- A local LAMP environment: XAMPP, WAMP, Laragon, or MAMP

### 2. Deploy Files
Copy the `store/` folder into your web server's document root:
- **XAMPP:** `C:\xampp\htdocs\store\`
- **WAMP:** `C:\wamp64\www\store\`
- **Linux:** `/var/www/html/store/`

### 3. Create the Database
1. Open phpMyAdmin (or MySQL CLI)
2. Run `config/schema.sql`:
   ```sql
   SOURCE /path/to/store/config/schema.sql;
   ```
   Or paste the contents into phpMyAdmin's SQL tab.

### 4. Configure Database Connection
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'store_db');
define('DB_USER', 'your_username');  // ← Change this
define('DB_PASS', 'your_password');  // ← Change this
```

### 5. Set Site URL
Edit `config/app.php`:
```php
define('SITE_URL', 'http://localhost/store');  // ← Match your local URL
```

### 6. Add Product Images
Place product images in `assets/images/`:
- `item1.jpg`
- `item2.jpg`
- `item3.jpg`

If images are missing, the fallback `placeholder.svg` displays automatically.

### 7. Visit the Site
Open your browser: `http://localhost/store/`

**Demo credentials:**
- Email: `admin@store.com`
- Password: `Admin1234!`

---

## Security Implementation

| Threat | Mitigation |
|--------|-----------|
| SQL Injection | PDO prepared statements throughout |
| XSS | `htmlspecialchars()` via `e()` on all output |
| CSRF | Token generated per session, validated on every POST |
| Session Fixation | `session_regenerate_id(true)` on login |
| Session Hijacking | `httponly` + `samesite=Strict` cookie flags |
| Password Exposure | `password_hash()` bcrypt cost=12 |
| Directory Listing | `Options -Indexes` in .htaccess |
| Config Exposure | .htaccess blocks `/config/` and `/includes/` |
| Error Leaking | `display_errors Off` in .htaccess |

---

## CRUD Operations Summary

### Products
| Operation | Function | File |
|-----------|----------|------|
| Read All | `getAllProducts()` | product_helpers.php |
| Read One | `getProductById($id)` | product_helpers.php |
| Create | `createProduct(...)` | product_helpers.php |
| Update | `updateProduct(...)` | product_helpers.php |
| Delete | `deleteProduct($id)` | product_helpers.php |

### Cart
| Operation | Function | File |
|-----------|----------|------|
| Add Item | `addToCart($userId, $productId, $qty)` | cart_helpers.php |
| Remove Item | `removeFromCart($userId, $productId)` | cart_helpers.php |
| Update Qty | `updateCartItem($userId, $productId, $qty)` | cart_helpers.php |
| Read Items | `getCartItems($userId)` | cart_helpers.php |
| Clear Cart | `clearCart($userId)` | cart_helpers.php |

---

## Testing Checklist

### Functional Testing
- [ ] Homepage loads with products visible
- [ ] Navigation works on all pages (mobile + desktop)
- [ ] Product detail page shows correct product
- [ ] Registration form validates all fields
- [ ] Login works with correct credentials
- [ ] Login fails gracefully with wrong credentials
- [ ] Add to cart works (logged in)
- [ ] Add to cart redirects to login when not authenticated
- [ ] Cart updates quantity correctly
- [ ] Cart removes items correctly
- [ ] Cart totals calculate correctly (inc. free shipping at £50)
- [ ] Logout destroys session

### Security Testing
- [ ] Try `?id=99999` on product_detail — should redirect gracefully
- [ ] Try submitting cart form without CSRF token — should be rejected
- [ ] Try SQL injection in login email field — should fail safely
- [ ] Try `<script>alert(1)</script>` in any input — should render as text
- [ ] Accessing `/config/database.php` directly — should return 403
- [ ] Session cookie has `httponly` flag (check browser DevTools)

### W3C Validation
- [ ] Validate `index.php` output at https://validator.w3.org
- [ ] Check for missing `alt` attributes on all images
- [ ] Verify all form inputs have associated `<label>` elements
- [ ] Confirm no duplicate `id` attributes

### Accessibility (WCAG 2.1 AA)
- [ ] All images have descriptive `alt` text
- [ ] All form inputs have `<label>` + `aria-required`
- [ ] All interactive elements are keyboard-focusable
- [ ] Focus styles are visible (`:focus-visible` styles applied)
- [ ] Error messages are announced via `aria-live`
- [ ] Colour contrast ratio meets 4.5:1 minimum
- [ ] Skip navigation link present and functional

---

## Key Learning Points

### Why PDO Prepared Statements?
```php
// ❌ Vulnerable to SQL injection
$stmt = $pdo->query("SELECT * FROM users WHERE email = '$email'");

// ✅ Safe — value is never interpreted as SQL
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### Why htmlspecialchars() on output?
```php
// ❌ XSS vulnerability — user could inject <script> tags
echo $product['name'];

// ✅ Safe — converts < > & " ' to HTML entities
echo htmlspecialchars($product['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
// Or using the e() helper function:
echo e($product['name']);
```

### Why bcrypt for passwords?
```php
// ❌ Never store plain text or use MD5/SHA1
$stored = md5($password);

// ✅ bcrypt is slow by design — cost=12 makes brute-force infeasible
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verification is timing-safe (prevents timing attacks)
$valid = password_verify($inputPassword, $storedHash);
```

---

## Extending the Project

To add admin functionality:
1. Create `admin/` directory
2. Add `is_admin` column to `users` table
3. Protect admin pages with `if (!isAdmin()) redirect(...)`
4. Build product CRUD forms using the functions in `product_helpers.php`

To add order processing:
1. Create `orders` and `order_items` tables
2. On checkout: copy cart items to order, clear cart
3. Send confirmation email using PHP's `mail()` or PHPMailer
