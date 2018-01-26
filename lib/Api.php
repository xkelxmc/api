<?php

namespace lib;


use Exception;
use Firebase\JWT\JWT;


class Api
{
    private $key = null;
    private $events = array();
    private $response = '';
    private $status = 'ok';
    private $userID = 0;
    private $auth = false;
    public $version = '3.0';
    private $JWTkey = "SLEjVcGXqG3P1fy5yCKRLpttYW7nSNIi";

    public function __construct($JWTkey, $key)
    {
        $this->JWTkey = $JWTkey;
        $this->key = $key;
        date_default_timezone_set('Asia/Omsk');
    }

    public function createKey($userID, $login)
    {
        $token = array(
            "url" => "https://legendcity.ru",
            "id" => $userID,
            "login" => $login
        );
        $key = JWT::encode($token, $this->getJWTkey());
        $this->key = $key;
    }
    public function createSessionKey($IP)
    {
        $token = array(
            "url" => "https://legendcity.ru",
            "id" => $IP,
        );
        $key = JWT::encode($token, $this->getJWTkey());
        $this->key = $key;
    }
    public function createLoginEmailKey($IP, $login, $email, $card)
    {
        $token = array(
            "url" => "https://legendcity.ru",
            "id" => $IP,
            "login" => $login,
            "email" => $email,
            "card" => $card,
        );
        $key = JWT::encode($token, $this->getJWTkey());
        $this->key = $key;
    }
    public function createRestoreKey($IP, $login, $card)
    {
        $token = array(
            "url" => "https://legendcity.ru",
            "id" => $IP,
            "login" => $login,
            "card" => $card,
        );
        $key = JWT::encode($token, $this->getJWTkey());
        $this->key = $key;
    }
    public function checkUserAuth()
    {
        if ($this->key == "") {
            $this->addEvents('Ошибка авторизации', 'Был передан пустой параметр token.');
            $this->status = 'auth_error';
            $this->auth = false;
            exit($this->response()->getResponse());
        }

        try {
            $decoded = JWT::decode($this->key, $this->JWTkey, array('HS256'));
            $decoded_array = (array)$decoded;
        } catch (Exception $e) {
            $this->addEvents('Ошибка авторизации', 'Ключ авторизации устарел или не валиден.');
            $this->status = 'auth_error';
            $this->auth = false;
            exit($this->response()->getResponse());
        }

        $this->userID = $decoded_array['id'];

        $USER = new User();
        $arUser = $USER->GetById($this->userID);

        if ($arUser->LOGIN != $decoded_array['login']) {
            $this->addEvents('Ошибка авторизации', 'Ключ был поврежден. Логин не совпадает.');
            $this->status = 'auth_error';
            $this->auth = false;
            exit($this->response()->getResponse());
        }

        $this->status = 'ok';
        $this->auth = true;
        return $arUser;
    }

    private function obj()
    {
        $loObj = (object)null;
        $lnArgCnt = func_num_args();
        for ($lnArgNo = 0; $lnArgNo < $lnArgCnt; $lnArgNo += 2) {
            $loObj->{func_get_arg($lnArgNo)} = func_get_arg($lnArgNo + 1);
        }
        return $loObj;
    }

    public function response($data = null)
    {
//        $data = is_null($data) ? $this->obj() : $data;
        $response = $this->obj(
            "events", $this->events,
            "data", $data,
            "settings", $this->obj("time", time(), "date", date('Y-m-d H:i:s', time()), "version", $this->version, "IP", $_SERVER['REMOTE_ADDR']),
            "status", $this->status,
            "token", $this->key
        );
        header('Content-type: application/json; charset=utf-8');
        $this->response = json_encode($response, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param array $events
     */
    public function setEvents($events)
    {
        $this->events = $events;
    }

    /**
     * @param string $title
     * @param string $text
     */
    public function addEvents($title, $text)
    {
        $this->events[] = array('title' => $title, 'text' => $text);
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isAuth()
    {
        return $this->auth;
    }

    /**
     * @return int
     */
    public function getUserID()
    {
        return $this->userID;
    }


    /**
     * @return string
     */
    public function getJWTkey()
    {
        return $this->JWTkey;
    }
}