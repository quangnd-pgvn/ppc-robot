<?php

require __DIR__ . '/vendor/autoload.php';

ChargeBee_Environment::configure("PPCEntourage-test","test_ggivMxDfsrnEZBWpJDM8EFWQlWfJTGcdA");
$result = ChargeBee_PortalSession::create(array(
  "redirectUrl" => "https://yourdomain.com/users/3490343", 
  "customer" => array(
    "id" => $_GET["chargebee_id"]
  )));
$portalSession = $result->portalSession();

//var_dump($portalSession);

?>

<div id="someclass" align="center"></div>

<script type="text/javascript">
var ua = navigator.userAgent.toLowerCase();
if (ua.indexOf('safari') != -1) {

  if (ua.indexOf('chrome') > -1) {
<?php echo 'document.location.href = "' . $portalSession->accessUrl . '";'; ?>
  } else {
    document.getElementById("someclass").innerHTML="<h1><a onclick='document.getElementById(\"someclass\").innerHTML=\"\";' href='<?php echo $portalSession->accessUrl; ?>' target='_blank'>PRESS HERE TO ENTER CUSTOMER PORTAL</a></h1>"; // Safari
  }
} else

	{

<?php echo 'document.location.href = "' . $portalSession->accessUrl . '";'; ?>

	}
 </script>
<?php

//echo '<script type="text/javascript">document.location.href = "' . $portalSession->accessUrl . '"; </script>';

//header("Location: ".$portalSession->accessUrl); /* Redirect browser */
exit();

?>