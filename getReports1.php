<?php
$user_id = isset($argv['2']) ? $argv['2'] : '';

echo $user_id;


set_time_limit(0);
ini_set("memory_limit", -1);

//$fh_lock = fopen(__DIR__.'/getReports.lock', 'w');
//if(!($fh_lock && flock($fh_lock, LOCK_EX | LOCK_NB)))
//    exit;

require_once 'functions1.php';
require_once 'AmazonAdvertisingApi/Client.php';
writeLog('GETreports1', 'Start for user: ' . $user_id, $user_id, true);
$fromTime = strtotime(@$_GET['date'] ? $_GET['date'] : $argv['1']);


$tillTime = time();
$regions = json_decode(@file_get_contents(__DIR__ . '/regions.json'), true);

while ($reportData = getReport($user_id)) {
    writeLog('GETreports1', 'Get report ' . $reportData['type'], $user_id);
    $config = array_merge(json_decode(@file_get_contents(__DIR__ . '/config.json'), true), array(
        'refreshToken' => $reportData['refresh_token'],
        'region'       => $reportData['region']
    ));

    $client = new AmazonAdvertisingApi\Client($config);


    $client->profileId = $reportData['profile_id'];

    $profilesResponse = $client->getProfiles();
    $profilesResponse['response'] = json_decode($profilesResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

    $currentProfile = NULL;

    foreach ($profilesResponse['response'] as $profile) {
        if (strtolower($profile['profileId']) == $reportData['profile_id']) {
            $currentProfile = $profile;
        }
    }
    echo $reportData['type'] . "\n";
    switch ($reportData['type']) {
        case 'productAds':
            $attemps = 0;
            do {
                sleep(20);
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);

                var_dump($reportResponse);


                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('GETreports1', 'we got empty report with code 200  ', $user_id);
                }
            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('GETreports1', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report ( \033[0m  \n";
                writeLog('GETreports1', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }

            include 'db.php';

            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {


//var_dump($reportResponse['response']);

                writeLog('GETreports1', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {
                    $data = array(
                        'Start Date'                                    => date('Y-m-d', $reportData['time']),
                        'End Date'                                      => date('Y-m-d', $reportData['time'] + 86400),
                        'Impressions'                                   => $report['impressions'],
                        'Clicks'                                        => $report['clicks'],
                        'CTR'                                           => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                        'Total Spend'                                   => $report['cost'],
                        'Average CPC'                                   => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                        'Currency'                                      => $currentProfile['currencyCode'],
                        '1-day Same SKU Units Ordered'                  => $report['attributedConversions1dSameSKU'],
                        '1-day Other SKU Units Ordered'                 => $report['attributedConversions1d'],
                        '1-day Same SKU Units Ordered Product Sales'    => $report['attributedSales1dSameSKU'],
                        '1-day Other SKU Units Ordered Product Sales'   => $report['attributedSales1d'],
                        '1-day Orders Placed'                           => $report['attributedConversions1d'],
                        '1-day Ordered Product Sales'                   => $report['attributedSales1d'],
                        '1-week Same SKU Units Ordered'                 => $report['attributedConversions7dSameSKU'],
                        '1-week Other SKU Units Ordered'                => $report['attributedConversions7d'],
                        '1-week Same SKU Units Ordered Product Sales'   => $report['attributedSales7dSameSKU'],
                        '1-week Other SKU Units Ordered Product Sales'  => $report['attributedSales7d'],
                        '1-week Orders Placed'                          => $report['attributedConversions7d'],
                        '1-week Ordered Product Sales'                  => $report['attributedSales7d'],
                        '1-month Same SKU Units Ordered'                => $report['attributedConversions30dSameSKU'],
                        '1-month Other SKU Units Ordered'               => $report['attributedConversions30d'],
                        '1-month Same SKU Units Ordered Product Sales'  => $report['attributedSales30dSameSKU'],
                        '1-month Other SKU Units Ordered Product Sales' => $report['attributedSales30d'],
                        '1-month Orders Placed'                         => $report['attributedConversions30d'],
                        '1-month Ordered Product Sales'                 => $report['attributedSales30d'],
                        'user'                                          => $reportData['user_id']
                    );

                    $data['1-day Conversion Rate'] = ($data['1-day Ordered Product Sales'] > 0 ? $data['1-day Ordered Product Sales'] / $data['1-day Ordered Product Sales'] : 0) * 100;
                    $data['1-week Conversion Rate'] = ($data['1-week Ordered Product Sales'] > 0 ? $data['1-week Ordered Product Sales'] / $data['1-week Ordered Product Sales'] : 0) * 100;
                    $data['1-month Conversion Rate'] = ($data['1-month Ordered Product Sales'] > 0 ? $data['1-month Ordered Product Sales'] / $data['1-month Ordered Product Sales'] : 0) * 100;

                    if (($productAdsResponse = getCache('pa' . $report['adId'])) == false) {
                        $productAdsResponse = $client->getProductAd($report['adId']);
                        setCache('pa' . $report['adId'], $productAdsResponse);
                    }

                    if ($productAdsResponse['success']) {
                        $productAdsResponse['response'] = json_decode($productAdsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data['Advertised SKU'] = $productAdsResponse['response']['sku'];

                        if (($campaignResponse = getCache('c' . $productAdsResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($productAdsResponse['response']['campaignId']);
                            setCache('c' . $productAdsResponse['response']['campaignId'], $campaignResponse);
                        }

                        if ($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Campaign Name'] = $campaignResponse['response']['name'];
                            $data['Campaign Id'] = $campaignResponse['response']['campaignId'];
                        }

                        if (($adGroupResponse = getCache('ag' . $productAdsResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($productAdsResponse['response']['adGroupId']);
                            setCache('ag' . $productAdsResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if ($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Ad Group Name'] = $adGroupResponse['response']['name'];
                            $data['Ad Group Id'] = $adGroupResponse['response']['adGroupId'];
                        }
                    }
                    do {
                        $result = $db->prepare('REPLACE INTO `productadsreport2` (`' . implode('`, `', array_keys($data)) . '`) VALUES (?' . str_repeat(', ?', count($data) - 1) . ')')->execute(array_values($data));
                        if (!$result) {
                            echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                            writeLog('GETreports1', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                            echo "\033[31m Sleep ....  \033[0m  \n";
                            sleep(5);
                        } else {
                            echo "\033[32m record inserted \033[0m  \n";
                            writeLog('GETreports1', 'we isert record  ', $user_id);
                        }
                    } while (!$result);
                }
            }
            break;
        case 'keywords':
            $attemps = 0;
            do {
                sleep(20);
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);

                var_dump($reportResponse);

                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('GETreports1', 'we got empty report with code 200  ', $user_id);
                }

            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('GETreports1', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report ( \033[0m  \n";
                writeLog('GETreports1', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }
            include 'db.php';

            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {
//var_dump($reportResponse['response']);
                writeLog('GETreports1', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {

                    $data = array(
                        'Start Date'                                    => date('Y-m-d', $reportData['time']),
                        'End Date'                                      => date('Y-m-d', $reportData['time'] + 86400),
                        'Impressions'                                   => $report['impressions'],
                        'Clicks'                                        => $report['clicks'],
                        'CTR'                                           => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                        'Total Spend'                                   => $report['cost'],
                        'Average CPC'                                   => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                        'Currency'                                      => $currentProfile['currencyCode'],
                        '1-day Same SKU Units Ordered'                  => $report['attributedConversions1dSameSKU'],
                        '1-day Other SKU Units Ordered'                 => $report['attributedConversions1d'],
                        '1-day Same SKU Units Ordered Product Sales'    => $report['attributedSales1dSameSKU'],
                        '1-day Other SKU Units Ordered Product Sales'   => $report['attributedSales1d'],
                        '1-day Orders Placed'                           => $report['attributedConversions1d'],
                        '1-day Ordered Product Sales'                   => $report['attributedSales1d'],
                        '1-week Same SKU Units Ordered'                 => $report['attributedConversions7dSameSKU'],
                        '1-week Other SKU Units Ordered'                => $report['attributedConversions7d'],
                        '1-week Same SKU Units Ordered Product Sales'   => $report['attributedSales7dSameSKU'],
                        '1-week Other SKU Units Ordered Product Sales'  => $report['attributedSales7d'],
                        '1-week Orders Placed'                          => $report['attributedConversions7d'],
                        '1-week Ordered Product Sales'                  => $report['attributedSales7d'],
                        '1-month Same SKU Units Ordered'                => $report['attributedConversions30dSameSKU'],
                        '1-month Other SKU Units Ordered'               => $report['attributedConversions30d'],
                        '1-month Same SKU Units Ordered Product Sales'  => $report['attributedSales30dSameSKU'],
                        '1-month Other SKU Units Ordered Product Sales' => $report['attributedSales30d'],
                        '1-month Orders Placed'                         => $report['attributedConversions30d'],
                        '1-month Ordered Product Sales'                 => $report['attributedSales30d'],
                        'user'                                          => $reportData['user_id']
                    );

                    $data['1-day Conversion Rate'] = ($data['1-day Ordered Product Sales'] > 0 ? $data['1-day Ordered Product Sales'] / $data['1-day Ordered Product Sales'] : 0) * 100;
                    $data['1-week Conversion Rate'] = ($data['1-week Ordered Product Sales'] > 0 ? $data['1-week Ordered Product Sales'] / $data['1-week Ordered Product Sales'] : 0) * 100;
                    $data['1-month Conversion Rate'] = ($data['1-month Ordered Product Sales'] > 0 ? $data['1-month Ordered Product Sales'] / $data['1-month Ordered Product Sales'] : 0) * 100;

                    if (($keywordResponse = getCache('k' . $report['keywordId'])) == false) {
                        $keywordResponse = $client->getBiddableKeyword($report['keywordId']);
                        setCache('k' . $report['keywordId'], $keywordResponse);
                    }

                    if ($keywordResponse['success']) {
                        $keywordResponse['response'] = json_decode($keywordResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data['Keyword'] = $keywordResponse['response']['keywordText'];
                        $data['Match Type'] = $keywordResponse['response']['matchType'];

                        if (($campaignResponse = getCache('c' . $keywordResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($keywordResponse['response']['campaignId']);
                            setCache('c' . $keywordResponse['response']['campaignId'], $campaignResponse);
                        }

                        if ($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Campaign Name'] = $campaignResponse['response']['name'];
                            $data['Campaign Id'] = $campaignResponse['response']['campaignId'];
                        }

                        if (($adGroupResponse = getCache('ag' . $keywordResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($keywordResponse['response']['adGroupId']);
                            setCache('ag' . $keywordResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if ($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Ad Group Name'] = $adGroupResponse['response']['name'];
                            $data['Ad Group Id'] = $adGroupResponse['response']['adGroupId'];
                        }
                    }
                    do {
                        $result = $db->prepare('REPLACE INTO `keywordsreport2` (`' . implode('`, `', array_keys($data)) . '`) VALUES (?' . str_repeat(', ?', count($data) - 1) . ')')->execute(array_values($data));
                        if (!$result) {
                            echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                            writeLog('GETreports1', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                            echo "\033[31m Sleep ....  \033[0m  \n";
                            sleep(5);
                        } else {
                            echo "\033[32m record inserted \033[0m  \n";
                            writeLog('GETreports1', 'we isert record  ', $user_id);
                        }
                    } while (!$result);
                }
            }
            break;
        case 'keywordsQuery':

            $attemps = 0;
            do {
                sleep(20);

                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);

                var_dump($reportResponse);


                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('GETreports1', 'we got empty report with code 200  ', $user_id);
                }
            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('GETreports1', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report ( \033[0m  \n";
                writeLog('GETreports1', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }
            include 'db.php';

            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {
//var_dump($reportResponse['response']);
                writeLog('GETreports1', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {

                    $data = array(
                        'Customer Search Term'                                 => $report['query'],
                        'First Day of Impression'                              => date('Y-m-d', $reportData['time']),
                        'Last Day of Impression'                               => date('Y-m-d', $reportData['time'] + 86400),
                        'Impressions'                                          => $report['impressions'],
                        'Clicks'                                               => $report['clicks'],
                        'CTR'                                                  => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                        'Total Spend'                                          => $report['cost'],
                        'Average CPC'                                          => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                        'Currency'                                             => $currentProfile['currencyCode'],
                        'Same SKU units Ordered within 1-week of click'        => $report['attributedConversions7dSameSKU'],
                        'Other SKU units Ordered within 1-week of click'       => $report['attributedConversions7d'],
                        'Same SKU units Product Sales within 1-week of click'  => $report['attributedSales7dSameSKU'],
                        'Other SKU units Product Sales within 1-week of click' => $report['attributedSales7d'],
                        'Orders placed within 1-week of a click'               => $report['attributedConversions7d'],
                        'Product Sales within 1-week of a click'               => $report['attributedSales7d'],
                        'user'                                                 => $reportData['user_id']
                    );

                    $data['ACoS'] = ($data['Product Sales within 1-week of a click'] > 0 ? $data['Total Spend'] / $data['Product Sales within 1-week of a click'] : 0) * 100;
                    $data['Conversion Rate within 1-week of a click'] = ($data['Product Sales within 1-week of a click'] > 0 ? $data['Orders placed within 1-week of a click'] / $data['Product Sales within 1-week of a click'] : 0) * 100;

                    if (($keywordResponse = getCache('k' . $report['keywordId'])) == false) {
                        $keywordResponse = $client->getBiddableKeyword($report['keywordId']);
                        setCache('k' . $report['keywordId'], $keywordResponse);
                    }

                    if ($keywordResponse['success']) {
                        $keywordResponse['response'] = json_decode($keywordResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data['Keyword'] = $keywordResponse['response']['keywordText'];
                        $data['Match Type'] = $keywordResponse['response']['matchType'];

                        if (($campaignResponse = getCache('c' . $keywordResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($keywordResponse['response']['campaignId']);
                            setCache('c' . $keywordResponse['response']['campaignId'], $campaignResponse);
                        }

                        if ($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Campaign Name'] = $campaignResponse['response']['name'];
                        }

                        if (($adGroupResponse = getCache('ag' . $keywordResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($keywordResponse['response']['adGroupId']);
                            setCache('ag' . $keywordResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if ($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Ad Group Name'] = $adGroupResponse['response']['name'];
                        }
                    }
                    do {
                        $result = $db->prepare('REPLACE INTO `searchtermreport2` (`' . implode('`, `', array_keys($data)) . '`) VALUES (?' . str_repeat(', ?', count($data) - 1) . ')')->execute(array_values($data));
                        if (!$result) {
                            echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                            writeLog('GETreports1', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                            echo "\033[31m Sleep ....  \033[0m  \n";
                            sleep(5);
                        } else {
                            echo "\033[32m record inserted \033[0m  \n";
                            writeLog('GETreports1', 'we isert record  ', $user_id);
                        }
                    } while (!$result);
                }
            }

            break;
    }
}

include 'db.php';

$usersResult = $db->query('SELECT * from mws where user=' . $user_id);

while ($user1 = $usersResult->fetch(PDO::FETCH_ASSOC)) {
    writeLog('GETreports1', 'firsttime for user   ' . $user1['firsttime'], $user_id);
    if ($user1['firsttime'] == 1) $usersResult1 = $db->query('update mws set firsttime=2 where user=' . $user_id);

}
writeLog('GETreports1', ' --FINISH--  ', $user_id);
echo '666661321321321312321321321';
