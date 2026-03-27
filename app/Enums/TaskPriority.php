<?php

namespace App\Enums;

enum TaskPriority: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case FROZEN = 'frozen';
}
