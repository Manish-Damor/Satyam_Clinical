<?php
/**
 * SEARCH PRODUCTS FOR INVOICE
 * Autocomplete handler for product search
 * Prepared statements for security
 */

header('Content-Type: application/json');
require_once 'json_core.php';

function normalizeText($value)
{
    return preg_replace('/[^a-z0-9]/', '', strtolower((string)$value));
}

function acronymFromText($value)
{
    $parts = preg_split('/\s+/', strtolower((string)$value));
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
    $idText = (string)($row['product_id'] ?? '');
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

$response = [];

try {
    $searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (strlen($searchTerm) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $searchPattern = '%' . $searchTerm . '%';
    $normalizedQuery = normalizeText($searchTerm);
    
    // For short abbreviation searches (e.g., mkd), score from a wider active catalog.
    if (strlen($normalizedQuery) <= 3) {
        $stmt = $connect->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.content,
                p.pack_size,
                p.hsn_code,
                p.expected_mrp,
                p.gst_rate,
                COALESCE(b.brand_name, '') AS brand_name
            FROM product p
            LEFT JOIN brands b ON b.brand_id = p.brand_id
            WHERE p.status = 1
            ORDER BY p.product_name ASC
            LIMIT 2000
        ");
    } else {
        // Pull candidate set then rank with fuzzy scoring.
        $stmt = $connect->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.content,
                p.pack_size,
                p.hsn_code,
                p.expected_mrp,
                p.gst_rate,
                COALESCE(b.brand_name, '') AS brand_name
            FROM product p
            LEFT JOIN brands b ON b.brand_id = p.brand_id
            WHERE p.status = 1
              AND (
                p.product_name LIKE ?
                OR p.content LIKE ?
                OR p.hsn_code LIKE ?
                OR COALESCE(b.brand_name, '') LIKE ?
              )
            LIMIT 400
        ");
    }
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    if (strlen($normalizedQuery) > 3) {
        $stmt->bind_param('ssss', $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $score = scoreRow($searchTerm, $row);
        if ($score <= 0) {
            continue;
        }

        $row['_score'] = $score;
        $rows[] = $row;
    }

    usort($rows, function($left, $right) {
        if ($left['_score'] !== $right['_score']) {
            return $right['_score'] <=> $left['_score'];
        }
        return strcmp((string)$left['product_name'], (string)$right['product_name']);
    });

    $rows = array_slice($rows, 0, 30);

    foreach ($rows as $row) {
        $response[] = [
            'id' => $row['product_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'content' => $row['content'],
            'pack_size' => $row['pack_size'],
            'hsn_code' => $row['hsn_code'],
            'expected_mrp' => $row['expected_mrp'],
            'gst_rate' => $row['gst_rate'],
            'brand_name' => $row['brand_name']
        ];
    }
    
} catch (Exception $e) {
    // Return empty for errors
}

echo json_encode($response);
?>
