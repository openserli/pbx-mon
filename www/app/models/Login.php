<?php

/*
 * The Login Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Db\Redis;
use Tool\Filter;

class LoginModel {
    public  $db = null;
    public $redis = null;
    public $config = null;
    private $username = null;
    private $password = null;
      
    public function __construct(array $data = null) {
        if (!isset($data['username'], $data['password']) {
            exit(0);
        }

        $this->username = Filter::alpha($data['username'], null, 1, 32);
        $this->password = Filter::string($data['password'], null, 8, 64);

        if ($this->username && $this->password) {
            $this->db = Yaf\Registry::get('db');
            $config = Yaf\Registry::get('config');

            if ($config) {
                $this->config = $config;
                $redis = new Redis($config->redis->host, $config->redis->password);
                $this->redis = $redis->handle;
            }

            $this->password = sha1(md5($this->password));
        }
    }

    public function verify() {
        if ($this->db && $this->username && $this->password) {
            $sth = $this->db->prepare('SELECT * FROM account WHERE username = :username AND password = :password LIMIT 1');
            $sth->bindParam(':username', $this->username, PDO::PARAM_STR);
            $sth->bindParam(':password', $this->password, PDO::PARAM_STR);
            $sth->execute();
            $result = $sth->fetch();

            if (is_array($result) && count($result) > 0) {
                return true;
            }
        }

        return false;
    }

    public function checkAcl($ip = null) {
        $ip = Filter::ip($ip, null);
        if ($ip != null) {
            $ip = ip2long($ip);
            return $this->redis->exists('whitelist.' . $ip);
        }

        return false;
    }
}
