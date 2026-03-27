<?php

namespace App\Enums;

enum ProjectType: string
{
    case MOBILE_APP = 'mobile_app';
    case TELEGRAM_BOT = 'telegram_bot';
    case WEBSITE = 'website';
    case CRM_SYSTEM = 'crm_system';

    public function getDefaultStages(): array
    {
        return match($this) {
            self::MOBILE_APP => ['Дизайн', 'Бэкенд', 'Мобилка', 'Тестирование'],
            self::TELEGRAM_BOT => ['Дизайн', 'Бэкенд', 'Тестирование'],
            self::WEBSITE => ['Дизайн', 'Бэкенд', 'Админка', 'Фронтенд', 'Тестирование'],
            self::CRM_SYSTEM => ['Дизайн', 'Бэкенд', 'Админка', 'Фронтенд', 'Тестирование'],
        };
    }
}
