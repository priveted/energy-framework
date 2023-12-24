<?php
namespace Energy;

class Encryption
{
    public static function bin($string = "")
    {
        return bin2hex($string);
    }

    public static function unbin($string = "")
    {
        return hex2bin($string);
    }

    public static function uncompress($string = "")
    {
        return gzinflate(base64_decode(strtr($string, '-_', '+/')));
    }

    public static function compress($string = "")
    {
        return rtrim(strtr(base64_encode(gzdeflate($string, 9)), '+/', '-_'), '=');
    }

    public static function encode($data)
    {
        return self::compress(self::bin(self::encodePlain($data)));
    }

    public static function decode($data)
    {
        return self::decodePlain(self::unbin(self::uncompress($data)));
    }

    public static function decodePlain($data = "")
    {
        return json_decode(base64_decode(@unserialize(gzuncompress(base64_decode(stripslashes($data))))), true);
    }

    public static function encodePlain($data = "")
    {
        return base64_encode(gzcompress(serialize(base64_encode(json_encode($data))), 9));
    }

    public static function irreversibleCompressed($string)
    {
        return md5(md5(self::compress($string)));
    }

    public static function irreversible($string)
    {
        return md5(md5($string));
    }

    public static function irreversibleUniqid($string)
    {
        return self::irreversible($string) . self::random() . time() . uniqid();
    }

    public static function generateToken()
    {
        return self::irreversibleUniqid("first") . "-" . self::irreversibleUniqid("last");
    }

    public static function random($min = 100, $max = 9999999)
    {
        return rand($min, $max);
    }

    public static function verifyPassword($password = '', $hash = '')
    {
        return password_verify($password, $hash);
    }

    public static function createPasswordHash($password = '')
    {
        return password_hash($password, PASSWORD_BCRYPT, array('cost' => 12));
    }
}
