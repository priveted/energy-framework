<?php

namespace Energy;

/** 
 * @author Eduard Y, dev@priveted.com, https://priveted.com
 * @version 1.0.1
 * @copyright Copyright (c) 2023, priveted.com
 */

class Hooks
{

    /**
     * Storage location of hooks data
     * @var array
     */

    private static $once = array();


    /**
     * Storage location of hooks data
     * @var array
     */
    private static $storage = array();


    /**
     * Add a hook once. After the first run, the hook will no longer be executed
     * @param string - Hook name (ID)
     * @param callable - Hook function. If you need to return a value, use & - the value will be written to the variable when the hook is executed/ function(&$data, $data2, ...) { ... }
     */

    public static function addOnce(string $name, callable $callback)
    {
        self::add($name, $callback);

        if (empty(self::$once[$name]))
            self::$once[$name] = array();

        self::$once[$name][] = array_key_last(self::$storage[$name]);
    }


    /**
     * Add data to the hook
     * @param string - Hook name (ID)
     * @param callable - Hook function. If you need to return a value, use & - the value will be written to the variable when the hook is executed/ function(&$data, $data2, ...) { ... }
     */

    public static function add(string $name, callable $callback)
    {
        $data = self::$storage[$name] ?? array();
        array_push($data, $callback);
        self::$storage[$name] = $data;
    }


    /**
     * Run all functions added to the current hook
     * @param string - Hook name (ID)
     * @param mixed - A set of variables or arrays in which values will be written / Hooks::apply("name", $var1, $array1, ...)
     */

    public static function apply(string $name, &...$keys)
    {

        $data = self::$storage[$name] ?? array();
        $results = array();

        if ($data) {

            foreach ($data as $key => $hook) {

                $results[$key] = $keys ? $hook(...$keys) : $hook();

                if (!empty(self::$once[$name])) {
                    if (in_array($key, self::$once[$name])) {
                        if (!empty(self::$storage[$name][$key])) {
                            unset(self::$storage[$name][$key]);
                        }
                    }
                }
            }

            if ($results) {
                foreach ($results as $key => $item) {

                    if ($item) {
                        if (is_array($item)) {
                            foreach ($item as $k => $v) {
                                $keys[$k] = $v;
                            }
                        }
                    }
                }
                return $keys;
            }
        } else
            return false;
    }


    /**
     * Clear the hook
     * @param string - Hook name (ID)
     */

    public static function delete(string $name)
    {
        if (self::is($name)) {
            unset(self::$storage[$name]);
        }
    }

    
    /**
     * Run all functions added to the current hook
     * @param string - Hook name (ID)
     */

    public static function is(string $name): bool
    {
        return isset(self::$storage[$name]);
    }
}
