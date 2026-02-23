<?php
chdir(__DIR__ . '/..');
require_once 'php_action/core.php';
$res = $connect->query("SHOW COLUMNS FROM product");
while($row = $res->fetch_assoc()){
    echo $row['Field']."\n";
}
$connect->close();
