<?php

namespace Energy\Components\Captcha;

use Energy\Kernel;
use Energy\Session;
use Energy\View;

class Captcha
{


    /**
     * Generate an image
     */
    public static function image(): void
    {
        $length = 6;
        $config = Kernel::config('components/captcha');
        $fontSize =  $config['fontSize'] ?? 24;
        $allowedCharacters = $config['allowedCharacters'] ?? '1234567890abcdefghijkmnpqrstuvwxyz';
        $textColor = $config['textColor'] ?? array(rand(50, 78), rand(90, 163), rand(80, 122));
        $imageWidth = $config['imageWidth'] ?? 120;
        $imageHeight = $config['imageHeight'] ?? 52;
        $maxLines = $config['maxLines'] ?? 6;
        $minLines = $config['minLines'] ?? 3;
        $textColor = $config['textColor'] ?? array(rand(50, 78), rand(90, 163), rand(80, 122));
        $pointColor = $config['pointColor'] ?? array(77, 77, 77);
        $code = substr(str_shuffle($allowedCharacters), 0, $length);

        $image = imagecreatetruecolor($imageWidth, $imageHeight);

        if ($image !== false) {
            $color = imagecolorallocatealpha($image, 0, 0, 0, 127);

            if ($color !== false) {
                imagesavealpha($image, true);
                imagefill($image, 0, 0, $color);

                $fontList = ['AntykwaBold', 'Ding-DongDaddyO', 'Duality', 'Jura', 'VeraSansBold', 'StayPuft', 'BrahmsGotischCyr', 'Alkalami-Regular'];

                $font = $fontList[random_int(0, count($fontList) - 1)];
                $fontSize = 24;
                $FontCalculate = imagettfbbox($fontSize, 0, __DIR__ . '/fonts/' . $font . '.ttf', $code);

                $angle = random_int(-10, 10);

                $color = imagecolorallocate(
                    $image,
                    $textColor[0],
                    $textColor[1],
                    $textColor[2]
                );

                if ($color !== false) {
                    imagettftext(
                        $image,
                        $fontSize,
                        $angle,
                        (int) (($imageWidth / 2) - ($FontCalculate[4] / 2)),
                        (int)  (($imageHeight / 2) - ($FontCalculate[5] / 2)),
                        $color,
                        __DIR__ . '/fonts/' . $font . '.ttf',
                        $code
                    );


                    for ($i = 0; $i < 1200; $i++) {
                        imagesetpixel(
                            $image,
                            rand() % 200,
                            rand() % 50,
                            imagecolorallocate(
                                $image,
                                $pointColor[0],
                                $pointColor[1],
                                $pointColor[2]
                            )
                        );
                    }

                    for ($i = 0; $i < random_int($minLines, $maxLines); $i++) {
                        imageline(
                            $image,
                            (int)$imageWidth,
                            (int)(($imageHeight / 2) - rand(1, 15) - ($FontCalculate[5] / 2)),
                            random_int(2, 4),
                            (int)(($imageHeight / 2) - rand(1, 25) - ($FontCalculate[5] / 2)),
                            imagecolorallocate(
                                $image,
                                random_int(125, 255),
                                random_int(125, 255),
                                random_int(125, 255)
                            )
                        );
                    }

                    Session::set('captcha', $code);
                }
               
                header('Content-type: image/png');
                imagepng($image);
                imagedestroy($image);
            }
        }
    }


    /**
     * Check the code
     * @param string Code
     */
    public static function verifyCode(string $code): bool
    {

        $result = false;

        if (isset($code) && !empty($code)) {

            if (preg_match('/^[a-zA-Z0-9 \s]+$/', $code) && Session::get('captcha') == $code)
                $result = true;
        }

        return $result;
    }


    /**
     * View captcha template
     * @param array Basic parameters
     */

    public static function view(array $params = array())
    {
        $defParams = [
            'template' => 'components/captcha/view',
            'isReturn' => false,
            'name' => 'signup[captcha]'
        ];

        $params = array_merge($defParams, $params);
        $html = View::load($params['template'], $params);

        if ($params['isReturn'])
            return $html;
        else
            echo $html;
    }
}
