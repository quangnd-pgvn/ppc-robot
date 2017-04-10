<?php
chdir("/var/www/html/");
//import the classes we'll be using and require the autoloader if it hasn't been already.
require_once('./vendor/autoload.php');
include 'db.php';
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

$usersResult = $db->query('select * from user where id=(SELECT parent_id FROM mws WHERE user=' . $argv['1'] . ')');

while($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {

//Create messages:
$message1 = ['To' => $user['email'],
             'Subject' => "Great News! Your SKU's Have Arrived!",
             'TextBody' => "Hey there, It's Mike. \n\r It looks like your SKU's have arrived and you are all set to get started with PPC Entourage. All you have to do now is go to the settings page and select your SKU's. If you chose an unlimited account, you don't have to do this. Cick here for the setting page ( https://ppcentourage.com/settings) .\n\r (Once you are done with that, we highly recommend clicking on the blue button located on the home screen entitled 'New to Entourage? Start Here'. This will help you get a jumpstart on the software.\n\r Cheers to your success, \n\r Mike",
             'From' => "admin@ppcentourage.com"];

$newClient = new PostmarkClient("8bf97df7-26e6-4cdd-ac31-ff6377c15993");

//Pass the messages as an array to the `sendEmailBatch` function.
$responses = $newClient->sendEmailBatch([$message1]);

foreach($responses as $key=>$response){
    echo $response;
}
}
?>