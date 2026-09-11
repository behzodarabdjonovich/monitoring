<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Department;

final class DepartmentController extends Controller
{
 public function index(Request $request): Response
{
    $departments = Department::all();

    return $this->view('departments.index', [
        'departments' => $departments,
    ]);
} 
 public function store(Request $request): Response
{
    $name = trim((string) $request->input('name'));
    $code = trim((string) $request->input('code'));

    if ($name === '') {
        Session::flash('error', 'Kafedra nomini kiriting.');
        return $this->redirect('/departments');
    }

    Department::create(
        $name,
        $code !== '' ? $code : null
    );

    Session::flash('success', 'Kafedra muvaffaqiyatli qo‘shildi.');

    return $this->redirect('/departments');
}  
 public function edit(Request $request): Response
{
    $id = (int) $request->param('id');
    $department = Department::find($id);

    if ($department === null) {
        return $this->notFound();
    }

    return $this->view('departments.edit', [
        'department' => $department,
    ]);
}

public function update(Request $request): Response
{
    $id = (int) $request->param('id');
    $department = Department::find($id);

    if ($department === null) {
        return $this->notFound();
    }

    $name = trim((string) $request->input('name'));
    $code = trim((string) $request->input('code'));

    if ($name === '') {
        Session::flash('error', 'Kafedra nomini kiriting.');
        return $this->redirect('/departments/' . $id . '/edit');
    }

    Department::update(
        $id,
        $name,
        $code !== '' ? $code : null
    );

    Session::flash('success', 'Kafedra muvaffaqiyatli tahrirlandi.');

    return $this->redirect('/departments');
}
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
