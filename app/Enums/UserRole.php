<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case USER = 'user';

    /**
     * Get all role values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if role has admin privileges
     */
    public function isAdmin(): bool
    {
        return $this === self::SUPER_ADMIN || $this === self::ADMIN;
    }

    /**
     * Check if role is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }
}
