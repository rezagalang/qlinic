<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'specialty', 'address', 'whatsapp', 'whatsapp_label', 'maps_url', 'map_embed_url',
    'footer_text', 'registration_hours_before', 'booking_days_ahead',
])]
class Clinic extends Model
{
    protected function casts(): array
    {
        return [
            'registration_hours_before' => 'integer',
            'booking_days_ahead' => 'integer',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function practiceSchedules(): HasMany
    {
        return $this->hasMany(PracticeSchedule::class);
    }

    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}
