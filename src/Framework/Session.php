<?php
namespace Energy;

class Session
{
    private static $temp = array();

    public static function set($key, $value = "", $unset = false)
    {
        if (!isset($_SESSION["session"]))
            $_SESSION["session"] = array();

        if (!isset($_SESSION["session"][$key]))
            $_SESSION["session"][$key] = array();

        if (is_array($value))
            $_SESSION["session"][$key] = Syntax::merge($_SESSION["session"][$key], $value, $unset);
        else {
            $_SESSION["session"][$key] = $value;
        }
    }

    public static function get(...$key) // ("one", "twoo", ...) or (["one", "twoo", ...])
    {
        $result = "";
        if (isset($key[0])) {
            $keys = (is_array($key[0])) ? $key[0] : $key;
            if (isset($_SESSION["session"][$keys[0]])) {
                if (count($keys) > 2) {
                    self::$temp = $_SESSION["session"][$keys[0]];
                    unset($keys[0]);
                    foreach ($keys as $v) {
                        if (isset(self::$temp[$v])) {
                            self::$temp = self::$temp[$v];
                        } else {
                            self::$temp = "";
                            break;
                        }
                    }
                    $result = self::$temp;
                } else {
                    if (isset($keys[1])) {
                        $result = (isset($_SESSION["session"][$keys[0]][$keys[1]])) ? $_SESSION["session"][$keys[0]][$keys[1]] : "";
                    } else {
                        $result = $_SESSION["session"][$keys[0]];
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
            if (isset($_SESSION["session"][$keys[0]])) {
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
                        if (isset($_SESSION["session"][$keys[0]][$keys[1]]))
                            unset($_SESSION["session"][$keys[0]][$keys[1]]);
                    } else {
                        unset($_SESSION["session"][$keys[0]]);
                    }
                }
            }
        }
    }
}
