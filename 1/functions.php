<?php

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

function getReport($diff = 86400) {
    $reports = json_decode(file_get_contents(__DIR__.'/reports.json'), true, 512, JSON_BIGINT_AS_STRING);

    do {
        $report = array_shift($reports);
    } while($report AND time() - $report['_time'] > $diff);

    file_put_contents(__DIR__.'/reports.json', json_encode($reports));

    return $report;
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