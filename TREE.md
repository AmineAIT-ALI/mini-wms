# Mini WMS – Project Tree (v1.0.0)

```
mini-wms/
├── .env.example                 ← Environment variables template
├── .gitignore
├── .htaccess                    ← Redirects everything to public/
├── README.md
├── CHANGELOG.md
├── TREE.md
│
├── logs/
│   └── .gitkeep
│
├── sql/
│   ├── schema.sql               ← DB creation: 6 tables + indexes
│   └── seed.sql                 ← 2 users, 8 products, 3 orders
│
├── app/
│   ├── .htaccess                ← Deny from all
│   │
│   ├── config/
│   │   ├── env.php              ← Load .env, define constants, error handler
│   │   ├── db.php               ← PDO singleton + db_ping()
│   │   └── bootstrap.php        ← Single require for all public pages
│   │
│   ├── lib/
│   │   ├── auth.php             ← e(), login/logout helpers, require_login/role
│   │   ├── csrf.php             ← csrf_token(), csrf_verify(), csrf_field()
│   │   ├── flash.php            ← flash(), flash_get()
│   │   ├── validators.php       ← validate_email/sku/qty/status/date
│   │   └── audit.php            ← audit_log()
│   │
│   ├── models/
│   │   ├── User.php             ← findByEmail, findById, attempt
│   │   ├── Product.php          ← CRUD + count/search/pagination/lowstock
│   │   ├── Order.php            ← CRUD + changeStatus (with transactions)
│   │   ├── StockMove.php        ← createManual + filtered pagination
│   │   └── AuditLog.php         ← read-only filtered pagination
│   │
│   └── views/
│       ├── layout.php           ← HTML shell (head, navbar, footer)
│       └── partials/
│           ├── nav.php          ← Sticky top navigation
│           ├── flash.php        ← Flash message renderer
│           └── alerts.php       ← Inline alert partial
│
└── public/
    ├── .htaccess                ← Security headers + mod_rewrite
    ├── index.php                ← Redirect → login or dashboard
    ├── login.php                ← Login form (PRG pattern)
    ├── logout.php               ← POST-only logout
    ├── dashboard.php            ← KPIs + recent orders + recent moves
    │
    ├── products.php             ← List + search + pagination
    ├── product_new.php          ← Create product (admin)
    ├── product_edit.php         ← Edit product (admin)
    ├── product_delete.php       ← Delete product POST handler (admin)
    │
    ├── orders.php               ← List + status filter tabs
    ├── order_new.php            ← Create order with dynamic items
    ├── order_view.php           ← Order detail + action buttons
    ├── order_status.php         ← Status change POST handler
    │
    ├── stock_moves.php          ← History + filters + pagination
    ├── stock_move_new.php       ← New manual in/out move
    │
    ├── audit.php                ← Audit log (admin)
    ├── health.php               ← JSON health check
    ├── 404.php                  ← Not found page
    ├── 500.php                  ← Server error page
    │
    └── assets/
        ├── css/
        │   └── app.css          ← Full design system (CSS variables, responsive)
        └── js/
            └── app.js           ← Alert dismiss, confirm, order ref auto-fill
```

## File Count: **43 files** across **14 directories**

## Database Schema

```
users (id, email, password_hash, role, created_at)
    ↓
orders (id, reference, status, created_by→users, created_at, updated_at)
    ↓
order_items (id, order_id→orders CASCADE, product_id→products, qty)
    ↓
stock_moves (id, product_id→products, delta, reason, order_id→orders?, created_by→users, created_at)
    ↓
audit_log (id, user_id→users?, action, entity, entity_id?, meta JSON, created_at)

products (id, sku, name, stock CHECK(≥0), threshold CHECK(≥0), created_at, updated_at)
```
