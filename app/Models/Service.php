<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['clinic_id', 'name', 'slug', 'icon', 'description', 'is_active', 'sort_order'])]
class Service extends Model
{
    /** Pilihan ikon yang tersedia (lihat components/service-icon). */
    public const ICONS = ['stethoscope' => 'Stetoskop', 'heart' => 'Hati', 'syringe' => 'Suntik', 'plus' => 'Umum'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}