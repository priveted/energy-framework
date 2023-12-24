<?php

namespace Energy\Components\Dependencies;

use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Db;
use Energy\Hooks;
use Energy\Core\Modify\Delete;


class Dependencies
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'dep_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'dependencies';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'dep_';


    /**
     * Component name
     * @var string
     */

    public const COMPONENT_NAME = 'dependencies';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 14;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'dep_id',                // int AI
        'dep_name',              // int varchar(100)
        'dep_description',       // text
        'dep_status',            // int
        'dep_component_id',      // int
        'dep_owner',             // int
        'dep_timestamp',         // int
        'dep_changed',           // int
        'dep_changed_timestamp'  // int
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */

    public const ORDER_TABLE_COLUMS = [
        'dep_id',                 // int AI
        'dep_timestamp',          // int
        'dep_changed_timestamp',  // int
        'dep_status'              // int
    ];


    /**
     * Name of the dependence key table
     * @var string
     */

    public const TABLE_NAME_DEPENDENCE_KEYS = 'dependence_keys';


    /**
     * Id auto-increment of the database table (DK)
     * @var string
     */

    public const TABLE_DEPENDENCE_KEYS_COLUMN_ID = 'dk_id';

    public const ALL_TABLE_COLUMS_DEPENDENCE_KEYS = [
        'dk_id',            // int AI
        'dk_name',          // varchar(100)
        'dk_dep_id',        // int
        'dk_component_id',  // int
        'dk_bind_id',       // int
        'dk_sort'           // int
    ];



    /**
     * Get categories list
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
                'dep_name',
                'dep_description'
            )
        );

        $params = array_merge($defParams, $params);

        return Data::get($params);
    }


    /**
     * Get the number of categories
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
     * Editing dependency data
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
                'owner' => true,
                'changed' => true
            )
        );

        return Edit::save($data, $params);
    }


    /**
     * Deleting Dependency component data
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
            self::deleteKeys($ids);
        });
    }


    /**
     * Check the format of the dependency name
     * @param string Name
     */

    public static function isDependenceNameFormat(string $name): bool
    {
        return !is_numeric($name) && preg_match('/^[a-zA-Z0-9_ \s]+$/', $name);
    }


    /**
     * Get keys
     * @param array Basic parameters
     */

    public static function getKeys(array $params = array()): array
    {
        $defParams = array(
            'tableName' => self::TABLE_NAME_DEPENDENCE_KEYS,
            'tableColumnId' => self::TABLE_DEPENDENCE_KEYS_COLUMN_ID,
            'tableColumns' => self::ALL_TABLE_COLUMS_DEPENDENCE_KEYS,
            'tableColumnPrefix' => 'dk_',
            'componentId' => self::COMPONENT_ID,
            'orderAllowedColumns' => [
                'dk_id'
            ]
        );

        $params = array_merge($defParams, $params);

        return Data::get($params);
    }


    /**
     * Create a binding key
     * @param array Where syntax
     */

    public static function сreateBindingKey(array $where = array()): bool
    {
        $result = false;

        $defWhere = array(
            'dk_name' => '',        // string
            'dk_dep_id' => 0,       // int
            'dk_component_id' => 0, // int
            'dk_bind_id' => 0,      // int
            'dk_sort' => 0          // int
        );

        $where = array_merge($defWhere, $where);
        $pdo = Db::insert(self::TABLE_NAME_DEPENDENCE_KEYS, $where);

        if ($pdo->rowCount())
            $result = true;

        return $result;
    }


    /**
     * Deleting keys
     * @param array|string|int Dependence Ids
     * @param array Where syntax
     */

    public static function deleteKeys($dependenceIds, array $where = array()): bool
    {
        $result = false;

        $whereData = [
            'dk_dep_id' => $dependenceIds
        ];

        $where = array_merge($whereData, $where);

        $pdo =  Db::delete(self::TABLE_NAME_DEPENDENCE_KEYS, $where);

        if ($pdo->rowCount())
            $result = true;

        return $result;
    }


    /**
     * Join the table
     * @param string Dependence Name
     * @param int|string Element Id
     */

    public static function join(string $dependenceName, $elId, array &$paramsData, array $params = array()): array
    {

        $defParams = array(
            'sortByPosition' => true
        );

        $params = array_merge($defParams, $params);

        if (empty($paramsData['join']))
            $paramsData['join'] = array();

        if (empty($paramsData['mergeSelect']))
            $paramsData['mergeSelect'] =  array();

        $paramsData['mergeSelect'] = ['dk_sort'];

        if ($params['sortByPosition']) {

            if (empty($paramsData['orderAllowedColumns']))
                $paramsData['orderAllowedColumns'] = array();

            $paramsData['orderAllowedColumns'][] = 'dk_sort';
            $paramsData['orderBy'] =  'dk_sort';
        }

        $paramsData['join'] = array_merge($paramsData['join'], [
            '[><]dependence_keys (depKey)' => [
                $elId => 'dk_bind_id',
                'AND' => [
                    'dk_name' => $dependenceName
                ]
            ]
        ]);

        return $paramsData;
    }
}
