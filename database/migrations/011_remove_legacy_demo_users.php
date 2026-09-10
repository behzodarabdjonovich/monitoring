<?php

use App\Core\DB;

return function (): void {
    $mapping = [
        'ilmiy' => 'admin',
        'rahbariyat' => 'admin',
        'rahbar' => 'doktorantura',
        'kafedra' => 'doktorantura',
        'sifat' => 'ekspert',
    ];

    $userReferences = [
        ['supervisors', 'user_id'],
        ['doctoral_students', 'user_id'],
        ['individual_plans', 'approved_by'],
        ['attestations', 'created_by'],
        ['documents', 'uploaded_by'],
        ['indicator_evidence', 'linked_by'],
        ['internal_audits', 'auditor_id'],
        ['deficiencies', 'identified_by'],
        ['action_plans', 'responsible_user_id'],
        ['notifications', 'user_id'],
        ['audit_logs', 'user_id'],
        ['password_resets', 'user_id'],
    ];

    foreach ($mapping as $oldUsername => $targetUsername) {
        $oldUser = DB::selectOne(
            'SELECT id FROM users WHERE username = :username LIMIT 1',
            ['username' => $oldUsername]
        );

        if (!$oldUser) {
            continue;
        }

        $targetUser = DB::selectOne(
            'SELECT id FROM users WHERE username = :username LIMIT 1',
            ['username' => $targetUsername]
        );

        if (!$targetUser) {
            continue;
        }

        $oldUserId = (int) $oldUser['id'];
        $targetUserId = (int) $targetUser['id'];

        foreach ($userReferences as [$table, $column]) {
            DB::run(
                "UPDATE {$table}
                 SET {$column} = :target_id
                 WHERE {$column} = :old_id",
                [
                    'target_id' => $targetUserId,
                    'old_id' => $oldUserId,
                ]
            );
        }

        DB::run(
            'DELETE FROM users WHERE id = :id',
            ['id' => $oldUserId]
        );
    }
};
