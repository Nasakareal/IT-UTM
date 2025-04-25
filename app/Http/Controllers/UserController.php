<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

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

        return view('settings.users.create', compact('roles', 'categorias', 'caracteres'));
    }

    public function store(Request $request)
    {
        // Validamos sin el campo 'name'
        $validated = $request->validate([
            'nombres'                => 'required|string|max:125',
            'apellido_paterno'       => 'nullable|string|max:125',
            'apellido_materno'       => 'nullable|string|max:125',
            'curp'                   => 'required|string|size:18|unique:users,curp',
            'correo_institucional'   => 'required|email|unique:users,correo_institucional',
            'correo_personal'        => 'required|email',
            'categoria'              => 'required|in:'.implode(',', [
                'Titular C','Titular B','Titular A',
                'Asociado C','Técnico Académico C',
                'Técnico Académico B','Técnico Académico A',
                'Profesor de Asignatura B',
            ]),
            'caracter'               => 'required|in:Indeterminado,Determinado',
            'password'               => 'required|min:6|confirmed',
            'area'                   => 'nullable|string|max:125',
            'foto_perfil'            => 'nullable|image|max:2048',
            'role'                   => 'required|exists:roles,name',
        ]);

        try {
            // Construimos el campo 'name' uniendo los apellidos y nombres
            $fullName = trim(
                $validated['nombres']
                .' '.($validated['apellido_paterno'] ?? '')
                .' '.($validated['apellido_materno'] ?? '')
            );

            // Guardamos la foto si llegó
            if ($request->hasFile('foto_perfil')) {
                $validated['foto_perfil'] = $request
                    ->file('foto_perfil')
                    ->store('perfiles', 'public');
            }

            $user = User::create([
                'name'                   => $fullName,
                'nombres'                => $validated['nombres'],
                'apellido_paterno'       => $validated['apellido_paterno'],
                'apellido_materno'       => $validated['apellido_materno'],
                'curp'                   => $validated['curp'],
                'correo_institucional'   => $validated['correo_institucional'],
                'correo_personal'        => $validated['correo_personal'],
                'categoria'              => $validated['categoria'],
                'caracter'               => $validated['caracter'],
                'password'               => Hash::make($validated['password']),
                'area'                   => $validated['area'] ?? null,
                'foto_perfil'            => $validated['foto_perfil'] ?? null,
                'estado'                 => 'Activo',
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

        return view('settings.users.edit', compact('user', 'roles', 'categorias', 'caracteres'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'nombres'                => 'required|string|max:125',
            'apellido_paterno'       => 'nullable|string|max:125',
            'apellido_materno'       => 'nullable|string|max:125',
            'curp'                   => 'required|string|size:18|unique:users,curp,'.$id,
            'correo_institucional'   => 'required|email|unique:users,correo_institucional,'.$id,
            'correo_personal'        => 'required|email',
            'categoria'              => 'required|in:'.implode(',', [
                'Titular C','Titular B','Titular A',
                'Asociado C','Técnico Académico C',
                'Técnico Académico B','Técnico Académico A',
                'Profesor de Asignatura B',
            ]),
            'caracter'               => 'required|in:Indeterminado,Determinado',
            'area'                   => 'nullable|string|max:125',
            'foto_perfil'            => 'nullable|image|max:2048',
            'role'                   => 'required|exists:roles,name',
        ];
        if ($request->filled('password')) {
            $rules['password'] = 'min:6|confirmed';
        }

        $validated = $request->validate($rules);

        try {
            $user = User::findOrFail($id);

            // Generamos el nombre completo de nuevo
            $fullName = trim(
                $validated['nombres']
                .' '.($validated['apellido_paterno'] ?? '')
                .' '.($validated['apellido_materno'] ?? '')
            );

            if ($request->hasFile('foto_perfil')) {
                $validated['foto_perfil'] = $request
                    ->file('foto_perfil')
                    ->store('perfiles', 'public');
            }

            $data = [
                'name'                   => $fullName,
                'nombres'                => $validated['nombres'],
                'apellido_paterno'       => $validated['apellido_paterno'],
                'apellido_materno'       => $validated['apellido_materno'],
                'curp'                   => $validated['curp'],
                'correo_institucional'   => $validated['correo_institucional'],
                'correo_personal'        => $validated['correo_personal'],
                'categoria'              => $validated['categoria'],
                'caracter'               => $validated['caracter'],
                'area'                   => $validated['area'] ?? null,
                'foto_perfil'            => $validated['foto_perfil'] ?? $user->foto_perfil,
                'estado'                 => 'Activo',
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
            return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error eliminando usuario: {$e->getMessage()}");
            return redirect()->back()->withErrors('Ocurrió un error al eliminar el usuario.');
        }
    }
}
