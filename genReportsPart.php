<?php
/*
 * !!!REMOVE REPORTS PARTS FIRST!!!!
 * Берём из базы пользователей которым нужно сгенерировать отчёт и  генерируем их
 * Результатом работы будет файл reports_part.json  который потом нужно переименовать в reports.json
 *  и скормить getReports
 *
 *
 */

set_time_limit(0);
ini_set("memory_limit", -1);

require_once 'functions.php';
require_once 'AmazonAdvertisingApi/Client.php';
$regions = json_decode(@file_get_contents(__DIR__ . '/regions.json'), true);
$countries = json_decode(@file_get_contents(__DIR__ . '/countries.json'), true);

include 'db.php';

$usersResult = $db->query('SELECT genReportsToProcess.user,genReportsToProcess.id, genReportsToProcess.date, 
mws.country_id, mws.code, mws.SellerID
FROM 
genReportsToProcess
INNER JOIN
mws ON
mws.user = genReportsToProcess.user
WHERE done = 0 ');
while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {
    //check again if report still  not done
    $Result = $db->query('SELECT done
FROM 
genReportsToProcess
WHERE id=' . $user['id'] . '
LIMIT 1');
    $r = $Result->fetch(PDO::FETCH_ASSOC);
    if ($r['done'] <> 0) {
        echo "\033[33m Report for user=" . $user['user'] . " date=" . $user['date'] . " already in work \033[0m  \n";
        continue;
    }
    ///end check

    $db->query('update genReportsToProcess set done=2 where id=' . $user['id']);
    $user_id = $user['user'];

    echo "\033[32m Gen reports for " . $user['user'] . "  \033[0m  \n";
    writeLog('GENreportsPart', 'Work with user :' . $user['user'], $user_id);
    switch ($user['country_id']) {
        case 'us':
        case 'ca':
        case 'mx':
            $config = array_merge(json_decode(@file_get_contents(__DIR__ . '/config.json'), true), array(
                'refreshToken' => $user['code'],
                'region'       => 'na'
            ));

            break;

        case  'gb':
        case  'de':
        case  'fr':
        case  'it':
        case  'es':
        case  'in':
            $config = array_merge(json_decode(@file_get_contents(__DIR__ . '/config.json'), true), array(
                'refreshToken' => $user['code'],
                'region'       => 'eu'
            ));
            break;
        default:
            break;

    }
if(empty($user['code'])){
	continue;
}
if(empty($config['refreshToken'])){
	continue;
}
    $client = new AmazonAdvertisingApi\Client($config);
    $reportTime = strtotime($user['date']);
    echo "\033[32m Gen reports for " . $user['user'] . "  time: " . $user['date'] . " \033[0m  \n";
    writeLog('GENreportsPart', 'Work with time : ' . $user['date'], $user_id);
    sleep(1);
	$error=false;
	$sleepTime=5;
	do{
	$profilesResponse = $client->getProfiles();
    $profilesResponse['response'] = json_decode($profilesResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
			if($profilesResponse['code'] !==200){
				 echo "\033[31m Response code " . $profilesResponse['code'] . "  message: " . $profilesResponse['response']['details'] . " \033[0m  \n";
				$error=true;
				echo 'sleep '.$sleepTime.' sec';
				sleep($sleepTime);
			//	if($sleepTime <60){
					$sleepTime+=5;
			//	}
			}
	}while($error);
   unset($error);
    $currentProfile = NULL;
    foreach ($profilesResponse['response'] as $profile) {

        if (strtolower($profile['countryCode']) == $user['country_id'] || ($profile['countryCode'] == 'UK' && $user['country_id'] == 'gb')) {
            if ($profile["accountInfo"]["sellerStringId"] == $user['SellerID']) {
                $currentProfile = $profile;
            }
        }
    }

    if ($currentProfile) {
        $client->profileId = $currentProfile['profileId'];

        //check if we have no record for this user and mark snapshot table to  start update
        $adgroupsCount = $db->query('select count(*) as total from snapshot_adgroups WHERE `userId`="' . $user['user'] . '"');
        $adgroups = $adgroupsCount->fetch(PDO::FETCH_NUM);
        if ($adgroups[0] == 0) {
            $db->query("INSERT INTO `snapshots` (`type`, `needUpdate`, `status`, `snapshotId`, `userId`) VALUES ('adGroups', 1, 'NEW', '', '" . $user['user'] . "') ON DUPLICATE KEY UPDATE `needUpdate` = 1 ");
        }
        $campaignsCount = $db->query('select count(*) as total from snapshot_campaigns WHERE `userId`="' . $user['user'] . '"');
        $campaigns = $campaignsCount->fetch(PDO::FETCH_NUM);
        if ($campaigns[0] == 0) {
            $db->query("INSERT INTO `snapshots` (`type`, `needUpdate`, `status`, `snapshotId`, `userId`) VALUES ('campaigns', 1, 'NEW', '', '" . $user['user'] . "') ON DUPLICATE KEY UPDATE `needUpdate` = 1 ");
        }

        $keywordsCount = $db->query('select count(*) as total from snapshot_keywords WHERE `userId`="' . $user['user'] . '"');
        $keywords = $keywordsCount->fetch(PDO::FETCH_NUM);
        if ($keywords[0] == 0) {
            $db->query("INSERT INTO `snapshots` (`type`, `needUpdate`, `status`, `snapshotId`, `userId`) VALUES ('keywords', 1, 'NEW', '', '" . $user['user'] . "') ON DUPLICATE KEY UPDATE `needUpdate` = 1 ");
        }

        $productadsCount = $db->query('select count(*) as total from snapshot_productads WHERE `userId`="' . $user['user'] . '"');
        $productads = $productadsCount->fetch(PDO::FETCH_NUM);
        if ($productads[0] == 0) {
            $db->query("INSERT INTO `snapshots` (`type`, `needUpdate`, `status`, `snapshotId`, `userId`) VALUES ('productAds', 1, 'NEW', '', '" . $user['user'] . "') ON DUPLICATE KEY UPDATE `needUpdate` = 1 ");
        }

        //request all snapshots
        $snapshotsToRequest = $db->query('SELECT snapshots.`type` FROM snapshots WHERE snapshots.needUpdate = 1');
        while ($snapshotKeys = $snapshotsToRequest->fetch(PDO::FETCH_ASSOC)) {
            $snapshotRequest = $client->requestSnapshot($snapshotKeys['type'], array(
                'campaignType' => 'sponsoredProducts'
            ));
            if ($snapshotRequest['success']) {
                $response = json_decode($snapshotRequest['response']);
                $db->query("UPDATE snapshots SET needUpdate = 0, `status` ='" . $response->status . "', snapshotId ='" . $response->snapshotId . "' WHERE `type` ='" . $snapshotKeys['type'] . "' AND `userId` =  " . $user['user'] . " LIMIT 1 ");

            } else {
                $response = json_decode($snapshotRequest['response']);
				print_r($response);
                echo 'Response code= ' . $response->code . ' Details=' . $response->details;
                exit();
            }
        }
        /*
                    'productAds'    =>{"adId":92401137296203,"adGroupId":170796789401382,"campaignId":12904586900739,"asin":"B00RO3Y5AW","sku":"U1-L9RV-MZ1W","state":"enabled"}
                    'keywords'      =>{"keywordId":150373667858280,"adGroupId":146464001908020,"campaignId":5159438961908,"keywordText":"kitty","matchType":"broad","state":"enabled"}
                    'campaigns'     =>{"campaignId":5159438961908,"name":"Beige cat mat Manual Targeting Strategy","campaignType":"sponsoredProducts","targetingType":"manual","premiumBidAdjustment":true,"dailyBudget":25.0,"startDate":"20150414","state":"enabled"}
                    'adGroups'      =>{"adGroupId":170796789401382,"name":"Automatic kitty litter may campaign","campaignId":12904586900739,"defaultBid":0.75,"state":"enabled"}
        */

  
   $reportsResponse = $client->requestReport('productAds', array(
            'reportDate'   => $user['date'],
            'campaignType' => 'sponsoredProducts',
            'metrics'      => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
        ));

        $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

        if ($reportsResponse['success'] && ($reportsResponse['response']['reportId'] != '')) {
            addReportPart($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'productAds');
            writeLog('GENreportsPart', 'Add report user=' . $user['user'] . ' date=' . $user['date'] . ' productAds', $user_id);
        }

        $reportsResponse = $client->requestReport('keywords', array(
            'reportDate'   => $user['date'],
            //'segment' => 'query',
            'campaignType' => 'sponsoredProducts',
            'metrics'      => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
        ));

        $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

        if ($reportsResponse['success'] && ($reportsResponse['response']['reportId'] != '')) {
            addReportPart($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'keywords');
            writeLog('GENreportsPart', 'Add report  user=' . $user['user'] . ' date=' . $user['date'] . ' keywords', $user_id);
        }

   /*  ENABLE WHEN UPLOAD ALL FILES*/
   $reportsResponse = $client->requestReport('keywords', array(
            'reportDate'   => $user['date'],
            'segment'      => 'query',
            'campaignType' => 'sponsoredProducts',
            'metrics'      => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
        ));

        $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

        if ($reportsResponse['success'] && ($reportsResponse['response']['reportId'] != '')) {
            addReportPart($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'keywordsQuery');
            writeLog('GENreportsPart', 'Add report   user=' . $user['user'] . ' date=' . $user['date'] . ' keywordsQuery', $user_id);
        }
		
		
    } else {
        echo "Can't find a profile";
        print_r($profilesResponse);
        writeLog('GENreportsPart', 'Can\'t find a profile ', $user_id);
    }

    $db->query('update genReportsToProcess set done=1 where id=' . $user['id']);
}
echo '--FINISH--';
writeLog('GENreportsPart', '--FINISH-- ', $user_id);