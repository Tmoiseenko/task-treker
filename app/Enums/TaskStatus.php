<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case IN_TESTING = 'in_testing';
    case TEST_FAILED = 'test_failed';
    case DONE = 'done';

    public function canTransitionTo(TaskStatus $newStatus): bool
    {
        return match($this) {
            self::TODO => in_array($newStatus, [self::IN_PROGRESS]),
            self::IN_PROGRESS => in_array($newStatus, [self::IN_TESTING, self::TODO]),
            self::IN_TESTING => in_array($newStatus, [self::TEST_FAILED, self::DONE]),
            self::TEST_FAILED => in_array($newStatus, [self::IN_PROGRESS]),
            self::DONE => false,
        };
    }
}
