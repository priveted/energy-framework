<?php

namespace Energy\Components\Options;

use Energy\Account;
use Energy\View;
use Energy\Languages;
use Energy\Hooks;
use Energy\Kernel;
use Energy\Security;
use Energy\Url;
use Energy\Search;
use Energy\Json;
use Energy\Db;
use Energy\Utils;

class OptionsAdminController
{

    /**
     * Home page of options
     * @param string The name of the included component
     * @param array Basic parameters
     */

    public static function index(string $componentName, array $params = array()): void
    {

        if (Account::isAdminLevel(Account::LEVEL_MODERATOR) && Kernel::config('components/options', 'status')) {

            if (Kernel::config('components/' . $componentName, 'status')) {
                $cId = Kernel::config('components/' . $componentName, 'id');

                if ($cId) {

                    $options = array();
                    $filters = array();

                    Hooks::apply('Components.Options.OptionsAdminControllers::index.filters', $filters);

                    Url::setFilter('option_status', [0, 1, 2], $filters);

                    $defParams = [
                        'filters' => $filters,
                        'allowSearch' => true,
                        'search' => $_GET['search'] ?? '',
                        'orderAllowedColumns' => [
                            'option_id',
                            'option_changed_timestamp'
                        ],
                        'orderBy' => Security::stringFilter($_GET['orderBy'] ?? 'option_id'),
                        'sortType' => Security::stringFilter($_GET['sortType'] ?? 'DESC'),

                        'render' => [
                            'item' => [
                                'view' => 'controllers/admin/options/inc/option',
                                'props' => [
                                    'name' => $componentName
                                ]
                            ]
                        ],

                        'owner' => true,
                        'changed' => true,
                        'pageTitle' => Languages::get('controllers', 'admin/options', 'title')
                    ];

                    $params = array_merge($defParams, $params);

                    if (empty($params['where']))
                        $params['where'] = array();

                    $params['where'] = array_merge($params['where'], [
                        'option_component_id' => $cId
                    ]);


                    Hooks::apply('Components.Options.OptionsAdminControllers::index.params', $params);

                    $options = Options::get($params);

                    $page = [
                        'name' => 'admin/options',
                        'title' => $params['pageTitle'],
                        'ajax' => true,
                        'props' => [
                            'name' => $componentName,
                            'componentId' => $cId,
                            "options" => $options
                        ]
                    ];

                    $cNameRoute = $componentName ? '/' . $componentName . '/' : '/';

                    Hooks::apply('[admin' . $cNameRoute . 'options]', $page);

                    View::page($page);
                }
            } else
                View::errorPage();
        } else
            View::errorPage();
    }


    /**
     * Request to display the following items
     */

    public static function requestMore(): void
    {

        if (Kernel::config('components/options', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $id = Security::stringFilter($_POST['protected'] ?? '');

            if ($id)
                Options::more($id);
        }
    }


    /**
     * Request to delete an item
     */

    public static function requestDelete(): void
    {
        $result = [
            'success' => false,
            'content' => Languages::get('errors', 'access_denied'),
            'status' => 'error'
        ];

        if (Kernel::config('components/options', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $ids = Security::stringFilter($_POST['ids'] ?? '');

            if ($ids) {

                $filtered = Utils::numberFilter($ids, true, false);

                if ($filtered) {

                    $options = Db::select(Options::TABLE_NAME, [
                        'option_id'
                    ], [
                        'option_id' => $filtered
                    ]);

                    $allow = [];

                    foreach ($options as $option) {
                        $allow[] = intval($option['option_id']);
                    }

                    if ($allow) {
                        Options::delete([
                            'option_id' => $allow,
                        ]);

                        $result = [
                            'success' => true,
                            'status' => 'success',
                            'content' => Languages::get('common', 'data_saved'),
                            'redirect' => '',
                            'redirectHref' => true
                        ];
                    }
                }
            } else {
                $result = [
                    'success' => false,
                    'status' => 'error',
                    'content' => Languages::get('errors', 'no_element_selected')
                ];
            }
        }

        Json::response($result);
    }


    /**
     * Request to the search page
     * @param string $queryKey
     * @param array $query
     */

    public static function indexRequestSearch(string $componentName, $queryKey = 'search', $query = 'query'): void
    {

        if (Account::isAdminLevel(Account::LEVEL_MODERATOR)) {
            $query = Security::stringFilter($_POST[$query] ?? '');

            Search::ajaxRedirect($query, [
                'controller' => 'admin/' . $componentName . '/options',
                'queryKey' => $queryKey
            ]);
        }
    }


    /**
     * Element Editing Page
     * @param string The name of the included component
     */

    public static function edit(string $componentName): void
    {

        if (Account::isAdminLevel(Account::LEVEL_MODERATOR) && Kernel::config('components/options', 'status')) {

            if (Kernel::config('components/' . $componentName, 'status')) {

                $cId = Kernel::config('components/' . $componentName, 'id');

                if ($cId) {
                    $title = Languages::get('controllers', 'admin/options/edit', 'create_new_option');
                    $status = false;
                    $id = Security::stringFilter($_GET['id'] ?? '');
                    $listParams = [
                        'where' => [
                            'option_id' => $id,
                            'option_component_id' => $cId
                        ]
                    ];

                    $option = array();
                    $params = array();
                    $statusTitle = Languages::get('page', 'admin', 'not_active');

                    if ($id) {

                        $checkOption = Options::getQuantity($listParams);

                        if ($checkOption) {
                            $title = Languages::get('controllers', 'admin/options/edit', 'edit_option');
                            $optionData = Options::get($listParams);
                            $option = $optionData['list'][0] ?? array();
                            $params = $optionData['params'] ?? array();
                            $status = true;

                            if ($option['option_status'] == 1)
                                $statusTitle = Languages::get('page', 'admin', 'active');
                            else if ($option['option_status'] == 2)
                                $statusTitle = Languages::get('page', 'admin', 'blocked');
                        }
                    } else {
                        $status = true;
                    }

                    if ($status) {
                        View::page(
                            [
                                'name' => 'admin/options/edit',
                                "title" => $title,
                                "ajax" => true,
                                'props' => [
                                    'option' => $option,
                                    'params' => $params,
                                    'statusTitle' => $statusTitle,
                                    'name' => $componentName
                                ]
                            ]
                        );
                    } else
                        View::errorPage();
                } else
                    View::errorPage();
            } else
                View::errorPage();
        } else
            View::errorPage();
    }


    /**
     * Request to edit an element
     * @param string The name of the included component
     */

    public static function editRequestEdit(string $componentName): void
    {

        $result = [
            'success' => false,
            'content' => Languages::get('errors', 'access_denied'),
            'status' => 'error'
        ];

        if (Account::isAdminLevel(Account::LEVEL_MANAGER) && Kernel::config('components/' . $componentName, 'status')) {

            $cId = Kernel::config('components/' . $componentName, 'id');

            if ($cId) {

                $filter = Security::sanitize(
                    $_POST['option'] ?? array(),
                    [
                        'title' => 'escape',
                        'description' => 'string',
                        'option_id' => 'int',
                        'option_sort' => 'int',
                        'option_status' => [
                            'type' => 'int',
                            'allowed' => [0, 1, 2]
                        ],
                        'option_type' => [
                            'type' => 'int',
                            'allowed' => [1, 2]
                        ]
                    ]
                );

                $title =  $filter->get('title');
                $option_id = $filter->get('option_id', 0);


                if (empty($title)) {

                    $result = [
                        'success' => false,
                        'content' => Languages::get('errors', 'required_fields'),
                        'status' => 'error'
                    ];
                } else {

                    $data = $filter->getAll();

                    if (!empty($data)) {

                        $redirectId = 0;

                        Hooks::add('Core.Modify.Edit::save.ids', function ($ids, $params) use (&$redirectId) {
                            if ($params['componentName'] == Options::COMPONENT_NAME)
                                $redirectId = $ids[0] ?? 0;
                        });

                        $data['option_component_id'] = $cId;

                        $status = Options::edit(
                            $data,
                            [
                                'id' => $option_id
                            ]
                        );

                        if ($status) {

                            $result = [
                                'success' => true,
                                'content' => Languages::get('common', 'data_saved'),
                                'status' => 'success',
                                'redirect' => Url::link('admin/' . $componentName . '/options/edit', ['id' => $redirectId])
                            ];
                        }
                    }
                }
            }
        }

        Json::response($result);
    }


    /**
     * Request for an option template
     * @param string The name of the included component
     */

    public static function addOptionTemplate(string $componentName): void
    {

        $result = [
            'success' => false,
            'content' => Languages::get('errors', 'access_denied'),
            'status' => 'error'
        ];

        if (Kernel::config('components/options', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $isId = false;

            $id = Security::stringFilter(($_POST['optionId'] ?? ''));
            $isChange = Security::stringFilter(($_POST['isChange'] ?? ''));
            $tmpId = Security::stringFilter(($_POST['tmpId'] ?? ''));
            $title = Security::stringFilter(($_POST['title'] ?? ''));

            if (!empty($id)) {
                $isId = !!Options::getQuantity([
                    'where' => [
                        'option_id' => $id
                    ]
                ]);
            }

            $attr = [
                'isEmpty' => !$isId,
                'name' => $componentName,
                'type' => 'create'
            ];

            $insert = [
                '#option-list .empty' => [
                    'insertType' => 'remove',
                ]
            ];

            if ($isId) {
                $attr['option_id'] = $id;
                $attr['title'] = $title;

                $insert['#option-item-' . $tmpId] = [
                    'insertType' =>  'outerHTML',
                    'content' => View::load('controllers/admin/options/edit/inc/option', $attr)
                ];
            } else
                $insert['#option-list'] =  [
                    'insertType' =>  'afterBegin',
                    'content' => View::load('controllers/admin/options/edit/inc/option', $attr)
                ];

            $result = [
                'success' => true,
                'insert' =>  $insert
            ];

            if ($tmpId && $isId && $isChange) {
                $result['insert']['.option-list'] = [
                    'insertType' => 'beforeEnd',
                    'content' => '<input type="hidden" name="optionTempDelete[]" value="' . $tmpId . '">'
                ];
            }
        }

        Json::response($result);
    }

    /**
     * Request for an option template
     * @param string The name of the included component
     * @param string|int Component Id
     */

    public static function ajaxList(string $componentName, $componentId): void
    {
        $result = [
            'success' => false,
            'content' => Languages::get('errors', 'access_denied'),
            'status' => 'error'
        ];

        if (Kernel::config('components/options', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {
            $isChange = Security::stringFilter(($_POST['isChange'] ?? ''));
            $id = Security::stringFilter(($_POST['optionId'] ?? ''));
            $tmpId = Security::stringFilter(($_POST['tmpId'] ?? ''));

            if ($id || $tmpId) {
                $attr = [
                    'name' => $componentName,
                    'isChange' => $isChange
                ];

                if (!empty($id))
                    $attr['tmpId'] = $id;

                if ($tmpId) {
                    $id = $tmpId;
                    $attr['tmpId'] = $id;
                }

                $options = Options::get([
                    'where' => [
                        'option_status' => 1,
                        'option_component_id' => $componentId
                    ],

                    'render' => [
                        'item' => [
                            'view' => 'controllers/admin/options/edit/inc/ajax-option',
                            'props' => $attr
                        ],
                        'more' => [
                            'view' => 'widgets/admin/more.button',
                            'contentContainer' => '#option-content-x-' . $id,
                            'callback' => 'ceOptions'
                        ]
                    ]
                ]);

                $result = [
                    'success' => true,
                    'insert' => [
                        '#option-content-x-' . $id => [
                            'insertType' => 'html',
                            'content' => $options['content']['list'] ?? ''
                        ],
                        '#option-more-x-' . $id => [
                            'insertType' => 'html',
                            'content' => $options['content']['more'] ?? ''
                        ]
                    ]
                ];
            }
        }

        Json::response($result);
    }
}
