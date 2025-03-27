<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'ver configuraciones',
            'ver usuarios',
            'crear usuarios',
            'editar usuarios',
            'eliminar usuarios',

            'ver roles',
            'crear roles',
            'editar roles',
            'eliminar roles',

            'ver secciones',
            'crear secciones',
            'editar secciones',
            'eliminar secciones',

            'ver modulos',
            'crear modulos',
            'editar modulos',
            'eliminar modulos',

            'ver carpetas',
            'crear carpetas',
            'editar carpetas',
            'eliminar carpetas',
            'subir archivos',

            'ver subsecciones',
            'crear subsecciones',
            'editar subsecciones',
            'eliminar subsecciones',

            'ver comunicados',
            'crear comunicados',
            'editar comunicados',
            'eliminar comunicados',

            'ver submodulos',
            'crear submodulos',
            'editar submodulos',
            'eliminar submodulos',

            'ver correspondencias',
            'crear correspondencias',
            'editar correspondencias',
            'eliminar correspondencias',

            'ver archivos',
            'crear archivos',
            'editar archivos',
            'eliminar archivos',
        ];

        // Crear permisos si no existen
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Definición de roles y permisos asignados
        $roles = [
            'Administrador' => $permissions,
            'Subdirector' => [
                'ver configuraciones',
                'ver usuarios',
                'ver roles',
            ],
            'Empleado' => [
                'ver requisiciones',
            ],
            'Observador' => [
                'ver requisiciones',
            ],
        ];

        // Crear roles y asignar permisos
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Obtener permisos y asignarlos al rol
            $permissionsToAssign = Permission::whereIn('name', $rolePermissions)->get();
            $role->syncPermissions($permissionsToAssign);
        }
    }
}
