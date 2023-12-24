<?php

namespace Energy;

class Url
{

    public static function scheme()
    {
        return (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 'https://' : 'http://';
    }

    public static function used()
    {
        $http = self::scheme();
        return $http . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    public static function refresh()
    {
        self::redirect(self::used());
    }

    public static function getReferer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
    }

    public static function back()
    {
        header('location: ' . self::getReferer('/'));
    }

    public static function redirect($link = '/')
    {
        header('location:' . $link);
    }

    /**
     *  Adding URL parameters
     * @param array - Inserts parameters into the common URL string
     * @param array - Data output parameters
     */

    public static function addParameters(array $urlParams = array(), array $params = array())
    {
        $result = '';
        $defParams = array(
            'remove' => false,      // Delete mode. Analog of the Url::removeParameters() function
            'url' => self::used(),  // The URL where the parameters will be inserted
            'host' => false,        // Show the host in the shared row (bool || string)
            'scheme' => false,      // Show the scheme (protocol) in the common line (bool || string)
        );

        $params = array_merge($defParams, $params);
        $parse = parse_url($params['url']);
        $path = $parse['path'] ?? '';

        if ($path && $urlParams) {

            $host = (isset($parse['host']) && $params['host']) ? $parse['host'] : '';
            if ($params['host'] && !is_bool($params['host'])) {
                $host =  $params['host'];
            }

            $scheme = (isset($parse['scheme']) && $params['scheme']) ? $parse['scheme'] . '://' : '';
            if ($params['scheme'] && !is_bool($params['scheme'])) {
                $scheme = $params['scheme'];
            }

            $str = isset($parse['query']) ? $parse['query'] : false;
            $arr = array();

            if ($str)
                parse_str($str, $arr);

            if ($params['remove']) {
                foreach ($urlParams as $key) {
                    if (isset($arr[$key]))
                        unset($arr[$key]);
                }
            } else
                $arr = array_merge($arr, $urlParams);

            $query = urldecode(http_build_query($arr));
            $query = ($query) ? '?' . $query : '';
            $result = $scheme . $host . $path . $query;
        }
        return $result;
    }


    /**
     *  Remove URL parameters
     * @param mixed - Deletes parameters to the common URL string ( array || string )
     * @param array - Data output parameters
     */

    public static function removeParameters($urlParams, $params = array())
    {

        if (is_string($urlParams))
            $urlParams = array($urlParams);

        return self::addParameters($urlParams, array_merge($params, array('remove' => true)));
    }

    public static function smartComparison(string $url1, string $url2 = '', $exactMatch = false)
    {
        $result = false;

        if (is_string($url1) && is_string($url2)) {

            $url2 = !$url2 ? $_SERVER['REQUEST_URI'] : $url2;
            $arr1 = self::smartParse($url1);
            $arr2 = self::smartParse($url2);

            if ($arr1 && $arr2) {
                $count1 = count($arr1);
                if ($exactMatch) {
                    $x = array_diff_assoc($arr2, $arr1);
                    if (!$x)
                        $result = true;
                } else {
                    $x = array_intersect_assoc($arr1, $arr2);
                    if ($x && count($x) === $count1)
                        $result = true;
                }
            }
        }

        return $result;
    }

    public static function smartParse($str = '')
    {
        $result = [];

        if ($str) {

            $str = ltrim($str, '/');
            $parse = parse_url($str);

            if (isset($parse['query'])) {
                parse_str($parse['query'], $result);
            }

            if (isset($parse['path']) && ltrim($parse['path'], '/') != ltrim($_SERVER['PHP_SELF'], '/')) {
                $result['route'] = ltrim($parse['path'], '/');
            }
        }

        return $result;
    }

    /**
     * Generates a link based on the enabled rewrite_url parameter
     * @param string - Name of the controller (route)
     * @param array - Inserts parameters into the common URL string
     * @param array - Parameters of the Url::addParameters() function
     */
    public static function link($name, $arr = array(), $params = array())
    {
        $result = '';

        if (is_array($params)) {
            $defParams = array(
                'url' => Kernel::config('config', 'rewrite_mode') ? '/' . $name : '/index.php?route=' . $name
            );
            $params = array_merge($defParams, $params);
            $result =  (is_array($arr) && $arr) ? self::addParameters($arr, $params) : $params['url'];
        }

        return $result;
    }

    public static function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    public static function setFilter(string $key, array $allowed = array(), &$filterList = false)
    {

        $result = array();
        if (!empty($key)) {

            $eKey = 'filter-' . Encryption::irreversible($key);

            if (isset($_GET[$eKey])) {

                $status = true;
                $value = $_GET[$eKey];

                if ((preg_match('/\[(.*?)]/', $value) && Json::isJson($value) && is_array(json_decode($value))))
                    $value = Security::sanitize(json_decode($value), array(), false)->getAll();
                else
                    $value = Security::stringFilter($value);

                if ($allowed) {
                    if (is_array($value) && $value = array_intersect($value,  $allowed)) {
                        if ($value || $value == 0)
                            $status = true;
                    } else
                        $status = in_array($value, $allowed);
                }

                if ($status) {

                    Registry::set('CORE_URL_FILTERS_DATA', [$key => $value]);
                    $result = array($key => $value);
                }
            }
        }

        if (is_array($filterList) && $result)
            $filterList[] = $result;

        return $result;
    }

    public static function isActiveFilter($key, $value): bool
    {
        $result = false;
        $rKey = Registry::get('CORE_URL_FILTERS_DATA', $key);

        if (!empty($rKey) || $rKey == 0) {
            if (is_array($rKey) && in_array($value, $rKey)) {
                $result = true;
            } elseif ($rKey == $value) {
                $result = true;
            }
        }

        return  $result;
    }

    public static function getFilterCount()
    {
        return (!empty(Registry::get('CORE_URL_FILTERS_DATA'))) ? count(Registry::get('CORE_URL_FILTERS_DATA')) : 0;
    }


    /**
     * Creates a filter link based on url data
     * @param string The filter key
     * @param mixed The value of filtration
     * @param array Parameters
     */

    public static function createFilterLink(string $key, mixed $value, array $params = array())
    {
        $defParams = array(
            'clear' => false, // Removes the filter from the url
            'toggle' => false, // Toggle switch of the filter activity. If the filter is active, it will be deleted, if inactive, it will be added
            'url' => $_SERVER['REQUEST_URI'] // URL for parsing
        );

        $params = array_merge($defParams, $params);
        $result = '';
        $filter = 'filter-' . Encryption::irreversible($key);
        $data = is_array($value) ? '[' . implode(',', $value) . ']' : $value;

        if ($params['clear'] || $params['toggle']) {

            if (!empty($_GET[$filter])) {

                $urlFilter = $_GET[$filter];
                $arr = (preg_match('/\[(.*?)]/', $urlFilter) && Json::isJson($urlFilter) && is_array(json_decode($urlFilter))) ? json_decode($urlFilter) : array($urlFilter);

                if ($arr || $arr == 0) {

                    $val = is_array($value) ? $value : array($value);

                    if ($val) {
                        foreach ($val as $vv) {

                            if (in_array($vv, $arr)) {
                                unset($arr[array_search($vv, $arr)]);
                            } else {
                                $arr[] = $vv;
                            }
                        }
                    }

                    $arr = array_values(array_unique($arr));

                    if ($arr && count($arr) > 1)
                        $data = '[' . implode(',', $arr) . ']';
                    else
                        $data = $arr[0] ?? '';
                }

                if ($params['clear'] || ($params['toggle'] && empty($data)))
                    $result = self::removeParameters($filter, $params);
            }
        }

        if (!empty($data) || $data != '[]')
            $result =  !empty($result) ? $result : self::addParameters([$filter => $data], $params);

        return $result;
    }
}
