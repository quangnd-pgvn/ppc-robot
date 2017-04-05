<?php
require_once 'AmazonAdvertisingApi/Client.php';
require_once 'db.php';
$result = array();
if (isset($argv[1])) $where = 'AND user="' . $argv[1] . '"'; else $where = '';
$usersResult = $db->query('SELECT `code`, `country_id`, `SellerID`, `user`  FROM `mws` WHERE `code` IS NOT NULL ' . $where);
while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {
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
    $response = json_decode($profilesResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
    $correctKey = false;
    foreach ($response as $profile) {
        if (($profile['accountInfo']['sellerStringId'] == $user['SellerID']) AND (strtolower($profile['countryCode']) == $user['country_id'] || ($profile['countryCode'] == 'UK' && $user['country_id'] == 'gb'))) {
            $correctKey = true;
			// save profile_id
			$users1 = $db->query('update `mws` set profileId='.$profile['profileId'].' WHERE `user`='.$user['user']);
			
        }
    }
    if ($correctKey) {
        $result['users'][$user['user']] = true;
    } else {
        $result['users'][$user['user']] = false;
    }
}
if (empty($result)) {
    $result['msg'] = 'User not found';
} else {
    $result['msg'] = 'OK';
}
echo json_encode($result);