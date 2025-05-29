<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('settings.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $categorias = [
            'Titular C', 'Titular B', 'Titular A',
            'Asociado C', 'Técnico Académico C',
            'Técnico Académico B', 'Técnico Académico A',
            'Profesor de Asignatura B',
        ];
        $caracteres = ['Indeterminado', 'Determinado'];
        $profesoresConUsuario = User::whereNotNull('teacher_id')->pluck('teacher_id');
        $profesores = DB::connection('cargahoraria')
            ->table('teachers as t')
            ->where('t.estado', 1)
            ->whereNotIn('t.teacher_id', $profesoresConUsuario)
            ->select('t.teacher_id', 't.teacher_name')
            ->orderBy('t.teacher_name')
            ->get();

        return view('settings.users.create', compact(
            'roles', 'categorias', 'caracteres', 'profesores'
        ));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres'               => 'required|string|max:125',
            'curp'                  => 'required|string|size:18|unique:users,curp',
            'correo_institucional'  => 'required|email|unique:users,correo_institucional',
            'correo_personal'       => 'nullable|email',
            'categoria'             => 'required|in:' . implode(',', [
                'Titular C','Titular B','Titular A',
                'Asociado C','Técnico Académico C',
                'Técnico Académico B','Técnico Académico A',
                'Profesor de Asignatura B',
            ]),
            'caracter'              => 'required|in:Indeterminado,Determinado',
            'password'              => 'required|min:6|confirmed',
            'area'                  => 'nullable|string|max:125',
            'foto_perfil'           => 'nullable|image|max:2048',
            'role'                  => 'required|exists:roles,name',
            'teacher_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->role === 'Profesor' && empty($value)) {
                        $fail('El campo teacher_id es obligatorio cuando el rol es Profesor.');
                    }
                    if ($request->role === 'Profesor') {
                        $exists = DB::connection('cargahoraria')
                            ->table('teachers')
                            ->where('teacher_id', $value)
                            ->exists();
                        if (!$exists) {
                            $fail('El teacher_id seleccionado no existe.');
                        }
                    }
                }
            ],
        ]);

        try {
            if ($request->hasFile('foto_perfil')) {
                $validated['foto_perfil'] = $request->file('foto_perfil')->store('perfiles', 'public');
            }

            $user = User::create([
                'nombres'               => $validated['nombres'],
                'curp'                  => $validated['curp'],
                'correo_institucional' => $validated['correo_institucional'],
                'correo_personal'      => $validated['correo_personal'],
                'categoria'            => $validated['categoria'],
                'caracter'             => $validated['caracter'],
                'password'             => Hash::make($validated['password']),
                'area'                 => $validated['area'] ?? null,
                'foto_perfil'          => $validated['foto_perfil'] ?? null,
                'estado'               => 'Activo',
                'teacher_id'           => $validated['teacher_id'] ?? null,
                'must_change_password' => true,
            ]);

            $user->assignRole($validated['role']);

            Log::info("Usuario creado: {$user->correo_institucional}");
            return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error creando usuario: {$e->getMessage()}");
            return redirect()->back()->withInput()->withErrors('Ocurrió un error al crear el usuario.');
        }
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('settings.users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all();
        $categorias = [
            'Titular C', 'Titular B', 'Titular A',
            'Asociado C', 'Técnico Académico C',
            'Técnico Académico B', 'Técnico Académico A',
            'Profesor de Asignatura B',
        ];
        $caracteres = ['Indeterminado', 'Determinado'];

        $profesores = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('teachers as t', 'ts.teacher_id', '=', 't.teacher_id')
            ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
            ->where('t.estado', 1)
            ->where('s.estado', 1)
            ->select('t.teacher_id', 't.teacher_name')
            ->distinct()
            ->orderBy('t.teacher_name')
            ->get();

        return view('settings.users.edit', compact(
            'user', 'roles', 'categorias', 'caracteres', 'profesores'
        ));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'nombres'               => 'required|string|max:125',
            'curp'                  => 'required|string|size:18|unique:users,curp,' . $id,
            'correo_institucional'  => 'required|email|unique:users,correo_institucional,' . $id,
            'correo_personal'       => 'required|email',
            'categoria'             => 'required|in:' . implode(',', [
                'Titular C','Titular B','Titular A',
                'Asociado C','Técnico Académico C',
                'Técnico Académico B','Técnico Académico A',
                'Profesor de Asignatura B',
            ]),
            'caracter'              => 'required|in:Indeterminado,Determinado',
            'area'                  => 'nullable|string|max:125',
            'foto_perfil'           => 'nullable|image|max:2048',
            'role'                  => 'required|exists:roles,name',
            'teacher_id'            => 'required_if:role,Profesor|exists:cargahoraria.teachers,teacher_id',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'min:6|confirmed';
        }

        $validated = $request->validate($rules);

        try {
            $user = User::findOrFail($id);

            if ($request->hasFile('foto_perfil')) {
                $validated['foto_perfil'] = $request->file('foto_perfil')->store('perfiles', 'public');
            }

            $data = [
                'nombres'               => $validated['nombres'],
                'curp'                  => $validated['curp'],
                'correo_institucional'  => $validated['correo_institucional'],
                'correo_personal'       => $validated['correo_personal'],
                'categoria'             => $validated['categoria'],
                'caracter'              => $validated['caracter'],
                'area'                  => $validated['area'] ?? null,
                'foto_perfil'           => $validated['foto_perfil'] ?? $user->foto_perfil,
                'estado'                => 'Activo',
                'teacher_id'            => $validated['teacher_id'] ?? null,
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($validated['password']);
            }

            $user->update($data);
            $user->syncRoles([$validated['role']]);

            Log::info("Usuario actualizado: {$user->correo_institucional}");
            return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error actualizando usuario: {$e->getMessage()}");
            return redirect()->back()->withInput()->withErrors('Ocurrió un error al actualizar el usuario.');
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            Log::info("Usuario eliminado: {$user->correo_institucional}");
            return redirect()->route('users.index')
                             ->with('success', 'Usuario eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error eliminando usuario: {$e->getMessage()}");
            return redirect()->back()
                             ->withErrors('Ocurrió un error al eliminar el usuario.');
        }
    }
}
