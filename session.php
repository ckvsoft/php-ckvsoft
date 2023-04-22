<?php

namespace ckvsoft;

class Session {

    /**
     * start - Starts a session if one doesn't exist
     */
    public static function start() {
        if (session_id() == false) {
            session_start();
        }
    }

    /**
     * set - Sets a value inside the session
     *
     * @param mixed $str_or_array String for single values, or an Associative array
     * @param string $value The value for the key (If using an Array do not set this value)
     */
    public static function set($str_or_array, $value = null) {
        if (is_string($str_or_array) && $value != null) {
            $_SESSION[$str_or_array] = $value;
        }

        if (is_array($str_or_array)) {
            $_SESSION = $str_or_array;
        }
    }

    /**
     * fetch - Retrieves a session value
     *
     * @param magic $key The name of the session key, single value OR...
     *
     *   For multi-dimensional eg:
     *       $_SESSION['data'] = [
     *           'name' => 'jesse',
     *           'info' => [
     *               0 => 'testing'
     *               'age' => 28,
     *               'gender' => 'male',
     *               'other' => [
     *                   'more' => 500
     *               ]
     *           ]
     *       ];
     *   fetch('info', '0'); // 'testing'
     *   fetch('info', 'other', 'more'); // 500
     *
     * @return mixed The value or false
     */
    public static function fetch() {
        $arg = func_get_args();
        $total = count($arg);

        if ($total == 0) return false;

        if ($total == 1)
                return (isset($_SESSION[$arg[0]])) ? $_SESSION[$arg[0]] : false;

        if ($total == 2)
                return (isset($_SESSION[$arg[0]][$arg[1]])) ? $_SESSION[$arg[0]][$arg[1]] : false;

        if ($total == 3)
                return (isset($_SESSION[$arg[0]][$arg[1]][$arg[2]])) ? $_SESSION[$arg[0]][$arg[1]][$arg[2]] : false;
    }

    /**
     * destroy - Kill the session if one exists
     *
     * @return boolean
     */
    public static function destroy() {
        if (session_id() == true) {
            session_destroy();
            return true;
        }
        return false;
    }

    /**
     * dump - Outputs the session for the browser
     */
    public static function dump() {
        if (session_id() == true) {
            echo '<pre>';
            print_r($_SESSION);
            echo '</pre>';
        }
    }

}
