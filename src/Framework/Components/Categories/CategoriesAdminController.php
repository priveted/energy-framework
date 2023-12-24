<?php

namespace Energy\Components\Categories;

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
use Energy\Seo;
use Energy\Db;
use Energy\Utils;

class CategoriesAdminController
{

    /**
     * Home page of categories
     * @param string The name of the included component
     * @param array Basic parameters
     */

    public static function index(string $componentName, array $params = array()): void
    {

        if (Account::isAdminLevel(Account::LEVEL_MODERATOR) && Kernel::config('components/categories', 'status')) {

            if (Kernel::config('components/' . $componentName, 'status')) {
                $cId = Kernel::config('components/' . $componentName, 'id');

                if ($cId) {

                    $categories = array();
                    $filters = array();

                    Hooks::apply('Components.Categories.CategoriesAdminControllers::index.filters', $filters);
                    Url::setFilter('ca_status', [0, 1, 2], $filters);

                    $defParams = [
                        'filters' => $filters,
                        'allowSearch' => true,
                        'search' => $_GET['search'] ?? '',
                        'childrenCount' => true,
                        'orderAllowedColumns' => [
                            'ca_id',
                            'ca_changed_timestamp',
                            'ca_sort',
                            'ca_parent'
                        ],
                        'orderBy' => Security::stringFilter($_GET['orderBy'] ?? 'ca_id'),
                        'sortType' => Security::stringFilter($_GET['sortType'] ?? 'ASC'),
                        'render' => [
                            'item' => [
                                'view' => 'controllers/admin/categories/inc/category',
                                'props' => [
                                    'name' => $componentName
                                ]
                            ]
                        ],
                        'owner' => true,
                        'changed' => true,
                        'pageTitle' => Languages::get('controllers', 'admin/categories', 'title')
                    ];

                    $params = array_merge($defParams, $params);

                    if (empty($params['where']))
                        $params['where'] = array();

                    $params['where'] = array_merge($params['where'], [
                        'ca_component_id' => $cId
                    ]);

                    Hooks::apply('Components.Categories.CategoriesAdminControllers::index.params.pre', $params);

                    if (empty($_GET['search'])) {
                        $params['where']['ca_parent'] = Security::stringFilter($_GET['parent'] ?? 0);
                    }

                    Hooks::apply('Components.Categories.CategoriesAdminControllers::index.params.post', $params);

                    $categories = Categories::get($params);

                    $page = [
                        'name' => 'admin/categories',
                        'title' => $params['pageTitle'],
                        'ajax' => true,
                        'header' => true,
                        'footer' => true,
                        'props' => [
                            'name' => $componentName,
                            'componentId' => $cId,
                            "categories" => $categories
                        ]
                    ];

                    $cNameRoute = $componentName ? '/' . $componentName . '/' : '/';

                    Hooks::apply('[admin' . $cNameRoute . 'categories]', $page);

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

        if (Kernel::config('components/categories', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $id = Security::stringFilter($_POST['protected'] ?? '');

            if ($id)
                Categories::more($id);
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

        if (Kernel::config('components/categories', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $ids = Security::stringFilter($_POST['ids'] ?? '');

            if ($ids) {

                $filtered = Utils::numberFilter($ids, true, false);

                if ($filtered) {

                    $categories = Db::select(Categories::TABLE_NAME, [
                        'ca_id'
                    ], [
                        'ca_id' => $filtered
                    ]);

                    $allow = [];

                    foreach ($categories as $category) {
                        $allow[] = intval($category['ca_id']);
                    }

                    if ($allow) {
                        Categories::delete([
                            'ca_id' => $allow,
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
                'controller' => 'admin/' . $componentName . '/categories',
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

        if (Account::isAdminLevel(Account::LEVEL_MODERATOR) && Kernel::config('components/categories', 'status')) {

            if (Kernel::config('components/' . $componentName, 'status')) {

                $cId = Kernel::config('components/' . $componentName, 'id');

                if ($cId) {
                    $title = Languages::get('controllers', 'admin/categories/edit', 'create_new_category');
                    $status = false;
                    $id = Security::stringFilter($_GET['id'] ?? '');
                    $listParams = [
                        'where' => [
                            'ca_id' => $id,
                            'ca_component_id' => $cId
                        ]
                    ];
                    $category = array();
                    $params = array();
                    $statusTitle = Languages::get('page', 'admin', 'not_active');
                    $parentAttributes = array();
                    $isId = false;

                    if ($id) {

                        $checkPost = Categories::getQuantity($listParams);

                        if ($checkPost) {
                            $listParams['preview'] = true;
                            $title = Languages::get('controllers', 'admin/categories/edit', 'edit_category');
                            $categoryData = Categories::get($listParams);
                            $category = $categoryData['list'][0] ?? array();
                            $params = $categoryData['params'] ?? array();
                            $status = true;
                            $isId = true;

                            if ($category['ca_status'] == 1)
                                $statusTitle = Languages::get('page', 'admin', 'active');
                            else if ($category['ca_status'] == 2)
                                $statusTitle = Languages::get('page', 'admin', 'blocked');

                            $parentAttributes['elementParentId'] =  $category['ca_parent'];
                        }
                    } else {
                        $status = true;
                    }

                    if ($status) {

                        $pmCat = [
                            'limit' => 50,
                            'sortType' => 'ASC',
                            'render' => [
                                'item' => [
                                    'view' => 'controllers/admin/categories/edit/inc/category',
                                    'props' => $parentAttributes
                                ]
                            ],
                            'owner' => true,
                            'changed' => true,
                            'sortByParent' => true,
                            'parentColumn' => 'ca_parent',
                            'parentOrder' => [
                                'ca_id'
                            ],
                            'childOrder' => [
                                'ca_id',
                            ],
                            'where' => []
                        ];

                        if ($isId) {
                            $pmCat['where']['ca_id[!]'] = Categories::getDependencies($id);
                        }

                        $pmCat['where']['ca_component_id'] = $cId;

                        $parentCategories = Categories::get($pmCat);

                        View::page(
                            [
                                'name' => 'admin/categories/edit',
                                "title" => $title,
                                "ajax" => true,
                                'props' => [
                                    'parentCategories' => $parentCategories,
                                    'category' => $category,
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
                    $_POST['category'] ?? array(),
                    [
                        'title' => 'escape',
                        'content' => 'html',
                        'keywords' => 'string',
                        'description' => 'string',
                        'seo_name' => 'string',
                        'ca_sort' => 'int',
                        'ca_id' => 'int',
                        'ca_status' => [
                            'type' => 'int',
                            'allowed' => [0, 1, 2]
                        ],
                        'ca_parent' => 'int'
                    ]
                );

                $title =  $filter->get('title');
                $seo_name = $filter->get('seo_name');
                $ca_id = $filter->get('ca_id', 0);
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
                } elseif (!empty($seo_name) && !Seo::isSeoNameFormat($seo_name)) {
                    $result = [
                        'success' => false,
                        'content' => Languages::get('errors', 'incorrect_seo_name_format'),
                        'status' => 'error'
                    ];
                } elseif (
                    !empty($seo_name) && !Seo::isSeoNameWritePermissions($seo_name, Categories::COMPONENT_ID, $ca_id)
                ) {
                    $result = [
                        'success' => false,
                        'content' => Languages::get('errors', 'this_seo_name_is_busy'),
                        'status' => 'error'
                    ];
                } elseif ($errorUpload) {
                    $result = $response;
                } else {

                    $data = $filter->getAll();

                    if (!empty($data)) {

                        $redirectId = 0;
                        $dependencies = Categories::getDependencies($ca_id);

                        $parent = Db::get(Categories::TABLE_NAME, ['ca_id'], [
                            'ca_id' => $data['ca_parent'],
                            'ca_component_id' => $cId
                        ]);

                        if (empty($parent['ca_id']) || $parent['ca_id'] == $ca_id || in_array($parent['ca_id'], $dependencies)) {
                            $data['ca_parent'] = 0;
                        }

                        Hooks::add('Core.Modify.Edit::save.ids', function ($ids, $params) use (&$redirectId) {
                            if ($params['componentName'] == Categories::COMPONENT_NAME)
                                $redirectId = $ids[0] ?? 0;
                        });

                        $data['ca_component_id'] = $cId;

                        $status = Categories::edit(
                            $data,
                            [
                                'id' => $ca_id
                            ]
                        );

                        if ($status) {

                            if ($delete_image) {
                                Categories::deleteImage([
                                    'bindType' => 0,
                                    'bindId' => $redirectId
                                ]);
                            }

                            if ($redirectId) {

                                if ($statusUpload) {

                                    Categories::deleteImage([
                                        'bindType' => 0,
                                        'bindId' => $redirectId
                                    ]);

                                    $uploadResult = Categories::uploadImage([
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
                                'redirect' => Url::link('admin/' . $componentName . '/categories/edit', ['id' => $redirectId])
                            ];
                        }
                    }
                }
            }
        }

        Json::response($result);
    }
}
