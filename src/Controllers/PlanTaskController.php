<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\FileStorage;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\IndividualPlan;
use App\Models\PlanTask;
use App\Models\DoctoralStudent;

/**
 * Individual reja vazifalari (item 5) — qo'shish, maydonlarni yangilash,
 * holat mashinasi bo'yicha holatni o'zgartirish (server tomonda majburiy).
 */
final class PlanTaskController extends Controller
{
   public function store(Request $request): Response
{
    $planId = (int) $request->param('id');
    $plan = IndividualPlan::find($planId);

    if ($plan === null) {
        return Response::html(
            \App\Core\View::render('errors.404'),
            404
        );
    }

    if (Auth::role() === 'doctoral_student') {
        $student = DoctoralStudent::findByUser((int) Auth::id());

        if (
            $student === null ||
            (int) ($plan['student_id'] ?? 0) !== (int) $student['id']
        ) {
            return Response::html(
                \App\Core\View::render('errors.403'),
                403
            );
        }
    }

          $input = $request->all();

    // qolgan kod...
        $validator = Validator::make($input, [
            'title' => 'required|string|max:191',
            'progress_percent' => 'integer',
        ]);
        if ($validator->fails()) {
            return $this->back($request, $validator->firstError() ?? 'Kiritishda xatolik.');
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'plan_id' => $planId,
            'title' => (string) $input['title'],
            'description' => $this->nullable($input['description'] ?? null),
            'task_type' => $this->nullable($input['task_type'] ?? null),
            'due_date' => $this->nullable($input['due_date'] ?? null),
            'progress_percent' => ($input['progress_percent'] ?? '') === '' ? 0 : (int) $input['progress_percent'],
            'student_comment' => $this->nullable($input['student_comment'] ?? null),
            'status' => PlanTask::PLANNED,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $id = DB::insert('plan_tasks', $data);
        AuditLogger::log('create', 'plan_tasks', $id, null, $data);
        Session::flash('success', 'Vazifa qo\'shildi.');
        return $this->redirect('/plans/' . $planId);
    }

    /**
     * Vazifa maydonlarini (izoh, foiz, xulosa, tasdiq) yangilash va/yoki
     * holat o'tishini bajarish. Holat o'tishi holat mashinasi + rol gating
     * orqali tekshiriladi; yaroqsiz o'tish RAD ETILADI.
     */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $task = PlanTask::find($id);
        if ($task === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        
if (Auth::role() === 'doctoral_student') {
    $student = DoctoralStudent::findByUser((int) Auth::id());

    if ($student === null) {
        return Response::html(
            \App\Core\View::render('errors.403'),
            403
        );
    }

    $plan = IndividualPlan::find((int) $task['plan_id']);

    if (
        $plan === null ||
        (int) ($plan['student_id'] ?? 0) !== (int) $student['id']
    ) {
        return Response::html(
            \App\Core\View::render('errors.403'),
            403
        );
    }
}

        if (Auth::role() === 'doctoral_student') {
    $student = DoctoralStudent::findByUser((int) Auth::id());

    if (
        $student === null ||
        (int) ($plan['student_id'] ?? 0) !== (int) $student['id']
    ) {
        return Response::html(
            \App\Core\View::render('errors.403'),
            403
        );
    }
}
        
        $input = $request->all();
        $now = date('Y-m-d H:i:s');
        $updates = [];

        // Maydon yozuvi (progress, izoh, xulosa, muddat, dalil) faqat
        // individual_plans.edit ruxsatiga ega rollarga ochiq. Faqat ko'rish
        // ruxsatiga ega rollar (institute_leadership, quality_control, expert)
        // vazifa maydonlarini o'zgartira olmaydi — holat o'tishi esa alohida,
        // holat mashinasi + rol gating orqali tekshiriladi.
        $canEditFields = Auth::can('individual_plans.edit');

        // Maydon yangilanishlari (rolga qarab tegishli maydonlar).
        if ($canEditFields) {
            foreach ([
                'progress_percent' => 'int',
                'student_comment' => 'str',
                'supervisor_conclusion' => 'str',
                'office_note' => 'str',
                'completed_date' => 'str',
                'due_date' => 'str',
            ] as $field => $cast) {
                if (array_key_exists($field, $input)) {
                    $val = $input[$field];
                    if ($cast === 'int') {
                        $updates[$field] = ($val === '' || $val === null) ? null : (int) $val;
                    } else {
                        $updates[$field] = $this->nullable($val);
                    }
                }
            }

            // Tasdiqlovchi hujjat (ixtiyoriy) — FileStorage orqali.
            $file = $request->file('evidence');
            if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                try {
                    $stored = FileStorage::store($file);
                    $updates['evidence_path'] = $stored['path'];
                    AuditLogger::log('upload', 'plan_tasks', $id, null, ['evidence' => $stored['path']]);
                } catch (\RuntimeException $ex) {
                    return $this->back($request, 'Hujjat: ' . $ex->getMessage());
                }
            }
        } else {
            // Faqat ko'rish ruxsati bo'lgan rol maydon yozishga urinsa — rad
            // etamiz (holat o'tishisiz maydon o'zgartirishga ruxsat yo'q).
            $wroteField = false;
            foreach (['progress_percent', 'student_comment', 'supervisor_conclusion', 'office_note', 'completed_date', 'due_date'] as $field) {
                if (array_key_exists($field, $input)) {
                    $wroteField = true;
                    break;
                }
            }
            $file = $request->file('evidence');
            if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $wroteField = true;
            }
            if ($wroteField) {
                return $this->back(
                    $request,
                    'Vazifa maydonlarini tahrirlash uchun ruxsat yo\'q (individual_plans.edit talab qilinadi).'
                );
            }
        }

        // Holat o'tishi (agar so'ralgan bo'lsa).
        $target = $this->nullable($input['target_status'] ?? null);
        if ($target !== null && $target !== (string) $task['status']) {
            $role = Auth::role();
            $from = (string) $task['status'];
            if (!PlanTask::roleCanTransition($role, $from, $target)) {
                return $this->back(
                    $request,
                    'Yaroqsiz holat o\'tishi rad etildi: "' . (PlanTask::LABELS[$from] ?? $from)
                    . '" -> "' . (PlanTask::LABELS[$target] ?? $target) . '" (rol yoki tartib mos emas).'
                );
            }
            $updates['status'] = $target;
            if ($target === PlanTask::COMPLETED) {
                $updates['completed_at'] = $now;
                if (empty($updates['completed_date'] ?? $task['completed_date'])) {
                    $updates['completed_date'] = date('Y-m-d');
                }
                $updates['progress_percent'] = 100;
            }
            AuditLogger::log('approve', 'plan_tasks', $id, ['status' => $from], ['status' => $target]);
        }

        if ($updates === []) {
            return $this->back($request, 'O\'zgartirish kiritilmadi.');
        }

        $updates['updated_at'] = $now;
        $sets = implode(', ', array_map(static fn ($k) => "$k = :$k", array_keys($updates)));
        DB::run("UPDATE plan_tasks SET $sets WHERE id = :id", array_merge($updates, ['id' => $id]));
        AuditLogger::log('update', 'plan_tasks', $id, $task, $updates);

        Session::flash('success', 'Vazifa yangilandi.');
        return $this->redirect('/plans/' . (int) $task['plan_id']);
    }

    private function nullable(mixed $v): ?string
    {
        return ($v === null || $v === '') ? null : (string) $v;
    }

    private function back(Request $request, string $error): Response
    {
        Session::flash('error', $error);
        return $this->redirect($request->header('Referer') ?? '/plans');
    }
}
