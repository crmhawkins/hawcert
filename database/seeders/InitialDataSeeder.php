<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Service;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permisos básicos
        $permissions = [
            ['name' => 'Lectura', 'slug' => 'read', 'description' => 'Permiso para leer datos'],
            ['name' => 'Escritura', 'slug' => 'write', 'description' => 'Permiso para escribir datos'],
            ['name' => 'Eliminación', 'slug' => 'delete', 'description' => 'Permiso para eliminar datos'],
            ['name' => 'Administración', 'slug' => 'admin', 'description' => 'Permiso de administrador completo'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Crear servicios básicos
        $services = [
            ['name' => 'API Principal', 'slug' => 'api', 'description' => 'API principal del sistema', 'endpoint' => 'https://api.example.com'],
            ['name' => 'Panel de Control', 'slug' => 'dashboard', 'description' => 'Panel de control administrativo', 'endpoint' => 'https://dashboard.example.com'],
            ['name' => 'Reportes', 'slug' => 'reports', 'description' => 'Sistema de reportes', 'endpoint' => 'https://reports.example.com'],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['slug' => $service['slug']],
                array_merge($service, ['is_active' => true])
            );
        }
    }
}
