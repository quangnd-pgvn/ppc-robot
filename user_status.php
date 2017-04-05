<?php
$inputJSON = file_get_contents('php://input');
$input= json_decode( $inputJSON, TRUE );

$link = mysql_connect('localhost', 'root', '');
if (!$link) {
    die('Could not connect: ' . mysql_error());
}

mysql_select_db('test', $link) or die('Could not select database.');

// if

if ($input['content']['subscription']["status"]=="cancelled")

{
// trying to find it in the list of subscriptions

//@mysql_query("INSERT INTO mws_cancelled select * from mws where subscription_id = '".$input['content']['subscription']["id"]."'");
//@mysql_query("DELETE from mws where subscription_id = '".$input['content']['subscription']["id"]."'");




}

mysql_close($link);



var_dump($input['content']['subscription']["id"]);
var_dump($input['content']['subscription']["status"]);
var_dump($input['content']['subscription']["plan_id"]);




?>