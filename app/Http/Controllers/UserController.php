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

        $areas = DB::connection('cargahoraria')
            ->table('programs')
            ->where('estado', 1)
            ->select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        return view('settings.users.create', compact('roles', 'categorias', 'caracteres', 'profesores', 'areas'));
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
            'areas'                 => 'nullable|array',
            'areas.*'               => 'string|max:125',
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
                'area'                 => isset($validated['areas']) ? implode(',', $validated['areas']) : null,
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

        // 1. Buscar el profesor actual aunque no tenga materias activas
        $profesorActual = null;
        if ($user->teacher_id) {
            $profesorActual = DB::connection('cargahoraria')
                ->table('teachers')
                ->where('teacher_id', $user->teacher_id)
                ->select('teacher_id', 'teacher_name')
                ->first();
        }

        // 2. Excluir profesores ya usados en users, excepto el actual
        $profesoresConUsuario = User::whereNotNull('teacher_id')
            ->where('id', '!=', $user->id)
            ->pluck('teacher_id');

        // 3. Obtener profesores con materias activas, excluyendo duplicados
        $profesores = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('teachers as t', 'ts.teacher_id', '=', 't.teacher_id')
            ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
            ->where('t.estado', 1)
            ->where('s.estado', 1)
            ->whereNotIn('t.teacher_id', $profesoresConUsuario)
            ->select('t.teacher_id', 't.teacher_name')
            ->distinct()
            ->orderBy('t.teacher_name')
            ->get();

        // 4. Asegurarse de incluir al profesor actual si no está en la lista
        if ($profesorActual && !$profesores->contains('teacher_id', $profesorActual->teacher_id)) {
            $profesores->push($profesorActual);
        }

        // 5. Obtener las áreas disponibles
        $areas = DB::connection('cargahoraria')
            ->table('programs')
            ->where('estado', 1)
            ->select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        $user_areas = $user->area ? explode(',', $user->area) : [];

        return view('settings.users.edit', compact(
            'user', 'roles', 'categorias', 'caracteres', 'profesores', 'areas', 'user_areas'
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
            'areas'                 => 'nullable|array',
            'areas.*'               => 'string|max:125',
            'foto_perfil'           => 'nullable|image|max:2048',
            'role'                  => 'required|exists:roles,name',
            'teacher_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request, $id) {
                    if ($request->role === 'Profesor') {
                        if (empty($value)) {
                            return $fail('El campo teacher_id es obligatorio cuando el rol es Profesor.');
                        }

                        // Validar existencia en la tabla teachers
                        $exists = DB::connection('cargahoraria')
                            ->table('teachers')
                            ->where('teacher_id', $value)
                            ->exists();

                        if (!$exists) {
                            return $fail('El teacher_id seleccionado no existe.');
                        }

                        // Validar que no esté asignado a otro usuario
                        $yaAsignado = \App\Models\User::where('teacher_id', $value)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($yaAsignado) {
                            return $fail('Este teacher_id ya está asignado a otro usuario.');
                        }
                    }
                }
            ],
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
                'nombres'              => $validated['nombres'],
                'curp'                 => $validated['curp'],
                'correo_institucional' => $validated['correo_institucional'],
                'correo_personal'      => $validated['correo_personal'],
                'categoria'            => $validated['categoria'],
                'caracter'             => $validated['caracter'],
                'area'                 => isset($validated['areas']) ? implode(',', $validated['areas']) : null,
                'foto_perfil'          => $validated['foto_perfil'] ?? $user->foto_perfil,
                'estado'               => 'Activo',
                'teacher_id'           => $validated['teacher_id'] ?? null,
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($validated['password']);
                $data['must_change_password'] = 1;
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
