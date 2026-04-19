<?php

namespace App\Enums;

enum TaskPriority: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case FROZEN = 'frozen';

    public function label(): string
    {
        return match($this) {
            self::HIGH   => 'Высокий',
            self::MEDIUM => 'Средний',
            self::LOW    => 'Низкий',
            self::FROZEN => 'Заморожен',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::HIGH   => 'red',
            self::MEDIUM => 'yellow',
            self::LOW    => 'green',
            self::FROZEN => 'blue',
        };
    }
}
