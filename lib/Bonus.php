<?php

namespace lib;


use PDO;

class Bonus
{
    private $fileUrl = '';
    public function __construct($fileUrl = FILE_URL)
    {
        $this->fileUrl = $fileUrl;
    }
    public function GetByCard($card){
        global $DB;
        $sql = "SELECT b.bonus, b.org_id, CONCAT(:fileUrl , org.INDEX_PICTURE_80) as org_logo, org.NAME as org_name, org.CAT_ID as cat_id FROM b_card_bonus b LEFT JOIN b_organization org ON org.ID = b.org_id WHERE org.ID IS NOT NULL AND b.bonus > 0 AND b.card=:card ORDER BY b.bonus DESC";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetByCardInOrg($card, $orgID){
        global $DB;
        $sql = <<<SQL
SELECT bonus FROM b_card_bonus WHERE org_id = :orgID AND card = :card
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("orgID", $orgID, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return $userData;
    }
    public function GetCountByCardInOrg($card, $orgID){
        $bonus = $this->GetByCardInOrg($card, $orgID);
        return intval($bonus->bonus);
    }
    public function InsertUserBonus($card, $orgID, $bonus){
        global $DB;
        $sql = <<<SQL
INSERT INTO b_card_bonus SET org_id = :orgID, card = :card, bonus = :bonus
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("orgID", $orgID, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("bonus", $bonus, PDO::PARAM_STR);
        $stmt->execute();
    }

    private function UpdateBonus($card, $bonus, $orgID){
        global $DB;
        $sql = <<<SQL
UPDATE b_card_bonus SET bonus= :bonus WHERE org_id = :orgID AND card = :card
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("bonus", $bonus, PDO::PARAM_STR);
        $stmt->bindParam("orgID", $orgID, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function AddHistoryPromoUp($id_org, $card, $count, $id_promo, $time = false){
        global $DB;
        $type = 'promo-up';
        $up_down = 'up';
        $time = ($time == false) ? time() : $time;
        $date = date("Y-m-d H:i:s", $time);

        global $DB;
        $sql = <<<SQL
INSERT INTO b_card_bonus_history 
SET type = :type, 
    up_down = :up_down, 
    id_org = :id_org, 
    card = :card, 
    count = :count, 
    id_promo = :id_promo, 
    date = :date, 
    date_unix = :date_unix 
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("type", $type, PDO::PARAM_STR);
        $stmt->bindParam("up_down", $up_down, PDO::PARAM_STR);
        $stmt->bindParam("id_org", $id_org, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->bindParam("count", intval($count), PDO::PARAM_STR);
        $stmt->bindParam("id_promo", $id_promo, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->bindParam("date_unix", $time, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function UpdateUserBonus($card, $bonus, $orgID)
    {
        $userData = $this->GetByCardInOrg($card, $orgID);

        if($userData){
            $this->UpdateBonus($card, $bonus, $orgID);
        }else{
            $this->InsertUserBonus($card, $orgID, $bonus);
        }
    }
}