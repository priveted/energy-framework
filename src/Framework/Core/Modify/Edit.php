<?php

namespace Energy\Core\Modify;

use Energy\Account;
use Energy\Db;
use Energy\Hooks;
use Energy\Security;
use Energy\Languages;
use Energy\Seo;

class Edit
{


    /**
     * Saving component data
     * @param array Data to write to the database
     * @param array Basic parameters
     */

    public static function save(array $data = array(), array $params = array()): mixed
    {

        $status = false;

        $defParams = array(

            // Component Id
            'componentId' => 0,

            // Table Parameters
            'tableName' => '',
            'tableColumnId' => 'id',
            'tableColumns' => array(),
            'tableColumnPrefix' => '',

            // Record the owner's data and time when creating
            'owner' => false,

            // Record data and change time
            'changed' => false,

            // Allow to save seo names
            'allowSeoName' => false,

            'id' => 0, // int|array
            'where' => array(),

            // The creation of the element will be allowed if the ID is not found or not specified
            'allowCreate' => true,

            // Allow language values to be used
            'allowLanguage' => false
        );

        $params = array_merge($defParams, $params);

        Hooks::apply('Core.Modify.Edit::save.pre', $data, $params, $status);

        if ($params['tableName'] && !empty($params['tableColumns']) && is_array($params['tableColumns'])) {

            $where = array_merge($params['where'], array(
                $params['tableColumnId'] => $params['id']
            ));

            Hooks::apply('Core.Modify.Edit::save.where', $where, $params, $data);

            $ids = array();
            $listIds = Db::select($params['tableName'], $params['tableColumnId'], $where);
            $protectedData = Security::allowedData($data, $params['tableColumns']);
            $newData = $protectedData;

            if ($params["allowLanguage"]) {

                if (isset($data['title']))
                    $newData['title'] = $data['title'];

                if (isset($data['content']))
                    $newData['content'] = $data['content'];

                if (isset($data['description']))
                    $newData['description'] = $data['description'];

                if (isset($data['keywords']))
                    $newData['keywords'] = $data['keywords'];
            }

            Hooks::apply('Core.Modify.Edit::save.params', $params, $newData, $protectedData, $status);


            if ($newData) {
                if (!$params['tableColumns'])
                    $params['tableColumns'] = [];

                // Create element
                if ($params['allowCreate'] && !$listIds) {

                    if ($params['owner']) {
                        self::setColumnData('owner', Account::id(), $protectedData, $params);
                        self::setColumnData('timestamp', time(), $protectedData, $params);
                    }

                    if ($params['changed']) {
                        self::setColumnData('changed', Account::id(), $protectedData, $params);
                        self::setColumnData('changed_timestamp', time(), $protectedData, $params);
                    }

                    if (Db::insert($params['tableName'], $protectedData)) {
                        $ids = array(Db::id());
                        $status = true;
                    }
                } else {

                    // Edit element
                    if ($protectedData) {

                        if ($params['changed']) {
                            self::setColumnData('changed', Account::id(), $protectedData, $params);
                            self::setColumnData('changed_timestamp', time(), $protectedData, $params);
                        }

                        Db::update($params['tableName'], $protectedData, $where);
                    }

                    $status = true;
                    $ids = $listIds;
                }
            }

            if ($ids) {

                Hooks::apply('Core.Modify.Edit::save.ids', $ids, $params, $status);

                foreach ($ids as $item) {

                    Hooks::apply('Core.Modify.Edit::save.item.pre', $item, $params, $status);

                    if ($params["allowLanguage"] && $params['componentId']) {

                        if (array_key_exists('title', $data)) {
                            $title = $data['title'];
                            if (empty($title))
                                Languages::deleteDb([
                                    'key' => $item,
                                    'component_id' => $params['componentId'],
                                    'type' => Languages::TYPE_TITLE
                                ]);
                            else {
                                Languages::setDb([
                                    "key" => $item,
                                    "value" => $title,
                                    "type" => Languages::TYPE_TITLE,
                                    "component_id" => $params['componentId']
                                ]);
                            }
                        }

                        if (array_key_exists('content', $data)) {
                            $content = $data['content'];
                            if (empty($content))
                                Languages::deleteDb([
                                    'key' => $item,
                                    'component_id' => $params['componentId'],
                                    'type' => Languages::TYPE_CONTENT
                                ]);
                            else {
                                Languages::setDb([
                                    "key" => $item,
                                    "value" => $content,
                                    "type" => Languages::TYPE_CONTENT,
                                    "component_id" => $params['componentId']
                                ]);
                            }
                        }

                        if (array_key_exists('description', $data)) {
                            $description = $data['description'];
                            if (empty($description))
                                Languages::deleteDb([
                                    'key' => $item,
                                    'component_id' => $params['componentId'],
                                    'type' => Languages::TYPE_DESCRIPTION
                                ]);
                            else {
                                Languages::setDb([
                                    "key" => $item,
                                    "value" => $description,
                                    "type" => Languages::TYPE_DESCRIPTION,
                                    "component_id" => $params['componentId']
                                ]);
                            }
                        }

                        if (array_key_exists('keywords', $data)) {
                            $keywords = $data['keywords'];
                            if (empty($keywords))
                                Languages::deleteDb([
                                    'key' => $item,
                                    'component_id' => $params['componentId'],
                                    'type' => Languages::TYPE_KEYWORDS
                                ]);
                            else {
                                Languages::setDb([
                                    "key" => $item,
                                    "value" => $keywords,
                                    "type" => Languages::TYPE_KEYWORDS,
                                    "component_id" => $params['componentId']
                                ]);
                            }
                        }

                        Hooks::apply('Core.Modify.Edit::save.item.post', $item, $params, $status);
                    }

                    if ($params["allowSeoName"] && $params['componentId']) {
                        if (array_key_exists('seo_name', $data)) {
                            $seoName =  $data['seo_name'];

                            if (empty($seoName)) {
                                Seo::deleteSeoName([
                                    'componentId' => $params['componentId'],
                                    'bindId' => $item
                                ]);
                            } else
                                Seo::setSeoName($seoName,  $params['componentId'], $item);
                        }
                    }
                }
            }
        }

        Hooks::apply('Core.Modify.Edit::save.post', $data, $params, $status);

        return $status;
    }


    /**
     * Set additional column data
     * @param string Column key
     * @param mixed Column value
     * @param array Protected data
     * @param array Parameters
     */

    private static function setColumnData(string $key, mixed $value, array &$data = array(), array $params = array())
    {
        if (in_array($params['tableColumnPrefix'] . $key, $params['tableColumns'])) {
            $data[$params['tableColumnPrefix'] . $key] = $value;
        }
    }
}
