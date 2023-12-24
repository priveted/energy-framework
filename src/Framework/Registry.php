<?php

namespace Energy;

class Registry
{
    private static $cache = array();
    private static $temp = array();

    public static function set($key, $value = '', $unset = false, $push = false,  $position = 0)
    {
        if (!isset(self::$cache[$key]))
            self::$cache[$key] = array();

        if (is_array($value))
            self::$cache[$key] = Syntax::merge(self::$cache[$key], $value, $unset, $push, $position);

        else {
            if ($push)
                self::$cache[$key][] = $value;
            else
                self::$cache[$key] = $value;
        }
    }

    public static function get(...$key) // ('one', 'twoo', ...) or (['one', 'twoo', ...])
    {
        $result = '';

        if (isset($key[0])) {
            $keys = (is_array($key[0])) ? $key[0] : $key;
            if (isset(self::$cache[$keys[0]])) {
                if (count($keys) > 2) {
                    self::$temp = self::$cache[$keys[0]];
                    unset($keys[0]);
                    foreach ($keys as $v) {
                        if (isset(self::$temp[$v])) {
                            self::$temp = self::$temp[$v];
                        } else {
                            self::$temp = '';
                            break;
                        }
                    }
                    $result =  self::$temp;
                } else {
                    if (isset($keys[1])) {
                        $result = self::$cache[$keys[0]][$keys[1]] ?? '';
                    } else {
                        $result = self::$cache[$keys[0]];
                    }
                }
            }
        }
        
        return $result;
    }

    public static function delete(...$key)
    {
        if (isset($key[0])) {
            $keys = (is_array($key[0])) ? $key[0] : $key;
            if (isset(self::$cache[$keys[0]])) {
                if (count($keys) > 2) {
                    if (self::get($keys)) {
                        $first = $keys[0];
                        unset($keys[0]);
                        $keys = array_values($keys);
                        $map = Syntax::getTreeMap($keys);
                        self::set($first, $map, true);
                    }
                } else {
                    if (isset($keys[1])) {
                        if (isset(self::$cache[$keys[0]][$keys[1]]))
                            unset(self::$cache[$keys[0]][$keys[1]]);
                    } else {
                        unset(self::$cache[$keys[0]]);
                    }
                }
            }
        }
    }
}
