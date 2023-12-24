<?php

namespace Energy;

class Languages
{

    const TYPE_TITLE = 0;
    const TYPE_CONTENT = 1;
    const TYPE_DESCRIPTION = 2;
    const TYPE_KEYWORDS = 3;

    public static function get(...$key)
    {
        $result = '';

        if (isset($key[0])) {

            $keys = (is_array($key[0])) ? $key[0] : $key;
            $data = '';
            array_unshift($keys, 'Store.Language.Values');

            if (isset($keys[2])) {

                if (!View::getAssigned('Store.Language.Values', $keys[1], $keys[2])) {

                    $langCode = self::getSelectedCode();
                    $path = $langCode . '/' . $keys[1]  . '/' .  $keys[2];

                    if (self::isExists($path)) {
                        $data =  array(
                            $keys[2] => self::load($path)
                        );
                    } else {
                        $path = $langCode . '/' . $keys[1];
                        $data = self::load($path);
                    }


                    View::assign(
                        'Store.Language.Values',
                        [
                            $keys[1] => $data
                        ]
                    );
                }
            }

            $result = View::getAssigned($keys);
        }
        return $result;
    }

    public static function getSystem()
    {
        return (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? mb_strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)) : Kernel::config('config', 'default_language');
    }

    public static function getSelected($key = '')
    {
        $selected = Registry::get('languages', 'enabledList', self::getSelectedCode());
        if ($selected != '' && is_array($selected)) {
            return ($key == '') ? $selected : $selected[$key];
        } else
            return false;
    }

    public static function getSelectedCode()
    {
        if (Kernel::config('config', 'allow_user_language_switch')) {
            $cookie = Cookie::get('language');
            $result = ($cookie) ? $cookie : self::getSystem();
        } else {
            $result = Kernel::config('config', 'default_language');
        }
        return $result;
    }

    public static function getPublicList()
    {
        return Registry::get('languages', 'enabledList');
    }

    public static function getList()
    {
        $config = Kernel::config('languages');
        return ($config && is_array($config)) ? $config : array();
    }

    public static function load($file, $isExists = false)
    {
        $f = resources_path('languages/' . $file);

        if (is_dir($f))
            $f = $f . '/index';

        return  $isExists ? Json::isExists($f) : Json::load($f);
    }

    public static function isExists($file)
    {
        return self::load($file, true);
    }

    public static function select($code)
    {
        if ($code)
            Cookie::set('language', $code);
    }

    public static function getByCode($code, $key = '')
    {
        return $key ?  Kernel::config('languages', $code, $key) : Kernel::config('languages', $code);
    }

    public static function getById($id, $key = '')
    {
        return $key ?  Registry::get('languages', 'fullListIds', $id, $key) : Registry::get('languages', 'fullListIds', $id);
    }

    public static function deleteDb($whereParams = array())
    {
        $result = false;

        $allowed = [
            'id',
            'key',
            'type',
            'component_id',
            'lang_id',
        ];

        $whereParams = Security::allowedData($whereParams, $allowed);

        if ($whereParams) {

            Db::delete('language_values', $whereParams);

            $result = false;
        }

        return $result;
    }

    public static function setDb($fileds = array())
    {

        $result = false;

        $allowed = [
            'id',
            'value',
            'key',
            'type',
            'component_id',
            'lang_id',
        ];

        $fileds = Security::allowedData($fileds, $allowed);

        if (isset($fileds['key']) && isset($fileds['value']) && isset($fileds['component_id'])) {

            $defFields = array(
                'lang_id' => self::getSelected('id'),
                'type' => 0
            );

            $fileds = array_merge($defFields, $fileds);

            $tmp = $fileds;
            unset($tmp['value']);

            $one = Db::get('language_values', '*', $tmp);

            if ($one && isset($one['id'])) {

                Db::update('language_values', [
                    'value' => $fileds['value']
                ], [
                    'id' => $one['id']
                ]);
            } else {

                Db::insert('language_values', [
                    'value' => $fileds['value'],
                    'key' => $fileds['key'],
                    'component_id' => $fileds['component_id'],
                    'type' => $fileds['type'],
                    'lang_id' => $fileds['lang_id'],
                ]);
            }

            $result = true;
        }

        return $result;
    }

    public static function getDb($fileds = array(), $params = array())
    {
        $result = array();

        $allowed = [
            'id',
            'value',
            'key',
            'type',
            'component_id',
            'lang_id',
        ];

        $fileds = Security::allowedData($fileds, $allowed);

        if ($fileds && isset($fileds['key'])) {

            $defParams = array(
                'select' => '*',
            );

            $params = array_merge($defParams, $params);

            $defFields = array(
                'lang_id' => self::getSelected('id')
            );

            $fileds = array_merge($defFields, $fileds);
            $result = Db::select('language_values', $params['select'], $fileds);
        }

        return $result;
    }
}
