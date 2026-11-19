<?php

namespace App\Models;

use App\Enums\PracticeSession;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['clinic_id', 'day_of_week', 'session', 'start_time', 'end_time', 'quota', 'is_active'])]
class PracticeSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'session' => PracticeSession::class,
            'quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
