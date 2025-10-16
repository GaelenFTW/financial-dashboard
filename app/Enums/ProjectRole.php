<?php

namespace App\Enums;

enum ProjectRole: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    /**
     * Get all role values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if role can edit
     */
    public function canEdit(): bool
    {
        return $this === self::ADMIN || $this === self::EDITOR;
    }

    /**
     * Check if role is admin
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role can view
     */
    public function canView(): bool
    {
        return true; // All roles can view
    }
}
