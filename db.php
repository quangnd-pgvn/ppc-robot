<?php

// $config_db = array(
//     'mysql_host' => 'ppcentourage.cluster-cqcfefpm2ww2.us-west-2.rds.amazonaws.com',
//     'mysql_db' => 'test',
//     'mysql_user' => 'entourage',
//     'mysql_password' => 'ENTO_Ppc2016',
//     'mysql_charset' => 'utf8',
// );

$config_db = array(
    'mysql_host' => '127.0.0.1',
    'mysql_db' => 'ppcdemo2',
    'mysql_user' => 'root',
    'mysql_password' => 'longthan',
    'mysql_charset' => 'utf8',
);

try {
    $db = new PDO('mysql:host='.$config_db['mysql_host'].';dbname='.$config_db['mysql_db'].';charset=utf8', $config_db['mysql_user'], $config_db['mysql_password']);
}
catch(PDOException $pdo_error) {
    die($pdo_error->getMessage());
}