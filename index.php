<?php
require 'config.php';
require 'Slim/Slim.php';
require 'Firebase/JWT/JWT.php';
require 'lib/Api.php';
require 'lib/DB.php';
require 'lib/User.php';
require 'common.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');    // cache for 1 day
header('Access-Control-Allow-Headers "origin, x-requested-with, content-type"');
header('Access-Control-Allow-Methods "PUT, GET, POST, DELETE, OPTIONS"');

$DB = new \lib\DB();
$DB = $DB->connect();

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app->notFound(function () use ($app) {
    $Api = new \lib\Api(SITE_KEY, '');
    $Api->setStatus('error');
    $Api->addEvents('404', '404 Page Not Found');
    exit($Api->response()->getResponse());
});
$app->post('/login','login'); /* User login */
$app->post('/restore','restore'); /* User login */
$app->post('/restore/check','restoreCheck'); /* User login */
$app->post('/restore/password','restorePassword'); /* User login */

$app->post('/signup', 'signup'); /* Регистрация. Проверка карты, кода карты, телефона, емейла, отправка кода подтверждения в SMS */
$app->post('/signup/check', 'signupCheck'); /* Регистрация. Проверка кода из SMS */
$app->post('/signup/create', 'signupCreate'); /* Регистрация. Создание пользователя */

$app->post('/user/info', 'userInfo'); /* Почение информации о пользователи */
$app->post('/user/notification', 'userNotification'); /* Почение списка уведомлений */
$app->post('/user/device_token', 'userSetDeviceToken'); /* Сохранение ключа google */
$app->post('/user/bonus', 'userBonus'); /* Почение списка бонусов пользователя */
$app->post('/user/buys', 'userBuys'); /* Почение списка покупок пользователя */
$app->post('/user/business/money', 'userBusinessMoney'); /* История начисления и списания средств */
$app->post('/user/business/payments', 'userBusinessPayments'); /* История запросов на вывод средств */
$app->post('/user/business/payments/types', 'userBusinessPaymentsTypes'); /* Счета для вывода средств */
$app->post('/user/business/payments/types/list', 'userBusinessPaymentsTypesList'); /* Доступные счета для вывода средств */
$app->post('/user/business/payments/types/create', 'userBusinessPaymentsTypesCreate'); /* Доступные счета для вывода средств */
$app->post('/user/business/payments/types/check', 'userBusinessPaymentsTypesCheck'); /* Доступные счета для вывода средств */
$app->post('/user/business/payments/cash_out', 'userBusinessPaymentsCashOut'); /* Доступные счета для вывода средств */
$app->post('/user/business/payments/cash_out/check', 'userBusinessPaymentsCashOutCheck'); /* Доступные счета для вывода средств */
$app->post('/user/buys/item', 'userBuyInfo'); /* Почение информации об одной покупке */
$app->post('/user/buys/item/setrating', 'userBuyRatingSet'); /* Отправка отзыва о покупке */
$app->post('/user/buys/item/staff/list', 'userBuyStaffList'); /* Почение списка персонала по ID покупки */
$app->post('/user/buys/item/staff/setrating', 'userBuyStaffRatingSet'); /* Отправка отзыва о персонале */
$app->post('/user/subscriptions', 'userSubscriptions'); /* Почение списка подписок клиента */
$app->post('/user/certificates', 'userCertificates'); /* Почение списка сертификатов клиента */
$app->post('/user/certificates/item', 'userCertificatesItem'); /* Почение информации об одном сертификате */
$app->post('/user/promocodes', 'userPromocodes'); /* Почение списка промокодов для клиента */
$app->post('/user/promocodes/send', 'userPromocodesSend'); /* Активация промокода */

$app->post('/companies/category/list', 'listCategories'); /* Почение списка категорий компаний */
$app->post('/companies/category/items', 'categoryItems'); /* Почение списка компаний в категории */
$app->post('/companies/subscribe', 'companySubscribe'); /* Подписка на компанию */
$app->post('/companies/unsubscribe', 'companyUnSubscribe'); /* Отписка от компании */
$app->post('/companies/item/info', 'companyItemInfo'); /* Получение информации о компании */
$app->post('/companies/item/branch/list', 'companyItemBranchList'); /* Почение списка филиалов компании */
$app->post('/companies/item/gallery', 'companyItemGallery'); /* Почение списка изображений галереи компании */
$app->post('/companies/item/reviews', 'companyItemReviews'); /* Почение списка отзывов о компании */
$app->post('/companies/item/shop/categories/list', 'companyItemShopCatList'); /* Почение списка категорий интернет магазина */
$app->post('/companies/item/shop/categories/items', 'companyItemShopCatItems'); /* Почение списка товаров в категории интернет магазина */
$app->post('/companies/item/shop/items', 'companyItemShopItems'); /* Почение списка товаров в по массиу id интернет магазина */
$app->post('/companies/item/shop/order', 'companyItemShopOrder'); /* Создание заказа в интернет магазине */

$app->post('/test', 'test');

$app->run();

/************************* USER LOGIN *************************************/
/* ### User login ### */

function test(){
//    var_dump($_SERVER['REMOTE_ADDR']);
    $salt = randString(32);
    echo 'test: '.$salt;
}
function restore() {

    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, "");

    try {
        global $DB;
        $IP = $request->getIp();
        $Api->createSessionKey($IP);

        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $check_sms_key = $Session->get($Api->getKey(), 'restore_password_time');
        $time = time();
        if($check_sms_key + 5 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Запрос на востановление пароля можно отправлять только раз в 5 секунд');
            exit($Api->response()->getResponse());
        }
        $Session->set($Api->getKey(), 'restore_password_time', $time);

        $login = htmlspecialchars(strip_tags(trim($data->login)));
        if($login == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили номер телефона');
            exit($Api->response()->getResponse());
        }

        $card = AddZeroBeforeCardCode($data->card);
        if($card == AddZeroBeforeCardCode(0)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой номер карты');
            exit($Api->response()->getResponse());
        }

        $sql = "SELECT a.ID, a.NAME, a.LAST_NAME, a.EMAIL, a.LOGIN, a.ACTIVE, a.PASSWORD, a.CONFIRM_CODE, uts.UF_CARD_CODE FROM b_user a LEFT JOIN b_uts_user uts ON uts.VALUE_ID = a.ID WHERE LOGIN=:login and uts.UF_CARD_CODE = :card and (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='')  ";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("login", $login, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);

        if(!$userData){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь не найден');
            exit($Api->response()->getResponse());
        }

        $Api->createRestoreKey($IP, $login, $card);

        $check_sms_key = $Session->get($Api->getKey(), 'restore_date');
        if($check_sms_key + 60 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы можете запросить новый код через: '.($check_sms_key + 60 - $time));
            exit($Api->response()->getResponse());
        }

        require_once 'lib/Base.php';
        $BaseClass = new \lib\Base();

        $Session->set($Api->getKey(), 'restore_date', $time);
        $key = generateKey(4);
        $Session->set($Api->getKey(), 'restore_check_key', $key);

        $text = "Код подтверждения: $key";
        $BaseClass->SendSMSformLEGEND($login, $text, $card, $userData->ID, 1);

        $Api->addEvents('Отлично', 'Код для востановления пароля отправлен в SMS');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка изменения пароля', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function restoreCheck() {

    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, "");

    try {
        global $DB;
        $IP = $request->getIp();
        $Api->createSessionKey($IP);

        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $check_sms_key = $Session->get($Api->getKey(), 'restore_password_check_time');
        $time = time();
        if($check_sms_key + 5 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Запрос на востановление пароля можно отправлять только раз в 5 секунд');
            exit($Api->response()->getResponse());
        }
        $Session->set($Api->getKey(), 'restore_password_check_time', $time);

        $login = htmlspecialchars(strip_tags(trim($data->login)));
        if($login == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили номер телефона');
            exit($Api->response()->getResponse());
        }

        $card = AddZeroBeforeCardCode($data->card);
        if($card == AddZeroBeforeCardCode(0)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой номер карты');
            exit($Api->response()->getResponse());
        }

        $key = isset($data->key) ? (intval($data->key) < 0 ? 0 : intval($data->key)) : 0 ;
        if($key == 0 || strlen(strval($key)) != 4){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код должен состоять из 4 цифр');
            exit($Api->response()->getResponse());
        }

        $sql = "SELECT a.ID, a.NAME, a.LAST_NAME, a.EMAIL, a.LOGIN, a.ACTIVE, a.PASSWORD, a.CONFIRM_CODE, uts.UF_CARD_CODE FROM b_user a LEFT JOIN b_uts_user uts ON uts.VALUE_ID = a.ID WHERE LOGIN=:login and uts.UF_CARD_CODE = :card and (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='')  ";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("login", $login, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);

        if(!$userData){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь не найден');
            exit($Api->response()->getResponse());
        }

        $Api->createRestoreKey($IP, $login, $card);

        $key_store = $Session->get($Api->getKey(), 'restore_check_key');

        if($key_store != $key){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код не вереный');
            exit($Api->response()->getResponse());
        }
        $Session->delete($Api->getKey(), 'restore_check_key');
        $Session->delete($Api->getKey(), 'restore_date');

        $key = randString(32);
        $Session->set($Api->getKey(), 'restore_start_date', $time);
        $Session->set($Api->getKey(), 'restore_change_code', $key);

        $Api->addEvents('Отлично', 'Изменение пароля разрешено');
        exit($Api->response($key)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка изменения пароля', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function restorePassword() {

    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, "");

    try {
        global $DB;
        $IP = $request->getIp();
        $Api->createSessionKey($IP);

        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $check_sms_key = $Session->get($Api->getKey(), 'restore_password_change_time');
        $time = time();
        if($check_sms_key + 5 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Запрос на изменение пароля можно отправлять только раз в 5 секунд');
            exit($Api->response()->getResponse());
        }
        $Session->set($Api->getKey(), 'restore_password_change_time', $time);

        $login = htmlspecialchars(strip_tags(trim($data->login)));
        if($login == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили номер телефона');
            exit($Api->response()->getResponse());
        }

        $card = AddZeroBeforeCardCode($data->card);
        if($card == AddZeroBeforeCardCode(0)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой номер карты');
            exit($Api->response()->getResponse());
        }

        $key = htmlspecialchars(strip_tags(trim($data->key)));
        if(strlen(strval($key)) != 32){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код должен состоять из 32 цифр');
            exit($Api->response()->getResponse());
        }

        $password = $data->password;
        $password_check = preg_match('~^[A-Za-z0-9!@#$%^&*()_]{6,32}$~i', $password);
        if($password == '' || $password_check <= 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Невозможно установить такой пароль, минимум 6 символов');
            exit($Api->response()->getResponse());
        }

        $confirm_password = $data->confirm_password;
        if($confirm_password != $password){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Пароли не совпадают');
            exit($Api->response()->getResponse());
        }

        $sql = "SELECT a.ID, a.NAME, a.LAST_NAME, a.EMAIL, a.LOGIN, a.ACTIVE, a.PASSWORD, a.CONFIRM_CODE, uts.UF_CARD_CODE FROM b_user a LEFT JOIN b_uts_user uts ON uts.VALUE_ID = a.ID WHERE LOGIN=:login and uts.UF_CARD_CODE = :card and (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='')  ";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("login", $login, PDO::PARAM_STR);
        $stmt->bindParam("card", $card, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);

        if(!$userData){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь не найден');
            exit($Api->response()->getResponse());
        }

        $Api->createRestoreKey($IP, $login, $card);

        $check_sms_key = $Session->get($Api->getKey(), 'restore_start_date');
        if($check_sms_key + 900 < $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Время востановления истекло, повторите операцию сначала');
            exit($Api->response()->getResponse());
        }

        $key_store = $Session->get($Api->getKey(), 'restore_change_code');

        if($key_store != $key){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код не вереный');
            exit($Api->response()->getResponse());
        }

        $Session->delete($Api->getKey(), 'restore_change_code');
        $Session->delete($Api->getKey(), 'restore_start_date');

        $sql = "UPDATE b_user SET PASSWORD = :password WHERE ID = :userID";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("userID", $userData->ID, PDO::PARAM_STR);

        $salt = randString(8);
        $password = $salt.md5($salt.$password);

        $stmt->bindParam("password", $password, PDO::PARAM_STR);
        $stmt->execute();

        $Api->addEvents('Отлично', 'Пароль успешно изменен');
        exit($Api->response($key)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка изменения пароля', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}

function signup(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, "");

    try {
        global $DB;
        $IP = $request->getIp();
        $Api->createSessionKey($IP);

        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $check_sms_key = $Session->get($Api->getKey(), 'signup_card_check_time');
        $time = time();
        if($check_sms_key + 5 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Запрос проверку карты можно отправлять только раз в 5 секунд');
            exit($Api->response()->getResponse());
        }
        $Session->set($Api->getKey(), 'signup_card_check_time', $time);

        $card = AddZeroBeforeCardCode($data->card);
        if($card == AddZeroBeforeCardCode(0)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой номер карты');
            exit($Api->response()->getResponse());
        }

        require_once 'lib/Cards.php';
        $Cards = new \lib\Cards();

        $card_admin = $Cards->GetCardAdmin($card);
        if(!$card_admin){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Карты не существует');
            exit($Api->response()->getResponse());
        }

        $card_user = $Cards->GetCardUser($card);
        if($card_user){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Карта уже зарегистирована');
            exit($Api->response()->getResponse());
        }

        $code = htmlspecialchars(strip_tags(trim($data->code)));
        if($code == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили проверочный код карты');
            exit($Api->response()->getResponse());
        }
        if($code != $card_admin->CODE){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Проверочный код карты не совпадает');
            exit($Api->response()->getResponse());
        }

        $login = htmlspecialchars(strip_tags(trim($data->login)));
        if($login == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили номер телефона');
            exit($Api->response()->getResponse());
        }
        if(!StripAndCheckPhone($login)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не верный формат телефона');
            exit($Api->response()->getResponse());
        }
        $email = htmlspecialchars(strip_tags(trim($data->email)));
        $email_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
        if($email == '' || $email_check <= 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили email');
            exit($Api->response()->getResponse());
        }

        $sql = "SELECT a.ID, a.NAME, a.LAST_NAME, a.EMAIL, a.LOGIN, a.ACTIVE, a.PASSWORD, a.CONFIRM_CODE, uts.UF_CARD_CODE FROM b_user a LEFT JOIN b_uts_user uts ON uts.VALUE_ID = a.ID WHERE LOGIN=:login OR EMAIL = :email";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("login", $login, PDO::PARAM_STR);
        $stmt->bindParam("email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);

        if($userData && $userData->LOGIN == $login){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь с таким телефоном уже зарегистирован');
            exit($Api->response()->getResponse());
        }
        if($userData && $userData->EMAIL == $email){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь с таким email уже зарегистирован');
            exit($Api->response()->getResponse());
        }

        $Api->createLoginEmailKey($IP, $login, $email, $card);

        $check_sms_key = $Session->get($Api->getKey(), 'signup_date');
        if($check_sms_key + 60 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы можете запросить новый код через: '.($check_sms_key + 60 - $time));
            exit($Api->response()->getResponse());
        }

        require_once 'lib/Base.php';
        $BaseClass = new \lib\Base();

        $Session->set($Api->getKey(), 'signup_date', $time);
        $key = generateKey(4);
        $Session->set($Api->getKey(), 'signup_check_key', $key);

        $text = "Код подтверждения: $key";
        $BaseClass->SendSMSformLEGEND($login, $text, $card, 0, 1);

        $Api->addEvents('Отлично', 'Код подтверждения отправлен в SMS');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка регистрации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function signupCheck(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, "");

    try {
        global $DB;
        $IP = $request->getIp();
        $Api->createSessionKey($IP);

        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $check_sms_key = $Session->get($Api->getKey(), 'signup_key_check_time');
        $time = time();
        if($check_sms_key + 5 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Запрос проверку кода можно отправлять только раз в 5 секунд');
            exit($Api->response()->getResponse());
        }
        $Session->set($Api->getKey(), 'signup_key_check_time', $time);

        $card = AddZeroBeforeCardCode($data->card);
        if($card == AddZeroBeforeCardCode(0)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой номер карты');
            exit($Api->response()->getResponse());
        }

        require_once 'lib/Cards.php';
        $Cards = new \lib\Cards();

        $card_admin = $Cards->GetCardAdmin($card);
        if(!$card_admin){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Карты не существует');
            exit($Api->response()->getResponse());
        }

        $card_user = $Cards->GetCardUser($card);
        if($card_user){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Карта уже зарегистирована');
            exit($Api->response()->getResponse());
        }

        $code = htmlspecialchars(strip_tags(trim($data->code)));
        if($code == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили проверочный код карты');
            exit($Api->response()->getResponse());
        }
        if($code != $card_admin->CODE){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Проверочный код карты не совпадает');
            exit($Api->response()->getResponse());
        }

        $login = htmlspecialchars(strip_tags(trim($data->login)));
        if($login == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили номер телефона');
            exit($Api->response()->getResponse());
        }
        if(!StripAndCheckPhone($login)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не верный формат телефона');
            exit($Api->response()->getResponse());
        }
        $email = htmlspecialchars(strip_tags(trim($data->email)));
        $email_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
        if($email == '' || $email_check <= 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили email');
            exit($Api->response()->getResponse());
        }

        $sql = "SELECT a.ID, a.NAME, a.LAST_NAME, a.EMAIL, a.LOGIN, a.ACTIVE, a.PASSWORD, a.CONFIRM_CODE, uts.UF_CARD_CODE FROM b_user a LEFT JOIN b_uts_user uts ON uts.VALUE_ID = a.ID WHERE LOGIN=:login OR EMAIL = :email";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("login", $login, PDO::PARAM_STR);
        $stmt->bindParam("email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);

        if($userData && $userData->LOGIN == $login){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь с таким телефоном уже зарегистирован');
            exit($Api->response()->getResponse());
        }
        if($userData && $userData->EMAIL == $email){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь с таким email уже зарегистирован');
            exit($Api->response()->getResponse());
        }

        $key = isset($data->key) ? (intval($data->key) < 0 ? 0 : intval($data->key)) : 0 ;
        if($key == 0 || strlen(strval($key)) != 4){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код должен состоять из 4 цифр');
            exit($Api->response()->getResponse());
        }

        $Api->createLoginEmailKey($IP, $login, $email, $card);

        $key_store = $Session->get($Api->getKey(), 'signup_check_key');

        if($key_store != $key){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код не вереный');
            exit($Api->response()->getResponse());
        }

        $Session->delete($Api->getKey(), 'signup_check_key');
        $Session->delete($Api->getKey(), 'signup_date');

        $key = randString(32);
        $Session->set($Api->getKey(), 'signup_start_date', $time);
        $Session->set($Api->getKey(), 'signup_change_code', $key);

        $Api->addEvents('Отлично', 'Регистрация разрешена');
        exit($Api->response($key)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка регистрации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function signupCreate(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, "");

    try {
        global $DB;
        $IP = $request->getIp();
        $Api->createSessionKey($IP);

        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $check_sms_key = $Session->get($Api->getKey(), 'signup_create_time');
        $time = time();
        if($check_sms_key + 5 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Запрос регистрации можно отправлять только раз в 5 секунд');
            exit($Api->response()->getResponse());
        }
        $Session->set($Api->getKey(), 'signup_create_time', $time);

        $card = AddZeroBeforeCardCode($data->card);
        if($card == AddZeroBeforeCardCode(0)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой номер карты');
            exit($Api->response()->getResponse());
        }

        require_once 'lib/Cards.php';
        $Cards = new \lib\Cards();

        $card_admin = $Cards->GetCardAdmin($card);
        if(!$card_admin){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Карты не существует');
            exit($Api->response()->getResponse());
        }

        $card_user = $Cards->GetCardUser($card);
        if($card_user){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Карта уже зарегистирована');
            exit($Api->response()->getResponse());
        }

        $code = htmlspecialchars(strip_tags(trim($data->code)));
        if($code == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили проверочный код карты');
            exit($Api->response()->getResponse());
        }
        if($code != $card_admin->CODE){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Проверочный код карты не совпадает');
            exit($Api->response()->getResponse());
        }

        $login = htmlspecialchars(strip_tags(trim($data->login)));
        if($login == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили номер телефона');
            exit($Api->response()->getResponse());
        }
        if(!StripAndCheckPhone($login)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не верный формат телефона');
            exit($Api->response()->getResponse());
        }
        $email = htmlspecialchars(strip_tags(trim($data->email)));
        $email_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
        if($email == '' || $email_check <= 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не отправили email');
            exit($Api->response()->getResponse());
        }

        $sql = "SELECT a.ID, a.NAME, a.LAST_NAME, a.EMAIL, a.LOGIN, a.ACTIVE, a.PASSWORD, a.CONFIRM_CODE, uts.UF_CARD_CODE FROM b_user a LEFT JOIN b_uts_user uts ON uts.VALUE_ID = a.ID WHERE LOGIN=:login OR EMAIL = :email";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("login", $login, PDO::PARAM_STR);
        $stmt->bindParam("email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        if($userData && $userData->LOGIN == $login){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь с таким телефоном уже зарегистирован');
            exit($Api->response()->getResponse());
        }
        if($userData && $userData->EMAIL == $email){
            $Api->setStatus('error');
            $Api->addEvents('Ошибка', 'Пользователь с таким email уже зарегистирован');
            exit($Api->response()->getResponse());
        }

        $key = htmlspecialchars(strip_tags(trim($data->key)));
        if(strlen(strval($key)) != 32){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код должен состоять из 32 цифр');
            exit($Api->response()->getResponse());
        }

        $password = isset($data->password) ? $data->password : '';
        $password_check = preg_match('~^[A-Za-z0-9!@#$%^&*()_]{6,32}$~i', $password);
        if($password == '' || $password_check <= 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Невозможно установить такой пароль');
            exit($Api->response()->getResponse());
        }

        $confirm_password = isset($data->confirm_password) ? $data->confirm_password : '';
        if($confirm_password != $password){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Пароли не совпадают');
            exit($Api->response()->getResponse());
        }
        $name = isset($data->name) ? $data->name : '';
        $name_check = preg_match('~^[A-Za-zа-яёА-ЯЁ\s\-]{3,32}$~iu', $name);
        if($name == '' || $name_check <= 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Невозможно создать пользователя с таким именем');
            exit($Api->response()->getResponse());
        }
        $surname = isset($data->surname) ? $data->surname : '';
        $surname_check = preg_match('~^[A-Za-zа-яёА-ЯЁ\s\-]{3,32}$~iu', $surname);
        if($surname == '' || $surname_check <= 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Невозможно создать пользователя с такой фамилией');
            exit($Api->response()->getResponse());
        }

        $gender = isset($data->gender) ? $data->gender : '';

        if($gender == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не выбран пол клиента');
            exit($Api->response()->getResponse());
        }
        if($gender != 'M' && $gender != 'F'){
            $gender = "";
        }

        $Api->createLoginEmailKey($IP, $login, $email, $card);

        $check_sms_key = $Session->get($Api->getKey(), 'signup_start_date');
        if($check_sms_key + 900 < $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Время регистрации истекло, повторите операцию сначала');
            exit($Api->response()->getResponse());
        }

        $key_store = $Session->get($Api->getKey(), 'signup_change_code');

        if($key_store != $key){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код не вереный');
            exit($Api->response()->getResponse());
        }

        $Session->delete($Api->getKey(), 'signup_change_code');
        $Session->delete($Api->getKey(), 'signup_start_date');


        $salt = randString(8);
        $password = $salt.md5($salt.$password);

        $salt = randString(8);
        $checkword = $salt.md5($salt.randString(32));

        $full_time = date('Y-m-d H:i:s', time());

        $user_reg_group = array(3,4,5); // Стандартные группы пользователя

        $allowed = array("LOGIN", "EMAIL", "NAME", "LAST_NAME", "PASSWORD", "CHECKWORD", "CHECKWORD_TIME", "DATE_REGISTER", "TIMESTAMP_X", "PERSONAL_GENDER", "PERSONAL_COUNTRY" , "LID", "ACTIVE");
        $allowed_uts = array("VALUE_ID", "UF_NEED_ADD_F", "UF_BANK_PERFECT", "UF_OWNER", "UF_CARD_CODE", "UF_REFID");
        $arFields = array(
            "LOGIN" => $login,
            "EMAIL" => strtolower($email),
            "PASSWORD" => $password,
            "CHECKWORD" => $checkword,
            "CHECKWORD_TIME" => $full_time,
            "DATE_REGISTER" => $full_time,
            "TIMESTAMP_X" => $full_time,
            "NAME" =>  $name,
            "LAST_NAME" => $surname,
            "PERSONAL_GENDER" =>  $gender,
            "PERSONAL_COUNTRY" =>  '1',
            "UF_NEED_ADD_F" =>  '2',
            "UF_BANK_PERFECT"   => ufBankPerfect(),
            "LID" => 's1',
            "ACTIVE" => "Y",
            "UF_OWNER" => $card_admin->USER_ID,
            "UF_CARD_CODE" => $card,
            "UF_REFID" => hash("crc32b", $login . time())
        );
        $values = array();
        $sql = "INSERT INTO b_user SET ".pdoSet($allowed,$values,$arFields);
        $stmt = $DB->prepare($sql);
        $stmt->execute($values);
        $userID = $DB->lastInsertId();
        $arFields['VALUE_ID'] = $userID;
        $values = array();
        $sql = "INSERT INTO b_uts_user SET ".pdoSet($allowed_uts,$values,$arFields);
        $stmt = $DB->prepare($sql);
        $stmt->execute($values);
        foreach ($user_reg_group as $item){
            $sql = "INSERT INTO b_user_group SET USER_ID = :userID, GROUP_ID = :group";
            $stmt = $DB->prepare($sql);
            $stmt->bindParam("userID", $userID, PDO::PARAM_STR);
            $stmt->bindParam("group", $item, PDO::PARAM_STR);
            $stmt->execute();
        }

        $Cards->UpdateCardState($card, $userID);

        $Api->createKey($userID, $login);
        $data = array(
            "id" => $userID,
            "name" => $name,
            "surname" => $surname,
            "card" => $card,
            "cardFormat" => FormatCreditCard($card),
        );
        $Api->addEvents('Отлично', 'Регистрация завершена');
        exit($Api->response($data)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка регистрации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}

function login() {

    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, "");

    try {
        global $DB;
        $userData ='';
        $sql = "SELECT a.ID, a.NAME, a.LAST_NAME, a.EMAIL, a.LOGIN, a.ACTIVE, a.PASSWORD, a.CONFIRM_CODE, uts.UF_CARD_CODE FROM b_user a LEFT JOIN b_uts_user uts ON uts.VALUE_ID = a.ID WHERE LOGIN=:login and (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='')  ";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("login", $data->login, PDO::PARAM_STR);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);

        if(!empty($userData)){
            if(strlen($userData->PASSWORD) > 32) {
                $salt = substr($userData->PASSWORD, 0, strlen($userData->PASSWORD) - 32);
                $db_password = substr($userData->PASSWORD, -32);
            } else {
                $salt = "";
                $db_password = $userData->PASSWORD;
            }
            $user_password =  md5($salt.$data->password);
            if($db_password === $user_password){
                if($userData->ACTIVE == "Y"){
                    $user_id=$userData->ID;
                    $card = AddZeroBeforeCardCode($userData->UF_CARD_CODE);
                    $Api->createKey($user_id, $userData->LOGIN);
                    $Api->addEvents('Отлично', 'Успешная авторизация');
                    $data = array(
                        "id" => $userData->ID,
                        "name" => $userData->NAME,
                        "surname" => $userData->LAST_NAME,
                        "card" => $card,
                        "cardFormat" => FormatCreditCard($card),
                    );
                    exit($Api->response($data)->getResponse());
                }elseif($userData->CONFIRM_CODE <> ""){
                    $Api->setStatus('error');
                    $Api->addEvents('Ошибка авторизации', 'Вы еще не подтвердили регистрацию по Email');
                    exit($Api->response()->getResponse());
                }else{
                    $Api->setStatus('error');
                    $Api->addEvents('Ошибка авторизации', 'Ваш логин заблокирован');
                    exit($Api->response()->getResponse());
                }
            }else{
                $Api->setStatus('error');
                $Api->addEvents('Ошибка авторизации', 'Неверный логин или пароль');
                exit($Api->response()->getResponse());
            }
        }else{
            $Api->setStatus('error');
            $Api->addEvents('Ошибка авторизации', 'Неверный логин или пароль');
            exit($Api->response()->getResponse());
        }
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка авторизации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}


function companyItemInfo(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $companyID = isset($data->id) ? intval($data->id) : 0;
        $userID = false;
        if($data->auth) {
            $Api->checkUserAuth();
            $userID = $Api->getUserID();
        }
        $companyInfo = $Companies->GetCompanyInfo($companyID, $userID);
        if( isset($companyInfo->phone)){
            $companyInfo->phone =preg_replace('~[^0-9]+~', '', $companyInfo->phone);
        }
        if(!$companyInfo){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не удалось получить информацию о компании');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Информация о компании получена');
        exit($Api->response($companyInfo)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации о компании', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companyItemBranchList(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, '');

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $companyID = intval($data->id);
        $limit = isset($data->limit) ? intval($data->limit) : 5;
        $companyInfo = $Companies->GetCompanyBranchList($companyID, $limit);
        $companyInfo->phone = preg_replace('~[^0-9]+~', '', $companyInfo->phone);
        if(!$companyInfo){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не удалось получить информацию о компании');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Информация о компании получена');
        exit($Api->response($companyInfo)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации о компании', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companyItemGallery(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, '');

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $companyID = intval($data->id);
        $companyInfo = $Companies->GetCompanyGallery($companyID);
        $companyInfo->phone = preg_replace('~[^0-9]+~', '', $companyInfo->phone);
        if(!$companyInfo){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не удалось получить информацию о компании');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Информация о компании получена');
        exit($Api->response($companyInfo)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации о компании', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companyItemReviews(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, '');

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $companyID = intval($data->id);
        $companyInfo = $Companies->GetCompanyReviews($companyID);
        if(!$companyInfo){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Список отзывов пуст');
            exit($Api->response()->getResponse());
        }
        foreach ($companyInfo as &$item){
            $item->date = date("d", $item->date) . ' ' . getMonthShort(date("m", $item->date)) . ' ' . date("Y", $item->date);
        }
        $Api->addEvents('Отлично', 'Информация о компании получена');
        exit($Api->response($companyInfo)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации о компании', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companyItemShopCatList(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, '');

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $companyID = intval($data->id);
        $categoriesList = $Companies->GetCompanyShopCategoriesList($companyID);
        if(!$categoriesList){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Список категорий интернет магазина пуст');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Список категорий интернет магазина получен');
        exit($Api->response($categoriesList)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка категорий интернет магазина', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companyItemShopCatItems(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, '');

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $categoryID = intval($data->id);
        $companyID = intval($data->companyID);
        $shopItems = $Companies->GetCompanyShopCategoriesItems($companyID, $categoryID);
        if(!$shopItems){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Список товаров интернет магазина пуст');
            exit($Api->response()->getResponse());
        }
        foreach ($shopItems as &$item) {
            $item->price = floatval($item->price);
        }
        $Api->addEvents('Отлично', 'Список товаров интернет магазина получен');
        exit($Api->response($shopItems)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка товаров интернет магазина', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companyItemShopItems(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, '');

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $itemIDs = $data->ids;
        if(!count($itemIDs)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Список товаров интернет магазина пуст');
            exit($Api->response()->getResponse());
        }
        $companyID = intval($data->companyID);
        $company = $Companies->GetCompanyByID($companyID);
        $shopItems = $Companies->GetCompanyShopItems($companyID, $itemIDs);
        if(!$shopItems){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Список товаров интернет магазина пуст');
            exit($Api->response()->getResponse());
        }
        foreach ($shopItems as &$item) {
            $item->price = floatval($item->price);
        }
        $data = array(
            "company" => $company,
            "items" => $shopItems,
        );
        $Api->addEvents('Отлично', 'Список товаров интернет магазина получен');
        exit($Api->response($data)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка товаров интернет магазина', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companyItemShopOrder(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        $user = $Api->checkUserAuth();
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $address = $data->address;
        $room = $data->room;
        $level = $data->level;
        $itemIDs = $data->ids;
        if(!count($itemIDs)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Список товаров интернет магазина пуст');
            exit($Api->response()->getResponse());
        }
        $companyID = intval($data->companyID);
        $itemIDsList = array();
        $itemIDsNew = array();
        $itemKeysIDsCount = array();
        foreach ($itemIDs as $item) {
            $itemIDsList[] = $item->id;
            $itemKeysIDsCount[$item->id] = $item->count;
            $itemIDsNew[] = array(
                "id" => $item->id,
                "count" => $item->count,
            );
        }
        $shopItems = $Companies->GetCompanyShopItems($companyID, $itemIDsList);
        if(!$shopItems){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Список товаров интернет магазина пуст');
            exit($Api->response()->getResponse());
        }
        $price = 0;
        foreach ($shopItems as $shopItem) {
            $price+= floatval($itemKeysIDsCount[$shopItem->id] * $shopItem->price);
        }
        $item_list = serialize($itemIDsNew);
        $status_order = '0';

        $Companies->SaveOrderCompany($companyID, $item_list, $address, $room, $level, $user->LOGIN, $price, $status_order, AddZeroBeforeCardCode($user->UF_CARD_CODE));

        $Api->addEvents('Отлично', 'Заказ успешно отправлен в магазин, ожидайте звонка оператора');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка товаров интернет магазина', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companySubscribe(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        $Api->checkUserAuth();
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $companyID = $data->id;
        $userID = $Api->getUserID();
        if($Companies->CompanyCheckSubscribe($companyID, $userID)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы уже подписаны на эту компанию');
            exit($Api->response()->getResponse());
        }
        $Companies->CompanySubscribe($companyID, $userID);

        $Api->addEvents('Отлично', 'Вы успешно подписались');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации о подписке', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function companyUnSubscribe(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        $Api->checkUserAuth();
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $companyID = $data->id;
        $userID = $Api->getUserID();

        if(!$Companies->CompanyCheckSubscribe($companyID, $userID)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы не подписаны на эту компанию');
            exit($Api->response()->getResponse());
        }

        $Companies->CompanyUnSubscribe($companyID, $userID);

        $Api->addEvents('Отлично', 'Вы успешно отписались');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации о подписке', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function categoryItems(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $categoryID = $data->id;
        $userID = false;
        if($data->auth) {
            $Api->checkUserAuth();
            $userID = $Api->getUserID();
        }
        $companyList = $Companies->GetCategoryItemsList($categoryID, $userID);
        if(!$companyList){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не удалось получить список компаний');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Список компаний получен');
        exit($Api->response($companyList)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка компаний', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function listCategories(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, '');

    try {
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();
        $categories = $Companies->GetList();
        if(!$categories){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не удалось получить список категорий');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Список категорий получен');
        exit($Api->response($categories)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка категорий', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}

function userBonus(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        $user = $Api->checkUserAuth();
        require 'lib/Bonus.php';
        $Bonus = new \lib\Bonus();
        $userBonus = $Bonus->GetByCard(AddZeroBeforeCardCode($user->UF_CARD_CODE));
        if(!$userBonus){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'У вас еще нет бонусов');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Список бонусов получен');
        exit($Api->response($userBonus)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка бонусов', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBusinessPaymentsTypesCheck(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();

        $paymentID = isset($data->payment) ? (intval($data->payment) < 0 ? 0 : intval($data->payment)) : 0 ;
        if($paymentID == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Ошибка отправки параметров');
            exit($Api->response()->getResponse());
        }
        $key = isset($data->key) ? (intval($data->key) < 0 ? 0 : intval($data->key)) : 0 ;
        if($key == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код должен состоять из цифр');
            exit($Api->response()->getResponse());
        }
        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $check_sms_key = $Session->get($Api->getKey(), 'check_sms_key');
        $time = time();
        if($check_sms_key + 10 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Проверить ключ из SMS можно раз в 10 секунд');
            exit($Api->response()->getResponse());
        }
        $Session->set($Api->getKey(), 'check_sms_key', $time);

        $payment = $User->GetUserPaymentsTypeByID($paymentID);
        if(!$payment){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Счета не найден');
            exit($Api->response()->getResponse());
        }
        if($payment->user_id != $Api->getUserID()){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Счет вам не принадлежит');
            exit($Api->response()->getResponse());
        }
        if($payment->active == 1){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Счета уже подтвержден');
            exit($Api->response()->getResponse());
        }
        if($payment->key_pay != $key){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код не вереный, счет НЕ активирован');
            exit($Api->response()->getResponse());
        }

        $User->updateUserPaymentsTypeToActive($paymentID);

        $Api->addEvents('Отлично', 'Счет активирован!');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка добавления счета', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBusinessPaymentsCashOutCheck(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();

        $key = isset($data->key) ? (intval($data->key) < 0 ? 0 : intval($data->key)) : 0 ;
        if($key == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код должен состоять из цифр');
            exit($Api->response()->getResponse());
        }
        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $check_sms_key = $Session->get($Api->getKey(), 'cash_out_check_sms_key');
        $time = time();
        if($check_sms_key + 10 > $time){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Проверить ключ из SMS можно раз в 10 секунд');
            exit($Api->response()->getResponse());
        }
        $Session->set($Api->getKey(), 'cash_out_check_sms_key', $time);


        $key_store = $Session->get($Api->getKey(), 'cash_out_check_key');

        if($key_store != $key){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Код не вереный, запрос на вывод не создан');
            exit($Api->response()->getResponse());
        }
        $cash_out_data = $Session->get($Api->getKey(), 'cash_out_data');
        if(!$cash_out_data){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Запрос для создания не найден');
            exit($Api->response()->getResponse());
        }
        $cash_out_data = unserialize($cash_out_data);
        $User->InsertNewCashOuth($Api->getUserID(), $cash_out_data['paymentID'], $cash_out_data['money']);
        $Session->delete($Api->getKey(), 'cash_out_data');
        $Session->delete($Api->getKey(), 'cash_out_check_key');

        require_once 'lib/Base.php';
        $BaseClass = new \lib\Base();
        $text = 'Запрос на вывод ID: '.$Api->getUserID().', '.$cash_out_data['money'].' руб';
//        $User->SetUserParam($Api->getUserID(), 'UF_MONEY_RUB', $value);
        $BaseClass->SendSMSformLEGEND('79620503960', $text, '', 0, 0);
        $BaseClass->SendSMSformLEGEND('79045820251', $text, '', 0, 0);
        $BaseClass->SendSMSformLEGEND('79136241352', $text, '', 0, 0);

        $Api->addEvents('Отлично', 'Запрос на вывод создан!');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка проверки кода', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBusinessPaymentsCashOut(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();

        $paymentID = isset($data->payment) ? (intval($data->payment) < 0 ? 0 : intval($data->payment)) : 0 ;
        if($paymentID == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не выбран тип счета');
            exit($Api->response()->getResponse());
        }
        $payment = $User->GetUserPaymentsTypeByID($paymentID);
        if(!$payment){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Тип счета не найден');
            exit($Api->response()->getResponse());
        }

        if($payment->user_id != $Api->getUserID()){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Счет вам не принадлежит');
            exit($Api->response()->getResponse());
        }
        $money = isset($data->money) ? floatval($data->money) < 0 ? 0 : floatval($data->money) : 0;
        if($money == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не верная сумма для вывода');
            exit($Api->response()->getResponse());
        }
        $payment_type = $User->GetPaymentsTypeByID($payment->payment_type_id);
        if($payment_type->start_price > $money){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Минимальная сумма для вывода: '.$payment_type->start_price.' рублей');
            exit($Api->response()->getResponse());
        }

        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $payment_last_time = $Session->get($Api->getKey(), 'cash_out_date');
        $time = time();
        if($payment_last_time + 30 > $time){
            $time_mid = $time - $payment_last_time;
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Запрашивать вывод можно только раз в 5 минут. Будет доступно через: '. $time_mid .' секунд');
            exit($Api->response()->getResponse());
        }

        require_once 'lib/Base.php';
        $BaseClass = new \lib\Base();
        $Session->set($Api->getKey(), 'cash_out_date', $time);
        $key = generateKey(4);
        $Session->set($Api->getKey(), 'cash_out_check_key', $key);
        $Session->set($Api->getKey(), 'cash_out_data', serialize(array("paymentID"=>$paymentID, "money"=>$money)));

        $text = "Код подтверждения: $key";
        $BaseClass->SendSMSformLEGEND($user->LOGIN, $text, AddZeroBeforeCardCode($user->UF_CARD_CODE), $Api->getUserID(), 1);

        $Api->addEvents('Отлично', 'Подтвердите запрос на вывод, код подтверждения отправен Вам в SMS');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка добавления счета', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBusinessPaymentsTypesCreate(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();

        $paymentID = isset($data->payment) ? (intval($data->payment) < 0 ? 0 : intval($data->payment)) : 0 ;
        if($paymentID == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не выбран тип счета');
            exit($Api->response()->getResponse());
        }
        $payment = $User->GetPaymentsTypeByID($paymentID);
        if(!$payment){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Тип счета не найден');
            exit($Api->response()->getResponse());
        }

        $number = htmlspecialchars(strip_tags(trim($data->number)));
        if($number == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой номер счета');
            exit($Api->response()->getResponse());
        }
        $name = htmlspecialchars(strip_tags(trim($data->name)));
        if($name == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустое имя получателя');
            exit($Api->response()->getResponse());
        }
        $surname = htmlspecialchars(strip_tags(trim($data->surname)));
        if($surname == ''){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустую фамилию получателя');
            exit($Api->response()->getResponse());
        }

        require_once 'lib/Session.php';
        $Session = new \lib\Session();

        $payment_last_time = $Session->get($Api->getKey(), 'last_create_payment');
        $time = time();
        if($payment_last_time + 300 > $time){
            $time_mid = $time - $payment_last_time;
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Создать счет можно только раз в 5 минут. Создать новый счет можно через: '. $time_mid .' секунд');
            exit($Api->response()->getResponse());
        }
        require_once 'lib/Base.php';
        $BaseClass = new \lib\Base();
        $Session->set($Api->getKey(), 'last_create_payment', $time);
        $key = generateKey(4);
        $payment_user_id = $User->InsertPaymentsType($Api->getUserID(), $paymentID, $number, $name, $surname, $key);
        $Session->set($Api->getKey(), 'payment_key_sms_delay', $time);
        $text = "Код подтверждения: $key";
        $BaseClass->SendSMSformLEGEND($user->LOGIN, $text, AddZeroBeforeCardCode($user->UF_CARD_CODE), $Api->getUserID(), 1);

        $data = array("id"=>$payment_user_id);

        $Api->addEvents('Отлично', 'Счет создан, код подтверждения отправен вам в SMS');
        exit($Api->response($data)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка добавления счета', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBusinessPaymentsTypesList(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();

        $userShopping = $User->GetPaymentsTypes();
        if(!$userShopping){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'У вас еще нет покупок');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Список покупок получен');
        exit($Api->response($userShopping)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка покупок', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBusinessPaymentsTypes(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();

        $userShopping = $User->GetPaymentsTypesByID($Api->getUserID());
        if(!$userShopping){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'У вас еще нет покупок');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Список покупок получен');
        exit($Api->response($userShopping)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка покупок', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBusinessPayments(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();

        $limit = isset($data->limit) ? (intval($data->limit) < 0 ? 5 : intval($data->limit)) : 5 ;
        $offset = isset($data->offset) ? (intval($data->offset) < 0 ? 0 : intval($data->offset)) : 0 ;
        $userShopping = $User->GetPaymentsByID($Api->getUserID(), $limit, $offset);
        if(!$userShopping){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'У вас еще нет покупок');
            exit($Api->response()->getResponse());
        }
        foreach ($userShopping as &$item){
            $item->money = round($item->summ - $item->summ * $item->type_commission * 0.01, 2);
            $item->date = date("d", $item->date) . ' ' . getMonthShort(date("m", $item->date)) . ' ' . date("Y", $item->date).' в '.date('H:i', $item->date);
            $item->update_date = date("d", $item->update_date) . ' ' . getMonthShort(date("m", $item->update_date)) . ' ' . date("Y", $item->update_date).' в '.date('H:i', $item->update_date);
        }
        $Api->addEvents('Отлично', 'Список покупок получен');
        exit($Api->response($userShopping)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка покупок', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBusinessMoney(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();

        $limit = isset($data->limit) ? (intval($data->limit) < 0 ? 5 : intval($data->limit)) : 5 ;
        $offset = isset($data->offset) ? (intval($data->offset) < 0 ? 0 : intval($data->offset)) : 0 ;
        $userShopping = $User->GetShoppingByID($Api->getUserID(), $limit, $offset);
        if(!$userShopping){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Изменений баланса не было');
            exit($Api->response()->getResponse());
        }
        foreach ($userShopping as &$item){
            $item->money = round($item->money, 2);
            $item->date = date("d", $item->date) . ' ' . getMonthShort(date("m", $item->date)) . ' ' . date("Y", $item->date).' в '.date('H:i', $item->date);
        }
        $Api->addEvents('Отлично', 'Список средств получен');
        exit($Api->response($userShopping)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка средств', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBuys(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require 'lib/Buys.php';
        $Buys = new \lib\Buys();

        $limit = isset($data->limit) ? (intval($data->limit) < 0 ? 5 : intval($data->limit)) : 5 ;
        $offset = isset($data->offset) ? (intval($data->offset) < 0 ? 0 : intval($data->offset)) : 0 ;
        $userBuys = $Buys->GetByCard(AddZeroBeforeCardCode($user->UF_CARD_CODE), time(), $limit, $offset);
        if(!$userBuys){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'У вас еще нет покупок');
            exit($Api->response()->getResponse());
        }
        foreach ($userBuys as &$item){
            $discount = ($item->price - $item->discount_price) / $item->price * 100;
            $item->discount = ($discount > 0 && $discount < 1) ? 1 : ($discount > 100 ? 100 : round($discount));
            $item->format_price_b = round($item->price);
            $item->format_price = round($item->discount_price);
            $item->date = date("d", $item->date) . ' ' . getMonthShort(date("m", $item->date)) . ' ' . date("Y", $item->date).' в '.date('H:i', $item->date);
        }
        $Api->addEvents('Отлично', 'Список покупок получен');
        exit($Api->response($userBuys)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка покупок', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBuyInfo(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require 'lib/Buys.php';
        $Buys = new \lib\Buys();

        $buyID = isset($data->id) ? (intval($data->id) < 0 ? 0 : intval($data->id)) : 0 ;
        $buyItem = $Buys->GetById($buyID, AddZeroBeforeCardCode($user->UF_CARD_CODE), time());
        if(!$buyItem){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Покупка не найдена');
            exit($Api->response()->getResponse());
        }
        $discount = ($buyItem->price - $buyItem->discount_price) / $buyItem->price * 100;
        $buyItem->discount = ($discount > 0 && $discount < 1) ? 1 : ($discount > 100 ? 100 : round($discount));
        $buyItem->format_price_b = round($buyItem->price);
        $buyItem->format_price = round($buyItem->discount_price);
        $buyItem->date = date("d", $buyItem->date) . ' ' . getMonthShort(date("m", $buyItem->date)) . ' ' . date("Y", $buyItem->date).' в '.date('H:i', $buyItem->date);

        $Api->addEvents('Отлично', 'Информация о покупке получена');
        exit($Api->response($buyItem)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации о покупоке', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBuyRatingSet(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require 'lib/Buys.php';
        $Buys = new \lib\Buys();

        $buyID = isset($data->id) ? (intval($data->id) < 0 ? 0 : intval($data->id)) : 0 ;
        $buyItem = $Buys->GetById($buyID, AddZeroBeforeCardCode($user->UF_CARD_CODE), time());
        if(!$buyItem){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Покупка не найдена');
            exit($Api->response()->getResponse());
        }
        if(!is_null($buyItem->rating)){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы уже оставляли отзыв об этой покупке');
            exit($Api->response()->getResponse());
        }

        $message = htmlspecialchars(strip_tags(trim($data->message)));
        $rating = intval($data->rating);
        if($rating == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой рейтинг');
            exit($Api->response()->getResponse());
        }
        $Buys->SetBuyRating($buyItem->id, $buyItem->aff_id, $buyItem->org_id, $rating, $message, time());

        require_once 'lib/User.php';
        $User = new \lib\User();
        $User->SetUserParam($user->ID, 'UF_VOLANTER_FINES', intval($user->UF_VOLANTER_FINES)+1);

        $discount = ($buyItem->price - $buyItem->discount_price) / $buyItem->price * 100;
        $buyItem->discount = ($discount > 0 && $discount < 1) ? 1 : ($discount > 100 ? 100 : round($discount));
        $buyItem->format_price_b = round($buyItem->price);
        $buyItem->format_price = round($buyItem->discount_price);
        $buyItem->date = date("d", $buyItem->date) . ' ' . getMonthShort(date("m", $buyItem->date)) . ' ' . date("Y", $buyItem->date).' в '.date('H:i', $buyItem->date);

        $Api->addEvents('Отлично', 'Отзыв сохранен');
        exit($Api->response($buyItem)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации с сервера', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBuyStaffRatingSet(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require 'lib/Buys.php';
        require 'lib/Staff.php';
        $Buys = new \lib\Buys();
        $Staff = new \lib\Staff();

        $buyID = isset($data->id) ? (intval($data->id) < 0 ? 0 : intval($data->id)) : 0 ;
        $buyItem = $Buys->GetById($buyID, AddZeroBeforeCardCode($user->UF_CARD_CODE), time());
        if(!$buyItem){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Покупка не найдена');
            exit($Api->response()->getResponse());
        }
        $userID = isset($data->userID) ? (intval($data->userID) < 0 ? 0 : intval($data->userID)) : 0 ;
        if($userID == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Не был передан ID пользователя');
            exit($Api->response()->getResponse());
        }

        $ratingList = $Staff->GetById($buyID, $userID);
        if($ratingList){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы уже оценили этого человека');
            exit($Api->response()->getResponse());
        }
        $message = htmlspecialchars(strip_tags(trim($data->message)));
        $rating = intval($data->rating);
        if($rating == 0){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Вы отправили пустой рейтинг');
            exit($Api->response()->getResponse());
        }

        $Staff->SetStaffRating($buyItem->id, $userID, $rating, $message, time());

        $Api->addEvents('Отлично', 'Отзыв сохранен');
        exit($Api->response($buyItem)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации с сервера', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userBuyStaffList(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require 'lib/Buys.php';
        require 'lib/Staff.php';
        $Buys = new \lib\Buys();
        $Staff = new \lib\Staff();

        $buyID = isset($data->id) ? (intval($data->id) < 0 ? 0 : intval($data->id)) : 0 ;
        $buyItem = $Buys->GetById($buyID, AddZeroBeforeCardCode($user->UF_CARD_CODE), time());
        if(!$buyItem){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Покупка не найдена');
            exit($Api->response()->getResponse());
        }
        $staffList = $Staff->GetListByBranch($buyID, $buyItem->org_id, $buyItem->aff_id);
        if(!$staffList){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Персонал не найден');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Список персонала получен');
        exit($Api->response($staffList)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка персонала', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userInfo(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {

        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();
        $userInfo = $User->GetUserInfo($Api->getUserID());
        if(!$userInfo){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Информация не получена');
            exit($Api->response()->getResponse());
        }
        $userInfo->money = round($userInfo->money, 2);
        $userInfo->parent = intval($userInfo->parent);
        $userInfo->business_type = intval($userInfo->business_type);
        switch ($userInfo->business_type){
            case '1':
                $userInfo->business_name = 'Изумруд';
                $userInfo->business_img = 'https://legendcity.ru/assets/business/img/izumrud.png';
                break;
            case '2':
                $userInfo->business_name = 'Сапфир';
                $userInfo->business_img = 'https://legendcity.ru/assets/business/img/sapfir.png';
                break;
            case '3':
                $userInfo->business_name = 'Рубин';
                $userInfo->business_img = 'https://legendcity.ru/assets/business/img/rubin.png';
                break;
            case '4':
                $userInfo->business_name = 'Бриллиант';
                $userInfo->business_img = 'https://legendcity.ru/assets/business/img/brilliant.png';
                break;
            default:
                $userInfo->business_name = '';
                $userInfo->business_img = '';
                break;
        }
        $Api->addEvents('Отлично', 'Информация не получена');
        exit($Api->response($userInfo)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userNotification(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {

        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();
        $limit = isset($data->limit) ? (intval($data->limit) < 0 ? 10 : intval($data->limit)) : 10 ;
        $offset = isset($data->offset) ? (intval($data->offset) < 0 ? 0 : intval($data->offset)) : 0 ;
        $messages = $User->GetUserNotification(AddZeroBeforeCardCode($user->UF_CARD_CODE), $limit, $offset);
        if(!$messages){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Список опвещений пуст');
            exit($Api->response()->getResponse());
        }
        foreach ($messages as &$item) {
            $item->date = date("d", $item->date) . ' ' . getMonthShort(date("m", $item->date)) . ' ' . date("Y", $item->date).' в '.date('H:i', $item->date);
        }
        
        $Api->addEvents('Отлично', 'Информация получена');
        exit($Api->response($messages)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userSetDeviceToken(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {

        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();
        $googleKey = isset($data->key) ? htmlspecialchars(strip_tags(trim($data->key))) : 0;

        if(!$googleKey){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Google ключь не отправлен');
            exit($Api->response()->getResponse());
        }
        $User->InsertGoogleKey($Api->getUserID(), $googleKey);

        $Api->addEvents('Отлично', 'Google ключь сохранен');
        exit($Api->response()->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function getGifts($card_to, $card_from, $id_code, $type){
    require_once 'lib/Keys.php';
    $Keys = new \lib\Keys();
    $key = $Keys->GetCompanyKeyById($id_code);
    if ($key) {
        if (intval($key->type) == 0 || intval($key->type) == 2 || intval($key->type) == 3 || intval($key->type) == 4) {
            $back = 0;
        } else {
            $back = 1;
        }
        require_once 'lib/Bonus.php';
        require_once 'lib/Companies.php';
        require_once 'lib/Base.php';
        require_once 'lib/Certificate.php';
        $Bonus = new \lib\Bonus();
        $Companies = new \lib\Companies();
        $BaseClass = new \lib\Base();
        $Certificate = new \lib\Certificate();


        //начисления активировашему промокод
        if (intval($key->type) == 0 || intval($key->type) == 1 || intval($key->type) == 3) {
            $bonus_user = $Bonus->GetByCardInOrg($card_to, $key->id_org);
            $bonus_user += intval($key->count_give_bonus_to);
            $Bonus->UpdateUserBonus($card_to, $bonus_user, $key->id_org);
            $Bonus->AddHistoryPromoUp($key->id_org, $card_to, $key->count_give_bonus_to, $id_code);
            $company = $Companies->GetCompanyByID($key->id_org);
            $text = 'По промокоду начислено ' . intval($key->count_give_bonus_to) . ' бонусов в компании ' . $company->NAME . '. Общий баланс: ' . $bonus_user;
            $text_sms = 'Вам начислено ' . intval($key->count_give_bonus_to) . ' бонусов в компании ' . $company->NAME;
            $BaseClass->InsertNewMessages($key->id_org, $card_to, 'Legendcity', $text, $text_sms);
        } else {
            $from_take = $Certificate->GetTakeBuyId($key->certificat_id);
            $Certificate->UpdateTakeCountSend($key->certificat_id, intval($from_take->promo_sell) + 1);
            $Certificate->AddSend($from_take->sertificat_id, $card_to, $key->certificat_id);
            $this_cert = $Certificate->GetCertificateByID($from_take->sertificat_id);
            $Certificate->UpdateCountTakePromo($from_take->sertificat_id, intval($this_cert->promo_sell) + 1);
            $company = $Companies->GetCompanyByID($this_cert->org_id);
            $text = 'По промокоду выдан сертификат "' . $this_cert->name . '" в компании ' . $company->NAME;
            $text_sms = 'Вам выдан сертификат в компании ' . $company->NAME;
            $BaseClass->InsertNewMessages($key->id_org, $card_to, 'Legendcity', $text, $text_sms);
        }

        //начисления владельцу промокода
        if ($type != 'org' && intval($key['type']) == 1) {
            $bonus_user_from = $Bonus->GetByCardInOrg($card_from, $key->id_org);
            $bonus_user_from += intval($key->count_give_bonus_from);
            $Bonus->UpdateUserBonus($card_from, $bonus_user_from, $key->id_org);
            $Bonus->AddHistoryPromoUp($key->id_org, $card_from, $key->count_give_bonus_from, $id_code);
            $company = $Companies->GetCompanyByID($key->id_org);
            $text = 'Ваш промокод активирован. Вы получили ' . intval($key->count_give_bonus_from) . ' бонусов в компании ' . $company->NAME;
            $text_sms = 'Вам начислено ' . intval($key->count_give_bonus_from) . ' бонусов в компании ' .$company->NAME;
            $BaseClass->InsertNewMessages($key->id_org, $card_from, 'Legendcity', $text, $text_sms);
        } elseif ($type != 'org' && intval($key['type']) == 5) {
            $from_take = $Certificate->GetTakeBuyId($key->certificat_id);
            $Certificate->UpdateTakeCountSend($key->certificat_id, intval($from_take->promo_sell) + 1);
            $Certificate->AddSend($from_take->sertificat_id, $card_from, $key->certificat_id);
            $this_cert = $Certificate->GetCertificateByID($from_take->sertificat_id);
            $Certificate->UpdateCountTakePromo($from_take->sertificat_id, intval($this_cert->promo_sell) + 1);
            $company = $Companies->GetCompanyByID($this_cert->org_id);
            $text = 'Ваш промокод активирован. Вам выдан сертификат "' . $this_cert->name . '" в компании ' . $company->NAME;
            $text_sms = 'Вам выдан сертификат в компании ' . $company->NAME;
            $BaseClass->InsertNewMessages($key->id_org, $card_from, 'Legendcity', $text, $text_sms);
        }
        $messages = array(
            'type' => 'ok',
            'back' => $back,
            'title' => 'Отлично',
            'text' => 'Промокод активирован.'
        );
    } else {
        $messages = array(
            'type' => 'error',
            'title' => 'Извините',
            'text' => 'Промокод не действителен'
        );
    }
    return $messages;
}

function userPromocodesSend(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();

        require_once 'lib/User.php';
        $User = new \lib\User();
        $key = htmlspecialchars(strip_tags(trim($data->key)));
        $userActivePromocodes = $User->GetUserActivePromocodes($key, AddZeroBeforeCardCode($user->UF_CARD_CODE));
        if($userActivePromocodes){
            if (intval($userActivePromocodes->active) == 0) {
                // Выдать бонусы за ключ и поменять active на 1
                $result = getGifts(AddZeroBeforeCardCode($user->UF_CARD_CODE), AddZeroBeforeCardCode($userActivePromocodes->card_from), $userActivePromocodes->key_id, 'user');
                $Api->addEvents($result['title'],$result['text']);
                if ($result['type'] != 'error') {
                    $Api->setStatus('ok');
                    $User->UpdateUserKeysActive($userActivePromocodes->id, $result['back']);
                    exit($Api->response(true)->getResponse());
                }else{
                    $Api->setStatus('error');
                    exit($Api->response()->getResponse());
                }
            } else {
                $Api->setStatus('error');
                $Api->addEvents('Предупреждение', 'Промокод уже активирован');
                exit($Api->response()->getResponse());
            }
        }else{
            require_once 'lib/Keys.php';
            $Keys = new \lib\Keys();
            $keys_new = $Keys->UserKeyGet($key);
            if ($keys_new) {
                if ($keys_new->card == AddZeroBeforeCardCode($user->UF_CARD_CODE)) {
                    $Api->setStatus('error');
                    $Api->addEvents('Ошибка', 'Вы не можете активировать свой промокод');
                    exit($Api->response()->getResponse());
                } else {
                    $u_have_key = $Keys->GetUserKeyByKeyId($keys_new->key_id, AddZeroBeforeCardCode($user->UF_CARD_CODE));
                    if ($u_have_key) {
                        $Api->setStatus('error');
                        $Api->addEvents('Ошибка', 'У вас уже есть промокод в этой компании');
                        exit($Api->response()->getResponse());
                    }else{
                        // выдать бонусы за ключ и добавить в таблицу b_user_key_activate
                        $result = getGifts(AddZeroBeforeCardCode($user->UF_CARD_CODE), $keys_new['card'], $keys_new['key_id'], 'user');
                        $Api->addEvents($result['title'],$result['text']);
                        if ($result['type'] != 'error') {
                            $Api->setStatus('ok');
                            $Keys->InsertUserKeysActive($key, AddZeroBeforeCardCode($user->UF_CARD_CODE), $keys_new->id, 1, $keys_new->card, $result['back'], $keys_new->key_id);
                            exit($Api->response(true)->getResponse());
                        }else{
                            $Api->setStatus('error');
                            exit($Api->response()->getResponse());
                        }
                    }
                }
            } else {
                $keys_new = $Keys->OrgUserKeyGet($key);
                if ($keys_new) {
                    if ($keys_new->max_send >= $keys_new->send) {
                        $Api->setStatus('error');
                        $Api->addEvents('Ошибка', 'Промокоды закончились');
                        exit($Api->response()->getResponse());
                    } elseif ($keys_new->date_end = !0 && $keys_new->date_end < time()) {
                        $Api->setStatus('error');
                        $Api->addEvents('Ошибка', 'Время действия промокода истекло');
                        exit($Api->response()->getResponse());
                    } else {
                        // выдать бонусы за ключ и добавить в таблицу b_user_key_activate
                        $result = getGifts(AddZeroBeforeCardCode($user->UF_CARD_CODE), '', $keys_new['id'], 'org');

                        $Api->addEvents($result['title'],$result['text']);
                        if ($result['type'] != 'error') {
                            $Api->setStatus('ok');
                            $Keys->InsertUserKeysActive($key, AddZeroBeforeCardCode($user->UF_CARD_CODE), $keys_new->id, 1, '', 1, $keys_new->key_id);
                            exit($Api->response(true)->getResponse());
                        }else{
                            $Api->setStatus('error');
                            exit($Api->response()->getResponse());
                        }
                    }
                } else {
                    $Api->setStatus('error');
                    $Api->addEvents('Ошибка', 'Промокод не действителен');
                    exit($Api->response()->getResponse());
                }
            }
        }
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userPromocodes(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {

        global $DB;
        $user = $Api->checkUserAuth();

        require_once 'lib/User.php';
        $User = new \lib\User();

        $userPromocodes = $User->GetUserPromocodes(AddZeroBeforeCardCode($user->UF_CARD_CODE));
        if(!$userPromocodes){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Информация не получена');
            exit($Api->response()->getResponse());
        }
        foreach ($userPromocodes as &$item){
            $item->back_money_lvl = ($item->back_money_lvl === 0) ? false : unserialize($item->back_money_lvl);
        }
        $Api->addEvents('Отлично', 'Информация не получена');
        exit($Api->response($userPromocodes)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userCertificates(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {

        global $DB;
        $user = $Api->checkUserAuth();
        require_once 'lib/User.php';
        $User = new \lib\User();
        $userCertificates = $User->GetUserCertificates(AddZeroBeforeCardCode($user->UF_CARD_CODE), time());
        foreach ($userCertificates as &$item){
            $item->date = date("d", $item->date) . ' ' . getMonthShort(date("m", $item->date)) . ' ' . date("Y", $item->date);
            $item->date_end = date("d", $item->date_end) . ' ' . getMonthShort(date("m", $item->date_end)) . ' ' . date("Y", $item->date_end);
        }
        if(!$userCertificates){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Информация не получена');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Информация получена');
        exit($Api->response($userCertificates)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userCertificatesItem(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        $user = $Api->checkUserAuth();

        global $DB;
        $certID = isset($data->id) ? intval($data->id) : 0;
        require_once 'lib/User.php';
        $User = new \lib\User();
        $userCertificateItem = $User->GetUserCertificatesItem(AddZeroBeforeCardCode($user->UF_CARD_CODE), $certID, time());
        if(!$userCertificateItem){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'Информация не получена');
            exit($Api->response()->getResponse());
        }
        $userCertificateItem->date = date("d", $userCertificateItem->date) . ' ' . getMonthShort(date("m", $userCertificateItem->date)) . ' ' . date("Y", $userCertificateItem->date);
        $userCertificateItem->date_end = date("d", $userCertificateItem->date_end) . ' ' . getMonthShort(date("m", $userCertificateItem->date_end)) . ' ' . date("Y", $userCertificateItem->date_end);
        $Api->addEvents('Отлично', 'Информация не получена');
        exit($Api->response($userCertificateItem)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения информации', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}
function userSubscriptions(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $Api = new \lib\Api(SITE_KEY, $data->token);

    try {
        global $DB;
        $user = $Api->checkUserAuth();
        require 'lib/Companies.php';
        $Companies = new \lib\Companies();

        $userSubscriptions = $Companies->GetUserSubscriptions($Api->getUserID());

        if(!$userSubscriptions){
            $Api->setStatus('error');
            $Api->addEvents('Предупреждение', 'У вас еще нет подписок');
            exit($Api->response()->getResponse());
        }
        $Api->addEvents('Отлично', 'Список подписок получен');
        exit($Api->response($userSubscriptions)->getResponse());
    }
    catch(PDOException $e) {
        $Api->setStatus('error');
        $Api->addEvents('Ошибка получения списка подписок', $e->getMessage());
        exit($Api->response()->getResponse());
    }
}

function email() {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $email=$data->email;

    try {
       
        $email_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
       
        if (strlen(trim($email))>0 && $email_check>0)
        {
            $db = getDB();
            $userData = '';
            $sql = "SELECT user_id FROM emailUsers WHERE email=:email";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $email,PDO::PARAM_STR);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $created=time();
            if($mainCount==0)
            {
                
                /*Inserting user values*/
                $sql1="INSERT INTO emailUsers(email)VALUES(:email)";
                $stmt1 = $db->prepare($sql1);
                $stmt1->bindParam("email", $email,PDO::PARAM_STR);
                $stmt1->execute();
                
                
            }
            $userData=internalEmailDetails($email);
            $db = null;
            if($userData){
               $userData = json_encode($userData);
                echo '{"userData": ' .$userData . '}';
            } else {
               echo '{"error":{"text":"Enter valid dataaaa"}}';
            }
        }
        else{
            echo '{"error":{"text":"Enter valid data"}}';
        }
    }
    
    catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}








?>
