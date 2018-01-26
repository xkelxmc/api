<?php

namespace lib;

use PDO;

class Keys
{
    private $fileUrl = '';
    private $userPhotoUrl = '';
    public function __construct($fileUrl = FILE_URL, $userPhotoUrl = USER_PHOTO_URL)
    {
        $this->fileUrl = $fileUrl;
        $this->userPhotoUrl = $userPhotoUrl;
    }

    public function GetCompanyKeyById($id){
        global $DB;
        $sql = <<<SQL
SELECT * FROM b_organization_keys WHERE id = :id
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }

    public function UserKeyGet($key){
        global $DB;
        $sql = <<<SQL
SELECT * FROM b_user_keys WHERE keym = :key
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("key", $key, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }

    public function GetUserKeyByKeyId($id, $card){
        global $DB;
        $sql = <<<SQL
SELECT * FROM b_user_keys WHERE card = :card AND key_id = :id ORDER BY id DESC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }

    public function OrgUserKeyGet($key){
        global $DB;
        $sql = <<<SQL
SELECT * FROM b_organization_keys WHERE sell_after_buy = 0 AND code = :code ORDER BY id DESC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("code", $key, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }

    public function InsertUserKeysActive($key, $card, $key_id, $active, $card_from, $back, $key_type_id){
        global $DB;
        $time = time();
        $sql = <<<SQL
INSERT INTO b_user_key_activate 
SET keym=:keym,
card = :card,
active = :active,
card_from = :card_from,
key_id = :key_id,
back = :back,
key_type_id = :key_type_id,
date = :date
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("keym", $key, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("active", $active, PDO::PARAM_STR);
        $stmt->bindParam("card_from", $card_from, PDO::PARAM_STR);
        $stmt->bindParam("key_id", $key_id, PDO::PARAM_STR);
        $stmt->bindParam("back", $back, PDO::PARAM_STR);
        $stmt->bindParam("key_type_id", $key_type_id, PDO::PARAM_STR);
        $stmt->bindParam("date", $time, PDO::PARAM_STR);
        $stmt->execute();
    }
}