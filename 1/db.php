<?php

$config_db = array(
    'mysql_host' => 'localhost',
    'mysql_db' => 'test',
    'mysql_user' => 'root',
    'mysql_password' => '',
    'mysql_charset' => 'utf8',
);

try {
    $db = new PDO('mysql:host='.$config_db['mysql_host'].';dbname='.$config_db['mysql_db'], $config_db['mysql_user'], $config_db['mysql_password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "'.$config_db['mysql_charset'].'"'));
}
catch(PDOException $pdo_error) {
    die($pdo_error->getMessage());
}