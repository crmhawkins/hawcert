<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Usuario admin por defecto (demo)
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );

        // Usuario de administración principal
        User::firstOrCreate(
            ['email' => 'hawadmin@example.com'],
            [
                'name' => 'hawadmin',
                'password' => bcrypt('xTNsxKKK15cE'),
            ]
        );

        $this->call([
            InitialDataSeeder::class,
        ]);
    }
}
