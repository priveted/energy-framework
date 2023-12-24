<?php
namespace Energy;

class Syntax
{
    private static $temp = array();

    public static function getTreeMap($ar, $end = "") // ["hello", "world"] = ["hello" => ["world" => ""] ]
    {
        $r = [];
        self::$temp = $ar;
        foreach (self::$temp as $k => $v) {

            if (isset(self::$temp[$k + 1])) {
                $r[$v] =  self::$temp[$k + 1];
                unset($r[self::$temp[$k]]);
            }

            unset(self::$temp[$k]);

            self::$temp = array_values(self::$temp);
            
            if (self::$temp || (count(self::$temp)  == $k)) {
                if (count(self::$temp) == $k)
                    $r[$v] = $end;
                else
                    $r[$v] = self::getTreeMap(self::$temp);
            }
        }
        return $r;
    }

    public static function calcPosition($arr, $num = 0)
    {
        if (isset($arr[$num])) {
            $num++;
            $num = self::calcPosition($arr, $num);
        }
        return $num;
    }

    public static function merge($array1, $array2 = array(), $unset = false, $push = false, $position = 0)
    {
        $merged = $array1;

        if (is_array($array2)) {
            foreach ($array2 as $key => $val) {
                if (is_array($array2[$key])) {
                    if (!isset($merged[$key]))
                        $merged[$key] = array();
                    if (is_array($merged[$key])) {
                        $merged[$key] = self::merge($merged[$key],  $array2[$key], $unset, $push, $position);
                    } else {
                        if ($unset)
                            unset($merged[$key]);
                        else {
                            $merged[$key] = $array2[$key];
                        }
                    }
                } else {
                    if ($push) {
                        if (!isset($merged[$key]))
                            $merged[$key] = "";

                        if (!isset($merged[$key]["push.saved"])) {
                            $save = $merged[$key];
                            $merged[$key] = array();
                            $merged[$key]["push.saved"] =  $save;
                        }
                        if ($position > 0) {
                            if (!isset($merged[$key]["push." . $push]))
                                $merged[$key]["push." . $push] = [];
                            $position = self::calcPosition($merged[$key]["push." . $push], $position);
                            $merged[$key]["push." . $push][$position] = $val;
                        } else
                            $merged[$key]["push." . $push][] = $val;

                        ksort($merged[$key]["push." . $push]);
                    } else
                        $merged[$key] = $val;
                    if ($unset)
                        unset($merged[$key]);
                }
            }
        }
        return $merged;
    }
}
