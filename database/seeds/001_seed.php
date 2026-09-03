<?php
/**
 * Boshlang'ich (seed) ma'lumotlar.
 *
 * - 9 ta rol (docs/03) + ruxsatlar + role_permission matritsasi.
 * - Har rol uchun bitta DEMO foydalanuvchi (parol README'da hujjatlangan).
 * - Bir nechta kafedra / ixtisoslik / doktorantura dasturi (demo).
 * - CLEARLY-LABELLED PLACEHOLDER akkreditatsiya + mezon + indikatorlar
 *   (is_placeholder = 1) — bular RASMIY tasdiqlangan qiymatlar bilan
 *   ALMASHTIRILISHI SHART (uydirma emas, namunaviy tuzilma).
 *
 * DIQQAT: bu yerda REAL SHAXSIY MA'LUMOTLAR yo'q — barcha ismlar aniq
 * ko'rinib turuvchi demo/namuna ismlardir.
 */

use App\Core\Auth;
use App\Core\DB;

return function (): void {
    $now = date('Y-m-d H:i:s');

    // ---------------------------------------------------------------
    // 1) Rollar (9 ta) — docs/03 bo'yicha.
    // ---------------------------------------------------------------
    $roles = [
        'super_admin' => ['Super Administrator', 'Tizimning to\'liq egasi.'],
        'institute_leadership' => ['Institut rahbariyati', 'Yuqori darajadagi ko\'rinish va strategik tasdiqlash.'],
        'research_vice_head' => ['Ilmiy ishlar bo\'yicha mas\'ul rahbar', 'Ilmiy natijalar, rejalar, attestatsiya va akkreditatsiyani boshqarish.'],
        'doctorate_office' => ['Doktorantura bo\'limi', 'Doktorantlar, rejalar, ixtisosliklar, attestatsiya ma\'lumotlarini yuritish.'],
        'quality_control' => ['Ta\'lim sifatini nazorat qilish bo\'limi', 'Ichki audit, kamchiliklar, chora-tadbirlar.'],
        'department_head' => ['Kafedra mudiri', 'O\'z kafedrasi doktorantlari va natijalari.'],
        'supervisor' => ['Ilmiy rahbar/maslahatchi', 'O\'z doktorantlarining rejalari va natijalari.'],
        'doctoral_student' => ['Doktorant/mustaqil izlanuvchi', 'O\'z rejasi, natijalari, dalillari.'],
        'expert' => ['Ekspert', 'Akkreditatsiya indikatorlarini baholash, dalillarni ko\'rib chiqish.'],
    ];

    $roleIds = [];
    foreach ($roles as $name => [$title, $desc]) {
        $roleIds[$name] = DB::insert('roles', [
            'name' => $name,
            'title_uz' => $title,
            'description' => $desc,
            'created_at' => $now,
        ]);
    }

    // ---------------------------------------------------------------
    // 2) Ruxsatlar (module.action) — docs/03 matritsasidagi barcha amallar.
    // ---------------------------------------------------------------
    $modules = [
        'dashboard' => ['view'],
        'doctoral_students' => ['view', 'create', 'edit', 'approve'],
        'supervisors' => ['view', 'create', 'edit'],
        'specialties' => ['view', 'create', 'edit'],
        'individual_plans' => ['view', 'create', 'edit', 'approve'],
        'scientific_results' => ['view', 'create', 'edit', 'upload', 'approve'],
        'attestations' => ['view', 'create', 'edit', 'approve'],
        'accreditation' => ['view', 'create', 'edit', 'approve', 'configure'],
        'documents' => ['view', 'upload', 'edit', 'approve'],
        'deficiencies' => ['view', 'create', 'edit', 'approve'],
        'action_plans' => ['view', 'create', 'edit', 'approve'],
        'internal_audits' => ['view', 'create', 'edit', 'audit'],
        'reports' => ['view'],
        'notifications' => ['view'],
        'users' => ['view', 'create', 'edit', 'configure'],
        'settings' => ['view', 'configure', 'audit'],
    ];

    $permIds = [];
    foreach ($modules as $module => $actions) {
        foreach ($actions as $action) {
            $code = "$module.$action";
            $permIds[$code] = DB::insert('permissions', [
                'code' => $code,
                'module' => $module,
                'action' => $action,
                'description' => "$module modulida $action amali.",
            ]);
        }
    }

    // ---------------------------------------------------------------
    // 3) role_permission matritsasi — docs/03 3.1-3.16 jadvallariga muvofiq.
    //    Har rol uchun ruxsat kodlari ro'yxati.
    // ---------------------------------------------------------------
    $matrix = [
        'super_admin' => array_keys($permIds), // hammasi

        'institute_leadership' => [
            'dashboard.view',
            'doctoral_students.view',
            'supervisors.view',
            'specialties.view',
            'individual_plans.view',
            'scientific_results.view',
            'attestations.view', 'attestations.approve',
            'accreditation.view', 'accreditation.approve',
            'documents.view',
            'deficiencies.view',
            'action_plans.view', 'action_plans.approve',
            'internal_audits.view', 'internal_audits.audit',
            'reports.view',
            'notifications.view',
            'settings.view', 'settings.audit',
        ],

        'research_vice_head' => [
            'dashboard.view',
            'doctoral_students.view', 'doctoral_students.edit', 'doctoral_students.approve',
            'supervisors.view', 'supervisors.create', 'supervisors.edit',
            'specialties.view', 'specialties.create', 'specialties.edit',
            'individual_plans.view', 'individual_plans.edit', 'individual_plans.approve',
            'scientific_results.view', 'scientific_results.create', 'scientific_results.edit', 'scientific_results.upload', 'scientific_results.approve',
            'attestations.view', 'attestations.create', 'attestations.edit', 'attestations.approve',
            'accreditation.view', 'accreditation.create', 'accreditation.edit', 'accreditation.approve', 'accreditation.configure',
            'documents.view', 'documents.upload', 'documents.edit', 'documents.approve',
            'deficiencies.view', 'deficiencies.create', 'deficiencies.edit', 'deficiencies.approve',
            'action_plans.view', 'action_plans.create', 'action_plans.edit', 'action_plans.approve',
            'internal_audits.view', 'internal_audits.audit',
            'reports.view',
            'notifications.view',
        ],

        'doctorate_office' => [
            'dashboard.view',
            'doctoral_students.view', 'doctoral_students.create', 'doctoral_students.edit', 'doctoral_students.approve',
            'supervisors.view', 'supervisors.create', 'supervisors.edit',
            'specialties.view', 'specialties.create', 'specialties.edit',
            'individual_plans.view', 'individual_plans.create', 'individual_plans.edit', 'individual_plans.approve',
            'scientific_results.view', 'scientific_results.create', 'scientific_results.edit', 'scientific_results.upload',
            'attestations.view', 'attestations.create', 'attestations.edit',
            'accreditation.view',
            'documents.view', 'documents.upload', 'documents.edit',
            'reports.view',
            'notifications.view',
        ],

        'quality_control' => [
            'dashboard.view',
            'doctoral_students.view',
            'supervisors.view',
            'specialties.view',
            'individual_plans.view',
            'scientific_results.view',
            'attestations.view',
            'accreditation.view', 'accreditation.edit',
            'documents.view', 'documents.upload', 'documents.edit', 'documents.approve',
            'deficiencies.view', 'deficiencies.create', 'deficiencies.edit', 'deficiencies.approve',
            'action_plans.view', 'action_plans.edit', 'action_plans.approve',
            'internal_audits.view', 'internal_audits.create', 'internal_audits.edit', 'internal_audits.audit',
            'reports.view',
            'notifications.view',
            'settings.audit',
        ],

        'department_head' => [
            'dashboard.view',
            'doctoral_students.view', 'doctoral_students.edit',
            'supervisors.view', 'supervisors.edit',
            'specialties.view',
            'individual_plans.view', 'individual_plans.edit', 'individual_plans.approve',
            'scientific_results.view', 'scientific_results.approve',
            'attestations.view',
            'accreditation.view',
            'documents.view', 'documents.upload',
            'action_plans.view', 'action_plans.edit',
            'reports.view',
            'notifications.view',
        ],

        'supervisor' => [
            'dashboard.view',
            'doctoral_students.view',
            'supervisors.view',
            'specialties.view',
            'individual_plans.view', 'individual_plans.create', 'individual_plans.edit', 'individual_plans.approve',
            'scientific_results.view', 'scientific_results.create', 'scientific_results.edit', 'scientific_results.upload', 'scientific_results.approve',
            'attestations.view',
            'documents.view', 'documents.upload',
            'reports.view',
            'notifications.view',
        ],

        'doctoral_student' => [
            'dashboard.view',
            'doctoral_students.view',
            'specialties.view',
            'individual_plans.view', 'individual_plans.create', 'individual_plans.edit',
            'scientific_results.view', 'scientific_results.create', 'scientific_results.edit', 'scientific_results.upload',
            'attestations.view',
            'documents.view', 'documents.upload',
            'notifications.view',
        ],

        'expert' => [
            'dashboard.view',
            'doctoral_students.view',
            'supervisors.view',
            'specialties.view',
            'individual_plans.view',
            'scientific_results.view',
            'accreditation.view', 'accreditation.approve',
            'documents.view', 'documents.approve',
            'deficiencies.view', 'deficiencies.create',
            'action_plans.view',
            'internal_audits.view', 'internal_audits.audit',
            'reports.view',
            'notifications.view',
        ],
    ];

    foreach ($matrix as $role => $codes) {
        foreach ($codes as $code) {
            if (!isset($permIds[$code])) {
                continue;
            }
            DB::insert('role_permission', [
                'role_id' => $roleIds[$role],
                'permission_id' => $permIds[$code],
            ]);
        }
    }

    // ---------------------------------------------------------------
    // 4) DEMO foydalanuvchilar — har rol uchun bittadan.
    //    Parol: Parol123!  (README'da hujjatlangan). REAL shaxs emas.
    // ---------------------------------------------------------------
    $demoPassword = Auth::hash('Parol123!');
    $demoUsers = [
        'super_admin' => ['Demo Super Admin', 'admin'],
        'institute_leadership' => ['Demo Rahbariyat', 'rahbariyat'],
        'research_vice_head' => ['Demo Ilmiy Prorektor', 'ilmiy'],
        'doctorate_office' => ['Demo Doktorantura Bolimi', 'doktorantura'],
        'quality_control' => ['Demo Sifat Nazorati', 'sifat'],
        'department_head' => ['Demo Kafedra Mudiri', 'kafedra'],
        'supervisor' => ['Demo Ilmiy Rahbar', 'rahbar'],
        'doctoral_student' => ['Demo Doktorant', 'doktorant'],
        'expert' => ['Demo Ekspert', 'ekspert'],
    ];

    $userIds = [];
    foreach ($demoUsers as $role => [$fullName, $username]) {
        $userIds[$role] = DB::insert('users', [
            'role_id' => $roleIds[$role],
            'full_name' => $fullName,
            'username' => $username,
            'email' => $username . '@demo.adpi.local',
            'password_hash' => $demoPassword,
            'is_active' => 1,
            'is_blocked' => 0,
            'must_reset' => 0,
            'twofa_secret' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // ---------------------------------------------------------------
    // 5) Ma'lumotnomalar (demo): kafedralar, ixtisosliklar, dasturlar.
    // ---------------------------------------------------------------
    $dep1 = DB::insert('departments', ['name' => 'Demo Pedagogika kafedrasi', 'code' => 'DEMO-PED', 'head_supervisor_id' => null, 'created_at' => $now]);
    $dep2 = DB::insert('departments', ['name' => 'Demo Aniq fanlar kafedrasi', 'code' => 'DEMO-ANIQ', 'head_supervisor_id' => null, 'created_at' => $now]);

    $spec1 = DB::insert('specialties', ['code' => '13.00.01', 'name' => 'Demo: Pedagogika nazariyasi va tarixi', 'branch' => 'Pedagogika fanlari', 'created_at' => $now]);
    $spec2 = DB::insert('specialties', ['code' => '01.01.02', 'name' => 'Demo: Differensial tenglamalar', 'branch' => 'Fizika-matematika fanlari', 'created_at' => $now]);

    DB::insert('doctoral_programs', ['specialty_id' => $spec1, 'name' => 'Demo PhD dasturi (Pedagogika)', 'program_type' => 'PhD', 'duration_years' => 3, 'created_at' => $now]);
    DB::insert('doctoral_programs', ['specialty_id' => $spec2, 'name' => 'Demo DSc dasturi (Matematika)', 'program_type' => 'DSc', 'duration_years' => 3, 'created_at' => $now]);

    // Demo ilmiy rahbar + demo doktorant (obvious demo names).
    $sup1 = DB::insert('supervisors', [
        'user_id' => $userIds['supervisor'],
        'full_name' => 'Demo Ilmiy Rahbar',
        'academic_degree' => 'PhD',
        'academic_title' => 'dotsent',
        'department_id' => $dep1,
        'created_at' => $now,
    ]);
    DB::insert('doctoral_students', [
        'user_id' => $userIds['doctoral_student'],
        'full_name' => 'Demo Doktorant',
        'student_type' => 'tayanch_doktorant',
        'department_id' => $dep1,
        'specialty_id' => $spec1,
        'program_id' => 1,
        'supervisor_id' => $sup1,
        'enrollment_year' => 2024,
        'course_stage' => 1,
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // ---------------------------------------------------------------
    // 6) PLACEHOLDER akkreditatsiya + mezon + indikatorlar (is_placeholder = 1).
    //    OGOHLANTIRISH: bular NAMUNA tuzilma. Ishga tushirishdan oldin
    //    RASMIY tasdiqlangan mezon/indikator/og'irliklar bilan almashtiring.
    // ---------------------------------------------------------------
    $placeholderNote = 'NAMUNA (placeholder) — rasmiy tasdiqlangan qiymatlar bilan almashtirilishi SHART.';

    $accId = DB::insert('accreditations', [
        'title' => '[NAMUNA] Maxsus davlat akkreditatsiyasiga tayyorgarlik sikli',
        'cycle_year' => date('Y'),
        'scope' => $placeholderNote,
        'status' => 'planning',
        'readiness_index' => null,
        'is_placeholder' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Namuna mezonlar (tarkib namunaviy, rasmiy emas).
    $criteria = [
        ['NAMUNA-1', '[NAMUNA] Ta\'lim dasturi va o\'quv jarayoni', 1.0, 1],
        ['NAMUNA-2', '[NAMUNA] Ilmiy-tadqiqot faoliyati va natijadorlik', 1.0, 2],
        ['NAMUNA-3', '[NAMUNA] Professor-o\'qituvchilar salohiyati', 1.0, 3],
    ];

    $indicatorNum = 1;
    foreach ($criteria as [$code, $name, $weight, $order]) {
        $critId = DB::insert('accreditation_criteria', [
            'accreditation_id' => $accId,
            'code' => $code,
            'name' => $name,
            'weight' => $weight,
            'display_order' => $order,
            'is_placeholder' => 1,
            'created_at' => $now,
        ]);

        // Har mezon uchun 2 ta namuna indikator.
        for ($i = 1; $i <= 2; $i++) {
            DB::insert('accreditation_indicators', [
                'criteria_id' => $critId,
                'code' => $code . '.' . $i,
                'name' => "[NAMUNA] Indikator $indicatorNum",
                'requirement' => $placeholderNote,
                'description' => $placeholderNote,
                'self_assessment' => null,
                'weight' => 1.0,
                'rag_status' => 'grey',
                'score' => null,
                'target_value' => null,
                'actual_value' => null,
                'responsible_role_id' => $roleIds['research_vice_head'],
                'responsible_dept' => null,
                'responsible_person' => null,
                'is_placeholder' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $indicatorNum++;
        }
    }

    // ---------------------------------------------------------------
    // 7) Sozlamalar (settings): baholash og'irliklari va chegaralari.
    // ---------------------------------------------------------------
    $settings = [
        ['scoring.threshold_green', '80', 'number', 'RAG yashil chegarasi (foiz).'],
        ['scoring.threshold_yellow', '50', 'number', 'RAG sariq chegarasi (foiz).'],
        ['scoring.default_indicator_weight', '1.0', 'number', 'Indikatorning standart og\'irligi.'],
        ['app.placeholder_notice', '1', 'boolean', 'Namuna (placeholder) ma\'lumot ogohlantirishini ko\'rsatish.'],
    ];
    foreach ($settings as [$key, $value, $type, $desc]) {
        DB::insert('settings', [
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'description' => $desc,
            'updated_at' => $now,
        ]);
    }

    // Demo bildirishnoma (super_admin uchun).
    DB::insert('notifications', [
        'user_id' => $userIds['super_admin'],
        'type' => 'info',
        'title' => 'Tizim ishga tushirildi',
        'body' => 'Akkreditatsiya mezonlari NAMUNA (placeholder) sifatida kiritilgan — rasmiy qiymatlar bilan almashtiring.',
        'link' => '/accreditations',
        'is_read' => 0,
        'created_at' => $now,
    ]);
};
