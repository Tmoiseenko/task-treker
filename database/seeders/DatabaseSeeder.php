<?php

namespace Database\Seeders;

use App\Models\MoonshineUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Создаем админа по умолчанию
        MoonshineUser::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin'),
                'moonshine_user_role_id' => 1, // Admin role
                'hourly_rate' => 100.00,
            ]
        );

        // Создаем тестовых пользователей
        MoonshineUser::factory()->count(5)->create();

        // Запускаем остальные сидеры
        $this->call([
            StageSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
