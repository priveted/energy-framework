<?php

namespace Energy;

class Search
{

    /**
     * Checking and redirecting to the search page
     * @param string Query
     * @param array Basic parameters
     */

    public static function ajaxRedirect($query, $params = array())
    {

        $defParams = array(
            'controller' => 'search',
            'queryKey' => 'query',
            'ajaxRedirect' => true,
            'maxLength' => 20,
            'minLength' => 2,
            'selector' => '.search'
        );

        $params = array_merge($defParams, $params);

        $result = array(
            'success' => false,
            'content' => '',
            'redirectHref' => $params['ajaxRedirect'],
        );

        if (Url::isPost() && $query) {

            Hooks::apply('Search::ajaxRedirect.pre', $query, $params, $result);

            $Length = iconv_strlen($query, 'UTF-8');

            if ($params['controller']) {

                if ($Length < $params["minLength"]) {
                    $result['status'] = 'error';
                    $result['content'] = Languages::get('errors', 'short_search_query');
                } elseif ($Length >= $params["maxLength"]) {
                    $result['status'] = 'error';
                    $result['content'] = Languages::get('errors', 'long_search_query');
                } else {
                    $result['success'] = true;
                    $result['insert'] = [
                        $params['selector'] => [
                            'insertType' => 'val',
                            'content' => ''
                        ]
                    ];
                    $result['redirect'] = Url::link($params['controller'], [
                        $params['queryKey'] => $query
                    ]);
                }
            } else {
                $result['status'] = 'error';
                $result['content'] = Languages::get('errors', 'request404');
            }

            Hooks::apply('Search::ajaxRedirect.post', $query, $params, $result);
        } else {

            $result['status'] = 'error';
            $result['content'] = Languages::get('errors', 'empty_search_query');

            Hooks::apply('Search::ajaxRedirect.error', $params, $result);
        }

        Json::response($result);
    }

    public static function statusByParamsDataList(array $params = array(), $searchGetParam = 'search'): bool
    {
        return (is_array($params) && isset($params['allowSearch']) && isset($params['searchStatus']) && !empty($_GET[$searchGetParam]) && $params['allowSearch'] && $params['searchStatus']);
    }
}
