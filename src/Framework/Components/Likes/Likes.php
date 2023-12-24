<?php

namespace Energy\Components\Likes;

use Energy\Account;
use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Db;
use Energy\Core\Modify\Delete;

class Likes
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'like_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'likes';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'like_';


    /**
     * Component name
     * @var string
     */
    public const COMPONENT_NAME = 'likes';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 15;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'like_id',            // int AI
        'like_component_id',  // int
        'like_bind_id',       // int
        'like_owner',         // int
        'like_timestamp',     // int
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */

    public const ORDER_TABLE_COLUMS = [
        'like_id',        // int AI
        'like_timestamp',  // int
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
            'orderAllowedColumns' => self::ORDER_TABLE_COLUMS
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
     * Deleting data about likes
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
     * Add like
     * @param int Bind Id
     * @param int Component Id
     */

    public static function like(int $bindId, int $componentId, int $owner = 0): bool
    {
        $status = false;

        $where = array(
            'like_component_id' => $componentId,
            'like_bind_id' => $bindId,
            'like_owner' => $owner ? $owner : Account::id(),
        );

        $count = Db::count(self::TABLE_NAME, $where);

        if ($count)
            $status = self::delete($where);
        else {
            $where['like_timestamp'] = time();
            $pdo = Db::insert(self::TABLE_NAME, $where);

            if ($pdo->rowCount())
                $status = true;
        }

        return $status;
    }


    /**
     * Select the number of likes in the general query
     * @param array Basic parameters
     */

    public static function selectQuantity(array &$params = array()): void
    {

        if (empty($params['mergeSelect']))
            $params['mergeSelect'] = array();

        if (isset($params['tableColumnId']) && isset($params['componentId'])) {
            $params['mergeSelect'] = array_merge($params['mergeSelect'], [
                'like_count' => Db::raw("(SELECT COUNT(<like_id>) FROM " . self::TABLE_NAME . " WHERE <{$params['tableColumnId']}> = <likes.like_bind_id> AND <like_component_id> = {$params['componentId']}" . ")")
            ]);
        }
    }


    /**
     * Select if the user has put a like in the general request
     * @param array Basic parameters
     */

    public static function selectUserLike(array &$params = array()): void
    {

        if (empty($params['mergeSelect']))
            $params['mergeSelect'] = array();

        if (isset($params['tableColumnId']) && isset($params['componentId'])) {
            $params['mergeSelect'] = array_merge($params['mergeSelect'], [
                'user_like' => Db::raw("(SELECT COUNT(<like_id>) FROM " . self::TABLE_NAME . " WHERE <{$params['tableColumnId']}> = <likes.like_bind_id> AND <like_owner> = " . Account::id() . " AND <like_component_id> = {$params['componentId']}" . ")")
            ]);
        }
    }
}
