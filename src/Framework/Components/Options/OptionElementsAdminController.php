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
use Energy\Files;
use Energy\Db;
use Energy\Utils;

class OptionElementsAdminController
{

    /**
     * Home page of option elements
     * @param string The name of the included component
     * @param array Basic parameters
     */

    public static function index(string $componentName, array $params = array()): void
    {

        if (Account::isAdminLevel(Account::LEVEL_MODERATOR) && Kernel::config('components/optionElements', 'status')) {

            if (Kernel::config('components/' . $componentName, 'status')) {
                $cId = Kernel::config('components/' . $componentName, 'id');

                if ($cId) {

                    $optionElements = array();
                    $filters = array();

                    Hooks::apply('Components.Options.OptionElementsAdminController::index.filters', $filters);

                    Url::setFilter('oe_status', [0, 1, 2], $filters);

                    $defParams = [
                        'limit' => 20,
                        'filters' => $filters,
                        'allowSearch' => true,
                        'search' => $_GET['search'] ?? '',
                        'childrenCount' => true,
                        'orderAllowedColumns' => [
                            'oe_id',
                            'oe_changed_timestamp'
                        ],
                        'orderBy' => Security::stringFilter($_GET['orderBy'] ?? 'oe_id'),
                        'sortType' => Security::stringFilter($_GET['sortType'] ?? 'DESC'),
                        'render' => [
                            'item' => [
                                'view' => 'controllers/admin/optionElements/inc/optionElement',
                                'props' => [
                                    'name' => $componentName
                                ]
                            ]
                        ],
                        'owner' => true,
                        'changed' => true,
                        'pageTitle' => Languages::get('controllers', 'admin/optionElements', 'title')
                    ];

                    $params = array_merge($defParams, $params);

                    if (empty($params['where']))
                        $params['where'] = array();

                    $params['where'] = array_merge($params['where'], [
                        'oe_component_id' => $cId
                    ]);


                    Hooks::apply('Components.Options.OptionElementsAdminController::index.params', $params);

                    $optionElements = OptionElements::get($params);

                    $page = [
                        'name' => 'admin/optionElements',
                        'title' => $params['pageTitle'],
                        'ajax' => true,
                        'props' => [
                            'name' => $componentName,
                            'componentId' => $cId,
                            "optionElements" => $optionElements
                        ]
                    ];

                    $cNameRoute = $componentName ? '/' . $componentName . '/' : '/';

                    Hooks::apply('[admin' . $cNameRoute . 'optionElements]', $page);

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

        if (Kernel::config('components/optionElements', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $id = Security::stringFilter($_POST['protected'] ?? '');

            if ($id)
                OptionElements::more($id);
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

        if (Kernel::config('components/optionElements', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $ids = Security::stringFilter($_POST['ids'] ?? '');

            if ($ids) {

                $filtered = Utils::numberFilter($ids, true, false);

                if ($filtered) {

                    $optionElements = Db::select(OptionElements::TABLE_NAME, [
                        'oe_id'
                    ], [
                        'oe_id' => $filtered
                    ]);

                    $allow = [];

                    foreach ($optionElements as $option) {
                        $allow[] = intval($option['oe_id']);
                    }

                    if ($allow) {
                        OptionElements::delete([
                            'oe_id' => $allow,
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
                'controller' => 'admin/' . $componentName . '/optionElements',
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

        if (Account::isAdminLevel(Account::LEVEL_MODERATOR) && Kernel::config('components/optionElements', 'status')) {

            if (Kernel::config('components/' . $componentName, 'status')) {

                $cId = Kernel::config('components/' . $componentName, 'id');

                if ($cId) {
                    $title = Languages::get('controllers', 'admin/optionElements/edit', 'create_new_option_element');
                    $status = false;
                    $id = Security::stringFilter($_GET['id'] ?? '');
                    $listParams = [
                        'where' => [
                            'oe_id' => $id,
                            'oe_component_id' => $cId
                        ]
                    ];
                    $optionElement = array();
                    $params = array();
                    $statusTitle = Languages::get('page', 'admin', 'not_active');

                    if ($id) {

                        $checkPost = OptionElements::getQuantity($listParams);

                        if ($checkPost) {
                            $listParams['preview'] = true;
                            $title = Languages::get('controllers', 'admin/optionElements/edit', 'edit_option_element');
                            $optionData = OptionElements::get($listParams);
                            $optionElement = $optionData['list'][0] ?? array();
                            $params = $optionData['params'] ?? array();
                            $status = true;

                            if ($optionElement['oe_status'] == 1)
                                $statusTitle = Languages::get('page', 'admin', 'active');
                            else if ($optionElement['oe_status'] == 2)
                                $statusTitle = Languages::get('page', 'admin', 'blocked');
                        }
                    } else {
                        $status = true;
                    }

                    if ($status) {
                        View::page(
                            [
                                'name' => 'admin/optionElements/edit',
                                "title" => $title,
                                "ajax" => true,
                                'props' => [
                                    'optionElement' => $optionElement,
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
                    $_POST['optionElement'] ?? array(),
                    [
                        'title' => 'escape',
                        'description' => 'string',
                        'oe_id' => 'int',
                        'oe_sort' => 'int',
                        'oe_status' => [
                            'type' => 'int',
                            'allowed' => [0, 1, 2]
                        ]
                    ]
                );

                $title =  $filter->get('title');
                $oe_id = $filter->get('oe_id', 0);
                $delete_image = intval(Security::stringFilter($_POST['delete_image'] ?? ''));
                $errorUpload = false;
                $statusUpload = false;
                $response = Files::RESULT_SCHEME;
                $response = $response['response'];

                if (!empty($_FILES['file']) && !$_FILES['file']['error'][0]) {

                    $validUpload = Files::prepare([
                        'file' => $_FILES['file'] ?? array(),
                        'allowExtensions' => Files::IMAGES_L,
                    ]);

                    if (isset($validUpload['finalStatus'])  && !$validUpload['finalStatus']) {
                        $errorUpload = true;
                        $response = $validUpload['response'];
                    } else {
                        $statusUpload = true;
                    }
                }


                if (empty($title)) {

                    $result = [
                        'success' => false,
                        'content' => Languages::get('errors', 'required_fields'),
                        'status' => 'error'
                    ];
                } elseif ($errorUpload) {
                    $result = $response;
                } else {

                    $data = $filter->getAll();

                    if (!empty($data)) {

                        $redirectId = 0;

                        Hooks::add('Core.Modify.Edit::save.ids', function ($ids, $params) use (&$redirectId) {
                            if ($params['componentName'] == OptionElements::COMPONENT_NAME)
                                $redirectId = $ids[0] ?? 0;
                        });

                        $data['oe_component_id'] = $cId;

                        $status = OptionElements::edit(
                            $data,
                            [
                                'id' => $oe_id
                            ]
                        );

                        if ($status) {

                            if ($delete_image) {
                                OptionElements::deleteImage([
                                    'bindType' => 0,
                                    'bindId' => $redirectId
                                ]);
                            }

                            if ($redirectId) {

                                if ($statusUpload) {

                                    OptionElements::deleteImage([
                                        'bindType' => 0,
                                        'bindId' => $redirectId
                                    ]);

                                    $uploadResult = OptionElements::uploadImage([
                                        'file' => $_FILES['file'] ?? array(),
                                        'crop' => Security::stringFilter($_POST['crop_data'] ?? ''),
                                        'bindType' => 0,
                                        'bindId' => $redirectId
                                    ]);

                                    if (!empty($uploadResult) && !$uploadResult['success']) {
                                        $result = $uploadResult;
                                    }
                                }
                            }

                            $result = [
                                'success' => true,
                                'content' => Languages::get('common', 'data_saved'),
                                'status' => 'success',
                                'redirect' => Url::link('admin/' . $componentName . '/optionElements/edit', ['id' => $redirectId])
                            ];
                        }
                    }
                }
            }
        }

        Json::response($result);
    }


    /**
     * Request for an option element template
     * @param string The name of the included component
     */

    public static function addOptionElementTemplate(string $componentName): void
    {

        $result = [
            'success' => false,
            'content' => Languages::get('errors', 'access_denied'),
            'status' => 'error'
        ];

        if (Kernel::config('components/optionElements', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $isId = false;

            $optionId = Security::stringFilter(($_POST['optionId'] ?? ''));
            $isChange = Security::stringFilter(($_POST['isChange'] ?? ''));
            $id = Security::stringFilter(($_POST['optionElementId'] ?? ''));
            $tmpId = Security::stringFilter(($_POST['tmpId'] ?? ''));
            $title = Security::stringFilter(($_POST['title'] ?? ''));

            if (!empty($id)) {
                $isId = !!OptionElements::getQuantity([
                    'where' => [
                        'oe_id' => $id
                    ]
                ]);
            }

            $attr = [
                'isChange' => false,
                'isEmpty' => !$isId,
                'name' => $componentName,
                'optionId' => $optionId,
                'type' => 'create'
            ];

            $insert = [];

            if ($isId) {
                $attr['oe_id'] = $id;
                $attr['title'] = $title;

                $insert['#option-element-' . $optionId . '-' . $tmpId] = [
                    'insertType' =>  'outerHTML',
                    'content' => View::load('controllers/admin/options/edit/inc/optionElement', $attr)
                ];
            } else

                $insert['#option-elements-' . $optionId] =  [
                    'insertType' =>  'afterBegin',
                    'content' => View::load('controllers/admin/options/edit/inc/optionElement', $attr)
                ];

            $result = [
                'success' => true,
                'insert' =>  $insert
            ];

            if ($tmpId && $isId && $isChange) {
                $result['insert']['.option-list'] = [
                    'insertType' => 'beforeEnd',
                    'content' => '<input type="hidden" name="optionKeysTempDelete[' . $optionId . '][]" value="' . $tmpId . '">'
                ];
            }
        }

        Json::response($result);
    }


    /**
     * Request a list of options
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

        if (Kernel::config('components/optionElements', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {
            $isChange = Security::stringFilter(($_POST['isChange'] ?? ''));
            $optionId = Security::stringFilter(($_POST['optionId'] ?? ''));
            $id = Security::stringFilter(($_POST['optionElementId'] ?? ''));
            $tmpId = Security::stringFilter(($_POST['tmpId'] ?? ''));

            if ($id || $tmpId) {

                $attr = [
                    'name' => $componentName,
                    'optionId' => $optionId,
                    'isChange' => $isChange
                ];

                if (!empty($id))
                    $attr['tmpId'] = $id;

                if ($tmpId) {
                    $id = $tmpId;
                    $attr['tmpId'] = $id;
                }

                $options = OptionElements::get([
                    'limit' => 20,
                    'where' => [
                        'oe_status' => 1,
                        'oe_component_id' => $componentId
                    ],
                    'render' => [
                        'item' => [
                            'view' => 'controllers/admin/optionElements/edit/inc/ajax-option-element',
                            'props' => $attr
                        ],
                        'more' => [
                            'view' => 'widgets/admin/more.button',
                            'props' => [
                                'contentContainer' => '#option-content-x-' . $optionId . '-' . $id,
                                'callback' => 'ceOptionElements'
                            ]
                        ]
                    ]
                ]);

                $result = [
                    'success' => true,
                    'insert' => [
                        '#option-content-x-' . $optionId . '-' . $id => [
                            'insertType' => 'html',
                            'content' => $options['content']['list'] ?? ''
                        ],
                        '#option-more-x-' . $optionId . '-' . $id => [
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
