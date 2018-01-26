<?php

namespace lib;


use PDO;

class Companies
{
    private $fileUrl = '';
    public function __construct($fileUrl = FILE_URL)
    {
        $this->fileUrl = $fileUrl;
    }
    public function GetList(){
        global $DB;
        $sql = "SELECT COUNT(*) AS count, CAT_ID AS id, b.name, b.icon_mobile as icon FROM b_organization LEFT JOIN b_organization_cat b ON CAT_ID = b.id WHERE SHORT_TEXT <> '' AND TEXT <> '' AND PHONE <> '' AND ADDRESS <> '' AND INDEX_PICTURE_80 <> '0' GROUP BY CAT_ID ORDER BY b.id asc";
        $stmt = $DB->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetCategoryItemsList($categoryID, $userID){
        global $DB;
        $searchByCat = '';
        $searchByCatSELECT = 'category.name AS category_name, category.icon_mobile AS category_icon, ';
        $searchByCatJOIN = 'left join b_organization_cat category ON a.cat_id = category.id ';
        if(intval($categoryID) != 0){
            $searchByCatSELECT = '';
            $searchByCatJOIN = '';
            $searchByCat = 'a.cat_id = :categoryID AND ';
        }
        $searchByUserSELECT = '';
        $searchByUserJOIN = '';
        $searchByUserORDER = '';
        if($userID){
            $searchByUserSELECT = 'IF(lorg.id IS NOT NULL, 1, 0) AS subscribe,';
            $searchByUserJOIN = 'left join b_organization_like lorg  ON lorg.id_org = a.id AND lorg.user_id = :userID ';
            $searchByUserORDER = 'CASE a.id WHEN :userID THEN 0 END DESC, subscribe DESC, ';
        }
        $sql = <<<SQL
SELECT {$searchByUserSELECT} 
       {$searchByCatSELECT}
       a.cat_id                              AS category_id, 
       a.sort                                AS sort, 
       a.id                                  AS id, 
       CONCAT(:fileUrl, a.index_picture_80)  AS logo, 
       a.name                                AS name, 
       a.short_text                          AS description, 
       a.main_info                           AS discount_info, 
       Count(rating.id)                      AS reviews, 
       SUM(rating.rating) / Count(rating.id) AS rating 
FROM   b_organization a 
       {$searchByCatJOIN}
       {$searchByUserJOIN}
       left join b_organization_rating rating 
              ON rating.id_org = a.id 
                 AND ( rating.COMMENT = '' 
                        OR rating.ACCEPT = '1' ) 
WHERE  {$searchByCat}
       a.short_text <> '' 
       AND a.text <> '' 
       AND a.phone <> '' 
       AND a.address <> '' 
       AND a.index_picture_80 <> '0' 
GROUP BY a.id
ORDER  BY {$searchByUserORDER}
          reviews DESC, 
          sort DESC 
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        if(intval($categoryID) != 0){
            $stmt->bindParam("categoryID", $categoryID, PDO::PARAM_STR);
        }
        if($userID){;
            $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }
    function SaveOrderCompany($org_id, $item_list, $address, $room, $level, $phone, $price, $status, $card){
        $date = time();
        global $DB;
        $sql = "INSERT INTO b_order_org_save SET `date` = :date, org_id = :org_id, item_list = :item_list, address = :address, room = :room, `level` = :level, phone = :phone, price = :price, status = :status, card = :card";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->bindParam("org_id", $org_id, PDO::PARAM_STR);
        $stmt->bindParam("item_list", $item_list, PDO::PARAM_STR);
        $stmt->bindParam("address", $address, PDO::PARAM_STR);
        $stmt->bindParam("room", $room, PDO::PARAM_STR);
        $stmt->bindParam("level", $level, PDO::PARAM_STR);
        $stmt->bindParam("phone", $phone, PDO::PARAM_STR);
        $stmt->bindParam("price", $price, PDO::PARAM_STR);
        $stmt->bindParam("status", $status, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();

    }
    function CompanyCheckSubscribe($companyID, $userID){
        global $DB;
        $sql = "SELECT COUNT(id) AS count FROM b_organization_like WHERE id_org = :orgID AND user_id =:userID";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("orgID", $companyID, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return intval($row->count) == 0 ? false : true;
    }

    function CompanySubscribe($companyID, $userID){
        global $DB;
        $sql = "INSERT INTO b_organization_like SET user_id = :userID, id_org = :companyID";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->execute();
    }

    function CompanyUnSubscribe($companyID, $userID){
        global $DB;
        $sql = "DELETE FROM b_organization_like WHERE user_id = :userID AND id_org = :companyID";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function GetCompanyInfo($companyID, $userID){
        global $DB;

        $sql = <<<SQL
SELECT org.NAME as name,
org.MAIN_INFO as discount_info,
org.TEXT as description,
org.PHONE as phone,
org.SITE as site,
IF(count(shop.id)>0 AND (org.ONLINE_SHOP = 1), true, false) as online_shop,
CONCAT(:fileUrl, org.INDEX_PICTURE_80)  AS logo, 
CONCAT(:fileUrl, org.MOBILE_HEADER)  AS header_picture, 
Count(rating.id)                      AS reviews, 
SUM(rating.rating) / Count(rating.id) AS rating
FROM b_organization org
LEFT JOIN b_organization_rating rating ON org.ID = rating.id_org 
LEFT JOIN b_org_catalog_item shop ON org.ID = shop.org_id 
WHERE org.ID = :companyID
GROUP BY org.ID
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        return $data;
    }
    public function GetCompanyByID($companyID){
        global $DB;

        $sql = <<<SQL
SELECT * FROM b_organization
WHERE ID = :companyID
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        return $data;
    }
    public function GetCompanyBranchList($companyID, $limit, $offset = 0){
        global $DB;

        $sql = <<<SQL
SELECT branch.id as id,
branch.name as name,
branch.address as address,
branch.map_x as map_x,
branch.map_y as map_y,
count(staff.id) as staff,
Count(rating.id)                      AS reviews, 
SUM(rating.rating) / Count(rating.id) AS rating
FROM b_organization_affiliates branch
LEFT JOIN b_org_staff staff ON staff.org_id = branch.org_id AND staff.aff_id = branch.id AND staff.state = '1'
LEFT JOIN b_organization_staff_rating rating ON rating.id_user = staff.user_id 
WHERE branch.org_id = :companyID
GROUP BY branch.id 
ORDER BY branch.sort DESC
LIMIT :limit OFFSET :offset
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);
        $stmt->bindParam("limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam("offset", $offset, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }
    public function GetCompanyGallery($companyID){
        global $DB;

        $sql = <<<SQL
SELECT org.description as title,
CONCAT(:fileUrl, org.id_img)  AS url 
FROM b_org_image org
WHERE org.org_id = :companyID
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }
    public function GetCompanyReviews($companyID){
        global $DB;

        $sql = <<<SQL
SELECT rating.rating  AS rating, 
       uts.value_id   AS id_user, 
       rating.comment AS comment, 
       rating.date    AS date, 
       usr.NAME       AS name, 
       usr.last_name  AS surname 
FROM   b_organization_rating rating 
       LEFT JOIN b_org_buy_list buy 
              ON rating.id_buy = buy.id 
       LEFT JOIN b_uts_user uts 
              ON uts.uf_card_code = buy.card_code 
       LEFT JOIN b_user usr 
              ON usr.id = uts.value_id 
WHERE  rating.id_org = :companyID 
       AND uts.value_id IS NOT NULL 
       AND comment <> '' AND accept = '1'
ORDER  BY rating.date DESC 
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetUserSubscriptions($userID){
        global $DB;

        $sql = <<<SQL
SELECT org.ID as id,
org.NAME as name,
org.MAIN_INFO as discount_info,
1 as subscribe,
org.short_text as description,
CONCAT(:fileUrl, org.INDEX_PICTURE_80)  AS logo
FROM b_organization_like sub
LEFT JOIN b_organization org ON org.ID = sub.id_org 
WHERE sub.user_id = :userID AND org.ID IS NOT NULL
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("userID", $userID, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetCompanyShopCategoriesList($companyID){
        global $DB;

        $sql = <<<SQL
SELECT a.*, 
       Count(b.id) AS count
FROM   b_org_catalog a 
       LEFT JOIN b_org_catalog_item b 
              ON a.id = b.catalog_id 
WHERE  a.org_id = :companyID
GROUP  BY b.catalog_id 
HAVING count > 0 
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetCompanyShopCategoriesItems($companyID, $categoryID){
        global $DB;

        $sql = <<<SQL
SELECT a.*, 
       b.NAME AS catalog, 
       CONCAT(:fileUrl, a.image) AS url 
FROM   b_org_catalog_item a 
       LEFT JOIN b_org_catalog b 
              ON a.catalog_id = b.id 
WHERE  a.org_id = :companyID
       AND a.catalog_id = :categoryID 
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("fileUrl", $this->fileUrl, PDO::PARAM_STR);
        $stmt->bindParam("companyID", $companyID, PDO::PARAM_STR);
        $stmt->bindParam("categoryID", $categoryID, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function GetCompanyShopItems($companyID, $itemIDs){
        global $DB;
        $qMarks = str_repeat('?,', count($itemIDs) - 1) . '?';
        $params = array_merge(array($this->fileUrl, $companyID), $itemIDs );
        $sql = <<<SQL
SELECT a.*, 
       b.NAME AS catalog, 
       CONCAT(?, a.image) AS url 
FROM   b_org_catalog_item a 
       LEFT JOIN b_org_catalog b 
              ON a.catalog_id = b.id 
WHERE  a.org_id = ?
       AND a.id IN ($qMarks) 
SQL;
        $stmt = $DB->prepare($sql);

        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }
}