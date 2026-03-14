<?php

namespace App\Core;
class Auth
{
    private static $user = null;

    public static function setUser($user)
    {
        self::$user = $user;
    }

    public static function user()
    {
        return self::$user;
    }

    public static function check()
    {
        return self::$user !== null;
    }
}