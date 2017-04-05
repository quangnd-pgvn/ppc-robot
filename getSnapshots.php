<?php
/**
 * Search in table snapshots  records with status IN_PROGRESS. and download all snapsots,
 * recognise them and save into DB
 *
 */
set_time_limit(0);
ini_set("memory_limit", -1);

require_once 'functions.php';
require_once 'AmazonAdvertisingApi/Client.php';

$regions = json_decode(@file_get_contents(__DIR__ . '/regions.json'), true);
$countries = json_decode(@file_get_contents(__DIR__ . '/countries.json'), true);

include 'db.php';
$usersResult = $db->query('SELECT * FROM `mws` WHERE `code` IS NOT NULL'); 
while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {
    echo ("=========Working with user ".$user['user']."===========\r\n");
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

    $client = new AmazonAdvertisingApi\Client($config);
    $profilesResponse = $client->getProfiles();
    $profilesResponse['response'] = json_decode($profilesResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
    $currentProfile = NULL;
    foreach ($profilesResponse['response'] as $profile) {
        if (strtolower($profile['countryCode']) == $user['country_id'] || ($profile['countryCode'] == 'UK' && $user['country_id'] == 'gb')) {
        if ($profile["accountInfo"]["sellerStringId"] == $user['SellerID'])
				{
                $currentProfile = $profile;
				}
        }
    }

    if ($currentProfile) {
        $client->profileId = $currentProfile['profileId'];
        $snapshotsToRequest = $db->query("SELECT snapshots.snapshotId, snapshots.`type` FROM snapshots WHERE snapshots.`status` = 'IN_PROGRESS'  AND `userId`=" . $user['user']);
        while ($snapshot = $snapshotsToRequest->fetch(PDO::FETCH_ASSOC)) {
            $snapshotRequest = $client->getSnapshot($snapshot['snapshotId']);
            if ($snapshotRequest['success']) {
                $response = json_decode($snapshotRequest['response']);
                if (!empty($response)) {
                    if(!isset($response->status)) {
                        echo ("-----Working with snapshot ".$snapshot['type']."------\r\n");
                        switch ($snapshot['type']) {
                            case 'adGroups':
                                $prepared = $db->prepare('REPLACE INTO `snapshot_adgroups` (`' . implode('`, `', array_keys((array)$response[0])) . '`, `userId`) VALUES (?' . str_repeat(', ?', count((array)$response[0])) . ')');
                                foreach ($response as $adGroup) {
                                    $adGroup->userId = $user['user'];
                                    $result = $prepared->execute(array_values((array)$adGroup));
                                }
                                $db->query("UPDATE snapshots SET needUpdate = 0, status='UPDATED' WHERE  `type` ='adGroups' AND `userId`=" . $user['user'] . "  LIMIT 1");

                                break;

                            case 'campaigns':
                                $prepared = $db->prepare('REPLACE INTO `snapshot_campaigns` (`campaignId`, `name`, `campaignType`, `targetingType`, `premiumBidAdjustment`, `dailyBudget`, `startDate`, `state`, `userId`) VALUES (?,?,?,?,?,?,?,?,?)');
                                foreach ($response as $campain) {
                                    $insert = array();
                                    $insert['campaignId'] = isset($campain->campaignId) ? $campain->campaignId : '';
                                    $insert['name'] = isset($campain->name) ? $campain->name : '';
                                    $insert['campaignType'] = isset($campain->campaignType) ? $campain->campaignType : '';
                                    $insert['targetingType'] = isset($campain->targetingType) ? $campain->targetingType : '';
                                    $insert['premiumBidAdjustment'] = isset($campain->premiumBidAdjustment) ? $campain->premiumBidAdjustment : 0;
                                    $insert['dailyBudget'] = isset($campain->dailyBudget) ? $campain->dailyBudget : '';
                                    $insert['startDate'] = isset($campain->startDate) ? $campain->startDate : '';
                                    $insert['state'] = isset($campain->state) ? $campain->state : '';
                                    $insert['userId'] = $user['user'];
                                    $result = $prepared->execute(array_values($insert));
                                }
                                $db->query("UPDATE snapshots SET needUpdate = 0, status='UPDATED' WHERE  `type` ='campaigns' AND `userId`=" . $user['user'] . "  LIMIT 1");
                                break;

                            case 'keywords':
                                //prepare a big  string
                                $countRecords = count($response);
                                $sqlBase = 'INSERT IGNORE INTO `snapshot_keywords` (`keywordId`, `adGroupId`, `campaignId`, `keywordText`, `matchType`, `state`, `bid`, `userId`) VALUES';

                                $i = 0;
                                $addonSql = '';
                                $partialData = array();
                                foreach ($response as $keyword) {
                                    $addonSql .= ' (?, ?, ?, ?, ?, ?, ?,?),';
                                    if (!isset($keyword->bid)) {
                                        $keyword->bid = 0;
                                    }
                                    $keyword->userId = $user['user'];
                                    $partialData = array_merge($partialData, array_values((array)$keyword));

                                    if ($i > 1000) {
                                        $addonSql = substr($addonSql, 0, -1);
                                        $prepared = $db->prepare($sqlBase . $addonSql);
                                        $result = $prepared->execute((array)$partialData);
                                        if (!$result) {
                                            print_r($db->errorInfo());
                                            exit();
                                        }
                                        $i = 0;
                                        $addonSql = '';
                                        $partialData = array();

                                    } else {
                                        $i++;
                                    }


                                }
                                //and add last part
                                $addonSql = substr($addonSql, 0, -1);
                                $prepared = $db->prepare($sqlBase . $addonSql);
                                $result = $prepared->execute((array)$partialData);

                                $db->query("UPDATE snapshots SET needUpdate = 0, status='UPDATED' WHERE  `type` ='keywords' AND `userId`=" . $user['user'] . "  LIMIT 1");

                                break;

                            case 'productAds':

                                $prepared = $db->prepare('REPLACE INTO `snapshot_productads` (`adId`, `adGroupId`, `campaignId`, `asin`, `sku`, `state`, `userId`) VALUES (?, ?, ?, ?, ?, ?, ?)');
                                foreach ($response as $productAds) {
                                    $insert = array();
                                    $insert['adId'] = isset($productAds->adId) ? $productAds->adId : '';
                                    $insert['adGroupId'] = isset($productAds->adGroupId) ? $productAds->adGroupId : '';
                                    $insert['campaignId'] = isset($productAds->campaignId) ? $productAds->campaignId : '';
                                    $insert['asin'] = isset($productAds->asin) ? $productAds->asin : '';
                                    $insert['sku'] = isset($productAds->sku) ? $productAds->sku : '';
                                    $insert['state'] = isset($productAds->state) ? $productAds->state : '';
                                    $insert['userId'] = $user['user'];
                                    $result = $prepared->execute(array_values($insert));
                                }
                                $db->query("UPDATE snapshots SET needUpdate = 0, status='UPDATED' WHERE  `type` ='productAds' AND `userId`=" . $user['user'] . "  LIMIT 1");

                                break;
                        }
                    }elseif($response->status=='FAILURE'){
                        echo ("\033[31m -----Snapshot ".$response->snapshotId."  FAILURE------\033[0m\r\n");
                        $db->query("UPDATE snapshots SET needUpdate = 1, status='FAILURE' WHERE  `snapshotId` ='".$response->snapshotId."' AND `userId`=" . $user['user'] . "  LIMIT 1");
                    }
                }
            } else {
                $response = json_decode($snapshotRequest['response']);
                echo 'Response code= ' . $response->code . ' Details=' . $response->details;
                exit();
            }
        }
    }
}
echo '1';

