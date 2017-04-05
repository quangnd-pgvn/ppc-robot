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
writeLog('GETreportsPrepared', 'Start for user: ' . $user_id, $user_id, true);
$fromTime = strtotime(@$_GET['date'] ? $_GET['date'] : $argv['1']);


$tillTime = time();
$regions = json_decode(@file_get_contents(__DIR__ . '/regions.json'), true);
include 'db.php';
while ($reportData = getReport($user_id)) {

    $productAdsPrepared = $db->prepare('REPLACE INTO `productadsreport2` (`Campaign Name`, `Campaign Id`, `Ad Group Name`, `Ad Group Id`, `Advertised SKU`,  `Start Date`, `End Date`, `Clicks`, `Impressions`, `CTR`, `Total Spend`, `Average CPC`, `Currency`, `1-day Orders Placed`, `1-day Ordered Product Sales`, `1-day Conversion Rate`, `1-day Same SKU Units Ordered`, `1-day Other SKU Units Ordered`, `1-day Same SKU Units Ordered Product Sales`, `1-day Other SKU Units Ordered Product Sales`, `1-week Orders Placed`, `1-week Ordered Product Sales`, `1-week Conversion Rate`, `1-week Same SKU Units Ordered`, `1-week Other SKU Units Ordered`, `1-week Same SKU Units Ordered Product Sales`, `1-week Other SKU Units Ordered Product Sales`, `1-month Orders Placed`, `1-month Ordered Product Sales`, `1-month Conversion Rate`, `1-month Same SKU Units Ordered`, `1-month Other SKU Units Ordered`, `1-month Same SKU Units Ordered Product Sales`, `1-month Other SKU Units Ordered Product Sales`, `updatedAt`,  `user`) VALUES (:CampaignName, :CampaignId, :AdGroupName, :AdGroupId, :AdvertisedSKU, :StartDate, :EndDate, :Clicks, :Impressions, :CTR, :TotalSpend, :AverageCPC, :Currency, :1dayOrdersPlaced, :1dayOrderedProductSales, :1dayConversionRate, :1daySameSKUUnitsOrdered, :1dayOtherSKUUnitsOrdered, :1daySameSKUUnitsOrderedProductSales, :1dayOtherSKUUnitsOrderedProductSales, :1weekOrdersPlaced, :1weekOrderedProductSales, :1weekConversionRate, :1weekSameSKUUnitsOrdered, :1weekOtherSKUUnitsOrdered, :1weekSameSKUUnitsOrderedProductSales, :1weekOtherSKUUnitsOrderedProductSales, :1monthOrdersPlaced, :1monthOrderedProductSales, :1monthConversionRate, :1monthSameSKUUnitsOrdered, :1monthOtherSKUUnitsOrdered, :1monthSameSKUUnitsOrderedProductSales, :1monthOtherSKUUnitsOrderedProductSales,  :updatedAt,  :user)');
    //WHY fields keyword and matchType ??

    $keywordsPrepared = $db->prepare('REPLACE INTO `keywordsreport2` (`Campaign Name`, `Campaign Id`, `Ad Group Name`, `Ad Group Id`,  `Keyword`, `Match Type`, `Start Date`, `End Date`, `Clicks`, `Impressions`, `CTR`, `Total Spend`, `Average CPC`, `Currency`, `1-day Orders Placed`, `1-day Ordered Product Sales`, `1-day Conversion Rate`, `1-day Same SKU Units Ordered`, `1-day Other SKU Units Ordered`, `1-day Same SKU Units Ordered Product Sales`, `1-day Other SKU Units Ordered Product Sales`, `1-week Orders Placed`, `1-week Ordered Product Sales`, `1-week Conversion Rate`, `1-week Same SKU Units Ordered`, `1-week Other SKU Units Ordered`, `1-week Same SKU Units Ordered Product Sales`, `1-week Other SKU Units Ordered Product Sales`, `1-month Orders Placed`, `1-month Ordered Product Sales`, `1-month Conversion Rate`, `1-month Same SKU Units Ordered`, `1-month Other SKU Units Ordered`, `1-month Same SKU Units Ordered Product Sales`, `1-month Other SKU Units Ordered Product Sales`, `updatedAt`,  `user`) VALUES (:CampaignName, :CampaignId, :AdGroupName, :AdGroupId, :Keyword, :MatchType, :StartDate, :EndDate, :Clicks, :Impressions, :CTR, :TotalSpend, :AverageCPC, :Currency, :1dayOrdersPlaced, :1dayOrderedProductSales, :1dayConversionRate, :1daySameSKUUnitsOrdered, :1dayOtherSKUUnitsOrdered, :1daySameSKUUnitsOrderedProductSales, :1dayOtherSKUUnitsOrderedProductSales, :1weekOrdersPlaced, :1weekOrderedProductSales, :1weekConversionRate, :1weekSameSKUUnitsOrdered, :1weekOtherSKUUnitsOrdered, :1weekSameSKUUnitsOrderedProductSales, :1weekOtherSKUUnitsOrderedProductSales, :1monthOrdersPlaced, :1monthOrderedProductSales, :1monthConversionRate, :1monthSameSKUUnitsOrdered, :1monthOtherSKUUnitsOrdered, :1monthSameSKUUnitsOrderedProductSales, :1monthOtherSKUUnitsOrderedProductSales,  :updatedAt,  :user)');
    // where adveristed SKU ?

    $serchTermPrepared = $db->prepare('REPLACE INTO `searchtermreport2` ( `Campaign Name`, `Ad Group Name`, `Customer Search Term`, `Keyword`, `Match Type`, `First Day of Impression`, `Last Day of Impression`, `Impressions`, `Clicks`, `CTR`, `Total Spend`, `Average CPC`, `ACoS`, `Currency`, `Orders placed within 1-week of a click`, `Product Sales within 1-week of a click`, `Conversion Rate within 1-week of a click`, `Same SKU units Ordered within 1-week of click`, `Other SKU units Ordered within 1-week of click`, `user`) VALUES ( :CampaignName, :AdGroupName, :CustomerSearchTerm, :Keyword, :MatchType, :FirstDayofImpression, :LastDayofImpression, :Impressions, :Clicks, :CTR, :TotalSpend, :AverageCPC, :ACoS, :Currency, :Ordersplacedwithin1weekofaclick, :ProductSaleswithin1weekofaclick, :ConversionRatewithin1weekofaclick, :SameSKUunitsOrderedwithin1weekofclick, :OtherSKUunitsOrderedwithin1weekofclick,  :user)');
    //':SameSKUunitsProductSaleswithin1weekofclick'=> , ':OtherSKUunitsProductSaleswithin1weekofclick'=> ,  WHERE THIS DATA ?

    writeLog('GETreportsPrepared', 'Get report ' . $reportData['type'], $user_id);
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
                if ($attemps > 0) { //no time to sleep at first run
                    sleep(20);
                }
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);
                var_dump($reportResponse);
                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('GETreportsPrepared', 'we got empty report with code 200  ', $user_id);
                }
            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('GETreportsPrepared', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report ( \033[0m  \n";
                writeLog('GETreportsPrepared', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }
            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {

//var_dump($reportResponse['response']);

                writeLog('GETreportsPrepared', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {
                    /* $data = array(
                         'Start Date' => date('Y-m-d', $reportData['time']),
                         'End Date' => date('Y-m-d', $reportData['time'] + 86400),
                         'Impressions' => $report['impressions'],
                         'Clicks' => $report['clicks'],
                         'CTR' => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                         'Total Spend' => $report['cost'],
                         'Average CPC' => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                         'Currency' => $currentProfile['currencyCode'],
                         '1-day Same SKU Units Ordered' => $report['attributedConversions1dSameSKU'],
                         '1-day Other SKU Units Ordered' => $report['attributedConversions1d'],
                         '1-day Same SKU Units Ordered Product Sales' => $report['attributedSales1dSameSKU'],
                         '1-day Other SKU Units Ordered Product Sales' => $report['attributedSales1d'],
                         '1-day Orders Placed' => $report['attributedConversions1d'],
                         '1-day Ordered Product Sales' => $report['attributedSales1d'],
                         '1-week Same SKU Units Ordered' => $report['attributedConversions7dSameSKU'],
                         '1-week Other SKU Units Ordered' => $report['attributedConversions7d'],
                         '1-week Same SKU Units Ordered Product Sales' => $report['attributedSales7dSameSKU'],
                         '1-week Other SKU Units Ordered Product Sales' => $report['attributedSales7d'],
                         '1-week Orders Placed' => $report['attributedConversions7d'],
                         '1-week Ordered Product Sales' => $report['attributedSales7d'],
                         '1-month Same SKU Units Ordered' => $report['attributedConversions30dSameSKU'],
                         '1-month Other SKU Units Ordered' => $report['attributedConversions30d'],
                         '1-month Same SKU Units Ordered Product Sales' => $report['attributedSales30dSameSKU'],
                         '1-month Other SKU Units Ordered Product Sales' => $report['attributedSales30d'],
                         '1-month Orders Placed' => $report['attributedConversions30d'],
                         '1-month Ordered Product Sales' => $report['attributedSales30d'],
                         'user' => $reportData['user_id']
                     );*/
                    $data = [
                        ':CampaignName'                           => '',
                        ':CampaignId'                             => '',
                        ':AdGroupName'                            => '',
                        ':AdGroupId'                              => '',
                        ':AdvertisedSKU'                          => '',
                        ':StartDate'                              => date('Y-m-d', $reportData['time']),
                        ':EndDate'                                => date('Y-m-d', $reportData['time'] + 86400),
                        ':Clicks'                                 => $report['clicks'],
                        ':Impressions'                            => $report['impressions'],
                        ':CTR'                                    => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                        ':TotalSpend'                             => $report['cost'],
                        ':AverageCPC'                             => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                        ':Currency'                               => $currentProfile['currencyCode'],
                        ':1dayOrdersPlaced'                       => $report['attributedConversions1d'],
                        ':1dayOrderedProductSales'                => $report['attributedSales1d'],
                        ':1daySameSKUUnitsOrdered'                => $report['attributedConversions1dSameSKU'],
                        ':1dayOtherSKUUnitsOrdered'               => $report['attributedConversions1d'],
                        ':1daySameSKUUnitsOrderedProductSales'    => $report['attributedSales1dSameSKU'],
                        ':1dayOtherSKUUnitsOrderedProductSales'   => $report['attributedSales1d'],
                        ':1weekOrdersPlaced'                      => $report['attributedConversions7d'],
                        ':1weekOrderedProductSales'               => $report['attributedSales7d'],
                        ':1weekSameSKUUnitsOrdered'               => $report['attributedConversions7dSameSKU'],
                        ':1weekOtherSKUUnitsOrdered'              => $report['attributedConversions7d'],
                        ':1weekSameSKUUnitsOrderedProductSales'   => $report['attributedSales7dSameSKU'],
                        ':1weekOtherSKUUnitsOrderedProductSales'  => $report['attributedSales7d'],
                        ':1monthOrdersPlaced'                     => $report['attributedConversions30d'],
                        ':1monthOrderedProductSales'              => $report['attributedSales30d'],
                        ':1monthSameSKUUnitsOrdered'              => $report['attributedConversions30dSameSKU'],
                        ':1monthOtherSKUUnitsOrdered'             => $report['attributedConversions30d'],
                        ':1monthSameSKUUnitsOrderedProductSales'  => $report['attributedSales30dSameSKU'],
                        ':1monthOtherSKUUnitsOrderedProductSales' => $report['attributedSales30d'],
                        ':updatedAt'                              => date('Y-m-d H:i:s'),
                        ':user'                                   => $reportData['user_id']
                    ];

                    /*        $data['1-day Conversion Rate'] = ($data['1-day Ordered Product Sales'] > 0 ? $data['1-day Ordered Product Sales'] / $data['1-day Ordered Product Sales'] : 0) * 100;
                            $data['1-week Conversion Rate'] = ($data['1-week Ordered Product Sales'] > 0 ? $data['1-week Ordered Product Sales'] / $data['1-week Ordered Product Sales'] : 0) * 100;
                            $data['1-month Conversion Rate'] = ($data['1-month Ordered Product Sales'] > 0 ? $data['1-month Ordered Product Sales'] / $data['1-month Ordered Product Sales'] : 0) * 100;*/
                    $data[':1dayConversionRate'] = ($data[':1dayOrderedProductSales'] > 0 ? $data[':1dayOrderedProductSales'] / $data[':1dayOrderedProductSales'] : 0) * 100;
                    $data[':1weekConversionRate'] = ($data[':1weekOrderedProductSales'] > 0 ? $data[':1weekOrderedProductSales'] / $data[':1weekOrderedProductSales'] : 0) * 100;
                    $data[':1monthConversionRate'] = ($data[':1monthOrderedProductSales'] > 0 ? $data[':1monthOrderedProductSales'] / $data[':1monthOrderedProductSales'] : 0) * 100;


                    if (($productAdsResponse = getCache('pa' . $report['adId'])) == false) {
                        $productAdsResponse = $client->getProductAd($report['adId']);
                        setCache('pa' . $report['adId'], $productAdsResponse);
                    }

                    if ($productAdsResponse['success']) {
                        $productAdsResponse['response'] = json_decode($productAdsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data['AdvertisedSKU'] = $productAdsResponse['response']['sku'];

                        if (($campaignResponse = getCache('c' . $productAdsResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($productAdsResponse['response']['campaignId']);
                            setCache('c' . $productAdsResponse['response']['campaignId'], $campaignResponse);
                        }

                        if ($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['CampaignName'] = $campaignResponse['response']['name'];
                            $data['CampaignId'] = $campaignResponse['response']['campaignId'];
                        }

                        if (($adGroupResponse = getCache('ag' . $productAdsResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($productAdsResponse['response']['adGroupId']);
                            setCache('ag' . $productAdsResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if ($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['AdGroupName'] = $adGroupResponse['response']['name'];
                            $data['AdGroupId'] = $adGroupResponse['response']['adGroupId'];
                        }
                    }

                    //       $result = $db->prepare('REPLACE INTO `productadsreport2` (`'.implode('`, `', array_keys($data)).'`) VALUES (?'.str_repeat(', ?', count($data) - 1).')')->execute(array_values($data));
                    $result = $productAdsPrepared->execute($data);

                    if (!$result) {
                        echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                        writeLog('GETreportsPrepared', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                    } else {
                        echo "\033[32m record inserted \033[0m  \n";
                        writeLog('GETreportsPrepared', 'we isert record  ', $user_id);
                    }
                }
            }
            break;

        case 'keywords':
            $attemps = 0;
            do {
                if ($attemps > 0) { //no time to sleep at first run
                    sleep(20);
                }
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);

                var_dump($reportResponse);

                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('GETreportsPrepared', 'we got empty report with code 200  ', $user_id);
                }

            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('GETreportsPrepared', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report ( \033[0m  \n";
                writeLog('GETreportsPrepared', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }


            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {
//var_dump($reportResponse['response']);
                writeLog('GETreportsPrepared', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {

                    /*  $data = array(
                          'Start Date' => date('Y-m-d', $reportData['time']),
                          'End Date' => date('Y-m-d', $reportData['time'] + 86400),
                          'Impressions' => $report['impressions'],
                          'Clicks' => $report['clicks'],
                          'CTR' => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                          'Total Spend' => $report['cost'],
                          'Average CPC' => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                          'Currency' => $currentProfile['currencyCode'],
                          '1-day Same SKU Units Ordered' => $report['attributedConversions1dSameSKU'],
                          '1-day Other SKU Units Ordered' => $report['attributedConversions1d'],
                          '1-day Same SKU Units Ordered Product Sales' => $report['attributedSales1dSameSKU'],
                          '1-day Other SKU Units Ordered Product Sales' => $report['attributedSales1d'],
                          '1-day Orders Placed' => $report['attributedConversions1d'],
                          '1-day Ordered Product Sales' => $report['attributedSales1d'],
                          '1-week Same SKU Units Ordered' => $report['attributedConversions7dSameSKU'],
                          '1-week Other SKU Units Ordered' => $report['attributedConversions7d'],
                          '1-week Same SKU Units Ordered Product Sales' => $report['attributedSales7dSameSKU'],
                          '1-week Other SKU Units Ordered Product Sales' => $report['attributedSales7d'],
                          '1-week Orders Placed' => $report['attributedConversions7d'],
                          '1-week Ordered Product Sales' => $report['attributedSales7d'],
                          '1-month Same SKU Units Ordered' => $report['attributedConversions30dSameSKU'],
                          '1-month Other SKU Units Ordered' => $report['attributedConversions30d'],
                          '1-month Same SKU Units Ordered Product Sales' => $report['attributedSales30dSameSKU'],
                          '1-month Other SKU Units Ordered Product Sales' => $report['attributedSales30d'],
                          '1-month Orders Placed' => $report['attributedConversions30d'],
                          '1-month Ordered Product Sales' => $report['attributedSales30d'],
                          'user' => $reportData['user_id']
                      );*/
                    $data = [
                        ':CampaignName'                           => '',
                        ':CampaignId'                             => '',
                        ':AdGroupName'                            => '',
                        ':AdGroupId'                              => '',
                        ':Keyword'                                => '',
                        ':MatchType'                              => '',
                        ':StartDate'                              => date('Y-m-d', $reportData['time']),
                        ':EndDate'                                => date('Y-m-d', $reportData['time'] + 86400),
                        ':Clicks'                                 => $report['clicks'],
                        ':Impressions'                            => $report['impressions'],
                        ':CTR'                                    => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                        ':TotalSpend'                             => $report['cost'],
                        ':AverageCPC'                             => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                        ':Currency'                               => $currentProfile['currencyCode'],
                        ':1dayOrdersPlaced'                       => $report['attributedConversions1d'],
                        ':1dayOrderedProductSales'                => $report['attributedSales1d'],
                        ':1dayConversionRate'                     => '',
                        ':1daySameSKUUnitsOrdered'                => $report['attributedConversions1dSameSKU'],
                        ':1dayOtherSKUUnitsOrdered'               => $report['attributedConversions1d'],
                        ':1daySameSKUUnitsOrderedProductSales'    => $report['attributedSales1dSameSKU'],
                        ':1dayOtherSKUUnitsOrderedProductSales'   => $report['attributedSales1d'],
                        ':1weekOrdersPlaced'                      => $report['attributedConversions7d'],
                        ':1weekOrderedProductSales'               => $report['attributedSales7d'],
                        ':1weekConversionRate'                    => '',
                        ':1weekSameSKUUnitsOrdered'               => $report['attributedConversions7dSameSKU'],
                        ':1weekOtherSKUUnitsOrdered'              => $report['attributedConversions7d'],
                        ':1weekSameSKUUnitsOrderedProductSales'   => $report['attributedSales7dSameSKU'],
                        ':1weekOtherSKUUnitsOrderedProductSales'  => $report['attributedSales7d'],
                        ':1monthOrdersPlaced'                     => $report['attributedConversions30d'],
                        ':1monthOrderedProductSales'              => $report['attributedSales30d'],
                        ':1monthConversionRate'                   => '',
                        ':1monthSameSKUUnitsOrdered'              => $report['attributedConversions30dSameSKU'],
                        ':1monthOtherSKUUnitsOrdered'             => $report['attributedConversions30d'],
                        ':1monthSameSKUUnitsOrderedProductSales'  => $report['attributedSales30dSameSKU'],
                        ':1monthOtherSKUUnitsOrderedProductSales' => $report['attributedSales30d'],
                        ':updatedAt'                              => date('Y-m-d H:i:s'),
                        ':user'                                   => $reportData['user_id']

                    ];


                    $data[':1dayConversionRate'] = ($data[':1dayOrderedProductSales'] > 0 ? $data[':1dayOrderedProductSales'] / $data[':1dayOrderedProductSales'] : 0) * 100;
                    $data[':1weekConversionRate'] = ($data[':1weekOrderedProductSales'] > 0 ? $data[':1weekOrderedProductSales'] / $data[':1weekOrderedProductSales'] : 0) * 100;
                    $data[':1monthConversionRate'] = ($data[':1monthOrderedProductSales'] > 0 ? $data[':1monthOrderedProductSales'] / $data[':1monthOrderedProductSales'] : 0) * 100;

                    if (($keywordResponse = getCache('k' . $report['keywordId'])) == false) {
                        $keywordResponse = $client->getBiddableKeyword($report['keywordId']);
                        setCache('k' . $report['keywordId'], $keywordResponse);
                    }

                    if ($keywordResponse['success']) {
                        $keywordResponse['response'] = json_decode($keywordResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data[':Keyword'] = $keywordResponse['response']['keywordText'];
                        $data[':MatchType'] = $keywordResponse['response']['matchType'];

                        if (($campaignResponse = getCache('c' . $keywordResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($keywordResponse['response']['campaignId']);
                            setCache('c' . $keywordResponse['response']['campaignId'], $campaignResponse);
                        }

                        if ($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data[':CampaignName'] = $campaignResponse['response']['name'];
                            $data[':CampaignId'] = $campaignResponse['response']['campaignId'];
                        }

                        if (($adGroupResponse = getCache('ag' . $keywordResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($keywordResponse['response']['adGroupId']);
                            setCache('ag' . $keywordResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if ($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data[':AdGroupName'] = $adGroupResponse['response']['name'];
                            $data[':AdGroupId'] = $adGroupResponse['response']['adGroupId'];
                        }
                    }

                    //       $result=     $db->prepare('REPLACE INTO `keywordsreport2` (`'.implode('`, `', array_keys($data)).'`) VALUES (?'.str_repeat(', ?', count($data) - 1).')')->execute(array_values($data));
                    $result = $keywordsPrepared->execute($data);
                    if (!$result) {
                        echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                        writeLog('GETreportsPrepared', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                    } else {
                        echo "\033[32m record inserted \033[0m  \n";
                        writeLog('GETreportsPrepared', 'we isert record  ', $user_id);
                    }
                }
            }
            break;
        case 'keywordsQuery':

            $attemps = 0;

            do {
                if ($attemps > 0) { //no time to sleep at first run
                    sleep(20);
                }

                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);

                var_dump($reportResponse);


                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('GETreportsPrepared', 'we got empty report with code 200  ', $user_id);
                }
            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('GETreportsPrepared', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report ( \033[0m  \n";
                writeLog('GETreportsPrepared', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }


            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {
//var_dump($reportResponse['response']);
                writeLog('GETreportsPrepared', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {

                    /*      $data = array(
                              'Customer Search Term' => $report['query'],
                              'First Day of Impression' => date('Y-m-d', $reportData['time']),
                              'Last Day of Impression' => date('Y-m-d', $reportData['time'] + 86400),
                              'Impressions' => $report['impressions'],
                              'Clicks' => $report['clicks'],
                              'CTR' => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                              'Total Spend' => $report['cost'],
                              'Average CPC' => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                              'Currency' => $currentProfile['currencyCode'],
                              'Same SKU units Ordered within 1-week of click' => $report['attributedConversions7dSameSKU'],
                              'Other SKU units Ordered within 1-week of click' => $report['attributedConversions7d'],
                              'Same SKU units Product Sales within 1-week of click' => $report['attributedSales7dSameSKU'],
                              'Other SKU units Product Sales within 1-week of click' => $report['attributedSales7d'],
                              'Orders placed within 1-week of a click' => $report['attributedConversions7d'],
                              'Product Sales within 1-week of a click' => $report['attributedSales7d'],
                              'user' => $reportData['user_id']
                          );*/
                    $data = [
                        ':CampaignName'                           => '',
                        ':AdGroupName'                            => '',
                        ':CustomerSearchTerm'                     => $report['query'],
                        ':Keyword'                                => '',
                        ':MatchType'                              => '',
                        ':FirstDayofImpression'                   => date('Y-m-d', $reportData['time']),
                        ':LastDayofImpression'                    => date('Y-m-d', $reportData['time'] + 86400),
                        ':Impressions'                            => $report['impressions'],
                        ':Clicks'                                 => $report['clicks'],
                        ':CTR'                                    => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                        ':TotalSpend'                             => $report['cost'],
                        ':AverageCPC'                             => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                        ':ACoS'                                   => '',
                        ':Currency'                               => $currentProfile['currencyCode'],
                        ':Ordersplacedwithin1weekofaclick'        => $report['attributedConversions7d'],
                        ':ProductSaleswithin1weekofaclick'        => $report['attributedSales7d'],
                        ':ConversionRatewithin1weekofaclick'      => '',
                        ':SameSKUunitsOrderedwithin1weekofclick'  => $report['attributedConversions7dSameSKU'],
                        ':OtherSKUunitsOrderedwithin1weekofclick' => $report['attributedConversions7d'],
                        ':user'                                   => $reportData['user_id']

                    ];


                    $data[':ACoS'] = ($data[':ProductSaleswithin1weekofaclick'] > 0 ? $data[':TotalSpend'] / $data[':ProductSaleswithin1weekofaclick'] : 0) * 100;
                    $data[':ConversionRatewithin1weekofaclick'] = ($data[':ProductSaleswithin1weekofaclick'] > 0 ? $data[':Ordersplacedwithin1weekofaclick'] / $data[':ProductSaleswithin1weekofaclick'] : 0) * 100;

                    if (($keywordResponse = getCache('k' . $report['keywordId'])) == false) {
                        $keywordResponse = $client->getBiddableKeyword($report['keywordId']);
                        setCache('k' . $report['keywordId'], $keywordResponse);
                    }

                    if ($keywordResponse['success']) {
                        $keywordResponse['response'] = json_decode($keywordResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data[':Keyword'] = $keywordResponse['response']['keywordText'];
                        $data[':MatchType'] = $keywordResponse['response']['matchType'];

                        if (($campaignResponse = getCache('c' . $keywordResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($keywordResponse['response']['campaignId']);
                            setCache('c' . $keywordResponse['response']['campaignId'], $campaignResponse);
                        }

                        if ($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data[':CampaignName'] = $campaignResponse['response']['name'];
                        }

                        if (($adGroupResponse = getCache('ag' . $keywordResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($keywordResponse['response']['adGroupId']);
                            setCache('ag' . $keywordResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if ($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data[':AdGroupName'] = $adGroupResponse['response']['name'];
                        }
                    }

                    //     $result =    $db->prepare('REPLACE INTO `searchtermreport2` (`'.implode('`, `', array_keys($data)).'`) VALUES (?'.str_repeat(', ?', count($data) - 1).')')->execute(array_values($data));
                    $result = $serchTermPrepared->execute($data);
                    if (!$result) {
                        echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                        writeLog('GETreportsPrepared', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                    } else {
                        echo "\033[32m record inserted \033[0m  \n";
                        writeLog('GETreportsPrepared', 'we isert record  ', $user_id);
                    }
                }
            }

            break;
    }
	exit();
}

include 'db.php';

$usersResult = $db->query('SELECT * from mws where user=' . $user_id);

while ($user1 = $usersResult->fetch(PDO::FETCH_ASSOC)) {
    writeLog('GETreportsPrepared', 'firsttime for user   ' . $user1['firsttime'], $user_id);
    if ($user1['firsttime'] == 1) $usersResult1 = $db->query('update mws set firsttime=2 where user=' . $user_id);

}
writeLog('GETreportsPrepared', ' --FINISH--  ', $user_id);
echo '666661321321321312321321321';
