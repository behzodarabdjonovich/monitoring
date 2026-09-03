# ADPI Doktorantura monitoringi va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi

Andijon davlat pedagogika instituti (ADPI) uchun doktorantura jarayonlarini
monitoring qilish va maxsus davlat akkreditatsiyasiga tayyorgarlik darajasini
baholovchi o'zbek tilidagi axborot tizimi.

Bu repozitoriya **foundation (asos)** bosqichini o'z ichiga oladi: loyiha karkasi,
yadro (core) freymvork, barcha jadvallar uchun migratsiyalar, seed ma'lumotlar,
RBAC asosi, login/parol autentifikatsiyasi va 16 bo'limli o'zbekcha sidebar bilan
responsive asosiy layout.

---

## Texnologik stek va uning asoslari

| Qaror | Sabab |
|-------|-------|
| **Dependency-free maxsus PHP 8.4 MVC** | Oflayn muhit (INTEGRATIONS_ONLY): Packagist bloklangan, Laravel/Symfony kabi freymvorklarni o'rnatib bo'lmaydi. Yadro qo'lda yozilgan va repozitoriyada. |
| **PDO + SQLite (dev)** | Sandbox'da PostgreSQL/MySQL demoni ishlamaydi. SQLite fayl asosida ishlaydi; sxema portativ saqlanadi (pgsql/mysql'ga ko'chiriladi). |
| **Qo'lda yozilgan CSS/JS** | npm/CDN bloklangan. Barcha aktivlar (`public/assets/`) repozitoriyada vendor qilingan. |
| **Composer faqat `dump-autoload`** | Composer'ning yagona oflayn ishlaydigan xususiyati — PSR-4 autoloader generatsiyasi. Hech qanday paket yuklanmaydi. |

Batafsil arxitektura: [`docs/01-architecture.md`](docs/01-architecture.md).

### Oflayn (offline) cheklovlar

- Tashqi tarmoq yo'q: **CDN, npm, Packagist ishlatilmaydi**.
- Barcha kod, CSS va JS repozitoriya ichida.
- `composer install`/`require` **ishlamaydi**; faqat `composer dump-autoload`.

---

## Katalog tuzilmasi

```
monitoring/
├── public/                 # webroot: front-kontroller (index.php) + assets
│   └── assets/{css,js}     # qo'lda yozilgan dizayn tizimi
├── src/
│   ├── Core/               # Router, Request, Response, View, DB, Auth,
│   │                       # Csrf, Validator, AuditLogger, FileStorage,
│   │                       # ScoringEngine, Config, Session
│   │   └── Middleware/     # Auth, Rbac, Csrf middleware'lari
│   ├── Controllers/        # AuthController, DashboardController, ...
│   └── helpers.php         # e() (XSS ekranlash) va boshqa helperlar
├── resources/views/        # layout + partiallar + sahifalar
├── database/
│   ├── Schema.php          # portativ migratsiya yordamchisi (Blueprint)
│   ├── migrations/         # sxema (27 jadval)
│   └── seeds/              # boshlang'ich (demo/placeholder) ma'lumotlar
├── storage/                # yuklangan dalil fayllari (webroot'dan TASHQARIDA)
├── config/                 # app.php, database.php, security.php
├── routes/web.php          # HTTP marshrutlar
├── bin/console             # CLI: migrate, migrate:fresh, seed, backup
├── tests/run.php           # dependency-free test runner
└── docs/                   # loyihaviy hujjatlar (ER, rollar, sahifalar, UI/UX)
```

---

## Ishga tushirish

Talab: **PHP 8.4** (`ext-pdo`, `ext-pdo_sqlite`), **Composer** (faqat autoload uchun).

```bash
# 1) PSR-4 autoloader (oflayn ishlaydi)
export COMPOSER_ALLOW_SUPERUSER=1
composer dump-autoload

# 2) Ma'lumotlar bazasi (SQLite) va seed ma'lumotlari
php bin/console migrate
php bin/console seed

# 3) Development serverni ishga tushirish
php -S 127.0.0.1:8000 -t public public/index.php
```

So'ng brauzerda `http://127.0.0.1:8000/login` sahifasiga kiring.

### CLI buyruqlari

| Buyruq | Vazifasi |
|--------|----------|
| `php bin/console migrate` | Barcha migratsiyalarni bajaradi |
| `php bin/console migrate:fresh` | SQLite DB'ni o'chirib qayta yaratadi |
| `php bin/console seed` | Seed (demo/placeholder) ma'lumotlarini kiritadi |
| `php bin/console backup` | SQLite DB zaxira nusxasini oladi (pgsql/mysql uchun dump buyrug'ini ko'rsatadi) |

### Testlar

```bash
php tests/run.php
```

Dependency-free runner: router, CSRF, auth hash/verify, RBAC deny, migrate+seed
yozuv sonlari va `e()` ekranlashini tekshiradi.

---

## Boshqa ma'lumotlar bazasiga ko'chirish (portability)

Sxema portativ ustun turlaridan foydalanadi va `config/database.php` drayver
almashtirgichiga ega. PostgreSQL yoki MySQL'ga o'tish uchun muhit o'zgaruvchilarini
o'rnating:

```bash
export DB_DRIVER=pgsql      # yoki mysql
export DB_HOST=127.0.0.1
export DB_PORT=5432
export DB_DATABASE=adpi_monitoring
export DB_USERNAME=postgres
export DB_PASSWORD=secret
php bin/console migrate && php bin/console seed
```

> Eslatma: sandbox muhitida PostgreSQL/MySQL demoni ishlamaydi, shuning uchun
> ishlab chiqish SQLite bilan olib boriladi. Sxema ushbu drayverlarga mos.

---

## Demo kirish ma'lumotlari

Barcha demo foydalanuvchilar uchun parol: **`Parol123!`**

| Rol | Login |
|-----|-------|
| Super Administrator | `admin` |
| Institut rahbariyati | `rahbariyat` |
| Ilmiy ishlar mas'uli | `ilmiy` |
| Doktorantura bo'limi | `doktorantura` |
| Sifat nazorati | `sifat` |
| Kafedra mudiri | `kafedra` |
| Ilmiy rahbar | `rahbar` |
| Doktorant | `doktorant` |
| Ekspert | `ekspert` |

> **Bu demo hisoblar** — real shaxsiy ma'lumotlar emas. Ishlab chiqarishga
> o'tishdan oldin ularni o'chiring yoki parollarni almashtiring.

---

## ⚠️ Akkreditatsiya mezonlari — NAMUNA (placeholder) ogohlantirishi

Seed'dagi akkreditatsiya sikli, mezonlari va indikatorlari **NAMUNA
(placeholder)** sifatida kiritilgan (`is_placeholder = 1`) va interfeysda
ogohlantiruvchi banner bilan belgilangan.

Rasmiy O'zbekiston normativ manbalarini oflayn muhitda tasdiqlab bo'lmagani
uchun ular **uydirilmagan** — faqat namunaviy tuzilma sifatida berilgan. Ular
to'liq administrator tomonidan sozlanadi.

**Ishlab chiqarishga o'tishdan oldin** placeholder mezon/indikator/og'irliklarni
**rasmiy tasdiqlangan qiymatlar bilan almashtiring**.

---

## Xavfsizlik

- **Parol**: `password_hash` / `password_verify` (bcrypt/argon2).
- **Sessiya**: HttpOnly + SameSite cookie, login vaqtida `session_regenerate_id`.
- **CSRF**: har o'zgartiruvchi (POST/PUT/PATCH/DELETE) so'rovda majburiy token
  (login formasi ham).
- **XSS**: barcha view chiqishi `e()` helperi orqali ekranlanadi.
- **SQL injection**: barcha DB kirishi PDO prepared statements orqali.
- **Fayl yuklash**: kengaytma + MIME + hajm oq ro'yxati; fayllar webroot'dan
  tashqarida `storage/` ichida tasodifiy nom bilan; olib berish faqat
  himoyalangan kontroller orqali (path traversal himoyasi bilan).
- **Audit**: `AuditLogger` create/update/upload/approve/login kabi amallarni
  o'zgarmas `audit_logs` jadvaliga yozadi.

---

## Litsenziya

Ichki foydalanish uchun (proprietary) — Andijon davlat pedagogika instituti.
