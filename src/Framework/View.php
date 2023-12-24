<?php

namespace Energy;

class View
{
    private static $path = array();
    private static $props = array();

    private static $page = array(
        'title' => '',
        'description' => '',
        'keywords' => '',
        'content' => '',
        'header' => true,
        'footer' => true,
    );

    private static $config = array(
        'showSiteName' => true,
        'siteName' => '',
        'separator' => '-',
        'description' => '',
        'keywords' => '',
        'controllersDirectory' => 'controllers',
        'routeType' => 'public'
    );

    private static function error($t)
    {
        $result = '<div style="padding:15px; border:2px solid red;background: rgba(255, 102, 102, .2); border-radius:12px;">';
        $result .= "<h2>#View Debugger</h2><p style='color:red'>This file was not found: <span style='color:darkcyan'>" . $t . "</span></p>";
        $result .= "<b>A scan was performed</b><br>";
        $result .= implode('<br>', self::$path);
        $result .= '</div>';
        die($result);
    }

    public static function setPath($path = array())
    {
        if ($path) {
            foreach ($path as $item) {
                if (!in_array($item, self::$path)) {
                    array_push(self::$path, rtrim($item, '/') . '/');
                }
            }
        }
    }

    public static function setConfig(array $params = array())
    {
        self::$config = array_merge(self::$config, $params);
    }

    public static function getRootPath(): string
    {
        return self::$path[0] ?? '';
    }

    public static function getCurrent($key = ''): mixed
    {
        $current = '';
        if (!empty($key)) {
            if (!empty(self::prop('__VIEW__')[$key]))
                $current = self::prop('__VIEW__')[$key];
        } else
            $current = self::prop('__VIEW__');

        return $current;
    }

    private static function findPath(string $file, bool $isViewPath = false): mixed
    {

        $file = trim($file, '/');
        $fullPath = '';
        $viewPath = '';

        if (self::$path) {
            foreach (self::$path as $item) {

                if (is_dir($item . $file)) {
                    if (file_exists($item . $file . '/index.php')) {
                        $fullPath = $item . $file . '/index.php';
                        $viewPath = $file . '/index.php';
                        break;
                    }
                } else if (file_exists($item . $file . '.php')) {
                    $fullPath = $item . $file . '.php';
                    $viewPath = $file . '.php';
                    break;
                }
            }
        }

        return $isViewPath ? array(
            'fullPath' => $fullPath,
            'path' => $viewPath
        ) : $fullPath;
    }

    public static function isExists($file)
    {
        return !!self::findPath($file);
    }

    private static function include($fileName, $props = array())
    {

        $type = explode(':', $fileName);

        $isExt = isset($type[1]);
        $fileName = ($type[0] ?? '');

        if ($isExt) {
            if ($type[0] === 'current')
                $fileName = self::getCurrent('dir') . '/' . $type[1];
        }

        $path = self::findPath($fileName, true);
        $viewData = pathinfo($path['path']);
        $file = $path['fullPath'];

        if ($file == '' || !file_exists($file)) {
            self::error($fileName);
        } else {

            $props = array_merge($props, array(
                '__VIEW__' =>  array(
                    'dir' =>  $viewData['dirname'] ?? '',
                    'fileName' =>  $viewData['filename'] ?? '',
                    'baseName' =>  $viewData['basename'] ?? '',
                    'path' => $file
                )
            ));

            array_push(self::$props, $props);
            ob_start();
            include($file);
            $file = ob_get_contents();
            ob_end_clean();
            array_pop(self::$props);
            return $file;
        }
    }


    public static function prop($name = false, $default = '')
    {
        $result = $default;
        if ($name) {
            if (self::$props) {
                $end = end(self::$props);
                $result =  isset($end[$name]) ? $end[$name] : $default;
            }
        } else {
            if (self::$props)
                $result = end(self::$props);
        }

        return $result;
    }

    public static function mergeAttr(array $ar = array(), ...$other)
    {
        $result = array();
        if ($ar && $other)
            $result = array_merge($ar, ...$other);
        return $result;
    }

    public static function parentMergeAttr($ar = array())
    {
        $props = array();
        if (self::prop())
            $props = self::prop();

        if ($ar)
            $props = self::mergeAttr($ar, $props);

        return $props;
    }

    public static function load($file, array|string $props = array(), callable|bool $content = false)
    {

        if (is_string($props) && $props == '*')
            $props = self::parentMergeAttr();

        if ($content && is_callable($content)) {
            $rand = rand(100, 999999);
            self::capture('load:capture.' . $rand, function () use ($props, $content) {
                return  $content($props);
            });

            $props['__CONTENT__'] = self::getCapture('load:capture.' . $rand);
        }

        return self::include($file, $props);
    }

    public static function content(): string
    {
        return self::prop('__CONTENT__');
    }

    public static function errorPage($param = array())
    {

        self::page(
            array_merge([
                'name' =>  'page/errors/404',
                'title' => 404,
                'ajax' => true,
                'controller' => false
            ], $param)
        );
    }

    public static function accessDeniedPage($param = array())
    {

        self::page(
            array_merge([
                'name' =>  'page/errors/access-denied',
                'title' => 403,
                'ajax' => true,
                'controller' => false
            ], $param)
        );
    }

    public static function getSiteName(string $name = '')
    {
        $result = $name;

        if (empty($name)) {
            if (!empty(self::$config['siteName']))
                $result = self::$config['siteName'];
            else
                $result = Kernel::config('config', 'site_name');
        }
        return $result;
    }

    public static function getTitle($siteName = true)
    {
        $title = '';

        if (self::$config['showSiteName'] && $siteName)
            $title = self::getSiteName() . ' ' . self::$config['separator'] . ' ';

        return $title . self::$page['title'];
    }

    public static function getDescription(string $description = '')
    {
        $result = $description;

        if (empty($description)) {
            if (!empty(self::$page['description']))
                $result = self::$page['description'];
            else
                $result = Kernel::config('config', 'description');
        }
        return $result;
    }

    public static function getKeywords(string $keywords = '')
    {
        $result = $keywords;

        if (empty($keywords)) {
            if (!empty(self::$page['keywords']))
                $result = self::$page['keywords'];
            else
                $result = Kernel::config('config', 'keywords');
        }
        return $result;
    }

    public static function getContent()
    {
        return self::$page['content'];
    }

    public static function getHeader($file = 'header')
    {
        $type = Account::isAdminRoute() ? 'admin/' : 'public/';
        return self::$page['header'] ? View::load('page/' . self::$config['routeType'] . '/' . $file) : '';
    }

    public static function getFooter($file = 'footer')
    {
        return self::$page['footer'] ? View::load('page/' . self::$config['routeType'] . '/' . $file) : '';
    }

    public static function page(array $params = array())
    {

        $routeString = Controllers::getRouteString();

        $defParams = array(
            'name' => $routeString,
            'description' => '',
            'keywords' => '',
            'ajax' => false,
            'props' => array(),
            'title' => '',
            'header' => true,
            'footer' => true,
            'document' => 'page/' . self::$config['routeType'] . '/document',
            'controller' => true
        );

        $params = array_merge($defParams, $params);
        $params['name'] = $params['controller'] ?  self::$config['controllersDirectory'] . '/' . $params['name'] : $params['name'];

        if (Hooks::is('View::page.pre'))
            Hooks::apply('View::page.pre', $params, $routeString, self::$page);

        self::$page['header'] = $params['header'];
        self::$page['footer'] = $params['footer'];
        self::$page['title'] = trim($params['title']);
        self::$page['description'] = trim($params['description']);
        self::$page['keywords'] = trim($params['keywords']);

        if ($routeString === 'index') {
            if (self::$page['description'] === '')
                self::$page['description'] = self::$config['description'];

            if (self::$page['keywords'] === '')
                self::$page['keywords'] = self::$config['keywords'];
        }

        $content = self::load($params['name'], $params['props']);

        if (Hooks::is('View::page.post'))
            Hooks::apply('View::page.post', $params, $content, self::$page);

        if (isset($_POST['ajax']) && $params['ajax']) {

            if (Hooks::is('View::page.ajax'))
                Hooks::apply('View::page.ajax', $params, $content, self::$page);

            Json::get([
                'title' => self::getTitle(),
                'content' => $content,
                'description' => self::$page['description'] ?? '',
                'keywords' => self::$page['keywords'] ?? '',
            ], true);
        } else {

            self::$page['content'] = $content;

            $html = self::include($params['document']);
            $html = preg_replace('/^\n+|^[\t\s]*\n+/m', '', $html);
            $html = preg_replace('/    +/',  '', $html);

            echo $html;
        }
    }

    public static function assign($key, $value = '')
    {
        Registry::set('assign_registry', (is_array($key)) ? $key : [$key => $value]);
    }

    public static function getAssigned(...$key)
    {
        $result = '';
        $post = '';
        $pre = '';

        if (isset($key[0])) {

            $keys = (is_array($key[0])) ? $key[0] : $key;
            array_unshift($keys, 'assign_registry');
            $reg = Registry::get($keys);

            if (!empty($reg)) {
                if ((is_array($reg) && isset($reg['push.saved']))) {
                    $result = $reg['push.saved'];

                    if (isset($reg['push.post'])) {
                        foreach ($reg['push.post'] as $item) {
                            $post .= $item;
                        }
                    }

                    if (isset($reg['push.pre'])) {
                        foreach ($reg['push.pre'] as $item) {
                            $pre .= $item;
                        }
                    }

                    $result =  $pre . $result . $post;
                } else
                    $result = $reg;
            }
        }
        return $result;
    }

    public static function deleteAssigned(...$key)
    {
        if (isset($key[0])) {

            $keys = (is_array($key[0])) ? $key[0] : $key;
            array_unshift($keys, 'assign_registry');
            Registry::delete($keys);
        }
    }

    public static function assignPost($key, $value = '', $position = 0)
    {
        Registry::set('assign_registry', (is_array($key)) ? $key : [$key => $value], false, 'post',  $position);
    }

    public static function assignPre($key,  $value = '', $position = 0)
    {
        Registry::set('assign_registry', (is_array($key)) ? $key : [$key => $value], false, 'pre',  $position);
    }

    /**
     * Capturing content
     * @param string - Capture ID
     * @param callable - Content capture function
     * @param string - Type of content addition. (override, post, pre)
     * @param int - Data sorting position
     */

    public static function capture(string $id, callable $callback, $type = 'override', $position = 0): string
    {
        if ($id) {

            ob_start();
            $callback();
            $output = ob_get_contents();
            ob_end_clean();

            if ($type === 'post')
                self::assignPost('capture.' . $id, $output, $position);
            elseif ($type === 'pre')
                self::assignPre('capture.' . $id, $output, $position);
            else
                self::assign('capture.' . $id, $output);

            return self::getCapture($id);
        }
    }

    public static function getCapture($id = false)
    {
        return $id ? self::getAssigned('capture.' . $id) : '';
    }

    public static function removeCapture($id = false)
    {
        if ($id)
            self::deleteAssigned('capture.' . $id);
    }
}
