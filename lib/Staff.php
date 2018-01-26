<?php

namespace lib;

use PDO;

class Staff
{
    private $fileUrl = '';
    private $userPhotoUrl = '';
    public function __construct($fileUrl = FILE_URL, $userPhotoUrl = USER_PHOTO_URL)
    {
        $this->fileUrl = $fileUrl;
        $this->userPhotoUrl = $userPhotoUrl;
    }
    public function GetListByBranch($buyID, $orgID, $branchID){
        global $DB;
        $sql = <<<SQL
SELECT staff.user_id as id,
staff.job as job,
usr.NAME as name,
usr.LAST_NAME as surname,
rating.rating as rating,
rating.comment as comment,
CONCAT(:userPhotoUrl, staff.user_id) AS photo
FROM   b_org_staff staff 
       LEFT JOIN b_user usr 
              ON staff.user_id = usr.ID
       LEFT JOIN b_organization_staff_rating rating
              ON rating.id_buy = :buyID AND rating.id_user = staff.user_id
WHERE staff.org_id = :orgID AND staff.aff_id = :branchID AND staff.state = '1' AND usr.ID IS NOT NULL 
ORDER BY staff.user_id ASC
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userPhotoUrl", $this->userPhotoUrl, PDO::PARAM_STR);
        $stmt->bindParam("buyID", $buyID, PDO::PARAM_STR);
        $stmt->bindParam("orgID", $orgID, PDO::PARAM_STR);
        $stmt->bindParam("branchID", $branchID, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetById($buyID, $userID){
        global $DB;
        $sql = "SELECT * FROM   b_organization_staff_rating WHERE id_buy = :buyID AND id_user = :userID";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("buyID", $buyID, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function SetStaffRating($id, $userID, $rating, $message, $time){
        global $DB;
        $sql = <<<SQL
INSERT INTO b_organization_staff_rating 
SET id_buy = :id, id_user = :userID, rating = :rating, comment = :message, date = :date
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->bindParam("rating", $rating, PDO::PARAM_STR);
        $stmt->bindParam("message", $message, PDO::PARAM_STR);
        $stmt->bindParam("date", $time, PDO::PARAM_STR);
        $stmt->execute();
    }
}