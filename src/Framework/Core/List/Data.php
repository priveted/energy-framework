<?php

namespace Energy\Core\List;

use Energy\Controllers;
use Energy\Session;
use Energy\Security;
use Energy\Db;
use Energy\Encryption;
use Energy\Files;
use Energy\Hooks;
use Energy\Languages;
use Energy\View;
use Energy\Cache;
use Energy\Utils;

class Data
{


    /**
     * Block ID Counter
     * @var int
     */

    private static int $blocks = 0;


    /**
     * Scheme of parameters
     * @var array
     */

    const PARAMS_SCHEME = array(

        /* Table params */
        'tableName' => '',                            // (string) Name of the table in the database
        'tableColumnId' => 'id',                      // (string) ID of the database table
        'tableColumns' => array(),                    // (array) List of columns in the table

        /* If used for a component, 
           you must specify the id */
        'componentId' => 0,                           // (int) Component Id
        'componentName' => '',                        // (string) Component Name

        /* Search */
        'allowSearch' => false,                       // (bool) Allow search
        'search' => '',                               // (string) Search phrase
        'searchMinLength' => 3,                       // (int) Minimum number of search characters
        'searchMinLengthStatus' => false,             // (bool) If the number of search characters corresponds to the minimum value
        'searchMaxLength' => 20,                      // (int) Maximum number of search characters
        'searchMaxLengthStatus' => false,             // (bool) If the number of search characters corresponds to the maximum value
        'searchStatus' => false,                      // (bool) Search query status
        'searchByTableColumns' => array(),            // (array) Searchable columns of database tables

        /* Language */
        'allowLanguage' => false,                     // (bool) Allow the use of language values
        'title' => true,                              // (bool) Use the names of the language values
        'content' => true,                            // (bool) Use language value content
        'description' => true,                        // (bool) Use Description
        'keywords' => true,                           // (bool) Use keywords

        /* Seo name */
        'allowSeoName' =>  false,                    // (bool) allow Seo name

        /* Recursive sorting 
           by parent element */
        'sortByParent' => false,                      // (bool) Sorting by parent element
        'parentColumn' => 'parent_id',                // (string) Parent column in the database

        // Parent
        'parentOrder' => array(),                     // (array) Sort order by parent elements
        'childOrder' => array(),                      // (array) Sorting order of child elements
        'pathChar' => 512,                            // (int) Length of the sorting path
        'whereRecursive' => array(),                  // (array) Where recursively


        'quantityOnly' => false,                      // (bool) Get only quantity
        'filters' => array(),                         // (array) WHERE Filters in URL
        'where' => array(),                           // (array) DB syntax
        'whereString' => '',                          // (string) The prepared PDO syntax is recommended (where)
        'preparedData' => array(),                    // (array) Prepared data
        'select' => array(),                          // (array|string) DB Select
        'mergeSelect' => array(),                     // (array|string) DB Merge Select
        'distinct'  => false,                         // (bool) Only 1 element with a similar column 
        'join' => array(),                            // (array) Join syntax
        'orderBy' => 'id',                            // (string) Sort by column
        'orderAllowedColumns' => array(),             // (array) Sorting by columns of the database table
        'reverse' => false,                           // (bool) Reverse result
        'sortType' => 'ASC',                          // (string)Type of sorting
        'limit' => 20,                                // (int) Limit
        '_reservedOffsetLimit' => 0,                  // (int) Private offset reservation
        'paramsId' => '',                             // (string) ID of the session parameters
        'count' => 0,                                 // (int) Total number of elements
        'countInLimit' => 0,                          // (int) The number of elements according to the limit
        'lastId' => 0,                                // (int|string) Last element ID
        'lastProperty' => false,                      // (int|string) Last property

        // Preview
        'preview' => false,                           // (bool) Allow preview
        'previewThumbnail' => false,                  // (bool) Allow thumbnail preview

        // Get user data
        'owner' => false,                             // (bool) Allow to use the owner
        'ownerPreview' => false,                      // (bool) Allow to use the owner's image
        'ownerPreviewThumbnail' => false,             // (bool) Allow to use a thumbnail of the owner's image
        'changed' => false,                           // (bool)
        'changedPreview' => false,                    // (bool)
        'changedPreviewThumbnail' => false,           // (bool)

        // Cache
        'cache' => false,                             // (bool) Caching
        'cacheExpire' => 0,                           // (int) Cache expiration time ()
        '_cacheName' => '',                           // (string) Private name of the cache
        '_cacheId' => 0,                              // (int) Private Cache ID

        // Rendering
        'render' => array(
            'item' => array(
                'view' => '',                         // (string) The path of the element rendering template
                'props' => array(),              // (array) Rendering Attributes
                'extend' => false                     // (bool) Use method parameters when renderin
            ),

            'more' => array(
                'view' => 'widgets/more.button',      // (string) The path of the element rendering template
                'props' => array(),              // (array) Rendering Attributes
                'extend' => false,                    // (bool) Use method parameters when rendering
                'disabled' => false,                  // (bool) Do not use,
                'beforeList' => false,                // (bool) Place the template before the list
                'afterList' => false                 // (bool) Place the template immediately after the list
            ),

            'empty' => array(
                'view' => 'common/empty',             // (string) The path of the element rendering template
                'props' => array(),              // (array) Rendering Attributes
                'extend' => false,                    // (bool) Use method parameters when rendering
                'disabled' => false                   // (bool) Do not use
            )
        ),

        'insertType' => 'beforeEnd'                   // Data output type
    );


    /**
     * Result scheme
     * @var array
     */

    const RESULT_SCHEME = array(
        'list' => array(),
        'params' => array(),
        'content' => array(
            'list' => '',
            'more' => '',
            'empty' => ''
        )
    );


    /**
     * Get data
     * @param array Basic parameters
     */

    public static function get(array $params = array()): mixed
    {

        $result = self::RESULT_SCHEME;
        $params = self::createParams($params);
        $preQuery = '';
        $tableName = '';
        if (empty($params['paramsId']))
            self::$blocks++;

        Hooks::apply('Core.List.Data::get.pre', $params, $result);

        if ($params['tableName'] && $params['tableColumnId'] && $params['tableColumns'] && $params['orderAllowedColumns']) {

            $params['orderBy'] = in_array($params['orderBy'], $params['orderAllowedColumns']) ?  $params['orderBy'] : $params['tableColumnId'];
            $params['sortType'] = in_array(strtoupper($params['sortType']), array('ASC', 'DESC')) ?  strtoupper($params['sortType']) : 'ASC';
            $rMap = [];
            $pMap = [];

            if (!$params['select'])
                $params['select'] = $params['tableColumns'];

            if (!$params['_reservedOffsetLimit'])
                $params['_reservedOffsetLimit'] = $params['limit'];

            if ($params['sortByParent'] && in_array($params['parentColumn'], $params['orderAllowedColumns'])) {

                $parentOrder = "''";
                if (!empty($params['parentOrder']))
                    $parentOrder = Utils::convertToString($params['parentOrder']);

                $childOrder = "''";
                if (!empty($params['childOrder']))
                    $childOrder = Utils::convertToString($params['childOrder'], ['char' => ", '/', ", 'quotes' => ['<c.', '>']]);

                $whereRecursive = '';
                $whereParentRecursive = '';

                if ($params['whereRecursive']) {
                    $wr = [];
                    foreach ($params['whereRecursive'] as $key => $val)
                        $wr['c.' . $key] = $val;

                    $whereRecursive = Db::whereClause($wr, $rMap);
                    $whereParentRecursive = Db::whereClause($params['whereRecursive'], $pMap);
                }

                $parentWh = (!empty($whereParentRecursive)) ? "AND <{$params['parentColumn']}> = 0" : "WHERE <{$params['parentColumn']}> = 0";

                $preQuery = "WITH RECURSIVE <cte_work_table> AS (
                        SELECT *,
                            CAST(CONCAT({$parentOrder}) AS CHAR({$params['pathChar']})) AS <cte_work_path>, 
                            CAST({$params['tableColumnId']} AS CHAR({$params['pathChar']})) AS <cte_id_path>, 
                            0 AS <cte_level>
                            FROM <{$params['tableName']}>
                            {$whereParentRecursive} {$parentWh}
                        UNION SELECT c.*,
                            CONCAT(<cte_work_table.cte_work_path>, '/', {$childOrder}) AS <cte_work_path>,
                            CONCAT(<cte_work_table.cte_id_path>, '/', <c.{$params['tableColumnId']}>) AS <cte_id_path>,
                            <cte_work_table.cte_level>+1
                            FROM <cte_work_table>
                            JOIN <{$params['tableName']}> AS c ON <c.{$params['parentColumn']}> = <cte_work_table.{$params['tableColumnId']}>
                            {$whereRecursive}
                    ) ";

                $params['orderBy'] = 'cte_work_path';
                $tableName = 'cte_work_table';
                $params['select'][] = 'cte_id_path';
                $params['select'][] = 'cte_work_path';
                $params['select'][] = 'cte_level';
            } else
                $tableName = $params['tableName'];

            $whereString = '';
            $operator = $params['sortType'] === 'ASC' ? '>' : '<';

            if ($params['paramsId'] && $params['lastId']) {
                if ($params['orderBy'] == $params['tableColumnId'])
                    $whereString .= " AND <{$params['tableColumnId']}> {$operator} {$params['lastId']}";
                else {
                    $lastProperty = self::filterType($params['lastProperty'] ?? '');
                    $whereString .= ' AND (' . $params['orderBy'] . ',' . $params['tableColumnId'] . ')' . $operator . ' (' . $lastProperty . ',' . $params['lastId'] . ')';
                }
            }

            if ($params['whereString'])
                $whereString .= ' AND ' . $params['whereString'];

            $whereData = array(
                $params['tableColumnId'] . '[>=]' => Db::raw("1 {$whereString}", $params['preparedData']),
                'ORDER' => [
                    $params['orderBy'] => $params['sortType'],
                    $params['tableColumnId'] => $params['sortType']
                ],
                'LIMIT' => $params['limit']
            );

            if ($params['where'])
                $whereData = array_merge($whereData, $params['where']);

            $filtersData = array();

            if ($params['filters']) {

                foreach ($params['filters'] as $key => $filter) {
                    if (is_array($filter)) {

                        $sanitizedFilter = Security::sanitizeFiltersData(
                            $filter,
                            $params['tableColumns']
                        );

                        foreach ($sanitizedFilter as $k => $v) {
                            if (isset($filtersData[$k])) {
                                $filtersData[$k] = array_merge_recursive($filtersData[$k], $v);
                            } else {
                                $filtersData[$k] = $v;
                            }
                        }
                    }
                }

                if ($filtersData) {
                    $filtersData = array(
                        'AND' => [
                            'OR' => $filtersData
                        ]
                    );
                }
            }

            $whereData = array_merge_recursive(
                $whereData,
                $filtersData
            );


            if ($params['allowSeoName']) {

                $params['join'] = array_merge($params['join'], [
                    '[>]seo_names (seo_name)' => [
                        $params['tableColumnId'] => 'bind_id',
                        'AND' => [
                            'seo_name.component_id' => $params['componentId']
                        ]
                    ]
                ]);

                $params['select'] = array_merge($params['select'], [
                    'seo_name.name (seo_name)',
                ]);
            }


            if ($params['owner'] && isset($params['tableColumnPrefix']) && in_array($params['tableColumnPrefix'] . 'owner', $params['tableColumns'])) {

                $params['join'] = array_merge($params['join'], [
                    '[>]users (owner)' => [
                        $params['tableColumnPrefix'] . 'owner' => 'user_id',
                    ]
                ]);

                $ownerData = [
                    'owner.user_firstname (owner_firstname)',
                    'owner.user_lastname (owner_lastname)',
                    'owner.user_username (owner_username)'
                ];

                if ($params['ownerPreview']) {

                    $params['join'] = array_merge($params['join'], [
                        '[>]files (ownerPreview)' => [
                            $params['tableColumnPrefix'] . 'owner' => 'file_bind_id',
                            'AND' => [
                                'ownerPreview.file_component_id' => 1,
                                'ownerPreview.file_type' => Files::FILE_TYPE_IMAGE,
                                'ownerPreview.file_bind_type' => 0,
                                'ownerPreview.file_bind_helper' => 0
                            ]
                        ]
                    ]);

                    $ownerData[] = 'ownerPreview.file_url (owner_preview)';
                }


                if ($params['ownerPreviewThumbnail']) {

                    $params['join'] = array_merge($params['join'], [
                        '[>]files (ownerPreviewThumbnail)' => [
                            $params['tableColumnPrefix'] . 'owner' => 'file_bind_id',
                            'AND' => [
                                'ownerPreviewThumbnail.file_component_id' => 1,
                                'ownerPreviewThumbnail.file_type' => Files::FILE_TYPE_IMAGE,
                                'ownerPreviewThumbnail.file_bind_type' => 0,
                                'ownerPreviewThumbnail.file_bind_helper' => 1
                            ]
                        ]
                    ]);

                    $ownerData[] = 'ownerPreviewThumbnail.file_url (owner_preview_thumbnail)';
                }

                $params['select'] = array_merge($params['select'], $ownerData);
            }

            if ($params['changed'] && isset($params['tableColumnPrefix'])  && in_array($params['tableColumnPrefix'] . 'changed', $params['tableColumns'])) {

                $params['join'] = array_merge($params['join'], [
                    '[>]users (changed)' => [
                        $params['tableColumnPrefix'] . 'changed' => 'user_id',
                    ]
                ]);

                $changedData = [
                    'changed.user_firstname (changed_firstname)',
                    'changed.user_lastname (changed_lastname)',
                    'changed.user_username (changed_username)'
                ];

                if ($params['changedPreview']) {

                    $params['join'] = array_merge($params['join'], [
                        '[>]files (changedPreview)' => [
                            $params['tableColumnPrefix'] . 'changed' => 'file_bind_id',
                            'AND' => [
                                'changedPreview.file_component_id' => 1,
                                'changedPreview.file_type' => Files::FILE_TYPE_IMAGE,
                                'changedPreview.file_bind_type' => 0,
                                'changedPreview.file_bind_helper' => 0
                            ]
                        ]
                    ]);

                    $changedData[] = 'changedPreview.file_url (changed_preview)';
                }

                if ($params['changedPreviewThumbnail']) {

                    $params['join'] = array_merge($params['join'], [
                        '[>]files (changedPreviewThumbnail)' => [
                            $params['tableColumnPrefix'] . 'changed' => 'file_bind_id',
                            'AND' => [
                                'changedPreviewThumbnail.file_component_id' => 1,
                                'changedPreviewThumbnail.file_type' => Files::FILE_TYPE_IMAGE,
                                'changedPreviewThumbnail.file_bind_type' => 0,
                                'changedPreviewThumbnail.file_bind_helper' => 1
                            ]
                        ]
                    ]);

                    $changedData[] = 'changedPreviewThumbnail.file_url (changed_preview_thumbnail)';
                }

                $params['select'] = array_merge($params['select'], $changedData);
            }

            if ($params['preview']) {
                $params['join'] = array_merge($params['join'], [
                    '[>]files (preview)' => [
                        $params['tableColumnId'] => 'file_bind_id',
                        'AND' => [
                            'preview.file_component_id' => $params['componentId'],
                            'preview.file_type' => Files::FILE_TYPE_IMAGE,
                            'preview.file_bind_type' => 0,
                            'preview.file_bind_helper' => 0
                        ]
                    ]
                ]);

                $params['select'] = array_merge($params['select'], [
                    'preview.file_url (preview)',
                ]);
            }

            if ($params['previewThumbnail']) {
                $params['join'] = array_merge($params['join'], [
                    '[>]files (preview_thumbnail)' => [
                        $params['tableColumnId'] => 'file_bind_id',
                        'AND' => [
                            'preview_thumbnail.file_component_id' => $params['componentId'],
                            'preview_thumbnail.file_type' => Files::FILE_TYPE_IMAGE,
                            'preview_thumbnail.file_bind_type' => 0,
                            'preview_thumbnail.file_bind_helper' => 1
                        ]
                    ]
                ]);

                $params['select'] = array_merge($params['select'], [
                    'preview_thumbnail.file_url (preview_thumbnail)',
                ]);
            }

            if ($params['allowLanguage']) {

                Hooks::apply('Core.List.Data::get.language.pre', $params, $whereData);

                if ($params['title']) {

                    $params['join'] = array_merge($params['join'], [
                        '[>]language_values (title)' => [
                            $params['tableColumnId'] => 'key',
                            'AND' => [
                                'title.component_id' => $params['componentId'],
                                'title.type' => 0,
                                'title.lang_id' => Languages::getSelected('id')
                            ]
                        ]
                    ]);

                    $params['select'] = array_merge($params['select'], [
                        'title.value (title)',
                    ]);
                }

                if ($params['content']) {
                    $params['join'] = array_merge($params['join'], [
                        '[>]language_values (content)' => [
                            $params['tableColumnId'] => 'key',
                            'AND' => [
                                'content.component_id' => $params['componentId'],
                                'content.type' => 1,
                                'content.lang_id' => Languages::getSelected('id')
                            ]
                        ],
                    ]);
                    $params['select'] = array_merge($params['select'], [
                        'content.value (content)',
                    ]);
                }

                if ($params['description']) {
                    $params['join'] = array_merge($params['join'], [
                        '[>]language_values (description)' => [
                            $params['tableColumnId'] => 'key',
                            'AND' => [
                                'description.component_id' => $params['componentId'],
                                'description.type' => 2,
                                'description.lang_id' => Languages::getSelected('id')
                            ]
                        ],
                    ]);
                    $params['select'] = array_merge($params['select'], [
                        'description.value (description)',
                    ]);
                }

                if ($params['keywords']) {
                    $params['join'] = array_merge($params['join'], [
                        '[>]language_values (keywords)' => [
                            $params['tableColumnId'] => 'key',
                            'AND' => [
                                'keywords.component_id' => $params['componentId'],
                                'keywords.type' => 3,
                                'keywords.lang_id' => Languages::getSelected('id')
                            ]
                        ],
                    ]);
                    $params['select'] = array_merge($params['select'], [
                        'keywords.value (keywords)',
                    ]);
                }

                Hooks::apply('Core.List.Data::get.language.post', $params, $whereData);
            }

            if ($params['allowSearch'] && !empty($params['search'])) {

                $search = Security::escapeHTML($params['search']);
                $Length = iconv_strlen($search, 'UTF-8');

                if ($Length >= $params['searchMinLength'])
                    $params['searchMinLengthStatus'] = true;

                if ($Length >= $params['searchMaxLength'])
                    $params['searchMaxLengthStatus'] = true;

                if ($Length >= $params['searchMinLength'] &&  $Length <= $params['searchMaxLength']) {

                    if ($params['searchByTableColumns']) {

                        foreach ($params['searchByTableColumns'] as $col) {
                            if (in_array($col, $params['tableColumns']) || in_array($col, $params['searchByTableColumns'])) {

                                if (!isset($whereData['OR']))
                                    $whereData['OR'] = array();

                                $whereData['OR'][$col . '[~]'] = $search;
                                $params['searchStatus'] = true;
                            }
                        }
                    }
                }
            }

            Hooks::apply('Core.List.Data::get.prepare', $preQuery, $tableName, $params, $whereData);

            $params['_cacheName'] = 'com.' . $params['componentId'] . '.' . $params['_cacheId'] . '.' . self::$blocks . '.' . (str_replace('/', '_', Controllers::getRouteString()));
            $params['_cacheId']++;

            if ($params['cache'] && Cache::is($params['_cacheName'] . '.count')) {
                $params['count'] = Cache::get($params['_cacheName'] . '.count');
            } else {

                $countWhere = $whereData;

                if (isset($countWhere['LIMIT']))
                    unset($countWhere['LIMIT']);

                if (isset($countWhere['ORDER']))
                    unset($countWhere['ORDER']);

                $cMap = [];
                $map = [];

                if ($params['whereRecursive']) {
                    foreach ($rMap as $k => $m) {
                        $cMap[$k] = $m;
                        $map[$k] = $m;
                    }

                    foreach ($pMap as $k => $m) {
                        $cMap[$k] = $m;
                        $map[$k] = $m;
                    }
                }

                $distinct = $params['distinct'] ? 'DISTINCT ' : '';

                $select = [
                    'num' => Db::raw("COUNT({$distinct}<{$params['tableColumnId']}>)")
                ];

                $cf = !empty($params['join']) ? $params['join'] : $select;
                $cs = !empty($params['join']) ?  $select : $countWhere;
                $cl = !empty($params['join']) ?  $countWhere : null;
                $cContext = Db::selectContext($tableName, $cMap, $cf, $cs, $cl);
                $params['count'] = Db::query($preQuery . $cContext, Db::prepareMapData($cMap))->fetchColumn();

                if ($params['cache'])
                    Cache::set($params['_cacheName'] . '.count', $params['count'], $params['cacheExpire']);
            }

            if ($params['quantityOnly']) {
                Hooks::apply('Core.List.Data::get.quantity', $params, $whereData);
                $result = $params['count'];
            } else {

                if ($params['cache'] && Cache::is($params['_cacheName'])) {
                    $sql = Cache::get($params['_cacheName']);
                } else {

                    if ($params['mergeSelect'])
                        $params['select'] = array_merge($params['select'], $params['mergeSelect']);

                    if ($params['distinct']) {
                        if (!empty($params['select'][$params['tableColumnId']]))
                            unset($params['select'][$params['tableColumnId']]);

                        $params['select'][] = '@' . $params['tableColumnId'];
                    }


                    $f = !empty($params['join']) ? $params['join'] : $params['select'];
                    $s = !empty($params['join']) ? $params['select'] : $whereData;
                    $l = !empty($params['join']) ?  $whereData : null;

                    $context = Db::selectContext($tableName, $map, $f, $s, $l);
                    $sql = Db::query($preQuery . $context, Db::prepareMapData($map))->fetchAll(\PDO::FETCH_ASSOC);

                    if ($params['cache'])
                        Cache::set($params['_cacheName'], $sql, $params['cacheExpire']);
                }

                if (!$params['paramsId']) {
                    $params['paramsId'] = Encryption::irreversibleUniqid(uniqid());
                }

                if ($sql) {

                    $list = [];
                    $ids = [];

                    foreach ($sql as $key => $item) {

                        if (count($sql) - 1 === $key) {

                            $params['lastId'] = $item[$params['tableColumnId']];

                            if ($params['orderBy'] != $params['tableColumnId']) {

                                if ($params['sortByParent']) {
                                    $params['lastProperty'] = $item['cte_work_path'];
                                } else {
                                    $params['lastProperty'] = $item[$params['orderBy']];
                                }
                            }
                        }
                        $ids[] = $item[$params['tableColumnId']];
                        $list[] = $item;
                    }

                    if ($params['reverse'])
                        $list = array_reverse($list);

                    Hooks::apply('Core.List.Data::get.list', $list, $ids, $params, $result['content']);

                    foreach ($list as $key => $item) {

                        Hooks::apply('Core.List.Data::get.item.pre', $item, $params);

                        if (!empty($params['render']['item']['view']) && View::isExists($params['render']['item']['view'])) {

                            if (!empty($params['render']['item']['props']) &&  is_array($params['render']['item']['props']))
                                $item = array_merge($item, $params['render']['item']['props']);

                            if (!empty($params['render']['item']['extend']) && $params['render']['item']['extend'])
                                $item['data.params'] = $params;

                            $result['content']['list'] .= View::load($params['render']['item']['view'], $item);
                        }

                        $result['list'][] = $item;

                        Hooks::apply('Core.List.Data::get.item.post', $item, $result, $params, $result['content']);
                    }

                    $params['countInLimit'] = count($list);

                    Hooks::apply('Core.List.Data::get.result', $result, $ids, $params);
                }



                if (!$params['render']['empty']['disabled'] && !$params['count']) {
                    if (View::isExists($params['render']['empty']['view']))
                        $result['content']['empty'] = View::load($params['render']['empty']['view'], $params['render']['empty']['props']);
                }

                if (!$params['render']['more']['disabled'] && $params['count'] > $params['limit']) {
                    if (View::isExists($params['render']['more']['view'])) {

                        $attr = array(
                            'url' => Controllers::getRouteString(),
                            'request' => 'more',
                            'paramsId' => $params['paramsId']
                        );

                        if (!empty($params['render']['more']['props']))
                            $attr = array_merge($attr, $params['render']['more']['props']);

                        $result['content']['more'] = View::load($params['render']['more']['view'], $attr);
                    }
                }

                $result['params'] =  $params;
                Session::set('component.params.' . $params['paramsId'], $params);
            }
        }

        Hooks::apply('Core.List.Data::get.post', $result, $params);

        return $result;
    }


    /**
     * Create a parameter schema
     * @param array Basic parameters
     */

    public static function createParams(array $params = array()): array
    {
        return array_replace_recursive(
            self::PARAMS_SCHEME,
            $params
        );
    }


    /**
     * Id of papameters
     * @param array Id of papameters
     */

    public static function getParams(string $id): array
    {
        $result = array();

        if (!empty(Session::get('component.params.' . $id)))
            $result =  Session::get('component.params.' . $id);

        return $result;
    }


    /**
     * Filter by type
     * @param array Data
     */

    private static function filterType(mixed $data): mixed
    {
        $result = $data;

        if (is_string($data))
            $result = "'{$data}'";
        elseif (is_int($data))
            $result = intval($data);

        return $result;
    }


    /**
     * Get all child dependencies
     * @param mixed  Ids
     * @param string Name of the database table
     * @param string Id auto-increment of the database table 
     * @param string Parent column in the table
     * @param bool Use the cache
     * @param int Cache expiration time
     */

    public static function getDependencies(mixed $ids, string $tableName, string $tableColumnId, string $tableColumnParent, $cache = false, $cacheExpire = 0): array
    {
        $resIds = array();

        if (is_array($ids))
            $ids = implode(',', $ids);


        $strId = str_replace(',', '_', $ids);
        $cacheName = 'pdep.' . $strId . $tableName . $tableColumnParent .  (str_replace('/', '_', Controllers::getRouteString()));
        if ($cache && Cache::is($cacheName)) {
            $sql = Cache::get($cacheName);
        } else {
            $sql = Db::query(
                "WITH RECURSIVE <cte> AS (
                     SELECT <{$tableColumnId}> FROM <{$tableName}> WHERE <{$tableColumnId}>IN({$ids})
                     UNION
                     SELECT <a.{$tableColumnId}> FROM <{$tableName}> a JOIN <cte> 
                         ON <a.{$tableColumnParent}> = <cte.{$tableColumnId}>
                     )
                     SELECT * FROM <cte>
                 "
            )->fetchAll(\PDO::FETCH_ASSOC);

            Cache::set($cacheName, $sql, $cacheExpire);
        }
        if ($sql)
            foreach ($sql as $item)
                if (!in_array($item[$tableColumnId], $resIds))
                    $resIds[] = $item[$tableColumnId];

        return $resIds;
    }


    /**
     * Get all parent dependencies
     * @param int Element ID
     * @param string Name of the database table
     * @param string Id auto-increment of the database table 
     * @param string Parent column in the table
     * @param bool Use the cache
     * @param int Cache expiration time
     */

    public static function getParentDependencies(int $id, string $tableName, string $tableColumnId, string $tableColumnParent, $cache = false, $cacheExpire = 0): array
    {
        $resIds = array();

        $cacheName = 'pdep.' . $id . $tableName . $tableColumnParent .  (str_replace('/', '_', Controllers::getRouteString()));
        if ($cache && Cache::is($cacheName)) {
            $sql = Cache::get($cacheName);
        } else {
            $sql = Db::query(
                "WITH RECURSIVE <cte> ({$tableColumnId}, {$tableColumnParent}, level) AS (
                    SELECT <{$tableColumnId}>, {$tableColumnParent}, 1 level
                    FROM <{$tableName}>
                    WHERE {$tableColumnId} = {$id}
                    union all
                    SELECT t.{$tableColumnId}, t.{$tableColumnParent}, level + 1
                    FROM <{$tableName}> t INNER JOIN <cte> x
                    ON <t.{$tableColumnId}> = <x.{$tableColumnParent}>
                  )
                  SELECT * FROM <cte>;
             "
            )->fetchAll(\PDO::FETCH_ASSOC);

            Cache::set($cacheName, $sql, $cacheExpire);
        }

        if ($sql)
            foreach ($sql as $item)
                if (!in_array($item[$tableColumnId], $resIds))
                    $resIds[] = $item[$tableColumnId];

        return $resIds;
    }
}
