<?php

namespace lib;

use PDO;

class Certificate
{
    private $fileUrl = '';
    private $userPhotoUrl = '';
    public function __construct($fileUrl = FILE_URL, $userPhotoUrl = USER_PHOTO_URL)
    {
        $this->fileUrl = $fileUrl;
        $this->userPhotoUrl = $userPhotoUrl;
    }

    public function GetTakeBuyId($id){
        global $DB;
        $sql = <<<SQL
SELECT * FROM b_sertificat_take WHERE id = :id
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }

    public function UpdateTakeCountSend($id, $count){
        global $DB;
        $sql = <<<SQL
UPDATE b_sertificat_take SET promo_sell=:count WHERE id = :id
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("count", $count, PDO::PARAM_STR);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function AddSend($sertificat_id, $card, $from_take, $filial_id = 0)
    {
        global $DB;
        $card = AddZeroBeforeCardCode($card);
        $time = time();
        $sql = <<<SQL
INSERT INTO b_sertificat_sell 
SET sertificat_id=:sertificat_id,
card = :card,
from_take = :from_take,
date = :date,
aff_id = :aff_id
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("sertificat_id", $sertificat_id, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("from_take", $from_take, PDO::PARAM_STR);
        $stmt->bindParam("date", $time, PDO::PARAM_STR);
        $stmt->bindParam("aff_id", $filial_id, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function GetCertificateByID($id){
        global $DB;
        $sql = <<<SQL
SELECT * FROM b_sertificat_s WHERE id = :id
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }

    public function UpdateCountTakePromo($id, $count)
    {
        global $DB;
        $sql = <<<SQL
UPDATE b_sertificat_s SET promo_sell=:count WHERE id = :id
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("count", $count, PDO::PARAM_STR);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->execute();
    }
}