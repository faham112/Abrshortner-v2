# Abrshortner-v2

Multi-user URL shortener with glassmorphism UI, admin panel, fully masked OG previews (Facebook / WhatsApp / Telegram), gallery image upload, and click analytics.

Built with PHP + MySQL (Hostinger ready).

## Features

- Single login form (admin + user)
- Admin dashboard: stats, create links, manage users, all links
- User dashboard: create and manage own short links
- Fully masked previews — destination URL is never shown in social previews
- Gallery image upload or image URL
- Click counter (bots excluded), search, edit, delete, copy
- Clean short URLs: `yourdomain.com/abc123`

## Default admin

- Email: `admin@link666xx.com`
- Password: `admin123`

Change this after first login.

## Setup (Hostinger)

1. Upload all files to `public_html`
2. Create a MySQL database
3. Import `database.sql` in phpMyAdmin
4. Copy `.env.example` to `.env` and fill database credentials
5. Make sure `uploads/` exists with permission `755`
6. Open the domain → login page

## Files

- `index.php` — redirects to login
- `login.php` — login page
- `dashboard.php` — user dashboard
- `admin.php` — admin panel
- `redirect.php` — short link + mask logic
- `config.php` — DB + auth helpers
- `logout.php`
- `.htaccess`
- `database.sql`
- `.env.example`
- `uploads/` — preview images
