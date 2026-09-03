# 05. Dashboard wireframe

**ADPI Doktorantura monitoringi va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi**

Ushbu hujjat bosh sahifa (dashboard) tuzilmasini matnli/ASCII wireframe ko'rinishida tavsiflaydi: katta tayyorlik ko'rsatkichi (hero), KPI kartalar to'plami, RAG rang afsonasi, grafik joylari, progress barlar va filtr paneli.

---

## 1. Umumiy tartib (layout)

```
┌──────────────┬───────────────────────────────────────────────────────────────┐
│              │  YUQORI PANEL (topbar):  ADPI logotipi | Qidiruv | 🔔(3) | Profil ▾ │
│  YON PANEL   ├───────────────────────────────────────────────────────────────┤
│  (SIDEBAR)   │  FILTR PANELI (quyida batafsil)                                 │
│              ├───────────────────────────────────────────────────────────────┤
│ ▸ Dashboard  │                                                                 │
│ ▸ Doktorant… │   HERO: MAXSUS DAVLAT AKKREDITATSIYASIGA TAYYORLIK — XX%        │
│ ▸ Ilmiy rah… │                                                                 │
│ ▸ Ixtisosl…  │   KPI KARTALAR GRIDI                                            │
│ ▸ Individu…  │                                                                 │
│ ▸ Ilmiy nat… │   GRAFIKLAR (inline-SVG)                                        │
│ ▸ Attestat…  │                                                                 │
│ ▸ Akkredit…  │   MEZONLAR BO'YICHA PROGRESS BARLAR                             │
│ ▸ Dalillar…  │                                                                 │
│ ▸ Kamchil…   │   SO'NGGI FAOLIYAT / MUDDATLAR                                  │
│ ▸ Action…    │                                                                 │
│ ▸ Ichki au…  │                                                                 │
│ ▸ Hisobot…   │                                                                 │
│ ▸ Bildiri…   │                                                                 │
│ ▸ Foydala…   │                                                                 │
│ ▸ Sozlama…   │                                                                 │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## 2. Filtr paneli (filter bar)

Dashboard tepasida joylashadi. Barcha KPI, grafik va progress barlar tanlangan filtrlarga qarab yangilanadi (AJAX orqali `/dashboard/data`).

```
┌───────────────────────────────────────────────────────────────────────────────┐
│ FILTRLAR:                                                                       │
│ [ O'quv yili ▾ ] [ Ixtisoslik ▾ ] [ Kafedra ▾ ] [ Doktorantura turi ▾ ]         │
│ [ Kurs/bosqich ▾ ] [ Ilmiy rahbar ▾ ] [ Akkreditatsiya holati ▾ ]  [Qo'llash]   │
└───────────────────────────────────────────────────────────────────────────────┘
```

| Filtr | Manba | Qiymatlar |
|-------|-------|-----------|
| O'quv yili | `individual_plans.academic_year` | masalan 2024/2025 |
| Ixtisoslik | `specialties` | ro'yxatdan |
| Kafedra | `departments` | ro'yxatdan |
| Doktorantura turi | `doctoral_students.student_type` | tayanch doktorant / doktorant / mustaqil izlanuvchi |
| Kurs/bosqich | `doctoral_students.course_stage` | 1, 2, 3 ... |
| Ilmiy rahbar | `supervisors` | ro'yxatdan |
| Akkreditatsiya holati | `accreditations.status` / RAG | planning / in_progress / submitted / completed; yoki RAG |

---

## 3. HERO — tayyorlik ko'rsatkichi

Dashboard'ning eng ko'zga tashlanadigan bloki. Katta doiraviy (gauge) yoki gorizontal progress bilan foizni ko'rsatadi.

```
┌───────────────────────────────────────────────────────────────────────────────┐
│                                                                                 │
│        MAXSUS DAVLAT AKKREDITATSIYASIGA TAYYORLIK                                │
│                                                                                 │
│                 ╭───────────────╮                                               │
│                 │      XX %      │   ◀── katta gauge (inline-SVG halqa)          │
│                 ╰───────────────╯                                               │
│                                                                                 │
│   [██████████████████░░░░░░░░]  XX%   (umumiy progress bar)                      │
│                                                                                 │
│   Holat: JARAYONDA   |   Oxirgi hisoblash: 2025-01-01   |   Sikl: 2024/2025     │
│                                                                                 │
│   ⚠ Eslatma: mezon/indikatorlar hozircha PLACEHOLDER (namunaviy) ma'lumotlar     │
│     asosida. Rasmiy qiymatlar bilan almashtirilishi kerak (06-hujjatga qarang).  │
└───────────────────────────────────────────────────────────────────────────────┘
```

- Foiz `ScoringEngine` tomonidan sozlanadigan og'irliklar va chegaralar asosida hisoblanadi (qarang [06-accreditation-module.md](06-accreditation-module.md)).
- Placeholder ma'lumotlar ishlatilayotgan bo'lsa, banner ko'rsatiladi.

---

## 4. KPI kartalar gridi

```
┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐
│ Jami doktorantlar    │ │ Faol individual reja │ │ Ilmiy natijalar      │
│        142           │ │        128           │ │        356           │
│  ▲ +6 (bu chorak)    │ │  tasdiqlangan: 110   │ │  tasdiqlangan: 300   │
└──────────────────────┘ └──────────────────────┘ └──────────────────────┘
┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐
│ Yuklangan dalillar   │ │ Akkreditatsiya       │ │ Ochiq kamchiliklar   │
│        512           │ │ indikatorlari: 40    │ │        17            │
│  tasdiqlangan: 430   │ │ 🟢18 🟡12 🔴6 ⚪4     │ │  yuqori: 3           │
└──────────────────────┘ └──────────────────────┘ └──────────────────────┘
┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐
│ Chora-tadbirlar      │ │ Muddati o'tган       │ │ Attestatsiya         │
│ jarayonda: 12        │ │ vazifalar: 9         │ │ o'tganlar: 88%       │
│  yakunlangan: 25     │ │  ⚠ e'tibor talab     │ │  keyingi: 2025-02    │
└──────────────────────┘ └──────────────────────┘ └──────────────────────┘
```

> Kartalardagi raqamlar — namunaviy (illustrativ) qiymatlar; haqiqiy qiymatlar filtr va ma'lumotlar bazasidan olinadi.

KPI kartalar to'plami (item 3):
- Jami doktorantlar (tur bo'yicha taqsimot bilan)
- Faol/tasdiqlangan individual rejalar
- Ilmiy natijalar (jami/tasdiqlangan)
- Yuklangan/tasdiqlangan dalillar
- Akkreditatsiya indikatorlari (RAG taqsimoti bilan)
- Ochiq kamchiliklar (jiddiylik bo'yicha)
- Chora-tadbirlar (jarayonda/yakunlangan)
- Muddati o'tgan vazifalar
- Attestatsiya ko'rsatkichi

---

## 5. RAG rang afsonasi (legend)

```
┌───────────────────────────────────────────────────────────────────────────────┐
│  RAG HOLAT AFSONASI:                                                            │
│   🟢 Yashil  — Talab bajarilgan (to'liq)                                        │
│   🟡 Sariq   — Qisman bajarilgan                                                │
│   🔴 Qizil   — Bajarilmagan / muammo                                            │
│   ⚪ Kulrang  — Ma'lumot kiritilmagan                                            │
└───────────────────────────────────────────────────────────────────────────────┘
```

| Rang | Holat kodi | Ma'nosi |
|------|-----------|---------|
| Yashil (green) | `green` | Talab bajarilgan |
| Sariq (yellow) | `yellow` | Qisman bajarilgan |
| Qizil (red) | `red` | Bajarilmagan / muammo |
| Kulrang (grey) | `grey` | Ma'lumot kiritilmagan |

---

## 6. Grafiklar (inline-SVG, JS kutubxonasisiz)

```
┌────────────────────────────────────┐ ┌────────────────────────────────────┐
│ Mezonlar bo'yicha tayyorlik (bar)   │ │ Indikatorlar RAG taqsimoti (donut)  │
│  ▐█████▌ Mezon 1  72%              │ │        🟢45%  🟡30%                  │
│  ▐███▌   Mezon 2  48%              │ │        🔴15%  ⚪10%                  │
│  ▐██████▌Mezon 3  85%              │ │                                     │
│  ▐██▌    Mezon 4  33%              │ │      (donut inline-SVG)             │
└────────────────────────────────────┘ └────────────────────────────────────┘
┌────────────────────────────────────┐ ┌────────────────────────────────────┐
│ Ilmiy natijalar dinamikasi (line)   │ │ Kafedralar bo'yicha holat (stacked) │
│  natijalar soni oy bo'yicha         │ │  har kafedra 🟢🟡🔴⚪ ustunlari      │
│  (line chart inline-SVG)            │ │  (stacked bar inline-SVG)           │
└────────────────────────────────────┘ └────────────────────────────────────┘
```

- Barcha grafiklar server tomonda SVG sifatida render qilinadi (Chart.js kabi tashqi kutubxona ishlatilmaydi — oflayn cheklov, qarang [01-architecture.md](01-architecture.md)).

---

## 7. Mezonlar bo'yicha progress barlar

```
┌───────────────────────────────────────────────────────────────────────────────┐
│  AKKREDITATSIYA MEZONLARI BO'YICHA TAYYORLIK                                     │
│                                                                                 │
│  Mezon 1 (namunaviy)   [████████████████░░░░]  72%  🟡                          │
│  Mezon 2 (namunaviy)   [██████████░░░░░░░░░░]  48%  🟡                          │
│  Mezon 3 (namunaviy)   [█████████████████░░░]  85%  🟢                          │
│  Mezon 4 (namunaviy)   [███████░░░░░░░░░░░░░]  33%  🔴                          │
│                                                                                 │
│  (mezon nomlari administrator kiritgan/sozlagan qiymatlar)                       │
└───────────────────────────────────────────────────────────────────────────────┘
```

---

## 8. So'nggi faoliyat va yaqin muddatlar

```
┌───────────────────────────────────┐ ┌───────────────────────────────────┐
│ SO'NGGI FAOLIYAT                    │ │ YAQIN MUDDATLAR                     │
│ • Yangi dalil yuklandi (2 soat)     │ │ • Reja vazifasi — 3 kun qoldi        │
│ • Indikator baholandi 🟢 (5 soat)   │ │ • Attestatsiya — 12 kun qoldi        │
│ • Kamchilik yopildi (kecha)         │ │ • Chora-tadbir muddati — 5 kun       │
│ • Chora-tadbir yaratildi (kecha)    │ │ • Audit rejalashtirilgan — 20 kun    │
└───────────────────────────────────┘ └───────────────────────────────────┘
```

---

## Xulosa

Dashboard tayyorlik indeksini eng yuqorida ko'rsatadi, so'ng KPI kartalar, RAG afsona, grafiklar va progress barlar orqali umumiy holatni tez tushunishga imkon beradi. Barcha bloklar filtr paneliga bo'ysunadi va placeholder ma'lumotlar ishlatilayotganda ogohlantirish banneri ko'rsatiladi.
