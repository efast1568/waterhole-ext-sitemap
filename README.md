[![Latest Stable Version](https://poser.pugx.org/flectar/waterhole-sitemap/v)](https://packagist.org/packages/flectar/waterhole-sitemap) [![Total Downloads](https://poser.pugx.org/flectar/waterhole-sitemap/downloads)](https://packagist.org/packages/flectar/waterhole-sitemap)  [![License](https://poser.pugx.org/flectar/waterhole-sitemap/license)](https://packagist.org/packages/flectar/waterhole-sitemap)

# Sitemap for Waterhole

A comprehensive, high-performance XML sitemap generator for [Waterhole](https://waterhole.dev).
This extension automatically generates SEO-compliant sitemaps for your community, helping search engines (Google, Bing...) discover and index your content faster.

---

### 🚀 Features

- Zero configuration: Works out of the box.
- Performance: 24-hour caching prevents database load, even with thousands of posts.
- Segmented sitemaps: Separate endpoints for Posts, Channels, Pages, and Users.
- SEO optimized: Includes `lastmod`, `changefreq`, and `priority` tags.
- Safe: Read-only operation. Does not modify your database.

---

### 📦 Installation

Install via Composer. Run the following command in your Waterhole root directory:

```bash
composer require flectar/waterhole-sitemap
```

---

### ♻️ Updating

To update the extension, simply run:

```bash
composer update flectar/waterhole-sitemap:"*"
php artisan cache:clear
```

---

### ⚙️ Usage

Once installed, the sitemap is automatically available at:

-   `https://your-domain.com/sitemap` (Sitemap index)
-   `https://your-domain.com/sitemap/pages` (Home page and pages)
-   `https://your-domain.com/sitemap/channels`
-   `https://your-domain.com/sitemap/posts`
-   `https://your-domain.com/sitemap/users`

Posts and users are split into sitemaps of 5,000 URLs each (`/sitemap/posts/2`, `/sitemap/posts/3`, ...). All of them are listed in the sitemap index.

Only content that guests can see and that is listed in your forum structure is included. Private channels, unlisted channels and pages, unapproved posts and deleted posts are left out. Users are included only if they have at least one public post.

Robots.txt
To ensure search engines find your sitemap, add the following line to your `public/robots.txt`:

```bash
Sitemap: https://your-domain.com/sitemap
```
---

### 🔧 Configuration

The sitemap is cached for 24 hours by default to ensure optimal performance.
To force a refresh (e.g., after a large content import), run:

```bash
php artisan optimize
php artisan cache:clear
```

---

### 📄 License

- Open-sourced under the [MIT License](https://github.com/flectar/waterhole-ext-sitemap/blob/main/LICENSE).

---

### 🔗 Useful Links

- [Flectar](https://flectar.com/)
- [Packagist - flectar/waterhole-sitemap](https://packagist.org/packages/flectar/waterhole-sitemap)
- [GitHub Repo](https://github.com/flectar/waterhole-ext-sitemap)
- [Composer](https://getcomposer.org/)
- [Waterhole](https://waterhole.dev/)
