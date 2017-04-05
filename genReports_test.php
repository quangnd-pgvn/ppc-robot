<?php
set_time_limit(0);
ini_set('memory_limit', -1);

require_once 'functions1.php';
require_once 'AmazonAdvertisingApi/Client.php';

$fromTime = strtotime(@$_GET['date'] ? $_GET['date'] : $argv['1']);
if (isset($argv['2'])) {
    $user_id = $argv['2'];
    $user_id1 = ' and user=' . $argv['2'];
} else {
    $user_id = '';
    $user_id1 = '';
}

$tillTime = time();
$regions = json_decode(@file_get_contents(__DIR__ . '/regions.json'), true);

include 'db.php';

$usersResult = $db->query('SELECT * FROM `mws` WHERE `code` IS NOT NULL' . $user_id1);
$baseConfig = json_decode(@file_get_contents(__DIR__ . '/config.json'), true);

writeLog('GENreports1', 'Start for time : ' . $fromTime . ' and user: ' . $user_id , $user_id , true);
while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {
    writeLog('GENreports1', 'Work with user: ' . $user['user'], $user_id);
    switch ($user['country_id']) {
        case 'us':
        case 'ca':
        case 'mx':
            $config = array_merge($baseConfig, array(
                'refreshToken' => $user['code'],
                'region' => 'na'
            ));
            break;
        case 'gb':
        case 'de':
        case 'fr':
        case 'it':
        case 'es':
        case 'in':
            $config = array_merge($baseConfig, array(
                'refreshToken' => $user['code'],
                'region' => 'eu'
            ));
            break;
    }

    $client = new AmazonAdvertisingApi\Client($config);

    $profilesResponse = $client->getProfiles();
    $profilesResponse['response'] = json_decode($profilesResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
    var_dump($profilesResponse['response']);

    $currentProfile = NULL;
    foreach ($profilesResponse['response'] as $profile) {
        if (strtolower($profile['countryCode']) == $user['country_id'] ||
            ($profile['countryCode'] == 'UK' && $user['country_id'] == 'gb')) {
            if ($profile["accountInfo"]["sellerStringId"] == $user['SellerID']) {
                $currentProfile = $profile;
            }
        }
    }

    if (!$currentProfile) {
        echo "Can't find a profile";
        writeLog('GENreports1', 'Can\'t find a profile ', $user_id);
        continue;
    }

    $client->profileId = $currentProfile['profileId'];

    /*for($reportTime = $fromTime; $reportTime <= $tillTime; $reportTime = $reportTime + 86400) {
        writeLog ('GENreports1', 'Work with time : '.date("Y-m-d",$reportTime) , $user_id );
        sleep(3);

        $groups_cached = array();
        $keywords_cached = array();
        $products_cached = array();
        $campaigns_cached = array();

        $reportDate = date('Ymd', $reportTime);

        $reportsResponse = $client->requestReport('productAds', array(
            'reportDate' => $reportDate,
            'campaignType' => 'sponsoredProducts',
            'metrics' => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
        ));

        $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
        print_r($reportsResponse);

        if ($reportsResponse['success'] && ($reportsResponse['response']['reportId'] != '')) {
            addReport($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'productAds');
            writeLog('GENreports1', 'Add report  productAds', $user_id);
        }

        $reportsResponse = $client->requestReport('keywords', array(
            'reportDate' => $reportDate,
            'campaignType' => 'sponsoredProducts',
            'metrics' => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
        ));
        $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
        print_r($reportsResponse);

        if ($reportsResponse['success'] && ($reportsResponse['response']['reportId'] != '')) {
            addReport($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'keywords');
            writeLog('GENreports1', 'Add report  keywords', $user_id);
        }

        $reportsResponse = $client->requestReport('keywords', array(
            'reportDate' => $reportDate,
            'segment' => 'query',
            'campaignType' => 'sponsoredProducts',
            'metrics' => 'impressions,clicks,cost,avgImpressionPosition,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d,attributedConversions7dSameSKU,attributedConversions7d,attributedSales7dSameSKU,attributedSales7d,attributedConversions30dSameSKU,attributedConversions30d,attributedSales30dSameSKU,attributedSales30d'
        ));
        $reportsResponse['response'] = json_decode($reportsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
        print_r($reportsResponse);

        if ($reportsResponse['success'] && ($reportsResponse['response']['reportId'] != '')) {
            addReport($reportsResponse, $client->profileId, $user['user'], $regions[$user['country_id']], $user['code'], $reportTime, 'keywordsQuery');
            writeLog('GENreports1', 'Add report  keywordsQuery' , $user_id);
        }
    }*/
}

echo 'step 1';
writeLog('GENreports1', 'First stage complete. Run sync data', $user_id);
exec('/usr/bin/php /srv/robots/syncdat.php ' . $argv[2]);
/*writeLog('GENreports1', 'First sync data complete', $user_id);
sleep(3000);
exec('/usr/bin/php /srv/robots/syncdat.php ' . $argv[2] . ' > /dev/null &');
writeLog('GENreports1', 'Second sync data complete', $user_id);
sleep(3000);

echo 'step 2';
exec('/usr/bin/php /srv/robots/lookup.php ' . $argv[2] . ' > /dev/null &');
writeLog('GENreports1', 'Loockup complete', $user_id);

echo 'step 3';
exec('/usr/bin/php /var/www/html/getReports1.php "-60 days" ' . $argv[2] . ' > /dev/null &');
writeLog('GENreports1', '--FINISH-- ' , $user_id);
//send email
exec('/usr/bin/php /var/www/html/sendemailfinish.php ' . $argv[2] . ' > /dev/null &');
writeLog('SendEmail', '--FINISH-- ' , $user_id);
*/
?>

