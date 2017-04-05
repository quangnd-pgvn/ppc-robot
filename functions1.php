<?php

function addReport($report, $profile_id, $user_id, $region, $refresh_token, $time, $type) {
    $reports = json_decode(file_get_contents(__DIR__.'/reports'.$user_id.'.json'), true, 512, JSON_BIGINT_AS_STRING);

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
if($reports){
    return file_put_contents(__DIR__.'/reports'.$user_id.'.json', json_encode($reports));
}else{
	return false;
}
}

/**
Add empty report to list
*/
function addEmptyReport($report, $userid=0) {
    $reports = json_decode(file_get_contents(__DIR__.'/reports_empty_'.$userid.'.json'), true, 512, JSON_BIGINT_AS_STRING);

    $reports[] = $report;

    return file_put_contents(__DIR__.'/reports_empty_'.$userid.'.json', json_encode($reports));
}

function getReport_old($user_id) {
	$diff = 86400;
    $reports = json_decode(file_get_contents(__DIR__.'/reports'.$user_id.'.json'), true, 512, JSON_BIGINT_AS_STRING);

    do {
        $report = array_shift($reports);
    } while($report AND time() - $report['_time'] > $diff);
if($reports){
    file_put_contents(__DIR__.'/reports'.$user_id.'.json', json_encode($reports));
}
    return $report;
}

/**
New version this function lock reports.json 
*/
function getReport($user_id)
{
	$diff = 86400;
	$minDiff=900; // 15 min diff
    $filename = __DIR__ . '/reports'.$user_id.'.json';
	if (!file_exists($filename)) {
		$fh = fopen($filename, 'w') or die("Can't create file");
	}
    if (is_writable($filename)) {
        $fp = fopen($filename, "a+");
        do {
            $locked = false;
            if ($t = flock($fp, LOCK_EX | LOCK_NB, $wouldblock)) {
				clearstatcache();
                $reports = json_decode(fread($fp, filesize($filename)), true, 512, JSON_BIGINT_AS_STRING);
           /*     do {
                    $report = array_shift($reports);
                } while ($report AND time() - $report['_time'] > $diff);*/
			do {
				$next=false;
				if(count($reports)==0){
					$report=false;
					break;
				}
				$report = array_shift($reports);
				if(time() - $report['_time'] < $minDiff){
					echo "report too young \n";
	//					writeLog ('FUNCTIONS1_getreport', $filename . " report too young \n");
					$reports[]=$report;
					$next=true;
				}
				if(time() - $report['_time'] > $diff){
					echo "report too old \n";
//						writeLog ('FUNCTIONS1_getreport', $filename . " report too old \n");
					$next=true;
				}
			} while($next);
				
				
				
				
				if($reports!==false ){
                $data = json_encode($reports);
                ftruncate($fp, 0); // очищаем файл
                if (!$write = fwrite($fp, $data)) {
                    echo "Не могу произвести запись в файл ($filename) \n";
						writeLog ('FUNCTIONS1_getreport', $filename . " Не могу произвести запись в файл\n");
                    exit;
                }
				}else{
					 echo "Не могу произвести запись в файл ($filename) \n";
					writeLog ('FUNCTIONS1_getreport', $filename . " чтото не так с репортс reports\n");
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
        $reportMsg = "working with report: ";
        $reportMsg .= "type= \033[36m".$report['type']."\033[0m ";
        $reportMsg .= " userId= \033[36m".$report['user_id']."\033[0m ";
        $reportMsg .= " reportDate= \033[36m".date('Y-m-d', $report['time'])."\033[0m ";
        $reportMsg .= " report generated at = \033[36m".date('Y-m-d H:i:s', $report['_time'])."\033[0m ";
        echo $reportMsg." \n";
        return $report;
    } else {
        echo($filename . " is not writable\n");
		writeLog ('FUNCTIONS1_getreport', $filename . " is not writable\n");
        exit();
    }
}


function getCache($key) {
    return file_exists(__DIR__.'/cache/'.$key.'.json') ? json_decode(file_get_contents(__DIR__.'/cache/'.$key.'.json'), true, 512, JSON_BIGINT_AS_STRING) : false;
}

function setCache($key, $value) {
    return file_put_contents(__DIR__.'/cache/'.$key.'.json', json_encode($value));
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
