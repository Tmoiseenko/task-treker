<?php

use App\Enums\ProjectType;
use App\Models\Stage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ProjectType Default Stages Integration', function () {
    beforeEach(function () {
        // Seed stages
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\StageSeeder']);
    });

    test('all default stages for mobile app exist in database', function () {
        $defaultStages = ProjectType::MOBILE_APP->getDefaultStages();
        
        foreach ($defaultStages as $stageName) {
            $stage = Stage::where('name', $stageName)->first();
            expect($stage)->not->toBeNull()
                ->and($stage->name)->toBe($stageName);
        }
    });

    test('all default stages for telegram bot exist in database', function () {
        $defaultStages = ProjectType::TELEGRAM_BOT->getDefaultStages();
        
        foreach ($defaultStages as $stageName) {
            $stage = Stage::where('name', $stageName)->first();
            expect($stage)->not->toBeNull()
                ->and($stage->name)->toBe($stageName);
        }
    });

    test('all default stages for website exist in database', function () {
        $defaultStages = ProjectType::WEBSITE->getDefaultStages();
        
        foreach ($defaultStages as $stageName) {
            $stage = Stage::where('name', $stageName)->first();
            expect($stage)->not->toBeNull()
                ->and($stage->name)->toBe($stageName);
        }
    });

    test('all default stages for crm system exist in database', function () {
        $defaultStages = ProjectType::CRM_SYSTEM->getDefaultStages();
        
        foreach ($defaultStages as $stageName) {
            $stage = Stage::where('name', $stageName)->first();
            expect($stage)->not->toBeNull()
                ->and($stage->name)->toBe($stageName);
        }
    });

    test('mobile app has exactly 4 default stages', function () {
        $stages = ProjectType::MOBILE_APP->getDefaultStages();
        expect($stages)->toHaveCount(4);
    });

    test('telegram bot has exactly 3 default stages', function () {
        $stages = ProjectType::TELEGRAM_BOT->getDefaultStages();
        expect($stages)->toHaveCount(3);
    });

    test('website has exactly 5 default stages', function () {
        $stages = ProjectType::WEBSITE->getDefaultStages();
        expect($stages)->toHaveCount(5);
    });

    test('crm system has exactly 5 default stages', function () {
        $stages = ProjectType::CRM_SYSTEM->getDefaultStages();
        expect($stages)->toHaveCount(5);
    });
});
