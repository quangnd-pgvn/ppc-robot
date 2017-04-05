<?php

set_time_limit(0);
ini_set("memory_limit", -1);

require_once 'functions1.php';
require_once 'AmazonAdvertisingApi/Client.php';

$fromTime = strtotime(@$_GET['date'] ? $_GET['date'] : $argv['1']);
if (isset($argv['2'])) $user_id1= ' and user='.$argv['2']; else $user_id1='';
$tillTime = time();
$regions = json_decode(@file_get_contents(__DIR__.'/regions.json'), true);

include 'db.php';

$usersResult = $db->query('SELECT `code`, `country_id`, `user` FROM `mws` WHERE `code` IS NOT NULL'.$user_id1);

while($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {



//    if(!$regions[$user['country_id']] OR ! strlen(trim($user['code'])))
//        continue;

switch ($user['country_id']) 
{

case 'us':
case 'ca':
case 'mx':
    $config = array_merge(json_decode(@file_get_contents(__DIR__.'/config.json'), true), array(
        'refreshToken' => $user['code'],
        'region' => 'na'
    ));


break;

case  'gb':
case  'de':
case  'fr':
case  'it':
case  'es':
case  'in':
    $config = array_merge(json_decode(@file_get_contents(__DIR__.'/config.json'), true), array(
        'refreshToken' => $user['code'],
        'region' => 'eu'
    ));


break;

default:

break;

}

    $client = new AmazonAdvertisingApi\Client($config);

    for($reportTime = $fromTime; $reportTime <= $tillTime; $reportTime = $reportTime + 86400) {

        sleep(3);

        $groups_cached = array();
        $keywords_cached = array();
        $products_cached = array();
        $campaigns_cached = array();

        $profilesResponse = $client->getProfiles();
        $profilesResponse['response'] = json_decode($profilesResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

        $currentProfile = NULL;

        foreach($profilesResponse['response'] as $profile) {
            if(strtolower($profile['countryCode']) == $user['country_id']||($profile['countryCode'] == 'UK' && $user['country_id']=='gb') ) {
                $currentProfile = $profile;
            }
        }

        if($currentProfile) {
            $client->profileId = $currentProfile['profileId'];

            $reportsResponse = $client->requestReport('productAds', array(
                'reportDate' => date('Ymd', $reportTime),
                'campaignType' => 'sponsoredProducts',
                'metrics' => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
            ));

            $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

            if($reportsResponse['success']&&($reportsResponse['response']['reportId']!='')) {
                addReport($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'productAds');
            }

            $reportsResponse = $client->requestReport('keywords', array(
                'reportDate' => date('Ymd', $reportTime),
                //'segment' => 'query',
                'campaignType' => 'sponsoredProducts',
                'metrics' => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
            ));

            $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

            if($reportsResponse['success']&&($reportsResponse['response']['reportId']!='')) {
                addReport($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'keywords');
            }

            $reportsResponse = $client->requestReport('keywords', array(
                'reportDate' => date('Ymd', $reportTime),
                'segment' => 'query',
                'campaignType' => 'sponsoredProducts',
                'metrics' => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
            ));

            $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

            if($reportsResponse['success']&&($reportsResponse['response']['reportId']!='')) {
                addReport($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'keywordsQuery');
            }
        }
        else {
            echo "Can't find a profile";
            print_r($profilesResponse);
        }
    }
}    
echo '1';
exec('/usr/bin/php /srv/robots/syncdata.php '.$argv[2].' > /dev/null &');
sleep(3000);
exec('/usr/bin/php /srv/robots/syncdata.php '.$argv[2].' > /dev/null &');
sleep(3000);
echo '2';
exec('/usr/bin/php /srv/robots/lookup.php '.$argv[2]. ' > /dev/null &');
echo '3';
exec('/usr/bin/php /var/www/html/getReports1.php "-30 days" '.$argv[2].' > /dev/null &');
?>