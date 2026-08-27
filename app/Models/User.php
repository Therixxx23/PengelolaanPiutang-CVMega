<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function isAdministrasi(): bool
    {
        return $this->role === 'bagian_administrasi';
    }

    public function isKeuangan(): bool
    {
        return $this->role === 'bagian_keuangan';
    }

    public function isPimpinan(): bool
    {
        return $this->role === 'pimpinan';
    }

    public function isSales(): bool
    {
        return $this->role === 'sales';
    }

    public function canViewReports(): bool
    {
        return in_array($this->role, ['bagian_keuangan', 'pimpinan']);
    }

    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function assignedTagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'assigned_sales_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Bagian Administrasi',
            'bagian_administrasi' => 'Bagian Administrasi',
            'bagian_keuangan' => 'Bagian Keuangan',
            'pimpinan' => 'Pimpinan',
            'sales' => 'Sales / Penagih',
            default => $this->role,
        };
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'is_active' => 'boolean',
        ];
    }
}
