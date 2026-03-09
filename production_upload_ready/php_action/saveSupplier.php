<?php
// Legacy endpoint – redirect to new createSupplier handler.
header('HTTP/1.1 301 Moved Permanently');
header('Location: createSupplier.php');
exit;

