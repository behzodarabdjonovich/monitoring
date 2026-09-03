<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\ScoringEngine;
use App\Core\Session;

/**
 * Sozlamalar (Sozlamalar / Settings) moduli — Super Admin.
 *
 * Baholash metodikasini SOZLASH: RAG chegaralari (green/yellow),
 * baho holatlari uchun kanonik ballar, kulrang siyosati va per-holat ballar.
 * O'zgargach barcha akkreditatsiya tayyorlik indekslari qayta hisoblanadi
 * (recompute on change). Barcha qiymatlar settings jadvalida saqlanadi —
 * ScoringEngine ularni har hisoblashda o'qiydi (kodda qattiq kodlash yo'q).
 */
final class SettingsController extends Controller
{
    /** Sozlanadigan baholash kalitlari va tavsiflari. */
    private const SCORING_KEYS = [
        'scoring.threshold_green' => ['RAG yashil chegarasi (foiz)', 'number'],
        'scoring.threshold_yellow' => ['RAG sariq chegarasi (foiz)', 'number'],
        'scoring.score_green' => ['"Talabga to\'liq mos" bahosi bali (0..100)', 'number'],
        'scoring.score_yellow' => ['"Qisman mos" bahosi bali (0..100)', 'number'],
        'scoring.score_red' => ['"Talabga mos emas" bahosi bali (0..100)', 'number'],
        'scoring.grey_policy' => ['Baholanmagan (kulrang) indikator siyosati', 'select'],
        'scoring.default_indicator_weight' => ['Indikatorning standart og\'irligi', 'number'],
    ];

    public function index(Request $request): Response
    {
        $values = $this->currentValues();
        return $this->view('settings.index', [
            'user' => Auth::user(),
            'title' => 'Sozlamalar',
            'active' => 'settings',
            'settings' => $values,
            'keys' => self::SCORING_KEYS,
            'thresholds' => ScoringEngine::thresholds(),
            'accreditations' => \App\Models\Accreditation::allWithReadiness(),
            'canConfigure' => Auth::can('settings.configure'),
        ]);
    }

    public function update(Request $request): Response
    {
        if (!Auth::can('settings.configure')) {
            return Response::html(\App\Core\View::render('errors.403'), 403);
        }
        $input = $request->all();
        $now = date('Y-m-d H:i:s');
        $old = $this->currentValues();
        $new = [];

        foreach (self::SCORING_KEYS as $key => [$label, $type]) {
            // PHP POST maydon nomlaridagi nuqtani "_" ga aylantiradi, shuning
            // uchun ikkala variantni ham tekshiramiz (scoring.x va scoring_x).
            $underscored = str_replace('.', '_', $key);
            if (array_key_exists($key, $input)) {
                $raw = (string) $input[$key];
            } elseif (array_key_exists($underscored, $input)) {
                $raw = (string) $input[$underscored];
            } else {
                continue;
            }
            $value = $this->sanitize($key, $raw, $type);
            $new[$key] = $value;
            $exists = (int) DB::scalar('SELECT COUNT(*) FROM settings WHERE key = :k', ['k' => $key]);
            if ($exists > 0) {
                DB::run('UPDATE settings SET value = :v, updated_at = :u WHERE key = :k', ['v' => $value, 'u' => $now, 'k' => $key]);
            } else {
                DB::insert('settings', ['key' => $key, 'value' => $value, 'type' => $type === 'select' ? 'string' : 'number', 'description' => $label, 'updated_at' => $now]);
            }
        }

        // Chegara mantig'i buzilmasligi uchun: green >= yellow.
        $g = (float) ($new['scoring.threshold_green'] ?? $old['scoring.threshold_green'] ?? 80);
        $y = (float) ($new['scoring.threshold_yellow'] ?? $old['scoring.threshold_yellow'] ?? 50);
        if ($y > $g) {
            DB::run('UPDATE settings SET value = :v WHERE key = :k', ['v' => (string) $g, 'k' => 'scoring.threshold_yellow']);
        }

        // Sozlamalar o'zgargach barcha akkreditatsiya indekslarini qayta hisoblash.
        ScoringEngine::refreshAll();
        AuditLogger::log('update', 'settings', null, $old, $new);
        Session::flash('success', 'Baholash metodikasi sozlamalari saqlandi va tayyorlik indekslari qayta hisoblandi.');
        return $this->redirect('/settings');
    }

    /**
     * @return array<string,string>
     */
    private function currentValues(): array
    {
        $rows = DB::select('SELECT key, value FROM settings');
        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r['key']] = (string) $r['value'];
        }
        // Standart qiymatlar (agar settings jadvalida yo'q bo'lsa).
        $defaults = [
            'scoring.threshold_green' => '80',
            'scoring.threshold_yellow' => '50',
            'scoring.score_green' => '100',
            'scoring.score_yellow' => '60',
            'scoring.score_red' => '20',
            'scoring.grey_policy' => 'exclude',
            'scoring.default_indicator_weight' => '1.0',
        ];
        return array_merge($defaults, $map);
    }

    private function sanitize(string $key, string $value, string $type): string
    {
        if ($key === 'scoring.grey_policy') {
            return $value === 'zero' ? 'zero' : 'exclude';
        }
        if ($type === 'number') {
            $num = (float) $value;
            if (str_contains($key, 'threshold') || str_contains($key, 'score')) {
                $num = max(0.0, min(100.0, $num));
            } elseif (str_contains($key, 'weight')) {
                $num = $num > 0 ? $num : 1.0;
            }
            // Butun bo'lsa butun ko'rinishda saqlaymiz.
            return $num == (int) $num ? (string) (int) $num : (string) $num;
        }
        return $value;
    }
}
