<?php
/**
 Get reports for one user. We use LOAD data in file
 * php getReportsPreparedInfile1.php "someparam" 530  - will download reports for user id 530 from file reports530.json
 first param - its from old script and unused
 */
$user_id = isset($argv['2']) ? $argv['2'] : '';
//$user_id = 530;
echo "user = " . $user_id . "\n";

set_time_limit(0);
ini_set("memory_limit", -1);

require_once 'functions1.php';
require_once 'AmazonAdvertisingApi/Client.php';
writeLog('getResponsePreparedInfile1', 'Start for user: ' . $user_id, $user_id, true);
include 'db.php';
$PDOInFile = new PDO('mysql:host=' . $config_db['mysql_host'] . ';dbname=' . $config_db['mysql_db'], $config_db['mysql_user'], $config_db['mysql_password'], array(PDO::MYSQL_ATTR_LOCAL_INFILE => true, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "' . $config_db['mysql_charset'] . '"'));


while ($reportData = getReport($user_id)) {

//WHY fields keyword and matchType ??
    $productADSPreparedFile = "LOAD DATA LOCAL INFILE '__FILENAME__' REPLACE INTO TABLE productadsreport2
    FIELDS TERMINATED BY ',' ENCLOSED BY \"'\"
    LINES TERMINATED BY '\n'
    (`Campaign Name`, `Campaign Id`, `Ad Group Name`, `Ad Group Id`, `Advertised SKU`, `Start Date`, `End Date`, `Clicks`, `Impressions`, `CTR`, `Total Spend`, `Average CPC`, `Currency`, `1-day Orders Placed`, `1-day Ordered Product Sales`, `1-day Conversion Rate`, `1-day Same SKU Units Ordered`, `1-day Other SKU Units Ordered`, `1-day Same SKU Units Ordered Product Sales`, `1-day Other SKU Units Ordered Product Sales`, `1-week Orders Placed`, `1-week Ordered Product Sales`, `1-week Conversion Rate`, `1-week Same SKU Units Ordered`, `1-week Other SKU Units Ordered`, `1-week Same SKU Units Ordered Product Sales`, `1-week Other SKU Units Ordered Product Sales`, `1-month Orders Placed`, `1-month Ordered Product Sales`, `1-month Conversion Rate`, `1-month Same SKU Units Ordered`, `1-month Other SKU Units Ordered`, `1-month Same SKU Units Ordered Product Sales`, `1-month Other SKU Units Ordered Product Sales`, `updatedAt`,  `user`);";

    // where adveristed SKU ?
    $keywordsPreparedFile = "LOAD DATA LOCAL INFILE '__FILENAME__' REPLACE INTO TABLE keywordsreport2
    FIELDS TERMINATED BY ',' ENCLOSED BY \"'\"
    LINES TERMINATED BY '\n'
    (`Campaign Name`, `Campaign Id`, `Ad Group Name`, `Ad Group Id`,  `Keyword`, `Match Type`, `Start Date`, `End Date`, `Clicks`, `Impressions`, `CTR`, `Total Spend`, `Average CPC`, `Currency`, `1-day Orders Placed`, `1-day Ordered Product Sales`, `1-day Conversion Rate`, `1-day Same SKU Units Ordered`, `1-day Other SKU Units Ordered`, `1-day Same SKU Units Ordered Product Sales`, `1-day Other SKU Units Ordered Product Sales`, `1-week Orders Placed`, `1-week Ordered Product Sales`, `1-week Conversion Rate`, `1-week Same SKU Units Ordered`, `1-week Other SKU Units Ordered`, `1-week Same SKU Units Ordered Product Sales`, `1-week Other SKU Units Ordered Product Sales`, `1-month Orders Placed`, `1-month Ordered Product Sales`, `1-month Conversion Rate`, `1-month Same SKU Units Ordered`, `1-month Other SKU Units Ordered`, `1-month Same SKU Units Ordered Product Sales`, `1-month Other SKU Units Ordered Product Sales`, `updatedAt`,  `user`);";

    $searchTermPreparedInfile = "LOAD DATA LOCAL INFILE '__FILENAME__' REPLACE INTO TABLE searchtermreport2
    FIELDS TERMINATED BY ',' ENCLOSED BY \"'\"
    LINES TERMINATED BY '\n'
    (`Campaign Name`, `Ad Group Name`, `Customer Search Term`, `Keyword`, `Match Type`, `First Day of Impression`, `Last Day of Impression`, `Impressions`, `Clicks`, `CTR`, `Total Spend`, `Average CPC`, `ACoS`, `Currency`, `Orders placed within 1-week of a click`, `Product Sales within 1-week of a click`, `Conversion Rate within 1-week of a click`, `Same SKU units Ordered within 1-week of click`, `Other SKU units Ordered within 1-week of click`, `Same SKU units Product Sales within 1-week of click`, `Other SKU units Product Sales within 1-week of click`,  `user`);";

    writeLog('getResponsePreparedInfile1', 'Get report ' . $reportData['type'], $user_id);
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
           //     var_dump($reportResponse);
                $reportMsg = "We got reportResponse. ";
                if ($reportResponse['success']) {
                    $reportMsg .= "\033[32m succes \033[0m ";
                } else {
                    $reportMsg .= "\033[31m NOT success \033[0m ";
                }
                $reportMsg .= "code : \033[36m " . $reportResponse['code'] . "  \033[0m ";
                $reportMsg .= "requestId = " . $reportResponse['requestId'] . "  \n ";
                echo $reportMsg;
                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('getResponsePreparedInfile1', 'we got empty report with code 200  ', $user_id);
                }
            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('getResponsePreparedInfile1', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report :) \033[0m  \n";
                writeLog('getResponsePreparedInfile1', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }
            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {
                writeLog('getResponsePreparedInfile1', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {
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
                    $data[':1dayConversionRate'] = ($data[':1dayOrderedProductSales'] > 0 ? $data[':1dayOrderedProductSales'] / $data[':1dayOrderedProductSales'] : 0) * 100;
                    $data[':1weekConversionRate'] = ($data[':1weekOrderedProductSales'] > 0 ? $data[':1weekOrderedProductSales'] / $data[':1weekOrderedProductSales'] : 0) * 100;
                    $data[':1monthConversionRate'] = ($data[':1monthOrderedProductSales'] > 0 ? $data[':1monthOrderedProductSales'] / $data[':1monthOrderedProductSales'] : 0) * 100;


                    if (($productAdsResponse = getCache('pa' . $report['adId'])) == false) {
                        $productAdsResponse = $client->getProductAd($report['adId']);
                        setCache('pa' . $report['adId'], $productAdsResponse);
                    }

                    if ($productAdsResponse['success']) {
                        $productAdsResponse['response'] = json_decode($productAdsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data[':AdvertisedSKU'] = $productAdsResponse['response']['sku'];

                        if (($campaignResponse = getCache('c' . $productAdsResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($productAdsResponse['response']['campaignId']);
                            setCache('c' . $productAdsResponse['response']['campaignId'], $campaignResponse);
                        }

                        if ($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                            $data[':CampaignName'] = $campaignResponse['response']['name'];
                            $data[':CampaignId'] = $campaignResponse['response']['campaignId'];
                        }

                        if (($adGroupResponse = getCache('ag' . $productAdsResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($productAdsResponse['response']['adGroupId']);
                            setCache('ag' . $productAdsResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if ($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                            $data[':AdGroupName'] = $adGroupResponse['response']['name'];
                            $data[':AdGroupId'] = $adGroupResponse['response']['adGroupId'];
                        }
                    }
                    WriteToFile($data, 'ADSReport', $reportResponse['requestId']);
                    /*            if (!$result) {
                                    echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                                    writeLog('getResponsePreparedInfile1', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                                } else {
                                    echo "\033[32m record inserted \033[0m  \n";
                                    writeLog('getResponsePreparedInfile1', 'we isert record  ', $user_id);
                                }*/
                }
                //execute file
                writeLog('getResponsePreparedInfile1', 'Try load from file  ', $user_id);
                $command = str_replace('__FILENAME__', str_replace("\\", "/", realpath("reports/" . $reportResponse['requestId'] . "/ADSReport.csv")), $productADSPreparedFile);
                insertFile($PDOInFile, $command);
                echo " Try delete file  " . realpath("reports/" . $reportResponse['requestId'] . "/ADSReport.csv") . " \n";
                if (unlink("reports/" . $reportResponse['requestId'] . "/ADSReport.csv")) {
                    $dir = "reports/" . $reportResponse['requestId'];
                    if (rmdir($dir)) {
                        echo "\033[32m File and folder deleted \033[0m  \n";
                        writeLog('getResponsePreparedInfile1', 'File and folder deleted  ', $user_id);
                    } else {
                        echo "\033[31m Cant remove folder  \033[0m  \n";
                        writeLog('getResponsePreparedInfile1', 'Cant remove folder  ', $user_id);
                    }
                } else {
                    echo "\033[31m Cant remove file  \033[0m  \n";
                    writeLog('getResponsePreparedInfile1', 'Cant remove file   ', $user_id);
                }

            }
            break;

        case 'keywords':
            //    continue;
            $attemps = 0;
            do {
                if ($attemps > 0) { //no time to sleep at first run
                    sleep(20);
                }
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);
            //    var_dump($reportResponse);
                $reportMsg = "We got reportResponse. ";
                if ($reportResponse['success']) {
                    $reportMsg .= "\033[32m succes \033[0m ";
                } else {
                    $reportMsg .= "\033[31m NOT success \033[0m ";
                }
                $reportMsg .= "code : \033[36m " . $reportResponse['code'] . "  \033[0m ";
                $reportMsg .= "requestId = " . $reportResponse['requestId'] . "  \n ";
                echo $reportMsg;
                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('getResponsePreparedInfile1', 'we got empty report with code 200  ', $user_id);
                }

            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('getResponsePreparedInfile1', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report ( \033[0m  \n";
                writeLog('getResponsePreparedInfile1', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }


            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {
                writeLog('getResponsePreparedInfile1', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {
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
                    //   $result = $keywordsPrepared->execute($data);
                    WriteToFile($data, 'KeywordsReport', $reportResponse['requestId']);
                    /*          if (!$result) {
                                  echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                                  writeLog('getResponsePreparedInfile1', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                              } else {
                                  echo "\033[32m record inserted \033[0m  \n";
                                  writeLog('getResponsePreparedInfile1', 'we isert record  ', $user_id);
                              }*/
                }
                writeLog('getResponsePreparedInfile1', 'Try load from file  ', $user_id);
                echo " Try load from file  \n";
                $command = str_replace('__FILENAME__', str_replace("\\", "/", realpath("reports/" . $reportResponse['requestId'] . "/KeywordsReport.csv")), $keywordsPreparedFile);
                insertFile($PDOInFile, $command);
                echo " Try delete file file " . realpath("reports/" . $reportResponse['requestId'] . "/KeywordsReport.csv") . " \n";
                if (unlink("reports/" . $reportResponse['requestId'] . "/KeywordsReport.csv")) {
                    $dir = "reports/" . $reportResponse['requestId'];
                    if (rmdir($dir)) {
                        echo "\033[32m File and folder deleted \033[0m  \n";
                        writeLog('getResponsePreparedInfile1', 'File and folder deleted  ', $user_id);
                    } else {
                        echo "\033[31m Cant remove folder  \033[0m  \n";
                        writeLog('getResponsePreparedInfile1', 'Cant remove folder  ', $user_id);
                    }
                } else {
                    echo "\033[31m Cant remove file  \033[0m  \n";
                    writeLog('getResponsePreparedInfile1', 'Cant remove file   ', $user_id);
                }
            }
            break;
        case 'keywordsQuery':
            //        continue;
            $attemps = 0;
            do {
                if ($attemps > 0) { //no time to sleep at first run
                    sleep(20);
                }
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);
                $reportMsg = "We got reportResponse. ";
                if ($reportResponse['success']) {
                    $reportMsg .= "\033[32m succes \033[0m ";
                } else {
                    $reportMsg .= "\033[31m NOT success \033[0m ";
                }
                $reportMsg .= "code : \033[36m " . $reportResponse['code'] . "  \033[0m ";
                $reportMsg .= "requestId = " . $reportResponse['requestId'] . "  \n ";
                echo $reportMsg;
          //      var_dump($reportResponse);
                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
                if ($reportResponse['success'] == true AND $reportResponse['code'] == 200 AND (!isset($reportResponse['response']['0']))) {
                    $attemps = 99;
                    echo "\033[33m WE cant get report Code =200 but empty response \033[0m  \n";
                    addEmptyReport($reportData, $user_id);
                    writeLog('getResponsePreparedInfile1', 'we got empty report with code 200  ', $user_id);
                }
            } while (!isset($reportResponse['response']['0']) AND $attemps++ < 5);
            echo "attempts =" . $attemps . "\n";
            if (!isset($reportResponse['response']['0'])) {
                echo "\033[31m WE cant get report ( \033[0m  \n";
                writeLog('getResponsePreparedInfile1', 'we CANT GET report in ' . $attemps . ' attempts  ', $user_id);
            } else {
                echo "\033[32m WE get report ( \033[0m  \n";
                writeLog('getResponsePreparedInfile1', 'we get report in ' . $attemps . ' attempts  ', $user_id);
            }


            if ($reportResponse['success'] AND isset($reportResponse['response']['0'])) {
                writeLog('getResponsePreparedInfile1', 'we need insert  ' . count($reportResponse['response']) . '  records  ', $user_id);
                foreach ($reportResponse['response'] as $report) {
                    $data = [
                        ':CampaignName'                                => '',
                        ':AdGroupName'                                 => '',
                        ':CustomerSearchTerm'                          => $report['query'],
                        ':Keyword'                                     => '',
                        ':MatchType'                                   => '',
                        ':FirstDayofImpression'                        => date('Y-m-d', $reportData['time']),
                        ':LastDayofImpression'                         => date('Y-m-d', $reportData['time'] + 86400),
                        ':Impressions'                                 => $report['impressions'],
                        ':Clicks'                                      => $report['clicks'],
                        ':CTR'                                         => ($report['impressions'] > 0 ? $report['clicks'] / $report['impressions'] : 0) * 100,
                        ':TotalSpend'                                  => $report['cost'],
                        ':AverageCPC'                                  => $report['clicks'] > 0 ? $report['cost'] / $report['clicks'] : 0,
                        ':ACoS'                                        => '',
                        ':Currency'                                    => $currentProfile['currencyCode'],
                        ':Ordersplacedwithin1weekofaclick'             => $report['attributedConversions7d'],
                        ':ProductSaleswithin1weekofaclick'             => $report['attributedSales7d'],
                        ':ConversionRatewithin1weekofaclick'           => '',
                        ':SameSKUunitsOrderedwithin1weekofclick'       => $report['attributedConversions7dSameSKU'],
                        ':OtherSKUunitsOrderedwithin1weekofclick'      => $report['attributedConversions7d'],
                        ':SameSKUunitsProductSaleswithin1weekofclick'  => $report['attributedSales7dSameSKU'],
                        ':OtherSKUunitsProductSaleswithin1weekofclick' => $report['attributedSales7d'],
                        ':user'                                        => $reportData['user_id']
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
                    WriteToFile($data, 'SearchTermReport', $reportResponse['requestId']);
                    /*   if (!$result) {
                           echo "\033[31m record not inserted " . $db->errorInfo() . " \033[0m  \n";
                           writeLog('getResponsePreparedInfile1', 'we can\'t insert record into DB  ' . http_build_query($data) . ' error= ' . $db->errorInfo(), $user_id);
                       } else {
                           echo "\033[32m record inserted \033[0m  \n";
                           writeLog('getResponsePreparedInfile1', 'we isert record  ', $user_id);
                       }*/
                }
                writeLog('getResponsePreparedInfile1', 'Try load from file  ', $user_id);
                echo " Try load from file  \n";
                $command = str_replace('__FILENAME__', str_replace("\\", "/", realpath("reports/" . $reportResponse['requestId'] . "/SearchTermReport.csv")), $searchTermPreparedInfile);
                insertFile($PDOInFile, $command);
                echo " Try delete file file " . realpath("reports/" . $reportResponse['requestId'] . "/SearchTermReport.csv") . " \n";
                if (unlink("reports/" . $reportResponse['requestId'] . "/SearchTermReport.csv")) {
                    $dir = "reports/" . $reportResponse['requestId'];
                    if (rmdir($dir)) {
                        echo "\033[32m File and folder deleted \033[0m  \n";
                        writeLog('getResponsePreparedInfile1', 'File and folder deleted  ', $user_id);
                    } else {
                        echo "\033[31m Cant remove folder  \033[0m  \n";
                        writeLog('getResponsePreparedInfile1', 'Cant remove folder  ', $user_id);
                    }
                } else {
                    echo "\033[31m Cant remove file  \033[0m  \n";
                    writeLog('getResponsePreparedInfile1', 'Cant remove file   ', $user_id);
                }
            }

            break;
    }
 //   exit('for test');
}
/**
 * When change table structeure change this function - ' only for char and varchar fields
 *
 * @param $data
 * @param $type
 * @param $requestId
 */
function WriteToFile($data, $type, $requestId)
{

    $dir = "reports/" . $requestId;
    if (!file_exists($dir) && !is_dir($dir)) {
        mkdir($dir);
    }

    foreach ($data as $k => $v) {
        $v = str_replace("'", "\\'", $v);
        $v = str_replace(",", "\\,", $v);
        $data[$k] = $v;
    }


    switch ($type) {
        case        'ADSReport':
            $filename = $dir . '/ADSReport.csv';
            $string =
                "'" . (string)$data[':CampaignName'] . "'," . (float)$data[':CampaignId'] . ",'" . (string)$data[':AdGroupName'] . "'," . (float)$data[':AdGroupId'] .
                ",'" . (string)$data[':AdvertisedSKU'] .
                "'," . $data[':StartDate'] . "," . $data[':EndDate'] . "," . (float)$data[':Clicks'] .
                "," . (float)$data[':Impressions'] . ",'" . (double)$data[':CTR'] . "','" . (double)$data[':TotalSpend'] . "','" . (double)$data[':AverageCPC'] .
                "','" . (string)$data[':Currency'] . "'," . (float)$data[':1dayOrdersPlaced'] . ",'" . (double)$data[':1dayOrderedProductSales'] .
                "','" . (double)$data[':1dayConversionRate'] . "'," . (float)$data[':1daySameSKUUnitsOrdered'] . "," . (float)$data[':1dayOtherSKUUnitsOrdered'] .
                ",'" . (string)$data[':1daySameSKUUnitsOrderedProductSales'] . "','" . (string)$data[':1dayOtherSKUUnitsOrderedProductSales'] .
                "'," . (float)$data[':1weekOrdersPlaced'] . ",'" . (double)$data[':1weekOrderedProductSales'] . "','" . (double)$data[':1weekConversionRate'] .
                "'," . (float)$data[':1weekSameSKUUnitsOrdered'] . "," . (float)$data[':1weekOtherSKUUnitsOrdered'] . ",'" . (double)$data[':1weekSameSKUUnitsOrderedProductSales'] .
                "','" . (double)$data[':1weekOtherSKUUnitsOrderedProductSales'] . "'," . (float)$data[':1monthOrdersPlaced'] . ",'" . (double)$data[':1monthOrderedProductSales'] .
                "','" . (double)$data[':1monthConversionRate'] . "'," . (float)$data[':1monthSameSKUUnitsOrdered'] . "," . (float)$data[':1monthOtherSKUUnitsOrdered'] .
                ",'" . (double)$data[':1monthSameSKUUnitsOrderedProductSales'] . "','" . (double)$data[':1monthOtherSKUUnitsOrderedProductSales'] .
                "'," . $data[':updatedAt'] . "," . (float)$data[':user'] . "\n";
            break;
        case'SearchTermReport':
            $filename = $dir . '/SearchTermReport.csv';
            $string =
                "'" . (string)$data[':CampaignName'] . "','" . (string)$data[':AdGroupName'] . "','" . (string)$data[':CustomerSearchTerm'] .
                "','" . (string)$data[':Keyword'] . "','" . (string)$data[':MatchType'] . "'," . $data[':FirstDayofImpression'] .
                "," . $data[':LastDayofImpression'] . "," . (float)$data[':Impressions'] . "," . (float)$data[':Clicks'] . "," . (double)$data[':CTR'] .
                "," . (double)$data[':TotalSpend'] . "," . (double)$data[':AverageCPC'] . "," . (double)$data[':ACoS'] . ",'" . (string)$data[':Currency'] .
                "'," . (float)$data[':Ordersplacedwithin1weekofaclick'] . "," . (double)$data[':ProductSaleswithin1weekofaclick'] .
                "," . (double)$data[':ConversionRatewithin1weekofaclick'] . "," . (float)$data[':SameSKUunitsOrderedwithin1weekofclick'] .
                "," . (float)$data[':OtherSKUunitsOrderedwithin1weekofclick'] . "," . (float)$data[':SameSKUunitsProductSaleswithin1weekofclick'] .
                "," . (float)$data[':OtherSKUunitsProductSaleswithin1weekofclick'] . "," . (float)$data[':user'] . "\n";
            break;
        case'KeywordsReport':
            $filename = $dir . '/KeywordsReport.csv';
            $string =
                "'" . (string)$data[':CampaignName'] . "'," . (float)$data[':CampaignId'] . ",'" . (string)$data[':AdGroupName'] .
                "'," . (float)$data[':AdGroupId'] .
                ",'" . (string)$data[':Keyword'] . "','" . (string)$data[':MatchType'] .
                "'," . $data[':StartDate'] . "," . $data[':EndDate'] .
                "," . (float)$data[':Clicks'] . "," . (float)$data[':Impressions'] . ",'" . (double)$data[':CTR'] . "','" . (double)$data[':TotalSpend'] .
                "','" . (double)$data[':AverageCPC'] . "','" . (string)$data[':Currency'] . "'," . (float)$data[':1dayOrdersPlaced'] .
                ",'" . (double)$data[':1dayOrderedProductSales'] . "','" . (double)$data[':1dayConversionRate'] . "'," . (float)$data[':1daySameSKUUnitsOrdered'] .
                "," . (float)$data[':1dayOtherSKUUnitsOrdered'] . ",'" . (double)$data[':1daySameSKUUnitsOrderedProductSales'] .
                "','" . (double)$data[':1dayOtherSKUUnitsOrderedProductSales'] . "'," . (float)$data[':1weekOrdersPlaced'] .
                ",'" . (double)$data[':1weekOrderedProductSales'] . "','" . (double)$data[':1weekConversionRate'] .
                "'," . (float)$data[':1weekSameSKUUnitsOrdered'] . "," . (float)$data[':1weekOtherSKUUnitsOrdered'] .
                ",'" . (double)$data[':1weekSameSKUUnitsOrderedProductSales'] .
                "','" . (double)$data[':1weekOtherSKUUnitsOrderedProductSales'] . "'," . (float)$data[':1monthOrdersPlaced'] .
                ",'" . (double)$data[':1monthOrderedProductSales'] . "','" . (double)$data[':1monthConversionRate'] .
                "'," . (float)$data[':1monthSameSKUUnitsOrdered'] . "," . (float)$data[':1monthOtherSKUUnitsOrdered'] .
                ",'" . (double)$data[':1monthSameSKUUnitsOrderedProductSales'] . "','" . (double)$data[':1monthOtherSKUUnitsOrderedProductSales'] .
                "'," . $data[':updatedAt'] . "," . (float)$data[':user'] . "\n";
            break;
    }

    /*
        $string='';
        foreach($data as $k=>$v){
        //    $v="t'est";
            $v = str_replace("'", "\\'",$v);
            $string.="'".$v."',";
        }
        $string=substr($string,0,-1);
        $string.="\n";*/
    file_put_contents($filename, $string, FILE_APPEND);
}

function insertFile($PDOInFile, $command)
{
    global $user_id;
    try {
        $r = $PDOInFile->exec($command);
        if (!$r) {
            $error = $PDOInFile->errorInfo();
            echo "\033[31m MYSQL error  " . $error[0] . "  " . $error[2] . " \033[0m  \n";
            writeLog('getResponsePreparedInfile1', " MYSQL error  " . $error[0] . "  " . $error[2], $user_id);
            exit();
        } else {
            writeLog('getResponsePreparedInfile1', "  processed " . $r . " rows  ", $user_id);
            echo "\033[32m  processed " . $r . " rows \033[0m  \n";
        }
    } catch (PDOException $e) {
        echo "\033[31m" . $e->getMessage() . "\033[0m \n";
        writeLog('getResponsePreparedInfile1', " Exeption  " . $e->getMessage(), $user_id);
        exit();
    }
}


$usersResult = $db->query('SELECT * from mws where user=' . $user_id);

while ($user1 = $usersResult->fetch(PDO::FETCH_ASSOC)) {
    writeLog('getResponsePreparedInfile1', 'firsttime for user   ' . $user1['firsttime'], $user_id);
    if ($user1['firsttime'] == 1) $usersResult1 = $db->query('update mws set firsttime=2 where user=' . $user_id);

}
writeLog('getResponsePreparedInfile1', ' --FINISH--  ', $user_id);
echo 'getResponsePreparedInfile1   FINISHED';
