<?php
// Simple read-only schema dumper for selected tables
header('Content-Type: application/json');
require_once __DIR__ . '/../constant/connect.php';
$out = [ 'ok' => false, 'db'=>null, 'tables'=>[] ];
if (!isset($connect) || !$connect) {
    echo json_encode(['ok'=>false,'error'=>'DB connect not available']);
    exit;
}
$res = $connect->query("SELECT DATABASE() as db");
$db = $res ? $res->fetch_assoc()['db'] : null;
$out['db'] = $db;
$tables = [
    'purchase_orders', 'purchase_order_items',
    'purchase_invoices','purchase_invoice_items',
    'product','suppliers','stock_batches','users'
];
foreach ($tables as $t) {
    $info = ['exists'=>false,'columns'=>[], 'sample'=>null];
    $q = $connect->prepare("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=? AND table_name=?");
    $q->bind_param('ss',$db,$t);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    if ($r && intval($r['c'])>0) {
        $info['exists'] = true;
        $colq = $connect->prepare("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA FROM information_schema.columns WHERE table_schema=? AND table_name=? ORDER BY ORDINAL_POSITION");
        $colq->bind_param('ss',$db,$t);
        $colq->execute();
        $cres = $colq->get_result();
        while ($crow = $cres->fetch_assoc()) {
            $info['columns'][] = $crow;
        }
        $sres = $connect->query("SELECT * FROM `".$t."` LIMIT 1");
        if ($sres) {
            $srow = $sres->fetch_assoc();
            if ($srow) {
                // mask potentially large text/blob fields
                foreach ($srow as $k=>$v) {
                    if (is_string($v) && strlen($v)>200) $srow[$k] = substr($v,0,200) . '...';
                }
                $info['sample'] = $srow;
            }
        }
    }
    $out['tables'][$t] = $info;
}
$out['ok'] = true;
echo json_encode($out, JSON_PRETTY_PRINT);
