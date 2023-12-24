<?php
namespace Energy;

class Cookie
{
    private static $temp = array();

    public static function set($key, $value = '', $params = array(), $unset = false)
    {

        $defParams = array(
            'time' => 15552000,  // 6 months
            'path' =>  Kernel::config('config', 'cookie_path'),
            'domain' => Kernel::config('config', 'cookie_domain'),
            'secure' => Kernel::config('config', 'cookie_secure'),
            'httponly' => Kernel::config('config', 'cookie_httponly')
        );

        $params = array_merge($defParams, $params);
        $cookie = isset($_COOKIE[$key]) ? self::decode($_COOKIE[$key]) : array();
        $cookie =  is_array($value) ? array_replace_recursive($cookie, $value) : $value;
        $cookie = is_array($value) ? Syntax::merge($cookie, $value, $unset) : $value;
        $protected = self::encode($cookie);

        return setcookie($key, $protected, time() + $params['time'], $params['path'], $params['domain'], $params['secure'], $params['httponly']); //bool
    }

    public static function get(...$key) // ('one', 'twoo', ...) or (['one', 'twoo', ...])
    {
        $result = '';
        if (isset($key[0])) {
            $keys = (is_array($key[0])) ? $key[0] : $key;
            if (isset($_COOKIE[$keys[0]])) {
                if (count($keys) > 2) {
                    self::$temp = self::decode($_COOKIE[$keys[0]]);
                    unset($keys[0]);
                    foreach ($keys as $v) {
                        if (isset(self::$temp[$v])) {
                            self::$temp = self::$temp[$v];
                        } else {
                            self::$temp = '';
                            break;
                        }
                    }
                    $result = self::$temp;
                } else {
                    if (isset($keys[1])) {
                        $result = (isset(self::decode($_COOKIE[$keys[0]])[$keys[1]])) ? self::decode($_COOKIE[$keys[0]])[$keys[1]] : '';
                    } else {
                        $result = self::decode($_COOKIE[$keys[0]]);
                    }
                }
            }
        }
        return $result;
    }

    public static function delete($key)
    {
        if (isset($_COOKIE[$key])) {
            setcookie($key, '', time() - 3600);
            unset($_COOKIE[$key]);
        }
    }

    private static function encode($ar)
    {
        return base64_encode(gzcompress(serialize(base64_encode(json_encode($ar))), 9));
    }

    private static function decode($ar)
    {
        return json_decode(base64_decode(@unserialize(gzuncompress(base64_decode(stripslashes($ar))))), true);
    }
}
