<?php

namespace Energy;

use Imagecow\Image;

class Images
{


    /**
     * Image Manager
     * @param mixed File path
     */

    public static function manager($file)
    {
        try {
            return Image::fromFile($file, Image::LIB_IMAGICK);
        } catch (\Exception $e) {
            if (env('APP_DEBUG'))
                throw new \Exception('Image Manager: ' .  $e->getMessage());
        }
    }


    /**
     * Image Manager
     * @param string File path
     * @param array Set the maximum width and height of the image
     * @param mixed Set image cropping parameters (x, y, width, height)
     * @param bool Image cropping is mandatory
     */

    public static function modify(string $path, array $resizeData = array(), mixed $cropData = '', $cropRequired = false): mixed
    {
        $img = self::manager($path);

        if ($cropData || $cropRequired) {
            $cdata = is_array($cropData) ? $cropData : self::sanitizeCropData($cropData);
            $img->crop($cdata['width'] ?? 100, $cdata['height'] ?? 100, $cdata['x'] ?? 0, $cdata['y'] ?? 0);
        }

        $img->resize(
            $resizeData['width'] ?? 200,
            $resizeData['height'] ?? 200,
        );

        return $img->getString();
    }


    /**
     * Sanitization of image cropping data
     * @param string Сropping data
     * @param array Default Settings
     */

    public static function sanitizeCropData(string $data, $params = array())
    {

        $defParams = array(
            'x' => 0,
            'y' => 0,
            'width' => 100,
            'height' => 100
        );

        $params = array_merge($defParams, $params);
        $result = $params;

        if ($cropData = json_decode($data, true)) {
            $result = $cropData;
        }

        return $result;
    }


    /**
     * Uploading an image using a file manager
     * @param array Default Settings
     */

    public static function upload(array $params = array()): array
    {

        $defParams = array(
            'allowExtensions' => Files::IMAGES_L,
            'returnResponse' => true,
            'crop' => '',
            'cropRequired' => false,
            'resize' => array(),
            'thumbnail' => false,
            'thumbnailBindType' => 1,
            'thumbnailResize' => array()
        );

        $params = array_merge($defParams, $params);

        if ($params['thumbnail']) {
      
            $params['thumbnailBindType'] = 1;

            Hooks::addOnce('Files::upload.file.success.pre', function (&$file, &$content) use ($params) {

                if ($file) {

                    $content = self::modify(
                        $file['tmp_name'],
                        [
                            'width' => $params['thumbnailResize']['width'] ?? 50,
                            'height' => $params['thumbnailResize']['width'] ?? 50
                        ],
                        $params['crop'],
                        $params['cropRequired']
                    );
                }
            });

            $saveParams = $params;
            $saveParams['bindHelper'] = 1;

            Files::manager('upload', $saveParams);
        }

        Hooks::addOnce('Files::upload.file.success.pre', function (&$file, &$content) use ($params) {

            if ($file) {

                $content = self::modify(
                    $file['tmp_name'],
                    [
                        'width' => $params['resize']['width'] ?? 100,
                        'height' => $params['resize']['width'] ?? 100
                    ],
                    $params['crop'],
                    $params['cropRequired']
                );
            }
        });

        return Files::manager('upload', $params);
    }

    
    /**
     * Delete an image using the file manager
     * @param array Default Settings
     */

    public static function delete(array $params = array()): array
    {

        $defParams = [
            'bindId' => Account::id(),
            'fileType' => Files::FILE_TYPE_IMAGE,
            'returnResponse' => true
        ];

        $params = array_merge($defParams, $params);

        return Files::manager('delete', $params);
    }
}
