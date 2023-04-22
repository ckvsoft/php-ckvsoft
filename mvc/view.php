<?php

namespace ckvsoft\mvc;

class View extends \stdClass
{

    public $mobile = false;

    /**
     * @var array $_viewQueue
     */
    private $_viewQueue = array();

    /**
     * @var string $_path
     */
    private $_path;
    private $_current;

    /**
     * __construct
     */
    public function __construct()
    {
        $user_agent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_SPECIAL_CHARS);
        if (strpos($user_agent ?? '', 'Mobile') !== false)
            $this->mobile = true;
    }

    /**
     * render - Render a template
     *
     * @param string $name The name of the page, eg: index/default
     * @param mixed $viewValues Associative Array or Object, values to pass into the view
     */
    public function render($name, $viewValues = array())
    {
        $this->_viewQueue[] = $name;

        /** Set variables to use in the view */
        foreach ($viewValues as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * setPath - Called from the Bootstrap
     *
     * @param string $path Location for the models
     */
    public function setPath($path)
    {
        $this->_path = $path;
    }

    /**
     * __destruct - Required the files when view is destroyed
     */
    public function __destruct()
    {
        foreach ($this->_viewQueue as $vc) {
            $path = $this->_path . $vc . '.php';

            if (file_exists($path)) {
                require $path;
            } else {
                // Finde den ersten Teil des Pfads
                $firstSlashPos = strpos($vc, "/");
                if ($firstSlashPos === 0) { // Wenn der erste Slash an erster Stelle ist, überspringe ihn
                    $firstPart = substr($vc, 1, strpos(substr($vc, 1), "/") + 1);
                } else {
                    $firstPart = substr($vc, 0, $firstSlashPos);
                }

                // Füge den neuen String danach ein
                $newPath = $this->_path . $firstPart . "/view/" . substr($vc, $firstSlashPos + 1) . '.php';
                require $newPath;
            }
        }
    }

}
