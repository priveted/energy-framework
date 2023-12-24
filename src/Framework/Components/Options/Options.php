<?php

namespace Energy\Components\Options;

use Energy\Core\List\More;
use Energy\Core\List\Data;
use Energy\Core\Modify\Edit;
use Energy\Core\Modify\Delete;
use Energy\Languages;
use Energy\Hooks;
use Energy\Kernel;
use Energy\Security;
use Energy\Db;

class Options
{

    /**
     * Id auto-increment of the database table 
     * @var string
     */

    public const TABLE_COLUMN_ID = 'option_id';


    /**
     * Name of the database table
     * @var string
     */

    public const TABLE_NAME = 'options';


    /**
     * Table column prefix
     * @var string
     */

    public const TABLE_COLUMN_PREFIX = 'option_';


    /**
     * Component name
     * @var string
     */

    public const COMPONENT_NAME = 'options';


    /**
     * Component ID
     * @var string
     */

    public const COMPONENT_ID = 7;


    /**
     * Full table structure
     * @var array
     */

    public const ALL_TABLE_COLUMS = [
        'option_id',                 // int AI
        'option_status',             // int
        'option_sort',             // int
        'option_owner',              // int
        'option_timestamp',          // int
        'option_changed',            // int
        'option_changed_timestamp',  // int
        'option_type',               // int
        'option_component_id'        // int
    ];


    /**
     * Allow ordering of table columns
     * @var array
     */

    public const ORDER_TABLE_COLUMS = [
        'option_id',                 // int AI
        'option_sort',               // int
        'option_timestamp',          // int
        'option_changed_timestamp',  // int
        'option_status'              // int
    ];


    /**
     * Name of the relationship key database table
     * @var string
     */

    public const TABLE_NAME_KEYS = 'option_keys';


    /**
     * Get options list
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
            'useElementKeys' => false,
            'elementKeysComponentId' => false,
            'elementKeysBindId' => false,
            'elementParams' => array()
        );

        $params = array_merge($defParams, $params);

        if ($params['useElementKeys']) {

            $optionKeysJoin =  [];

            $cId = $params['elementKeysComponentId'];
            $bId = $params['elementKeysBindId'];

            if ($cId || $bId)
                if (empty($optionKeysJoin['AND']))
                    $optionKeysJoin['AND'] = array();

            if ($cId)
                $optionKeysJoin['AND']['optionKeys.ok_component_id'] = $cId;

            if ($bId)
                $optionKeysJoin['AND']['optionKeys.ok_bind_id'] = $bId;

            $optJoin = [
                self::TABLE_COLUMN_ID => 'ok_option_id'
            ];

            $optJoin = array_merge($optJoin, $optionKeysJoin);

            if (empty($params['mergeSelect']))
                $params['mergeSelect'] = array();

            if (empty($params['join']))
                $params['join'] = array();

            $params['join'] = array_merge($params['join'], [
                '[><]option_keys (optionKeys)' => $optJoin
            ]);

            $params['mergeSelect'] = array_merge($params['mergeSelect'], [
                'optionKeys.ok_bind_id',
                'optionKeys.ok_component_id',
            ]);

            $params['distinct'] = true;

            Hooks::addOnce('Core.List.Data::get.list', function (&$list, $ids, $params) use ($optionKeysJoin) {

                if ($params['componentId'] == self::COMPONENT_ID) {

                    if ($ids) {

                        $oeJoin = [
                            OptionElements::TABLE_COLUMN_ID => 'ok_oe_id'
                        ];

                        $optionKeysJoin['AND']['optionKeys.ok_option_id'] = $ids;
                        $oeJoin = array_merge($oeJoin, $optionKeysJoin);

                        $elementParams = [
                            'mergeSelect' => ['optionKeys.ok_option_id'],
                            'join' =>  [
                                '[><]option_keys (optionKeys)' =>  $oeJoin
                            ],
                            'limit' => false
                        ];

                        $elementParams = array_merge($params['elementParams'], $elementParams);
                        $elements = OptionElements::get($elementParams);
                    }

                    if (!empty($elements['list'])) {

                        $tmpResult = $list;

                        foreach ($tmpResult as $key => $val) {
                            foreach ($elements['list'] as $elem) {
                                if ($val['option_id'] == $elem['ok_option_id']) {
                                    if (empty($list[$key]['elements']))
                                        $list[$key]['elements'] = array();

                                    $list[$key]['elements'][] = $elem;
                                }
                            }
                        }
                    }
                }
            });
        }

        return Data::get($params);
    }


    /**
     * Get the number of options
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

    public static function edit(array $data = array(), $params = array()): mixed
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
     * Deleting option data
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

            self::deleteKeys(false, false, $ids);

            Languages::deleteDb([
                'key' => $ids,
                'component_id' => self::COMPONENT_ID,
            ]);
        });
    }


    /**
     * Attach an array of options and elements to a component
     * @param array List of component elements
     * @param array Component Binding IDs
     * @param array Component Parameters
     */

    public static function attachToComponent(array &$componentList, array $componentBindIds, array &$componentParams, array &$componentContent = array()): void
    {

        if (
            Kernel::config('components/options', 'status') &&
            in_array($componentParams['componentName'] ?? 0, Kernel::config('components/options', 'allowForComponents'))
        ) {
            if (isset($componentParams['useOptions']) && $componentParams['useOptions'] && !empty($componentList)) {

                $defParams = [
                    'useElementKeys' => true,
                    'elementKeysComponentId' => $componentParams['componentId'],
                    'elementKeysBindId' => $componentBindIds,
                    'elementParams' => array(
                        'where' => [
                            'oe_status' => 1
                        ]
                    ),
                    'where' => [
                        'option_status' => 1
                    ]
                ];

                $optionParams = array_merge($defParams, ($componentParams['optionParams'] ?? array()));
                $options = Options::get($optionParams);

                if (!empty($options['list'])) {

                    $tmpList = $componentList;
                    foreach ($tmpList as $key => $val) {
                        foreach ($options['list'] as $option) {
                            if (!empty($option['ok_bind_id']) && $val[$componentParams['tableColumnId']] == $option['ok_bind_id']) {

                                if (empty($componentList[$key]['options']))
                                    $componentList[$key]['options'] = array();

                                $componentList[$key]['options'][] = $option;
                            }
                        }
                    }

                    $componentParams['options'] = $options['params'] ?? array();
                    $componentContent['options'] = $options['content'] ?? array();
                }
            }
        }
    }


    /**
     * Create a binding key
     * @param int Component Id
     * @param string|int Bind Id
     * @param string|int Option Id
     * @param string|int Option Element Id
     */

    public static function сreateBindingKey($componentId, $bindId, $optionId, $optionElementId): bool
    {
        $result = false;

        $pdo = Db::insert(self::TABLE_NAME_KEYS, [
            'ok_bind_id' => $bindId,
            'ok_option_id' => $optionId,
            'ok_oe_id' => $optionElementId,
            'ok_component_id' => $componentId
        ]);

        if ($pdo->rowCount())
            $result = true;

        return $result;
    }


    /**
     * Deleting keys
     * @param int Component Id
     * @param string|int  Bind Id
     * @param mixed  Option Id
     * @param mixed  Option Element Id
     */

    public static function deleteKeys($componentId = false, $bindId = false, $optionId = false, $optionElementId = false): bool
    {
        $result = false;
        $where = [];

        if ($componentId !== false)
            $where['ok_component_id'] = $componentId;

        if ($bindId !== false)
            $where['ok_bind_id'] = $bindId;

        if ($optionId !== false)
            $where['ok_option_id'] = $optionId;

        if ($optionElementId !== false)
            $where['ok_oe_id'] = $optionElementId;


        $pdo =  Db::delete(self::TABLE_NAME_KEYS, $where);

        if ($pdo->rowCount())
            $result = true;

        return $result;
    }


    /**
     * Saving only for changes
     * @param int|string Component Id
     * @param int|string Component Id
     */

    public static function saveData($componentId, $bindId): bool
    {

        $result = false;

        if (!empty($_POST['optionData']) && is_array($_POST['optionData'])) {
            $data = $_POST['optionData'];
            $safeData = array();
            $allowed =  ['default', 'delete', 'create'];


            if (!empty($_POST['optionTempDelete']) && $_POST['optionTempDelete']) {
                $tmp = $_POST['optionTempDelete'];

                foreach ($tmp as $del) {
                    $id = Security::stringFilter($del);
                    $isOption = !!self::getQuantity([
                        'where' => [
                            'option_id' => $id
                        ]
                    ]);

                    if ($isOption) {
                        self::deleteKeys($componentId, $bindId, $id);
                    }
                }
            }

            if (!empty($_POST['optionKeysTempDelete']) && $_POST['optionKeysTempDelete']) {
                $kTmp = $_POST['optionKeysTempDelete'];

                foreach ($kTmp as $key => $del) {

                    $key = Security::stringFilter($key);

                    if ($key) {
                        $isOption = !!self::getQuantity([
                            'where' => [
                                'option_id' => $key
                            ]
                        ]);

                        if ($isOption && is_array($del)) {
                            foreach ($del as $el) {
                                $el = Security::stringFilter($el);
                                if ($el)
                                    self::deleteKeys($componentId, $bindId, $key, $el);
                            }
                        }
                    }
                }
            }

            foreach ($data as $key => $item) {
                if (is_array($item)) {

                    $type = Security::sanitize($item, [
                        'type' => [
                            'type' => 'string',
                            'allowed' => $allowed,
                            'default' => 'default'
                        ]
                    ]);

                    $type = $type->get('type');

                    if ($type != 'default') {
                        $safeData[$key] = array(
                            'type' => $type
                        );
                    }

                    if (!empty($item['list'])) {
                        foreach ($item['list'] as $k => $el) {
                            if (!empty($el)) {

                                $eType = Security::stringFilter($el);

                                if ($eType != 'default' && in_array($eType, $allowed)) {

                                    if (empty($safeData[$key]))
                                        $safeData[$key]['type'] = 'create';

                                    if (empty($safeData[$key]['create']))
                                        $safeData[$key]['create'] = array();

                                    if (empty($safeData[$key]['delete']))
                                        $safeData[$key]['delete'] = array();

                                    $safeData[$key][$eType][] = $k;
                                }
                            }
                        }
                    } else
                        unset($safeData[$key]);
                }
            }

            foreach ($safeData as $key => $item) {

                if (!empty($item['type'])) {

                    $isOption = !!self::getQuantity([
                        'where' => [
                            'option_id' => $key
                        ]
                    ]);

                    if ($isOption) {

                        if ($item['type'] === 'delete') {
                            self::deleteKeys($componentId, $bindId, $key);
                        } else {

                            if (!empty($item['delete'])) {
                                foreach ($item['delete'] as $k => $el) {

                                    $isOptionElement = !!OptionElements::getQuantity([
                                        'where' => [
                                            'oe_id' => $el
                                        ]
                                    ]);

                                    if ($isOptionElement) {
                                        self::deleteKeys($componentId, $bindId, $key, $el);
                                    }
                                }
                            }

                            if (!empty($item['create'])) {
                                foreach ($item['create'] as $k => $el) {

                                    $isOptionElement = !!OptionElements::getQuantity([
                                        'where' => [
                                            'oe_id' => $el
                                        ]
                                    ]);

                                    if ($isOptionElement) {
                                        self::сreateBindingKey($componentId, $bindId, $key, $el);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
