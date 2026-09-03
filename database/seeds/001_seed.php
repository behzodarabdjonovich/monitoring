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
    // Kafedralar (demo) — 4 ta, turli fan yo'nalishlari bo'yicha.
    $dep1 = DB::insert('departments', ['name' => 'Demo Pedagogika kafedrasi', 'code' => 'DEMO-PED', 'head_supervisor_id' => null, 'created_at' => $now]);
    $dep2 = DB::insert('departments', ['name' => 'Demo Aniq fanlar kafedrasi', 'code' => 'DEMO-ANIQ', 'head_supervisor_id' => null, 'created_at' => $now]);
    $dep3 = DB::insert('departments', ['name' => 'Demo Filologiya kafedrasi', 'code' => 'DEMO-FIL', 'head_supervisor_id' => null, 'created_at' => $now]);
    $dep4 = DB::insert('departments', ['name' => 'Demo Tarix kafedrasi', 'code' => 'DEMO-TAR', 'head_supervisor_id' => null, 'created_at' => $now]);
    $deps = [$dep1, $dep2, $dep3, $dep4];

    // Ixtisosliklar (demo) — 4 ta.
    $spec1 = DB::insert('specialties', ['code' => '13.00.01', 'name' => 'Demo: Pedagogika nazariyasi va tarixi', 'branch' => 'Pedagogika fanlari', 'created_at' => $now]);
    $spec2 = DB::insert('specialties', ['code' => '01.01.02', 'name' => 'Demo: Differensial tenglamalar', 'branch' => 'Fizika-matematika fanlari', 'created_at' => $now]);
    $spec3 = DB::insert('specialties', ['code' => '10.00.02', 'name' => 'Demo: O\'zbek tili va adabiyoti', 'branch' => 'Filologiya fanlari', 'created_at' => $now]);
    $spec4 = DB::insert('specialties', ['code' => '07.00.01', 'name' => 'Demo: O\'zbekiston tarixi', 'branch' => 'Tarix fanlari', 'created_at' => $now]);

    // Dasturlar (demo) — har ixtisoslik uchun PhD va DSc.
    $prog = [];
    $prog[$spec1] = ['PhD' => DB::insert('doctoral_programs', ['specialty_id' => $spec1, 'name' => 'Demo PhD dasturi (Pedagogika)', 'program_type' => 'PhD', 'duration_years' => 3, 'created_at' => $now])];
    $prog[$spec1]['DSc'] = DB::insert('doctoral_programs', ['specialty_id' => $spec1, 'name' => 'Demo DSc dasturi (Pedagogika)', 'program_type' => 'DSc', 'duration_years' => 3, 'created_at' => $now]);
    $prog[$spec2] = ['PhD' => DB::insert('doctoral_programs', ['specialty_id' => $spec2, 'name' => 'Demo PhD dasturi (Matematika)', 'program_type' => 'PhD', 'duration_years' => 3, 'created_at' => $now])];
    $prog[$spec2]['DSc'] = DB::insert('doctoral_programs', ['specialty_id' => $spec2, 'name' => 'Demo DSc dasturi (Matematika)', 'program_type' => 'DSc', 'duration_years' => 3, 'created_at' => $now]);
    $prog[$spec3] = ['PhD' => DB::insert('doctoral_programs', ['specialty_id' => $spec3, 'name' => 'Demo PhD dasturi (Filologiya)', 'program_type' => 'PhD', 'duration_years' => 3, 'created_at' => $now])];
    $prog[$spec3]['DSc'] = DB::insert('doctoral_programs', ['specialty_id' => $spec3, 'name' => 'Demo DSc dasturi (Filologiya)', 'program_type' => 'DSc', 'duration_years' => 3, 'created_at' => $now]);
    $prog[$spec4] = ['PhD' => DB::insert('doctoral_programs', ['specialty_id' => $spec4, 'name' => 'Demo PhD dasturi (Tarix)', 'program_type' => 'PhD', 'duration_years' => 3, 'created_at' => $now])];
    $prog[$spec4]['DSc'] = DB::insert('doctoral_programs', ['specialty_id' => $spec4, 'name' => 'Demo DSc dasturi (Tarix)', 'program_type' => 'DSc', 'duration_years' => 3, 'created_at' => $now]);

    $specDeps = [$spec1 => $dep1, $spec2 => $dep2, $spec3 => $dep3, $spec4 => $dep4];

    // ---------------------------------------------------------------
    // 5a) Ilmiy rahbarlar (demo) — 6 ta. Birinchisi seed user'ga bog'langan.
    // ---------------------------------------------------------------
    $supNames = [
        ['Demo Ilmiy Rahbar', 'DSc', 'professor', $dep1, $userIds['supervisor']],
        ['Demo Rahbar Ikki', 'PhD', 'dotsent', $dep2, null],
        ['Demo Rahbar Uch', 'DSc', 'professor', $dep3, null],
        ['Demo Rahbar Tort', 'PhD', 'dotsent', $dep4, null],
        ['Demo Rahbar Besh', 'PhD', 'katta o\'qituvchi', $dep1, null],
        ['Demo Rahbar Olti', 'DSc', 'professor', $dep2, null],
    ];
    $supIds = [];
    foreach ($supNames as [$fname, $degree, $title, $depId, $uid]) {
        $supIds[] = DB::insert('supervisors', [
            'user_id' => $uid,
            'full_name' => $fname,
            'academic_degree' => $degree,
            'academic_title' => $title,
            'department_id' => $depId,
            'created_at' => $now,
        ]);
    }

    // ---------------------------------------------------------------
    // 5b) Doktorantlar (demo) — turli tur / ixtisoslik / kafedra / kurs /
    //     o'quv yili bo'yicha taqsimlangan. Barcha ismlar aniq DEMO.
    //     Birinchi yozuv seed doctoral_student user'iga bog'lanadi.
    // student_type: tayanch_doktorant (PhD), doktorant (DSc), mustaqil_izlanuvchi
    // ---------------------------------------------------------------
    $specList = [$spec1, $spec2, $spec3, $spec4];
    $types = ['tayanch_doktorant', 'doktorant', 'mustaqil_izlanuvchi'];
    $enrollYears = [2022, 2023, 2024, 2025];
    $academicYears = ['2022/2023', '2023/2024', '2024/2025', '2025/2026'];

    $studentIds = [];
    // 24 ta doktorant — deterministik taqsimot (test barqarorligi uchun).
    for ($i = 0; $i < 24; $i++) {
        $spec = $specList[$i % 4];
        $type = $types[$i % 3];
        $ptype = $type === 'doktorant' ? 'DSc' : 'PhD';
        $enrollIdx = $i % 4;
        $enroll = $enrollYears[$enrollIdx];
        $course = ($i % 3) + 1;
        $supId = $supIds[$i % count($supIds)];
        $uid = $i === 0 ? $userIds['doctoral_student'] : null;
        // status: aksariyat active, ba'zilari graduated/expelled.
        $status = 'active';
        if ($i % 8 === 7) {
            $status = 'graduated';
        } elseif ($i % 11 === 10) {
            $status = 'expelled';
        }
        $studentIds[$i] = DB::insert('doctoral_students', [
            'user_id' => $uid,
            'full_name' => 'Demo Doktorant ' . ($i + 1),
            'student_type' => $type,
            'department_id' => $specDeps[$spec],
            'specialty_id' => $spec,
            'program_id' => $prog[$spec][$ptype],
            'supervisor_id' => $supId,
            'enrollment_year' => $enroll,
            'course_stage' => $course,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // ---------------------------------------------------------------
    // 5c) Individual rejalar + vazifalar (demo). Reja holati va vazifalar
    //     bajarilishi KPI (to'liq bajarganlar / ortda qolayotganlar) uchun.
    // ---------------------------------------------------------------
    $planApprover = $userIds['research_vice_head'];
    foreach ($studentIds as $i => $sid) {
        $ay = $academicYears[$i % 4];
        // Reja holati: approved / draft / submitted.
        $planStatus = match ($i % 4) {
            0 => 'approved',
            1 => 'approved',
            2 => 'submitted',
            default => 'draft',
        };
        $planId = DB::insert('individual_plans', [
            'student_id' => $sid,
            'supervisor_id' => $supIds[$i % count($supIds)],
            'academic_year' => $ay,
            'start_date' => ($enrollYears[$i % 4]) . '-09-01',
            'end_date' => ($enrollYears[$i % 4] + 3) . '-08-31',
            'status' => $planStatus,
            'approved_by' => $planStatus === 'approved' ? $planApprover : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Har rejaga 4 ta vazifa. Bajarilish darajasi i bo'yicha o'zgaradi:
        //   completedCount 0..4 => to'liq bajargan (=4) yoki ortda (muddati o'tgan bor).
        $completedCount = $i % 5; // 0,1,2,3,4 aylanadi
        for ($t = 0; $t < 4; $t++) {
            $done = $t < $completedCount;
            // Muddati o'tgan, bajarilmagan vazifa => ortda qolish signali.
            $due = date('Y-m-d', strtotime(($t < 2 ? '-40 days' : '+40 days')));
            $tstatus = $done ? 'completed' : (strtotime($due) < time() ? 'overdue' : 'planned');
            DB::insert('plan_tasks', [
                'plan_id' => $planId,
                'title' => 'Demo vazifa ' . ($t + 1),
                'description' => 'Namuna reja vazifasi.',
                'task_type' => ['maqola', 'konferensiya', 'bob', 'tajriba'][$t],
                'due_date' => $due,
                'status' => $tstatus,
                'completed_at' => $done ? $now : null,
                'created_at' => $now,
            ]);
        }
    }

    // ---------------------------------------------------------------
    // 5d) Nashrlar (publications) — turli tur bo'yicha: milliy, xalqaro
    //     (Scopus/WoS), boshqa. KPI: ilmiy maqolalar / xalqaro bazadagi.
    // ---------------------------------------------------------------
    $pubTypes = ['scopus', 'wos', 'milliy', 'boshqa'];
    $pubIds = [];
    foreach ($studentIds as $i => $sid) {
        // Har doktorantda 0..3 ta maqola (i bo'yicha).
        $n = $i % 4;
        for ($p = 0; $p < $n; $p++) {
            $ptype = $pubTypes[($i + $p) % 4];
            $pubIds[] = ['id' => DB::insert('publications', [
                'student_id' => $sid,
                'title' => 'Demo maqola ' . ($i + 1) . '.' . ($p + 1),
                'journal' => 'Demo jurnal ' . (($i + $p) % 5 + 1),
                'publication_type' => $ptype,
                'published_at' => date('Y-m-d', strtotime('-' . (($i * 3 + $p) % 300) . ' days')),
                'doi' => in_array($ptype, ['scopus', 'wos'], true) ? '10.0000/demo.' . $i . '.' . $p : null,
                'created_at' => $now,
            ]), 'student' => $sid, 'type' => $ptype];
        }
    }

    // ---------------------------------------------------------------
    // 5e) Konferensiya materiallari (demo).
    // ---------------------------------------------------------------
    $confLevels = ['xalqaro', 'respublika', 'universitet'];
    $confIds = [];
    foreach ($studentIds as $i => $sid) {
        $n = $i % 3; // 0..2 ta
        for ($c = 0; $c < $n; $c++) {
            $confIds[] = ['id' => DB::insert('conferences', [
                'student_id' => $sid,
                'title' => 'Demo konferensiya tezis ' . ($i + 1) . '.' . ($c + 1),
                'conference_name' => 'Demo ilmiy anjuman ' . (($i + $c) % 4 + 1),
                'level' => $confLevels[($i + $c) % 3],
                'location' => 'Andijon',
                'event_date' => date('Y-m-d', strtotime('-' . (($i * 5 + $c) % 200) . ' days')),
                'created_at' => $now,
            ]), 'student' => $sid];
        }
    }

    // ---------------------------------------------------------------
    // 5f) Ilmiy natijalar (scientific_results) — maqola / konferensiya /
    //     dissertatsiya himoyasi turlari. KPI: dissertatsiya himoyalari.
    // ---------------------------------------------------------------
    foreach ($pubIds as $p) {
        DB::insert('scientific_results', [
            'student_id' => $p['student'],
            'plan_task_id' => null,
            'result_type' => 'maqola',
            'publication_id' => $p['id'],
            'conference_id' => null,
            'title' => 'Maqola natijasi',
            'achieved_at' => date('Y-m-d'),
            'verified' => 1,
            'created_at' => $now,
        ]);
    }
    foreach ($confIds as $c) {
        DB::insert('scientific_results', [
            'student_id' => $c['student'],
            'plan_task_id' => null,
            'result_type' => 'konferensiya',
            'publication_id' => null,
            'conference_id' => $c['id'],
            'title' => 'Konferensiya natijasi',
            'achieved_at' => date('Y-m-d'),
            'verified' => 1,
            'created_at' => $now,
        ]);
    }
    // Dissertatsiya himoyalari — bitiruvchi (graduated) doktorantlar uchun.
    foreach ($studentIds as $i => $sid) {
        if ($i % 8 === 7) {
            DB::insert('scientific_results', [
                'student_id' => $sid,
                'plan_task_id' => null,
                'result_type' => 'dissertatsiya_himoyasi',
                'publication_id' => null,
                'conference_id' => null,
                'title' => 'Dissertatsiya himoyasi',
                'achieved_at' => date('Y-m-d', strtotime('-' . ($i * 10) . ' days')),
                'verified' => 1,
                'created_at' => $now,
            ]);
        }
    }

    // ---------------------------------------------------------------
    // 5g) Attestatsiyalar (demo) — yakuniy KPI'ga hissa.
    // ---------------------------------------------------------------
    foreach ($studentIds as $i => $sid) {
        if ($i % 2 === 0) {
            DB::insert('attestations', [
                'student_id' => $sid,
                'period' => $academicYears[$i % 4],
                'attestation_date' => date('Y-m-d', strtotime('-' . (($i % 6) * 30) . ' days')),
                'result' => $i % 5 === 0 ? 'ijobiy' : ($i % 7 === 0 ? 'salbiy' : 'ijobiy'),
                'commission_notes' => 'Namuna attestatsiya izohi.',
                'created_by' => $userIds['doctorate_office'],
                'created_at' => $now,
            ]);
        }
    }

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
    $critIds = [];
    // Har indikator uchun namunaviy ball (score) — tayyorlik indeksi va RAG
    // taqsimotini ko'rsatish uchun. null => grey (ma'lumot kiritilmagan).
    // green>=80, yellow>=50, else red (settings chegaralari bo'yicha).
    $demoScores = [
        // Mezon 1: yaxshi holat.
        [92.0, 78.0, 85.0],
        // Mezon 2: aralash.
        [64.0, 41.0, null],
        // Mezon 3: muammoli / to'liqsiz.
        [55.0, 30.0, null],
    ];
    $ragFor = function (?float $s): string {
        if ($s === null) {
            return 'grey';
        }
        if ($s >= 80.0) {
            return 'green';
        }
        if ($s >= 50.0) {
            return 'yellow';
        }
        return 'red';
    };

    foreach ($criteria as $ci => [$code, $name, $weight, $order]) {
        $critId = DB::insert('accreditation_criteria', [
            'accreditation_id' => $accId,
            'code' => $code,
            'name' => $name,
            'weight' => $weight,
            'display_order' => $order,
            'is_placeholder' => 1,
            'created_at' => $now,
        ]);
        $critIds[] = $critId;

        // Har mezon uchun 3 ta namuna indikator (ball va RAG bilan).
        foreach ($demoScores[$ci] as $k => $score) {
            $rag = $ragFor($score === null ? null : (float) $score);
            DB::insert('accreditation_indicators', [
                'criteria_id' => $critId,
                'code' => $code . '.' . ($k + 1),
                'name' => "[NAMUNA] Indikator $indicatorNum",
                'requirement' => $placeholderNote,
                'description' => $placeholderNote,
                'self_assessment' => null,
                'weight' => 1.0,
                'rag_status' => $rag,
                'score' => $score,
                'target_value' => '100',
                'actual_value' => $score === null ? null : (string) $score,
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
    // 6a) Ichki audit + kamchiliklar (deficiencies) + chora-tadbirlar.
    //     KPI: muammoli indikatorlar / ochiq kamchiliklar.
    // ---------------------------------------------------------------
    $auditId = DB::insert('internal_audits', [
        'accreditation_id' => $accId,
        'title' => '[NAMUNA] Ichki audit — tayyorgarlik bahosi',
        'audit_date' => date('Y-m-d', strtotime('-20 days')),
        'auditor_id' => $userIds['quality_control'],
        'scope' => $placeholderNote,
        'status' => 'completed',
        'summary' => 'Namuna audit xulosasi.',
        'created_at' => $now,
    ]);

    // Bir nechta kamchilik — turli jiddiylik va holat.
    $defs = [
        ['[NAMUNA] Yetishmayotgan dalil hujjati', 'high', 'open'],
        ['[NAMUNA] Indikator bo\'yicha izoh yetishmaydi', 'medium', 'open'],
        ['[NAMUNA] Reja vazifasi bajarilmagan', 'medium', 'in_progress'],
        ['[NAMUNA] Nashr bazasi tasdiqlanmagan', 'low', 'resolved'],
        ['[NAMUNA] O\'quv dasturi hujjati eskirgan', 'high', 'open'],
    ];
    foreach ($defs as $di => [$dtitle, $severity, $dstatus]) {
        DB::insert('deficiencies', [
            'indicator_id' => null,
            'internal_audit_id' => $auditId,
            'title' => $dtitle,
            'description' => $placeholderNote,
            'severity' => $severity,
            'status' => $dstatus,
            'identified_by' => $userIds['quality_control'],
            'identified_at' => date('Y-m-d', strtotime('-' . (($di + 1) * 5) . ' days')),
            'created_at' => $now,
        ]);
    }

    // ---------------------------------------------------------------
    // 6b) Hujjatlar (documents) — dalil sifatida. KPI: yetishmayotgan
    //     hujjatlar = dalilsiz indikatorlar soni bilan hisoblanadi.
    //     Bir nechta indikatorga dalil bog'laymiz.
    // ---------------------------------------------------------------
    $indicatorRows = DB::select('SELECT id FROM accreditation_indicators ORDER BY id');
    foreach ($indicatorRows as $ir => $indRow) {
        // Har ikkinchi indikatorga dalil hujjati bog'laymiz.
        if ($ir % 2 === 0) {
            $docId = DB::insert('documents', [
                'title' => 'Demo dalil hujjati ' . ($ir + 1),
                'file_path' => 'storage/uploads/demo-' . ($ir + 1) . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 10240,
                'doc_type' => 'dalil',
                'uploaded_by' => $userIds['doctorate_office'],
                'student_id' => null,
                'scientific_result_id' => null,
                'created_at' => $now,
            ]);
            DB::insert('indicator_evidence', [
                'indicator_id' => $indRow['id'],
                'document_id' => $docId,
                'note' => 'Namuna dalil.',
                'linked_by' => $userIds['doctorate_office'],
                'linked_at' => $now,
            ]);
        }
    }

    // Akkreditatsiya tayyorlik indeksini ScoringEngine bilan hisoblab yozamiz.
    $assessment = \App\Core\ScoringEngine::assessAccreditation($accId);
    DB::run('UPDATE accreditations SET readiness_index = :ri WHERE id = :id', [
        'ri' => $assessment['readiness_index'],
        'id' => $accId,
    ]);

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
