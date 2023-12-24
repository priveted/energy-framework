<?php

namespace Energy;



class Controllers
{

    private static $registeredIds = array();

    public static function launch($params = array())
    {

        $defParams = array(
            'adminRoute' => false,
            'isAdminRouteStatus' => false
        );

        $params = array_merge($defParams, $params);
        $error = false;
        $customState = false;
        $fileById = '';
        $routeList = self::getRoute();
        $route = 'index';
        $isCookie = (isset($_COOKIE) && $_COOKIE);
        $reserved = true;
        $securityCSRFId = $_POST['security_id'] ?? false;
        $request = $_POST['request'] ?? '';
        $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
        $allowRequest = (Kernel::config('config', 'allow_requests') && $isPost && $isCookie);

        if (!$isPost && !Security::isCSRFToken())
            Security::createCSRFToken();


        if (Hooks::is('Controllers::launch.pre'))
            Hooks::apply('Controllers::launch.pre', $routeList);

        if (Kernel::config('config', 'site_online') == 1 || Session::get('site_access_allowed')) {

            if (Hooks::is('Controllers::launch.isCustomState'))
                Hooks::apply('Controllers::launch.isCustomState', $customState,  $routeList);
            if ($customState) {
                if (Hooks::is('Controllers::launch.customState'))
                    Hooks::apply('Controllers::launch.customState', $customState, $routeList);
            } else {

                $allowAdmin = true;

                if ($routeList) {
                    $route = self::getRouteString();

                    if ($params['adminRoute'] && Kernel::config('config', 'allow_admin')) {
                        if (isset($routeList[0]) && $routeList[0] === $params['adminRoute']) {
                            $allowAdmin = $params['isAdminRouteStatus'];
                        }
                    }
                }

                if ($allowAdmin) {
                    $controllersPath = controllers_path();
                    $dir =  $controllersPath . '/';
                    $c = trim($route, '/');
                    $regId = false;
                    $ids = array();

                    if (in_array('requests', $routeList) || in_array('inc', $routeList))
                        $reserved = false;

                    if ($reserved) {

                        $fileById =  $controllersPath;

                        foreach ($routeList as $item) {

                            $ids[] = $item;
                            $fileById = $fileById . '/' . $item;

                            if (file_exists($fileById . '.id.php') || file_exists($fileById . '/index.id.php')) {
                                self::$registeredIds = $ids;
                                $fileById = (file_exists($fileById . '/index.id.php')) ?  $fileById . '/index.id.php' : $fileById . '.id.php';
                                $regId = true;

                                break;
                            }
                        }

                        $ids = array();
                    }

                    $csrfHeader = false;
                    $allHeaders = getallheaders();

                    if ($allHeaders && isset($allHeaders['X-Csrf-Token']))
                        $csrfHeader = $allHeaders['X-Csrf-Token'];

                    if (
                        !$isPost ||
                        (
                            ($isPost && $securityCSRFId === Security::getCSRFToken()) ||
                            ($csrfHeader != '' && $isPost && $csrfHeader  === Security::getCSRFToken())
                        )
                    ) {
                        if ($allowRequest && $request)
                            $c = $c . '/requests/' . $request;


                        $cc = is_dir($dir . $c) ? $dir . $c . '/index.php' : $dir . $c . '.php';

                        if ($regId && self::getId(false, true))
                            $cc = $fileById;

                        if (file_exists($cc) && $reserved)
                            include_once $cc;

                        else {

                            if ($allowRequest && !isset($_POST['ajax']))
                                $error = true;

                            else
                                self::page404();
                        }
                    } else {

                        $error = true;

                        Json::response([
                            'success' => false,
                            'content' => Languages::get('errors', 'cookie_or_csrf'),
                            'status' => 'error'
                        ]);
                    }

                    if (($allowRequest && $request) || $error) {
                        Json::get(Registry::get('result', 'main'), true);
                        exit;
                    }
                } else {
                    self::page404();
                }
            }
        } else
            self::siteUnavailable();

        if (Hooks::is('Controllers::launch.post'))
            Hooks::apply('Controllers::launch.post', $routeList);
    }

    public static function isReserved($name)
    {

        return in_array(
            $name,
            array(
                'admin',
                'settings',
                'components',
                'signup',
                'login',
                'account',
                'index',
                'mamnager',
                'fileManager',
                'fileServers',
                'language',
                'darkmode',
                'restore',
                'profile',
                'user',
                'common',
                'connect',
                'captcha',
                'categories',
                'welcome',
                'forum'
            )
        );
    }

    public static function page404()
    {

        $http404 = true;

        if (Hooks::is('Controllers::page404'))
            Hooks::apply('Controllers::page404', $http404);

        if ($http404)
            header('HTTP/1.0 404 Not Found');
    }


    public static function siteUnavailable()
    {

        $unavailableMessage = true;

        if (Hooks::is('Controllers::siteUnavailable'))
            Hooks::apply('Controllers::siteUnavailable', $unavailableMessage);

        if ($unavailableMessage)
            die('For technical reasons, the site has been temporarily suspended. We apologize for the inconvenience');
    }

    public static function getRoute($position = false, $string = false)
    {
        $url = empty($_REQUEST['route']) ? 'index' :  trim($_REQUEST['route'], '/');
        $routes =  explode('/', $url);
        $result = $string ? '' : array();

        if ($routes)
            $result = ($position || $position === 0) ?  ($routes[$position] ?? '') : $routes;

        if ($string && is_array($result) && $result)
            $result = implode('/', $result);

        return $result;
    }

    public static function getRouteString()
    {
        return self::getRoute(false, true);
    }

    public static function getId($id = 1, $useArray = false)
    {

        $result = $useArray ? array() : '';
        $active = self::$registeredIds;

        if ($active) {
            $active = array_values(array_diff(self::getRoute(), $active));

            if ($id) {
                if (isset($active[$id - 1])) {
                    $result = $active[$id - 1];
                    if ($useArray)
                        $result = array($result);
                }
            } else
                $result = $useArray ?  $active : implode('/', $active);
        }

        return $result;
    }
}
