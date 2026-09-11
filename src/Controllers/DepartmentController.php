<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Department;

final class DepartmentController extends Controller
{
    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        $department = Department::find($id);

        if ($department === null) {
            return $this->notFound();
        }

        if (!Department::delete($id)) {
            Session::flash(
                'error',
                'Kafedrani o‘chirib bo‘lmadi. U ixtisoslik, ilmiy rahbar yoki doktorantga bog‘langan.'
            );

            return $this->redirect('/specialties');
        }

        Session::flash('success', 'Kafedra muvaffaqiyatli o‘chirildi.');

        return $this->redirect('/specialties');
    }
}
