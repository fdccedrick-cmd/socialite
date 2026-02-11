<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}
define('APP', ROOT . DS . 'src' . DS);
define('CONFIG', ROOT . DS . 'config' . DS);
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', ROOT . DS . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CORE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);

use Cake\Core\Configure;

// Helper function for environment variables
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Helper function h() for HTML escaping
if (!function_exists('h')) {
    function h($text, $double = true, $charset = null) {
        return htmlspecialchars($text, ($double ? ENT_QUOTES : ENT_COMPAT) | ENT_SUBSTITUTE, $charset ?? 'UTF-8');
    }
}

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
