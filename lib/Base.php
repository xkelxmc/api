<?php

namespace lib;

use PDO;

class Base
{
    private $fileUrl = '';
    private $userPhotoUrl = '';
    public function __construct($fileUrl = FILE_URL, $userPhotoUrl = USER_PHOTO_URL)
    {
        $this->fileUrl = $fileUrl;
        $this->userPhotoUrl = $userPhotoUrl;
    }

    public function InsertNewMessages($id_org, $card, $title, $text, $text_sms, $sms = 0){
        global $DB;
        $date = time();
        $card = AddZeroBeforeCardCode($card);
        $sql = <<<SQL
INSERT INTO b_messages 
SET id_org = :id_org, 
card = :card, 
title = :title, 
text = :text, 
text_sms = :text_sms,
sms = :sms,
date = :date
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id_org", $id_org, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("title", $title, PDO::PARAM_STR);
        $stmt->bindParam("text", $text, PDO::PARAM_STR);
        $stmt->bindParam("text_sms", $text_sms, PDO::PARAM_STR);
        $stmt->bindParam("sms", $sms, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->execute();
    }
    public function SendSMSformLEGEND($phone, $text, $card, $id_user_to, $tele2 = 0){
        global $DB;
        $date = time();
        $phone = StripAndCheckPhone($phone);
        if(!$phone) return false;
        $sql = <<<SQL
INSERT INTO b_sms_data 
SET id_user = '100', 
type = 'legend', 
id_org_from = '0',
id_delivery = '0', 
delivered = '0', 
phone = :phone, 
text = :text, 
card = :card, 
id_user_to = :idUserTO,
date = :date,
tele2 = :tele2
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("phone", $phone, PDO::PARAM_STR);
        $stmt->bindParam("text", $text, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("idUserTO", $id_user_to, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->bindParam("tele2", $tele2, PDO::PARAM_STR);
        $stmt->execute();
    }
}