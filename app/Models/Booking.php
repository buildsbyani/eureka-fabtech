<?php

namespace App\Models;

use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'uid', 'room_id', 'date', 'start_min', 'end_min', 'title', 'organizer_id', 'booked_by_id',
        'invitees', 'status', 'series_id', 'sequence', 'checked_in_at', 'reminder_sent_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'invitees' => 'array',
            'checked_in_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    /** Bookings in a room that overlap [start, end) on a date. */
    public function scopeOverlapping(Builder $query, int $roomId, string $date, int $start, int $end): Builder
    {
        return $query->active()
            ->where('room_id', $roomId)
            ->whereDate('date', $date)
            ->where('start_min', '<', $end)
            ->where('end_min', '>', $start);
    }

    public function scopeInvolving(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->where('organizer_id', $user->id)->orWhere('booked_by_id', $user->id));
    }

    public function dateString(): string
    {
        return $this->date->format('Y-m-d');
    }

    public function startsAt(): CarbonImmutable
    {
        return Clock::at($this->dateString(), $this->start_min);
    }

    public function endsAt(): CarbonImmutable
    {
        return Clock::at($this->dateString(), $this->end_min);
    }

    public function timeRange(): string
    {
        return Clock::range($this->start_min, $this->end_min);
    }

    public function dayLabel(): string
    {
        return Clock::dayLabel($this->dateString());
    }

    public function isRecurring(): bool
    {
        return $this->series_id !== null;
    }

    public function isPast(): bool
    {
        return $this->endsAt()->lessThanOrEqualTo(Clock::now());
    }

    /** True from 15 minutes before the start until the auto-release deadline. */
    public function canCheckIn(): bool
    {
        if ($this->status !== 'active' || $this->checked_in_at) {
            return false;
        }
        $now = Clock::now();

        return $now->greaterThanOrEqualTo($this->startsAt()->subMinutes(15))
            && $now->lessThanOrEqualTo($this->startsAt()->addMinutes(config('eureka.auto_release_minutes')));
    }

    public function invitees(): array
    {
        return array_values(array_filter($this->invitees ?? []));
    }
}
