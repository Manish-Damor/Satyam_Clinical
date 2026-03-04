<?php
header('Content-Type: application/json');
require_once 'core.php';

function normalizeText($value)
{
    return preg_replace('/[^a-z0-9]/', '', strtolower((string) $value));
}

function acronymFromText($value)
{
    $parts = preg_split('/\s+/', strtolower((string) $value));
    $acronym = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $acronym .= $part[0];
        }
    }
    return $acronym;
}

function isSubsequence($query, $target)
{
    if ($query === '') return true;
    $pointer = 0;
    $queryLen = strlen($query);
    $targetLen = strlen($target);
    for ($index = 0; $index < $targetLen && $pointer < $queryLen; $index++) {
        if ($target[$index] === $query[$pointer]) {
            $pointer++;
        }
    }
    return $pointer === $queryLen;
}

function scoreRow($queryRaw, $row)
{
    $query = normalizeText($queryRaw);
    if ($query === '') return 0;

    $name = normalizeText($row['product_name'] ?? '');
    $brand = normalizeText($row['brand_name'] ?? '');
    $content = normalizeText($row['content'] ?? '');
    $hsn = normalizeText($row['hsn_code'] ?? '');
    $idText = (string) ($row['product_id'] ?? '');
    $acronym = normalizeText(acronymFromText(($row['product_name'] ?? '') . ' ' . ($row['brand_name'] ?? '')));

    if ($name === $query) return 1200;
    if ($idText === $queryRaw) return 1100;
    if (strpos($name, $query) === 0) return 950;
    if (strpos($brand, $query) === 0) return 870;
    if (strpos($name, $query) !== false) return 760;
    if (strpos($brand, $query) !== false) return 700;
    if (strpos($content, $query) !== false) return 620;
    if (strpos($acronym, $query) === 0) return 580;
    if (isSubsequence($query, $name)) return 520;
    if (isSubsequence($query, $brand)) return 500;
    if (strpos($hsn, $query) !== false) return 460;
    return 0;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['search']) ? trim($_GET['search']) : '');

if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

$searchTerm = '%' . $search . '%';

$sql = "SELECT p.product_id, p.product_name, p.pack_size, p.content,
               p.hsn_code, p.gst_rate, COALESCE(b.brand_name, '') AS brand_name
        FROM product p
        LEFT JOIN brands b ON b.brand_id = p.brand_id
        WHERE p.status = 1
          AND (
            p.product_name LIKE ?
            OR COALESCE(b.brand_name, '') LIKE ?
            OR p.content LIKE ?
            OR p.hsn_code LIKE ?
          )
        LIMIT 200";

$stmt = $connect->prepare($sql);
$stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $score = scoreRow($search, $row);
    if ($score > 0) {
        $row['_score'] = $score;
        $rows[] = $row;
    }
}

usort($rows, function ($left, $right) {
    if ($left['_score'] !== $right['_score']) {
        return $right['_score'] <=> $left['_score'];
    }
    return strcmp($left['product_name'], $right['product_name']);
});

$rows = array_slice($rows, 0, 30);

$payload = [];
foreach ($rows as $row) {
    $payload[] = [
        'product_id' => (int) $row['product_id'],
        'product_name' => $row['product_name'],
        'brand_name' => $row['brand_name'],
        'content' => $row['content'],
        'pack_size' => $row['pack_size'],
        'hsn_code' => $row['hsn_code'],
        'gst_rate' => (float) $row['gst_rate']
    ];
}

echo json_encode($payload);
$stmt->close();
$connect->close();
?>
