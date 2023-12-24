<?php

namespace Energy\Components\Posts;

use Energy\Components\Categories\Categories;
use Energy\Components\Comments\Comments;
use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Db;
use Energy\Hooks;
use Energy\Files;
use Energy\Core\Modify\Delete;
use Energy\Images;
use Energy\Kernel;
use Energy\Languages;
use Energy\Seo;

class Posts
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'post_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'posts';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'post_';


    /**
     * Component name
     * @var string
     */
    public const COMPONENT_NAME = 'posts';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 2;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'post_id',                   // int AI
        'post_views',                // int
        'post_timestamp',            // int
        'post_changed_timestamp',    // int
        'post_sort',                 // int
        'post_status',               // int
        'post_owner',                // int
        'post_changed',              // int
        'post_allow_comments',       // int
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */
    public const ORDER_TABLE_COLUMS = [
        'post_id',
        'post_views',
        'post_timestamp',
        'post_changed_timestamp',
        'post_sort',
        'post_status',
        'post_allow_comments'
    ];


    /**
     * Get posts list
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
            'allowSeoName' => true
        );

        $params = array_merge($defParams, $params);
        return Data::get($params);
    }


    /**
     * Get the number of posts
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
     * Deleting post data
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

            Categories::deleteKeys(self::COMPONENT_ID, $ids);

            self::deleteImage([
                'bindId' => $ids
            ]);

            Languages::deleteDb([
                'key' => $ids,
                'component_id' => self::COMPONENT_ID,
            ]);

            Seo::deleteSeoName([
                'componentId' => self::COMPONENT_ID,
                'bindId' => $ids
            ]);

            Comments::delete([
                'cm_component_id' => self::COMPONENT_ID,
                'cm_bind_id' => $ids
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
            'path' => Files::generatePath('ps'),
            'multiple' => false,
            'encryptName' => true,
            'componentId' => self::COMPONENT_ID,
            'bindId' => false,
            'bindType' => 0,
            'cropRequired' => false,
            'resize' => [
                'width' => Kernel::config('components/posts', 'imageResizeMaxWidth'),
                'height' => Kernel::config('components/posts', 'imageResizeMaxHeight')
            ],
            'thumbnail' => true,
            'thumbnailResize' => [
                'width' => Kernel::config('components/posts', 'imageResizeThumbnailMaxWidth'),
                'height' => Kernel::config('components/posts', 'imageResizeThumbnailMaxHeight')
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


    /**
     * if comments are allowed
     * @param int|string Post Id
     */

    public static function isCommentsAllowed($id): bool
    {

        return !!Db::count(self::TABLE_NAME, [
            'post_id' => $id,
            'post_allow_comments' => 1
        ]);
    }


    /**
     * Update the number of views
     * @param array Post Id
     */

    public static function updateViews(int $id): bool
    {

        $result = true;

        $pdo = Db::update(self::TABLE_NAME, [
            "post_views[+]" => 1
        ], [
            'post_id' => $id
        ]);

        if ($pdo->rowCount())
            $result = true;

        return $result;
    }
}
