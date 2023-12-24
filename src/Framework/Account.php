<?php

namespace Energy;

class Account
{

    const LEVEL_USER = 0;
    const LEVEL_ROOT_ADMIN = 1;
    const LEVEL_ADMIN = 2;
    const LEVEL_MODERATOR = 3;
    const LEVEL_MANAGER = 4;
    const LEVEL_ANALYST = 5;

    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_NOT_SPECIFIED = 2;

    public const ALL_TABLE_COLUMS = array(
        'user_id',
        'user_email',
        'user_system_uid',
        'user_username',
        'user_password',
        'user_ip',
        'user_ip_signup',
        'user_login_timestamp',
        'user_signup_timestamp',
        'user_level',
        'user_banned',
        'user_banned_timestamp',
        'user_login_confirm',
        'user_firstname',
        'user_lastname',
        'user_gender',
        'user_deleted',
        'user_deleted_timestamp',
        'user_timezone',
        'user_language'
    );

    public const ORDER_TABLE_COLUMS = array(
        'user_id',
        'user_email',
        'user_username',
        'user_login_timestamp',
        'user_signup_timestamp',
        'user_level',
        'user_banned',
        'user_banned_timestamp',
        'user_firstname',
        'user_lastname',
        'user_gender',
        'user_deleted',
        'user_deleted_timestamp'
    );

    public static function authorized()
    {
        return (Session::get('user') !== '' && Session::get('user', 'user_id') > 0);
    }

    public static function get($key = '')
    {
        $data = Session::get('user');
        $result = false;
        if ($data) {
            if ($key === '') {
                $result = $data;
            } else
                $result = isset($data['user_' . $key]) ? $data['user_' . $key] : false;
        }
        return $result;
    }

    public static function set($key, $value)
    {
        Session::set('user', ['user_' . $key =>  $value]);
    }

    public static function id()
    {
        return self::get('id');
    }

    public static function isBanned($user)
    {
        $count = Db::count('users', [
            'user_banned' => 1,
            'OR' => [
                'user_username' => $user,
                'user_email' => $user,
                'user_id' => $user,
            ]
        ]);

        return !!$count;
    }

    public static function getBanTimestamp($user)
    {

        $sql = Db::get('users', ['user_banned_timestamp'], [
            'user_banned' => 1,
            'OR' => [
                'user_username' => $user,
                'user_email' => $user,
                'user_id' => $user
            ]
        ]);

        return $sql['user_banned_timestamp'] ?? 0;
    }

    public static function isLoginConfirm($user)
    {
        $count = Db::count('users', [
            'user_login_confirm' => 1,
            'OR' => [
                'user_username' => $user,
                'user_email' => $user,
                'user_id' => $user,
            ]
        ]);

        return !!$count;
    }

    public static function isLevelAllowsChange($level = 0): bool
    {
        return (self::get('level') <= $level || $level == self::LEVEL_USER);
    }

    public static function getLevel($user = '')
    {
        $result = 0;

        if ($user === '' && self::authorized()) {
            $result = self::get('level');
        } else {

            $sql = Db::get('users', ['user_level'], [
                'OR' => [
                    'user_username' => $user,
                    'user_email' => $user,
                    'user_id' => $user
                ]
            ]);

            if (isset($sql['user_level']))
                $result = intval($sql['user_level']);
        }
        return $result;
    }


    public static function getDataLevels($level = false, $key = '')
    {

        $list = array(
            array(
                'level' => self::LEVEL_USER,
                'type' => 'user',
                'title' => Languages::get('common', 'user')
            ),
            array(
                'level' => self::LEVEL_ROOT_ADMIN,
                'type' => 'root_admin',
                'title' => Languages::get('common', 'root_admin')
            ),
            array(
                'level' => self::LEVEL_ADMIN,
                'type' => 'admin',
                'title' => Languages::get('common', 'admin')
            ),
            array(
                'level' => self::LEVEL_MODERATOR,
                'type' => 'moderator',
                'title' => Languages::get('common', 'moderator')
            ),
            array(
                'level' => self::LEVEL_MANAGER,
                'type' => 'manager',
                'title' => Languages::get('common', 'manager')
            ),
            array(
                'level' => self::LEVEL_ANALYST,
                'type' => 'analyst',
                'title' => Languages::get('common', 'analyst')
            )
        );

        $result = $list;

        if ($level || $level !== false) {

            if ($key)
                $result = isset($list[$level][$key]) ? $list[$level][$key] : '';
            else
                $result = $list[$level] ?? $list[0];
        }


        return $result;
    }

    public static function getListMaxLevels($maxLevel = false, $allowUser = false)
    {

        $list = self::getDataLevels();

        if ($maxLevel) {
            $newList = $list;
            $list = [];
            foreach ($newList as $item) {
                if ($maxLevel <= $item['level'] || ($allowUser && $item['level'] == 0))
                    $list[$item['level']] = $item;
            }
        }

        return $list;
    }

    public static function isUsername($user)
    {

        $reserved = Seo::isReservedName($user);

        $count = Db::count('users', [
            'OR' => [
                'user_system_uid' => $user,
                'user_username' => $user,
            ]
        ]);

        return (!!$count || $reserved);
    }

    public static function isUserSystemId($u)
    {
        return !!preg_match('/^id[0-9]+$/', $u);
    }

    public static function isUsernameFormat($u): bool
    {
        return !!(!self::isUserSystemId($u) && preg_match('/^[a-z][a-z_0-9]+[a-z0-9]+$/', $u));
    }

    public static function isNameFormat($n)
    {
        return !!(preg_match("/^[a-zа-яё]+$/iu", $n));
    }

    public static function isShortName($n)
    {
        return (mb_strlen($n) < 2);
    }

    public static function isEmail($email)
    {
        return !!Db::count('users', ['user_email' => $email]);
    }

    public static function isEmailFormat($e)
    {
        return Security::sanitizeEmail($e);
    }

    public static function getEmail($user = '')
    {

        $result = false;

        if ($user === '' && self::authorized()) {
            $result = self::get('email');
        } else {
            $sql = Db::get('users', ['user_email'], [
                'OR' => [
                    'user_id' => $user,
                    'user_email' => $user,
                    'user_username' => $user
                ]
            ]);
            $result = $sql['user_email'] ?? false;
        }

        return $result;
    }

    public static function create($fields = array())
    {
        $result = false;
        $allowed = self::ALL_TABLE_COLUMS;

        Hooks::apply('Account::create.pre', $fields, $allowed);

        if ($fields && is_array($fields)) {

            $protected = Security::allowedData($fields, $allowed);

            $defFileds = array(
                'user_email' => false,
                'user_password' => false,
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'user_ip_signup' => $_SERVER['REMOTE_ADDR'],
                'user_login_timestamp' => time(),
                'user_signup_timestamp' => time(),
                'user_level' => 0,
                'user_banned' => 0,
                'user_login_confirm' => 0,
                'user_gender' => 0,
                'user_deleted' => 0,
                'user_timezone' => 0,
                'user_language' => Languages::getSelected('id'),
            );

            $fields = array_merge($defFileds, $protected);

            Hooks::apply('Account::create.fields', $fields, $allowed);

            if ($fields['user_email'] && $fields['user_password']) {

                $fields['user_password'] = Encryption::createPasswordHash($fields['user_password']);
                Db::insert('users', $fields);
                $userId = Db::id();

                Db::update('users', [
                    'user_username' => 'id' . $userId,
                    'user_system_uid' => 'id' . $userId
                ], [
                    'user_id' => $userId
                ]);

                Hooks::apply('Account::create.user', $userId, $fields, $allowed);

                $result = true;
            }
        }

        Hooks::apply('Account::create.post', $result, $fields);

        return $result;
    }

    public static function update($user, $fields = array(), $unlockFree = false)
    {
        $result = false;
        $allowed = self::ALL_TABLE_COLUMS;

        Hooks::apply('Account::update.pre', $user, $fields, $unlockFree, $allowed);

        if ($unlockFree) {
            $allowed[] = 'user_id';
            $allowed[] = 'user_system_uid';
        }

        if ($fields && is_array($fields)) {

            $protected = Security::allowedData($fields, $allowed);

            $defFileds = array(
                'user_ip' => $_SERVER['REMOTE_ADDR']
            );

            Hooks::apply('Account::update.fields', $protected, $user, $allowed);

            if ($protected) {

                $fields = array_merge($defFileds, $protected);

                Hooks::apply('Account::update.fields.protected', $fields, $user, $allowed);

                if (isset($fields['user_password']))
                    $fields['user_password'] = Encryption::createPasswordHash($fields['user_password']);

                $sql = Db::select('users', ['user_id'], [
                    'OR' => [
                        'user_id' => $user,
                        'user_email' => $user,
                        'user_username' => $user
                    ]
                ]);

                if ($sql) {
                    $ids = [];
                    foreach ($sql  as $item) {
                        $ids[] = $item['user_id'];
                    }
                    Db::update('users', $fields, [
                        'user_id' => $ids
                    ]);

                    Hooks::apply('Account::update.user', $ids, $fields);

                    $result = true;
                }
            }
        }

        Hooks::apply('Account::update.post', $result, $fields, $unlockFree);

        return $result;
    }

    public static function updatePassword($user, $password, $login = false)
    {
        $result = false;
        if ($user && $password) {

            $one = Db::get('users', [
                'user_id',
                'user_password'
            ], [
                'OR' => [
                    'user_username' => $user,
                    'user_email' => $user,
                    'user_id' => $user,
                ]
            ]);

            if ($one && isset($one['user_id'])) {

                $userId = $one['user_id'];

                self::createOldPasswordHash($userId, $one['user_password']);
                Db::delete('user_devices', ['user_id' => $userId]);

                $update = [
                    'user_password' => Encryption::createPasswordHash($password)
                ];

                if (self::isBanned($userId) &&  self::getBanTimestamp($userId) < time()) {
                    $update['user_banned'] = 0;
                    $update['user_banned_timestamp'] = 0;
                }

                Db::update('users', $update, [
                    'user_id' => $userId
                ]);

                if ($login)
                    self::login($userId);

                $result = true;
            }
        }

        return $result;
    }

    public static function checkPassword($user, $password)
    {
        $result = false;

        if ($user && $password) {
            $sql = Db::get('users', ['user_password'], [
                'OR' => [
                    'user_username' => $user,
                    'user_email' => $user,
                    'user_id' => $user,
                ]
            ]);

            if (isset($sql['user_password'])) {
                $result = Encryption::verifyPassword($password, $sql['user_password']);
            }
        }

        return $result;
    }

    public static function createOldPasswordHash($userId, $hash)
    {
        $result = false;
        if ($userId && $hash) {
            Db::insert('user_old_passwords', [
                'p_user_id' => $userId,
                'p_password' => $hash
            ]);
            $result = true;
        }

        return $result;
    }

    public static function isOldPassword($user, $password)
    {
        $result = false;

        if ($user && $password) {

            $one = Db::get('users', [
                'user_id',
                'user_password'
            ], [
                'OR' => [
                    'user_username' => $user,
                    'user_email' => $user,
                    'user_id' => $user
                ]
            ]);

            if ($one && isset($one['user_id'])) {
                $realPassword = Encryption::verifyPassword($password, $one['user_password']);
                if ($realPassword) {
                    $result = true;
                } else {
                    $old = Db::select('user_old_passwords', ['p_password'], [
                        'p_user_id' => $one['user_id']
                    ]);
                    if ($old) {
                        foreach ($old as $item) {
                            if (Encryption::verifyPassword($password, $item['p_password'])) {
                                $result = true;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    public static function isPasswordFormat($str = false): bool
    {
        $result = false;

        if ($str)
            $result = !preg_match('~[\\<>]~', $str);

        return $result;
    }

    public static function concatName($firstname = '', $lastname = '', $username = '')
    {
        return ($firstname == '') ?  $username : $firstname  . ' ' .  $lastname;
    }

    public static function getName($id = '', $extended = false)
    {
        $users = array();

        // Session user
        if ($id == '') {
            $arr = array(
                'fullName' => (self::get('firstname') == '') ? self::get('username') : self::get('firstname') . ' ' . self::get('lastname')
            );

            if ($extended) {
                $arr['user_id'] = self::get('id');
                $arr['user_firstname'] = self::get('firstname');
                $arr['user_lastname'] = self::get('lastname');
                $arr['user_username'] = self::get('username');
            }
            $users = array($arr);
        } else {
            if ($id) {

                $sql = Db::select('users', [
                    'user_id',
                    'user_firstname',
                    'user_lastname',
                    'user_username'
                ], [
                    'user_id' => $id
                ]);

                if ($sql) {
                    foreach ($sql as $user) {
                        $user['fullName'] =  ($user['user_firstname'] == '') ? $user['user_username'] : $user['user_firstname']  . ' ' . $user['user_lastname'];
                        $users[] = ($extended) ?  $user : array('fullName' => $user['fullName']);
                    }
                } else
                    $users = array();
            }
        }

        if ($users) {
            if (!is_array($id))
                $name = ($extended) ? $users[0] : $users[0]['fullName'];
            else
                $name = $users;
        } else
            $name = false;

        return $name;
    }

    public static function isAdmin()
    {
        if (Kernel::config('config', 'allow_admin') && self::authorized() && self::get('level') > 0)
            $r = true;
        elseif (!Kernel::config('config', 'allow_admin') && self::authorized() && (self::get('level') == 1 || self::get('level') == 2))
            $r = true;
        else
            $r = false;
        return  $r;
    }

    // ID : 1 => 'root_admin', 2 => 'admin', 3 => 'moderator', 4 => 'manager', 5 => 'analyst'
    public static function isAdminLevel($id = 0, $isOnlyLevel = false)
    {
        if ($id > 0) {

            $return = false;

            if (self::authorized() && self::getLevel() > 0) {
                if ((!$isOnlyLevel && self::getLevel() <= $id) || ($isOnlyLevel && self::getLevel() == $id)) {
                    $return = true;
                }
            }
        } else
            $return = true;

        return $return;
    }

    public static function isAdminRoute()
    {
        return (self::isAdmin() && Controllers::getRoute(0) === 'admin' && self::authorized() && self::get('level') > 0);
    }

    public static function login($user)
    {
        $a_key = Encryption::irreversible(uniqid() . time());
        $device =  Client::device();
        $result = false;

        if ($user) {

            $sql = Db::get('users', [
                'user_id',
                'user_language'
            ], [
                'OR' => [
                    'user_id' => $user,
                    'user_email' => $user,
                    'user_username' => $user
                ]
            ]);

            if ($sql) {

                $userId = $sql['user_id'];
                $data = array('user_id' => $userId, 'key' => $a_key);
                $timestamp_active = time() + 60 * 60 * 24 * 30;

                Db::delete('user_devices', [
                    'AND' => [
                        'user_id' => $userId,
                        'os' => $device['os'],
                        'device_type' => $device['type'],
                        'browser' =>  $device['browser']
                    ]
                ]);

                Db::insert('user_devices', [
                    'user_id' => $userId,
                    'timestamp_active' => $timestamp_active,
                    'key' => $a_key,
                    'os' => $device['os'],
                    'ip' => $device['ip'],
                    'device_type' => $device['type'],
                    'browser' => $device['browser'],
                    'timestamp' => time()
                ]);

                Session::set('user.device_id', Db::id());

                // Delete old sessions
                $oldDevices = Db::select('user_devices', [
                    'id',
                    'timestamp_active'
                ], [
                    'user_id' => $userId
                ]);

                if ($oldDevices) {
                    $protected = [];
                    foreach ($oldDevices as $item) {
                        if (time() >  $item['timestamp_active'])
                            $protected[] = $item['id'];
                    }

                    if ($protected) {
                        Db::delete('user_devices', [
                            'id' => $protected
                        ]);
                    }
                }

                // Select the user's language
                if (Kernel::config('config', 'allow_user_language_switch')) {
                    $lang_code = Languages::getById($sql['user_language'], 'code');
                    if ($lang_code)
                        Languages::select($lang_code);
                }


                // Update the last login to your account
                Db::update('users', [
                    'user_login_timestamp' => time(),
                    'user_ip' => $_SERVER['REMOTE_ADDR']
                ], [
                    'user_id' => $userId
                ]);


                $one = Db::get('users', '*', [
                    'user_id' => $userId
                ]);

                if ($one) {

                    Cookie::set('_u', $data, [
                        'secure' => Kernel::config('config', 'cookie_secure'),
                        'domain' => Kernel::config('config', 'cookie_domain')
                    ]);

                    unset($one['user_password']);
                    Session::set('user', $one);

                    $result = true;
                }
            } else {
                $result = false;
            }
        }
        return $result;
    }

    public static function refreshDevices($where = array())
    {
        $result = false;

        $defWhere = array(
            'user_id' => self::id()
        );

        $where = array_merge($defWhere, $where);

        if (self::authorized()) {

            Db::update(
                'user_devices',
                [
                    'refresh' => 1
                ],
                $where
            );

            $result = true;
        }

        return $result;
    }

    public static function remember()
    {
        $cookie = Cookie::get('_u');
        if ($cookie) {

            $data = $cookie;
            $device = Client::device();

            if (isset($data['user_id']) && isset($data['key'])) {

                $one = Db::get('user_devices', [
                    'id',
                    'refresh'
                ], [
                    'AND' => [
                        'user_id' => $data['user_id'],
                        'key' => $data['key'],
                        'os' => $device['os'],
                        'device_type' => $device['type'],
                        'browser' => $device['browser'],
                    ]
                ]);

                if ($one && isset($one['id'])) {

                    if (Session::get('user') === '') {
                        self::login($data['user_id']);
                        Url::refresh();
                    } else {

                        if ($one['refresh']) {

                            Db::update(
                                'user_devices',
                                [
                                    'refresh' => 0
                                ],
                                [
                                    'key' =>  $data['key'],
                                    'os' => Client::os(),
                                    'browser' => Client::browser(),
                                    'device_type' => Client::isMobile(1),
                                    'user_id' => self::id()
                                ]
                            );


                            $u = Db::get('users', '*', [
                                'user_id' => self::id()
                            ]);

                            unset($u['user_password']);
                            Session::delete('user', 'user_image');
                            Session::set('user', $u);
                        }
                    }
                } else
                    self::logout();
            } else
                self::logout();
        } else {
            if (Session::get('user') !== '')
                self::logout();
        }
    }

    public static function logout($redirect = '')
    {
        $device = Client::device();
        $cookie = Cookie::get('_u');
        $userId = 0;
        $result = false;

        if (self::authorized())
            $userId = self::id();
        elseif ($cookie && isset($cookie['user_id']))
            $userId = $cookie['user_id'];

        if ($userId) {

            Db::delete('user_devices', [
                'AND' => [
                    'user_id' => $userId,
                    'os' => $device['os'],
                    'device_type' => $device['type'],
                    'browser' => $device['browser']
                ]
            ]);

            // If the site is inactive and accessible only by an access key
            if (!Kernel::config('config', 'site_online')) {
                if (Session::get('site_access_allowed')) {
                    Session::delete('site_access_allowed');
                }
            }

            Cookie::delete('_u');
            Session::delete('user');
            $result = true;
        }

        if ($redirect)
            Url::redirect($redirect ? $redirect : '/');

        return $result;
    }


    public static function refreshSessionImage(bool $clear = false): void
    {
        if (self::authorized()) {

            if (empty(self::get('image')) || $clear) {
                $url = '';

                $file = Files::get([
                    'componentId' => 1,
                    'bindId' => self::id(),
                    'bindType' => 0,
                    'bindHelper' => 1,
                    'fileType' => Files::FILE_TYPE_IMAGE
                ]);

                if (!empty($file[0]['file_url']))
                    $url = $file[0]['file_url'];

                self::set('image', $url);
            }
        }
    }
}
