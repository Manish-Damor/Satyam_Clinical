<?php
require_once 'php_action/core.php';

function runAction($post) {
    $_POST = $post;
    ob_start();
    include 'php_action/po_actions.php';
    return ob_get_clean();
}

echo "Approve PO 11:\n";
echo runAction(['action'=>'approve_po','po_id'=>11]);

echo "\nCancel PO 12:\n";
echo runAction(['action'=>'cancel_po','po_id'=>12]);

echo "\nMark_received PO 13:\n";
echo runAction(['action'=>'mark_received','po_id'=>13]);

?>