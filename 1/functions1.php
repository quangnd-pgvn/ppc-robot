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

    return file_put_contents(__DIR__.'/reports'.$user_id.'.json', json_encode($reports));
}

function getReport($user_id) {
	$diff = 86400;
    $reports = json_decode(file_get_contents(__DIR__.'/reports'.$user_id.'.json'), true, 512, JSON_BIGINT_AS_STRING);

    do {
        $report = array_shift($reports);
    } while($report AND time() - $report['_time'] > $diff);

    file_put_contents(__DIR__.'/reports'.$user_id.'.json', json_encode($reports));

    return $report;
}

function getCache($key) {
    return file_exists(__DIR__.'/cache/'.$key.'.json') ? json_decode(file_get_contents(__DIR__.'/cache/'.$key.'.json'), true, 512, JSON_BIGINT_AS_STRING) : false;
}

function setCache($key, $value) {
    return file_put_contents(__DIR__.'/cache/'.$key.'.json', json_encode($value));
}
