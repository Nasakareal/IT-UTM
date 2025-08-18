<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class EnsurePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Asegura que Spatie no use la caché vieja
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 🔒 Lista “oficial” de permisos
        $permissions = [
            // Configuración / administración
            'ver configuraciones',

            // Usuarios
            'ver usuarios','crear usuarios','editar usuarios','eliminar usuarios',

            // Roles
            'ver roles','crear roles','editar roles','eliminar roles',

            // Secciones
            'ver secciones','crear secciones','editar secciones','eliminar secciones',

            // Módulos
            'ver modulos','crear modulos','editar modulos','eliminar modulos',

            // Carpetas
            'ver carpetas','crear carpetas','editar carpetas','eliminar carpetas','subir archivos',

            // Subsecciones
            'ver subsecciones','crear subsecciones','editar subsecciones','eliminar subsecciones',

            // Comunicados
            'ver comunicados','crear comunicados','editar comunicados','eliminar comunicados',

            // Submódulos
            'ver submodulos','crear submodulos','editar submodulos','eliminar submodulos',

            // Correspondencias
            'ver correspondencias','crear correspondencias','editar correspondencias','eliminar correspondencias',

            // Archivos (gestor)
            'ver archivos','crear archivos','editar archivos','eliminar archivos',

            // Revisiones / Gestión académica
            'ver revisiones','crear revisiones','editar revisiones','eliminar revisiones',
            'calificar documentos',
            'subir documentos',

            // Documentos por profesor
            'ver documentos profesores',
            'eliminar documentos profesores',

            // Tutoriales
            'ver tutoriales','crear tutoriales','editar tutoriales','eliminar tutoriales',

            // Actividades (settings/actividad)
            'ver actividades','crear actividades','editar actividades','eliminar actividades',

            // Requisiciones (porque tus roles las mencionan)
            'ver requisiciones',
        ];

        // Crea cada permiso si no existe (idempotente)
        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }
    }
}
