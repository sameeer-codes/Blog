<?php

namespace App\Core;
class Middleware
{
    protected $middlewares = [];
    public function setMiddleware($name, $value)
    {
        $this->middlewares[$name] = $value;
    }

    public function handle($name)
    {
        if (array_key_exists($name, $this->middlewares)) {
            call_user_func($this->middlewares[$name]);
        } else {
            error_log("No Middleware found for $name");
            sendResponse("error", 500, "Internal Server Error");
        }
    }
}