<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            [
                'name' => 'Дизайн',
                'description' => 'Разработка дизайна и UI/UX',
                'order' => 1,
            ],
            [
                'name' => 'Бэкенд',
                'description' => 'Разработка серверной части и API',
                'order' => 2,
            ],
            [
                'name' => 'Админка',
                'description' => 'Разработка административной панели',
                'order' => 3,
            ],
            [
                'name' => 'Фронтенд',
                'description' => 'Разработка клиентской части',
                'order' => 4,
            ],
            [
                'name' => 'Мобилка',
                'description' => 'Разработка мобильного приложения',
                'order' => 5,
            ],
            [
                'name' => 'Тестирование',
                'description' => 'Тестирование и проверка качества',
                'order' => 6,
            ],
        ];

        foreach ($stages as $stage) {
            Stage::create($stage);
        }
    }
}
