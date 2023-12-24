<?php

namespace Energy;

use HTMLPurifier_Config;
use HTMLPurifier;
use Energy\Core\Utils\ObjectData;

class Security
{

    public const ALLOWED_TAGS = [
        "a",
        "abbr",
        "address",
        "article",
        "audio",
        "b",
        "bdi",
        "bdo",
        "blockquote",
        "br",
        "canvas",
        "caption",
        "cite",
        "code",
        "col",
        "colgroup",
        "dd",
        "del",
        "div",
        "em",
        "fieldset",
        "figcaption",
        "figure",
        "h1",
        "h2",
        "h3",
        "h4",
        "h5",
        "h6",
        "hr",
        "i",
        "img",
        "li",
        "mark",
        "ol",
        "p",
        "picture",
        "pre",
        "q",
        "s",
        "section",
        "small",
        "span",
        "strong",
        "sub",
        "summary",
        "sup",
        "table",
        "tbody",
        "td",
        "tfoot",
        "th",
        "thead",
        "time",
        "tr",
        "track",
        "u",
        "ul",
        "video",
        'iframe'
    ];

    private static $purifierStatus = false;
    private static $purifierConfig = false;
    private static $purifier = false;

    public static function getAttemptCount($name, $max = 10)
    {
        $Attempts = Session::get('attempt_count.' . $name);
        return ($Attempts >= $max);
    }

    public static function setAttemptCount($name, $default = 1)
    {
        $Attempts =  Session::get('attempt_count.' . $name);
        $Attempts = (!$Attempts) ? 1 : intval($Attempts + $default);
        Session::set('attempt_count.' . $name, $Attempts);
    }

    public static function deleteAttemptCount($name)
    {
        Session::delete('attempt_count.' . $name);
    }

    public static function createCSRFToken()
    {
        Session::set(
            'core_csrf_token',
            uniqid() . Encryption::irreversible(
                Encryption::random() . uniqid()
            ) . Encryption::irreversible(
                uniqid()
            )
        );
    }

    public static function getCSRFToken()
    {
        $token =  Session::get('core_csrf_token');

        if (empty($token))
            $token = uniqid() . Encryption::irreversible(
                Encryption::random() . uniqid()
            ) . Encryption::irreversible(
                uniqid()
            );

        return $token;
    }

    public static function isCSRFToken()
    {
        return !empty(Session::get('core_csrf_token'));
    }

    public static function escapeHTML($data, $revert = false)
    {
        $charset = Kernel::config('config', 'charset');
        $charset = $charset ? $charset : 'UTF-8';
        if (is_array($data)) {
            foreach ($data as $k => $sub) {
                if (is_string($k)) {
                    $_k = ($revert == false)
                        ? htmlspecialchars($k, ENT_QUOTES, $charset)
                        : htmlspecialchars_decode($k, ENT_QUOTES);
                    if ($k != $_k) {
                        unset($data[$k]);
                    }
                } else {
                    $_k = $k;
                }
                if (is_array($sub) === true) {
                    $data[$_k] = self::escapeHTML($sub, $revert);
                } elseif (is_string($sub)) {
                    $data[$_k] = ($revert == false)
                        ? htmlspecialchars($sub, ENT_QUOTES,  $charset)
                        : htmlspecialchars_decode($sub, ENT_QUOTES);
                }
            }
        } else {
            $data = ($revert == false)
                ? htmlspecialchars($data, ENT_QUOTES, $charset)
                : htmlspecialchars_decode($data, ENT_QUOTES);
        }

        return $data;
    }

    public static function sanitizeHTML($html = "", $escapePreformatted = true)
    {
        try {
            $result = $html;
            if (!self::$purifierStatus) {

                self::$purifierStatus =  true;
                self::$purifierConfig = HTMLPurifier_Config::createDefault();
                self::$purifierConfig->set('HTML.DefinitionID', '1');

                self::$purifierConfig->set('HTML.AllowedAttributes', [
                    // def
                    '*.class'               => true,
                    '*.title'               => true,

                    // a
                    'a.target'              => true,
                    'a.href'                => true,

                    // img 
                    'img.src'               => true,
                    'img.width'             => true,
                    'img.height'            => true,
                    'img.alt'               => true,

                    // iframe
                    'iframe.style'           => true,
                    'iframe.src'             => true,
                    'iframe.frameborder'     => true,
                    'iframe.width'           => true,
                    'iframe.height'          => true,
                ]);


                self::$purifierConfig->set('HTML.SafeIframe', true);

                $allowedDomains = [];
                foreach (Kernel::config('config', 'allowed_iframe_domains') as $domain) {
                    $quotedDomain = preg_quote($domain);
                    $allowedDomains[] = $quotedDomain;
                }
                if ($allowedDomains)
                    $allowedDomains = implode('|', $allowedDomains);

                self::$purifierConfig->set('URI.SafeIframeRegexp', '%^(https?:)?//(' . $allowedDomains . ')/%');

                self::$purifierConfig->set('AutoFormat.AutoParagraph', true);
                self::$purifierConfig->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
                self::$purifierConfig->set('AutoFormat.RemoveEmpty', true);
                self::$purifierConfig->set('AutoFormat.Linkify', true);

                self::$purifierConfig->set('Core.EscapeInvalidTags', true);
                self::$purifierConfig->set('Core.EscapeInvalidChildren', true);

                self::$purifierConfig->set('Attr.AllowedFrameTargets', ['_blank']);
                self::$purifierConfig->set('HTML.TargetNoreferrer', false);
                self::$purifierConfig->set('HTML.TargetNoopener', false);
                self::$purifierConfig->set('Cache.DefinitionImpl', null);

                if ($def = self::$purifierConfig->maybeGetRawHTMLDefinition()) {
                    $def->addElement('figcaption', 'Block', 'Flow', 'Common');
                    $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
                }

                Hooks::apply('Security::sanitizeHTML', self::$purifierConfig, $result);

                self::$purifier = new HTMLPurifier(self::$purifierConfig);
            }

            $result = (self::$purifier) ? self::$purifier->purify($result) : self::escapeHTML($result);

            if ($escapePreformatted)
                $result = self::escapeTagContent(['pre', 'code'], $result);

            $result = self::removeEmptyTags($result);
            return trim(str_replace(array('&amp;', "\r", "\n"), array('&', '', ''), $result));
        } catch (\Exception $e) {
            if (env('APP_DEBUG'))
                throw new \Exception('An error has been detected | ' .  $e->getMessage());
        }
    }

    public static function removeEmptyTags($str = '')
    {
        $regexp = '/<[^\/>]*>([\s]?)*<\/[^>]*>/';
        $str = preg_replace($regexp, '', $str);

        if (preg_match($regexp, $str))
            $str = self::removeEmptyTags($str);

        return $str;
    }

    public static function sanitizeFileName($filename)
    {
        $special_chars = array(
            '\\', '/', ':', '*', '?', '"', '<', '>', '|',
            '+', ' ', '%', '!', '@', '&', '$', '#', '`',
            ';', '(', ')', chr(0)
        );

        $filename = preg_replace("#\x{00a0}#siu", ' ', $filename);
        $filename = str_replace($special_chars, '_', $filename);

        return $filename;
    }

    public static function sanitizeEmail(string $email)
    {
        $result = '';

        if ($email) {
            if (preg_match('/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i', $email))
                $result = self::stringFilter(filter_var($email, FILTER_VALIDATE_EMAIL));
        }

        return $result;
    }

    public static function sanitizePhone($phone): int
    {
        $phone = intval(preg_replace('/[^0-9]/', '', $phone));
        return (strlen($phone) > 10) ? $phone : 0;
    }

    public static function sanitize($data = array(), $scheme = array(), $strictScheme = true)
    {

        $result = array();

        if (is_array($data)) {
            foreach ($data as $key =>  $value) {
                if (is_array($data[$key])) {
                    if (is_array($value) && isset($scheme[$key])) {
                        $result[$key] = self::sanitize($data[$key], $scheme[$key], $strictScheme);
                    }
                } else {

                    foreach ($scheme as $sk => $sv) {
                        if ($sk === $key) {

                            $dataKey = $data[$key];

                            if (is_array($sv)) {
                                $default  = '';

                                if (!empty($sv['default']))
                                    $default = $sv['default'];

                                if (!empty($sv['maxLength']) && is_int($sv['maxLength']))
                                    $dataKey = mb_strimwidth($dataKey, 0, $sv['maxLength']);

                                if (!empty($sv['allowed']))
                                    $dataKey = self::allowed($data[$key], is_array($sv['allowed']) ? $sv['allowed'] : array($sv['allowed']), $default);

                                $sv = $sv['type'] ?? 'string';
                            }
                            
                            switch ($sv) {
                                case "html":
                                    $result[$key] = self::sanitizeHTML($dataKey);
                                    break;
                                case 'escape':
                                    $result[$key] = self::escapeHTML($dataKey);
                                    break;
                                case 'string':
                                    $result[$key] = self::stringFilter($dataKey);
                                    break;
                                case 'int':
                                    $result[$key] = intval(self::stringFilter($dataKey));
                                    break;
                                case 'float':
                                    $result[$key] = floatval(self::stringFilter($dataKey));
                                    break;
                                case 'email':
                                    $result[$key] = self::sanitizeEmail($dataKey);
                                case 'phone':
                                    $result[$key] = self::sanitizePhone($dataKey);
                                case 'filename':
                                    $result[$key] = self::sanitizeFileName($dataKey);
                                default:
                                    $result[$key] = self::stringFilter($dataKey);
                                    break;
                            }
                        }
                    }

                    if (!$strictScheme) {
                        if ($diff = array_diff_key($data, $scheme)) {
                            foreach ($diff as $sk => $sv) {
                                if ($sk == $key) {
                                    $result[$key] = self::stringFilter($data[$key]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $object = new ObjectData($result);

        return $object;
    }


    public static function stringFilter($string = '')
    {
        $string = self::escapeHTML($string, true);

        return str_replace(
            array("\r\n", "\r", "\n"),
            '',
            strip_tags(
                stripslashes(
                    nl2br(
                        $string
                    )
                )
            )
        );
    }

    public static function clearSymbols($string = '')
    {
        return preg_replace('/[^\p{L}\p{N}]/u', '',  $string);
    }

    public static function allowed(string $key, array $allowed, mixed $default = '')
    {
        return (in_array($key, $allowed)) ? $key : $default;
    }

    public static function allowedData(array $data = array(), $allowed = array())
    {
        $protected = array();

        if ($data && $allowed) {

            foreach ($data as $key => $value) {
                if (in_array($key, $allowed)) {
                    $protected[$key] = $value;
                }
            }
        }

        return $protected;
    }


    public static function sanitizeDateTimeObject($object = array())
    {
        $defObject = array(
            'year' => '',
            'month' =>  '',
            'day' => '',
            'hour' => 0,
            'minute' => 0,
            'second' => 0,
        );

        $object = array_merge($defObject, $object);

        return DateTimeTool::getNormalizedDateTime(
            intval($object['year']),
            intval($object['month']),
            intval($object['day']),
            intval($object['hour']),
            intval($object['minute']),
            intval($object['second'])
        );
    }


    protected static function stringWasChanged($old, $new)
    {
        foreach (array('old', 'new') as $var) {
            $$var = str_replace(array(' ', "\t", "\r", "\n"), '', $$var);
        }
        return $old != $new;
    }

    public static function allowedTags($string, $tags = array(), $override = false)
    {
        $result = array(
            "a",
            "abbr",
            "address",
            "article",
            "audio",
            "b",
            "bdi",
            "bdo",
            "blockquote",
            "br",
            "canvas",
            "caption",
            "cite",
            "code",
            "col",
            "colgroup",
            "dd",
            "del",
            "div",
            "em",
            "fieldset",
            "figcaption",
            "figure",
            "h1",
            "h2",
            "h3",
            "h4",
            "h5",
            "h6",
            "hr",
            "i",
            "img",
            "li",
            "mark",
            "ol",
            "p",
            "picture",
            "pre",
            "q",
            "s",
            "section",
            "small",
            "span",
            "strong",
            "sub",
            "summary",
            "sup",
            "table",
            "tbody",
            "td",
            "tfoot",
            "th",
            "thead",
            "time",
            "tr",
            "track",
            "u",
            "ul",
            "video",
            'iframe'
        );

        if ($tags && is_array($tags))
            $result = ($override) ? $tags : array_merge($result, $tags);

        return strip_tags($string, $result);
    }

    public static function escapeTagContent(mixed $tags, string $content)
    {
        $result = '';
        if (!is_array($tags))
            $tags = explode('.', $tags);

        if ($tags) {
            foreach ($tags as $key => $value) {
                $result =  preg_replace_callback('/(?<=<' . $value . '>)(.*?)(?=<\/' . $value . '>)/si', function ($m) use ($tags, $key) {

                    if (count($tags) == $key + 1)
                        return htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                    else {
                        unset($tags[$key]);
                        array_values($tags);
                        return self::escapeTagContent($tags, $m[1]);
                    }
                }, $content);
            }
        }

        return $result;
    }

    public static function sanitizeFiltersData($data = array(), $allowed = array())
    {

        $filters = array();

        if ($data) {
            foreach ($data as $key => $val) {
                $rkey = preg_replace('/[^-a-z0-9_\\/]+/i', '', $key);

                if ($rkey != $key && is_array($val))
                    $val = $val[0] ?? '';

                if (in_array($rkey, $allowed))
                    $filters[$key] = $val;
            }
        }

        return $filters;
    }
}
