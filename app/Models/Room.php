<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['name', 'capacity', 'senior_only', 'equipment', 'active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'senior_only' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeListed(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function equipmentLine(): string
    {
        return $this->capacity.' seats'.($this->equipment ? ' · '.$this->equipment : '');
    }
}
