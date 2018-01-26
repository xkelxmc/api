<?php
namespace lib;


use PDO;

class User
{
    private $fileUrl = '';
    public function __construct($fileUrl = FILE_URL)
    {
        $this->fileUrl = $fileUrl;
    }
    public function GetById($id){
        global $DB;
        $sql = "SELECT u.*, uts.* FROM b_user u LEFT JOIN b_uts_user uts ON uts.VALUE_ID = u.ID WHERE ID=:id ";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }
    public function SetUserParam($userID, $param, $value){
        global $DB;

        $set=substr("`".str_replace("`","``",$param)."`". "=:value, ", 0, -2);
        $sql = "UPDATE b_uts_user SET $set WHERE VALUE_ID = :userID";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->bindParam("value", $value, PDO::PARAM_STR);
        $stmt->execute();
    }
    public function GetUserInfo($userID){
        global $DB;
        $sql = <<<SQL
SELECT u.NAME, 
       u.last_name, 
       Count(org.id) AS count_sub,
       uts.UF_PARENT_ID as parent,
       uts.UF_EXPER_TYPE as business_type,
       uts.UF_MONEY_RUB as money
FROM   b_user u 
       LEFT JOIN b_uts_user uts 
              ON uts.value_id = u.id 
       LEFT JOIN b_organization_like sub 
              ON sub.user_id = u.id 
       LEFT JOIN b_organization org 
              ON sub.id_org = org.id 
                 AND org.id IS NOT NULL 
WHERE  u.id = :userID 
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }

    public function GetUserNotification($card, $limit, $offset = 0){
        global $DB;
        $limits = "";
        if($limit != 0){
            $limits = "LIMIT :limit OFFSET :offset";
        }
        $sql = <<<SQL
SELECT id, title, text, `date`
FROM   b_messages
WHERE  card = :card  
ORDER BY id DESC
$limits
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        if($limit != 0) {
            $stmt->bindParam("limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam("offset", $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        $userData = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $userData;
    }
    public function GetUserCertificates($card, $time){
        global $DB;

        $sql = <<<SQL
SELECT
cert.id as id,
cert.active as active,
cert.date as date,
@date_end:= IF(cert_info.time_of_action <> 0, cert.date + cert_info.time_of_action * 604800, cert_info.date_finish)as date_end,
IF(@date_end > :times, 1, 0) as active_date,
@date_middle:=@date_end - :times,
IF(@date_middle < 259200, 'red', IF(@date_middle < 259200, '604800 ', 'green')) as date_end_color,
cert_info.name as name,
cert_info.description as description,
cert_info.org_id as org_id,
org.NAME as org_name,
CONCAT(:fileUrl, org.INDEX_PICTURE_80)  AS logo,
CONCAT(:fileUrl, org.MOBILE_HEADER)  AS header_picture
FROM b_sertificat_sell cert 
LEFT JOIN b_sertificat_s cert_info ON cert_info.id = cert.sertificat_id
LEFT JOIN b_organization org ON cert_info.org_id = org.ID
WHERE cert.card = :card AND cert_info.ID IS NOT NULL AND org.ID IS NOT NULL
ORDER BY date_end ASC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("times", $time, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }
    public function GetUserCertificatesItem($card, $certID, $time){
        global $DB;

        $sql = <<<SQL
SELECT
cert.id as id,
cert.active as active,
cert.date as date,
@date_end:= IF(cert_info.time_of_action <> 0, cert.date + cert_info.time_of_action * 604800, cert_info.date_finish)as date_end,
IF(@date_end > :times, 1, 0) as active_date,
@date_middle:=@date_end - :times,
IF(@date_middle < 259200, 'red', IF(@date_middle < 259200, '604800 ', 'green')) as date_end_color,
cert_info.name as name,
cert_info.description as description,
cert_info.org_id as org_id,
org.NAME as org_name,
CONCAT(:fileUrl, org.INDEX_PICTURE_80)  AS logo,
CONCAT(:fileUrl, org.MOBILE_HEADER)  AS header_picture
FROM b_sertificat_sell cert 
LEFT JOIN b_sertificat_s cert_info ON cert_info.id = cert.sertificat_id
LEFT JOIN b_organization org ON cert_info.org_id = org.ID
WHERE cert.card = :card AND cert_info.ID IS NOT NULL AND org.ID IS NOT NULL AND cert.id = :certID
ORDER BY date_end ASC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("certID", $certID, PDO::PARAM_STR);
        $stmt->bindParam("times", $time, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetUserPromocodes($card){
        global $DB;
        $sql = <<<SQL
SELECT keym.id as id,
keym.name as name,
keym.description as description,
keym.text_for_sell as text_for_sell,
keym.count_give_bonus_from as count_give_bonus_from,
keym.count_give_bonus_to as count_give_bonus_to,
keym.certificat_id as certificat_id,
keym.back_money_lvl as back_money_lvl,
IF(keym.type = 0, 1, keym.type) as type,
usrkeys.keym as 'key',
org.ID as org_id,
org_cert.ID as org_cert_id,
org.NAME as org_name,
CONCAT(:fileUrl, org.INDEX_PICTURE_80)  AS org_logo,
CONCAT(:fileUrl, org.MOBILE_HEADER)  AS org_header_picture,
CONCAT(:fileUrl, org_cert.INDEX_PICTURE_80)  AS cert_org_logo
FROM b_organization_keys keym
LEFT JOIN b_organization org ON org.ID = keym.id_org
LEFT JOIN b_user_keys usrkeys ON usrkeys.key_id = keym.id AND usrkeys.card = :card
LEFT JOIN b_sertificat_take cert_take ON cert_take.id = keym.certificat_id 
LEFT JOIN b_sertificat_s cert ON cert.id = cert_take.sertificat_id
LEFT JOIN b_organization org_cert ON org_cert.ID = cert.org_id
WHERE org.ID IS NOT NULL AND keym.sell_after_buy = 1
ORDER BY usrkeys.keym DESC, keym.id DESC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $userData;
    }

    public function GetUserActivePromocodes($pkey, $card){
        global $DB;
        $sql = <<<SQL
SELECT * FROM b_user_key_activate WHERE keym = :pkey AND card = :card
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("pkey", $pkey, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }

    public function UpdateUserKeysActive($id, $back){
        global $DB;
        $sql = <<<SQL
UPDATE b_user_key_activate SET active = 1, back = :back WHERE id = :id
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("back", $back, PDO::PARAM_STR);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function GetShoppingByID($userID, $limit, $offset = 0){
        global $DB;
        $limits = "";
        if($limit != 0){
            $limits = "LIMIT :limit OFFSET :offset";
        }
        $sql = <<<SQL
SELECT    a.date, 
          a.id_buy, 
          a.type, 
          a.comment, 
          a.money, 
          b.org_id, 
          b.card_code, 
          b.price, 
          b.discount_price,
          CONCAT(:fileUrl, org.INDEX_PICTURE_80)  AS logo
FROM      b_shopping a 
LEFT JOIN b_org_buy_list b 
       ON b.id=a.id_buy
LEFT JOIN b_organization org 
       ON org.ID = b.ORG_ID 
WHERE  a.id_user_two = :userID
ORDER BY a.date DESC
$limits
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        if($limit != 0) {
            $stmt->bindParam("limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam("offset", $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }
    public function GetPaymentsByID($userID, $limit, $offset = 0){
        global $DB;
        $limits = "";
        if($limit != 0){
            $limits = "LIMIT :limit OFFSET :offset";
        }
        $sql = <<<SQL
SELECT    a.state,
          a.date,
          a.update_date,
          a.summ,
          p.number,
          b.name as type_name,
          b.commission as type_commission,
          CONCAT(:fileUrl, b.img_small)  AS logo
FROM      b_payment_send a 
LEFT JOIN b_user_payment p 
       ON p.id=a.payment_id
LEFT JOIN b_payment_types b 
       ON b.id=p.payment_type_id
WHERE  a.user_id = :userID
ORDER BY a.id DESC
$limits
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        if($limit != 0) {
            $stmt->bindParam("limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam("offset", $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }
    public function GetPaymentsTypesByID($userID){
        global $DB;
        $sql = <<<SQL
SELECT    p.id,
          p.number,
          p.name,
          p.active,
          p.surname,
          b.name as type_name,
          b.commission as type_commission,
          b.start_price as type_start_price,
          CONCAT(:fileUrl, b.img_small)  AS logo
FROM      b_user_payment p 
LEFT JOIN b_payment_types b 
       ON b.id=p.payment_type_id
WHERE  p.user_id = :userID
ORDER BY p.id DESC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetPaymentsTypes(){
        global $DB;
        $sql = <<<SQL
SELECT    *,
          CONCAT(:fileUrl, img_small)  AS logo
FROM      b_payment_types
ORDER BY id DESC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetPaymentsTypeByID($paymentID){
        global $DB;
        $sql = <<<SQL
SELECT    *,
          CONCAT(:fileUrl, img_small)  AS logo
FROM      b_payment_types
WHERE id = :paymentID
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("paymentID", $paymentID, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        return $data;
    }
    public function GetUserPaymentsTypeByID($paymentID){
        global $DB;
        $sql = <<<SQL
SELECT    *
FROM      b_user_payment
WHERE id = :paymentID
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("paymentID", $paymentID, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        return $data;
    }
    public function InsertPaymentsType($userID, $payment_type_ID, $number, $name, $surname, $key){
        global $DB;
        $sql = <<<SQL
INSERT INTO b_user_payment 
SET user_id = :userID, payment_type_id = :paymentID, number = :number, name = :name, surname = :surname, key_pay = :key
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->bindParam("paymentID", $payment_type_ID, PDO::PARAM_STR);
        $stmt->bindParam("number", $number, PDO::PARAM_STR);
        $stmt->bindParam("name", $name, PDO::PARAM_STR);
        $stmt->bindParam("surname", $surname, PDO::PARAM_STR);
        $stmt->bindParam("key", $key, PDO::PARAM_STR);
        $stmt->execute();
        $lastId = $DB->lastInsertId();
        return $lastId;
    }
    public function InsertNewCashOuth($userID, $paymentID, $money){
        global $DB;
        $date = time();
        $sql = <<<SQL
INSERT INTO b_payment_send 
SET user_id = :userID, payment_id = :paymentID, summ = :money, date = :date, state = 'new', update_date = :update_date
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->bindParam("paymentID", $paymentID, PDO::PARAM_STR);
        $stmt->bindParam("money", $money, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->bindParam("update_date", $date, PDO::PARAM_STR);
        $stmt->execute();
        $lastId = $DB->lastInsertId();
        return $lastId;
    }
    public function updateUserPaymentsTypeToActive($id){
        global $DB;
        $sql = "UPDATE b_user_payment SET active = '1' WHERE id = :id";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->execute();
    }

    function InsertGoogleKey($userID, $key){
        global $DB;
        $sql = "SELECT * FROM b_auth_mobile WHERE user_id =:userID";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);

        if(!$data){
            $this->storeGoogleKey($userID, $key);
        }else{
            $this->updateGoogleKey($data->id, $key);
        }
    }
    function updateGoogleKey($ID, $key){
        global $DB;
        $date = time();
        $sql = "UPDATE b_auth_mobile SET google_key = :googleKey, last_auth = :date, mobile = '1' WHERE id = :ID";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("ID", $ID, PDO::PARAM_STR);
        $stmt->bindParam("googleKey", $key, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->execute();
    }
    function storeGoogleKey($userID, $key){
        global $DB;
        $date = time();
        $sql = "INSERT INTO b_auth_mobile SET user_id = :userID, google_key = :googleKey, last_auth = :date, mobile = '1'";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->bindParam("googleKey", $key, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->execute();
    }
}