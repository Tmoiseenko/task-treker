<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case IN_TESTING = 'in_testing';
    case TEST_FAILED = 'test_failed';
    case FOR_UNLOADING = 'for_unloading';
    case DONE = 'done';

    public function label(): string
    {
        return match($this) {
            self::TODO          => 'Не выполнено',
            self::IN_PROGRESS   => 'В работе',
            self::IN_TESTING    => 'На тестировании',
            self::TEST_FAILED   => 'Тест провален',
            self::FOR_UNLOADING => 'Готово к выгрузке',
            self::DONE          => 'Выполнено',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::TODO          => 'gray',
            self::IN_PROGRESS   => 'blue',
            self::IN_TESTING    => 'purple',
            self::TEST_FAILED   => 'red',
            self::FOR_UNLOADING => 'yellow',
            self::DONE          => 'green',
        };
    }

    public function canTransitionTo(TaskStatus $newStatus): bool
    {
        return match($this) {
            self::TODO          => in_array($newStatus, [self::IN_PROGRESS]),
            self::IN_PROGRESS   => in_array($newStatus, [self::IN_TESTING, self::TODO]),
            self::IN_TESTING    => in_array($newStatus, [self::TEST_FAILED, self::FOR_UNLOADING]),
            self::TEST_FAILED   => in_array($newStatus, [self::IN_PROGRESS]),
            self::FOR_UNLOADING => in_array($newStatus, [self::DONE]),
            self::DONE          => false,
        };
    }
}


