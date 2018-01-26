<?php

namespace lib;


use PDO;

class Buys
{
    private $fileUrl = '';
    public function __construct($fileUrl = FILE_URL)
    {
        $this->fileUrl = $fileUrl;
    }
    public function GetByCard($card, $time, $limit, $offset = 0){
        global $DB;
        $limits = "";
        if($limit != 0){
            $limits = "LIMIT :limit OFFSET :offset";
        }
        $sql = <<<SQL
SELECT buy.ID as id,
buy.DISCOUNT_PRICE as discount_price,
buy.PRICE as price,
buy.ORG_BONUS as bonus_up, 
buy.ORG_BONUS_DOWN as bonus_down,
buy.ORG_BONUS - buy.ORG_BONUS_DOWN as bonus_summ,
ABS(buy.ORG_BONUS - buy.ORG_BONUS_DOWN) as bonus_summ_abs,
buy.DATE as date,
org.ID as org_id,
org.NAME as org_name,
IF((:times - buy.DATE) < 604800, 1, 0) as can_review,
rating.rating as rating,
rating.comment as comment,
count(staff.id) as staff,
CONCAT(:fileUrl, org.INDEX_PICTURE_80)  AS logo,
CONCAT(:fileUrl, org.MOBILE_HEADER)  AS header_picture
FROM   b_org_buy_list buy 
       LEFT JOIN b_organization org 
              ON org.ID = buy.ORG_ID 
       LEFT JOIN b_organization_rating rating
              ON rating.id_buy = buy.ID
       LEFT JOIN b_org_staff staff
              ON staff.org_id = buy.ORG_ID AND staff.aff_id = buy.FILIAL_ID AND staff.state = '1'
WHERE  buy.CARD_CODE = :card
GROUP BY buy.ID
ORDER BY buy.DATE DESC
$limits
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("times", $time, PDO::PARAM_STR);
        if($limit != 0) {
            $stmt->bindParam("limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam("offset", $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetById($id, $card, $time){
        global $DB;
        $sql = <<<SQL
SELECT buy.ID as id,
buy.DISCOUNT_PRICE as discount_price,
buy.PRICE as price,
buy.ORG_BONUS as bonus_up, 
buy.ORG_BONUS_DOWN as bonus_down,
buy.ORG_BONUS - buy.ORG_BONUS_DOWN as bonus_summ,
ABS(buy.ORG_BONUS - buy.ORG_BONUS_DOWN) as bonus_summ_abs,
buy.DATE as date,
buy.FILIAL_ID as aff_id,
org.ID as org_id,
org.NAME as org_name,
IF((:times - buy.DATE) < 604800, 1, 0) as can_review,
rating.rating as rating,
rating.comment as comment,
count(staff.id) as staff,
CONCAT(:fileUrl, org.INDEX_PICTURE_80)  AS logo,
CONCAT(:fileUrl, org.MOBILE_HEADER)  AS header_picture
FROM   b_org_buy_list buy 
       LEFT JOIN b_organization org 
              ON org.ID = buy.ORG_ID 
       LEFT JOIN b_organization_rating rating
              ON rating.id_buy = buy.ID
       LEFT JOIN b_org_staff staff
              ON staff.org_id = buy.ORG_ID AND staff.aff_id = buy.FILIAL_ID AND staff.state = '1'
WHERE  buy.ID = :id AND buy.CARD_CODE = :card
GROUP BY buy.ID
ORDER BY buy.DATE DESC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("times", $time, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        return $data;
    }


    public function SetBuyRating($id, $branchID, $orgID, $rating, $message, $time){
        global $DB;
        $sql = <<<SQL
INSERT INTO b_organization_rating 
SET id_buy = :id, id_aff = :branchID, id_org = :orgID, rating = :rating, comment = :message, date = :date
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->bindParam("branchID", $branchID, PDO::PARAM_STR);
        $stmt->bindParam("orgID", $orgID, PDO::PARAM_STR);
        $stmt->bindParam("rating", $rating, PDO::PARAM_STR);
        $stmt->bindParam("message", $message, PDO::PARAM_STR);
        $stmt->bindParam("date", $time, PDO::PARAM_STR);
        $stmt->execute();
    }
}