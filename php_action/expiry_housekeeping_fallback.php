<?php
if (!isset($connect) || !($connect instanceof mysqli)) {
    return;
}

$cacheDir = sys_get_temp_dir();
$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'satyam_clinical_expiry_fallback_' . date('Ymd') . '.flag';

if (is_file($cacheFile)) {
    return;
}

try {
    $schedulerOn = false;
    $schedulerRes = $connect->query("SHOW VARIABLES LIKE 'event_scheduler'");
    if ($schedulerRes && $schedulerRow = $schedulerRes->fetch_assoc()) {
        $schedulerOn = strtolower((string) $schedulerRow['Value']) === 'on';
    }

    $eventEnabled = false;
    $eventStmt = $connect->prepare(
        "SELECT STATUS
         FROM information_schema.EVENTS
         WHERE EVENT_SCHEMA = DATABASE()
           AND EVENT_NAME = 'ev_mark_expired_batches_daily'
         LIMIT 1"
    );
    if ($eventStmt) {
        $eventStmt->execute();
        $eventRes = $eventStmt->get_result();
        if ($eventRes && $eventRow = $eventRes->fetch_assoc()) {
            $eventEnabled = strtoupper((string) $eventRow['STATUS']) === 'ENABLED';
        }
        $eventStmt->close();
    }

    if (!($schedulerOn && $eventEnabled)) {
        $connect->query(
            "UPDATE product_batches
             SET status = 'Expired', updated_at = CURRENT_TIMESTAMP
             WHERE status = 'Active' AND expiry_date < CURDATE()"
        );
    }

    @file_put_contents($cacheFile, (string) time());
} catch (Throwable $e) {
    // silent fallback: do not interrupt page rendering
}
