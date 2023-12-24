<?php

namespace Energy\Components\Favorites;

use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Delete;
use Energy\Account;
use Energy\Kernel;
use Energy\Db;

class Favorites
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'fav_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'favorites';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'fav_';


    /**
     * Component name
     * @var string
     */
    public const COMPONENT_NAME = 'favorites';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 5;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'fav_id',            // int AI
        'fav_component_id',  // int
        'fav_bind_id',       // int
        'fav_owner',         // int
        'fav_timestamp'      // int
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */
    public const ORDER_TABLE_COLUMS = [
        'fav_id',         // int AI
        'fav_timestamp',  // int
    ];


    /**
     * Get favorite list
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
            'orderAllowedColumns' => self::ORDER_TABLE_COLUMS
        );

        $params = array_merge($defParams, $params);

        return Data::get($params);
    }


    /**
     * Get the number of favorites
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
     * Add or remove from favorites
     * @param int Bind Id
     * @param int Component Id
     */

    public static function add(int $bindId, int $componentId, int $owner = 0): bool
    {
        $status = false;

        $where = array(
            'fav_component_id' => $componentId,
            'fav_bind_id' => $bindId,
            'fav_owner' => $owner ? $owner : Account::id(),
        );

        $count = Db::count(self::TABLE_NAME, $where);

        if ($count)
            $status = self::delete($where);
        else {
            $where['fav_timestamp'] = time();
            $pdo = Db::insert(self::TABLE_NAME, $where);

            if ($pdo->rowCount())
                $status = true;
        }

        return $status;
    }


    /**
     * Deleting comment data
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
        ]);
    }


    /**
     * If the item is in favorites
     * @param array Basic parameters
     */

    public static function isUserFavorite(array &$params = array()): void
    {
        if (
            Account::authorized() &&
            Kernel::config('components/' . self::COMPONENT_NAME, 'status') &&
            in_array($params['componentName'] ?? 0, Kernel::config('components/' . self::COMPONENT_NAME, 'allowForComponents'))
        ) {

            if (!isset($params['isUserFavorite']) || !empty($params['isUserFavorite'])) {

                if (empty($params['mergeSelect']))
                    $params['mergeSelect'] = array();

                if (isset($params['tableColumnId']) && isset($params['componentId'])) {
                    $params['mergeSelect'] = array_merge($params['mergeSelect'], [
                        'is_user_favorite' => Db::raw("(SELECT COUNT(<" . self::TABLE_COLUMN_ID . ">) FROM " . self::TABLE_NAME . " WHERE <{$params['tableColumnId']}> = <favorites.fav_bind_id> AND <fav_owner> = " . Account::id() . " AND <fav_component_id> = {$params['componentId']}" . ")")
                    ]);
                }
            }
        }
    }


    /**
     * Join a component
     * @param array Component Parameters
     * @param string ID of the table column
     * @param array Basic parameters
     */

    public static function join(array $componentParams, $tableColumnId, array $params = array()): array
    {

        $defParams = array(
            'owner' => 0
        );

        $params = array_merge($defParams, $params);

        if (empty($componentParams['join']))
            $componentParams['join'] = array();

        $joinParams = [
            $tableColumnId => 'fav_bind_id',
            'AND' => [
                'fav_owner' => $params['owner'] ? $params['owner'] : Account::id()
            ]
        ];

        $componentParams['join'] = array_merge($componentParams['join'], [
            '[><]' . self::TABLE_NAME . ' (fav)' => $joinParams
        ]);

        return $componentParams;
    }
}
