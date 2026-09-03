<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;

/**
 * Audit jurnali ko'rigi (item 17) — FAQAT o'qish, FAQAT Super Admin.
 *
 * kim (user), qachon (created_at), nima yaratildi/o'zgartirildi (action +
 * entity), qaysi fayl yuklandi (upload), nima tasdiqlandi (approve), oldingi
 * qiymat (old_values), yangi qiymat (new_values). O'chirish/yangilash
 * marshruti YO'Q — audit_logs o'zgarmas.
 */
final class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = AuditLog::sanitizeFilters($request->all());
        $perPage = 100;
        $page = max(1, (int) $request->query('page', '1'));
        $offset = ($page - 1) * $perPage;

        $total = AuditLog::count($filters);
        $logs = AuditLog::all($filters, $perPage, $offset);
        $options = AuditLog::filterOptions();

        return $this->view('audit_logs.index', [
            'user' => Auth::user(),
            'title' => 'Audit jurnali',
            'active' => 'audit-logs',
            'logs' => $logs,
            'filters' => $filters,
            'options' => $options,
            'users' => DB::select('SELECT id, full_name, username FROM users ORDER BY full_name'),
            'actionLabels' => self::ACTION_LABELS,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage),
        ]);
    }

    /** Amal kodlari uchun o'zbekcha yorliqlar. */
    public const ACTION_LABELS = [
        'create' => 'Yaratildi',
        'update' => 'O\'zgartirildi',
        'upload' => 'Fayl yuklandi',
        'approve' => 'Tasdiqlandi',
        'close' => 'Yopildi',
        'delete' => 'O\'chirildi',
        'login' => 'Tizimga kirdi',
        'logout' => 'Tizimdan chiqdi',
        'login_failed' => 'Muvaffaqiyatsiz kirish',
        'password_reset' => 'Parol tiklandi',
        'password_reset_requested' => 'Parol tiklash so\'raldi',
        'block' => 'Bloklandi',
        'unblock' => 'Blokdan chiqarildi',
    ];
}
