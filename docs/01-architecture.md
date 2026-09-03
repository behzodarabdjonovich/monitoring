# 01. Tizim arxitekturasi

**ADPI Doktorantura monitoringi va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi**

Ushbu hujjat tizimning umumiy arxitekturasini, so'rov hayot siklini, xavfsizlik qatlamlarini, asosiy xizmat komponentlarini (ScoringEngine, AuditLogger), fayllarni saqlash tuzilmasini va texnologik stek tanlovining asoslarini tavsiflaydi.

---

## 1. Umumiy yondashuv

Tizim **qatlamli (layered) maxsus PHP MVC** arxitekturasi asosida qurilgan. Tashqi freymvork (Laravel, Symfony va h.k.) ishlatilmaydi. Barcha kod va aktivlar (assets) repozitoriya ichida qo'lda yoziladi va joylashtiriladi (vendored).

Arxitektura tamoyillari:

- **Bitta kirish nuqtasi (single entry point):** barcha HTTP so'rovlari `public/index.php` front-kontroller orqali o'tadi.
- **Vazifalar ajratilishi (separation of concerns):** marshrutlash, autentifikatsiya, avtorizatsiya (RBAC), biznes-mantiq, ma'lumotlarga kirish va ko'rsatish qatlamlari bir-biridan ajratilgan.
- **Xavfsizlik sukut bo'yicha (secure by default):** har bir so'rov autentifikatsiya, RBAC va CSRF middleware'lari orqali tekshiriladi; har bir chiqish (output) ekranlanadi (escaped).
- **Ma'lumotlarga asoslangan sozlash (data-driven configuration):** akkreditatsiya mezonlari, indikatorlari, og'irliklari (weights) va chegaralari (thresholds) kodda emas, ma'lumotlar bazasida saqlanadi va administrator tomonidan sozlanadi.

---

## 2. Qatlamli struktura

```
                         HTTP so'rov (Request)
                                │
                                ▼
                ┌───────────────────────────────┐
                │   public/index.php             │  Front-kontroller
                │   (bootstrap, autoload, env)   │  (yagona kirish nuqtasi)
                └───────────────┬───────────────┘
                                ▼
                ┌───────────────────────────────┐
                │   Router                       │  URL + HTTP metod -> Controller@action
                └───────────────┬───────────────┘
                                ▼
                ┌───────────────────────────────┐
                │   Middleware quvuri (pipeline) │
                │   1) AuthMiddleware  (sessiya) │
                │   2) RbacMiddleware  (ruxsat)  │
                │   3) CsrfMiddleware  (token)   │
                └───────────────┬───────────────┘
                                ▼
                ┌───────────────────────────────┐
                │   Controller                   │  So'rovni qabul qiladi, validatsiya,
                │   (biznes-mantiqni chaqiradi)  │  javob tayyorlaydi
                └───────────────┬───────────────┘
                                ▼
                ┌───────────────────────────────┐
                │   Service qatlami              │  ScoringEngine, AuditLogger,
                │   (domain xizmatlari)          │  FileStorage, NotificationService
                └───────────────┬───────────────┘
                                ▼
                ┌───────────────────────────────┐
                │   Model (PDO)                  │  Ma'lumotlarga kirish (data access),
                │   (tayyorlangan so'rovlar)     │  prepared statements
                └───────────────┬───────────────┘
                                ▼
                ┌───────────────────────────────┐
                │   Ma'lumotlar bazasi           │  PDO: SQLite (dev) / PostgreSQL / MySQL
                └───────────────────────────────┘
                                │
                                ▼
                ┌───────────────────────────────┐
                │   View (server-render PHP)     │  Layout + partiallar + modul sahifalari,
                │   e() ekranlash helperi         │  inline-SVG grafiklar
                └───────────────┬───────────────┘
                                ▼
                         HTTP javob (Response)
```

### Kataloglar tuzilmasi

```
monitoring/
├── public/                 # webroot: front-kontroller + aktivlar
│   ├── index.php           # yagona kirish nuqtasi (front controller)
│   └── assets/
│       ├── css/            # qo'lda yozilgan dizayn tizimi (design system)
│       └── js/             # minimal vanilla JS (filtrlar, modallar)
├── src/
│   ├── Core/               # freymvork yadrosi
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── View.php
│   │   ├── DB.php          # PDO ulanish abstraksiyasi
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   ├── Validator.php
│   │   ├── Middleware/     # AuthMiddleware, RbacMiddleware, CsrfMiddleware
│   │   ├── ScoringEngine.php
│   │   └── AuditLogger.php
│   ├── Controllers/        # har modul uchun kontroller
│   └── Models/             # PDO modellari (har jadval uchun)
├── resources/
│   └── views/              # layout, sidebar, partiallar, modul sahifalari
├── database/
│   ├── migrations/         # sxema (portable SQL)
│   ├── seeds/              # boshlang'ich ma'lumotlar (placeholder demo)
│   └── database.sqlite     # dev DB (gitignored, migrate/seed bilan yaratiladi)
├── storage/                # yuklangan dalil fayllari (webroot'dan tashqarida)
├── config/                 # sozlamalar (env asosida)
├── bin/console             # CLI: migrate, seed, backup
├── tests/                  # dependency-free PHP test runner
└── docs/                   # ushbu loyihaviy hujjatlar
```

---

## 3. So'rov hayot sikli (request lifecycle)

1. **Bootstrap:** `public/index.php` PSR-4 autoloader'ni (`vendor/autoload.php`), konfiguratsiyani va sessiyani ishga tushiradi.
2. **Router:** so'rov URL'i va HTTP metodi mos keladigan `Controller@action`ga moslanadi. Mos kelmasa 404 qaytariladi.
3. **Middleware quvuri:** marshrutga biriktirilgan middleware'lar navbatma-navbat ishlaydi:
   - **AuthMiddleware** — sessiyada autentifikatsiya qilingan foydalanuvchi borligini tekshiradi; bo'lmasa `/login`ga yo'naltiradi.
   - **RbacMiddleware** — foydalanuvchi rolining marshrut talab qiladigan ruxsatga (permission) egaligini ma'lumotlar bazasidan yuklangan `role -> permission` xaritasi bo'yicha tekshiradi; huquqi bo'lmasa 403 qaytaradi.
   - **CsrfMiddleware** — o'zgartiruvchi (POST/PUT/PATCH/DELETE) so'rovlarda sessiya CSRF tokenini tekshiradi.
4. **Controller:** kirish ma'lumotlarini `Validator` orqali tekshiradi, kerakli Service/Model'larni chaqiradi.
5. **Service qatlami:** domen mantig'i (masalan, `ScoringEngine` tayyorlik indeksini hisoblaydi, `AuditLogger` audit yozuvini yaratadi).
6. **Model (PDO):** faqat tayyorlangan so'rovlar (prepared statements) orqali ma'lumotlar bazasi bilan ishlaydi.
7. **View:** natija server tomonda PHP shablonlar orqali render qilinadi; layout + partiallar merosxo'rligi (inheritance) ishlatiladi; barcha dinamik chiqish `e()` helperi bilan ekranlanadi.
8. **Response:** HTTP javob (HTML, JSON yoki fayl) mijozga qaytariladi.

---

## 4. Xavfsizlik qatlamlari

| Qatlam | Chora | Tavsif |
|--------|-------|--------|
| Autentifikatsiya | `password_hash` / `password_verify` | Parollar bcrypt/argon2 bilan xesh qilinadi. Sessiya asosidagi login. |
| Avtorizatsiya (RBAC) | `RbacMiddleware` | Rol -> ruxsat xaritasi DB'dan yuklanadi; har marshrutda talab qilinadigan ruxsat tekshiriladi. |
| CSRF himoyasi | `Csrf` + `CsrfMiddleware` | Har sessiyada CSRF token; o'zgartiruvchi so'rovlarda majburiy. |
| XSS himoyasi | `e()` ekranlash helperi | Barcha view'larda dinamik chiqish HTML-ekranlanadi. |
| SQL-inyeksiya himoyasi | PDO prepared statements | Barcha so'rovlar parametrlangan; string konkatenatsiya yo'q. |
| Fayl yuklash xavfsizligi | kengaytma + MIME + hajm validatsiyasi | Fayllar webroot'dan tashqarida `storage/`da saqlanadi, himoyalangan yuklab olish kontrolleri orqali beriladi. |
| Audit | `AuditLogger` (immutable) | create/update/upload/approve amallarida o'zgarmas audit yozuvi. |
| Sessiya xavfsizligi | HttpOnly, SameSite cookie, regenerate id | Sessiya o'g'irlanishiga qarshi. |

---

## 5. Asosiy xizmat komponentlari

### 5.1 ScoringEngine (baholash mexanizmi)

- Akkreditatsiya indikatorlari va mezonlarining RAG holatini (yashil/sariq/qizil/kulrang) va umumiy **tayyorlik indeksini** hisoblaydi.
- Og'irliklar (weights) va chegaralar (thresholds) **kodda emas**, `accreditation_criteria` / `accreditation_indicators` jadvallaridan o'qiladi — ya'ni administrator tomonidan sozlanadi.
- Hisoblash algoritmi: har indikator bahosi -> mezon darajasida og'irlikli o'rtacha -> akkreditatsiya darajasida umumiy foizli tayyorlik. Batafsil: [06-accreditation-module.md](06-accreditation-module.md).

### 5.2 AuditLogger (audit jurnali)

- `create`, `update`, `upload`, `approve`, `delete` kabi muhim amallarda `audit_logs` jadvaliga **o'zgarmas (immutable)** yozuv qo'shadi.
- Yozuvda: kim (user_id), qachon, qaysi obyekt (entity + id), qanday amal, eski/yangi qiymatlar (JSON) saqlanadi.

### 5.3 FileStorage (fayl saqlash)

- Dalil (evidence) fayllari `storage/` katalogida, **webroot'dan tashqarida** saqlanadi.
- Yuklashda: kengaytma oq ro'yxati (whitelist), MIME-tur tekshiruvi, maksimal hajm cheklovi.
- Yuklab olish faqat autentifikatsiya + RBAC tekshiruvidan o'tgan **himoyalangan download kontrolleri** orqali amalga oshiriladi (to'g'ridan-to'g'ri URL bilan kirish yo'q).

### 5.4 NotificationService (bildirishnomalar)

- Muddatlar, tasdiqlash kutayotgan elementlar, yangi kamchiliklar bo'yicha tizim ichidagi bildirishnomalarni `notifications` jadvaliga yozadi.

---

## 6. Ma'lumotlar bazasi va ko'chiriluvchanlik (portability)

- **Dev DB:** PDO SQLite (`database/database.sqlite`).
- **Ishlab chiqarish (production):** sxema **PostgreSQL** yoki **MySQL**ga ko'chiriladigan qilib loyihalangan.
- Ko'chiriluvchanlikni ta'minlash uchun:
  - Barcha ma'lumotlarga kirish `DB` abstraksiyasi va PDO orqali amalga oshiriladi.
  - Faqat **portativ ustun turlari** ishlatiladi (INTEGER, TEXT, REAL, TIMESTAMP/DATETIME, BOOLEAN sifatida INTEGER/SMALLINT).
  - SQLite'ga xos SQL konstruksiyalaridan (app mantig'ida) qochiladi.
  - Migratsiyalar sxemani neytral tarzda ta'riflaydi; drayverga xos farqlar `DB` qatlamida hal qilinadi.

---

## 7. Texnologik stek tanlovi va uning asoslari

> **Nima uchun tashqi bog'liqliksiz maxsus PHP MVC?**

Tizim **oflayn (izolyatsiyalangan) muhitda** ishlab chiqilmoqda (tarmoq rejimi: INTEGRATIONS_ONLY). Bu muhitda:

- **Packagist** bloklangan — `composer install` orqali paketlarni (Laravel, Symfony va h.k.) yuklab bo'lmaydi.
- **npm registry** bloklangan — Vite/Webpack/React/Vue/Tailwind (npm orqali) o'rnatib bo'lmaydi.
- **CDN'lar** (jsdelivr va boshqalar) bloklangan — Chart.js, Bootstrap va shu kabilarni CDN orqali ulab bo'lmaydi.

Shu sababli:

| Qaror | Sabab |
|-------|-------|
| **Dependency-free maxsus PHP MVC** | Tashqi freymvorklarni oflayn o'rnatib bo'lmaydi; yadro qo'lda yoziladi va repozitoriyada vendor qilinadi. |
| **PHP 8.4 + PDO** | Muhitda oldindan o'rnatilgan va tasdiqlangan (validated). |
| **PDO SQLite (dev)** | Sandbox'da PostgreSQL/MySQL **demoni** ishlamaydi (faqat klient/drayverlar mavjud); SQLite fayl asosida ishlaydi. Sxema portativ saqlanadi. |
| **Qo'lda yozilgan CSS dizayn tizimi** | Tailwind/Bootstrap'ni npm/CDN orqali olib bo'lmaydi; CSS repozitoriyada vendor qilinadi. |
| **Inline-SVG grafiklar** | Chart.js kabi JS grafik kutubxonalarini CDN orqali olib bo'lmaydi; grafiklar server tomonda SVG sifatida render qilinadi. |
| **Composer faqat `dump-autoload`** | Composer'ning yagona oflayn ishlaydigan xususiyati — mahalliy PSR-4 autoloader generatsiyasi. Hech qanday paket yuklanmaydi. |

Ushbu qarorlar tizimni tashqi tarmoqqa bog'liq bo'lmagan holda to'liq quriladigan, ishga tushiriladigan va sinovdan o'tkaziladigan qiladi, shu bilan birga sxema va abstraksiyalar tufayli ishlab chiqarish uchun PostgreSQL/MySQL'ga oson ko'chiriladi.

---

## 8. Xulosa

Arxitektura sodda, xavfsiz va ko'chiriluvchan bo'lishga qaratilgan. U yadro (Core) freymvork, aniq ajratilgan qatlamlar va ma'lumotlarga asoslangan sozlash orqali kelgusi bosqichlarda (migratsiyalar, kontrollerlar, view'lar, akkreditatsiya moduli) barqaror rivojlanishga imkon beradi.
