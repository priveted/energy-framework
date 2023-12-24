<?php

namespace Energy\Core\Modify;

use Energy\Hooks;
use Energy\Db;
use Energy\Kernel;

class Delete
{

    /**
     * Saving component data
     * @param array Basic parameters
     * @param callable Callback of successful deletion
     */

    public static function delete(array $params = array(), callable $callback = null): bool
    {

        $status = false;
        $ids = array();

        $defParams = array(

            // Component name
            'componentName' => '',

            // Component Id
            'componentId' => 0,

            // Table Parameters
            'tableName' => '',
            'tableColumnId' => 'id',

            // Where syntax
            'where' => array()
        );

        $params = array_merge($defParams, $params);

        Hooks::apply('Core.Modify.Delete::delete.pre', $params, $ids, $status);

        if (!empty($params['componentName']) && !empty($params['tableName']) && !empty($params['tableColumnId']) && !empty($params['where'])) {

            $cName = $params['componentName'];

            if (Kernel::config('components/' . $cName, 'status')) {

                $hookName = ucfirst($cName);

                Hooks::apply('Components.' . $hookName . '::delete.pre', $ids, $params['where'], $status);

                $tName = $params['tableName'];
                $tId = $params['tableColumnId'];

                if (!empty($ids)) {
                    $params['where'] = array_merge($params['where'], [
                        $tId = $ids
                    ]);
                }

                $sql = Db::select($tName, [$tId], $params['where']);

                foreach ($sql as $item) {
                    $ids[] = $item[$tId];
                }

                if ($ids) {

                    Hooks::apply('Components.' . $hookName . '::delete.ids', $ids, $params, $status);
                    Hooks::apply('Core.Modify.Delete::delete.ids', $ids, $params, $status);

                    $pdo = Db::delete($tName, [
                        $tId => $ids
                    ]);

                    if ($pdo->rowCount()) {
                        $status = true;
                        if (!empty($callback)) {

                            Hooks::apply('Core.Modify.Delete::delete.success', $ids, $params, $status);

                            $callback($ids, $params, $status);
                        }
                    }
                }

                Hooks::apply('Components.' . $hookName . '::delete.post', $ids, $params['where'], $status);
            }
        }

        Hooks::apply('Core.Modify.Delete::delete.post', $params, $ids, $status);

        return $status;
    }
}
