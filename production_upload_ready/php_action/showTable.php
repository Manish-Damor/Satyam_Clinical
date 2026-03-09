<?php
require '../constant/connect.php';
if (!isset($argv[1])) {
    echo "Usage: php showTable.php table_name\n";
    exit;
}
$table = $argv[1];
$res = $connect->query("SHOW CREATE TABLE `$table`")->fetch_assoc();
print_r($res);
