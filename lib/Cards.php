<?php

namespace lib;


use PDO;

class Cards
{
    private $fileUrl = '';
    public function __construct($fileUrl = FILE_URL)
    {
        $this->fileUrl = $fileUrl;
    }
    public function GetCardAdmin($card){
        global $DB;
        $sql = "SELECT USER_ID, CODE FROM b_user_cards WHERE CARD_CODE = :card";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetCardUser($card){
        global $DB;
        $sql = "SELECT VALUE_ID FROM b_uts_user WHERE UF_CARD_CODE = :card";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        return $data;
    }

    function UpdateCardState($card, $userID)
    {
        global $DB;
        $date = time();
        $sql = "UPDATE b_user_cards SET USER_CARD = :userID, DATE = :date WHERE CARD_CODE = :card";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
    }
}