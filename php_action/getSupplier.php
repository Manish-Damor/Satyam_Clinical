<?php
// header('Content-Type: application/json');
// require_once 'core.php';

// $supplierId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// if(!$supplierId) {
//     echo json_encode(['error' => 'Supplier ID not provided']);
//     exit;
// }

// $sql = "SELECT * FROM suppliers WHERE supplier_id = $supplierId";
// $result = $connect->query($sql);

// if($result->num_rows > 0) {
//     $supplier = $result->fetch_assoc();
//     echo json_encode($supplier);
// } else {
//     echo json_encode(['error' => 'Supplier not found']);
// }

// $connect->close();
?>

<?php
header('Content-Type: application/json');
require_once 'core.php';

$response = [
    "success" => false,
    "data" => null,
    "error" => null
];

$supplierId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$supplierId) {
    http_response_code(400);
    $response["error"] = "Invalid supplier ID";
    echo json_encode($response);
    exit;
}

$stmt = $connect->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$stmt->bind_param("i", $supplierId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    $response["error"] = "Supplier not found";
} else {
    $response["success"] = true;
    $response["data"] = $result->fetch_assoc();
}

echo json_encode($response);
$connect->close();
?>
