<?php

use App\Enums\ProjectType;

describe('ProjectType Enum', function () {
    test('mobile app returns correct default stages', function () {
        $stages = ProjectType::MOBILE_APP->getDefaultStages();
        
        expect($stages)->toBe(['Дизайн', 'Бэкенд', 'Мобилка', 'Тестирование']);
    });

    test('telegram bot returns correct default stages', function () {
        $stages = ProjectType::TELEGRAM_BOT->getDefaultStages();
        
        expect($stages)->toBe(['Дизайн', 'Бэкенд', 'Тестирование']);
    });

    test('website returns correct default stages', function () {
        $stages = ProjectType::WEBSITE->getDefaultStages();
        
        expect($stages)->toBe(['Дизайн', 'Бэкенд', 'Админка', 'Фронтенд', 'Тестирование']);
    });

    test('crm system returns correct default stages', function () {
        $stages = ProjectType::CRM_SYSTEM->getDefaultStages();
        
        expect($stages)->toBe(['Дизайн', 'Бэкенд', 'Админка', 'Фронтенд', 'Тестирование']);
    });

    test('all project types return non-empty stage arrays', function () {
        foreach (ProjectType::cases() as $projectType) {
            $stages = $projectType->getDefaultStages();
            
            expect($stages)
                ->toBeArray()
                ->not->toBeEmpty();
        }
    });

    test('all default stages are strings', function () {
        foreach (ProjectType::cases() as $projectType) {
            $stages = $projectType->getDefaultStages();
            
            foreach ($stages as $stage) {
                expect($stage)->toBeString();
            }
        }
    });
});
