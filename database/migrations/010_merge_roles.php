<?php

use App\Core\DB;

return function (): void {
    $mapping = [
        'research_vice_head' => 'super_admin',
        'institute_leadership' => 'super_admin',
        'supervisor' => 'doctorate_office',
        'department_head' => 'doctorate_office',
        'quality_control' => 'expert',
    ];

    foreach ($mapping as $oldRoleName => $newRoleName) {
        $oldRole = DB::selectOne(
            'SELECT id FROM roles WHERE name = :name LIMIT 1',
            ['name' => $oldRoleName]
        );

        if (!$oldRole) {
            continue;
        }

        $newRole = DB::selectOne(
            'SELECT id FROM roles WHERE name = :name LIMIT 1',
            ['name' => $newRoleName]
        );

        if (!$newRole) {
            continue;
        }

        $oldRoleId = (int) $oldRole['id'];
        $newRoleId = (int) $newRole['id'];

        // Eski rol foydalanuvchilarini yangi asosiy rolga o'tkazish.
        DB::run(
            'UPDATE users
             SET role_id = :new_role_id
             WHERE role_id = :old_role_id',
            [
                'new_role_id' => $newRoleId,
                'old_role_id' => $oldRoleId,
            ]
        );

        // Akkreditatsiya indikatorlaridagi mas'ul rolni ham ko'chirish.
        DB::run(
            'UPDATE accreditation_indicators
             SET responsible_role_id = :new_role_id
             WHERE responsible_role_id = :old_role_id',
            [
                'new_role_id' => $newRoleId,
                'old_role_id' => $oldRoleId,
            ]
        );

        // Eski rol ruxsatlarini yangi rolga birlashtirish.
        $permissions = DB::select(
            'SELECT permission_id
             FROM role_permission
             WHERE role_id = :role_id',
            ['role_id' => $oldRoleId]
        );

        foreach ($permissions as $permission) {
            $permissionId = (int) $permission['permission_id'];

            $exists = DB::selectOne(
                'SELECT id
                 FROM role_permission
                 WHERE role_id = :role_id
                   AND permission_id = :permission_id
                 LIMIT 1',
                [
                    'role_id' => $newRoleId,
                    'permission_id' => $permissionId,
                ]
            );

            if (!$exists) {
                DB::insert('role_permission', [
                    'role_id' => $newRoleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        DB::run(
            'DELETE FROM role_permission WHERE role_id = :role_id',
            ['role_id' => $oldRoleId]
        );

        DB::run(
            'DELETE FROM roles WHERE id = :id',
            ['id' => $oldRoleId]
        );
    }
};
