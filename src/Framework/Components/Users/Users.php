<?php

namespace Energy\Components\Users;

use Energy\Account;
use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Db;
use Energy\Hooks;
use Energy\Files;
use Energy\Images;
use Energy\Kernel;
use Energy\Core\Modify\Delete;

class Users
{


    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'user_id';


    /**
     * Name of the database table
     * @var string
     */
    public const TABLE_NAME = 'users';


    /**
     * Component name
     * @var string
     */
    public const COMPONENT_NAME = 'users';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 1;


    /**
     * Get users list
     * @param array Basic parameters
     */

    public static function get(array $params = array()): mixed
    {

        $defParams = array(
            'tableName' => self::TABLE_NAME,
            'tableColumnId' => self::TABLE_COLUMN_ID,
            'tableColumns' => Account::ALL_TABLE_COLUMS,
            'componentId' => self::COMPONENT_ID,
            'componentName' => self::COMPONENT_NAME,
            'orderAllowedColumns' => Account::ORDER_TABLE_COLUMS,
            'searchByTableColumns' => array(
                'user_username',
                'user_firstname',
                'user_lastname',
                'user_system_uid',
                'user_email',
            ),
            'preview' => true
        );

        $params = array_merge($defParams, $params);
        return Data::get($params);
    }


    /**
     * Get the number of users
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
     * Editing user data
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
                'tableColumns' => Account::ALL_TABLE_COLUMS
            )
        );

        return Edit::save($data, $params);
    }


    /**
     * Deleting user data
     * @param array Where
     */

    public static function delete(array $where = array()): bool
    {

        return Delete::delete([
            'componentName' => self::COMPONENT_NAME,
            'componentId' => self::COMPONENT_ID,
            'tableName' => self::TABLE_NAME,
            'tableColumnId' => self::TABLE_COLUMN_ID,
            'where' => $where
        ], function ($ids) {
            self::deleteImage([
                'bindId' => $ids,
                'owner' => $ids
            ]);
        });
    }

    /**
     * The function of detecting outdated database elements for the cleaner to work (Hook 'Cleaner::clean')
     * @param array Data about tables and quantity
     */

    public static function clean(&$data): void
    {
        $tid = self::TABLE_COLUMN_ID;
        $tname = self::TABLE_NAME;
        $ids = [];
        $users = Db::select($tname, [$tid], [
            'AND' => [
                'user_deleted' => 1,
                'user_deleted_timestamp[<]' => time()
            ],
            'LIMIT' => 500
        ]);

        if ($users) {

            foreach ($users as $user) {
                $ids[] = $user[$tid];
            }

            if ($ids) {

                $data['count'] = $data['count'] + count($users);
                $data['list'][$tname] = count($users);

                self::delete(['user_id' => $ids]);

                Hooks::apply('Components.Users::clean', $ids, $tname, $tid);
            }
        }
    }


    /**
     * Upload an image
     * @param array Basic parameters
     */

    public static function uploadImage(array $params = array()): array
    {

        $defParams = [
            'path' => Files::generatePath('ai'),
            'multiple' => false,
            'encryptName' => true,
            'componentId' => self::COMPONENT_ID,
            'bindId' => Account::id(),
            'bindType' => 0,
            'cropRequired' => true,
            'resize' => [
                'width' => Kernel::config('components/users', 'imageResizeMaxWidth'),
                'height' => Kernel::config('components/users', 'imageResizeMaxHeight')
            ],
            'thumbnail' => true,
            'thumbnailResize' => [
                'width' => Kernel::config('components/users', 'imageResizeThumbnailMaxWidth'),
                'height' => Kernel::config('components/users', 'imageResizeThumbnailMaxHeight')
            ]
        ];

        $params = array_merge($defParams, $params);

        return Images::upload($params);
    }


    /**
     * Delete an image
     * @param array Basic parameters
     */
    public static function deleteImage(array $params = array())
    {

        $defParams = [
            'componentId' => self::COMPONENT_ID,
        ];

        $params = array_merge($defParams, $params);

        return Images::delete($params);
    }
}
