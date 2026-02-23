<?php
// CLI test to run searchMedicines.php
chdir(__DIR__ . '/..'); // ensure correct relative paths
// don't start session here; core.php will start it. set userId after core.php if needed.
$_GET['search'] = isset($argv[1]) ? $argv[1] : 'a';
include 'php_action/searchMedicines.php';
