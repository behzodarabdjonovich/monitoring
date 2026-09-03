# 07. UI/UX dizayn konsepsiyasi

**ADPI Doktorantura monitoringi va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi**

Ushbu hujjat tizimning foydalanuvchi interfeysi va foydalanish tajribasi (UI/UX) konsepsiyasini tavsiflaydi: umumiy uslub, tartib, rang palitrasi, tipografika, komponentlar to'plami, moslashuvchanlik va foydalanish qulayligi (accessibility).

---

## 1. Umumiy uslub va tamoyillar

- **Til:** o'zbek tili (lotin yozuvi). Barcha interfeys matni, tugmalar, xabarlar o'zbekcha.
- **Uslub:** akademik / davlat muassasasi uslubi — jiddiy, tiniq, ishonchli. Ortiqcha bezaklarsiz, ma'lumotga yo'naltirilgan (data-first).
- **Tamoyillar:**
  - Aniqlik va soddalik: har sahifada asosiy amal aniq ko'rinadi.
  - Izchillik (consistency): bir xil komponentlar hamma joyda bir xil ko'rinadi va ishlaydi.
  - Ma'lumotni tez o'qish: RAG ranglari, KPI kartalar, progress barlar orqali holatni bir qarashda tushunish.
  - Ishonchlilik: placeholder ma'lumotlar aniq belgilanadi (uydirmadan qochish tamoyili UI darajasida ham).

---

## 2. Tartib (layout)

- **Chap yon panel (left sidebar):** doimiy, 16 ta bo'lim (qarang [04-pages.md](04-pages.md)). Kichik ekranlarda yig'iladigan (collapsible) / off-canvas.
- **Yuqori panel (topbar):** logotip, global qidiruv, bildirishnoma ikonkasi (o'qilmaganlar soni bilan), profil menyusi.
- **Asosiy kontent:** breadcrumb (non-zanjir), sahifa sarlavhasi, amal tugmalari, kontent bloklari.
- **Grid:** 12-ustunli moslashuvchan grid; kartalar va jadvallar shu gridга joylashadi.

```
┌──────────┬───────────────────────────────────────┐
│  SIDEBAR │  TOPBAR                                 │
│          ├───────────────────────────────────────┤
│  16 ta   │  Breadcrumb > Sahifa sarlavhasi         │
│  bo'lim  │  [Amal tugmalari]                       │
│          │  ── Kontent (kartalar/jadvallar) ──     │
└──────────┴───────────────────────────────────────┘
```

---

## 3. Rang palitrasi

Davlat/akademik muassasa uslubiga mos, ko'k asosli ishonchli palitra.

| Rol | Rang (namunaviy HEX) | Ishlatilishi |
|-----|----------------------|--------------|
| Asosiy (primary) | `#1F4E79` (to'q ko'k) | topbar, asosiy tugmalar, sarlavhalar |
| Ikkilamchi (secondary) | `#2E75B6` (ko'k) | havolalar, faol holat |
| Fon (background) | `#F5F7FA` (och kulrang) | umumiy fon |
| Kartalar foni | `#FFFFFF` | kartalar, panellar |
| Matn | `#1B1F24` (deyarli qora) | asosiy matn |
| Ikkilamchi matn | `#5A6675` | izohlar, meta |

### RAG ranglari (holat indikatorlari)

| Holat | Rang (HEX) | Ma'nosi |
|-------|-----------|---------|
| 🟢 Yashil | `#2E9E5B` | Talab bajarilgan |
| 🟡 Sariq | `#E0A800` | Qisman bajarilgan |
| 🔴 Qizil | `#D64545` | Bajarilmagan / muammo |
| ⚪ Kulrang | `#9AA5B1` | Ma'lumot kiritilmagan |

> RAG ranglari faqat rang bilan emas, matn yorlig'i (label) va ikonka/shakl bilan ham ajratiladi (rang ko'rishda qiyinchilikка ega foydalanuvchilar uchun).

---

## 4. Tipografika

- **Asosiy shrift:** tizim sans-serif steki (masalan `system-ui, "Segoe UI", Roboto, Arial, sans-serif`) — internetga bog'liqliksiz, o'zbek lotin belgilarini (o', g', sh, ch) to'liq qo'llab-quvvatlaydi. CDN'dan shrift yuklanmaydi (oflayn cheklov).
- **O'lchamlar (ierarxiya):** H1 24–28px, H2 20px, H3 16px, asosiy matn 14–15px, meta 12–13px.
- **Qatorlar oralig'i:** o'qishga qulay 1.4–1.6.

---

## 5. Komponentlar to'plami (component inventory)

| Komponent | Tavsif |
|-----------|--------|
| **Kartalar (cards)** | KPI kartalar, indikator kartalari, ma'lumot bloklari. |
| **Jadvallar (tables)** | ro'yxatlar (doktorantlar, rejalar, hujjatlar); saralash, sahifalash (pagination), qator amallari. |
| **Yorliqlar/badge'lar (badges)** | RAG holat, status (draft/approved), tur belgilari. |
| **Progress barlar** | tayyorlik foizi, mezon progressi (gorizontal). |
| **Gauge (halqa)** | dashboard hero'dagi umumiy tayyorlik indeksi (inline-SVG). |
| **Grafiklar (charts)** | bar/donut/line — inline-SVG (JS kutubxonasisiz). |
| **Filtrlar (filters)** | dropdown filtr paneli (dashboard va ro'yxatlarda). |
| **Modallar (modals)** | tez qo'shish/tahrir, tasdiqlash dialoglari. |
| **Formalar (forms)** | validatsiya xabarlari, majburiy maydonlar, fayl yuklash. |
| **Bildirishnoma banner** | placeholder ma'lumot ogohlantirishi, xato/muvaffaqiyat xabarlari (toast/alert). |
| **Breadcrumb** | navigatsiya konteksti. |
| **Yon panel elementi** | ikonka + matn, faol holat, ochiladigan kichik menyular. |

---

## 6. Moslashuvchanlik (responsive)

Uch asosiy uzilish nuqtasi (breakpoints):

| Qurilma | Kenglik | Xatti-harakat |
|---------|---------|---------------|
| **Desktop** | ≥ 1024px | yon panel doimiy ochiq, ko'p ustunli grid. |
| **Planshet (tablet)** | 640–1023px | yon panel yig'iladigan, 2 ustunli grid. |
| **Telefon (phone)** | < 640px | yon panel off-canvas (gamburger menyu), 1 ustunli, jadvallar gorizontal scroll yoki kartalarga aylanadi. |

- CSS qo'lda yozilgan (hand-authored) dizayn tizimi orqali amalga oshiriladi (Tailwind/Bootstrap npm/CDN orqali olib bo'lmaydi — oflayn cheklov, qarang [01-architecture.md](01-architecture.md)).

---

## 7. Foydalanish qulayligi (accessibility)

- **Kontrast:** matn/fon kontrasti WCAG AA darajasiga mos (kamida 4.5:1).
- **Rangdan tashqari signal:** RAG holatlar rang + matn + shakl bilan beriladi (faqat rangga tayanmaslik).
- **Klaviatura:** barcha interaktiv elementlar klaviatura bilan boshqariladi (tab tartibi, focus ko'rinishi).
- **ARIA:** modallar, navigatsiya, jadvallar uchun mos ARIA atributlari.
- **Formalar:** har maydonда `label`, xato xabarlari matn bilan (nafaqat rang).
- **Til atributi:** `<html lang="uz">`.
- **Alt matn:** ikonka/rasmlar uchun ma'noli alternativa yoki dekorativ bo'lsa `aria-hidden`.

---

## 8. Xulosa

UI/UX konsepsiyasi o'zbek tilidagi, akademik-davlat uslubidagi, ma'lumotга yo'naltirilgan, moslashuvchan va foydalanish qulay interfeysni belgilaydi. Barcha vizual aktivlar oflayn muhitda ishlashi uchun repozitoriya ichida qo'lda yoziladi; RAG tizimi va placeholder ogohlantirishlari interfeysда aniq va izchil ko'rsatiladi.
