# ADPI Doktorantura Monitoringi va Maxsus Davlat Akkreditatsiyasiga Tayyorgarlik Axborot Tizimi

**Andijon davlat pedagogika instituti (ADPI)**

Ushbu hujjatlar to'plami tizimni ishlab chiqishdan **oldin** tayyorlangan loyihaviy (dizayn) hujjatlaridir. Ular foydalanuvchi topshirig'ining 22-bandida talab qilingan yettita dastlabki tayyorgarlik hujjatini o'z ichiga oladi.

> **MUHIM ogohlantirish (uydirmadan voz kechish qoidasi):** Akkreditatsiya mezonlari, indikatorlari, huquqiy hujjat raqamlari, muddatlar va majburiy talablar **taxmin qilinmagan yoki uydirilmagan**. Tizim oflayn muhitda ishlab chiqilmoqda va Ta'lim sifatini ta'minlash milliy agentligi hamda Vazirlar Mahkamasi qarorlaridagi rasmiy qiymatlarni tekshirib bo'lmadi. Shu sababli barcha akkreditatsiya mezon/indikatorlari **ma'lumotlarga asoslangan (data-driven)** va **administrator tomonidan sozlanadigan** qilib loyihalangan. Har qanday boshlang'ich (seed) mezonlar shartli namunaviy (placeholder demo) ma'lumotlar bo'lib, ishlab chiqarishga (production) o'tishdan oldin tekshirilgan rasmiy qiymatlar bilan almashtirilishi shart. Batafsil: [06-accreditation-module.md](06-accreditation-module.md).

---

## Hujjatlar ro'yxati

| № | Hujjat | Mazmuni |
|---|--------|---------|
| 1 | [01-architecture.md](01-architecture.md) | Tizim arxitekturasi: qatlamli (layered) maxsus PHP MVC, so'rov hayot sikli, xavfsizlik qatlamlari, ScoringEngine, AuditLogger, fayl saqlash, texnologiyalar tanlovi asoslari |
| 2 | [02-er-diagram.md](02-er-diagram.md) | Ma'lumotlar bazasi ER diagrammasi: 24 ta jadval, PK/FK, 1:M va M:N bog'lanishlar, Mermaid erDiagram |
| 3 | [03-roles-permissions.md](03-roles-permissions.md) | 9 ta foydalanuvchi roli va ruxsatlar matritsasi (permission matrix) |
| 4 | [04-pages.md](04-pages.md) | Barcha sahifalar/marshrutlar (routes) 16 ta yon panel bo'limi bo'yicha |
| 5 | [05-dashboard-wireframe.md](05-dashboard-wireframe.md) | Bosh sahifa (dashboard) wireframe'i: tayyorlik ko'rsatkichi, KPI kartalar, RAG afsona, grafiklar, filtrlar |
| 6 | [06-accreditation-module.md](06-accreditation-module.md) | Akkreditatsiya moduli strukturasi: 8 bosqichli iyerarxiya, RAG holatlar, sozlanadigan baholash |
| 7 | [07-uiux.md](07-uiux.md) | UI/UX dizayn konsepsiyasi: rang palitrasi, tipografika, komponentlar, moslashuvchanlik, foydalanish qulayligi |

---

## Tizimning markaziy ma'lumot zanjiri (22-band)

Butun tizim quyidagi uzluksiz zanjir atrofida qurilgan. Har bir bo'g'in o'zidan keyingisiga bevosita ma'lumot yetkazib beradi va oxir-oqibat akkreditatsiyaga tayyorlik ko'rsatkichini shakllantiradi:

```
DOKTORANT
   │  (kim monitoring qilinmoqda)
   ▼
INDIVIDUAL REJA
   │  (nima rejalashtirilgan: vazifalar, muddatlar)
   ▼
ILMIY NATIJA
   │  (nima bajarildi: maqolalar, konferensiyalar, ilmiy natijalar)
   ▼
DALIL (evidence)
   │  (natijani tasdiqlovchi hujjat/fayl)
   ▼
MONITORING
   │  (natija va dalillar holatini kuzatish, RAG baholash)
   ▼
AKKREDITATSIYA INDIKATORI
   │  (dalillar qaysi rasmiy indikatorni qoplaydi)
   ▼
KAMCHILIK (deficiency)
   │  (indikator to'liq qoplanmagan joyi)
   ▼
CHORA-TADBIR (action plan)
   │  (kamchilikni bartaraf etish rejasi)
   ▼
NAZORAT
   │  (chora-tadbir bajarilishini nazorat qilish, ichki audit)
   ▼
HISOBOT
       (yakuniy tahliliy hisobotlar va tayyorlik indeksi)
```

Ushbu zanjir tizimning ma'lumotlar modeli (ER diagramma), rollar, sahifalar va akkreditatsiya modulida to'liq aks ettirilgan.

---

## Texnologik stek (qisqacha)

- **Backend:** PHP 8.4, tashqi bog'liqliksiz (dependency-free) maxsus (custom) MVC freymvork.
- **Ma'lumotlar bazasi:** PDO SQLite (ishlab chiqish uchun), PostgreSQL/MySQL'ga ko'chiriladigan (portable) sxema.
- **Frontend:** server tomonda render qilinadigan PHP shablonlar, qo'lda yozilgan (hand-authored) moslashuvchan CSS dizayn tizimi, inline-SVG grafiklar (JS grafik kutubxonasisiz).
- **Autoload:** Composer faqat mahalliy PSR-4 autoload generatsiyasi uchun (`composer dump-autoload`, oflayn ishlaydi).

Batafsil asoslar va sabablar: [01-architecture.md](01-architecture.md).
