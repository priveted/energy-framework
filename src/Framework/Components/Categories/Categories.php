<?php

namespace Energy\Components\Categories;

use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Db;
use Energy\Hooks;
use Energy\Files;
use Energy\Images;
use Energy\Kernel;
use Energy\Languages;
use Energy\Seo;
use Energy\Core\Modify\Delete;
use Energy\Utils;

class Categories
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'ca_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'categories';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'ca_';


    /**
     * Component name
     * @var string
     */
    public const COMPONENT_NAME = 'categories';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 4;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'ca_id',                 // int AI
        'ca_parent',             // int
        'ca_sort',               // int
        'ca_timestamp',          // int
        'ca_changed_timestamp',  // int
        'ca_owner',              // int
        'ca_changed',            // int
        'ca_status',             // int
        'ca_component_id'        // int
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */

    public const ORDER_TABLE_COLUMS = [
        'ca_id',                 // int AI
        'ca_parent',             // int
        'ca_sort',               // int
        'ca_timestamp',          // int
        'ca_changed_timestamp',  // int
        'ca_status'              // int
    ];


    /**
     * Name of the category key table
     * @var string
     */

    public const TABLE_NAME_CATEGORY_KEYS = 'category_keys';


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
                'title.value',
                'content.value'
            ),
            'allowLanguage' => true,
            'allowSeoName' => true,
            'childrenCount' => false,
        );

        $params = array_merge($defParams, $params);

        if ($params['childrenCount']) {
            Hooks::addOnce('Core.List.Data::get.prepare', function ($preQuery, $tableName, &$params) {
                $params['select']  = array_merge(
                    $params['select'],
                    [
                        'childrens' => Db::raw("(SELECT COUNT(<A.ca_id>) FROM <{$tableName}> A WHERE <A.ca_parent> = <{$tableName}.ca_id>)")
                    ]
                );
            });
        }

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
     * Editing post data
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
                'allowSeoName' => true,
                'changed' => true,
                'owner' => true
            )
        );

        return Edit::save($data, $params);
    }


    /**
     * Get all child dependencies
     * @param mixed Parent Ids
     * @param bool Use the cache
     * @param int Cache expiration time
     */

    public static function getDependencies($ids, bool $cache = false, int $cacheExpire = 0): array
    {
        return Data::getDependencies($ids, self::TABLE_NAME, self::TABLE_COLUMN_ID, 'ca_parent', $cache, $cacheExpire);
    }


    /**
     * Get all parent dependencies
     * @param int|string Element Id
     * @param bool Use the cache
     * @param int Cache expiration time
     */

    public static function getParentDependencies($id, bool $cache = false, int $cacheExpire = 0): array
    {
        return Data::getParentDependencies(intval($id), self::TABLE_NAME, self::TABLE_COLUMN_ID, 'ca_parent', $cache, $cacheExpire);
    }


    /**
     * Get keys
     * @param int Component Id
     * @param string|int Bind Id
     * @param string|int Category Id
     */

    public static function getKeys($componentId, $bindId, $categoryId = false): array
    {
        $ids = array();

        $where = [
            'ck_bind_id' => $bindId,
            'ck_component_id' => $componentId
        ];

        if ($categoryId !== false)
            $where['ck_ca_id'] = $categoryId;

        $sql = Db::select(self::TABLE_NAME_CATEGORY_KEYS, ['ck_ca_id'], $where);

        if ($sql)
            foreach ($sql as $item)
                if (!in_array($item['ck_ca_id'], $ids))
                    $ids[] = $item['ck_ca_id'];

        return $ids;
    }


    /**
     * Deleting keys
     * @param int Component Id
     * @param string|int  Bind Id
     * @param mixed  Category Id
     */

    public static function deleteKeys($componentId = false, $bindId = false, $categoryId = false): bool
    {
        $result = false;
        $where = [];

        if ($componentId !== false)
            $where['ck_component_id'] = $componentId;

        if ($bindId !== false)
            $where['ck_bind_id'] = $bindId;

        if ($categoryId !== false)
            $where['ck_ca_id'] = $categoryId;

        $pdo =  Db::delete(self::TABLE_NAME_CATEGORY_KEYS, $where);

        if ($pdo->rowCount())
            $result = true;

        return $result;
    }


    /**
     * Create a binding key
     * @param int Component Id
     * @param string|int  Bind Id
     * @param mixed  Category Id
     */

    public static function сreateBindingKey($componentId, $bindId, $categoryId): bool
    {
        $result = false;

        $pdo = Db::insert(self::TABLE_NAME_CATEGORY_KEYS, [
            'ck_bind_id' => $bindId,
            'ck_ca_id' => $categoryId,
            'ck_component_id' => $componentId
        ]);

        if ($pdo->rowCount())
            $result = true;

        return $result;
    }


    /**
     * Create multiple keys for component elements
     * @param int Component Id
     * @param int Bind Id
     * @param array|int|string Bind Id
     */

    public static function createMultipleKeys(int $componentId, int $bindId, $keys): void
    {
        $catIds = [];
        if (!empty($keys)) {
            if (is_array($keys) || is_int($keys)) {
                $filtered = $keys;
            } else {
                $filtered = Utils::numberFilter($keys, true, false);
            }

            if ($filtered) {
                $cats = Db::select(self::TABLE_NAME, ['ca_id'], [
                    'ca_id' => $filtered,
                    'ca_component_id' => $componentId
                ]);

                foreach ($cats as $cat) {
                    $catIds[] = $cat['ca_id'];
                }
            }

            self::deleteKeys($componentId, $bindId);

            if ($catIds) {
                foreach ($catIds as $cid) {
                    self::сreateBindingKey($componentId, $bindId, $cid);
                }
            }
        }
    }

    /**
     * Combining keys by component element
     * @param array Component parameter data
     * @param array Basic parameters
     */

    public static function combiningKeys(array $data, array $params): array
    {

        $defParams = array(
            'componentId' => 0,        // @param int Component Id
            'categoryId' => false,     // @param bool|int Category Id
            'primaryKeyColumn' => '',  // @param string Primary key column
            'filters' => array()       // @param array Filters
        );

        $params = array_merge($defParams, $params);

        if (empty($data['join']))
            $data['join'] = array();

        $f = [
            'category.ck_component_id' => $params['componentId']
        ];

        if ($params['categoryId'])
            $f['category.ck_ca_id'] = self::getDependencies($params['categoryId'], true);

        $f = array_merge($f, $params['filters']);

        $data['join'] = array_merge($data['join'], [
            '[><]category_keys (category)' => [
                $params['primaryKeyColumn'] => 'ck_bind_id',
                'AND' => $f
            ]
        ]);

        return $data;
    }


    /**
     * Deleting post data
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
        ], function ($ids, $params, $status) {

            self::deleteImage([
                'bindId' => $ids
            ]);

            self::deleteKeys(false, false, $ids);

            Languages::deleteDb([
                'key' => $ids,
                'component_id' => self::COMPONENT_ID,
            ]);

            Seo::deleteSeoName([
                'componentId' => self::COMPONENT_ID,
                'bindId' => $ids
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
            'path' => Files::generatePath('ca'),
            'multiple' => false,
            'encryptName' => true,
            'componentId' => self::COMPONENT_ID,
            'bindId' => false,
            'bindType' => 0,
            'cropRequired' => false,
            'resize' => [
                'width' => Kernel::config('components/categories', 'imageResizeMaxWidth'),
                'height' => Kernel::config('components/categories', 'imageResizeMaxHeight')
            ],
            'thumbnail' => true,
            'thumbnailResize' => [
                'width' => Kernel::config('components/categories', 'imageResizeThumbnailMaxWidth'),
                'height' => Kernel::config('components/categories', 'imageResizeThumbnailMaxHeight')
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
            'componentId' => self::COMPONENT_ID,
        ];

        $params = array_merge($defParams, $params);

        return Images::delete($params);
    }
}
