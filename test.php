<?php
set_time_limit(0);
ini_set("memory_limit", -1);

require_once 'functions.php';
$fromTime = strtotime(@$_GET['date'] ? $_GET['date'] : $argv['1']);
if (isset($argv['2'])) $user_id1 = ' and user=' . $argv['2']; else $user_id1 = '';
$tillTime = time();
include 'db.php';
$Result = $db->query('SELECT requestId FROM genReportsToProcess ORDER BY requestId DESC  LIMIT 1');
if($Result){
$request= $Result->fetch(PDO::FETCH_ASSOC);
$requestId = $request['requestId'];
$requestId++;
}else{
	$requestId =1;
}


$usersResult = $db->query('SELECT * FROM `mws` WHERE `code` IS NOT NULL' . $user_id1);



while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {
   

    for ($reportTime = $fromTime; $reportTime <= $tillTime; $reportTime = $reportTime + 86400) {
		
echo "\033[32m Gen reports for ".$user['user']."  time: ".date("Ymd",$reportTime)." \033[0m  \n";	
$data=['user'=>$user['user'],
'date'=>date("Ymd",$reportTime),
'requestId'=>$requestId
];


 $result =    $db->prepare('INSERT INTO `genReportsToProcess` (`'.implode('`, `', array_keys($data)).'`) VALUES (?'.str_repeat(', ?', count($data) - 1).')')->execute(array_values($data));
							 if(!$result){
				echo "\033[31m record not inserted ".$db->errorInfo()." \033[0m  \n";
				}else{
				echo "\033[32m record inserted \033[0m  \n";	
				}


    }
}
echo '1';
