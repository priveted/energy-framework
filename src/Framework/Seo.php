<?php

namespace Energy;

class Seo
{
    /**
     * Name of the database table
     * @var string
     */
    private const SEO_TABLE_NAME = 'seo_names';


    /**
     * If the name is reserved
     * @param string Name
     */

    public static function isReservedName(string $name)
    {
        return !!Db::count('reserved_names', ['name' => $name]);
    }


    /**
     * If the seo name exists
     * @param string Name
     * @param int|string Component Id
     */

    public static function isSeoName(string $name, $componentId): bool
    {
        return !!Db::count(self::SEO_TABLE_NAME, [
            'name' => $name,
            'component_id' => $componentId
        ]);
    }


    /**
     * Get seo name data
     * @param string Name
     * @param int|string Component Id
     */

    public static function getSeoNameData(string $name, $componentId): array
    {
        $result = array();

        if (self::isSeoName($name, $componentId))
            $result = Db::get(self::SEO_TABLE_NAME, '*', [
                'name' => $name,
                'component_id' => $componentId
            ]);

        return $result;
    }


    /**
     * Delete seo name
     * @param array Deletion Parameters
     */

    public static function deleteSeoName(array $params = array()): bool
    {
        $result = false;

        if (!empty($params['componentId']) && !empty($params['bindId'])) {

            $pdo = Db::delete(self::SEO_TABLE_NAME, [
                'component_id' => $params['componentId'],
                'bind_id' => $params['bindId']
            ]);

            if ($pdo->rowCount())
                $result = true;
        }

        return $result;
    }


    /**
     * Set seo name
     * @param string Name
     * @param int|string Component Id
     * @param int|string Bind Id
     */

    public static function setSeoName(string $name, $componentId, $bindId): bool
    {

        $result = false;
        $name = Security::stringFilter($name);

        if (self::isSeoNameFormat($name)) {

            $is = !!Db::count(self::SEO_TABLE_NAME, [
                'component_id' => $componentId,
                'bind_id' => $bindId,
            ]);

            if ($is) {

                Db::update(self::SEO_TABLE_NAME, [
                    'name' => $name
                ], [
                    'component_id' => $componentId,
                    'bind_id' => $bindId
                ]);

                $result = true;
            } else {

                Db::insert(self::SEO_TABLE_NAME, [
                    'name' => $name,
                    'component_id' => $componentId,
                    'bind_id' => $bindId
                ]);

                $result = true;
            }
        }



        return $result;
    }


    /**
     * Check the rights to write the seo name taking into account the binding ID and the component
     * @param string Name
     * @param int|string Component Id
     * @param int|string Bind Id
     */

    public static function isSeoNameWritePermissions(string $name, $componentId, $bindId)
    {
        $result = false;

        if (self::isSeoName($name, $componentId)) {
            $result = !!Db::count(self::SEO_TABLE_NAME, [
                'name' => $name,
                'component_id' => $componentId,
                'bind_id' => $bindId
            ]);
        } else
            $result = true;

        return $result;
    }

    /**
     * Check the format of the seo name
     * @param string Name
     */

    public static function isSeoNameFormat(string $name)
    {
        return !is_numeric($name) && preg_match('/^[a-zA-Z0-9_-]+$/', $name);
    }
}
