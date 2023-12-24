<?php

namespace Energy;

use Exception;

class Json
{

    public static function response($ar = array())
    {
        $data = Registry::get('result', 'main');
        if ($data)
            $ar  = array_merge($data, $ar);
        Registry::set('result', ['main' => self::get($ar)]);
    }

    public static function get($arr = [], $isEcho = false)
    {
        try {
            header('Content-Type: application/json');

            $json = ($arr) ? $arr : array(
                'success' => false,
                'error_detected' => true,
                'error_message' => 'The value of the Json::get result is not set',
                'content' => Languages::get('errors', 'request404'),
                'status' => 'error'
            );

            $json = json_encode($json);

            if ($isEcho)
                echo $json;
            else
                return $arr;
        } catch (Exception $e) {
            if (env('APP_DEBUG'))
                throw new \Exception('An error has been detected | ' .  $e->getMessage());
        }
        return '';
    }

    public static function isExists($file)
    {
        $file = $file . '.json';
        return (file_exists($file));
    }

    public static function load($file)
    {
        $result = '';

        try {
            if (self::isExists($file)) {
                $file = $file . '.json';
                $result = json_decode(file_get_contents($file), true);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG'))
                throw new \Exception('Failed to process data: ' . $file . ' | ' .  $e->getMessage());
        }

        return $result;
    }

    public static function isJson($string = '')
    {
        $result = false;
        if ($string) {
            json_decode($string);
            $result = (json_last_error() === JSON_ERROR_NONE);
        }

        return $result;
    }
}
