# 06. Akkreditatsiya moduli strukturasi

**ADPI Doktorantura monitoringi va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi**

Ushbu hujjat akkreditatsiya modulining tuzilmasini tavsiflaydi: sakkiz bosqichli iyerarxiya, indikator kartasi maydonlari, to'rtta RAG baholash holati va sozlanadigan tayyorlik indeksi metodologiyasi.

---

> ## ⚠️ ENG MUHIM OGOHLANTIRISH: PLACEHOLDER (NAMUNAVIY) MEZONLAR
>
> Ushbu tizimga **boshlang'ich (seed) sifatida kiritiladigan barcha akkreditatsiya mezonlari, indikatorlari, talablari, og'irliklari, chegaralari, muddatlari va huquqiy hujjat havolalari — SHARTLI NAMUNAVIY (PLACEHOLDER DEMO) MA'LUMOTLARDIR.**
>
> **Nima uchun?** Tizim **oflayn (izolyatsiyalangan) muhitda** ishlab chiqilmoqda va **rasmiy manbalarni tekshirib bo'lmadi**. Aniqrog'i, quyidagilar tasdiqlanmadi:
> - **Ta'lim sifatini ta'minlash milliy agentligi** ning akkreditatsiya mezonlari va indikatorlari;
> - **Vazirlar Mahkamasi qarorlari** va boshqa normativ-huquqiy hujjatlar (raqamlari, sanalari);
> - Majburiy talablar, muddatlar va me'yoriy qiymatlar.
>
> **Shu sababli tizim hech qanday aniq huquqiy mezon, indikator kodi, hujjat raqami yoki muddatni "rasmiy/haqiqiy" sifatida taqdim etmaydi.** Barcha bunday qiymatlar **taxmin qilinmagan va uydirilmagan**.
>
> **Amaliy yechim:**
> - Barcha mezon/indikatorlar **ma'lumotlar bazasida saqlanadi** va **administrator tomonidan to'liq sozlanadi** (data-driven).
> - Har bir namunaviy yozuv `is_placeholder = true` bayrog'i bilan belgilanadi.
> - Interfeysda (dashboard va akkreditatsiya sahifalarida) **ko'rinarli banner** chiqadi: "Namunaviy ma'lumotlar — rasmiy qiymatlar bilan almashtirilishi kerak".
> - Faqat aniq namunaviy nomlar ishlatiladi (masalan "Mezon 1 (namunaviy)", "Indikator 1.1 (namunaviy)"); haqiqiy shaxsiy ma'lumotlar yo'q.
>
> **Ishlab chiqarishga (production) o'tishdan oldin** ushbu placeholder ma'lumotlar **tekshirilgan rasmiy** Ta'lim sifatini ta'minlash milliy agentligi / Vazirlar Mahkamasi qiymatlari bilan **almashtirilishi shart**.

---

## 1. Sakkiz bosqichli iyerarxiya

Akkreditatsiya moduli quyidagi ketma-ket iyerarxiya asosida qurilgan (item 9):

```
AKKREDITATSIYA (Accreditation)
   │  sikl/tur, umumiy tayyorlik indeksi
   ▼
MEZON (Criteria)
   │  yirik baholash bo'limi, o'z og'irligiga (weight) ega
   ▼
INDIKATOR (Indicator)
   │  o'lchanadigan ko'rsatkich, o'z og'irligiga ega
   ▼
TALAB (Requirement)
   │  indikator qanoatlantirishi kerak bo'lgan shart
   ▼
DALIL (Evidence / Document)
   │  talabni tasdiqlovchi hujjat/fayl (documents <-> indicators M:N)
   ▼
BAHO (Assessment / RAG)
   │  indikatorning RAG holati va bali
   ▼
KAMCHILIK (Deficiency)
   │  talab to'liq qoplanmaganda aniqlanadi
   ▼
CHORA-TADBIR (Action Plan)
        kamchilikni bartaraf etish rejasi (mas'ul + muddat + holat)
```

Ma'lumotlar bazasidagi mos jadvallar: `accreditations` → `accreditation_criteria` → `accreditation_indicators` → (talab: `accreditation_indicators.requirement`) → `documents` (`indicator_evidence` pivot orqali) → baho (`accreditation_indicators.rag_status` + `score`) → `deficiencies` → `action_plans`. Batafsil: [02-er-diagram.md](02-er-diagram.md).

---

## 2. Indikator kartasi maydonlari

Har bir indikator interfeysda "karta" ko'rinishida ko'rsatiladi. Karta maydonlari (item 9):

| Maydon | Manba (jadval.ustun) | Tavsif |
|--------|----------------------|--------|
| Indikator kodi | `accreditation_indicators.code` | masalan "1.1 (namunaviy)" |
| Talab (requirement) | `accreditation_indicators.requirement` | indikator qanoatlantirishi kerak bo'lgan shart |
| Tavsif | `accreditation_indicators.description` | qo'shimcha izoh |
| Mezon (tegishli) | `accreditation_criteria.name` | ota-mezon nomi |
| Maqsadli qiymat | `accreditation_indicators.target_value` | rejalashtirilgan/talab qilinadigan qiymat |
| Haqiqiy qiymat | `accreditation_indicators.actual_value` | erishilgan qiymat |
| RAG holati | `accreditation_indicators.rag_status` | green/yellow/red/grey |
| Bal (score) | `accreditation_indicators.score` | hisoblangan/qo'yilgan ball |
| Og'irlik (weight) | `accreditation_indicators.weight` | sozlanadigan og'irlik |
| Mas'ul rol | `accreditation_indicators.responsible_role_id` | kim javobgar |
| Dalillar | `indicator_evidence` → `documents` | biriktirilgan hujjatlar ro'yxati |
| Kamchiliklar | `deficiencies` | ushbu indikator bo'yicha ochiq kamchiliklar |
| Placeholder bayrog'i | `accreditation_indicators.is_placeholder` | namunaviymi? |

Karta ko'rinishi (namuna):

```
┌───────────────────────────────────────────────────────────────┐
│ Indikator 1.1 (namunaviy)                          🟡 QISMAN    │
│ Talab: [namunaviy talab matni]                                  │
│ Mezon: Mezon 1 (namunaviy)          Og'irlik: 1.0               │
│ Maqsad: 100%    Haqiqiy: 60%        Bal: 0.6                    │
│ Mas'ul: Ilmiy ishlar bo'yicha mas'ul rahbar                     │
│ Dalillar: 3 ta biriktirilgan  [Dalil biriktirish]              │
│ Kamchiliklar: 1 ochiq  [Ko'rish]                               │
│ ⚠ Namunaviy ma'lumot — rasmiy qiymat bilan almashtiriladi       │
└───────────────────────────────────────────────────────────────┘
```

---

## 3. To'rtta RAG baholash holati (item 10)

| Rang | Holat kodi | Ma'nosi | Odatiy shart (sozlanadigan) |
|------|-----------|---------|------------------------------|
| 🟢 Yashil | `green` | Talab bajarilgan (to'liq) | bal ≥ yuqori chegara (masalan ≥ 0.85) |
| 🟡 Sariq | `yellow` | Qisman bajarilgan | quyi chegara ≤ bal < yuqori chegara |
| 🔴 Qizil | `red` | Bajarilmagan / muammo | bal < quyi chegara |
| ⚪ Kulrang | `grey` | Ma'lumot kiritilmagan | baho/dalil kiritilmagan (bal = null) |

- Chegaralar (thresholds) — **sozlanadigan**; yuqoridagi 0.85 / quyi chegara qiymatlari **namunaviy standart** bo'lib, administrator tomonidan o'zgartiriladi.
- Kulrang holat ma'lumot yetishmasligini ajratib ko'rsatadi (qizildan farqli — bu "muammo" emas, "hali kiritilmagan").

---

## 4. Sozlanadigan tayyorlik indeksi metodologiyasi

Umumiy **tayyorlik indeksi** (dashboard'dagi "XX%") `ScoringEngine` tomonidan quyidagi tarzda hisoblanadi:

### 4.1 Hisoblash bosqichlari

1. **Indikator bali:** har indikator uchun `score` (0.0–1.0) qo'yiladi yoki haqiqiy/maqsadli qiymat nisbatidan hisoblanadi. Kulrang (ma'lumot yo'q) indikatorlar konfiguratsiyaga qarab 0 sifatida yoki hisobdan chiqarilgan holda qaraladi.
2. **Mezon bali:** mezon ichidagi indikatorlarning **og'irlikli o'rtachasi**:
   `mezon_ball = Σ(indikator_ball × indikator_weight) / Σ(indikator_weight)`
3. **Umumiy tayyorlik:** mezonlarning **og'irlikli o'rtachasi**, foizga aylantiriladi:
   `tayyorlik% = 100 × Σ(mezon_ball × mezon_weight) / Σ(mezon_weight)`

### 4.2 Sozlanadigan parametrlar (admin tomonidan)

| Parametr | Saqlanish joyi | Tavsif |
|----------|----------------|--------|
| Mezon og'irligi | `accreditation_criteria.weight` | har mezonning umumiy indeksga hissasi |
| Indikator og'irligi | `accreditation_indicators.weight` | har indikatorning mezon ichidagi hissasi |
| RAG yuqori chegara | scoring config (settings) | yashil holat chegarasi |
| RAG quyi chegara | scoring config (settings) | qizil/sariq ajratuvchi chegara |
| Kulrangni hisoblash | scoring config (settings) | 0 sifatida yoki hisobdan chiqarish |

- Sozlash sahifasi: `/accreditations/{id}/scoring` (ruxsat: `accreditation.configure`), qarang [04-pages.md](04-pages.md).
- Barcha og'irlik va chegaralar DB'da saqlanadi; ScoringEngine ularni har hisoblashda o'qiydi — kodda "qattiq kodlangan" (hardcoded) qiymat yo'q.

---

## 5. Baholash oqimi (workflow)

```
1) Administrator mezon/indikatorlarni kiritadi/sozlaydi (yoki placeholder seed).
2) Mas'ul foydalanuvchilar indikatorlarga DALIL (documents) biriktiradi.
3) Ekspert/mas'ul rahbar indikatorni baholaydi -> RAG + score qo'yiladi (audit yoziladi).
4) ScoringEngine mezon va umumiy tayyorlik indeksini qayta hisoblaydi.
5) Talab to'liq qoplanmasa -> KAMCHILIK (deficiency) yaratiladi.
6) Kamchilikka CHORA-TADBIR (action plan) biriktiriladi (mas'ul + muddat).
7) Ichki audit NAZORAT qiladi; bajarilgach kamchilik yopiladi.
8) Natijalar HISOBOT va dashboard'da aks etadi.
```

Bu oqim tizimning markaziy zanjirining (22-band) akkreditatsiya qismini amalga oshiradi.

---

## 6. Xulosa

Akkreditatsiya moduli to'liq **ma'lumotlarga asoslangan va sozlanadigan** qilib loyihalangan. Bu oflayn cheklov sababli rasmiy mezonlarni tekshirib bo'lmagani holatida ham tizimni ishlab chiqish va sinovdan o'tkazishga imkon beradi, va production'da rasmiy qiymatlar kiritilganda ularni to'liq qo'llab-quvvatlaydi. **Hech qanday huquqiy mezon, indikator yoki muddat uydirilmagan** — barcha namunaviy ma'lumotlar aniq belgilangan va almashtirilishi kerak.
