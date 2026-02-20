<?php
/**
 * AuditLogger - Comprehensive Audit Trail
 * 
 * Logs all UPDATE and DELETE operations on financial tables:
 * - purchase_orders, goods_received, purchase_invoices
 * - orders, customer_payments
 * - supplier_payments, inventory_adjustments
 * - stock_movements, product_batches
 * 
 * Stores before/after JSON snapshots for complete history
 * Enables compliance and forensic analysis
 * 
 * @package Services
 * @version 2.0
 * @date February 2026
 */

namespace Services;

class AuditLogger
{
    private $db;
    private $user_id;
    private $tables_to_audit = [
        'purchase_orders',
        'goods_received',
        'purchase_invoices',
        'orders',
        'customer_payments',
        'supplier_payments',
        'inventory_adjustments',
        'stock_movements',
        'product_batches',
        'customers',
        'suppliers'
    ];

    public function __construct($database, $user_id = null)
    {
        $this->db = $database;
        $this->user_id = $user_id ?? (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
    }

    /**
     * Log INSERT operation
     * 
     * @param string $table_name Table name
     * @param int $record_id Record primary key
     * @param array $new_data New record data
     * @return bool Success status
     */
    public function logInsert($table_name, $record_id, $new_data)
    {
        return $this->writeAuditLog($table_name, $record_id, 'INSERT', null, $new_data);
    }

    /**
     * Log UPDATE operation
     * 
     * @param string $table_name Table name
     * @param int $record_id Record primary key
     * @param array $old_data Original data before update
     * @param array $new_data Updated data
     * @return bool Success status
     */
    public function logUpdate($table_name, $record_id, $old_data, $new_data)
    {
        // Calculate changes summary
        $changes = $this->calculateChanges($old_data, $new_data);
        $summary = $this->generateChangesSummary($changes);

        return $this->writeAuditLog(
            $table_name,
            $record_id,
            'UPDATE',
            $old_data,
            $new_data,
            $summary
        );
    }

    /**
     * Log DELETE operation
     * 
     * @param string $table_name Table name
     * @param int $record_id Record primary key
     * @param array $deleted_data Data before deletion (soft delete stores this)
     * @return bool Success status
     */
    public function logDelete($table_name, $record_id, $deleted_data)
    {
        return $this->writeAuditLog(
            $table_name,
            $record_id,
            'DELETE',
            $deleted_data,
            null,
            'Record soft deleted'
        );
    }

    /**
     * Get audit trail for a record
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @param int $limit Number of records to return
     * @return array Audit history
     */
    public function getAuditHistory($table_name, $record_id, $limit = 50)
    {
        $sql = "SELECT 
                    id,
                    action,
                    u.name as user_name,
                    old_data,
                    new_data,
                    changes_summary,
                    ip_address,
                    action_timestamp
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = ? AND al.record_id = ?
                ORDER BY al.action_timestamp DESC
                LIMIT " . intval($limit);
        
        $result = $this->db->execute_query($sql, [$table_name, $record_id]);
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            if ($row['old_data']) {
                $row['old_data'] = json_decode($row['old_data'], true);
            }
            if ($row['new_data']) {
                $row['new_data'] = json_decode($row['new_data'], true);
            }
            $history[] = $row;
        }
        
        return $history;
    }

    /**
     * Get recent audit trail across all tables
     * 
     * @param int $limit Number of records
     * @return array Recent audit logs
     */
    public function getRecentAuditTrail($limit = 100)
    {
        $sql = "SELECT 
                    id,
                    table_name,
                    record_id,
                    action,
                    u.name as user_name,
                    changes_summary,
                    action_timestamp
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.action_timestamp DESC
                LIMIT " . intval($limit);
        
        $result = $this->db->execute_query($sql);
        $trail = [];
        
        while ($row = $result->fetch_assoc()) {
            $trail[] = $row;
        }
        
        return $trail;
    }

    /**
     * Get changes for specific field across all versions
     * Useful for tracking how a field was modified over time
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @param string $field_name Field to track
     * @return array Change history for field
     */
    public function getFieldChangeHistory($table_name, $record_id, $field_name)
    {
        $sql = "SELECT 
                    action_timestamp,
                    action,
                    u.name as user_name,
                    old_data,
                    new_data
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = ? AND al.record_id = ?
                ORDER BY al.action_timestamp ASC";
        
        $result = $this->db->execute_query($sql, [$table_name, $record_id]);
        $changes = [];
        
        while ($row = $result->fetch_assoc()) {
            $old_val = null;
            $new_val = null;

            if ($row['old_data']) {
                $old = json_decode($row['old_data'], true);
                $old_val = $old[$field_name] ?? null;
            }

            if ($row['new_data']) {
                $new = json_decode($row['new_data'], true);
                $new_val = $new[$field_name] ?? null;
            }

            // Only record if field actually changed
            if ($old_val !== $new_val) {
                $changes[] = [
                    'timestamp' => $row['action_timestamp'],
                    'action' => $row['action'],
                    'user' => $row['user_name'],
                    'old_value' => $old_val,
                    'new_value' => $new_val
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Get all changes by user in date range
     * For compliance and user activity tracking
     * 
     * @param int $user_id User ID
     * @param string $from_date Start date (YYYY-MM-DD)
     * @param string $to_date End date (YYYY-MM-DD)
     * @return array User activity
     */
    public function getUserActivityLog($user_id, $from_date, $to_date)
    {
        $sql = "SELECT 
                    table_name,
                    record_id,
                    action,
                    changes_summary,
                    action_timestamp
                FROM audit_logs
                WHERE user_id = ? 
                  AND DATE(action_timestamp) BETWEEN ? AND ?
                ORDER BY action_timestamp DESC";
        
        $result = $this->db->execute_query($sql, [$user_id, $from_date, $to_date]);
        $activity = [];
        
        while ($row = $result->fetch_assoc()) {
            $activity[] = $row;
        }
        
        return $activity;
    }

    /**
     * Get changes to financial amounts
     * Critical for variance analysis
     * 
     * @param string $table_name Table name
     * @param string $amount_field Field name containing amount
     * @param string $from_date Start date
     * @param string $to_date End date
     * @return array Amount changes
     */
    public function getFinancialChanges($table_name, $amount_field, $from_date, $to_date)
    {
        $sql = "SELECT 
                    record_id,
                    action,
                    DATE(action_timestamp) as change_date,
                    u.name as user_name,
                    old_data,
                    new_data
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = ? 
                  AND DATE(al.action_timestamp) BETWEEN ? AND ?
                ORDER BY al.action_timestamp DESC";
        
        $result = $this->db->execute_query($sql, [$table_name, $from_date, $to_date]);
        $changes = [];
        
        while ($row = $result->fetch_assoc()) {
            $old_val = null;
            $new_val = null;
            $diff = 0;

            if ($row['old_data']) {
                $old = json_decode($row['old_data'], true);
                $old_val = floatval($old[$amount_field] ?? 0);
            }

            if ($row['new_data']) {
                $new = json_decode($row['new_data'], true);
                $new_val = floatval($new[$amount_field] ?? 0);
            }

            if ($old_val != $new_val) {
                $diff = $new_val - $old_val;
                $changes[] = [
                    'record_id' => $row['record_id'],
                    'user' => $row['user_name'],
                    'change_date' => $row['change_date'],
                    'old_amount' => $old_val,
                    'new_amount' => $new_val,
                    'difference' => $diff,
                    'percent_change' => ($old_val > 0) ? (($diff / $old_val) * 100) : 100
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Export audit log as CSV
     * For external compliance/audits
     * 
     * @param string $table_name Table name
     * @param string $from_date Start date
     * @param string $to_date End date
     * @return string CSV content
     */
    public function exportAuditLog($table_name, $from_date, $to_date)
    {
        $sql = "SELECT 
                    table_name,
                    record_id,
                    action,
                    u.name as user_name,
                    action_timestamp,
                    ip_address,
                    changes_summary
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = ? 
                  AND DATE(al.action_timestamp) BETWEEN ? AND ?
                ORDER BY al.action_timestamp DESC";
        
        $result = $this->db->execute_query($sql, [$table_name, $from_date, $to_date]);
        
        $csv = "Table,Record ID,Action,User,Timestamp,IP Address,Summary\n";
        
        while ($row = $result->fetch_assoc()) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $row['table_name'],
                $row['record_id'],
                $row['action'],
                $row['user_name'] ?? 'SYSTEM',
                $row['action_timestamp'],
                $row['ip_address'],
                addslashes($row['changes_summary'])
            );
        }
        
        return $csv;
    }

    /**
     * Error logging for system issues
     * 
     * @param string $message Error message
     * @return bool Success
     */
    public function logError($message)
    {
        error_log("[AuditLogger] " . $message);
        return true;
    }

    // ======================== PRIVATE METHODS ========================

    /**
     * Write audit log to database
     */
    private function writeAuditLog(
        $table_name,
        $record_id,
        $action,
        $old_data = null,
        $new_data = null,
        $summary = ''
    ) {
        try {
            // Don't audit audit_logs table itself
            if ($table_name == 'audit_logs') {
                return true;
            }

            $sql = "INSERT INTO audit_logs 
                    (table_name, record_id, action, user_id, old_data, new_data, 
                     changes_summary, ip_address, user_agent, action_timestamp)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            return $this->db->execute_query($sql, [
                $table_name,
                $record_id,
                $action,
                $this->user_id,
                json_encode($old_data),
                json_encode($new_data),
                substr($summary, 0, 255), // Limit to 255 chars
                $ip,
                substr($user_agent, 0, 255)
            ]);

        } catch (\Exception $e) {
            error_log("Failed to write audit log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate which fields changed
     */
    private function calculateChanges($old_data, $new_data)
    {
        $changes = [];

        if (!is_array($old_data)) {
            $old_data = [];
        }
        if (!is_array($new_data)) {
            $new_data = [];
        }

        $all_keys = array_unique(array_merge(array_keys($old_data), array_keys($new_data)));

        foreach ($all_keys as $key) {
            $old_val = $old_data[$key] ?? null;
            $new_val = $new_data[$key] ?? null;

            if ($old_val !== $new_val) {
                $changes[$key] = [
                    'old' => $old_val,
                    'new' => $new_val
                ];
            }
        }

        return $changes;
    }

    /**
     * Generate human-readable summary of changes
     */
    private function generateChangesSummary($changes)
    {
        if (empty($changes)) {
            return 'No changes';
        }

        $summaries = [];
        $critical_fields = ['status', 'total_amount', 'payment_status', 'amount_paid'];

        foreach ($changes as $field => $change) {
            if (in_array($field, $critical_fields)) {
                $summaries[] = ucfirst(str_replace('_', ' ', $field)) . 
                              ": {$change['old']} → {$change['new']}";
            }
        }

        if (count($summaries) == 0) {
            $summaries[] = count($changes) . " field(s) modified";
        }

        return implode('; ', array_slice($summaries, 0, 4));
    }
}
