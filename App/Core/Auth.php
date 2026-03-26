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

    public static function id()
    {
        if (self::$user === null || !array_key_exists('id', self::$user)) {
            return null;
        }

        return self::$user['id'];
    }

    public static function check()
    {
        return self::$user !== null;
    }
}
