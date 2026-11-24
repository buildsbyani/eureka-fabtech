<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'emp_id', 'email', 'department', 'role', 'senior', 'active',
        'must_change_password', 'password', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'senior' => 'boolean',
            'active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'organizer_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isReception(): bool
    {
        return $this->role === 'reception';
    }

    /** Reception and admins can book for anyone and see the whole office. */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isReception();
    }

    public function canUseSeniorRooms(): bool
    {
        return $this->senior || $this->isStaff();
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0];
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }

    public function roleLabel(): string
    {
        return ucfirst($this->role).($this->senior ? ' · Senior' : '');
    }
}
