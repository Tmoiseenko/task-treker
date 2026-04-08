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

    public function canTransitionTo(TaskStatus $newStatus): bool
    {
        return match($this) {
            self::TODO => in_array($newStatus, [self::IN_PROGRESS]),
            self::IN_PROGRESS => in_array($newStatus, [self::IN_TESTING, self::TODO]),
            self::IN_TESTING => in_array($newStatus, [self::TEST_FAILED, self::FOR_UNLOADING]),
            self::TEST_FAILED => in_array($newStatus, [self::IN_PROGRESS]),
            self::FOR_UNLOADING => in_array($newStatus, [self::DONE]),
            self::DONE => false,
        };
    }
}
