<?php

require_once 'core.php';
require_once '../config/bootstrap.php';

use Controllers\GRNController;

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Unknown error'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (!isset($_SESSION['userId']) || $_SESSION['userId'] <= 0) {
            throw new Exception('Session expired');
        }

        $userId = intval($_SESSION['userId']);
        $userRole = $_SESSION['user_role'] ?? 'user';

        $grnId = intval($_POST['grn_id'] ?? 0);
        $approvalNotes = $_POST['approval_notes'] ?? '';

        if (!$grnId) {
            throw new Exception('GRN ID is required');
        }

        // Check authorization
        if (!in_array($userRole, ['manager', 'admin'])) {
            throw new Exception('You do not have permission to approve GRNs');
        }

        $controller = new GRNController($connect, $userId, $userRole);
        $result = $controller->approveGRN($grnId, $approvalNotes);

        $response['success'] = $result['success'];
        $response['message'] = $result['message'];

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);

?>
