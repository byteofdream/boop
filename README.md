# boop

A minimal, Reddit-like social network written in pure PHP with no external dependencies. No Composer, no frameworks — just PHP and MySQL.

## features

- **User system** — register, login, profile with avatar (click to change)
- **Posts** — create posts with title and formatted content
- **Text formatting** — `**bold**`, `*italic*`, `#tags` (auto-linked)
- **Media embedding** — upload and embed images/GIFs anywhere in text via `[img]filename.ext[/img]`
- **Voting** — upvote/downvote with toggle (prevents double-votes)
- **Comments** — comment on posts
- **Search** — search by post title, content, or `#tag`
- **Feed sorting** — sort by newest or top score
- **Pagination** — 20 posts per page
- **Avatars** — auto-generated SVG initials fallback, custom upload supported
- **MySQL storage** — relational database, schema in `schema.sql`
- **Secure** — passwords stored as bcrypt hashes only, file upload MIME validation
- **i18n** — English and Russian interface with one-click switching (EN/RU in nav)
- **Theme** — orange-on-black minimalist design

## requirements

- PHP 8.0+
- MySQL 5.7+
- Extensions: `fileinfo`, `mbstring`, `mysqli` (all commonly enabled by default)

## quick start

```bash
# 1. Import the database schema
mysql -u root < schema.sql

# 2. Edit database credentials in config.php if needed

# 3. Start the dev server
cd boop
php -S localhost:8000
```

Open `http://localhost:8000` in your browser.

## project structure

```
boop/
├── index.php           # homepage / post feed
├── post.php            # single post view + comments
├── create_post.php     # post editor with drag-drop upload
├── search.php          # search by text and tags
├── profile.php         # user profile + avatar upload
├── vote.php            # upvote/downvote handler
├── upload.php          # AJAX file upload endpoint
├── login.php           # login form
├── register.php        # registration form
├── logout.php          # logout
├── config.php          # constants and settings
├── functions.php       # storage, formatting, helpers
├── auth.php            # auth logic
├── header.php          # site header + nav
├── footer.php          # site footer
├── lang/               # interface translations (en.php, ru.php)
├── schema.sql          # MySQL database schema
├── style.css           # orange-black theme
├── uploads/            # uploaded files (avatars, images)
├── README.md
└── README.ru.md
```

## license

MIT
