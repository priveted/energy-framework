<?php

namespace Energy\Components\FileManager;

use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Db;
use Energy\Hooks;
use Energy\Kernel;
use Energy\Files;
use Energy\Images;

class FileManager
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'file_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'files';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'file_';


    /**
     * Component name
     * @var string
     */
    public const COMPONENT_NAME = 'fileManager';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 12;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'file_id',            // int AI
        'file_url',           // text
        'file_path',          // text
        'file_component_id',  // int
        'file_bind_id',       // int
        'file_bind_type',     // int
        'file_bind_helper',   // int
        'file_type',          // int
        'file_timestamp',     // int
        'file_owner',         // int
        'file_server_id'      // int
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */
    public const ORDER_TABLE_COLUMS = [
        'file_id',            // int AI
        'file_component_id',  // int
        'file_timestamp'     // int
    ];


    /**
     * Get files list
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
                'file_url',
                'file_path'
            )
        );

        $params = array_merge($defParams, $params);

        Hooks::add('Core.List.Data::get.prepare', function ($preQuery, $tableName, $params, &$whereData) {
            if ($params['componentId'] == self::COMPONENT_ID)
                $whereData['file_bind_type'] = self::COMPONENT_ID;
        });

        return Data::get($params);
    }


    /**
     * Get the number of files
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
     * Editing file data
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
     * Upload a file
     * @param array Basic parameters
     */

    public static function upload(array $params = array()): array
    {
        $result = array();

        $defParams = [
            'method' => 1,
            'path' => Files::generatePath('st'),
            'multiple' => false,
            'encryptName' => true,
            'componentId' => self::COMPONENT_ID,
            'bindId' => false,
            'bindType' => self::COMPONENT_ID,
            'cropRequired' => false,
            'resize' => [
                'width' => Kernel::config('components/fileManager', 'imageResizeMaxWidth'),
                'height' => Kernel::config('components/fileManager', 'imageResizeMaxHeight')
            ]
        ];

        $params = array_merge($defParams, $params);

        if (!$params['method']) {

            $result = Images::upload($params);
        } else {
            $result = Files::manager('upload', $params);
        }


        return  $result;
    }

    /**
     * Deleting file data
     * @param array Where
     */

    public static function delete(array $params = array())
    {
        $status = false;

        if ($params) {
            Hooks::apply('Components.FileManager::delete.pre',  $params, $status);

            $r = Files::manager('delete', $params);

            if ($r)
                $status = true;
        }

        Hooks::apply('Components.FileManager::delete.post', $ids, $params, $status);

        return $status;
    }
}
