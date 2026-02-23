<?php
// CLI test to run getSupplier.php
chdir(__DIR__ . '/..');
// suppress warnings for CLI test output
error_reporting(0);
$_GET['id'] = isset($argv[1]) ? intval($argv[1]) : 1;
include 'php_action/getSupplier.php';
