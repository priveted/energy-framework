<?php

namespace Energy\Components\Devices;

use Energy\Account;
use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Db;
use Energy\Hooks;
use Energy\Core\Modify\Delete;

class Devices
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'id';


    /**
     * Name of the database table
     * @var string
     */
    public const TABLE_NAME = 'user_devices';


    /**
     * Component name
     * @var string
     */

    public const COMPONENT_NAME = 'devices';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 17;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'id',
        'os',
        'browser',
        'ip',
        'user_id',
        'device_type',
        'timestamp',
        'timestamp_active',
        'key',
        'refresh'
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */

    public const ORDER_TABLE_COLUMS = [
        'os',
        'browser',
        'device_type',
        'timestamp',
        'timestamp_active'
    ];


    /**
     * Allow search by table columns
     * @var array
     */

    public const SEARCH_TABLE_COLUMS = [
        'os',
        'browser',
        'ip',
        'device_type'
    ];


    /**
     * Get users list
     * @param array Basic parameters
     */

    public static function get(array $params = array()): mixed
    {

        $defParams = array(
            'tableName' => self::TABLE_NAME,
            'tableColumnId' => self::TABLE_COLUMN_ID,
            'tableColumns' => self::ALL_TABLE_COLUMS,
            'componentId' => self::COMPONENT_ID,
            'componentName' => self::COMPONENT_NAME,
            'orderAllowedColumns' => self::ORDER_TABLE_COLUMS,
            'searchByTableColumns' => self::SEARCH_TABLE_COLUMS
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
     * Deleting user devices data
     * @param array Where
     */

    public static function delete(array $where = array()): bool
    {
        //  vprint($where);
        return Delete::delete([
            'componentName' => self::COMPONENT_NAME,
            'componentId' => self::COMPONENT_ID,
            'tableName' => self::TABLE_NAME,
            'tableColumnId' => self::TABLE_COLUMN_ID,
            'where' => $where
        ]);
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
        $devices = Db::select($tname, [$tid], ['timestamp_active[<]' => time()]);

        if ($devices) {

            foreach ($devices as $device) {
                $ids[] = $device[$tid];
            }

            if ($ids) {

                $data['count'] = $data['count'] + count($device);
                $data['list'][$tname] = count($device);

                self::delete(['id' => $ids]);

                Hooks::apply('Components.Devices::clean', $ids, $tname, $tid);
            }
        }
    }
}
