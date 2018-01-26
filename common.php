<?php
/**
 * Created by PhpStorm.
 * User: xkelx
 * Date: 10.11.2017
 * Time: 0:39
 */
function getMonthShort($id)
{
    $month = array(
        '01' => 'Янв',
        '02' => 'Февр',
        '03' => 'Март',
        '04' => 'Фпр',
        '05' => 'Май',
        '06' => 'Июнь',
        '07' => 'Июль',
        '08' => 'Авг',
        '09' => 'Сент',
        '10' => 'Окт',
        '11' => 'Нояб',
        '12' => 'Дек',
    );
    return $month[$id];
}
function pdoSet($allowed, &$values, $source= array()) {
    $set = '';
    $values = array();
    foreach ($allowed as $field) {
        if (isset($source[$field])) {
            $set.="`".str_replace("`","``",$field)."`". "=:$field, ";
            $values[$field] = $source[$field];
        }
    }
    return substr($set, 0, -2);
}

function getPhrase( $number, $titles ) {
    $cases = array( 2, 0, 1, 1, 1, 2 );

    return $titles[ ( $number % 100 > 4 && $number % 100 < 20 ) ? 2 : $cases[ min( $number % 10, 5 ) ] ];
}
function generateKey($length = 4)
{
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= rand(1, 9);
    }
    return $key;
}

function AddZeroBeforeCardCode( $card_code ) {
    $card_code = intval( trim( $card_code ) );
    $len       = strlen( $card_code );
    $zero      = '';
    for ( $i = 0; $i < 13 - $len; $i ++ ) {
        $zero = $zero . '0';
    }

    return $zero . $card_code;
}
function StripAndCheckPhone($phone){
    $phone = preg_replace("/[^0-9]/", '', $phone);;
    if (strlen($phone) == 10 && $phone[0] == '9') {
        $phone = '7' . $phone;
    }
    if (strlen($phone) == 11 && $phone[0] == '8') {
        $phone[0] = '7';
    }
    if (strlen($phone) == 12 && $phone[0] == '+') {
        $phone = substr($phone, 1);
    }
    if (strlen($phone) != 11) {
        return false;
    }
    return $phone;
}
function ufBankPerfect(){
    $UF_BANK_PERFECT = 'U';
    for($i = 0; $i < 7; $i++){
        $UF_BANK_PERFECT = $UF_BANK_PERFECT . rand(0, 9);
    }
    return $UF_BANK_PERFECT;
}
function FormatCreditCard($cc) {
    $segments = array(
        substr($cc, 0, 3),
        substr($cc, 3, 3),
        substr($cc, 6, 3),
        substr($cc, 9, 4),
    );
    $newCreditCard = implode(' ', $segments);
    return $newCreditCard;
}

function randString($pass_len=10, $pass_chars=false)
{
    static $allchars = "abcdefghijklnmopqrstuvwxyzABCDEFGHIJKLNMOPQRSTUVWXYZ0123456789";
    $string = "";
    if(is_array($pass_chars))
    {
        while(strlen($string) < $pass_len)
        {
            if(function_exists('shuffle'))
                shuffle($pass_chars);
            foreach($pass_chars as $chars)
            {
                $n = strlen($chars) - 1;
                $string .= $chars[mt_rand(0, $n)];
            }
        }
        if(strlen($string) > count($pass_chars))
            $string = substr($string, 0, $pass_len);
    }
    else
    {
        if($pass_chars !== false)
        {
            $chars = $pass_chars;
            $n = strlen($pass_chars) - 1;
        }
        else
        {
            $chars = $allchars;
            $n = 61; //strlen($allchars)-1;
        }
        for ($i = 0; $i < $pass_len; $i++)
            $string .= $chars[mt_rand(0, $n)];
    }
    return $string;
}