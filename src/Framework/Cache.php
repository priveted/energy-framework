<?php

namespace Energy;

class Cache
{

    /** 
     * Object of the connection class to the Redis server
     * @var object
     */
    private static $redis;


    /**
     * Array of cached data objects
     * @var array
     */
    private static $cachedClasses = array();


    /**
     * Get the full path to the cache file based on the data type
     * @param string - Cache key
     * @param string - Data Type / Subdirectory
     */

    private static function getPath(string $key, string $dataType = 'data/html'): string
    {
        return 'cache/' . $dataType . '/' . Languages::getSelectedCode() . '/cache.' . $key . '.' . Encryption::irreversibleCompressed($key) . '.php';
    }


    /**
     * Creating a local cache by key based on the data type (The private method is the foundation)
     * @param string - Cache key
     * @param mixed - Content based on data type
     * @param int - Expiration time (seconds)
     * @param string - Data Type / Subdirectory
     */

    private static function createCacheData(string $key, mixed $content = '', int $expire = 0, $dataType = 'data/html'): bool
    {

        $result = true;

        if ($key) {

            $filePath = self::getPath($key, $dataType);
            $content = self::generateClass($key, $content, $expire, $dataType);

            try {
                if (Storage::local()->write($filePath, $content))
                    $result = true;
            } catch (\Exception $e) {
                if (env('APP_DEBUG'))
                    throw new \Exception('Well it was possible to create a cache file: ' . $filePath . ' | ' . $e->getMessage());
            }
        }

        return $result;
    }


    /**
     * Deleting the local cache by key based on the data type (The private method is the foundation)
     * @param string - Cache key
     * @param string - Data Type / Subdirectory
     */

    private static function deleteCacheData(string $key, string $dataType = 'data/html'): bool
    {

        $result = false;

        if ($key && self::isCacheData($key, $dataType)) {

            $filePath = self::getPath($key, $dataType);

            try {
                if (Storage::local()->delete($filePath))
                    $result = true;
            } catch (\Exception $e) {
                if (env('APP_DEBUG'))
                    throw new \Exception('Failed to delete cache file: ' . $filePath . ' | ' . $e->getMessage());
            }
        }

        return $result;
    }


    /**
     * Check for a local cache by key depending on the data type (The private method is the foundation)
     * @param string - Cache key
     * @param string - Data Type / Subdirectory
     */

    private static function isCacheData(string $key, string $dataType = 'data/html'): bool
    {
        return file_exists(base_path(self::getPath($key, $dataType)));
    }


    /**
     * Get local cache data by key depending on the data type (The private method is the foundation)
     * @param string - Cache key
     * @param string - Data Type / Subdirectory
     */

    private static function getCacheData(string $key, string $dataType = 'data/html'): mixed
    {
        $result = $dataType ? '' : array();

        if ($key && self::isCacheData($key, $dataType)) {

            $filePath = self::getPath($key, $dataType);
            $className = 'CData_' . md5($dataType) . '_' . md5($key);

            if (!isset(self::$cachedClasses[$className])) {
                include base_path($filePath);
                self::$cachedClasses[$className] = new $className;
            }

            if (class_exists($className)) {

                $c = self::$cachedClasses[$className];

                if (method_exists($className, 'get'))
                    $result = $c->get();

                if (method_exists($className, 'isTimeExpired')) {
                    if ($c->isTimeExpired()) {

                        if ($dataType === 'data/html')
                            self::deleteHtmlCache($key);
                        else
                            self::deleteArrayCache($key);
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Generates a class foundation with local cache data by key depending on the data type (The private method is the foundation)
     * @param string - Cache key
     * @param mixed - Content based on data type
     * @param int - Expiration time (seconds)
     * @param string - Data Type / Subdirectory
     */

    private static function generateClass(string $key, mixed $content = '', int $expire = 0, string $contentType = 'data/html'): string
    {
        if ($contentType === 'data/html') {
            $content =  "'" . addslashes($content) . "'";
        } else {

            $content = Utils::arrayBuilder(
                $content,
                [
                    'name' => false,
                    'tab' => 1
                ]
            );
        }

        $f = "<?php\n" . PHP_EOL . " use Energy\Core\Cache\CacheModel;\n" . PHP_EOL;
        $f .= "class CData_" . md5($contentType) . '_' . md5($key) . " extends CacheModel\n{\n";
        $f .= "\tpublic \$key = '" . $key . "';\n" . PHP_EOL;
        $f .= "\tpublic \$data = " . $content . ";\n" . PHP_EOL;
        $f .= "\tpublic \$type = '" . $contentType . "';\n" . PHP_EOL;
        $f .= "\tpublic \$expire = " . ($expire ? time() + $expire : 0) . "; // {$expire} s\n";
        $f .= "}\n";

        return $f;
    }


    /* HTML cache*/

    /**
     * Creating a local html cache
     * @param string - Cache key
     * @param string - Content html
     * @param int - Expiration time (seconds)
     */

    public static function setHtmlCache(string  $key, string $content = '', int $expire = 0): bool
    {
        return (!is_array($content)) ? self::createCacheData($key, $content, $expire) : false;
    }


    /**
     * Deleting the local html cache
     * @param string - Cache key
     */

    public static function deleteHtmlCache(string $key): bool
    {
        return self::deleteCacheData($key);
    }


    /**
     * Checks the existence of a local html cache
     * @param string - Cache key
     */

    public static function isHtmlCache(string $key): bool
    {
        return self::isCacheData($key);
    }


    /**
     * Get local html cache
     * @param string - Cache key
     */
    public static function getHtmlCache(string $key): string
    {
        return self::getCacheData($key);
    }


    /**
     * Capturing the contents of the local html cache
     * @param string - Cache key
     * @param callable - Content capture function
     * @param int - Expiration time (seconds)
     * @param mixed - Arguments of the content capture function
     */

    public static function htmlCapture(string $key, callable $callback, int $expire = 0, ...$arguments): string
    {

        $result = '';

        if ($key && $callback) {

            $key = 'Cache.htmlCapture.' . $key;

            if (self::isHtmlCache($key)) {
                $result = self::getHtmlCache($key);
            } else {

                ob_start();
                $callback(...$arguments);
                $result = ob_get_contents();
                ob_end_clean();

                self::setHtmlCache($key, $result, $expire);
            }
        }
        return $result;
    }


    /* Array cache */

    /**
     * Creating a local array cache
     * @param string - Cache key
     * @param array - Content html
     * @param int - Expiration time (seconds)
     */

    public static function setArrayCache(string $key, array $content = array(), int $expire = 0): bool
    {
        return (is_array($content)) ? self::createCacheData($key, $content, $expire, 'data/array') : false;
    }


    /**
     * Deleting local array cache
     * @param string - Cache key
     */

    public static function deleteArrayCache(string $key): bool
    {
        return self::deleteCacheData($key, 'data/array');
    }


    /**
     * Checks the existence of a local array cache
     * @param string - Cache key
     */

    public static function isArrayCache(string $key): bool
    {
        return self::isCacheData($key, 'data/array');
    }


    /**
     * Get local array cache
     * @param string - Cache key
     */

    public static function getArrayCache($key): array
    {
        return self::getCacheData($key, 'data/array');
    }


    /**
     * Caching in RAM using Redis (Available only when using the configuration option cache_type = 1)
     */

    public static function redis(): object
    {
        try {

            if (class_exists("\Redis") && Kernel::config('config', 'cache_type')) {

                if (!self::$redis) {

                    $host = Kernel::config('config', 'redis_host');
                    $port = Kernel::config('config', 'redis_port');
                    $password = Kernel::config('config', 'redis_password');

                    self::$redis = new \Redis();
                    self::$redis->connect($host, $port);

                    if ($password)
                        self::$redis->auth($password);

                    Hooks::apply('Cache::redis.config', self::$redis);
                }
            }

            return self::$redis;
        } catch (\Exception $e) {
            throw new \Exception('Connection to the Redis server could not be established | ' .  $e->getMessage());
        }
    }


    /* 
        Universal managed cache regardless of the data type.
        If the configuration parameter cache_type is enabled, Redis will be used.
        (Redis must be installed and configured correctly)
    */

    /**
     * Get universal cache data by key
     * @param string Cache key
     * @param bool Check for existence (Used as a private parameter for the Cache::is() method)
     */

    public static function get(string $key, bool $isset = false): mixed
    {
        $result = false;

        if (Kernel::config('config', 'cache_type')) {

            if (method_exists(self::redis(), 'get'))
                $result = self::redis()->get($key);

            if ($isset && $result !== false) {
                $result = true;
            } else {
                if ($result && Utils::isSerialized($result))
                    $result = unserialize($result);
            }
        } else {
            if (self::isArrayCache($key))
                $result = $isset ? true : self::getArrayCache($key);

            elseif (self::isHtmlCache($key))
                $result = $isset ? true :  self::getHtmlCache($key);
        }

        return $result;
    }


    /**
     * Set universal cache data
     * @param string Cache key
     * @param mixed Any type of content
     * @param int - Expiration time (seconds)
     */

    public static function set(string $key, mixed $value, int $expire = 0): bool
    {

        $result = false;

        if ($key) {
            if (Kernel::config('config', 'cache_type')) {

                if (is_array($value))
                    $value = serialize($value);

                $expire = ($expire || $expire === 0) ? $expire : null;

                if (method_exists(self::redis(), 'set') && self::redis()->set($key, $value, $expire))
                    $result = true;
            } else {
                if (is_array($value))
                    $result = self::setArrayCache($key, $value, $expire);
                else
                    $result = self::setHtmlCache($key, $value, $expire);
            }
        }

        return $result;
    }

    /**
     * Checks the existence of a universal cache by key
     * @param string Cache key
     */

    public static function is(string $key): bool
    {
        return self::get($key, true);
    }


    /**
     * Deletes the universal cache by key
     * @param string Cache key (Use * to completely delete all cache data)
     */

    public static function delete(string $key): bool
    {
        $result = false;

        if ($key) {
            if (Kernel::config('config', 'cache_type')) {

                if (method_exists(self::redis(), 'del') && method_exists(self::redis(), 'keys') && self::redis()->del(self::redis()->keys($key)))
                    $result = true;
            } else {
                if ($key === '*') {
                    self::clearData();
                } else {
                    if (self::isArrayCache($key))
                        $result =  self::deleteArrayCache($key);

                    elseif (self::isHtmlCache($key))
                        $result = self::deleteHtmlCache($key);
                }
            }
        }

        return $result;
    }


    /* Clearing the cache */

    /**
     * Clear the fully universal cache
     */

    public static function clearAllUniversalCache(): bool
    {
        return self::delete('*');
    }


    /**
     * Complete cache deletion
     * @param string - Cache path 
     * @param bool - Clear the RAM data of the universal cache
     */

    public static function clearAll(string $path = '', bool $universal = true): bool
    {
        $result = false;

        if (Kernel::config('config', 'cache_type') && $universal)
            self::clearAllUniversalCache();

        try {

            Storage::local()->deleteDirectory('cache' . $path);
            Storage::local()->createDirectory('cache' . $path);

            $result = true;
        } catch (\Exception $e) {
            if (env('APP_DEBUG'))
                throw new \Exception('Well it was possible to create a cache folder: `cache` | ' .  $e->getMessage());
        }

        return $result;
    }

    /**
     * Clear local and universal data cache
     */

    public static function clearData(): bool
    {
        return self::clearAll('/data');
    }

    /**
     * Various types of cache are compiled
     */
    public static function compilation()
    {
        if (!is_dir(cache_path())) {
            try {
                Storage::local()->createDirectory('cache');
            } catch (\Exception $e) {
                if (env('APP_DEBUG'))
                    throw new \Exception('Well it was possible to create a cache folder: `cache` | ' .  $e->getMessage());
            }
        }
    }
}
