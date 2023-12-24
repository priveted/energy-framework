<?php

namespace Energy;

class Files
{

    /** 
     * Output scheme of the result
     * @var array
     */

    public const RESULT_SCHEME = array(
        'response' => array(
            'success' => false,
            'content' => '',
            'status' => 'error'
        ),
        'errors' => array(),
        'success' => array(),
        'finalStatus' => false,
        'count' => 0
    );


    /** 
     * File Table
     * @var array
     */

    public const FILES_TABLE = [
        'file_id',            // int
        'file_url',           // text
        'file_path',          // text
        'file_component_id',  // int
        'file_bind_id',       // int
        'file_bind_type',     // int
        'file_bind_helper',   // int
        'file_type',          // int
        'file_timestamp',     // int
        'file_owner',         // int
        'file_server_id'      // int
    ];


    /** 
     * Limited list of supported image formats
     * @var array
     */

    public const IMAGES_L = [
        'jpg',
        'jpeg',
        'png',
        'gif'
    ];


    /** 
     * List of supported image formats
     * @var array
     */

    public const IMAGES = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'svg',
        'bmp',
        'webp'
    ];


    /** 
     * List of supported audio formats
     * @var array
     */

    public const AUDIO = [
        'ogg',
        'mp3',
        'aac',
        'aac',
        'aiff',
        'dsd',
        'flac',
        'aac',
        'mqa',
        'wav',
        'wma'
    ];


    /** 
     * List of supported video formats
     * @var array
     */
    public const VIDEO = [
        'ogv',
        'mp4',
        'webm',
        'flv',
        'avi',
        'wmv',
        'mov',
        'mkv',
        '3gp'
    ];


    /** 
     * List of supported archive formats
     * @var array
     */

    public const ARCHIVES = [
        '7z',
        'deb',
        'gz',
        'gzip',
        'jar',
        'pkg',
        'rar',
        'rpm',
        'sh',
        'sitx',
        'tar',
        'tar-gz',
        'tgz',
        'xar',
        'zip',
        'zipx'
    ];


    /** 
     * List of supported document formats
     * @var array
     */

    public const DOCUMENTS = [
        'txt',
        'rtf',
        'doc',
        'docx',
        'html',
        'xml',
        'json',
        'pdf',
        'odt'
    ];


    /** 
     * Image ID
     * @var array
     */

    public const FILE_TYPE_IMAGE = 1;


    /** 
     * Audio ID
     * @var array
     */
    public const FILE_TYPE_AUDIO = 2;


    /** 
     * Video ID
     * @var array
     */

    public const FILE_TYPE_VIDEO = 3;


    /** 
     * Archive ID
     * @var array
     */

    public const FILE_TYPE_ARCHIVE = 4;

    /** 
     * Document ID
     * @var array
     */
    public const FILE_TYPE_DOCUMENT = 5;


    /**
     * Get path information
     * @param string - Path
     * @param string - Type 
     */

    public static function getPathInfo(string $path, string $type = ''): mixed
    {
        $result = '';
        $inf = pathinfo($path);

        if ($type) {
            if (isset($inf[$type]))
                $result = $inf[$type];
        } else
            $result = $inf;

        return $result;
    }


    /**
     * Generate a path based on the current month and year
     * @param string - Initial directory
     */

    public static function generatePath(string $directory = ''): string
    {
        $Year = Encryption::irreversible(date('Y'));
        $Month = Encryption::irreversible(date('m'));
        return trim($directory, '/') . '/' . $Year . '/' . $Month;
    }


    /**
     * Check for the file extension in the array
     * @param array - List of extensions
     * @param string - File Extension
     */

    public static function checkExtensions(array $arr, string $extension): bool
    {
        $result = false;
        foreach ($arr as $item) {
            if ($item === mb_strtolower($extension))
                $result = true;
        }
        return $result;
    }


    /**
     * Get a list of available file extensions
     */

    public static function getListTypes(): array
    {
        $custom_extensions = explode(',', Kernel::config('config', 'custom_extensions'));

        $arr = array(
            1 => self::IMAGES,
            2 => self::AUDIO,
            3 => self::VIDEO,
            4 => self::ARCHIVES,
            5 => self::DOCUMENTS
        );

        if ($custom_extensions)
            $arr[6] = $custom_extensions;

        Hooks::apply('Files::getListTypes', $arr);

        return $arr;
    }


    /**
     * Get the group ID based on the file extension
     * @param string - File Extension
     */

    public static function getGroupId(string $extension): int
    {
        $result = false;
        $arr = self::getListTypes();
        for ($i = 1; $i <= 6; $i++) {
            foreach ($arr[$i] as $item) {
                if ($item === mb_strtolower($extension)) {
                    $result = $i;
                }
            }
        }
        return $result;
    }


    /**
     * Quick server response
     * @param array - Result data
     * @param bool - Success status
     * @param string - Status type (success|error|info|warning)
     * @param string - ID of the language for the content
     * @param bool|string - Final status (bool|string - 'warning')
     */

    private static function response(array &$data = array(), bool $success = false, string $status = 'error', string $langId = '', $finalStatus = false): array
    {

        if (isset($data['response']['success']))
            $data['response']['success'] = $success;

        if (isset($data['response']['status']))
            $data['response']['status'] = $status;

        if (!empty($data['response']['content']))
            $data['response']['content'] = Languages::get('common', 'files', $langId);

        if (isset($data['finalStatus']))
            $data['finalStatus'] = $finalStatus;

        return $data;
    }


    /**
     * If there is one element, then we get the last successful or erroneous response element
     * @param array - Result data
     */

    private static function getLastResponseElement(array &$data = array())
    {

        if ($data['count'] == 1) {
            if (!empty($data['success'])) {
                $data['response']['content'] = $data['success'][0]['message'] ?? '';
                $data['response']['success'] = true;
                $data['response']['status'] = 'success';
            } elseif (!empty($data['errors'])) {
                $data['response']['content'] = $data['errors'][0]['message'] ?? '';
                $data['response']['success'] = false;
                $data['response']['status'] = 'error';
            }
        }
    }


    /**
     * Prepare, filter and validate the file before uploading
     * @param array File Parameters
     * @param callable Perform a callback function if the file has passed validation
     */

    public static function prepare(array $params = array(), callable $successCallback = null): array
    {

        $result = self::RESULT_SCHEME;
        $result['response']['content'] = Languages::get('common', 'files', 'not_enough_data_to_upload');
        $allowInteractive = true;
        $isLimit = false;

        $defParams = array(
            'file' => false,                                           // $_FILES
            'maxSize' => Kernel::config('config', 'file_max_size'),    // Maximum file size (KB)
            'allowExtensions' => array(),                              // Available file extensions
            'multiple' => false,                                       // Multiple file uploads
            'multipleMaxQuantity' => 5,                                // Maximum number of files to upload
            'interactiveResponse' => false                             // Interactive server response with html list
        );

        $params = array_merge($defParams, $params);
        Hooks::apply('Files::prepare.pre', $params, $result);

        if ($params['file'] && is_array($params['file'])) {

            $files = [];

            if (isset($params['file']['name'])) {

                if (is_array($params['file']['name'])) {

                    $files = [];
                    $fCount = count($params['file']['name']);
                    $fKeys  = array_keys($params['file']);

                    for ($i = 0; $i < $fCount; $i++) {
                        foreach ($fKeys as $key) {
                            $files[$i][$key] = $params['file'][$key][$i];
                        }
                    }

                    if (!$params['multiple'] && isset($files[0])) {
                        $files = array($files[0]);
                    }
                } else {
                    $files[] = $params['file'];
                }

                if ($files && count($files) <= $params['multipleMaxQuantity']) {

                    $result['count'] = count($files);

                    Hooks::apply('Files::prepare.files', $files, $params, $result);

                    // Filtering files based on configuration and permissions
                    foreach ($files as $file) {

                        Hooks::apply('Files::prepare.file.pre', $file, $params, $result);

                        // Sznitize data
                        $file = Security::sanitize($file, [
                            'name' => 'filename',
                            'type' => 'string',
                            'tmp_name' => 'escape',
                            'size' => 'int',
                            'error' => 'int'
                        ])->getAll();

                        if (!empty($file['name']) && !empty($file['tmp_name'])) {

                            $name = $file['name'];
                            $ext = self::getPathInfo($file['name'], 'extension');
                            $allowExtensions = (is_array($params['allowExtensions']) && !empty($params['allowExtensions'])) ? self::checkExtensions($params['allowExtensions'], $ext) : self::getGroupId($ext);
                            $isImageStructureError = false;

                            if ('image/svg+xml' !== mime_content_type($file['tmp_name'])) {
                                if (in_array(strtolower($ext), self::IMAGES)  && (!exif_imagetype($file['tmp_name']))) {
                                    $isImageStructureError = true;
                                    unset($result['success'][$key]);
                                }
                            }

                            if ($file['size'] > ($params['maxSize'] * 1000)) {

                                $result['errors'][] = array_merge($file, [
                                    'name' => $name,
                                    'extension' => $ext,
                                    'message' => Languages::get('common', 'files', 'large_file_size'),
                                    'code' => 'large_file_size'
                                ]);
                            } elseif (!$allowExtensions || $isImageStructureError) {

                                $result['errors'][] = array_merge($file, [
                                    'name' => $name,
                                    'extension' => $ext,
                                    'message' => Languages::get('common', 'files', 'format_is_not_supported'),
                                    'code' => 'format_is_not_supported'
                                ]);
                            } elseif (!Kernel::config('config', 'allow_upload')) {

                                $allowInteractive = false;
                                $result['errors'][] = array_merge($file, [
                                    'name' => $name,
                                    'extension' => $ext,
                                    'message' => Languages::get('common', 'files', 'file_upload_is_not_available'),
                                    'code' => 'file_upload_is_not_available'
                                ]);
                            } else {

                                $result['success'][] = array_merge($file, [
                                    'name' => $name,
                                    'extension' => $ext,
                                    'message' => Languages::get('common', 'files', 'file_uploaded'),
                                    'code' => 'file_uploaded'
                                ]);
                            }
                        } else {
                            $allowInteractive = false;
                            $result['errors'][] = [
                                'name' => false,
                                'message' => Languages::get('common', 'files', 'not_enough_data_to_upload'),
                                'code' => 'not_enough_data_to_upload'
                            ];
                        }

                        Hooks::apply('Files::prepare.file.post', $file, $params, $result);
                    }

                    if (!empty($result['success'])) {

                        if ($successCallback)
                            $result = $successCallback($result);
                    }
                } else {
                    if (count($files) > $params['multipleMaxQuantity']) {
                        $isLimit = true;
                    }
                }
            }
        }

        if (!Kernel::config('config', 'allow_upload'))
            self::response($result, false, 'error', 'file_upload_is_not_available');

        elseif ($isLimit)
            self::response($result, false, 'error', 'too_many_files');

        elseif (!empty($result['success']) && !empty($result['errors']))
            self::response($result, true, 'warning', 'not_all_files_are_uploaded', 'warning');

        elseif (!empty($result['success']))
            self::response($result, true, 'success', 'files_uploaded', true);

        else
            self::response($result, false, 'error', 'files_not_uploaded', false);

        self::getLastResponseElement($result);

        if ($params['interactiveResponse'] && $allowInteractive) {
            $result['response']['content'] .= self::interactiveResponse($result);
        }

        Hooks::apply('Files::prepare.post', $params, $result);

        return $result;
    }


    /**
     * Upload a file based on parameters
     * @param array - Parameters
     */

    public static function upload($params = array()): array
    {

        $defParams = array(
            'file' => false,                                           // $_FILES 
            'path' => false,                                           // default: SERVER_LOCAL = 'path'| config('local_upload_path'), SERVER_FTP/_SFTP = 'path'|'/'
            'maxSize' => Kernel::config('config', 'file_max_size'),    // Maximum file size (KB)
            'allowExtensions' => array(),                              // Available file extensions
            'encryptName' => false,                                    // Encrypt the file name
            'multiple' => false,                                       // Multiple file uploads
            'multipleMaxQuantity' => 5,                                // Maximum number of files to upload
            'serverId' => 0,                                           // Upload to a remote server with the corresponding ID / 0 = Use an active server
            'interactiveResponse' => false                             // Interactive server response with html list
        );

        $params = array_merge($defParams, $params);

        Hooks::apply('Files::upload.pre', $params);

        $result = self::prepare($params, function ($result) use ($params) {

            Hooks::apply('Files::upload.files.success', $result['success'], $params, $result);

            if (!empty($result['success'])) {

                foreach ($result['success'] as $key => $file) {

                    $storageType = Kernel::config('config', 'storage_type');
                    $localPath = Kernel::config('config', 'local_upload_path');
                    $ext = self::getPathInfo($file['name'], 'extension');
                    $name = $params['encryptName'] ? Encryption::irreversibleUniqid($file['name'] . Encryption::random() . uniqid()) . '.' . $ext : $file['name'];
                    $fileInfo = [];

                    if ($storageType) {
                        $server = Storage::getRemoteServerById($params['serverId']);
                        $storageType = $server['type'] ?? 1;
                        $fullPathName = $params['path'] ? trim($params['path'], '/') . '/' . $name : '/' . $name;
                        $dir = '/' . trim($params['path'], '/');

                        $fileInfo = [
                            'storageType' => $storageType,
                            'title_url' => $server['title_url'] ?? '',
                            'remoteServerDir' => $server['path'] ?? '',
                            'dir' => $dir,
                            'path' => $dir . '/' . $name,
                            'url' => rtrim($server['title_url'], '/') . $dir . '/' . trim($name, '/'),
                            'serverId' => $server['id'] ?? 0
                        ];
                    } else {

                        $l = empty($params['path']) ? $localPath : $localPath . '/' . $params['path'];
                        $fullPathName = trim($l, '/') . '/' . $name;

                        $fileInfo = [
                            'dir' => '/' . trim($l, '/'),
                            'path' =>  '/' . trim($fullPathName, '/'),
                            'url' => '/' . trim($fullPathName, '/'),
                            'serverId' => 0
                        ];
                    }

                    try {
                        $content = file_get_contents($file['tmp_name']);

                        Hooks::apply('Files::upload.file.success.pre', $file, $content, $params, $result);
                        Storage::disk($storageType)->write($fullPathName, $content);  // Upload

                        $file = array_merge($file, $fileInfo);
                        $file['fileType'] = self::getGroupId($file['extension']);
                        $result['success'][$key] = array_merge($result['success'][$key], $file);

                        Hooks::apply('Files::upload.file.success.post', $file, $params, $result);
                    } catch (\Exception $e) {
                        if (env('APP_DEBUG'))
                            throw new \Exception('Failed to upload file: ' . $file['tmp_name'] . ' | ' .  $file['name'] . ' | ' .  $e->getMessage());

                        $result['errors'][] = $file; // Move to Errors
                    }
                }
            }

            return $result;
        });

        Hooks::apply('Files::upload.post', $params, $result);

        return $result;
    }


    /**
     * Interactive server response with html list
     * @param array File upload data
     */

    private static function interactiveResponse(array $data): string
    {
        return View::load('common/files/interactive-response', $data);
    }


    /**
     * Delete the file considering the server type and server id
     * @param mixed File path
     * @param int|string ID of the remote server
     * @param bool - Interactive server response with html list
     */

    public static function delete(mixed $path, $serverId = 0, $interactiveResponse = false): array
    {
        $result = self::RESULT_SCHEME;
        $result['response']['content'] = Languages::get('common', 'files', 'not_enough_data_to_delete');
        $pathes = [];
        $serverIds = [];

        Hooks::apply('Files::delete.pre', $result, $pathes, $serverId);

        if (is_array($path)) {
            foreach ($path as $key => $val) {

                $pathes[] = [$key => $val];

                if ($val)
                    $serverIds[] = $val;
            }
        } else {
            $pathes[] = [
                $path => $serverId
            ];

            if ($serverId)
                $serverIds[] = $serverId;
        }

        if ($pathes) {

            $result['count'] = count($pathes);

            Hooks::apply('Files::delete.pathes', $pathes, $result);

            $serverList = [];

            if ($serverIds) {
                $serverList = Storage::getRemoteServers([
                    'id' => $serverIds
                ]);
            }

            foreach ($pathes as $pathKey => $pathObject) {

                foreach ($pathObject as $k => $v) {

                    Hooks::apply('Files::delete.file.pre', $k, $v, $result);

                    $srvType = 'local';
                    $srvData = [];

                    if ($v && isset($serverList[$v])) {
                        $srvType = $serverList[$v]['type'] ?? 0;
                        $srvData = ['serverId' => $v];
                    }

                    try {

                        $disk = Storage::disk($srvType, $srvData);

                        if ($disk->fileExists($k)) {

                            $disk->delete($k);

                            $result['success'][] = [
                                'name' => basename($k),
                                'message' => Languages::get('common', 'files', 'file_deleted'),
                                'code' => 'file_deleted'
                            ];
                        } else {


                            $result['errors'][] = [
                                'name' => basename($k),
                                'message' => Languages::get('common', 'files', 'file_not_found'),
                                'code' => 'file_not_found'
                            ];
                        }
                    } catch (\Exception $e) {
                        if (env('APP_DEBUG'))
                            throw new \Exception('Failed to delete file: ' . $k . ' | ' .  $e->getMessage());

                        $result['errors'][] = [
                            'name' => basename($k),
                            'message' => Languages::get('common', 'files', 'file_deletion_error'),
                            'code' => 'file_deletion_error'
                        ];
                    }

                    Hooks::apply('Files::delete.file.post', $k, $v, $result);
                }
            }
        }

        if (!empty($result['success']) && !empty($result['errors']))
            self::response($result, true, 'warning', 'not_all_files_have_been_deleted', 'warning');

        elseif (!empty($result['success']))
            self::response($result, true, 'success', 'files_deleted', true);

        else
            self::response($result, false, 'error', 'files_not_deleted', false);

        self::getLastResponseElement($result);

        if ($interactiveResponse) {
            $result['response']['content'] .= self::interactiveResponse($result);
        }

        Hooks::apply('Files::delete.post', $result);

        return $result;
    }


    /**
     * Creating an entry about a new file in the database
     * @param array Data relative to the table
     */

    public static function registerFile(array $data = array()): bool
    {

        $status = false;
        $allowed = self::FILES_TABLE;
        unset($allowed['file_id']);

        $defFileds = array(
            'file_url' => '',
            'file_path' => '',
            'file_component_id' => 0,
            'file_bind_id' => 0,
            'file_bind_type' => 0,
            'file_bind_helper' => 0,
            'file_type' => 0,
            'file_timestamp' => time(),
            'file_owner' => Account::id(),
            'file_server_id' => 0
        );

        $data = array_merge($defFileds, $data);
        $protected = Security::allowedData($data, $allowed);

        if (!empty($protected['file_url']) && !empty($protected['file_path'])) {
            if (Db::insert('files', $protected)) {
                $status = true;
                $id = Db::id();
                Hooks::apply('Files::registerFile.success', $id, $status, $data);
            }
        }

        return $status;
    }


    /**
     * Get files by parameters
     * @param array Basic parameters
     */

    public static function get(array $params = array()): array
    {

        $result = array();

        $defParams = array(
            'componentId' => false,
            'bindId' => false,
            'bindType' => false,
            'bindHelper' => false,
            'owner' => Account::authorized() ? Account::id() : false,
            'serverId' => false,
            'id' => false,
            'fileType' => false,
            'limit' => 200
        );

        $params = array_merge($defParams, $params);
        $where = [];

        if ($params['componentId'] !== false)
            $where['file_component_id'] = $params['componentId'];

        if ($params['bindId'] !== false)
            $where['file_bind_id'] = $params['bindId'];

        if ($params['bindType'] !== false)
            $where['file_bind_type'] = $params['bindType'];


        if ($params['bindHelper'] !== false)
            $where['file_bind_helper'] = $params['bindHelper'];

        if ($params['fileType'] !== false)
            $where['file_type'] = $params['fileType'];

        if ($params['serverId'] !== false)
            $where['file_server_id'] = $params['serverId'];

        if ($params['owner'] !== false)
            $where['file_owner'] = $params['owner'];

        if ($params['id'] !== false)
            $where['file_id'] = $params['id'];

        $where['LIMIT'] = $params['limit'];

        $result = Db::select('files', '*', $where);


        return $result;
    }


    /**
     * File manager
     * @param string Type of action (upload|delete)
     * @param array Basic parameters
     */

    public static function manager(string $type, array $params = array()): array
    {

        $result = self::RESULT_SCHEME;

        $defParams = array(
            'returnResponse' => false,                             // Get only the server response    
            'componentId' => false,                                // Component ID
            'bindId' => false,                                     // Binding identifier (user_id or post_id ...)
            'bindType' => false,                                   // Binding Type (int)
            'bindHelper' => false,                                 // Binding Assistant (Binds files into a group for example a thumbnail image and the main image)
            'serverId' => false,                                   // ID of the remote server
            'fileType' => false,                                   // File Type Identifier (int) - ( Files::FILE_TYPE_* )
            'owner' => Account::authorized() ? Account::id() : 0,  // File Owner
            'id' => false,                                         // Delete a file by ID (Delete only)
            'interactiveResponse' => false                         // Interactive server response with html list
        );

        $params = array_merge($defParams, $params);

        Hooks::apply('Files::manager.pre', $params, $result);

        if ($type === 'upload') {

            $result['response']['content'] = Languages::get('common', 'files', 'not_enough_data_to_upload');

            Hooks::apply('Files::manager.upload.pre', $params, $result);

            if ($params['componentId'] !== false && $params['bindId'] !== false && $params['bindType'] !== false) {

                Hooks::addOnce('Files::upload.file.success.post', function ($file) use ($params) {

                    self::registerFile([
                        'file_url' => $file['url'],
                        'file_path' => $file['path'],
                        'file_type' => $file['fileType'],
                        'file_server_id' => $file['serverId'],
                        'file_component_id' => $params['componentId'],
                        'file_bind_id' => $params['bindId'],
                        'file_bind_type' => $params['bindType'],
                        'file_bind_helper' => $params['bindHelper'],
                        'file_owner' => $params['owner']
                    ]);
                });

                $result = self::upload($params);
            }

            Hooks::apply('Files::manager.upload.post', $params, $result);
        } elseif ($type === 'delete') {

            $result['response']['content'] = Languages::get('common', 'files', 'not_enough_data_to_delete');

            Hooks::apply('Files::manager.delete.pre', $params, $result);

            $files = self::get([
                'componentId' => $params['componentId'],
                'bindId' => $params['bindId'],
                'bindType' => $params['bindType'],
                'owner' => $params['owner'],
                'serverId' => $params['serverId'],
                'id' => $params['id'],
                'fileType' => $params['fileType']
            ]);

            Hooks::apply('Files::manager.delete.files', $files, $params, $result);

            if ($files) {

                $ids = [];
                $pathes = [];

                foreach ($files as $file) {
                    $ids[] = $file['file_id'];
                    $pathes[$file['file_path']] = intval($file['file_server_id']);
                }

                if ($pathes) {
                    $result = self::delete($pathes, 0,  $params['interactiveResponse']);
                    Db::delete('files', ['file_id' => $ids]);
                    Hooks::apply('Files::manager.delete.files.success', $files, $params, $result);
                }
            }

            Hooks::apply('Files::manager.delete.post', $params, $result);
        }

        Hooks::apply('Files::manager.post', $result, $params, $result);

        if ($params['returnResponse'])
            $result = $result['response'];

        return $result;
    }
}
