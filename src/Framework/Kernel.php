<?php

namespace Energy;

class Kernel
{

    public static function launch()
    {
        Hooks::apply('Kernel::launch');

        View::setConfig([
            'showSiteName' => self::config("config", "is_title_site_name"),
            'siteName' => Languages::get('page', 'main', 'site_name'),
            'separator' => self::config("config", "type_separator"),
            'routeType' => Account::isAdminRoute() ? 'admin' : 'public'
        ]);

        View::setPath([resources_path('views')]);

        if (self::config('config', 'cache_autoclear') || env('APP_DEBUG'))
            Cache::clearAll();

        self::languages();

        if ((preg_match('/MSIE\s(?P<v>\d+)/i', @$_SERVER['HTTP_USER_AGENT'], $B) && $B['v'] <= 11))
            View::page([
                'name' => 'page/errors/browser',
                'title' => Languages::get('page', 'errors/browser', 'title'),
                'document' => 'page/errors/document',
                'controller' => false
            ]);
        else {

            Hooks::add('Controllers::page404', function (&$http404) {
                $http404 = false;
                View::errorPage();
            });

            Hooks::add('Controllers::siteUnavailable', function (&$unavailableMessage) {
                $unavailableMessage = false;

                View::page([
                    'name' => 'page/errors/site-unavailable',
                    'title' => Languages::get('page', 'errors/site-unavailable', 'title'),
                    'document' => 'page/errors/document',
                    'controller' => false
                ]);
            });

            Account::remember();

            self::includeAll(
                app_path('__autoload')
            );


            if (Account::authorized()) {

                if (Account::get('banned') == 1)
                    Account::logout();
            }

            if (!self::config('config', 'site_online')) {
                $aa = $_GET['allow_access'] ?? false;
                if ($aa && $aa === self::config('config', 'site_access_key')) {
                    Session::set('site_access_allowed', 1);
                }
            }

            if (Account::authorized() && Account::get('deleted') && Controllers::getRouteString() !== 'deleted')
                Url::redirect('/deleted');

            Cache::compilation();
            Controllers::launch([
                'adminRoute' => 'admin',
                'isAdminRouteStatus' => Account::isAdminRoute()
            ]);
        }

        Hooks::apply('Kernel::launch.destructor');
    }

    public static function includeAll($directory)
    {
        $directory = rtrim($directory, '/') . '/';

        if (is_dir($directory)) {

            $scan = array_diff(scandir($directory), array('.', '..'));

            foreach ($scan as $file) {

                if (is_dir($directory . "/" . $file)) {
                    if (strpos($file, 'admin') !== false) {
                        if (Account::isAdminRoute())
                            self::includeAll($directory . "/" . $file);
                    } elseif (strpos($file, 'public') !== false) {
                        if (!Account::isAdminRoute())
                            self::includeAll($directory . "/" . $file);
                    } else
                        self::includeAll($directory . "/" . $file);
                } else {
                    if (strpos($file, '.php') !== false)
                        include_once($directory . "/" . $file);
                }
            }
        }
    }

    public static function config(string $path = '', ...$key)
    {
        $result = Registry::get('configuration', $path);

        if ($result) {
            if ($key)
                $result = Registry::get('configuration', $path, ...$key);
        } else {
            $cfgDir = config_path() . '/';
            if (is_dir($cfgDir . $path)) {
                $scan = array_diff(scandir($cfgDir . $path), array('.', '..'));
                $arr = array();

                foreach ($scan as $file) {
                    include $cfgDir . $path . '/' . $file;

                    if (isset($config))
                        $arr[basename($file, '.php')] = $config;
                }

                if ($arr)
                    Registry::set('configuration', [$path =>  $arr]);

                $result = $key ? Registry::get('configuration', $path, ...$key) : Registry::get('configuration', $path);
            } else {

                $f = $cfgDir . $path . '.php';

                if (file_exists($f)) {

                    include $f;

                    if (isset($config)) {

                        Registry::set('configuration', [$path => $config]);
                        $result = $key ? Registry::get('configuration', $path, ...$key) : Registry::get('configuration', $path);
                    }
                }
            }
        }

        return $result;
    }

    private static function languages()
    {

        $list = self::config('languages');
        $enabledList = array();
        $fullListIds = array();

        if ($list) {
            foreach ($list as $item) {

                $fullListIds[$item['id']] = $item;

                if ($item['status'])
                    $enabledList[$item['code']] = $item;
            }
            Registry::set('languages', ['enabledList' => $enabledList]);
            Registry::set('languages', ['fullListIds' => $fullListIds]);
        }
    }
}
