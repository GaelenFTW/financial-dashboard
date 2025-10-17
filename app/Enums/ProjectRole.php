<?php

namespace App\Enums;

enum ProjectRole: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canEdit(): bool
    {
        return $this === self::ADMIN || $this === self::EDITOR;
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function canView(): bool
    {
        return true; // All roles can view
    }
}
