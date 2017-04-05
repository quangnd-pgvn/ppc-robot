<?php
/**
 * Выбираем юзеров для которых нужны отчёты и списком их вставляем в базу данных.
 * В результате работы получим просто список  юзеров и дней для которых нужны отчёты
 * Потом с базы робот genReportsPart будет забирать по очереди и обрабатывать
 * запуск
 *
 * php genReportsToProcessList.php "-8 days"    - сгенерирует за 8 дней
 */
set_time_limit(0);
ini_set("memory_limit", -1);
$start =microtime(true);

require_once 'functions.php';
$fromTime = strtotime(@$_GET['date'] ? $_GET['date'] : $argv['1']);
if (isset($argv['2'])) $user_id1 = ' and user=' . $argv['2']; else $user_id1 = '';
$tillTime = time();
include 'db.php';
$Result = $db->query('SELECT requestId FROM genReportsToProcess ORDER BY requestId DESC  LIMIT 1');
if ($Result) {
    $request = $Result->fetch(PDO::FETCH_ASSOC);
    $requestId = $request['requestId'];
    $requestId++;
} else {
    $requestId = 1;
}
$usersResult = $db->query('SELECT count(*) as totalUser FROM `mws` WHERE `code` IS NOT NULL' . $user_id1);
$users = $usersResult->fetch(PDO::FETCH_ASSOC);
$totalUsers=$users['totalUser'];
$totalDays = ($tillTime -$fromTime)/86400;
echo $totalUsers.'*'.$totalDays;
$totalRecords = $totalUsers*($totalDays + 1);
$usersResult = $db->query('SELECT * FROM `mws` WHERE `code` IS NOT NULL' . $user_id1);
$i=1;
while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {
    for ($reportTime = $fromTime; $reportTime <= $tillTime; $reportTime = $reportTime + 86400) {
        echo "\033[32m Prepare Gen reports for " . $user['user'] . "  time: " . date("Ymd", $reportTime) . "   (".$i."/".$totalRecords.")   \033[0m  \n";
        $data = ['user'      => $user['user'],
                 'date'      => date("Ymd", $reportTime),
                 'requestId' => $requestId
        ];
        $result = $db->prepare('INSERT INTO `genReportsToProcess` (`' . implode('`, `', array_keys($data)) . '`) VALUES (?' . str_repeat(', ?', count($data) - 1) . ')')->execute(array_values($data));
        if (!$result) {
            echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
        } else {
            echo "\033[32m record inserted \033[0m  \n";
        }
        $i++;
    }
}
$workingTime =  microtime(true)-$start;

$hours = floor($workingTime / 3600);
$minutes = floor(($workingTime / 60) % 60);
$seconds = $workingTime % 60;

echo "\033[32m Script finish in ".$hours.":".$minutes.":".$seconds."    \033[0m  \n";
echo 'Done';
