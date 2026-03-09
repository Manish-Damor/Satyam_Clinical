<?php
require_once __DIR__ . '/php_action/db_connect.php';
$tables = ['orders','order_item','order_items','order_details'];
foreach($tables as $t){
    $res = $connect->query("SHOW COLUMNS FROM $t");
    if(!$res){
        echo "$t: error " . $connect->error . "\n";
        continue;
    }
    echo "Table $t:\n";
    while($r=$res->fetch_assoc()){
        echo "  {$r['Field']} ({$r['Type']})\n";
    }
    echo "\n";
}
?>