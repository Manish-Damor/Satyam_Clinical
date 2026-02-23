<?php
// Purchase Invoice actions - integrated with project core (session + db)
// Phase 1: Complete with GST split, validation, and proper calculations

require_once 'core.php';

class PurchaseInvoiceAction {

    /**
     * Create a new purchase invoice with complete validation and transaction
     * @param array $data Invoice header data
     * @param array $items Invoice line items
     * @return array Result with success flag and message/invoice_id
     */
    public static function createInvoice($data, $items) {
        global $connect;
        if (!$connect) return ['success' => false, 'error' => 'Database connection missing'];

        // ===== VALIDATION PHASE =====
        
        // 1. Validate header data
        $validation = self::validateInvoiceHeader($data);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        // 2. Validate items
        if (empty($items) || !is_array($items)) {
            return ['success' => false, 'error' => 'No invoice items provided'];
        }

        $itemsValidation = self::validateInvoiceItems($items, $data);
        if (!$itemsValidation['valid']) {
            return ['success' => false, 'error' => $itemsValidation['error']];
        }

        // 3. Check invoice number uniqueness
        $supplier_id = intval($data['supplier_id']);
        $invoice_no = $data['invoice_no'];
        $supplier_invoice_no = $data['supplier_invoice_no'] ?? '';
        
        $uniqueCheck = $connect->query("SELECT id FROM purchase_invoices WHERE supplier_id = $supplier_id AND invoice_no = '$invoice_no'");
        if ($uniqueCheck && $uniqueCheck->num_rows > 0) {
            return ['success' => false, 'error' => "Invoice number '$invoice_no' already exists for this supplier"];
        }

        // 3b. Check supplier invoice number uniqueness (prevent duplicates from same supplier)
        if (!empty($supplier_invoice_no)) {
            $supplier_invoice_no_escaped = $connect->real_escape_string($supplier_invoice_no);
            $uniqueCheck = $connect->query("SELECT id FROM purchase_invoices WHERE supplier_id = $supplier_id AND supplier_invoice_no = '$supplier_invoice_no_escaped'");
            if ($uniqueCheck && $uniqueCheck->num_rows > 0) {
                return ['success' => false, 'error' => "Supplier invoice number '$supplier_invoice_no' already exists for this supplier. Cannot process duplicate invoice."];
            }
        }

        // 4. Supplier existence check
        $suppRes = $connect->query("SELECT supplier_id FROM suppliers WHERE supplier_id = $supplier_id LIMIT 1");
        if (!$suppRes || $suppRes->num_rows === 0) {
            return ['success' => false, 'error' => 'Supplier not found'];
        }

        // Fetch supplier details for denormalization
        $suppRes = $connect->query("SELECT supplier_id, state, gst_number FROM suppliers WHERE supplier_id = $supplier_id LIMIT 1");
        if (!$suppRes || $suppRes->num_rows === 0) {
            return ['success' => false, 'error' => 'Supplier not found'];
        }
        $supplierData = $suppRes->fetch_assoc();
        $supplierState = $supplierData['state'] ?? '';
        $supplierGstin = $supplierData['gst_number'] ?? '';

        // ===== CALCULATION & RECALCULATION PHASE (Backend) =====
        
        $gst_type = $data['gst_type'] ?? 'intrastate';
        $calculations = self::recalculateInvoice($items, $data, $gst_type);
        
        // ===== TRANSACTION PHASE =====
        
        $connect->begin_transaction();
        try {
            // Insert invoice header with all required fields
            $sql = "INSERT INTO purchase_invoices (
                supplier_id, invoice_no, supplier_invoice_no, supplier_invoice_date,
                invoice_date, po_reference, grn_reference, payment_terms, due_date, currency,
                subtotal, total_discount, total_tax,
                total_cgst, total_sgst, total_igst,
                freight, round_off, grand_total,
                status, attachment_path, notes, created_by,
                company_location_state, supplier_location_state, place_of_supply, gst_determination_type, is_gst_registered, supplier_gstin,
                paid_amount, payment_mode, outstanding_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $connect->prepare($sql);
            if (!$stmt) throw new Exception('Prepare header failed: ' . $connect->error);

            $companyState = 'Gujarat'; // Hardcoded for now, can fetch from settings later

            // Extract header fields into local variables so bind_param receives references
            $v_supplier_id = $supplier_id;
            $v_invoice_no = $invoice_no;
            $v_supplier_invoice_no = $data['supplier_invoice_no'] ?? '';
            $v_supplier_invoice_date = $data['supplier_invoice_date'] ?? null;
            $v_invoice_date = $data['invoice_date'] ?? null;
            $v_po_reference = $data['po_reference'] ?? null;
            $v_grn_reference = null; // Deprecated, set null
            $v_payment_terms = $data['payment_terms'] ?? null;
            $v_due_date = $data['due_date'] ?? null;
            $v_currency = 'INR'; // Default currency
            $v_subtotal = $calculations['subtotal'];
            $v_total_discount = $calculations['total_discount'];
            $v_total_tax = $calculations['total_tax'];
            $v_total_cgst = $calculations['total_cgst'];
            $v_total_sgst = $calculations['total_sgst'];
            $v_total_igst = $calculations['total_igst'];
            $v_freight = $calculations['freight'];
            $v_round_off = $calculations['round_off'];
            $v_grand_total = $calculations['grand_total'];
            $v_status = $data['status'] ?? 'Draft';
            $v_attachment_path = $data['attachment_path'] ?? null;
            $v_notes = $data['notes'] ?? null;
            $v_created_by = $_SESSION['userId'] ?? null;
            $v_company_state = $companyState;
            $v_supplier_state = $supplierState;
            $v_place_of_supply = $data['place_of_supply'] ?? 'Gujarat';
            $v_gst_type = $gst_type;
            $v_is_gst_registered = 1; // Assume registered by default
            $v_supplier_gstin = $supplierGstin;
            $v_paid_amount = $calculations['paid_amount'];
            $v_payment_mode = $data['payment_mode'] ?? null;
            $v_outstanding_amount = $calculations['outstanding_amount'];

            $stmt->bind_param(
                'isssssssssdddddddddsssiissssidsd',
                $v_supplier_id,
                $v_invoice_no,
                $v_supplier_invoice_no,
                $v_supplier_invoice_date,
                $v_invoice_date,
                $v_po_reference,
                $v_grn_reference,
                $v_payment_terms,
                $v_due_date,
                $v_currency,
                $v_subtotal,
                $v_total_discount,
                $v_total_tax,
                $v_total_cgst,
                $v_total_sgst,
                $v_total_igst,
                $v_freight,
                $v_round_off,
                $v_grand_total,
                $v_status,
                $v_attachment_path,
                $v_notes,
                $v_created_by,
                $v_company_state,
                $v_supplier_state,
                $v_place_of_supply,
                $v_gst_type,
                $v_is_gst_registered,
                $v_supplier_gstin,
                $v_paid_amount,
                $v_payment_mode,
                $v_outstanding_amount
            );

            if (!$stmt->execute()) throw new Exception('Execute header failed: ' . $stmt->error);
            $invoice_id = $connect->insert_id;
            $stmt->close();

            // Insert invoice items with GST split (including effective_rate)
            $itemSql = "INSERT INTO purchase_invoice_items (
                invoice_id, product_id, product_name, hsn_code, batch_no, 
                manufacture_date, expiry_date, qty, free_qty, unit_cost, effective_rate, mrp, 
                discount_percent, discount_amount, taxable_value,
                cgst_percent, sgst_percent, igst_percent,
                cgst_amount, sgst_amount, igst_amount,
                tax_rate, tax_amount, line_total
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $itemStmt = $connect->prepare($itemSql);
            if (!$itemStmt) throw new Exception('Prepare items failed: ' . $connect->error);

            foreach ($calculations['items'] as $item) {
                // Extract item fields to local vars for bind_param
                $v_item_invoice_id = $invoice_id;
                $v_product_id = intval($item['product_id'] ?? 0);
                $v_product_name = $item['product_name'] ?? null;
                $v_hsn_code = $item['hsn_code'] ?? null;
                $v_batch_no = $item['batch_no'] ?? null;
                $v_manufacture_date = $item['manufacture_date'] ?? null;
                $v_expiry_date = $item['expiry_date'] ?? null;
                $v_qty = floatval($item['qty'] ?? 0);
                $v_free_qty = floatval($item['free_qty'] ?? 0);
                $v_unit_cost = floatval($item['unit_cost'] ?? 0);
                $v_effective_rate = floatval($item['effective_rate'] ?? 0);
                $v_mrp = floatval($item['mrp'] ?? 0);
                $v_discount_percent = floatval($item['discount_percent'] ?? 0);
                $v_discount_amount = floatval($item['discount_amount'] ?? 0);
                $v_taxable_value = floatval($item['taxable_value'] ?? 0);
                $v_cgst_percent = floatval($item['cgst_percent'] ?? 0);
                $v_sgst_percent = floatval($item['sgst_percent'] ?? 0);
                $v_igst_percent = floatval($item['igst_percent'] ?? 0);
                $v_cgst_amount = floatval($item['cgst_amount'] ?? 0);
                $v_sgst_amount = floatval($item['sgst_amount'] ?? 0);
                $v_igst_amount = floatval($item['igst_amount'] ?? 0);
                $v_tax_rate = floatval($item['tax_rate'] ?? 0);
                $v_tax_amount = floatval($item['tax_amount'] ?? 0);
                $v_line_total = floatval($item['line_total'] ?? 0);

                $types = 'iisssss' . str_repeat('d', 17); // now 24 params including effective_rate
                $itemStmt->bind_param(
                    $types,
                    $v_item_invoice_id,
                    $v_product_id,
                    $v_product_name,
                    $v_hsn_code,
                    $v_batch_no,
                    $v_manufacture_date,
                    $v_expiry_date,
                    $v_qty,
                    $v_free_qty,
                    $v_unit_cost,
                    $v_effective_rate,
                    $v_mrp,
                    $v_discount_percent,
                    $v_discount_amount,
                    $v_taxable_value,
                    $v_cgst_percent,
                    $v_sgst_percent,
                    $v_igst_percent,
                    $v_cgst_amount,
                    $v_sgst_amount,
                    $v_igst_amount,
                    $v_tax_rate,
                    $v_tax_amount,
                    $v_line_total
                );

                if (!$itemStmt->execute()) {
                    throw new Exception('Item execute failed: ' . $itemStmt->error);
                }
            }
            $itemStmt->close();

            // Handle batch updates in stock_batches table ONLY IF STATUS IS APPROVED
            if ($data['status'] === 'Approved') {
                foreach ($calculations['items'] as $item) {
                    self::updateOrCreateStockBatch($invoice_id, $item, $supplier_id);
                }
            }

            $connect->commit();
            return ['success' => true, 'invoice_id' => $invoice_id, 'message' => 'Invoice created successfully'];
            
        } catch (Exception $e) {
            $connect->rollback();
            return ['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()];
        }
    }

    /**
     * Validate invoice header data
     */
    private static function validateInvoiceHeader($data) {
        if (empty($data['supplier_id'])) {
            return ['valid' => false, 'error' => 'Supplier ID is required'];
        }
        if (empty($data['invoice_no'])) {
            return ['valid' => false, 'error' => 'Invoice number is required'];
        }
        if (empty($data['supplier_invoice_no'])) {
            return ['valid' => false, 'error' => 'Supplier invoice number is required'];
        }
        if (empty($data['supplier_invoice_date'])) {
            return ['valid' => false, 'error' => 'Supplier invoice date is required'];
        }
        if (empty($data['invoice_date'])) {
            return ['valid' => false, 'error' => 'Invoice date is required'];
        }
        if (empty($data['gst_type'])) {
            return ['valid' => false, 'error' => 'GST type is required'];
        }
        if (!in_array($data['gst_type'], ['intrastate', 'interstate'])) {
            return ['valid' => false, 'error' => 'Invalid GST type'];
        }
        // Validate supplier_invoice_date <= invoice_date
        $suppInvDate = new DateTime($data['supplier_invoice_date']);
        $invDate = new DateTime($data['invoice_date']);
        if ($suppInvDate > $invDate) {
            return ['valid' => false, 'error' => 'Supplier invoice date cannot be after our invoice date'];
        }
        return ['valid' => true];
    }

    /**
     * Validate invoice items
     */
    private static function validateInvoiceItems($items, $invoiceData) {
        global $connect;
        
        $invoiceDate = new DateTime($invoiceData['invoice_date']);
        
        foreach ($items as $idx => $item) {
            // Required fields
            if (empty($item['product_name'])) {
                return ['valid' => false, 'error' => "Item " . ($idx + 1) . ": Product name is required"];
            }
            if (empty($item['batch_no'])) {
                return ['valid' => false, 'error' => "Item " . ($idx + 1) . ": Batch number is required"];
            }
            if (empty($item['expiry_date'])) {
                return ['valid' => false, 'error' => "Item " . ($idx + 1) . ": Expiry date is required"];
            }

            // Quantity validation
            $qty = floatval($item['qty'] ?? 0);
            if ($qty <= 0) {
                return ['valid' => false, 'error' => "Item " . ($idx + 1) . ": Quantity must be greater than 0"];
            }

            // Expiry date validation
            $expiryDate = new DateTime($item['expiry_date']);
            if ($expiryDate <= $invoiceDate) {
                return ['valid' => false, 'error' => "Item " . ($idx + 1) . ": Expiry date must be after invoice date"];
            }

            // MRP validation
            $mrp = floatval($item['mrp'] ?? 0);
            if ($mrp <= 0) {
                return ['valid' => false, 'error' => "Item " . ($idx + 1) . ": MRP must be provided and greater than 0"];
            }

            // GST percent validation
            $gstPercent = floatval($item['tax_rate'] ?? 0);
            if ($gstPercent < 0 || $gstPercent > 100) {
                return ['valid' => false, 'error' => "Item " . ($idx + 1) . ": Invalid GST percentage"];
            }
        }

        return ['valid' => true];
    }

    /**
     * Recalculate all invoice values from scratch (backend calculation)
     * Never trust frontend calculations
     */
    private static function recalculateInvoice($items, $data, $gst_type) {
        $subtotal = 0;
        $total_discount = 0;
        $total_cgst = 0;
        $total_sgst = 0;
        $total_igst = 0;
        $calculatedItems = [];

        foreach ($items as $item) {
            $qty = floatval($item['qty'] ?? 0);
            $unit_cost = floatval($item['unit_cost'] ?? 0);
            $discount_percent = floatval($item['discount_percent'] ?? 0);
            $tax_rate = floatval($item['tax_rate'] ?? 0);
            $mrp = floatval($item['mrp'] ?? 0);
            $free_qty = floatval($item['free_qty'] ?? 0);

            // Calculate effective rate (cost per unit when free items are included)
            $total_qty = $qty + $free_qty;
            $effective_rate = ($total_qty > 0) ? ($qty * $unit_cost) / $total_qty : $unit_cost;

            // Line calculations
            $lineAmount = $qty * $unit_cost;
            $discount_amount = ($lineAmount * $discount_percent) / 100;
            $taxable_value = $lineAmount - $discount_amount;

            // GST calculation
            $cgst_amount = 0;
            $sgst_amount = 0;
            $igst_amount = 0;
            $cgst_percent = 0;
            $sgst_percent = 0;
            $igst_percent = 0;

            if ($gst_type === 'intrastate') {
                $cgst_percent = $tax_rate / 2;
                $sgst_percent = $tax_rate / 2;
                $cgst_amount = ($taxable_value * $cgst_percent) / 100;
                $sgst_amount = ($taxable_value * $sgst_percent) / 100;
                $total_cgst += $cgst_amount;
                $total_sgst += $sgst_amount;
            } else if ($gst_type === 'interstate') {
                $igst_percent = $tax_rate;
                $igst_amount = ($taxable_value * $igst_percent) / 100;
                $total_igst += $igst_amount;
            }

            $tax_amount = $cgst_amount + $sgst_amount + $igst_amount;
            $line_total = $taxable_value + $tax_amount;

            $subtotal += $lineAmount;
            $total_discount += $discount_amount;

            $calculatedItems[] = [
                'product_id' => intval($item['product_id'] ?? 0),
                'product_name' => $item['product_name'],
                'hsn_code' => $item['hsn_code'] ?? null,
                'batch_no' => $item['batch_no'],
                'manufacture_date' => $item['manufacture_date'] ?? null,
                'expiry_date' => $item['expiry_date'],
                'qty' => $qty,
                'free_qty' => $free_qty,
                'unit_cost' => $unit_cost,
                'effective_rate' => $effective_rate,
                'mrp' => $mrp,
                'discount_percent' => $discount_percent,
                'discount_amount' => $discount_amount,
                'taxable_value' => $taxable_value,
                'cgst_percent' => $cgst_percent,
                'sgst_percent' => $sgst_percent,
                'igst_percent' => $igst_percent,
                'cgst_amount' => $cgst_amount,
                'sgst_amount' => $sgst_amount,
                'igst_amount' => $igst_amount,
                'tax_rate' => $tax_rate,
                'tax_amount' => $tax_amount,
                'line_total' => $line_total
            ];
        }

        $freight = floatval($data['freight'] ?? 0);
        $round_off = floatval($data['round_off'] ?? 0);
        $paid_amount = floatval($data['paid_amount'] ?? 0);
        $total_tax = $total_cgst + $total_sgst + $total_igst;
        $grand_total = $subtotal - $total_discount + $total_tax + $freight + $round_off;
        $outstanding_amount = $grand_total - $paid_amount;

        return [
            'items' => $calculatedItems,
            'subtotal' => $subtotal,
            'total_discount' => $total_discount,
            'total_cgst' => $total_cgst,
            'total_sgst' => $total_sgst,
            'total_igst' => $total_igst,
            'total_tax' => $total_tax,
            'freight' => $freight,
            'round_off' => $round_off,
            'grand_total' => $grand_total,
            'paid_amount' => $paid_amount,
            'outstanding_amount' => $outstanding_amount
        ];
    }

    /**
     * Update or create stock batch entry
     */
    private static function updateOrCreateStockBatch($invoice_id, $item, $supplier_id) {
        global $connect;
        
        $product_id = intval($item['product_id']);
        $batch_no = $item['batch_no'];
        $manufacture_date = $item['manufacture_date'];
        $expiry_date = $item['expiry_date'];
        $qty = floatval($item['qty']);
        $free_qty = floatval($item['free_qty'] ?? 0);
        $total_qty = $qty + $free_qty; // TOTAL stock quantity includes free items
        $mrp = floatval($item['mrp']);
        $cost_price = floatval($item['unit_cost']);
        $tax_rate = floatval($item['tax_rate'] ?? 0);

        // Check if batch already exists
        $checkSql = "SELECT id FROM stock_batches WHERE product_id = ? AND batch_no = ?";
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param('is', $product_id, $batch_no);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $checkStmt->close();

            if ($result->num_rows > 0) {
                // Update existing batch - add to quantity (using total qty)
                $row = $result->fetch_assoc();
                $batch_id = $row['id'];
                $updateSql = "UPDATE stock_batches SET qty = qty + ? WHERE id = ?";
                $updateStmt = $connect->prepare($updateSql);
                $updateStmt->bind_param('di', $total_qty, $batch_id);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
            // Insert new batch with supplier and invoice tracking (using total qty)
            $insertSql = "INSERT INTO stock_batches (product_id, batch_no, manufacture_date, expiry_date, qty, mrp, cost_price, supplier_id, invoice_id, gst_rate_applied, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $connect->prepare($insertSql);
            $userId = $_SESSION['userId'] ?? null;
            $insertStmt->bind_param('isssdddiidi', $product_id, $batch_no, $manufacture_date, $expiry_date, $total_qty, $mrp, $cost_price, $supplier_id, $invoice_id, $tax_rate, $userId);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    public static function getInvoice($invoice_id) {
        global $connect;
        $id = intval($invoice_id);
        $invRes = $connect->query("SELECT * FROM purchase_invoices WHERE id = $id");
        $inv = $invRes ? $invRes->fetch_assoc() : null;
        if (!$inv) return null;
        $inv['items'] = [];
        $res = $connect->query("SELECT * FROM purchase_invoice_items WHERE invoice_id = $id");
        while ($row = $res->fetch_assoc()) $inv['items'][] = $row;
        return $inv;
    }

    /**
     * Set an invoice to approved and, if not already handled, credit the stock.
     * This method is transaction‑safe and returns true on success.
     */
    public static function approveInvoice($invoice_id, $user_id) {
        global $connect;
        $invoice_id = intval($invoice_id);
        $connect->begin_transaction();
        try {
            // update status
            $stmt = $connect->prepare("UPDATE purchase_invoices SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            if (!$stmt) throw new Exception('Prepare approve failed: ' . $connect->error);
            $stmt->bind_param('ii', $user_id, $invoice_id);
            if (!$stmt->execute()) throw new Exception('Execute approve failed: ' . $stmt->error);
            $stmt->close();

            // fetch supplier id for stock entries
            $res = $connect->query("SELECT supplier_id FROM purchase_invoices WHERE id = $invoice_id");
            if (!$res || $res->num_rows === 0) throw new Exception('Invoice not found after approve');
            $row = $res->fetch_assoc();
            $supplier_id = intval($row['supplier_id']);

            // get all items and update/create batches
            $itemsRes = $connect->query("SELECT * FROM purchase_invoice_items WHERE invoice_id = $invoice_id");
            while ($item = $itemsRes->fetch_assoc()) {
                // use same helper that createInvoice uses
                self::updateOrCreateStockBatch($invoice_id, $item, $supplier_id);
            }

            $connect->commit();
            return true;
        } catch (Exception $e) {
            $connect->rollback();
            return false;
        }
    }

}

?>
