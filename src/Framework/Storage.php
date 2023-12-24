<?php

namespace Energy;

use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

class Storage
{


    /** 
     * Structure of the remote server table
     * @var array
     */

    public const SERVERS_TABLE = [
        'id',                // int
        'host',              // text
        'port',              // int
        'ssl',               // int
        'password',          // text
        'user',              // varchar(50)
        'title_url',         // text
        'path',              // text
        'sftp_private_key',  // text
        'sftp_passphrase',   // text
        'status',            // int
        'type'               // int
    ];


    /** 
     * ID of the local server type
     * @var int
     */

    public const SERVER_LOCAL = 0;

    /** 
     * ID of the FTP server type
     * @var int
     */
    public const SERVER_FTP = 1;


    /** 
     * ID of the SFTP server type
     * @var int
     */
    public const SERVER_SFTP = 2;


    /** 
     * Instance of the FTP class
     * @var object
     */

    private static $clientFTP;


    /** 
     * Instance of the SFTP class
     * @var object
     */

    private static $clientSFTP;

    /** 
     * Instance of the local connection class
     * @var object
     */
    private static $clientLocal = false;


    /** 
     * Destruct
     */
    public function __destruct()
    {
        self::$clientFTP = false;
        self::$clientSFTP = false;
        self::$clientLocal = false;
    }


    /**
     * Disk utility for working with different types of servers
     * @param string|int Server Type
     * @param array Server Parameters
     */
    public static function disk($type = 'local', $params = array())
    {

        if ($type === 'local' || $type == 0)
            $filesystem = self::local($params);

        elseif ($type === 'ftp' || $type == 1)
            $filesystem = self::ftp($params);

        elseif ($type === 'sftp' || $type == 2)
            $filesystem = self::sftp($params);

        return $filesystem;
    }


    /**
     * Method for working with a local server
     * @param array Server Parameters
     */

    public static function local(array $params = array())
    {

        if (!self::$clientLocal) {

            $defParams = array(
                'root' => base_path(),
                'publicFilePermissions' => 0644,
                'privateFilePermissions' => 0644,
                'publicDirPermissions' => 0775,
                'privateDirPermissions' => 0775
            );

            $params = array_merge($defParams, $params);

            // The internal adapter
            $adapter = new LocalFilesystemAdapter(

                // Determine the root directory
                $params['root'],

                // Customize how visibility is converted to unix permissions
                PortableVisibilityConverter::fromArray([
                    'file' => [
                        'public' => $params['publicFilePermissions'],
                        'private' => $params['privateFilePermissions'],
                    ],
                    'dir' => [
                        'public' => $params['publicDirPermissions'],
                        'private' => $params['privateDirPermissions'],
                    ]
                ]),

                // Write flags
                LOCK_EX,

                // How to deal with links, either DISALLOW_LINKS or SKIP_LINKS
                // Disallowing them causes exceptions when encountered
                LocalFilesystemAdapter::DISALLOW_LINKS
            );
            self::$clientLocal = new Filesystem($adapter);
        }

        return self::$clientLocal;
    }


    /**
     * Method for working with an ftp server
     * @param array Server Parameters
     */

    public static function ftp(array $params = array())
    {

        if (!self::$clientFTP) {

            $defParams = array(
                'host' => false,
                'user' => 'anonymous',
                'password' => '',
                'ssl' => 1,
                'port' => 21,
                'path' => '',
                'serverId' => 0,
                'publicFilePermissions' => 0644,
                'privateFilePermissions' => 0644,
                'publicDirPermissions' => 0775,
                'privateDirPermissions' => 0775
            );

            $params = array_merge($defParams, $params);

            if (!$params['host'])
                $params = array_merge($params, self::getRemoteServerById($params['serverId']));

            if ($params['host']) {
                $adapter = new FtpAdapter(
                    FtpConnectionOptions::fromArray([
                        'host' => $params['host'],
                        'root' => '/' . ltrim($params['path'], '/'),
                        'username' => $params['user'],
                        'password' => $params['password'],
                        'port' => intval($params['port']),
                        'ssl' => !!$params['ssl'],
                        'timeout' => 90,
                        'transferMode' => FTP_BINARY,
                        'recurseManually' => true
                    ]),
                    null,
                    null,
                    PortableVisibilityConverter::fromArray([
                        'file' => [
                            'public' => $params['publicFilePermissions'],
                            'private' => $params['privateFilePermissions'],
                        ],
                        'dir' => [
                            'public' => $params['publicDirPermissions'],
                            'private' => $params['privateDirPermissions'],
                        ]
                    ]),
                );
            }
            self::$clientFTP = new Filesystem($adapter);
        }

        return self::$clientFTP;
    }


    /**
     * Method for working with an sftp server
     * @param array Server Parameters
     */

    public static function sftp(array $params = array())
    {

        if (!self::$clientSFTP) {

            $defParams = array(
                'host' => false,
                'user' => 'anonymous',
                'password' => '',
                'port' => 22,
                'title_url' => '',
                'path' => '',
                'sftp_private_key' => null,
                'sftp_passphrase' => null,
                'serverId' => 0,
                'publicFilePermissions' => 0644,
                'privateFilePermissions' => 0644,
                'publicDirPermissions' => 0775,
                'privateDirPermissions' => 0775
            );

            $params = array_merge($defParams, $params);

            if (!$params['host'])
                $params = array_merge($params, self::getRemoteServerById($params['serverId']));

            $adapter = new SftpAdapter(
                new SftpConnectionProvider(
                    $params['host'], // host (required)
                    $params['user'], // username (required)
                    $params['password'], // password (optional, default: null) set to null if privateKey is used
                    $params['sftp_private_key'], // private key (optional, default: null) can be used instead of password, set to null if password is set
                    $params['sftp_passphrase'], // passphrase (optional, default: null), set to null if privateKey is not used or has no passphrase
                    intval($params['port']), // port (optional, default: 22)
                    false, // use agent (optional, default: false)
                    30, // timeout (optional, default: 10)
                    4, // max tries (optional, default: 4)
                    null, // host fingerprint (optional, default: null),
                    null, // connectivity checker (must be an implementation of 'League\Flysystem\PhpseclibV2\ConnectivityChecker' to check if a connection can be established (optional, omit if you don't need some special handling for setting reliable connections)
                ),
                rtrim($params['path'], '/') . '/', // root path (required)
                PortableVisibilityConverter::fromArray([
                    'file' => [
                        'public' => $params['publicFilePermissions'],
                        'private' => $params['privateFilePermissions'],
                    ],
                    'dir' => [
                        'public' => $params['publicDirPermissions'],
                        'private' => $params['privateDirPermissions'],
                    ]
                ]),
            );
            self::$clientSFTP = new Filesystem($adapter);
        }

        return self::$clientSFTP;
    }


    /**
     * Local recursive deletion of a file or directory
     * @param string The path to the file or direcory
     */

    public static function delete($path)
    {
        if (is_dir($path)) {

            if (!$paths = new \DirectoryIterator(rtrim($path, '/') . '/')) {
                return false;
            }

            foreach ($paths as $file) {

                if ($file->isFile()) {
                    if (!(file_exists($file->getRealPath()) && @unlink($file->getRealPath()))) {
                        return false;
                    }
                } elseif (!$file->isDot() && $file->isDir()) {
                    self::delete($file->getRealPath());

                    if (is_dir($path))
                        @rmdir($path);
                }
            }

            return is_dir($path) && @rmdir($path);
        } else
            return file_exists($path) && @unlink($path);
    }


    /**
     * Create a directory locally (Alternative method Storage::local()->createDirectory(...))
     * @param string Directory path
     */

    public static function createDirectory($path, $permissions = 0777)
    {
        return !is_dir($path) && @mkdir($path, $permissions, true);
    }


    /**
     * Locally copy a file or directory
     * @param string Copy from
     * @param string Insert into
     */

    public static function copy($from, $to)
    {
        if (!file_exists($from))
            return false;

        $dir = dirname($to);

        if ($dir)
            self::createDirectory($dir);

        return copy($from, $to);
    }


    /**
     * Get Content
     * @param string File path
     */

    public static function getContents($path)
    {
        $result = "";
        if (file_exists($path)) {

            ob_start();
            include($path);
            $result = ob_get_contents();
            ob_end_clean();
        }

        return $result;
    }


    /**
     * Get remote server data by ID
     * @param int|string Server ID (If the value is 0, the active one will be used)
     */

    public static function getRemoteServerById($id = 0)
    {
        $result = array();

        $fields = array(
            'status' => 1
        );

        if ($id)
            $fields = array('id' => intval($id));

        $one = Db::get('file_servers', '*', $fields);

        if ($one)
            $result = $one;

        return $result;
    }


    /**
     * GGet a list of servers based on the specified parameters
     * @param array Parameters
     */

    public static function getRemoteServers($params = array()): array
    {

        $table  = self::SERVERS_TABLE;
        unset($table['id']);

        return Db::select('file_servers', [
            'id' => $table  // Group by id
        ], $params);
    }
}
