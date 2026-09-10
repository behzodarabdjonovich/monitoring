# 03. Foydalanuvchi rollari va ruxsatlar matritsasi

**ADPI Doktorantura monitoringi va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi**

Ushbu hujjat tizimning 4 ta rolini va rol-ruxsat matritsasini (permission matrix) tavsiflaydi. Ruxsatlar rolga statik biriktirilmaydi, balki `role_permission` (M:N) jadvali orqali ma'lumotlar bazasida saqlanadi va administrator tomonidan sozlanadi (RBAC).

---

## 1. Rollar (4 ta)

| № | Rol (kod) | Nomi (Uzbek) | Tavsif |
|---|-----------|--------------|--------|
| 1 | `super_admin` | Super Administrator | Tizim boshqaruvi, rahbariyat va ilmiy boshqaruv funksiyalari. |
| 2 | `doctorate_office` | Doktorantura bo‘limi | Doktorantlar, ilmiy rahbarlar, kafedralar, rejalar, natijalar va attestatsiyalarni boshqarish. |
| 3 | `expert` | Ekspert | Akkreditatsiya, sifat nazorati, ichki audit, kamchiliklar va dalillarni baholash. |
| 4 | `doctoral_student` | Doktorant/mustaqil izlanuvchi | O‘z rejasi, ilmiy natijalari va dalillarini yuritish. |

---

## 2. Ruxsat turlari (amallar)

Har modul uchun quyidagi standart amallar (permissions) belgilanadi:

| Amal (action) | Kod qo'shimchasi | Ma'nosi |
|---------------|------------------|---------|
| Ko'rish | `.view` | Modul ma'lumotlarini ko'rish |
| Yaratish | `.create` | Yangi yozuv qo'shish |
| Tahrirlash | `.edit` | Mavjud yozuvni o'zgartirish |
| Tasdiqlash | `.approve` | Yozuvni tasdiqlash/rad etish |
| Yuklash | `.upload` | Fayl/dalil yuklash |
| Sozlash | `.configure` | Modul konfiguratsiyasi (og'irliklar, chegaralar, mezonlar) |
| Audit | `.audit` | Audit jurnali va ichki auditni ko'rish |

Ruxsat kodi formati: `<module>.<action>` (masalan `accreditation.configure`, `doctoral_students.create`).

---

## 3. Ruxsatlar matritsasi (rollar x modul amallari)

Belgilar: **✓** — ruxsat bor; **—** — ruxsat yo'q.
Rol qisqartmalari: **SA** super_admin, **IR** institut rahbariyati, **IM** ilmiy ishlar mas'uli, **DB** doktorantura bo'limi, **QC** sifat nazorati, **KM** kafedra mudiri, **SV** ilmiy rahbar, **DS** doktorant, **EX** ekspert.

### 3.1 Dashboard
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| dashboard.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

### 3.2 Doktorantlar (doctoral_students)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| create | ✓ | — | — | ✓ | — | — | — | — | — |
| edit | ✓ | — | ✓ | ✓ | — | ✓ | — | — | — |
| approve | ✓ | — | ✓ | ✓ | — | — | — | — | — |

### 3.3 Ilmiy rahbarlar (supervisors)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| create | ✓ | — | ✓ | ✓ | — | — | — | — | — |
| edit | ✓ | — | ✓ | ✓ | — | ✓ | — | — | — |

### 3.4 Ixtisosliklar (specialties + doctoral_programs)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| create | ✓ | — | ✓ | ✓ | — | — | — | — | — |
| edit | ✓ | — | ✓ | ✓ | — | — | — | — | — |

### 3.5 Individual rejalar (individual_plans + plan_tasks)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| create | ✓ | — | — | ✓ | — | — | ✓ | ✓ | — |
| edit | ✓ | — | ✓ | ✓ | — | ✓ | ✓ | ✓ | — |
| approve | ✓ | — | ✓ | ✓ | — | ✓ | ✓ | — | — |

### 3.6 Ilmiy natijalar (scientific_results, publications, conferences)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| create | ✓ | — | ✓ | ✓ | — | — | ✓ | ✓ | — |
| edit | ✓ | — | ✓ | ✓ | — | — | ✓ | ✓ | — |
| upload | ✓ | — | ✓ | ✓ | — | — | ✓ | ✓ | — |
| approve | ✓ | — | ✓ | — | — | ✓ | ✓ | — | — |

### 3.7 Attestatsiya (attestations)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| create | ✓ | — | ✓ | ✓ | — | — | — | — | — |
| edit | ✓ | — | ✓ | ✓ | — | — | — | — | — |
| approve | ✓ | ✓ | ✓ | — | — | — | — | — | — |

### 3.8 Akkreditatsiya (accreditations, criteria, indicators)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ |
| create | ✓ | — | ✓ | — | — | — | — | — | — |
| edit | ✓ | — | ✓ | — | ✓ | — | — | — | — |
| approve | ✓ | ✓ | ✓ | — | — | — | — | — | ✓ |
| configure | ✓ | — | ✓ | — | — | — | — | — | — |

> **configure** ruxsati — mezon/indikator og'irliklari, chegaralari va tarkibini o'zgartirishga imkon beradi. Faqat super_admin va ilmiy ishlar mas'uliga beriladi (akkreditatsiya konfiguratsiyasi sozlanadi).

### 3.9 Dalillar bazasi (documents, indicator_evidence)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| upload | ✓ | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| edit | ✓ | — | ✓ | ✓ | ✓ | — | — | — | — |
| approve | ✓ | — | ✓ | — | ✓ | — | — | — | ✓ |

### 3.10 Kamchiliklar (deficiencies)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ |
| create | ✓ | — | ✓ | — | ✓ | — | — | — | ✓ |
| edit | ✓ | — | ✓ | — | ✓ | — | — | — | — |
| approve | ✓ | — | ✓ | — | ✓ | — | — | — | — |

### 3.11 Action Plan (action_plans)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ |
| create | ✓ | — | ✓ | — | ✓ | — | — | — | — |
| edit | ✓ | — | ✓ | — | ✓ | ✓ | — | — | — |
| approve | ✓ | ✓ | ✓ | — | ✓ | — | — | — | — |

### 3.12 Ichki audit (internal_audits)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | — | ✓ | — | — | — | ✓ |
| create | ✓ | — | — | — | ✓ | — | — | — | — |
| edit | ✓ | — | — | — | ✓ | — | — | — | — |
| audit | ✓ | ✓ | ✓ | — | ✓ | — | — | — | ✓ |

### 3.13 Hisobotlar (reports)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |

### 3.14 Bildirishnomalar (notifications)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

### 3.15 Foydalanuvchilar (users, roles)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | — | — | — | — | — | — | — | — |
| create | ✓ | — | — | — | — | — | — | — | — |
| edit | ✓ | — | — | — | — | — | — | — | — |
| configure | ✓ | — | — | — | — | — | — | — | — |

### 3.16 Sozlamalar (settings, audit_logs, scoring config)
| Amal | SA | IR | IM | DB | QC | KM | SV | DS | EX |
|------|----|----|----|----|----|----|----|----|----|
| view | ✓ | ✓ | — | — | — | — | — | — | — |
| configure | ✓ | — | — | — | — | — | — | — | — |
| audit | ✓ | ✓ | — | — | ✓ | — | — | — | — |

---

## 4. RBAC amalga oshirilishi

- Har bir marshrut (route) o'ziga kerakli ruxsat kodini (masalan `accreditation.configure`) e'lon qiladi.
- `RbacMiddleware` foydalanuvchi rolining `role_permission` orqali ushbu ruxsatga egaligini tekshiradi.
- Doktorant (`doctoral_student`) uchun qo'shimcha **ma'lumot darajasidagi cheklov (row-level scope)** qo'llaniladi: ular faqat o'zlariga tegishli yozuvlarni ko'radi/tahrirlaydi (masalan doktorant faqat o'z rejasi va natijalari).
- Barcha ruxsatlar ma'lumotlar bazasida saqlangani uchun matritsa administrator tomonidan production'da qayta sozlanishi mumkin. Yuqoridagi jadval — boshlang'ich (seed) taqsimotdir.
