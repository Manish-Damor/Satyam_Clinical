<?php
chdir(__DIR__ . '/..');
// Usage: php run_po_action.php action=<action> po_id=<id> [item_id[]=1 item_id[]=2 quantity_received[]=1 quantity_received[]=2]
parse_str(implode('&', array_slice($argv,1)), $_POST);
if (session_status() == PHP_SESSION_NONE) session_start();
$_SESSION['userId'] = 1;
// suppress notices/warnings from core.php during CLI runs
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');
ob_start();
include 'php_action/po_actions.php';
$out = ob_get_clean();
echo "Response:\n" . $out . "\n";
