<?php

namespace App\Core;

class Container
{
    protected $services = [];

    public function setService($name, $factory)
    {
        $this->services[$name] = function () use ($factory) {
            static $instance = null;
            if ($instance === null) {
                $instance = $factory();
            }
            return $instance;
        };
    }

    public function getService($name)
    {
        if (array_key_exists($name, $this->services)) {
            return $this->services[$name]();
        } else {
            error_log("$name service was not found, please check the name and try again");
            return "No $name service was found";
        }
    }
}