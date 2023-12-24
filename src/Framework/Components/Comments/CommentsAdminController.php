<?php

namespace Energy\Components\Comments;

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

class CommentsAdminController
{

    /**
     * Request to display the following items
     */

    public static function requestMore(): void
    {
        if (Kernel::config('components/comments', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {
            $id = Security::stringFilter($_POST['protected'] ?? '');

            if ($id)
                Comments::more($id);
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

        if (Kernel::config('components/comments', 'status') && Account::isAdminLevel(Account::LEVEL_MANAGER)) {

            $ids = Security::stringFilter($_POST['ids'] ?? '');

            if ($ids) {

                $filtered = Utils::numberFilter($ids, true, false);

                if ($filtered) {

                    $comments = Db::select(Comments::TABLE_NAME, [
                        'cm_id'
                    ], [
                        'cm_id' => $filtered
                    ]);

                    $allow = [];

                    foreach ($comments as $comment) {
                        $allow[] = intval($comment['cm_id']);
                    }

                    if ($allow) {
                        Comments::delete([
                            'cm_id' => $allow,
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
     * Element Editing Page
     * @param string The name of the included component
     */

    public static function edit(string $componentName): void
    {

        if (Account::isAdminLevel(Account::LEVEL_MODERATOR) && Kernel::config('components/comments', 'status')) {
            if (Kernel::config('components/' . $componentName, 'status')) {
                $cId = Kernel::config('components/' . $componentName, 'id');

                if ($cId) {
                    $title = Languages::get('controllers', 'admin/comments/edit', 'create_new_comment');
                    $status = false;
                    $id = Security::stringFilter($_GET['id'] ?? '');
                    $listParams = [
                        'where' => [
                            'cm_id' => $id,
                            'cm_component_id' => $cId
                        ]
                    ];
                    $comment = array();
                    $params = array();
                    $statusTitle = Languages::get('page', 'admin', 'not_active');
                    $isId = false;

                    if ($id) {
                        $checkPost = Comments::getQuantity($listParams);

                        if ($checkPost) {
                        }
                    } else {
                        $status = true;
                    }

                    if ($status) {
                        $filters = array();
                        Url::setFilter('сm_status', [0, 1], $filters);
                        $comments = Comments::get([
                            'filters' => $filters,
                            'allowSearch' => true,
                            'search' => $_GET['search'] ?? '',
                            'orderAllowedColumns' => [
                                'cm_id',
                            ],
                            'orderBy' => Security::stringFilter($_GET['orderBy'] ?? 'cm_id'),
                            'sortType' => Security::stringFilter($_GET['sortType'] ?? 'DESC'),

                            'render' => [
                                'item' => [
                                    'view' => 'controllers/admin/comments/inc/comment'
                                ]
                            ],
                            'owner' => true
                        ]);

                        $page = [
                            'title' =>  $title,
                            'ajax' => true,
                            'props' => [
                                'comment' => $comment,
                                'params' => $params,
                                'statusTitle' => $statusTitle,
                            ]
                        ];

                        View::page($page);
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
                    $_POST['comment'] ?? array(),
                    [
                        'title' => 'escape',
                        'content' => 'html',
                        'keywords' => 'string',
                        'description' => 'string',
                        'seo_name' => 'string',
                        'cm_sort' => 'int',
                        'cm_id' => 'int',
                        'cm_status' => [
                            'type' => 'int',
                            'allowed' => [0, 1, 2]
                        ],
                        'cm_parent' => 'int'
                    ]
                );

                $title =  $filter->get('title');
                $seo_name = $filter->get('seo_name');
                $cm_id = $filter->get('cm_id', 0);
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
                    !empty($seo_name) && !Seo::isSeoNameWritePermissions($seo_name, Comments::COMPONENT_ID, $cm_id)
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
                        $dependencies = Comments::getDependencies($cm_id);

                        $parent = Db::get(Comments::TABLE_NAME, ['cm_id'], [
                            'cm_id' => $data['cm_parent'],
                            'cm_component_id' => $cId
                        ]);

                        if (empty($parent['cm_id']) || $parent['cm_id'] == $cm_id || in_array($parent['cm_id'], $dependencies)) {
                            $data['cm_parent'] = 0;
                        }

                        Hooks::add('Core.Modify.Edit::save.ids', function ($ids, $params) use (&$redirectId) {
                            if ($params['componentName'] == Comments::COMPONENT_NAME)
                                $redirectId = $ids[0] ?? 0;
                        });

                        $data['cm_component_id'] = $cId;

                        $status = Comments::edit(
                            $data,
                            [
                                'id' => $cm_id
                            ]
                        );

                        if ($status) {


                            if ($redirectId) {

                                if ($statusUpload) {



                                    if (!empty($uploadResult) && !$uploadResult['success']) {
                                        $result = $uploadResult;
                                    }
                                }
                            }

                            $result = [
                                'success' => true,
                                'content' => Languages::get('common', 'data_saved'),
                                'status' => 'success',
                                'redirect' => Url::link('admin/' . $componentName . '/comments/edit', ['id' => $redirectId])
                            ];
                        }
                    }
                }
            }
        }

        Json::response($result);
    }
}
