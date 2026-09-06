<?php

use App\Database\Schema;

return function (): void {
    Schema::create('supervisor_requests', function ($t) {
        $t->id();

        // So‘rov yuborgan doktorant
        $t->integer('student_id', false);

        // Doktorant taklif qilgan ilmiy rahbar
        $t->integer('supervisor_id', false);

        // pending / approved / rejected
        $t->string('status', 32, false, 'pending');

        // Doktorantning ixtiyoriy izohi
        $t->text('student_note');

        // Ilmiy bo‘limning izohi yoki rad etish sababi
        $t->text('review_note');

        // So‘rovni ko‘rib chiqqan foydalanuvchi
        $t->integer('reviewed_by');

        // Ko‘rib chiqilgan vaqt
        $t->timestamp('reviewed_at');

        $t->timestamp('created_at');
        $t->timestamp('updated_at');

        $t->foreign('student_id', 'doctoral_students');
        $t->foreign('supervisor_id', 'supervisors');
        $t->foreign('reviewed_by', 'users');
    });
};
