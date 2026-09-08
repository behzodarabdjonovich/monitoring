<?php

use App\Core\DB;

return function (): void {
    $roleId = DB::scalar(
    "SELECT id FROM roles WHERE name = :name",
    ['name' => 'doctorate_office']
);

    if (!$roleId) {
        return;
    }

    $permissionNames = [
    'deficiencies.view',
    'action_plans.view',
    'action_plans.create',
    'action_plans.edit',
    'internal_audits.view',
];

    foreach ($permissionNames as $permissionName) {
       $permissionId = DB::scalar(
    "SELECT id FROM permissions WHERE code = :code",
    ['code' => $permissionName]
);

        if (!$permissionId) {
            continue;
        }

        $exists = (int) DB::scalar(
            "SELECT COUNT(*)
             FROM role_permission
             WHERE role_id = :role_id
               AND permission_id = :permission_id",
            [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]
        );

        if ($exists === 0) {
            DB::insert('role_permission', [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
};
