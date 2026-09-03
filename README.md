# ADPI Doktorantura monitoringi va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi

Andijon davlat pedagogika instituti (ADPI) uchun doktorantura jarayonlarini
monitoring qilish va maxsus davlat akkreditatsiyasiga tayyorgarlik darajasini
baholovchi o'zbek tilidagi axborot tizimi.

Bu repozitoriya **to'liq tizim**ni o'z ichiga oladi: yadro (core) freymvork,
RBAC (9 rol), analitik dashboard, doktorantlar, individual rejalar (5 bosqichli
oqim), ilmiy natijalar, ilmiy rahbarlar, ixtisosliklar, markazlashtirilgan
akkreditatsiya moduli (sozlanadigan tayyorlik indeksi), dalillar bazasi,
kamchiliklar + Action Plan, ichki audit, **hisobotlar (PDF/Excel/print),
bildirishnomalar, global qidiruv, o'zgarmas audit jurnali** va to'liq xavfsizlik
hardening.

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
| `php bin/console notify` | Joriy ma'lumotlardan bildirishnomalarni shakllantiradi (muddat, dalil, indikator) |

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

## Hisobotlar (PDF / Excel / chop etish)

Tizim item 14'dagi **15 ta hisobot turini** avtomatik shakllantiradi (doktorant
monitoring, doktorant yillik, individual reja bajarilishi, kafedra kesimi,
ixtisoslik, ilmiy rahbar, ilmiy natijalar, maqolalar, attestatsiya,
akkreditatsiya indikatorlari, bajarilmagan indikatorlar, yetishmayotgan
dalillar, kamchiliklar + Action Plan, ichki baholash, akkreditatsiyaga
tayyorlik). Har bir hisobot uchta chiqish yo'liga ega:

- **Chop etish (print)** — `?format=print`: layoutsiz, chop etishga mos CSS
  bilan HTML ko'rinish. Brauzerning "Chop etish → PDF sifatida saqlash"
  imkoniyati orqali PDF ham olinadi.
- **Excel** — `?format=excel`: **kutubxonasiz** (dependency-free) qo'lda yozilgan
  minimal **OpenXML/SpreadsheetML `.xlsx`** yozuvchi (`App\Core\Spreadsheet`,
  PHP'ning ichki `ZipArchive` orqali). Agar `ZipArchive` mavjud bo'lmasa,
  UTF-8 BOM bilan **CSV** fallback qaytariladi (Excel ochadi).
- **PDF** — `?format=pdf`: **kutubxonasiz** qo'lda yozilgan minimal **PDF 1.4**
  yozuvchi (`App\Core\Pdf`) jadval ma'lumotlarini ko'p sahifali hujjatga
  chizadi (standart Helvetica shrifti, WinAnsi kodlash). Bu tashqi kutubxonasiz
  to'g'ridan-to'g'ri yuklab olinadigan PDF beradi; formatlashga boy hujjat kerak
  bo'lsa print-view (brauzer print-to-PDF) tavsiya etiladi.

> **Nima uchun shunday?** Oflayn muhitda (Packagist/npm/CDN bloklangan)
> `dompdf`, `PhpSpreadsheet` kabi kutubxonalarni o'rnatib bo'lmaydi. Shuning
> uchun `.xlsx` va `.pdf` yozuvchilari qo'lda, faqat PHP standart kengaytmalari
> (`zip`, `iconv`, `hash`) bilan amalga oshirilgan.

## Bildirishnomalar

Avtomatik ogohlantirishlar foydalanuvchining shaxsiy kabinetida (topbar
qo'ng'iroq + `/notifications` sahifasi) ko'rsatiladi va **o'qilgan deb
belgilanadi**. Generator (`php bin/console notify` yoki sahifadagi "yangilash"
tugmasi) joriy ma'lumotlardan hodisalarni hisoblaydi: vazifa muddati
yaqinlashmoqda (7 kun qoldi), indikatorda dalil yetishmayapti, ixtisoslikda N ta
bajarilmagan indikator, ilmiy rahbar tasdig'ini kutayotgan vazifalar.

## Global qidiruv

Topbardagi qidiruv qutisi `/search` endpointiga ulanadi: doktorantlar, ilmiy
rahbarlar, ixtisosliklar, hujjatlar (dalillar) va akkreditatsiya indikatorlari
bo'yicha qidiradi; natijalar tur bo'yicha guruhlanadi. JSON (`?format=json`) va
HTML chiqishlarini qo'llab-quvvatlaydi.

## Xavfsizlik

- **Parol**: `password_hash` / `password_verify` (bcrypt/argon2).
- **Sessiya**: HttpOnly + SameSite cookie, login vaqtida `session_regenerate_id`,
  **harakatsizlik muddati (idle timeout)** (`security.session.lifetime`), logout.
- **CSRF**: har o'zgartiruvchi (POST/PUT/PATCH/DELETE) so'rovda majburiy token
  (login formasi ham).
- **XSS**: barcha view chiqishi `e()` helperi orqali ekranlanadi.
- **SQL injection**: barcha DB kirishi PDO prepared statements orqali.
- **Fayl yuklash**: kengaytma + MIME + hajm oq ro'yxati; fayllar webroot'dan
  tashqarida `storage/` ichida tasodifiy nom bilan; olib berish faqat
  himoyalangan kontroller orqali (path traversal himoyasi bilan).
- **Foydalanuvchini bloklash**: `is_blocked = 1` bo'lgan foydalanuvchi tizimga
  kira olmaydi (`/users` sahifasidan boshqariladi).
- **Parolni tiklash**: `password_resets` tokeni (sha256, muddatli) orqali
  forgot/reset oqimi.
- **2FA (TOTP) skafoldi**: `App\Core\Totp` (RFC 6238, kutubxonasiz). `APP_2FA=1`
  muhit o'zgaruvchisi bilan yoqiladi; yoqilganda `twofa_secret` o'rnatilgan
  foydalanuvchilardan login paytida 6 xonali kod so'raladi.
- **Xavfsizlik sarlavhalari**: `SecurityHeadersMiddleware` har bir javobga
  `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`
  va faqat `self` resurslariga ruxsat beruvchi **CSP** qo'shadi (global).
- **DB zaxira**: `php bin/console backup`.
- **Audit**: `AuditLogger` create/update/upload/approve/login/block kabi
  amallarni o'zgarmas `audit_logs` jadvaliga yozadi (kim, qachon, oldingi/yangi
  qiymat). Audit jurnali ko'rigi (`/audit-logs`) **faqat o'qish** va **faqat
  Super Admin** uchun; o'chirish marshruti/endpointi **atayin yo'q**.

---

## Litsenziya

Ichki foydalanish uchun (proprietary) — Andijon davlat pedagogika instituti.
