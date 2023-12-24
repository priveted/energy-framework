<?php

namespace Energy\Components\Comments;

use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Core\Modify\Delete;
use Energy\Hooks;
use Energy\Languages;
use Energy\Security;
use Energy\Account;


class Comments
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'cm_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'comments';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'cm_';


    /**
     * Component name
     * @var string
     */
    public const COMPONENT_NAME = 'comments';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 3;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'cm_id',
        'cm_owner',
        'cm_content',
        'cm_timestamp',
        'cm_status',
        'cm_bind_id',
        'cm_component_id',
        'cm_reply_to'
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */
    public const ORDER_TABLE_COLUMS = [
        'cm_id',
        'cm_timestamp',
        'cm_status',
        'cm_reply_to'
    ];


    /**
     * Get comments list
     * @param array Basic parameters
     */

    public static function get(array $params = array()): mixed
    {

        $defParams = array(
            'tableName' => self::TABLE_NAME,
            'tableColumnId' => self::TABLE_COLUMN_ID,
            'tableColumns' => self::ALL_TABLE_COLUMS,
            'tableColumnPrefix' => self::TABLE_COLUMN_PREFIX,
            'componentId' => self::COMPONENT_ID,
            'componentName' => self::COMPONENT_NAME,
            'orderAllowedColumns' => self::ORDER_TABLE_COLUMS,
            'searchByTableColumns' => array(
                'cm_content',
            )
        );

        $params = array_merge($defParams, $params);


        return Data::get($params);
    }


    /**
     * Get the number of comments
     * @param array Basic parameters
     */

    public static function getQuantity(array $params = array()): int
    {

        return self::get(
            array_merge(
                $params,
                ['quantityOnly' => true]
            )
        );
    }


    /**
     * Show more from the general list
     * @param string ID of the session with data
     * @param array Basic parameters of the data list
     */
    public static function more(string $id, array $params = array()): void
    {

        More::response($id, function ($saved) {
            return self::get($saved);
        }, $params);
    }


    /**
     * Editing comment data
     * @param array Data for changes
     * @param array Basic parameters (Core\Modify\Edit:save(..., $params))
     */

    public static function edit(array $data = array(), array $params = array()): mixed
    {

        $params = array_merge(
            $params,
            array(
                'componentId' => self::COMPONENT_ID,
                'componentName' => self::COMPONENT_NAME,
                'tableName' => self::TABLE_NAME,
                'tableColumnId' => self::TABLE_COLUMN_ID,
                'tableColumns' => self::ALL_TABLE_COLUMS,
                'tableColumnPrefix' => self::TABLE_COLUMN_PREFIX,
                'owner' => true
            )
        );

        return Edit::save($data, $params);
    }


    /**
     * Ajax editing comment data
     * @param array Data for changes
     * @param array Basic parameters (Core\Modify\Edit:save(..., $params))
     */

    public static function ajaxModifyComment(array $data = array(), $params = array()): array
    {
        $result = [
            'success' => false,
            'content' => Languages::get('errors', 'access_denied'),
            'status' => 'error'
        ];

        if (Account::authorized()) {

            $filter = Security::sanitize(
                $data,
                [
                    'cm_content' => 'escape',
                    'cm_reply_to' => 'int',
                    'cm_bind_id' => 'int',
                    'cm_component_id' => 'int',
                    'cm_id' => 'int'
                ]
            );

            $cm_id = $filter->get('cm_id', 0);
            $cm_bind_id = $filter->get('cm_bind_id', 0);
            $cm_content = $filter->get('cm_content', '');
            $cm_reply_to = $filter->get('cm_reply_to', 0);
            $cm_component_id = $filter->get('cm_component_id', 0);
            $defParams = array(
                'id' => $cm_id,
                'protected' => ''
            );

            $params = array_merge($defParams, $params);

            if (empty($cm_content) || !$cm_component_id || !$cm_bind_id) {

                $result = [
                    'success' => false,
                    'content' => Languages::get('errors', 'required_fields'),
                    'status' => 'error'
                ];
            } else {

                $refId = 0;

                Hooks::addOnce('Core.Modify.Edit::save.ids', function ($ids) use (&$refId) {
                    if (isset($ids[0]))
                        $refId = $ids[0];
                });

                $status = self::edit(
                    [
                        'cm_content' =>  $cm_content,
                        'cm_bind_id' => $cm_bind_id,
                        'cm_reply_to' => $cm_reply_to,
                        'cm_component_id' => $cm_component_id
                    ],
                    $params
                );

                if ($status) {
                    if (!empty($params['protected']) && $dataParams = Data::getParams($params['protected'])) {

                        if ($dataParams && $refId) {

                            $dataParams['limit'] = 1;
                            $dataParams['paramsId'] = '';

                            $dataParams['render']['item']['props'] = array_merge($dataParams['render']['item']['props'], [
                                'isNewComment' => true
                            ]);

                            if (!isset($dataParams['where']))
                                $dataParams['where'] = array();

                            $dataParams['where'] = array_merge($dataParams['where'], [
                                'cm_id' => $refId
                            ]);

                            $new = self::get($dataParams);

                            if ($new && isset($new['content']['list'])) {
                                $result = [
                                    'success' => true,
                                    'content' => $new['content']['list'],
                                    'insertType' => $new['params']['insertType'] ?? 'beforeEnd',
                                    'parent' => $cm_reply_to,
                                    'id' => $refId
                                ];
                            }
                        }
                    } else {
                        $result = [
                            'success' => true,
                            'content' => Languages::get('common', 'data_saved'),
                            'status' => 'success',
                        ];
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Deleting comment data
     * @param array Where
     */

    public static function delete(array $where = array()): bool
    {

        Hooks::add('Components.' . ucfirst(self::COMPONENT_NAME) . '::delete.ids', function (&$ids) {
            $ids = self::getDependencies($ids);
        });

        return Delete::delete([
            'componentName' => self::COMPONENT_NAME,
            'componentId' => self::COMPONENT_ID,
            'tableName' => self::TABLE_NAME,
            'tableColumnId' => self::TABLE_COLUMN_ID,
            'where' => $where
        ]);
    }


    /**
     * Get all parent dependencies
     * @param mixed Parent Ids
     */

    public static function getDependencies($ids): array
    {
        return Data::getDependencies($ids, self::TABLE_NAME, self::TABLE_COLUMN_ID, 'cm_reply_to');
    }
}
