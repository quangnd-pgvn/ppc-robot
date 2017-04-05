<?php
/*
function addReport($report, $profile_id, $user_id, $region, $refresh_token, $time, $type) {
    $reports = json_decode(file_get_contents(__DIR__.'/reports.json'), true, 512, JSON_BIGINT_AS_STRING);

    $reports[] = [
        'report' => $report,
        'type' => $type,
        'user_id' => $user_id,
        'profile_id' => $profile_id,
        'region' => $region,
        'refresh_token' => $refresh_token,
        'time' => $time,
        '_time' => time()
    ];

    return file_put_contents(__DIR__.'/reports.json', json_encode($reports));
}
*/
function addReport($report, $profile_id, $user_id, $region, $refresh_token, $time, $type)
{
	echo "add report \n";
    $filename = __DIR__ . '/reports.json';
    if (is_writable($filename)) {
        $fp = fopen($filename, "a+");
        do {
            $locked = false;
            if ($t = flock($fp, LOCK_EX | LOCK_NB, $wouldblock)) {
				clearstatcache();
			  $reports = json_decode(fread($fp, filesize($filename)), true);
	//			echo 'reports';
	//			$t1=	fread($fp, filesize($filename));
		//	echo $t1;
	//		echo 'reports12 \n';
	//		$reports = json_decode($t1, true);
//var_dump($reports);
				if(!$reports){
				 echo($filename . " cant get data from file \n");
				 	writeLog ('FUNCTIONS_addreport', $filename . " cant get data from file \n");
				   flock($fp, LOCK_UN); // отпираем файл
        exit();
				}
			$reports[] = [
				'report' => $report,
					'type' => $type,
					'user_id' => $user_id,
					'profile_id' => $profile_id,
					'region' => $region,
					'refresh_token' => $refresh_token,
					'time' => $time,
					'_time' => time()
				];
                $data = json_encode($reports);
				if(empty($data) OR $data==null OR !$data){
					echo "\033[31m somthing wrong with data \033[0m \n";
					flock($fp, LOCK_UN); // отпираем файл
				}else{
               ftruncate($fp, 0); // очищаем файл
                if (!$write = fwrite($fp, $data)) {
                    echo "Не могу произвести запись в файл ($filename) \n";
					writeLog ('FUNCTIONS_addreport', "Не могу произвести запись в файл ($filename) \n");
                    exit;
                }
	//			file_put_contents($filename, json_encode($reports));
				}
                flock($fp, LOCK_UN); // отпираем файл
            } else {
                if ($wouldblock) {
                    echo($filename . " File already  locked. Wait....\n");
					writeLog ('FUNCTIONS_addreport', $filename . " File already  locked. Wait....\n");
                    $locked = true;
                    sleep(5);
                } else {
                    echo($filename . " cant obtain Lock\n");
						writeLog ('FUNCTIONS_addreport', $filename . " cant obtain Lock\n");
                }
            }
        } while ($locked);
        fclose($fp);
//		echo "working with report \n";
//		print_r($report);
        return $report;
    } else {
        echo($filename . " is not writable\n");
		writeLog ('FUNCTIONS_addreport', $filename . " is not writable\n");
        exit();
    }
}

/**
Add empty report to list
*/
function addEmptyReport($report) {
    $reports = json_decode(file_get_contents(__DIR__.'/reports_empty.json'), true, 512, JSON_BIGINT_AS_STRING);

    $reports[] = $report;

    return file_put_contents(__DIR__.'/reports_empty.json', json_encode($reports));
}

function getReport_old($diff = 86400) {
    $reports = json_decode(file_get_contents(__DIR__.'/reports.json'), true, 512, JSON_BIGINT_AS_STRING);

    do {
        $report = array_shift($reports);
    } while($report AND time() - $report['_time'] > $diff);

    file_put_contents(__DIR__.'/reports.json', json_encode($reports));

    return $report;
}

/**
New version this function lock reports.json 
*/
function getReport($diff = 86400)
{
	$minDiff=900; // 15 min diff
    $filename = __DIR__ . '/reports.json';
    if (is_writable($filename)) {
        $fp = fopen($filename, "a+");
        do {
            $locked = false;
            if ($t = flock($fp, LOCK_EX | LOCK_NB, $wouldblock)) {
				clearstatcache();
                $reports = json_decode(fread($fp, filesize($filename)), true, 512, JSON_BIGINT_AS_STRING);
             /*   do {
                    $report = array_shift($reports);
                } while ($report AND time() - $report['_time'] > $diff);*/
				echo "we have ".count($reports)." to work \n";
			do {
				$next=false;
				if(count($reports)==0){
					$report=false;
					break;
				}
				$report = array_shift($reports);
				if(time() - $report['_time'] < $minDiff){
					echo "report too young \n";
					writeLog ('FUNCTIONS_getreport',  "report too young \n");
					$reports[]=$report;
					$next=true;
				}
				if(time() - $report['_time'] > $diff){
					echo "report too old \n";
					writeLog ('FUNCTIONS_getreport',  "report too old \n");
					$next=true;
				}
			} while($next);
				
				
                $data = json_encode($reports);
                ftruncate($fp, 0); // очищаем файл
                if (!$write = fwrite($fp, $data)) {
                    echo "Не могу произвести запись в файл ($filename) \n";
					writeLog ('FUNCTIONS_getreport', $filename . "Не могу произвести запись в файл \n");
                    exit;
                }
                flock($fp, LOCK_UN); // отпираем файл
            } else {
                if ($wouldblock) {
                    echo($filename . " File already  locked. Wait....\n");
					writeLog ('FUNCTIONS_getreport', $filename . " File already  locked. Wait....\n");
                    $locked = true;
                    sleep(5);
                } else {
                    echo($filename . " cant obtain Lock\n");
					writeLog ('FUNCTIONS_getreport', $filename . " cant obtain Lock\n");
                }
            }
        } while ($locked);
        fclose($fp);
        $reportMsg = "working with report: ";
        $reportMsg .= "type= \033[36m".$report['type']."\033[0m ";
        $reportMsg .= " userId= \033[36m".$report['user_id']."\033[0m ";
        $reportMsg .= " reportDate= \033[36m".date('Y-m-d', $report['time'])."\033[0m ";
        $reportMsg .= " report generated at = \033[36m".date('Y-m-d H:i:s', $report['_time'])."\033[0m ";
        echo $reportMsg." \n";
        return $report;
    } else {
        echo($filename . " is not writable\n");
		writeLog ('FUNCTIONS_getreport', $filename . " is not writable\n");
        exit();
    }
}
/**
New version this function lock reports.json 
*/
function getEmptyReport($diff = 86400)
{
    $filename = __DIR__ . '/reports_empty.json';
    if (is_writable($filename)) {
        $fp = fopen($filename, "a+");
        do {
            $locked = false;
            if ($t = flock($fp, LOCK_EX | LOCK_NB, $wouldblock)) {
				clearstatcache();
                $reports = json_decode(fread($fp, filesize($filename)), true, 512, JSON_BIGINT_AS_STRING);
				echo "\033[32m  WE have ".count($reports). "empty reports in file \033[0m \n";
                do {
                    $report = array_shift($reports);
                } while ($report AND time() - $report['_time'] > $diff);
                $data = json_encode($reports);
                ftruncate($fp, 0); // очищаем файл
                if (!$write = fwrite($fp, $data)) {
                    echo "Не могу произвести запись в файл ($filename) \n";
                    exit;
                }
                flock($fp, LOCK_UN); // отпираем файл
            } else {
                if ($wouldblock) {
                    echo($filename . " File already  locked. Wait....\n");
                    $locked = true;
                    sleep(5);
                } else {
                    echo($filename . " cant obtain Lock\n");
                }
            }
        } while ($locked);
        fclose($fp);
		echo "working with report \n";
		print_r($report);
        return $report;
    } else {
        echo($filename . " is not writable\n");
        exit();
    }
}

function getCache($key) {
    return file_exists(__DIR__.'/cache/'.$key.'.json') ? json_decode(file_get_contents(__DIR__.'/cache/'.$key.'.json'), true, 512, JSON_BIGINT_AS_STRING) : false;
}

function setCache($key, $value) {
    return file_put_contents(__DIR__.'/cache/'.$key.'.json', json_encode($value));
}

//@todo  merge requests into 1 (for example productADS  with campaigns and adgroups
/*
 *
SELECT *
FROM snapshot_productads
LEFT JOIN
snapshot_campaigns ON
snapshot_productads.campaignId =snapshot_campaigns.campaignId
LEFT JOIN
snapshot_adgroups ON
snapshot_productads.adGroupId= snapshot_adgroups.adGroupId
WHERE snapshot_productads.adId = 534705714696
LIMIT 1
 *
 SELECT *
FROM snapshot_keywords
LEFT JOIN
snapshot_adgroups ON
snapshot_keywords.adGroupId= snapshot_adgroups.adGroupId
LEFT JOIN
snapshot_campaigns ON
snapshot_keywords.campaignId =snapshot_campaigns.campaignId
WHERE snapshot_keywords.keywordId = 154524941680640
LIMIT 1
 *
 */
/**
 * Search in database data from snapshot
 * @param $db link to database connection
 * @param $type snapshot type
 * @param $key search key
 * @return bool false if nothing found. and founded result if record exists
 */
function searchSnapshot($db, $type, $key)
{
    switch ($type) {
        case'productAds':
            $sql = "SELECT * FROM snapshot_productads WHERE snapshot_productads.adId =" . $key . " LIMIT 1";
            break;
        case'keywords':
            $sql = "SELECT * FROM snapshot_keywords WHERE snapshot_keywords.keywordId =" . $key . " LIMIT 1";
            break;
        case'campaigns':
            $sql = "SELECT * FROM snapshot_campaigns WHERE snapshot_campaigns.campaignId =" . $key . " LIMIT 1";
            break;
        case'adgroups':
            $sql = "SELECT * FROM snapshot_adgroups WHERE snapshot_adgroups.adGroupId =" . $key . " LIMIT 1";
            break;
    }

    $result = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        return $result;
    } else {
        return false;
    }

}

function writeLog ($script='SCRIPT', $msg='', $fileSuffix='', $newFile=false){
	 
	$filename = __DIR__ . '/logs/'.$script.'_'.$fileSuffix.'.log';
	if (!file_exists($filename)) {
		$fh = fopen($filename, 'w') or die("Can't create file");
	}
    if (is_writable($filename)) {
		if($newFile){
			$fp = fopen($filename, "w");
		}else{
			$fp = fopen($filename, "a");
		}
		$data = date("Y-m-d H:i:s")."\t".$msg."\n";
			if (!$write = fwrite($fp, $data)) {
				echo "\033[31m Не могу произвести запись в файл ($filename) \033[0m  \n";
			}
		fclose($fp);
	}else{
		echo "\033[31m Log file not writable \033[0m  \n";	
	}
}

function addReportPart($report, $profile_id, $user_id, $region, $refresh_token, $time, $type)
{
	echo "add report \n";
    $filename = __DIR__ . '/reports.json';
	if (!file_exists($filename)) {
		$fh = fopen($filename, 'w') or die("Can't create file");
	}
    if (is_writable($filename)) {
        $fp = fopen($filename, "a+");
        do {
            $locked = false;
            if ($t = flock($fp, LOCK_EX | LOCK_NB, $wouldblock)) {
				clearstatcache();
			  $reports = json_decode(fread($fp, filesize($filename)), true);
	//			echo 'reports';
	//		$t1=	fread($fp, filesize($filename));
	//		var_dump($t1);
	//		echo "reports12 \n";
	//		$reports = json_decode($t1, true);
//var_dump($reports);
				if($reports===false){
				 echo($filename . " cant get data from file \n");
				 	writeLog ('FUNCTIONS_addreport', $filename . " cant get data from file \n");
				   flock($fp, LOCK_UN); // отпираем файл
        exit();
				}
			$reports[] = [
				'report' => $report,
					'type' => $type,
					'user_id' => $user_id,
					'profile_id' => $profile_id,
					'region' => $region,
					'refresh_token' => $refresh_token,
					'time' => $time,
					'_time' => time()
				];
                $data = json_encode($reports);
				if(empty($data) OR $data==null OR !$data){
					echo "\033[31m somthing wrong with data \033[0m \n";
					flock($fp, LOCK_UN); // отпираем файл
				}else{
               ftruncate($fp, 0); // очищаем файл
                if (!$write = fwrite($fp, $data)) {
                    echo "Не могу произвести запись в файл ($filename) \n";
					writeLog ('FUNCTIONS_addreport', "Не могу произвести запись в файл ($filename) \n");
                    exit;
                }
	//			file_put_contents($filename, json_encode($reports));
				}
                flock($fp, LOCK_UN); // отпираем файл
            } else {
                if ($wouldblock) {
                    echo($filename . " File already  locked. Wait....\n");
					writeLog ('FUNCTIONS_addreport', $filename . " File already  locked. Wait....\n");
                    $locked = true;
                    sleep(2);
                } else {
                    echo($filename . " cant obtain Lock\n");
						writeLog ('FUNCTIONS_addreport', $filename . " cant obtain Lock\n");
                }
            }
        } while ($locked);
        fclose($fp);
//		echo "working with report \n";
//		print_r($report);
        return $report;
    } else {
        echo($filename . " is not writable\n");
		writeLog ('FUNCTIONS_addreport', $filename . " is not writable\n");
        exit();
    }
}