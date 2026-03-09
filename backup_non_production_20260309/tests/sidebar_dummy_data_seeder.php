<?php
/**
 * Sidebar QA dummy data seeder (idempotent).
 *
 * Usage:
 *   php tests/sidebar_dummy_data_seeder.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../constant/connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

function fetchOneInt(mysqli $db, string $sql, array $types = [], array $params = []): int
{
    $stmt = $db->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param(implode('', $types), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_row() : null;
    $stmt->close();

    return $row ? (int) $row[0] : 0;
}

function upsertBrand(mysqli $db, string $name): int
{
    $stmt = $db->prepare(
        'INSERT INTO brands (brand_name, brand_active, brand_status) VALUES (?, 1, 1)
         ON DUPLICATE KEY UPDATE brand_active = 1, brand_status = 1'
    );
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->close();

    return fetchOneInt($db, 'SELECT brand_id FROM brands WHERE brand_name = ? LIMIT 1', ['s'], [$name]);
}

function upsertCategory(mysqli $db, string $name): int
{
    $stmt = $db->prepare(
        'INSERT INTO categories (categories_name, categories_active, categories_status) VALUES (?, 1, 1)
         ON DUPLICATE KEY UPDATE categories_active = 1, categories_status = 1'
    );
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->close();

    return fetchOneInt($db, 'SELECT categories_id FROM categories WHERE categories_name = ? LIMIT 1', ['s'], [$name]);
}

function upsertQaUser(mysqli $db, string $username, string $email, string $plainPassword): int
{
    $passwordHash = md5($plainPassword);
    $userType = 'ADMIN';

    $stmt = $db->prepare(
        'INSERT INTO users (username, password, email, user_type, status)
         VALUES (?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE password = VALUES(password), user_type = VALUES(user_type), status = 1'
    );
    $stmt->bind_param('ssss', $username, $passwordHash, $email, $userType);
    $stmt->execute();
    $stmt->close();

    return fetchOneInt($db, 'SELECT user_id FROM users WHERE email = ? LIMIT 1', ['s'], [$email]);
}

function upsertSupplier(mysqli $db, array $data): int
{
    $stmt = $db->prepare(
        "INSERT INTO suppliers
            (supplier_code, supplier_name, company_name, contact_person, email, phone, address, city, state, pincode, supplier_status, is_verified)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 1)
         ON DUPLICATE KEY UPDATE
            supplier_name = VALUES(supplier_name),
            company_name = VALUES(company_name),
            contact_person = VALUES(contact_person),
            email = VALUES(email),
            phone = VALUES(phone),
            address = VALUES(address),
            city = VALUES(city),
            state = VALUES(state),
            pincode = VALUES(pincode),
            supplier_status = 'Active',
            is_verified = 1"
    );

    $stmt->bind_param(
        'ssssssssss',
        $data['supplier_code'],
        $data['supplier_name'],
        $data['company_name'],
        $data['contact_person'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['city'],
        $data['state'],
        $data['pincode']
    );

    $stmt->execute();
    $stmt->close();

    return fetchOneInt($db, 'SELECT supplier_id FROM suppliers WHERE supplier_code = ? LIMIT 1', ['s'], [$data['supplier_code']]);
}

function upsertClient(mysqli $db, array $data): int
{
    $stmt = $db->prepare(
        "INSERT INTO clients
            (client_code, name, contact_phone, email, billing_address, shipping_address, city, state, postal_code, country, status, business_type, credit_limit, payment_terms, notes)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 'India', 'ACTIVE', 'Retail', ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            contact_phone = VALUES(contact_phone),
            email = VALUES(email),
            billing_address = VALUES(billing_address),
            shipping_address = VALUES(shipping_address),
            city = VALUES(city),
            state = VALUES(state),
            postal_code = VALUES(postal_code),
            status = 'ACTIVE',
            business_type = 'Retail',
            credit_limit = VALUES(credit_limit),
            payment_terms = VALUES(payment_terms),
            notes = VALUES(notes)"
    );

    $stmt->bind_param(
        'sssssssssdis',
        $data['client_code'],
        $data['name'],
        $data['contact_phone'],
        $data['email'],
        $data['billing_address'],
        $data['shipping_address'],
        $data['city'],
        $data['state'],
        $data['postal_code'],
        $data['credit_limit'],
        $data['payment_terms'],
        $data['notes']
    );

    $stmt->execute();
    $stmt->close();

    return fetchOneInt($db, 'SELECT client_id FROM clients WHERE client_code = ? LIMIT 1', ['s'], [$data['client_code']]);
}

function upsertProduct(mysqli $db, array $data): int
{
    $id = fetchOneInt($db, 'SELECT product_id FROM product WHERE product_name = ? LIMIT 1', ['s'], [$data['product_name']]);

    if ($id > 0) {
        $stmt = $db->prepare(
            "UPDATE product
             SET content = ?,
                 brand_id = ?,
                 categories_id = ?,
                 product_type = ?,
                 unit_type = ?,
                 pack_size = ?,
                 hsn_code = ?,
                 gst_rate = ?,
                 reorder_level = ?,
                 status = 1,
                 expected_mrp = ?,
                 purchase_rate = ?
             WHERE product_id = ?"
        );

        $stmt->bind_param(
            'siissssdiddi',
            $data['content'],
            $data['brand_id'],
            $data['categories_id'],
            $data['product_type'],
            $data['unit_type'],
            $data['pack_size'],
            $data['hsn_code'],
            $data['gst_rate'],
            $data['reorder_level'],
            $data['expected_mrp'],
            $data['purchase_rate'],
            $id
        );
        $stmt->execute();
        $stmt->close();

        return $id;
    }

    $stmt = $db->prepare(
        "INSERT INTO product
            (product_name, content, brand_id, categories_id, product_type, unit_type, pack_size, hsn_code, gst_rate, reorder_level, status, expected_mrp, purchase_rate)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)"
    );

    $stmt->bind_param(
        'ssiissssdidd',
        $data['product_name'],
        $data['content'],
        $data['brand_id'],
        $data['categories_id'],
        $data['product_type'],
        $data['unit_type'],
        $data['pack_size'],
        $data['hsn_code'],
        $data['gst_rate'],
        $data['reorder_level'],
        $data['expected_mrp'],
        $data['purchase_rate']
    );

    $stmt->execute();
    $newId = (int) $db->insert_id;
    $stmt->close();

    return $newId;
}

function upsertBatch(mysqli $db, array $data): int
{
    $batchId = fetchOneInt(
        $db,
        'SELECT batch_id FROM product_batches WHERE product_id = ? AND batch_number = ? LIMIT 1',
        ['i', 's'],
        [$data['product_id'], $data['batch_number']]
    );

    if ($batchId > 0) {
        $stmt = $db->prepare(
            "UPDATE product_batches
             SET supplier_id = ?,
                 manufacturing_date = ?,
                 expiry_date = ?,
                 available_quantity = ?,
                 reserved_quantity = 0,
                 damaged_quantity = 0,
                 purchase_rate = ?,
                 mrp = ?,
                 status = 'Active'
             WHERE batch_id = ?"
        );

        $stmt->bind_param(
            'issiddi',
            $data['supplier_id'],
            $data['manufacturing_date'],
            $data['expiry_date'],
            $data['available_quantity'],
            $data['purchase_rate'],
            $data['mrp'],
            $batchId
        );
        $stmt->execute();
        $stmt->close();

        return $batchId;
    }

    $stmt = $db->prepare(
        "INSERT INTO product_batches
            (product_id, supplier_id, batch_number, manufacturing_date, expiry_date, available_quantity, reserved_quantity, damaged_quantity, purchase_rate, mrp, status)
         VALUES
            (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, 'Active')"
    );

    $stmt->bind_param(
        'iisssidd',
        $data['product_id'],
        $data['supplier_id'],
        $data['batch_number'],
        $data['manufacturing_date'],
        $data['expiry_date'],
        $data['available_quantity'],
        $data['purchase_rate'],
        $data['mrp']
    );

    $stmt->execute();
    $newId = (int) $db->insert_id;
    $stmt->close();

    return $newId;
}

function upsertReorderRule(mysqli $db, array $data): void
{
    $stmt = $db->prepare(
        'INSERT INTO reorder_management
            (product_id, reorder_level, reorder_quantity, current_stock, is_low_stock, alert_date, preferred_supplier_id, is_active)
         VALUES
            (?, ?, ?, ?, ?, NOW(), ?, 1)
         ON DUPLICATE KEY UPDATE
            reorder_level = VALUES(reorder_level),
            reorder_quantity = VALUES(reorder_quantity),
            current_stock = VALUES(current_stock),
            is_low_stock = VALUES(is_low_stock),
            alert_date = NOW(),
            preferred_supplier_id = VALUES(preferred_supplier_id),
            is_active = 1'
    );

    $stmt->bind_param(
        'iiiiii',
        $data['product_id'],
        $data['reorder_level'],
        $data['reorder_quantity'],
        $data['current_stock'],
        $data['is_low_stock'],
        $data['preferred_supplier_id']
    );

    $stmt->execute();
    $stmt->close();
}

function upsertPurchaseOrder(mysqli $db, array $data): int
{
    $stmt = $db->prepare(
        "INSERT INTO purchase_orders
            (po_number, po_date, supplier_id, expected_delivery_date, delivery_location, subtotal, discount_percentage, discount_amount, gst_percentage, gst_amount, other_charges, grand_total, po_status, payment_status, notes, delete_status, created_by, status, submitted_at, submitted_by)
         VALUES
            (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, 0, ?, 'Approved', 'PartialPaid', ?, 0, ?, 'APPROVED', NOW(), ?)
         ON DUPLICATE KEY UPDATE
            po_date = VALUES(po_date),
            supplier_id = VALUES(supplier_id),
            expected_delivery_date = VALUES(expected_delivery_date),
            delivery_location = VALUES(delivery_location),
            subtotal = VALUES(subtotal),
            gst_percentage = VALUES(gst_percentage),
            gst_amount = VALUES(gst_amount),
            grand_total = VALUES(grand_total),
            po_status = 'Approved',
            payment_status = 'PartialPaid',
            notes = VALUES(notes),
            delete_status = 0,
            created_by = VALUES(created_by),
            status = 'APPROVED',
            submitted_at = NOW(),
            submitted_by = VALUES(submitted_by)"
    );

    $stmt->bind_param(
        'ssissddddsii',
        $data['po_number'],
        $data['po_date'],
        $data['supplier_id'],
        $data['expected_delivery_date'],
        $data['delivery_location'],
        $data['subtotal'],
        $data['gst_percentage'],
        $data['gst_amount'],
        $data['grand_total'],
        $data['notes'],
        $data['created_by'],
        $data['submitted_by']
    );

    $stmt->execute();
    $stmt->close();

    return fetchOneInt($db, 'SELECT po_id FROM purchase_orders WHERE po_number = ? LIMIT 1', ['s'], [$data['po_number']]);
}

function upsertPoItem(mysqli $db, array $data): void
{
    $itemId = fetchOneInt(
        $db,
        'SELECT po_item_id FROM po_items WHERE po_id = ? AND product_id = ? LIMIT 1',
        ['i', 'i'],
        [$data['po_id'], $data['product_id']]
    );

    if ($itemId > 0) {
        $stmt = $db->prepare(
            "UPDATE po_items
             SET quantity_ordered = ?,
                 pending_qty = ?,
                 quantity_received = ?,
                 unit_price = ?,
                 total_price = ?,
                 item_status = 'PartialReceived',
                 notes = ?,
                 gst_percentage = ?
             WHERE po_item_id = ?"
        );

        $stmt->bind_param(
            'iiiddsdi',
            $data['quantity_ordered'],
            $data['pending_qty'],
            $data['quantity_received'],
            $data['unit_price'],
            $data['total_price'],
            $data['notes'],
            $data['gst_percentage'],
            $itemId
        );
        $stmt->execute();
        $stmt->close();
        return;
    }

    $stmt = $db->prepare(
        "INSERT INTO po_items
            (po_id, product_id, quantity_ordered, pending_qty, quantity_received, unit_price, total_price, item_status, notes, gst_percentage)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, 'PartialReceived', ?, ?)"
    );

    $stmt->bind_param(
        'iiiiiddsd',
        $data['po_id'],
        $data['product_id'],
        $data['quantity_ordered'],
        $data['pending_qty'],
        $data['quantity_received'],
        $data['unit_price'],
        $data['total_price'],
        $data['notes'],
        $data['gst_percentage']
    );
    $stmt->execute();
    $stmt->close();
}

function upsertPurchaseInvoice(mysqli $db, array $data): int
{
    $stmt = $db->prepare(
        "INSERT INTO purchase_invoices
            (supplier_id, invoice_no, supplier_invoice_no, supplier_invoice_date, invoice_date, po_reference, payment_terms, due_date, currency, subtotal, total_discount, total_tax, freight, round_off, grand_total, paid_amount, amount_paid, payment_mode, outstanding_amount, status, notes, created_by, payment_status, last_payment_date, company_location_state, supplier_location_state, place_of_supply, gst_determination_type, is_gst_registered, supplier_gstin, total_cgst, total_sgst, total_igst)
         VALUES
            (?, ?, ?, ?, ?, ?, '30 days', ?, 'INR', ?, 0, ?, 0, 0, ?, ?, ?, 'Credit', ?, 'Approved', ?, ?, 'PARTIAL', ?, 'Gujarat', 'Gujarat', 'Gujarat', 'intrastate', 1, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            supplier_id = VALUES(supplier_id),
            supplier_invoice_no = VALUES(supplier_invoice_no),
            supplier_invoice_date = VALUES(supplier_invoice_date),
            invoice_date = VALUES(invoice_date),
            po_reference = VALUES(po_reference),
            due_date = VALUES(due_date),
            subtotal = VALUES(subtotal),
            total_tax = VALUES(total_tax),
            grand_total = VALUES(grand_total),
            paid_amount = VALUES(paid_amount),
            amount_paid = VALUES(amount_paid),
            outstanding_amount = VALUES(outstanding_amount),
            status = 'Approved',
            notes = VALUES(notes),
            created_by = VALUES(created_by),
            payment_status = 'PARTIAL',
            last_payment_date = VALUES(last_payment_date),
            supplier_gstin = VALUES(supplier_gstin),
            total_cgst = VALUES(total_cgst),
            total_sgst = VALUES(total_sgst),
            total_igst = VALUES(total_igst),
            deleted_at = NULL"
    );

    $stmt->bind_param(
        'issssssddddddsissddd',
        $data['supplier_id'],
        $data['invoice_no'],
        $data['supplier_invoice_no'],
        $data['supplier_invoice_date'],
        $data['invoice_date'],
        $data['po_reference'],
        $data['due_date'],
        $data['subtotal'],
        $data['total_tax'],
        $data['grand_total'],
        $data['paid_amount'],
        $data['amount_paid'],
        $data['outstanding_amount'],
        $data['notes'],
        $data['created_by'],
        $data['last_payment_date'],
        $data['supplier_gstin'],
        $data['total_cgst'],
        $data['total_sgst'],
        $data['total_igst']
    );

    $stmt->execute();
    $stmt->close();

    return fetchOneInt($db, 'SELECT id FROM purchase_invoices WHERE invoice_no = ? LIMIT 1', ['s'], [$data['invoice_no']]);
}

function refreshPurchaseInvoiceItems(mysqli $db, int $invoiceId, array $items): void
{
    $del = $db->prepare('DELETE FROM purchase_invoice_items WHERE invoice_id = ?');
    $del->bind_param('i', $invoiceId);
    $del->execute();
    $del->close();

    $stmt = $db->prepare(
        'INSERT INTO purchase_invoice_items
            (invoice_id, product_id, product_name, pack_size_snapshot, manufacturer_snapshot, hsn_code, batch_no, manufacture_date, expiry_date, qty, free_qty, unit_cost, effective_rate, mrp, discount_percent, discount_amount, taxable_value, tax_rate, cgst_percent, sgst_percent, igst_percent, cgst_amount, sgst_amount, igst_amount, tax_amount, line_total, product_gst_rate, supplier_quoted_mrp, our_selling_price, margin_amount, margin_percent)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 0, 0, ?, ?, ?, ?, 0, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?)' 
    );

    foreach ($items as $item) {
        $stmt->bind_param(
            'iisssssssddddddddddddddddd',
            $invoiceId,
            $item['product_id'],
            $item['product_name'],
            $item['pack_size_snapshot'],
            $item['manufacturer_snapshot'],
            $item['hsn_code'],
            $item['batch_no'],
            $item['manufacture_date'],
            $item['expiry_date'],
            $item['qty'],
            $item['unit_cost'],
            $item['effective_rate'],
            $item['mrp'],
            $item['taxable_value'],
            $item['tax_rate'],
            $item['cgst_percent'],
            $item['sgst_percent'],
            $item['cgst_amount'],
            $item['sgst_amount'],
            $item['tax_amount'],
            $item['line_total'],
            $item['product_gst_rate'],
            $item['supplier_quoted_mrp'],
            $item['our_selling_price'],
            $item['margin_amount'],
            $item['margin_percent']
        );
        $stmt->execute();
    }

    $stmt->close();
}

function upsertSalesInvoice(mysqli $db, array $data): int
{
    $stmt = $db->prepare(
        "INSERT INTO sales_invoices
            (invoice_number, client_id, invoice_date, due_date, delivery_address, billing_address, shipping_address, subtotal, discount_amount, discount_percent, gst_amount, cgst_amount, sgst_amount, igst_amount, gst_percent, client_gstin, client_pan, client_dl_no, cgst_percent, sgst_percent, igst_percent, grand_total, paid_amount, due_amount, payment_type, payment_method, payment_place, payment_notes, payment_status, created_by, submitted_by, submitted_at, fulfilled_by, fulfilled_at, updated_by, is_cancelled, deleted_at)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, 0, ?, NULL, NULL, NULL, ?, ?, 0, ?, ?, ?, 'Credit', 'Cash', 'Store', ?, ?, ?, ?, NOW(), ?, NOW(), ?, 0, NULL)
         ON DUPLICATE KEY UPDATE
            client_id = VALUES(client_id),
            invoice_date = VALUES(invoice_date),
            due_date = VALUES(due_date),
            delivery_address = VALUES(delivery_address),
            billing_address = VALUES(billing_address),
            shipping_address = VALUES(shipping_address),
            subtotal = VALUES(subtotal),
            gst_amount = VALUES(gst_amount),
            cgst_amount = VALUES(cgst_amount),
            sgst_amount = VALUES(sgst_amount),
            gst_percent = VALUES(gst_percent),
            cgst_percent = VALUES(cgst_percent),
            sgst_percent = VALUES(sgst_percent),
            grand_total = VALUES(grand_total),
            paid_amount = VALUES(paid_amount),
            due_amount = VALUES(due_amount),
            payment_notes = VALUES(payment_notes),
            payment_status = VALUES(payment_status),
            created_by = VALUES(created_by),
            submitted_by = VALUES(submitted_by),
            submitted_at = NOW(),
            fulfilled_by = VALUES(fulfilled_by),
            fulfilled_at = NOW(),
            updated_by = VALUES(updated_by),
            is_cancelled = 0,
            deleted_at = NULL"
    );

    $stmt->bind_param(
        'sisssssddddddddddssiiii',
        $data['invoice_number'],
        $data['client_id'],
        $data['invoice_date'],
        $data['due_date'],
        $data['delivery_address'],
        $data['billing_address'],
        $data['shipping_address'],
        $data['subtotal'],
        $data['gst_amount'],
        $data['cgst_amount'],
        $data['sgst_amount'],
        $data['gst_percent'],
        $data['cgst_percent'],
        $data['sgst_percent'],
        $data['grand_total'],
        $data['paid_amount'],
        $data['due_amount'],
        $data['payment_notes'],
        $data['payment_status'],
        $data['created_by'],
        $data['submitted_by'],
        $data['fulfilled_by'],
        $data['updated_by']
    );

    $stmt->execute();
    $stmt->close();

    return fetchOneInt($db, 'SELECT invoice_id FROM sales_invoices WHERE invoice_number = ? LIMIT 1', ['s'], [$data['invoice_number']]);
}

function refreshSalesInvoiceItems(mysqli $db, int $invoiceId, array $items): void
{
    $del = $db->prepare('DELETE FROM sales_invoice_items WHERE invoice_id = ?');
    $del->bind_param('i', $invoiceId);
    $del->execute();
    $del->close();

    $stmt = $db->prepare(
        'INSERT INTO sales_invoice_items
            (invoice_id, product_id, batch_id, quantity, unit_rate, purchase_rate, line_subtotal, gst_rate, gst_amount, line_total, batch_number, expiry_date)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' 
    );

    foreach ($items as $item) {
        $stmt->bind_param(
            'iiidddddddss',
            $invoiceId,
            $item['product_id'],
            $item['batch_id'],
            $item['quantity'],
            $item['unit_rate'],
            $item['purchase_rate'],
            $item['line_subtotal'],
            $item['gst_rate'],
            $item['gst_amount'],
            $item['line_total'],
            $item['batch_number'],
            $item['expiry_date']
        );
        $stmt->execute();
    }

    $stmt->close();
}

function refreshQaStockMovements(mysqli $db, array $entries): void
{
    $delete = $db->prepare("DELETE FROM stock_movements WHERE reference_number LIKE 'QA-%'");
    $delete->execute();
    $delete->close();

    $insert = $db->prepare(
        'INSERT INTO stock_movements
            (product_id, batch_id, movement_type, quantity, balance_before, balance_after, movement_date, reference_number, reference_type, reference_id, recorded_by, reason, notes, created_by)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' 
    );

    foreach ($entries as $entry) {
        $insert->bind_param(
            'iisiddsssssssi',
            $entry['product_id'],
            $entry['batch_id'],
            $entry['movement_type'],
            $entry['quantity'],
            $entry['balance_before'],
            $entry['balance_after'],
            $entry['movement_date'],
            $entry['reference_number'],
            $entry['reference_type'],
            $entry['reference_id'],
            $entry['recorded_by'],
            $entry['reason'],
            $entry['notes'],
            $entry['created_by']
        );
        $insert->execute();
    }

    $insert->close();
}

try {
    $connect->begin_transaction();

    $qaUser = [
        'username' => 'qa_sidebar_admin',
        'email' => 'qa.sidebar@satyam.local',
        'password' => 'qaadmin',
    ];

    $qaUserId = upsertQaUser($connect, $qaUser['username'], $qaUser['email'], $qaUser['password']);

    $brandId = upsertBrand($connect, 'QA Brand Prime');
    $categoryId = upsertCategory($connect, 'QA Category Prime');

    $supplierId = upsertSupplier($connect, [
        'supplier_code' => 'QA-SUP-001',
        'supplier_name' => 'QA Supplier Prime',
        'company_name' => 'QA Supplier Pvt Ltd',
        'contact_person' => 'QA Operations',
        'email' => 'supplier.qa@satyam.local',
        'phone' => '9000000001',
        'address' => 'QA Supply Park, Ahmedabad',
        'city' => 'Ahmedabad',
        'state' => 'Gujarat',
        'pincode' => '380001',
    ]);

    $clientId = upsertClient($connect, [
        'client_code' => 'QA-CLIENT-001',
        'name' => 'QA Walkin Pharmacy',
        'contact_phone' => '9000000011',
        'email' => 'client.qa@satyam.local',
        'billing_address' => 'QA Billing Address, Ahmedabad',
        'shipping_address' => 'QA Shipping Address, Ahmedabad',
        'city' => 'Ahmedabad',
        'state' => 'Gujarat',
        'postal_code' => '380015',
        'credit_limit' => 150000.00,
        'payment_terms' => 30,
        'notes' => 'Seeded for sidebar module QA testing.',
    ]);

    $productAId = upsertProduct($connect, [
        'product_name' => 'QA Paracetamol 650',
        'content' => 'Paracetamol 650mg',
        'brand_id' => $brandId,
        'categories_id' => $categoryId,
        'product_type' => 'Tablet',
        'unit_type' => 'Strip',
        'pack_size' => '10x10',
        'hsn_code' => '30049099',
        'gst_rate' => 12.00,
        'reorder_level' => 40,
        'expected_mrp' => 120.00,
        'purchase_rate' => 78.00,
    ]);

    $productBId = upsertProduct($connect, [
        'product_name' => 'QA Cough Syrup',
        'content' => 'Ambroxol + Guaifenesin',
        'brand_id' => $brandId,
        'categories_id' => $categoryId,
        'product_type' => 'Syrup',
        'unit_type' => 'Bottle',
        'pack_size' => '100ml',
        'hsn_code' => '30049011',
        'gst_rate' => 12.00,
        'reorder_level' => 25,
        'expected_mrp' => 95.00,
        'purchase_rate' => 56.00,
    ]);

    $batchAId = upsertBatch($connect, [
        'product_id' => $productAId,
        'supplier_id' => $supplierId,
        'batch_number' => 'QA-BATCH-A-2026',
        'manufacturing_date' => date('Y-m-d', strtotime('-30 days')),
        'expiry_date' => date('Y-m-d', strtotime('+300 days')),
        'available_quantity' => 180,
        'purchase_rate' => 78.00,
        'mrp' => 120.00,
    ]);

    $batchBId = upsertBatch($connect, [
        'product_id' => $productBId,
        'supplier_id' => $supplierId,
        'batch_number' => 'QA-BATCH-B-2026',
        'manufacturing_date' => date('Y-m-d', strtotime('-20 days')),
        'expiry_date' => date('Y-m-d', strtotime('+75 days')),
        'available_quantity' => 42,
        'purchase_rate' => 56.00,
        'mrp' => 95.00,
    ]);

    upsertReorderRule($connect, [
        'product_id' => $productAId,
        'reorder_level' => 40,
        'reorder_quantity' => 120,
        'current_stock' => 180,
        'is_low_stock' => 0,
        'preferred_supplier_id' => $supplierId,
    ]);

    upsertReorderRule($connect, [
        'product_id' => $productBId,
        'reorder_level' => 25,
        'reorder_quantity' => 80,
        'current_stock' => 42,
        'is_low_stock' => 0,
        'preferred_supplier_id' => $supplierId,
    ]);

    $poSubtotal = 7800.00 + 2800.00;
    $poTax = round($poSubtotal * 0.12, 2);
    $poGrand = $poSubtotal + $poTax;

    $poNumber = 'QA-PO-20260307-01';
    $poId = upsertPurchaseOrder($connect, [
        'po_number' => $poNumber,
        'po_date' => date('Y-m-d', strtotime('-7 days')),
        'supplier_id' => $supplierId,
        'expected_delivery_date' => date('Y-m-d', strtotime('+3 days')),
        'delivery_location' => 'Main QA Warehouse',
        'subtotal' => $poSubtotal,
        'gst_percentage' => 12.00,
        'gst_amount' => $poTax,
        'grand_total' => $poGrand,
        'notes' => 'Seeded QA purchase order for sidebar testing.',
        'created_by' => $qaUserId,
        'submitted_by' => $qaUserId,
    ]);

    upsertPoItem($connect, [
        'po_id' => $poId,
        'product_id' => $productAId,
        'quantity_ordered' => 100,
        'pending_qty' => 20,
        'quantity_received' => 80,
        'unit_price' => 78.00,
        'total_price' => 7800.00,
        'notes' => 'QA PO line item A',
        'gst_percentage' => 12.00,
    ]);

    upsertPoItem($connect, [
        'po_id' => $poId,
        'product_id' => $productBId,
        'quantity_ordered' => 50,
        'pending_qty' => 0,
        'quantity_received' => 50,
        'unit_price' => 56.00,
        'total_price' => 2800.00,
        'notes' => 'QA PO line item B',
        'gst_percentage' => 12.00,
    ]);

    $piSubtotal = 10600.00;
    $piTax = round($piSubtotal * 0.12, 2);
    $piGrand = $piSubtotal + $piTax;
    $piPaid = 6000.00;
    $piOutstanding = $piGrand - $piPaid;

    $purchaseInvoiceNo = 'QA-PI-20260307-01';
    $purchaseInvoiceId = upsertPurchaseInvoice($connect, [
        'supplier_id' => $supplierId,
        'invoice_no' => $purchaseInvoiceNo,
        'supplier_invoice_no' => 'SUP-QA-INV-01',
        'supplier_invoice_date' => date('Y-m-d', strtotime('-5 days')),
        'invoice_date' => date('Y-m-d', strtotime('-5 days')),
        'po_reference' => $poNumber,
        'due_date' => date('Y-m-d', strtotime('+25 days')),
        'subtotal' => $piSubtotal,
        'total_tax' => $piTax,
        'grand_total' => $piGrand,
        'paid_amount' => $piPaid,
        'amount_paid' => $piPaid,
        'outstanding_amount' => $piOutstanding,
        'notes' => 'Seeded QA purchase invoice for dashboard/report testing.',
        'created_by' => $qaUserId,
        'last_payment_date' => date('Y-m-d', strtotime('-2 days')),
        'supplier_gstin' => '24ABCDE1234F1Z5',
        'total_cgst' => $piTax / 2,
        'total_sgst' => $piTax / 2,
        'total_igst' => 0.00,
    ]);

    refreshPurchaseInvoiceItems($connect, $purchaseInvoiceId, [
        [
            'product_id' => $productAId,
            'product_name' => 'QA Paracetamol 650',
            'pack_size_snapshot' => '10x10',
            'manufacturer_snapshot' => 'QA Brand Prime',
            'hsn_code' => '30049099',
            'batch_no' => 'QA-BATCH-A-2026',
            'manufacture_date' => date('Y-m-d', strtotime('-30 days')),
            'expiry_date' => date('Y-m-d', strtotime('+300 days')),
            'qty' => 80.000,
            'unit_cost' => 78.0000,
            'effective_rate' => 78.0000,
            'mrp' => 120.00,
            'taxable_value' => 6240.00,
            'tax_rate' => 12.00,
            'cgst_percent' => 6.00,
            'sgst_percent' => 6.00,
            'cgst_amount' => 374.40,
            'sgst_amount' => 374.40,
            'tax_amount' => 748.80,
            'line_total' => 6988.80,
            'product_gst_rate' => 12.00,
            'supplier_quoted_mrp' => 120.00,
            'our_selling_price' => 120.00,
            'margin_amount' => 42.00,
            'margin_percent' => 35.00,
        ],
        [
            'product_id' => $productBId,
            'product_name' => 'QA Cough Syrup',
            'pack_size_snapshot' => '100ml',
            'manufacturer_snapshot' => 'QA Brand Prime',
            'hsn_code' => '30049011',
            'batch_no' => 'QA-BATCH-B-2026',
            'manufacture_date' => date('Y-m-d', strtotime('-20 days')),
            'expiry_date' => date('Y-m-d', strtotime('+75 days')),
            'qty' => 50.000,
            'unit_cost' => 56.0000,
            'effective_rate' => 56.0000,
            'mrp' => 95.00,
            'taxable_value' => 2800.00,
            'tax_rate' => 12.00,
            'cgst_percent' => 6.00,
            'sgst_percent' => 6.00,
            'cgst_amount' => 168.00,
            'sgst_amount' => 168.00,
            'tax_amount' => 336.00,
            'line_total' => 3136.00,
            'product_gst_rate' => 12.00,
            'supplier_quoted_mrp' => 95.00,
            'our_selling_price' => 95.00,
            'margin_amount' => 39.00,
            'margin_percent' => 41.05,
        ],
    ]);

    $si1Subtotal = 5050.00;
    $si1Tax = round($si1Subtotal * 0.12, 2);
    $si1Grand = $si1Subtotal + $si1Tax;
    $si1Paid = 4500.00;
    $si1Due = $si1Grand - $si1Paid;

    $salesInvoice1No = 'QA-SI-20260307-01';
    $salesInvoice1Id = upsertSalesInvoice($connect, [
        'invoice_number' => $salesInvoice1No,
        'client_id' => $clientId,
        'invoice_date' => date('Y-m-d', strtotime('-3 days')),
        'due_date' => date('Y-m-d', strtotime('+20 days')),
        'delivery_address' => 'QA Delivery Point',
        'billing_address' => 'QA Billing Point',
        'shipping_address' => 'QA Shipping Point',
        'subtotal' => $si1Subtotal,
        'gst_amount' => $si1Tax,
        'cgst_amount' => $si1Tax / 2,
        'sgst_amount' => $si1Tax / 2,
        'gst_percent' => 12.00,
        'cgst_percent' => 6.00,
        'sgst_percent' => 6.00,
        'grand_total' => $si1Grand,
        'paid_amount' => $si1Paid,
        'due_amount' => $si1Due,
        'payment_notes' => 'Seeded partial payment.',
        'payment_status' => 'PARTIAL',
        'created_by' => $qaUserId,
        'submitted_by' => $qaUserId,
        'fulfilled_by' => $qaUserId,
        'updated_by' => $qaUserId,
    ]);

    refreshSalesInvoiceItems($connect, $salesInvoice1Id, [
        [
            'product_id' => $productAId,
            'batch_id' => $batchAId,
            'quantity' => 25.000,
            'unit_rate' => 120.0000,
            'purchase_rate' => 78.0000,
            'line_subtotal' => 3000.00,
            'gst_rate' => 12.00,
            'gst_amount' => 360.00,
            'line_total' => 3360.00,
            'batch_number' => 'QA-BATCH-A-2026',
            'expiry_date' => date('Y-m-d', strtotime('+300 days')),
        ],
        [
            'product_id' => $productBId,
            'batch_id' => $batchBId,
            'quantity' => 20.000,
            'unit_rate' => 95.0000,
            'purchase_rate' => 56.0000,
            'line_subtotal' => 1900.00,
            'gst_rate' => 12.00,
            'gst_amount' => 228.00,
            'line_total' => 2128.00,
            'batch_number' => 'QA-BATCH-B-2026',
            'expiry_date' => date('Y-m-d', strtotime('+75 days')),
        ],
    ]);

    $si2Subtotal = 1680.00;
    $si2Tax = round($si2Subtotal * 0.12, 2);
    $si2Grand = $si2Subtotal + $si2Tax;

    $salesInvoice2No = 'QA-SI-20260307-02';
    $salesInvoice2Id = upsertSalesInvoice($connect, [
        'invoice_number' => $salesInvoice2No,
        'client_id' => $clientId,
        'invoice_date' => date('Y-m-d', strtotime('-1 day')),
        'due_date' => date('Y-m-d', strtotime('+15 days')),
        'delivery_address' => 'QA Delivery Point',
        'billing_address' => 'QA Billing Point',
        'shipping_address' => 'QA Shipping Point',
        'subtotal' => $si2Subtotal,
        'gst_amount' => $si2Tax,
        'cgst_amount' => $si2Tax / 2,
        'sgst_amount' => $si2Tax / 2,
        'gst_percent' => 12.00,
        'cgst_percent' => 6.00,
        'sgst_percent' => 6.00,
        'grand_total' => $si2Grand,
        'paid_amount' => $si2Grand,
        'due_amount' => 0.00,
        'payment_notes' => 'Seeded full payment.',
        'payment_status' => 'PAID',
        'created_by' => $qaUserId,
        'submitted_by' => $qaUserId,
        'fulfilled_by' => $qaUserId,
        'updated_by' => $qaUserId,
    ]);

    refreshSalesInvoiceItems($connect, $salesInvoice2Id, [
        [
            'product_id' => $productAId,
            'batch_id' => $batchAId,
            'quantity' => 10.000,
            'unit_rate' => 120.0000,
            'purchase_rate' => 78.0000,
            'line_subtotal' => 1200.00,
            'gst_rate' => 12.00,
            'gst_amount' => 144.00,
            'line_total' => 1344.00,
            'batch_number' => 'QA-BATCH-A-2026',
            'expiry_date' => date('Y-m-d', strtotime('+300 days')),
        ],
        [
            'product_id' => $productBId,
            'batch_id' => $batchBId,
            'quantity' => 5.000,
            'unit_rate' => 95.0000,
            'purchase_rate' => 56.0000,
            'line_subtotal' => 475.00,
            'gst_rate' => 12.00,
            'gst_amount' => 57.00,
            'line_total' => 532.00,
            'batch_number' => 'QA-BATCH-B-2026',
            'expiry_date' => date('Y-m-d', strtotime('+75 days')),
        ],
    ]);

    refreshQaStockMovements($connect, [
        [
            'product_id' => $productAId,
            'batch_id' => $batchAId,
            'movement_type' => 'Purchase',
            'quantity' => 80,
            'balance_before' => 100.00,
            'balance_after' => 180.00,
            'movement_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'reference_number' => $purchaseInvoiceNo,
            'reference_type' => 'PurchaseInvoice',
            'reference_id' => (string) $purchaseInvoiceId,
            'recorded_by' => (string) $qaUserId,
            'reason' => 'QA purchase intake',
            'notes' => 'Seeded for stock movement report.',
            'created_by' => $qaUserId,
        ],
        [
            'product_id' => $productAId,
            'batch_id' => $batchAId,
            'movement_type' => 'Sales',
            'quantity' => 35,
            'balance_before' => 180.00,
            'balance_after' => 145.00,
            'movement_date' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'reference_number' => $salesInvoice1No,
            'reference_type' => 'SalesInvoice',
            'reference_id' => (string) $salesInvoice1Id,
            'recorded_by' => (string) $qaUserId,
            'reason' => 'QA sales issue',
            'notes' => 'Seeded for stock movement report.',
            'created_by' => $qaUserId,
        ],
        [
            'product_id' => $productBId,
            'batch_id' => $batchBId,
            'movement_type' => 'Sales',
            'quantity' => 25,
            'balance_before' => 67.00,
            'balance_after' => 42.00,
            'movement_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'reference_number' => $salesInvoice2No,
            'reference_type' => 'SalesInvoice',
            'reference_id' => (string) $salesInvoice2Id,
            'recorded_by' => (string) $qaUserId,
            'reason' => 'QA sales issue',
            'notes' => 'Seeded for stock movement report.',
            'created_by' => $qaUserId,
        ],
        [
            'product_id' => $productBId,
            'batch_id' => $batchBId,
            'movement_type' => 'Adjustment',
            'quantity' => 3,
            'balance_before' => 39.00,
            'balance_after' => 42.00,
            'movement_date' => date('Y-m-d H:i:s'),
            'reference_number' => 'QA-ADJ-20260307-01',
            'reference_type' => 'ManualAdjust',
            'reference_id' => '1',
            'recorded_by' => (string) $qaUserId,
            'reason' => 'QA adjustment correction',
            'notes' => 'Seeded for stock movement report.',
            'created_by' => $qaUserId,
        ],
    ]);

    $connect->commit();

    echo "QA sidebar dummy data seeded successfully.\n";
    echo "QA_LOGIN_EMAIL=" . $qaUser['email'] . "\n";
    echo "QA_LOGIN_PASSWORD=" . $qaUser['password'] . "\n";
    echo "QA_USER_ID=" . $qaUserId . "\n";
    echo "QA_CLIENT_ID=" . $clientId . "\n";
    echo "QA_SUPPLIER_ID=" . $supplierId . "\n";
    echo "QA_PRODUCT_IDS=" . $productAId . ',' . $productBId . "\n";
    echo "QA_BATCH_IDS=" . $batchAId . ',' . $batchBId . "\n";
    echo "QA_PO_ID=" . $poId . "\n";
    echo "QA_PI_ID=" . $purchaseInvoiceId . "\n";
    echo "QA_SI_IDS=" . $salesInvoice1Id . ',' . $salesInvoice2Id . "\n";

    exit(0);
} catch (Throwable $e) {
    if ($connect->errno || $connect->error) {
        $connect->rollback();
    }

    fwrite(STDERR, "Seeder failed at " . $e->getFile() . ':' . $e->getLine() . " - " . $e->getMessage() . "\n");
    exit(1);
}
