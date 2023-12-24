<?php


use Energy\Registry;
use Energy\Url;

/** Outputs readable information about the variable (simple version)
 * @param mixed Any data type
 * @param string Container background
 */

function vprint(mixed $data, string $background = "#00264d"): void
{
    echo "<pre style='color:#80ff80;font-size:16px;font-weight:500;position:relative;z-index:7777;padding:16px;background:{$background}'>" . htmlspecialchars(var_export($data, true)) . '</pre>';
}


/** Outputs readable information about the variable
 * @param mixed Any type of data
 */

function vdump()
{
    if (env('APP_DEBUG')) {

        $args = func_get_args();

        if (Url::isPost()) {
            $data = Registry::get("result", "main");

            if (!$data)
                $data = array();

            $data["debugger"] = (isset($data["debugger"])) ? array_merge($data["debugger"], $args) : $args;
            Registry::set("result", ["main" =>  $data]);
        } else {
            foreach ($args as $arg) {
                Symfony\Component\VarDumper\VarDumper::dump($arg);
            }
        }
    }
}

/**
 * Use the environment
 * @param string The path to the environment file
 */

function use_env(string $path = ""): void
{
    $path = base_path($path);

    if (empty($path))
        throw new ErrorException(".env file path is missing");

    if (!is_file(realpath($path)))
        throw new ErrorException("Environment File is Missing.");

    if (!is_readable(realpath($path)))
        throw new ErrorException("Permission Denied for reading the " . (realpath($path)) . ".");

    $tmp = [];
    $fopen = fopen(realpath($path), 'r');

    if ($fopen) {
        while (($line = fgets($fopen)) !== false) {
            $line_is_comment = (substr(trim($line), 0, 1) == '#') ? true : false;
            if ($line_is_comment || empty(trim($line)))
                continue;

            $line_no_comment = explode("#", $line, 2)[0];
            $env_ex = preg_split('/(\s?)\=(\s?)/', $line_no_comment);
            $env_name = trim($env_ex[0]);
            $env_value = isset($env_ex[1]) ? trim($env_ex[1]) : "";
            $tmp[$env_name] = $env_value;
        }
        fclose($fopen);
    }

    if ($tmp) {
        foreach ($tmp as $name => $value) {
            putenv("{$name}=$value");
            if (is_numeric($value))
                $value = floatval($value);
            if (in_array(strtolower($value), ["true", "false"]))
                $value = (strtolower($value) == "true") ? true : false;
            $_ENV[$name] = $value;
        }
    }
}


/**
 * Get environment value
 * @param string Environment Key
 */


if (!function_exists('env')) {
    function env(string $key, mixed $defalt = false): mixed
    {
        $data  = $_ENV[$key] ?? false;
        return $data !== false ? $data : $defalt;
    }

}

/**
 * Get the basic application path
 * @param string $path
 */

function base_path($path = ''): string
{
    return realpath(__DIR__ . "/../../../../" . $path);
}

function app_path($path = ''): string
{
    return base_path('app/' . $path);
}

function resources_path($path = ''): string
{
    return base_path('resources/' . $path);
}

function public_path($path = ''): string
{
    return base_path('public/' . $path);
}


function controllers_path($path = ''): string
{

    return app_path('controllers/' . $path);

}

function config_path($path = ''): string
{
    return base_path('config/' . $path);
}

function cache_path($path = ''): string
{
    return base_path('cache/' . $path);
}
