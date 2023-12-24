<?php
namespace Energy;

class Client
{
    public static function browser()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $browser        = "";
        $browser_array  = array(
            '/msie/i' =>  'Internet Explorer',
            '/firefox/i' =>  'Firefox',
            '/safari/i' =>  'Safari',
            '/chrome/i' =>  'Chrome',
            '/edga/i' => 'Microsoft EDGE',
            '/xiaomi/i' =>  'Xiaomi Mint',
            '/samsungbrowser/i' =>  'Samsung Internet',
            '/yabrowser/i' =>  'Yandex',
            '/atom/i' =>  'Atom',
            '/ucturbo/i' =>  'UC',
            '/edge/i' =>  'Edge',
            '/opera/i' =>  'Opera',
            '/opr/i' =>  'Opera',
            '/opt/i' =>  'Opera Touch',
            '/netscape/i' =>  'Netscape',
            '/maxthon/i' =>  'Maxthon',
            '/konqueror/i' =>  'Konqueror'
        );
        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $browser = $value;
            }
        }
        return $browser;
    }

    public static function version()
    {
        $Agent = $_SERVER['HTTP_USER_AGENT'];
        $ClientName = self::browser();
        $version = "";
        if ($ClientName == "Yandex")
            $ClientName = "YaBrowser";

        $known = array('Version', $ClientName, 'other');
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $Agent, $matches)) {
        }

        $i = count($matches['browser']);
        if ($i != 1) {
            if (strripos($Agent, "Version") < strripos($Agent, $ClientName)) {
                $version = isset($matches['version'][0]) ? $matches['version'][0] :  "";
            } else {
                $version =  isset($matches['version'][1])  ? $matches['version'][1] : "";
            }
        } else
            $version = $matches['version'][0];

        if ($version == null || $version == "")
            $version = "";

        return  $version;
    }

    public static function os()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $OS =   "Unknown";
        $os_array =   array(
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iOS (iPhone)',
            '/ipod/i'               =>  'iOS (iPod)',
            '/ipad/i'               =>  'iOS (iPad)',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $OS = $value;
            }
        }
        return $OS;
    }

    public static function isMobile($type = 1)
    {
        $bool = preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
        if ($type == 1)
            $result = $bool;
        else
            $result = ($bool) ? "Mobile" : "PC";
        return $result;
    }

    public static function device()
    {
        return array(
            "browser" => self::browser(),
            "v" => self::version(),
            "os" => self::os(),
            "type" => self::isMobile(1),
            "ip" => $_SERVER['REMOTE_ADDR'],
            "device_key" => Encryption::irreversible(self::browser() . self::os())
        );
    }
}
