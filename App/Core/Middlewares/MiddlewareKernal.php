<?php

namespace App\Core\Middlewares;
class MiddlewareKernal
{
    protected $middlewares = [];

    // public function __construct()
    // {
    //     echo "Middlewares object created at: " . spl_object_id($this) . PHP_EOL;
    // }
    public function setMiddleware($name, $value)
    {
        $this->middlewares[$name] = $value;
        return $this;
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

    public function get()
    {
        dd($this->middlewares);
    }
}