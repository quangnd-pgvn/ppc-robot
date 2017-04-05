<?php
echo "run \n";
exec('/usr/bin/php /var/www/html/testoutput/output.php  > /dev/null &');

sleep(1);
echo "run2 \n";
exec('/usr/bin/php /var/www/html/testoutput/output.php ');
sleep(1);
echo "run3 \n";
exec('/usr/bin/php /var/www/html/testoutput/output.php 2>&1');
sleep(1);
echo "run4 \n";
exec("/usr/bin/php /var/www/html/testoutput/output.php 2>&1", $t);
print_r($t);
sleep(1);

echo "run5 \n";
passthru ("/usr/bin/php /var/www/html/testoutput/output.php 2>&1");

sleep(1);
echo "run6 \n";
passthru ("/usr/bin/php /var/www/html/testoutput/output.php");

sleep(1);
echo "run7 \n";
passthru ("/usr/bin/php /var/www/html/testoutput/output.php > /dev/null &");

sleep(1);