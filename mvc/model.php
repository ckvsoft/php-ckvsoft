<?php

/**
 * We handle models ourselves.
 */
class Model extends Config
{

    /**
     * Init anything we need in the models,
     * I always like a database connection
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * __call - Error Catcher
     *
     * @param string $name
     * @param string $arg
     */
    public function __call($name, $arg)
    {
        die("<div>Model Error: (Method) <b>$name</b> is not defined</div>");
    }

}
