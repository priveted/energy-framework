<?php

namespace Energy\Core\List;

use Energy\Controllers;
use Energy\Session;
use Energy\Hooks;
use Energy\Languages;
use Energy\Json;
use Energy\Account;

class More
{

    /**
     * Request the following items
     * @param string Protected Identifier
     * @param callable Callback
     * @param array Basic parameters
     */


    public static function response(string $id, callable $callback, array $params = array()): void
    {

        $error = false;
        $admin = Account::isAdminRoute() ? 'admin' : 'public';

        $defParams = array(
            'insertType' => 'beforeEnd',
            'buttonTemplate' => 'widgets/' . $admin . '/more.button',
            'url' => Controllers::getRouteString()
        );

        $params = array_merge($defParams, $params);

        $result = array(
            'success' => true,
            'content' => ''
        );

        Hooks::apply('Core.List.More::response.pre', $params, $result, $error);

        if ($id) {

            $comParams = Session::get('component.params.' . $id);

            if (!empty($comParams) && is_array($comParams)) {

                $params = array_merge($params, $comParams);

                Hooks::apply('Core.List.More::response.success.pre', $params, $comParams, $result, $error);

                Session::set('component.params.' . $id, $comParams);

                $data = $callback($comParams);
                $content = '';

                if ($data && isset($data['list']) && isset($data['params']) && isset($data['content'])) {

                    $content = $data['content']['list'] ?? '';
                    $moreButton = $data['content']['more'] ?? '';
                    $result['content'] = $content;
                    $result['insertType'] = $params['insertType'];
                    $remove = $result['insert'] = [
                        '#more-' . $id => [
                            'content' => '',
                            'insertType' => 'remove'
                        ],
                        '.more-' . $id => [
                            'content' => '',
                            'insertType' => 'remove'
                        ]
                    ];

                    if (isset($params['render']['more']['afterList']) && $params['render']['more']['afterList']) {
                        $result['insert'] = $remove;
                        $result['content'] = $result['content'] . $moreButton;
                    } else  if (isset($params['render']['more']['beforeList']) && $params['render']['more']['beforeList']) {
                        $result['insert'] = $remove;;
                        $result['content'] =  $moreButton . $result['content'];
                    } else {
                        $result['insert'] = [
                            '#more-' . $id => [
                                'content' => $moreButton,
                                'insertType' => 'outerHTML'
                            ]
                        ];
                    }

                    $result['status'] = '';
                } else
                    $error = true;

                Hooks::apply('Core.List.More::response.success.post', $params, $comParams, $result, $error);
            } else
                $error = true;
        } else
            $error = true;

        if ($error) {
            $errorMessage = Languages::get('errors', 'access_error');

            $result = [
                'success' => false,
                'content' => $errorMessage,
                'status' => 'error'
            ];

            Hooks::apply('Core.List.More::response.error', $result, $params, $error);
        }

        Hooks::apply('Core.List.More::response.post', $result, $params, $error);

        Json::response($result);
    }
}
