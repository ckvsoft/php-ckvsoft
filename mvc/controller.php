<?php

namespace ckvsoft\mvc;

class Controller extends \stdClass
{

    /** @var object $view Set from the bootstrap */
    public $view;

    /** @var string $pathModel Reusable path declared from the bootstrap */
    public $pathModel;
    public $pathHelper;
    public $pathRoot;
    public $pathClass;
    public $mobile = false;

    /**
     * __construct - Required
     */
    public function __construct()
    {

    }

    /**
     *
     * @param string $model
     * @return \ckvsoft\MVC\model
     */
    public function loadModel($model)
    {
        $path = $this->pathModel . $model . "/model/";
        $model = $model . '_model';

        require_once($path . $model . '.php');

        $args = func_get_args();
        array_shift($args); // das erste Argument entfernen

        if (count($args) > 0) {
            return new $model(...$args);
        } else {
            return new $model();
        }
    }

    public function loadHelper($helper, $params = array())
    {
        try {
            if (strpos($helper, '/') !== false) {
                // Wenn der Helper-Name einen Schrägstrich enthält, wird er als Modul-spezifischer Helper betrachtet
                $helper_parts = explode('/', $helper);
                $module_name = $helper_parts[0];
                $helper_file = $helper_parts[1] . '_helper.php';
                $helper_path = $this->pathHelper . $module_name . '/helper/' . $helper_file;
                $helper_name = $helper_parts[1] . '_helper';

                require_once($helper_path);

                if (isset($params['method']) && is_callable(array($helper_name, $params['method']))) {
                    return call_user_func_array(array($helper_name, $params['method']), $params['args']);
                }

                return new $helper_name();
            } else {
                // Andernfalls wird der Helper-Name als allgemeiner Helper betrachtet
                $helper_name = $helper . '_helper';

                if (isset($params['method']) && is_callable(array($helper_name, $params['method']))) {
                    return call_user_func_array(array($helper_name, $params['method']), $params['args']);
                }

                return new $helper_name();
            }
        } catch (\Exception $e) {
            throw new ckvsoft\CkvException($e->getMessage());
        }
    }

    public function getRoot()
    {
        return $this->pathRoot;
    }

    /**
     * location - Shortcut for a page redirect
     *
     * @param string $url
     */
    public function location($url)
    {
        header("location: $url");
        exit(0);
    }

    public function loadScript($script)
    {
        $fullpath = $this->pathClass . "view/" . $script;
        if (file_exists($fullpath)) {
            $script_content = file_get_contents($fullpath);
            return $script_content;
        } else {
            throw new \ckvsoft\CkvException("Script $fullpath not found!");
        }
    }

    /**
     * __call - Error Catcher
     *
     * @param string $name
     * @param string $arg
     */
    public function __call($name, $arg)
    {
        die("<div>Controller Error: (Method) <b>$name</b> is not defined</div>");
    }

}
