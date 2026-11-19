<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['clinic_id', 'day_of_week', 'start_time', 'end_time', 'note', 'is_active'])]
class TelemedicineSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Contoh: "13.00-15.00" */
    public function timeLabel(): string
    {
        return CarbonImmutable::createFromTimeString($this->start_time)->format('G.i')
            .'-'.CarbonImmutable::createFromTimeString($this->end_time)->format('G.i');
    }
}