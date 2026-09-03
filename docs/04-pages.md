# 04. Sahifalar va marshrutlar ro'yxati

**ADPI Doktorantura monitoringi va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi**

Ushbu hujjat tizimning barcha sahifalarini (routes) 16 ta yon panel (sidebar) bo'limi bo'yicha, shuningdek autentifikatsiya sahifalarini ro'yxatlaydi. Har sahifa uchun URL, HTTP metod, kontroller va talab qilinadigan ruxsat ko'rsatilgan.

> Yon panel bo'limlari topshiriqning 18-bandidagi **aniq tartibda** keltirilgan.

---

## 0. Autentifikatsiya sahifalari (sidebar'siz)

| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Login formasi | `/login` | GET | `AuthController@showLogin` | umumiy (mehmon) |
| Login yuborish | `/login` | POST | `AuthController@login` | umumiy (mehmon) |
| Chiqish | `/logout` | POST | `AuthController@logout` | autentifikatsiya |
| Profil | `/profile` | GET | `ProfileController@show` | autentifikatsiya |
| Parolni o'zgartirish | `/profile/password` | POST | `ProfileController@changePassword` | autentifikatsiya |
| 403 (ruxsat yo'q) | — | — | `ErrorController@forbidden` | — |
| 404 (topilmadi) | — | — | `ErrorController@notFound` | — |

---

## Yon panel (sidebar) bo'limlari — 16 ta

### 1. Dashboard
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Bosh sahifa (dashboard) | `/` | GET | `DashboardController@index` | `dashboard.view` |
| Dashboard filtrlangan ma'lumot (AJAX) | `/dashboard/data` | GET | `DashboardController@data` | `dashboard.view` |

### 2. Doktorantlar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Ro'yxat | `/students` | GET | `StudentController@index` | `doctoral_students.view` |
| Ko'rish | `/students/{id}` | GET | `StudentController@show` | `doctoral_students.view` |
| Yangi forma | `/students/create` | GET | `StudentController@create` | `doctoral_students.create` |
| Saqlash | `/students` | POST | `StudentController@store` | `doctoral_students.create` |
| Tahrir forma | `/students/{id}/edit` | GET | `StudentController@edit` | `doctoral_students.edit` |
| Yangilash | `/students/{id}` | POST | `StudentController@update` | `doctoral_students.edit` |

### 3. Ilmiy rahbarlar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Ro'yxat | `/supervisors` | GET | `SupervisorController@index` | `supervisors.view` |
| Ko'rish | `/supervisors/{id}` | GET | `SupervisorController@show` | `supervisors.view` |
| Yangi forma | `/supervisors/create` | GET | `SupervisorController@create` | `supervisors.create` |
| Saqlash | `/supervisors` | POST | `SupervisorController@store` | `supervisors.create` |
| Tahrirlash | `/supervisors/{id}/edit` | GET | `SupervisorController@edit` | `supervisors.edit` |
| Yangilash | `/supervisors/{id}` | POST | `SupervisorController@update` | `supervisors.edit` |

### 4. Ixtisosliklar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Ixtisosliklar ro'yxati | `/specialties` | GET | `SpecialtyController@index` | `specialties.view` |
| Ixtisoslik yaratish | `/specialties` | POST | `SpecialtyController@store` | `specialties.create` |
| Ixtisoslik tahrirlash | `/specialties/{id}` | POST | `SpecialtyController@update` | `specialties.edit` |
| Doktorantura dasturlari | `/programs` | GET | `ProgramController@index` | `specialties.view` |
| Dastur yaratish | `/programs` | POST | `ProgramController@store` | `specialties.create` |

### 5. Individual rejalar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Rejalar ro'yxati | `/plans` | GET | `PlanController@index` | `individual_plans.view` |
| Reja ko'rish | `/plans/{id}` | GET | `PlanController@show` | `individual_plans.view` |
| Reja yaratish forma | `/plans/create` | GET | `PlanController@create` | `individual_plans.create` |
| Reja saqlash | `/plans` | POST | `PlanController@store` | `individual_plans.create` |
| Reja tahrirlash | `/plans/{id}/edit` | GET | `PlanController@edit` | `individual_plans.edit` |
| Reja yangilash | `/plans/{id}` | POST | `PlanController@update` | `individual_plans.edit` |
| Reja tasdiqlash | `/plans/{id}/approve` | POST | `PlanController@approve` | `individual_plans.approve` |
| Vazifa qo'shish | `/plans/{id}/tasks` | POST | `PlanTaskController@store` | `individual_plans.edit` |
| Vazifa yangilash | `/tasks/{id}` | POST | `PlanTaskController@update` | `individual_plans.edit` |

### 6. Ilmiy natijalar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Natijalar ro'yxati | `/results` | GET | `ResultController@index` | `scientific_results.view` |
| Natija ko'rish | `/results/{id}` | GET | `ResultController@show` | `scientific_results.view` |
| Natija qo'shish | `/results` | POST | `ResultController@store` | `scientific_results.create` |
| Natija tahrirlash | `/results/{id}` | POST | `ResultController@update` | `scientific_results.edit` |
| Natija tasdiqlash | `/results/{id}/approve` | POST | `ResultController@approve` | `scientific_results.approve` |
| Dalil yuklash | `/results/{id}/upload` | POST | `ResultController@upload` | `scientific_results.upload` |
| Maqolalar | `/publications` | GET | `PublicationController@index` | `scientific_results.view` |
| Maqola qo'shish | `/publications` | POST | `PublicationController@store` | `scientific_results.create` |
| Konferensiyalar | `/conferences` | GET | `ConferenceController@index` | `scientific_results.view` |
| Konferensiya qo'shish | `/conferences` | POST | `ConferenceController@store` | `scientific_results.create` |

### 7. Attestatsiya
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Attestatsiyalar ro'yxati | `/attestations` | GET | `AttestationController@index` | `attestations.view` |
| Attestatsiya ko'rish | `/attestations/{id}` | GET | `AttestationController@show` | `attestations.view` |
| Attestatsiya qo'shish | `/attestations` | POST | `AttestationController@store` | `attestations.create` |
| Attestatsiya tahrirlash | `/attestations/{id}` | POST | `AttestationController@update` | `attestations.edit` |
| Attestatsiya tasdiqlash | `/attestations/{id}/approve` | POST | `AttestationController@approve` | `attestations.approve` |

### 8. Akkreditatsiya
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Akkreditatsiyalar ro'yxati | `/accreditations` | GET | `AccreditationController@index` | `accreditation.view` |
| Akkreditatsiya ko'rish (iyerarxiya) | `/accreditations/{id}` | GET | `AccreditationController@show` | `accreditation.view` |
| Akkreditatsiya yaratish | `/accreditations` | POST | `AccreditationController@store` | `accreditation.create` |
| Mezonlar boshqaruvi | `/accreditations/{id}/criteria` | GET | `CriteriaController@index` | `accreditation.view` |
| Mezon yaratish | `/criteria` | POST | `CriteriaController@store` | `accreditation.configure` |
| Mezon tahrirlash (og'irlik) | `/criteria/{id}` | POST | `CriteriaController@update` | `accreditation.configure` |
| Indikator ko'rish (karta) | `/indicators/{id}` | GET | `IndicatorController@show` | `accreditation.view` |
| Indikator yaratish | `/indicators` | POST | `IndicatorController@store` | `accreditation.configure` |
| Indikator baholash (RAG) | `/indicators/{id}/assess` | POST | `IndicatorController@assess` | `accreditation.approve` |
| Baholash sozlash (weights/thresholds) | `/accreditations/{id}/scoring` | GET/POST | `ScoringConfigController@edit` | `accreditation.configure` |

### 9. Dalillar bazasi
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Hujjatlar ro'yxati | `/documents` | GET | `DocumentController@index` | `documents.view` |
| Hujjat yuklash forma | `/documents/upload` | GET | `DocumentController@uploadForm` | `documents.upload` |
| Hujjat yuklash | `/documents` | POST | `DocumentController@store` | `documents.upload` |
| Hujjat yuklab olish (himoyalangan) | `/documents/{id}/download` | GET | `DocumentController@download` | `documents.view` |
| Indikatorga dalil biriktirish | `/indicators/{id}/evidence` | POST | `EvidenceController@link` | `documents.upload` |
| Dalilni tasdiqlash | `/evidence/{id}/approve` | POST | `EvidenceController@approve` | `documents.approve` |

### 10. Kamchiliklar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Kamchiliklar ro'yxati | `/deficiencies` | GET | `DeficiencyController@index` | `deficiencies.view` |
| Kamchilik ko'rish | `/deficiencies/{id}` | GET | `DeficiencyController@show` | `deficiencies.view` |
| Kamchilik qo'shish | `/deficiencies` | POST | `DeficiencyController@store` | `deficiencies.create` |
| Kamchilik tahrirlash | `/deficiencies/{id}` | POST | `DeficiencyController@update` | `deficiencies.edit` |
| Kamchilikni yopish | `/deficiencies/{id}/resolve` | POST | `DeficiencyController@resolve` | `deficiencies.approve` |

### 11. Action Plan (chora-tadbirlar)
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Chora-tadbirlar ro'yxati | `/action-plans` | GET | `ActionPlanController@index` | `action_plans.view` |
| Chora-tadbir ko'rish | `/action-plans/{id}` | GET | `ActionPlanController@show` | `action_plans.view` |
| Chora-tadbir yaratish | `/action-plans` | POST | `ActionPlanController@store` | `action_plans.create` |
| Chora-tadbir yangilash | `/action-plans/{id}` | POST | `ActionPlanController@update` | `action_plans.edit` |
| Chora-tadbir tasdiqlash/yopish | `/action-plans/{id}/approve` | POST | `ActionPlanController@approve` | `action_plans.approve` |

### 12. Ichki audit
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Auditlar ro'yxati | `/audits` | GET | `InternalAuditController@index` | `internal_audits.view` |
| Audit ko'rish | `/audits/{id}` | GET | `InternalAuditController@show` | `internal_audits.view` |
| Audit yaratish | `/audits` | POST | `InternalAuditController@store` | `internal_audits.create` |
| Audit yangilash | `/audits/{id}` | POST | `InternalAuditController@update` | `internal_audits.edit` |

### 13. Hisobotlar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Hisobotlar paneli | `/reports` | GET | `ReportController@index` | `reports.view` |
| Tayyorlik hisoboti | `/reports/readiness` | GET | `ReportController@readiness` | `reports.view` |
| Doktorantlar hisoboti | `/reports/students` | GET | `ReportController@students` | `reports.view` |
| Kamchiliklar/chora-tadbir hisoboti | `/reports/deficiencies` | GET | `ReportController@deficiencies` | `reports.view` |
| Eksport (PDF/CSV) | `/reports/{type}/export` | GET | `ReportController@export` | `reports.view` |

### 14. Bildirishnomalar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Bildirishnomalar ro'yxati | `/notifications` | GET | `NotificationController@index` | `notifications.view` |
| O'qildi deb belgilash | `/notifications/{id}/read` | POST | `NotificationController@markRead` | `notifications.view` |
| Barchasi o'qildi | `/notifications/read-all` | POST | `NotificationController@markAllRead` | `notifications.view` |

### 15. Foydalanuvchilar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Foydalanuvchilar ro'yxati | `/users` | GET | `UserController@index` | `users.view` |
| Foydalanuvchi yaratish | `/users` | POST | `UserController@store` | `users.create` |
| Foydalanuvchi tahrirlash | `/users/{id}` | POST | `UserController@update` | `users.edit` |
| Rollar boshqaruvi | `/roles` | GET | `RoleController@index` | `users.configure` |
| Rol ruxsatlarini sozlash | `/roles/{id}/permissions` | POST | `RoleController@updatePermissions` | `users.configure` |

### 16. Sozlamalar
| Sahifa | URL | Metod | Kontroller@amal | Ruxsat |
|--------|-----|-------|-----------------|--------|
| Umumiy sozlamalar | `/settings` | GET | `SettingsController@index` | `settings.view` |
| Sozlamalarni saqlash | `/settings` | POST | `SettingsController@update` | `settings.configure` |
| Audit jurnali | `/settings/audit-logs` | GET | `AuditLogController@index` | `settings.audit` |
| O'quv yillari / ma'lumotnomalar | `/settings/reference` | GET | `SettingsController@reference` | `settings.configure` |

---

## Xulosa

Barcha 16 ta yon panel bo'limi hamda autentifikatsiya sahifalari qamrab olindi. Har marshrut RBAC ruxsati bilan himoyalangan; o'zgartiruvchi (POST) marshrutlar qo'shimcha CSRF tekshiruvidan o'tadi (qarang [01-architecture.md](01-architecture.md)).
