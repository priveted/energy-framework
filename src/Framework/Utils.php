<?php

namespace Energy;

class Utils
{

    public static function truncate($text, $characters = 15, $start = 0, $after = "", $before = "", $reverse = false, $stringFilter = true)
    {
        if ($stringFilter)
            $text = Security::stringFilter($text);
        $after = (mb_strlen($text) > $characters) ? $after : "";
        $before = (mb_strlen($text) > $characters) ? $before : "";

        if ($reverse) {
            if (mb_strlen($text) > $characters) {
                $text = ($start > 0) ? mb_strimwidth($text, 0,  mb_strlen($text) - $start) : $text;
                $text = mb_substr($text, $characters);
            }
        } else {
            if ($start <= mb_strlen($text)) {
                $text = mb_strimwidth($text, $start, $characters);
            }
        }

        return $before . trim($text) . $after;
    }

    public static function convertToString($arr = array(), $params = array())
    {
        $defParams = array(
            "char" => ",",
            "quotes" => false,
            "quotesKeys" => true,
            "quotesValues" => true,
            "combnieValue" => false,
            "combineChar" => ":"
        );

        $params = array_merge($defParams, $params);

        if ($arr && count($arr) > 0) {

            $result = "";
            if (!$params["combnieValue"])
                $arr = array_values($arr);

            $i = 0;
            foreach ($arr as $key => $value) {
                $q1 = "";
                $q2 = "";
                if ($params["quotes"]) {

                    if (is_array($params["quotes"])) {
                        $q1 = isset($params["quotes"][0]) ? $params["quotes"][0] : $q1;
                        $q2 = isset($params["quotes"][1]) ? $params["quotes"][1] : $q1;
                    } elseif (is_string($params["quotes"])) {
                        $q1 = $params["quotes"];
                        $q2 = $params["quotes"];
                    } else {
                        $q1 = "'";
                        $q2 = "'";
                    }
                }

                $qKey = $params["quotesKeys"] ? $q1 . $key . $q2 : $key;
                $qVal = $params["quotesValues"] ? $q1 . $value . $q2 : $value;
                $rval = ($params["quotes"]) ? $qVal : $value;
                $rkey = "";

                if ($params["combnieValue"])
                    $rkey = ($params["quotes"]) ? $qKey . $params["combineChar"] : $key . $params["combineChar"];

                $z = (($i + 1) >= count($arr)) ? "" : $params["char"];
                $result .= $rkey . $rval . $z;
                $i++;
            }
            return $result;
        }
    }

    public static function numberFilter($string = "", $unset = false, $stringResult = true,  $t = ",")
    {
        $ar = explode($t, $string);
        $result = $string;

        if ($ar) {

            foreach ($ar as $key => $value) {
                $ar[$key] = intval($value);
                if ($unset && !intval($value) && $value != '0')
                    unset($ar[$key]);
            }

            $result = $stringResult ? self::convertToString($ar) : $ar;
        }
        return $result;
    }

    public static function sortArrayByKey(&$array, $key, $reindex = false)
    {
        $sorter = array();
        $ret = array();

        reset($array);

        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }

        asort($sorter);

        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }

        $array = $ret;

        if ($reindex)
            $array = array_values($array);

        return $array;
    }

    public static function createDomAttributes($arr = array())
    {
        if ($arr) {

            $result = "";

            foreach ($arr as $key =>  $value) {
                $result .= ' ' . $key . '="' . $value . '"';
            }

            return $result;
        }
    }

    public static function varShortExport($var, $indent = "")
    {
        switch (gettype($var)) {
            case "string":
                return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
            case "array":
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent  " . ($indexed ? "" : self::varShortExport($key) . " => ") .  self::varShortExport($value, "$indent  ");
                }
                $r = " [\n" . implode(",\n", $r) . "\n" . $indent . "]";
                return $r;
            case "boolean":
                return $var ? "true" : "false";
            default:
                return var_export($var, true);
        }
    }

    public static function arrayBuilder($arr, $params = array())
    {

        $defParams = array(
            'file' => false,
            'name' => 'config',
            'short' => false,
            'sleep' => 0,
            'comment' => '',
            'tab' => 0,
            'firstTab' => false
        );

        $params = array_merge($defParams, $params);
        $tab = '';

        if ($params['tab']) {
            for ($i = 1; $i <= intval($params['tab']); $i++) {
                $tab .= "\t";
            }
        }

        $bk = $params['short'] ? ["[", "]", " ["] : array("array(", ")", "array (");
        $comment = $params["comment"] ? "/* " . $params["comment"] . " */\n" : '';
        $export  = $params['short'] ? self::varShortExport($arr, $tab) : var_export($arr, true);
        $content = preg_replace("/=> \n /", "=>", $export);
        $content = str_replace("  ", "    " . ($params['short'] ? '' : $tab), $content);
        $content = str_replace($bk[2], $bk[0], $content);
        $content = preg_replace("/=> \n /", "=>", $content);

        if (!$params['short'])
            $content = preg_replace("/\s+array\(/", " " . $bk[0], $content);
        else
            $content = str_replace("=>   [", "=> [", $content);

        $content = $params['firstTab'] ? $tab . $content : $content;

        $content = preg_replace_callback('/,\s+\)/', function ($m) use ($tab) {
            $space =  "";
            $sp_count = strlen($m[0]) - 3;

            if ($sp_count > 0) {

                for ($i = 1; $i <= $sp_count; $i++) {
                    $space .= " ";
                }
            }

            $result  = "\n" . $tab . $space . ")";

            return  $result;
        }, $content);

        $first = ($params['name']) ? "\$" . $params['name'] . " = " : '';
        $last =  $params['file'] ? ";" : '';
        $php = $params['file'] ? "<?php\n" : '';
        $content = $php . $comment . $first . $content . $last;

        if ($params['file']) {

            try {
                Storage::local()->write($params['file'], $content);
            } catch (\Exception $e) {
                if (env('APP_DEBUG'))
                    throw new \Exception('Well, it was possible to write to a file: ' . $params['file'] . ' | ' . $e->getMessage());
            }
        }

        if ($params['sleep'])
            sleep(intval($params['sleep']));

        return $params['file'] ? true : $content;
    }

    public static function isSerialized($string)
    {
        try {
            unserialize($string);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
