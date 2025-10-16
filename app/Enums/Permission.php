<?php

namespace App\Enums;

enum Permission: int
{
    case SUPER_ADMIN = 1;      // 0001
    case ADMIN = 2;            // 0010
    case MANAGER = 4;          // 0100
    case STAFF = 8;            // 1000

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::MANAGER => 'Manager',
            self::STAFF => 'Staff',
        };
    }
}