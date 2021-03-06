<?php

set_time_limit(0);
ini_set("memory_limit", -1);

$fh_lock = fopen(__DIR__.'/getReports.lock', 'w');
if(!($fh_lock && flock($fh_lock, LOCK_EX | LOCK_NB)))
    exit;

require_once 'functions.php';
require_once 'AmazonAdvertisingApi/Client.php';

$fromTime = strtotime(@$_GET['date'] ? $_GET['date'] : $argv['1']);
$tillTime = time();
$regions = json_decode(@file_get_contents(__DIR__.'/regions.json'), true);
$countries = json_decode(@file_get_contents(__DIR__.'/countries.json'), true);

while($reportData = getReport()) {

    $config = array_merge(json_decode(@file_get_contents(__DIR__.'/config.json'), true), array(
        'refreshToken' => $reportData['refresh_token'],
        'region' => $reportData['region']
    ));

    $client = new AmazonAdvertisingApi\Client($config);
    $client->profileId = $reportData['profile_id'];
	
	    $profilesResponse = $client->getProfiles();
        $profilesResponse['response'] = json_decode($profilesResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

        $currentProfile = NULL;

        foreach($profilesResponse['response'] as $profile) {
            if(strtolower($profile['profileId']) == $reportData['profile_id']) {
                $currentProfile = $profile;
            }
        }

    switch($reportData['type']) {
        case 'productAds':
            $attemps = 0;
            do {
                sleep(20);
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);

                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
            } while(!isset($reportResponse['response']['0']) AND $attemps++ < 100);

            include 'db.php';

            if($reportResponse['success'] AND isset($reportResponse['response']['0'])) {

                foreach($reportResponse['response'] as $report) {
                    $data = array(
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
                    );

                    $data['1-day Conversion Rate'] = ($data['1-day Ordered Product Sales'] > 0 ? $data['1-day Ordered Product Sales'] / $data['1-day Ordered Product Sales'] : 0) * 100;
                    $data['1-week Conversion Rate'] = ($data['1-week Ordered Product Sales'] > 0 ? $data['1-week Ordered Product Sales'] / $data['1-week Ordered Product Sales'] : 0) * 100;
                    $data['1-month Conversion Rate'] = ($data['1-month Ordered Product Sales'] > 0 ? $data['1-month Ordered Product Sales'] / $data['1-month Ordered Product Sales'] : 0) * 100;

                    if(($productAdsResponse = getCache('pa'.$report['adId'])) == false) {
                        $productAdsResponse = $client->getProductAd($report['adId']);
                        setCache('pa'.$report['adId'], $productAdsResponse);
                    }

                    if($productAdsResponse['success']) {
                        $productAdsResponse['response'] = json_decode($productAdsResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data['Advertised SKU'] = $productAdsResponse['response']['sku'];

                        if(($campaignResponse = getCache('c'.$productAdsResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($productAdsResponse['response']['campaignId']);
                            setCache('c'.$productAdsResponse['response']['campaignId'], $campaignResponse);
                        }

                        if($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Campaign Name'] = $campaignResponse['response']['name'];
                            $data['Campaign Id'] = $campaignResponse['response']['campaignId'];
                        }

                        if(($adGroupResponse = getCache('ag'.$productAdsResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($productAdsResponse['response']['adGroupId']);
                            setCache('ag'.$productAdsResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Ad Group Name'] = $adGroupResponse['response']['name'];
                            $data['Ad Group Id'] = $adGroupResponse['response']['adGroupId'];
                        }
                    }

                    $result = $db->prepare('REPLACE INTO `productadsreport2` (`'.implode('`, `', array_keys($data)).'`) VALUES (?'.str_repeat(', ?', count($data) - 1).')')->execute(array_values($data));
                }
            }
            break;
        case 'keywords':
            $attemps = 0;
            do {
                sleep(20);
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);
                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
            } while(!isset($reportResponse['response']['0']) AND $attemps++ < 100);

            include 'db.php';

            if($reportResponse['success'] AND isset($reportResponse['response']['0'])) {

                foreach($reportResponse['response'] as $report) {

                    $data = array(
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
                    );

                    $data['1-day Conversion Rate'] = ($data['1-day Ordered Product Sales'] > 0 ? $data['1-day Ordered Product Sales'] / $data['1-day Ordered Product Sales'] : 0) * 100;
                    $data['1-week Conversion Rate'] = ($data['1-week Ordered Product Sales'] > 0 ? $data['1-week Ordered Product Sales'] / $data['1-week Ordered Product Sales'] : 0) * 100;
                    $data['1-month Conversion Rate'] = ($data['1-month Ordered Product Sales'] > 0 ? $data['1-month Ordered Product Sales'] / $data['1-month Ordered Product Sales'] : 0) * 100;

                    if(($keywordResponse = getCache('k'.$report['keywordId'])) == false) {
                        $keywordResponse = $client->getBiddableKeyword($report['keywordId']);
                        setCache('k'.$report['keywordId'], $keywordResponse);
                    }

                    if($keywordResponse['success']) {
                        $keywordResponse['response'] = json_decode($keywordResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data['Keyword'] = $keywordResponse['response']['keywordText'];
                        $data['Match Type'] = $keywordResponse['response']['matchType'];

                        if(($campaignResponse = getCache('c'.$keywordResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($keywordResponse['response']['campaignId']);
                            setCache('c'.$keywordResponse['response']['campaignId'], $campaignResponse);
                        }

                        if($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Campaign Name'] = $campaignResponse['response']['name'];
                            $data['Campaign Id'] = $campaignResponse['response']['campaignId'];
                        }

                        if(($adGroupResponse = getCache('ag'.$keywordResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($keywordResponse['response']['adGroupId']);
                            setCache('ag'.$keywordResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Ad Group Name'] = $adGroupResponse['response']['name'];
                            $data['Ad Group Id'] = $adGroupResponse['response']['adGroupId'];
                        }
                    }

                    $db->prepare('REPLACE INTO `keywordsreport2` (`'.implode('`, `', array_keys($data)).'`) VALUES (?'.str_repeat(', ?', count($data) - 1).')')->execute(array_values($data));
                }
            }
            break;
        case 'keywordsQuery':

            $attemps = 0;
            do {
                sleep(20);
                $reportResponse = $client->getReport($reportData['report']['response']['reportId']);
                $reportResponse['response'] = json_decode($reportResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
            } while(!isset($reportResponse['response']['0']) AND $attemps++ < 100);

            include 'db.php';

            if($reportResponse['success'] AND isset($reportResponse['response']['0'])) {

                foreach($reportResponse['response'] as $report) {

                    $data = array(
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
                    );

                    $data['ACoS'] = ($data['Product Sales within 1-week of a click'] > 0 ? $data['Total Spend'] / $data['Product Sales within 1-week of a click'] : 0) * 100;
                    $data['Conversion Rate within 1-week of a click'] = ($data['Product Sales within 1-week of a click'] > 0 ? $data['Orders placed within 1-week of a click'] / $data['Product Sales within 1-week of a click'] : 0) * 100;

                    if(($keywordResponse = getCache('k'.$report['keywordId'])) == false) {
                        $keywordResponse = $client->getBiddableKeyword($report['keywordId']);
                        setCache('k'.$report['keywordId'], $keywordResponse);
                    }

                    if($keywordResponse['success']) {
                        $keywordResponse['response'] = json_decode($keywordResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                        $data['Keyword'] = $keywordResponse['response']['keywordText'];
                        $data['Match Type'] = $keywordResponse['response']['matchType'];

                        if(($campaignResponse = getCache('c'.$keywordResponse['response']['campaignId'])) == false) {
                            $campaignResponse = $client->getCampaign($keywordResponse['response']['campaignId']);
                            setCache('c'.$keywordResponse['response']['campaignId'], $campaignResponse);
                        }

                        if($campaignResponse['success']) {
                            $campaignResponse['response'] = json_decode($campaignResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Campaign Name'] = $campaignResponse['response']['name'];
                        }

                        if(($adGroupResponse = getCache('ag'.$keywordResponse['response']['adGroupId'])) == false) {
                            $adGroupResponse = $client->getAdGroup($keywordResponse['response']['adGroupId']);
                            setCache('ag'.$keywordResponse['response']['adGroupId'], $adGroupResponse);
                        }

                        if($adGroupResponse['success']) {
                            $adGroupResponse['response'] = json_decode($adGroupResponse['response'], true, 512, JSON_BIGINT_AS_STRING);

                            $data['Ad Group Name'] = $adGroupResponse['response']['name'];
                        }
                    }

                    $db->prepare('REPLACE INTO `searchtermreport2` (`'.implode('`, `', array_keys($data)).'`) VALUES (?'.str_repeat(', ?', count($data) - 1).')')->execute(array_values($data));
                }
            }

            break;
    }
}


echo '1';
