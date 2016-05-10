<?php

/* Set details here */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$apiUser = $_SESSION['login'];
$apiKey = $_SESSION['password'];
$siteUrl = $_SESSION['siteURL'];


/* Connect to the Magento Site */
$client  = new SoapClient($siteUrl.'/api/v2_soap/?wsdl', array('trace' =>true,
    'connection_timeout' => 500000,
    'cache_wsdl' => WSDL_CACHE_BOTH,
    'keep_alive' => false));
try {
    $session = $client->login($apiUser, $apiKey);
} catch (Exception $ex) {
    echo $siteUrl.'/api/v2_soap/?wsdl<br>';
    print_r($ex->getTrace());
    die();
}

?>