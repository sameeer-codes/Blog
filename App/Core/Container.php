<?php

namespace App\Core;

class Container
{
    protected $services = [];
    protected $instances = [];

    public function setService($name, $factory)
    {
        $this->services[$name] = $factory;
    }

    public function getService($name)
    {
        if (array_key_exists($name, $this->services)) {
            if (isset($this->instances[ $name])) {
                return $this->instances[$name];
            }
            // Create, cache, and return instance
            $this->instances[$name] = ($this->services[$name])();
            return $this->instances[$name];

        } else {
            error_log("$name service was not found, please check the name and try again");
            sendResponse("error", 500, "Internal Server Error");
        }
    }
}