<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN     = 'admin';
    public const ROLE_GUDANG    = 'staff_gudang';
    public const ROLE_PENJUALAN = 'staff_penjualan';
    public const ROLE_PEMBELIAN = 'staff_pembelian';

    public const ROLES = [
        self::ROLE_ADMIN     => 'Admin',
        self::ROLE_GUDANG    => 'Staff Gudang',
        self::ROLE_PENJUALAN => 'Staff Penjualan',
        self::ROLE_PEMBELIAN => 'Staff Pembelian',
    ];

    // role & is_active sengaja TIDAK fillable (anti mass assignment)
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_login_at'     => 'datetime',
        ];
    }

    /** Admin selalu dianggap punya semua role. */
    public function hasRole(string ...$roles): bool
    {
        return $this->role === self::ROLE_ADMIN
            || in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? (string) $this->role;
    }
}