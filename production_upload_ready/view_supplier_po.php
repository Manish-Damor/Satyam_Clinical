<?php
// Redirect to purchase order list filtered by supplier
if (isset($_GET['id']) && ($id = intval($_GET['id'])) > 0) {
    header('Location: po_list.php?supplier=' . $id);
} else {
    header('Location: manage_suppliers.php');
}
exit;
