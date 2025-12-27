<?php

namespace App\Constants;

class UserConst
{
    const ADMIN = 1;

    const USER = 2;

    public static function getAccessTypes()
    {
        return [
            self::ADMIN => 'Admin',
            self::USER => 'User',
        ];
    }
}
