<?php
require_once 'AmazonAdvertisingApi/Client.php';
require_once 'db.php';
$result = array();
if (isset($argv[1])) $where = 'user="' . $argv[1] . '"'; else $where = '';
$usersResult = $db->query('SELECT `code`, `country_id`, `SellerID`, `user`  FROM `mws` WHERE ' . $where);
while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {
            $config = array_merge(json_decode(@file_get_contents(__DIR__ . '/config.json'), true), array(
                'refreshToken' => 'Atzr|IwEBIKkEwdtV-GiDChFBIwNkkayvqvfMMy5QMh33ONSCDgmHRu5TLmiAiCXH-g1RNK9885PFYrSwljG_ffCr5QjD_KDlmR9d2NRISFoFwe4rVadaXA4yJkVixIBPWCCS1lIOk6c8Fjde6VLwnAgDBpoJ2yQW7hDkBxfSo2jMPsZFb6c5UiJYpWADV7FfPiMycYNU49fd0C03eCMV_aFsEySD3erTiS5xgX5_xR3IBI-iqUY7oCjVNUxVM5Zv0GZug4XDdTXyhzTBzTfN8Cleq1fOT0UJH75ayh41FoB6wlJiEoPzukQSp4a_Xg4sl7hNDWx6IVgWIaKPS2RILEZP9HAp3UMzpzvcrzsNBnaqvnOCJxMmby2nwc22xWUzSRZOPE1Als_2RTJQAat5l0yw0Bye7AjDEytqZMeqSRFW7JyR3-EoSapfzHSrZyr7VURz7mdla7GKUE35FsgVT44FQ72l1iQ3_EgYB1yubyJptPUf5jTckTTto6wpb4Yqu6rgTov5Wgh0ARDLbU05YHLu9VfI7IeP_5fyz6q2r0Y9ijAfpjBfrhP-c5sKlvwyiEnU1D4DokI',
                'region'       => 'na'
            ));
 
    $client = new AmazonAdvertisingApi\Client($config);
    $profilesResponse = $client->getProfiles();
    $response = json_decode($profilesResponse['response'], true, 512, JSON_BIGINT_AS_STRING);
var_dump($profilesResponse);
    $correctKey = false;
    foreach ($response as $profile) {
        if (($profile['accountInfo']['sellerStringId'] == $user['SellerID']) AND (strtolower($profile['countryCode']) == $user['country_id'] || ($profile['countryCode'] == 'UK' && $user['country_id'] == 'gb'))) {
            $correctKey = true;
			// save profile_id
			//$users1 = $db->query('update `mws` set profileId='.$profile['profileId'].' WHERE `user`='.$user['user']);
			
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
