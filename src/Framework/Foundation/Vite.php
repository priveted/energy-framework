<?php

namespace Energy\Foundation;

use Energy\Json;

class Vite
{

    /**
     * Development Host
     * @var string
     */

    protected static $viteHost = '';


    /**
     * If the Vite module is initiated
     * @var string
     */

    protected static $isModuleLoaded = false;


    /**
     * Data from the manifest.json file
     * @var array
     */

    protected static $mainfest = array();


    /**
     * The build path
     * @return string
     */

    public static function buildPath(): string
    {
        return '/build/';
    }

    /**
     * Get a development host
     * @return string
     */
    public static function getHost(): string
    {
        if (self::$viteHost)
            return self::$viteHost;

        if (!file_exists(self::hotFile()))
            return '';

        self::$viteHost = self::hotAsset('');

        return self::$viteHost;
    }

    /**
     * Get the path to a given asset when running in HMR mode.
     * @return string
     */
    protected static function hotAsset($asset)
    {
        return rtrim(file_get_contents(self::hotFile())) . '/' . $asset;
    }

    /**
     * Get the Vite "hot" file path.
     * @return string
     */
    public static function hotFile()
    {
        return public_path('/hot');
    }


    /**
     * Get the data from the manifest.json file
     * @return array
     */

    public static function getManifest(): array
    {

        if (self::$mainfest)
            return self::$mainfest;

        $manifest =  public_path('build') . '/manifest';

        if (!Json::isExists($manifest))
            return array();

        self::$mainfest = Json::load($manifest);

        return self::$mainfest;
    }


    /**
     * Start the Vite module
     * @return string
     */

    public static function useModule(): string
    {

        if (!env('APP_VITE_DEV'))
            return '';

        if (self::$isModuleLoaded)
            return '';

        self::$isModuleLoaded = true;

        return '<script type="module" src="' . self::getHost() . '@vite/client"></script>' . "\n";
    }


    /**
     * Use the script
     * @param string $entry
     * @return string
     */

    public static function useScript(string $entry): string
    {
        $entryPath = 'resources/js/' . $entry;

        if (!env('APP_VITE_DEV')) {
            $mainfest = self::getManifest();
            return !empty($mainfest[$entryPath]['file']) ? "\n" . '<script type="application/javascript" src="' . self::buildPath() . $mainfest[$entryPath]['file'] . '">' . "\n" : '';
        }

        return '<script type="module" src="' . self::getHost() . $entryPath . '"></script>';
    }

    /**
     * Use style
     * @param string $entry
     * @return string
     */

    public static function useStyle(string $entry): string
    {
        $entryPath = 'resources/styles/' . $entry;
        if (env('APP_VITE_DEV'))
            return '';

        $mainfest = self::getManifest();

        return !empty($mainfest[$entryPath]['file']) ? "\n" . '<link rel="stylesheet" href="'  . self::buildPath() .  $mainfest[$entryPath]['file'] . '">' . "\n" : '';
    }
}
