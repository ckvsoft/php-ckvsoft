<?php

/**
 * Description of csshelper
 *
 * @author chris
 *
 * Example call from Controller
 * $params = [
 *           'method' => 'getCss',
 *           'args' => ['inc/css/mbv.css']
 *       ];
 * $css = $this->loadHelper("css", $params);
 */
class Css_Helper
{

    public static function getCss($css)
    {
        $trace = debug_backtrace();
        $path = getcwd() . '/' . MODULES_URI . basename($trace[2]['file'], '.php') . "/view/" . $css;
        $style = file_get_contents($path);
        $style = preg_replace('/\s+/', ' ', $style);
        $style = preg_replace('/\/\*[\s\S]*?\*\//', '', $style);
        return str_replace(array("\r", "\n"), '', $style);
    }

}
