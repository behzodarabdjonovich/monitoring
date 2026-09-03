<?php
/**
 * 001 — To'liq sxema: barcha 24 obyekt + 2 M:N pivot + settings + password_resets.
 *
 * Portativ ustun turlari Schema/Blueprint yordamchisi orqali beriladi,
 * shunda bir xil migratsiya sqlite (dev) / pgsql / mysql'ga mos keladi.
 *
 * Bu fayl bin/console tomonidan yuklanadi va massiv (jadval => callable)
 * qaytaradi. Kalitlar yaratilish tartibini (FK bog'liqliklarini) belgilaydi.
 */

use App\Database\Schema;

return function (): void {
    // 1) roles
    Schema::create('roles', function ($t) {
        $t->id();
        $t->string('name', 64, false);
        $t->string('title_uz', 191, false);
        $t->text('description');
        $t->timestamp('created_at');
        $t->unique(['name']);
    });

    // 2) permissions
    Schema::create('permissions', function ($t) {
        $t->id();
        $t->string('code', 128, false);
        $t->string('module', 64, false);
        $t->string('action', 32, false);
        $t->text('description');
        $t->unique(['code']);
    });

    // 3) role_permission (M:N pivot: roles <-> permissions)
    Schema::create('role_permission', function ($t) {
        $t->id();
        $t->integer('role_id', false);
        $t->integer('permission_id', false);
        $t->foreign('role_id', 'roles');
        $t->foreign('permission_id', 'permissions');
        $t->unique(['role_id', 'permission_id']);
    });

    // 4) users
    Schema::create('users', function ($t) {
        $t->id();
        $t->integer('role_id');
        $t->string('full_name', 191, false);
        $t->string('username', 64, false);
        $t->string('email', 191);
        $t->text('password_hash', false);
        $t->boolean('is_active', false, true);
        $t->boolean('is_blocked', false, false);
        $t->boolean('must_reset', false, false);
        $t->text('twofa_secret');
        $t->timestamps();
        $t->foreign('role_id', 'roles');
        $t->unique(['username']);
        $t->unique(['email']);
    });

    // 5) departments (head_supervisor_id FK'si supervisors yaratilgach ORM darajasida ishlatiladi)
    Schema::create('departments', function ($t) {
        $t->id();
        $t->string('name', 191, false);
        $t->string('code', 64);
        $t->integer('head_supervisor_id');
        $t->timestamp('created_at');
    });

    // 6) specialties — item 8 ixtisoslik profili.
    Schema::create('specialties', function ($t) {
        $t->id();
        $t->string('code', 64);                   // ixtisoslik shifri
        $t->string('name', 191, false);           // nomi
        $t->string('branch', 128);
        $t->integer('responsible_department_id'); // mas'ul kafedra
        $t->integer('program_lead_supervisor_id'); // dastur rahbari
        $t->text('scientific_potential');         // ilmiy salohiyat
        $t->text('normative_docs');               // normativ hujjatlar
        $t->text('material_base');                // moddiy-texnik baza
        $t->text('research_infrastructure');      // ilmiy infratuzilma
        $t->text('international_cooperation');     // xalqaro hamkorlik
        $t->text('scientific_results');           // ilmiy natijalar
        $t->integer('accreditation_id');          // akkreditatsiya indikatorlari linki
        $t->timestamp('created_at');
        $t->timestamp('updated_at');
        $t->foreign('responsible_department_id', 'departments');
    });

    // 7) doctoral_programs
    Schema::create('doctoral_programs', function ($t) {
        $t->id();
        $t->integer('specialty_id');
        $t->string('name', 191, false);
        $t->string('program_type', 16);           // PhD / DSc
        $t->integer('duration_years');
        $t->timestamp('created_at');
        $t->foreign('specialty_id', 'specialties');
    });

    // 8) supervisors — item 7 ilmiy rahbar profili.
    Schema::create('supervisors', function ($t) {
        $t->id();
        $t->integer('user_id');
        $t->string('full_name', 191, false);      // F.I.Sh.
        $t->string('academic_degree', 64);        // ilmiy daraja
        $t->string('academic_title', 64);         // unvon
        $t->integer('department_id');             // kafedra
        $t->integer('specialty_id');              // ixtisoslik
        $t->string('research_field', 191);        // ilmiy yo'nalish
        $t->text('meetings_note');                // uchrashuvlar (matn)
        $t->text('assignments_note');             // berilgan topshiriqlar (matn)
        $t->text('approvals_note');               // tasdiqlashlar (matn)
        $t->timestamp('created_at');
        $t->timestamp('updated_at');
        $t->foreign('user_id', 'users');
        $t->foreign('department_id', 'departments');
        $t->foreign('specialty_id', 'specialties');
    });

    // 9) doctoral_students — item 4 to'liq elektron profil.
    Schema::create('doctoral_students', function ($t) {
        $t->id();
        $t->integer('user_id');
        $t->string('full_name', 191, false);
        $t->string('student_type', 32);
        $t->integer('department_id');
        $t->integer('specialty_id');
        $t->integer('program_id');
        $t->integer('supervisor_id');
        $t->integer('enrollment_year');
        $t->integer('course_stage');
        $t->string('status', 32);
        // Item 4: qo'shimcha profil maydonlari.
        $t->string('national_id', 32);           // JSHSHIR yoki ichki identifikator
        $t->text('photo_path');                  // fotosurat (FileStorage yo'li)
        $t->text('dissertation_topic');          // dissertatsiya mavzusi
        $t->string('advisor_name', 191);         // ilmiy maslahatchi (rahbardan tashqari)
        $t->string('admission_order', 128);      // qabul buyrug'i (raqam/sana)
        $t->date('study_start_date');            // ta'lim muddati boshi
        $t->date('study_end_date');              // ta'lim muddati oxiri
        $t->integer('dissertation_percent');     // dissertatsiya bajarilish foizi
        $t->text('scientific_results_summary');  // ilmiy natijalar (matn)
        $t->string('defense_readiness', 32);     // himoyaga tayyorgarlik holati
        $t->timestamps();
        $t->foreign('user_id', 'users');
        $t->foreign('department_id', 'departments');
        $t->foreign('specialty_id', 'specialties');
        $t->foreign('program_id', 'doctoral_programs');
        $t->foreign('supervisor_id', 'supervisors');
    });

    // 10) individual_plans
    Schema::create('individual_plans', function ($t) {
        $t->id();
        $t->integer('student_id', false);
        $t->integer('supervisor_id');
        $t->string('academic_year', 32);
        $t->date('start_date');
        $t->date('end_date');
        $t->string('status', 32, false, 'draft');
        $t->integer('approved_by');
        $t->timestamps();
        $t->foreign('student_id', 'doctoral_students');
        $t->foreign('supervisor_id', 'supervisors');
        $t->foreign('approved_by', 'users');
    });

    // 11) plan_tasks — item 5 individual reja vazifasi monitoringi.
    Schema::create('plan_tasks', function ($t) {
        $t->id();
        $t->integer('plan_id', false);
        $t->string('title', 191, false);           // vazifa nomi
        $t->text('description');
        $t->string('task_type', 64);
        $t->date('due_date');                       // rejalashtirilgan muddat
        $t->date('completed_date');                 // amaldagi bajarilgan sana
        $t->integer('progress_percent');            // bajarilish foizi (0..100)
        $t->text('evidence_path');                  // tasdiqlovchi hujjat (FileStorage yo'li)
        $t->text('student_comment');                // doktorant izohi
        $t->text('supervisor_conclusion');          // ilmiy rahbar xulosasi
        $t->text('office_note');                    // doktorantura bo'limi tasdig'i izohi
        $t->string('status', 32, false, 'planned');
        $t->timestamp('completed_at');
        $t->timestamp('created_at');
        $t->timestamp('updated_at');
        $t->foreign('plan_id', 'individual_plans');
    });

    // 12) publications
    Schema::create('publications', function ($t) {
        $t->id();
        $t->integer('student_id', false);
        $t->string('title', 255, false);
        $t->string('journal', 191);
        $t->string('publication_type', 32);
        $t->date('published_at');
        $t->string('doi', 128);
        $t->timestamp('created_at');
        $t->foreign('student_id', 'doctoral_students');
    });

    // 13) conferences
    Schema::create('conferences', function ($t) {
        $t->id();
        $t->integer('student_id', false);
        $t->string('title', 255, false);
        $t->string('conference_name', 191);
        $t->string('level', 32);
        $t->string('location', 191);
        $t->date('event_date');
        $t->timestamp('created_at');
        $t->foreign('student_id', 'doctoral_students');
    });

    // 14) scientific_results — item 6: barcha ilmiy natija turlari.
    //     Har natija doktorant va/yoki ilmiy rahbarga bog'lanadi; tasdiqlovchi
    //     fayl (FileStorage yo'li) YOKI havola (URL) biriktiriladi.
    Schema::create('scientific_results', function ($t) {
        $t->id();
        $t->integer('student_id');            // doktorant (ixtiyoriy — rahbar natijasi ham bo'lishi mumkin)
        $t->integer('supervisor_id');         // ilmiy rahbar (ixtiyoriy)
        $t->integer('plan_task_id');
        $t->string('result_type', 48, false); // enum/lookup kaliti (ScientificResult::TYPES)
        $t->integer('publication_id');        // maqola specializatsiyasi (publications)
        $t->integer('conference_id');         // konferensiya specializatsiyasi (conferences)
        $t->string('title', 255);
        $t->text('description');              // qisqacha izoh
        $t->date('achieved_at');
        $t->integer('document_id');           // tasdiqlovchi hujjat (documents) — fayl varianti
        $t->text('url');                      // tasdiqlovchi havola (havola) — URL varianti
        $t->boolean('verified', false, false);
        $t->integer('created_by');
        $t->timestamp('created_at');
        $t->timestamp('updated_at');
        $t->foreign('student_id', 'doctoral_students');
        $t->foreign('supervisor_id', 'supervisors');
        $t->foreign('plan_task_id', 'plan_tasks');
        $t->foreign('publication_id', 'publications');
        $t->foreign('conference_id', 'conferences');
    });

    // 15) attestations
    Schema::create('attestations', function ($t) {
        $t->id();
        $t->integer('student_id', false);
        $t->string('period', 64);
        $t->date('attestation_date');
        $t->string('result', 32);
        $t->text('commission_notes');
        $t->integer('created_by');
        $t->timestamp('created_at');
        $t->foreign('student_id', 'doctoral_students');
        $t->foreign('created_by', 'users');
    });

    // 16) accreditations
    Schema::create('accreditations', function ($t) {
        $t->id();
        $t->string('title', 191, false);
        $t->string('cycle_year', 32);
        $t->text('scope');
        $t->string('status', 32, false, 'planning');
        $t->real('readiness_index');
        $t->boolean('is_placeholder', false, false);
        $t->timestamps();
    });

    // 17) accreditation_criteria
    Schema::create('accreditation_criteria', function ($t) {
        $t->id();
        $t->integer('accreditation_id', false);
        $t->string('code', 64);
        $t->string('name', 255, false);
        $t->real('weight', false, 1.0);
        $t->integer('display_order');
        $t->boolean('is_placeholder', false, false);
        $t->timestamp('created_at');
        $t->foreign('accreditation_id', 'accreditations');
    });

    // 18) accreditation_indicators
    Schema::create('accreditation_indicators', function ($t) {
        $t->id();
        $t->integer('criteria_id', false);
        $t->string('code', 64);
        $t->string('name', 255);
        $t->text('requirement');
        $t->text('description');
        $t->text('self_assessment');
        $t->real('weight', false, 1.0);
        $t->string('rag_status', 16, false, 'grey');
        $t->real('score');
        $t->string('target_value', 191);
        $t->string('actual_value', 191);
        $t->integer('responsible_role_id');
        $t->string('responsible_dept', 191);
        $t->string('responsible_person', 191);
        $t->boolean('is_placeholder', false, false);
        $t->timestamps();
        $t->foreign('criteria_id', 'accreditation_criteria');
        $t->foreign('responsible_role_id', 'roles');
    });

    // 20) documents (indicator_evidence'dan oldin yaratiladi, chunki FK)
    //     item 11: markazlashtirilgan dalillar bazasi — barcha hujjat
    //     toifalari (category), metama'lumot (title, category, owner, upload
    //     date, size, mime). Fayllar webroot'dan TASHQARIDA storage/ ichida.
    Schema::create('documents', function ($t) {
        $t->id();
        $t->string('title', 255, false);
        $t->string('category', 64, false, 'boshqa'); // dalil toifasi (Document::CATEGORIES)
        $t->text('file_path', false);
        $t->string('original_name', 255);            // dastlabki fayl nomi
        $t->string('mime_type', 128);
        $t->integer('file_size');
        $t->string('doc_type', 64);                  // legacy: umumiy tur belgisi
        $t->integer('uploaded_by');                  // egasi (yuklagan foydalanuvchi)
        $t->integer('student_id');
        $t->integer('scientific_result_id');
        $t->timestamp('created_at');                 // yuklangan sana
        $t->foreign('uploaded_by', 'users');
        $t->foreign('student_id', 'doctoral_students');
        $t->foreign('scientific_result_id', 'scientific_results');
    });

    // 19) indicator_evidence (M:N pivot: indicators <-> documents)
    Schema::create('indicator_evidence', function ($t) {
        $t->id();
        $t->integer('indicator_id', false);
        $t->integer('document_id', false);
        $t->text('note');
        $t->integer('linked_by');
        $t->timestamp('linked_at');
        $t->foreign('indicator_id', 'accreditation_indicators');
        $t->foreign('document_id', 'documents');
        $t->foreign('linked_by', 'users');
        $t->unique(['indicator_id', 'document_id']);
    });

    // 21) internal_audits
    Schema::create('internal_audits', function ($t) {
        $t->id();
        $t->integer('accreditation_id');
        $t->string('title', 191, false);
        $t->date('audit_date');
        $t->integer('auditor_id');
        $t->text('scope');
        $t->string('status', 32, false, 'planned');
        $t->text('summary');
        $t->timestamp('created_at');
        $t->foreign('accreditation_id', 'accreditations');
        $t->foreign('auditor_id', 'users');
    });

    // 22) deficiencies
    Schema::create('deficiencies', function ($t) {
        $t->id();
        $t->integer('indicator_id');
        $t->integer('internal_audit_id');
        $t->string('title', 191, false);
        $t->text('description');
        $t->string('severity', 16, false, 'medium');
        $t->string('status', 16, false, 'open');
        $t->integer('identified_by');
        $t->date('identified_at');
        $t->timestamp('created_at');
        $t->foreign('indicator_id', 'accreditation_indicators');
        $t->foreign('internal_audit_id', 'internal_audits');
        $t->foreign('identified_by', 'users');
    });

    // 23) action_plans
    Schema::create('action_plans', function ($t) {
        $t->id();
        $t->integer('deficiency_id', false);
        $t->string('title', 191, false);
        $t->text('description');
        $t->integer('responsible_user_id');
        $t->date('due_date');
        $t->string('status', 32, false, 'planned');
        $t->timestamp('completed_at');
        $t->timestamp('created_at');
        $t->foreign('deficiency_id', 'deficiencies');
        $t->foreign('responsible_user_id', 'users');
    });

    // 24) notifications
    Schema::create('notifications', function ($t) {
        $t->id();
        $t->integer('user_id', false);
        $t->string('type', 64);
        $t->string('title', 191, false);
        $t->text('body');
        $t->string('link', 255);
        $t->boolean('is_read', false, false);
        $t->timestamp('created_at');
        $t->foreign('user_id', 'users');
    });

    // 25) audit_logs (o'zgarmas)
    Schema::create('audit_logs', function ($t) {
        $t->id();
        $t->integer('user_id');
        $t->string('action', 32, false);
        $t->string('entity_type', 64);
        $t->integer('entity_id');
        $t->text('old_values');
        $t->text('new_values');
        $t->string('ip_address', 64);
        $t->timestamp('created_at');
        $t->foreign('user_id', 'users');
    });

    // Qo'shimcha: settings (admin sozlanadigan og'irliklar/chegaralar)
    Schema::create('settings', function ($t) {
        $t->id();
        $t->string('key', 128, false);
        $t->text('value');
        $t->string('type', 32, false, 'string');
        $t->text('description');
        $t->timestamp('updated_at');
        $t->unique(['key']);
    });

    // Qo'shimcha: password_resets (parolni tiklash tokenlari)
    Schema::create('password_resets', function ($t) {
        $t->id();
        $t->integer('user_id', false);
        $t->text('token', false);
        $t->timestamp('expires_at');
        $t->boolean('used', false, false);
        $t->timestamp('created_at');
        $t->foreign('user_id', 'users');
    });
};
