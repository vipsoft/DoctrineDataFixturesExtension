<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft;

/**
 * Generic autoloader
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class Bootstrap
{
    /**
     * Load class
     *
     * @param string $class Class name
     */
    public static function autoload($class)
    {
        $file = str_replace(array('\\', '_'), '/', $class);
        $path = __DIR__ . '/src/' . $file . '.php';

        if (file_exists($path)) {
            include_once $path;
        }
    }
}

spl_autoload_register('VIPSoft\Bootstrap::autoload');
