# Fuzzywire

E-commerce site for a fuzzy-wire flower bouquet shop. Customers browse ready-made bouquets or build their own from flowers, wrappers, ribbons, and base sizes, then check out via GCash.

## Features

- **Storefront** — home, about, and bouquet catalog pages
- **Bouquet builder** — customize flowers, wrappers, ribbons, and base size with live pricing
- **Customer accounts** — email OTP signup/verification, order history
- **Checkout** — GCash payment with reference number verification
- **Reviews** — customers can leave ratings, comments, and photos on orders
- **Admin dashboard** — manage inventory, presets, wrappers, ribbons, orders, and sales stats

## Stack

- PHP (no framework) + SQLite (via PDO)
- Vanilla JS/CSS frontend
- PHPMailer/SMTP for OTP and order emails

## Setup

1. Copy `config.example.php` to `config.php`.
2. Fill in your own SMTP credentials (Gmail app password recommended) and GCash QR image path.
3. Serve the project root with PHP (e.g. XAMPP, `php -S localhost:8000`).
4. On first request, `config.php` auto-creates the SQLite DB at `data/fuzzywire.db` and seeds starter flowers, wrappers, ribbons, and bouquets.

`config.php` and `data/` are gitignored — never commit real credentials or the local DB.
