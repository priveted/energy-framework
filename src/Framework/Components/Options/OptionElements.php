<?php

namespace Energy\Components\Options;

use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Core\Modify\Delete;
use Energy\Languages;
use Energy\Kernel;
use Energy\Files;
use Energy\Images;


class OptionElements
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'oe_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'option_elements';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'oe_';


    /**
     * Component name
     * @var string
     */
    public const COMPONENT_NAME = 'optionElements';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 8;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'oe_id',                 // int AI
        'oe_owner',              // int
        'oe_sort',               // int
        'oe_timestamp',          // int
        'oe_changed',            // int
        'oe_changed_timestamp',  // int
        'oe_status',             // int
        'oe_component_id'        // int
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */

    public const ORDER_TABLE_COLUMS = [
        'oe_id',                 // int AI
        'oe_sort',               // int
        'oe_timestamp',          // int
        'oe_changed_timestamp',  // int
        'oe_status'              // int
    ];


    /**
     * Get option element list
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
                'title.value',
                'description.value'
            ),
            'allowLanguage' => true,
            'keywords' => false,
            'content' => false,
            'limit' => false
        );

        $params = array_merge($defParams, $params);
        return Data::get($params);
    }


    /**
     * Get the number of option elements
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
     * Editing option data
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
                'allowLanguage' => true,
                'changed' => true,
                'owner' => true
            )
        );

        return Edit::save($data, $params);
    }


    /**
     * Deleting option elements data
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
        ], function ($ids, $params, $status) {

            Options::deleteKeys(false, false, false, $ids);

            self::deleteImage([
                'bindId' => $ids
            ]);

            Languages::deleteDb([
                'key' => $ids,
                'component_id' => self::COMPONENT_ID,
            ]);
        });
    }


    /**
     * Upload an image
     * @param array Basic parameters
     */

    public static function uploadImage(array $params = array()): array
    {

        $defParams = [
            'path' => Files::generatePath('oe'),
            'multiple' => false,
            'encryptName' => true,
            'componentId' => self::COMPONENT_ID,
            'bindId' => false,
            'bindType' => 0,
            'cropRequired' => false,
            'resize' => [
                'width' => Kernel::config('components/optionElements', 'imageResizeMaxWidth'),
                'height' => Kernel::config('components/optionElements', 'imageResizeMaxHeight')
            ]
        ];

        $params = array_merge($defParams, $params);

        return Images::upload($params);
    }


    /**
     * Delete an image
     * @param array Basic parameters
     */

    public static function deleteImage(array $params = array()): array
    {

        $defParams = [
            'componentId' => self::COMPONENT_ID
        ];

        $params = array_merge($defParams, $params);

        return Images::delete($params);
    }
}
