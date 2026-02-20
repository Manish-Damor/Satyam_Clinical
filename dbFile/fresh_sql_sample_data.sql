-- =========================================
-- PROFESSIONAL INVENTORY FULL DATA FILE
-- =========================================

USE satyam_clinical;

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================
-- 1. PRODUCT_BATCHES
-- =========================================

INSERT INTO product_batches 
(product_id, supplier_id, batch_number, manufacturing_date, expiry_date, available_quantity, purchase_rate, mrp, status)
VALUES
(1, 1, 'PCM240101', '2024-01-01', '2026-06-30', 150, 18.00, 35.00, 'Active'),
(2, 2, 'AMX240201', '2024-02-01', '2026-02-28', 80, 85.00, 120.00, 'Active'),
(3, 3, 'AZT240301', '2024-03-01', '2026-03-31', 60, 65.00, 95.00, 'Active'),
(4, 2, 'PAN240401', '2024-04-01', '2026-12-31', 90, 55.00, 85.00, 'Active'),
(5, 4, 'IBU240501', '2024-05-01', '2026-12-31', 120, 40.00, 75.00, 'Active'),
(6, 5, 'VTC240601', '2024-06-01', '2027-07-31', 100, 30.00, 60.00, 'Active');

-- =========================================
-- 2. STOCK_MOVEMENTS
-- =========================================

INSERT INTO stock_movements
(product_id, batch_id, movement_type, quantity, reference_number, reference_type, reason, created_by)
VALUES
(1, 1, 'Purchase', 150, 'PO001', 'PurchaseOrder', 'Initial stock purchase', 1),
(2, 2, 'Purchase', 80, 'PO002', 'PurchaseOrder', 'New batch received', 1),
(1, 1, 'Sales', -20, 'INV001', 'Invoice', 'Sold to customer', 2),
(3, 3, 'Adjustment', -5, 'ADJ001', 'Adjustment', 'Damaged strips removed', 1),
(4, 4, 'Sales', -10, 'INV002', 'Invoice', 'Clinic sale', 2);

-- =========================================
-- 3. PURCHASE_ORDERS
-- =========================================

INSERT INTO purchase_orders
(po_number, po_date, supplier_id, subtotal, grand_total, po_status, payment_status, created_by)
VALUES
('PO001', '2024-01-10', 1, 5000, 5250, 'Received', 'Paid', 1),
('PO002', '2024-02-15', 2, 7500, 7800, 'Received', 'PartialPaid', 1),
('PO003', '2024-03-20', 3, 6000, 6300, 'Approved', 'Due', 1),
('PO004', '2024-04-05', 2, 4500, 4700, 'Draft', 'NotDue', 1),
('PO005', '2024-05-01', 1, 9000, 9500, 'Submitted', 'Due', 1);

-- =========================================
-- 4. PO_ITEMS
-- =========================================

INSERT INTO po_items
(po_id, product_id, quantity_ordered, quantity_received, unit_price, total_price, item_status)
VALUES
(1, 1, 200, 200, 18.00, 3600.00, 'Received'),
(2, 2, 100, 100, 85.00, 8500.00, 'Received'),
(3, 3, 120, 0, 65.00, 7800.00, 'Pending'),
(4, 4, 80, 0, 55.00, 4400.00, 'Pending'),
(5, 5, 150, 0, 40.00, 6000.00, 'Pending');

-- =========================================
-- 5. INVENTORY_ADJUSTMENTS
-- =========================================

INSERT INTO inventory_adjustments
(adjustment_number, product_id, batch_id, adjustment_type, quantity_variance, old_quantity, new_quantity, reason, requested_by, adjustment_date)
VALUES
('ADJ001', 1, 1, 'PhysicalCount', -5, 150, 145, 'Stock mismatch during audit', 1, '2024-02-01'),
('ADJ002', 2, 2, 'Damage', -3, 80, 77, 'Damaged capsules', 1, '2024-03-01'),
('ADJ003', 3, 3, 'Loss', -2, 60, 58, 'Expired sample removed', 1, '2024-04-01'),
('ADJ004', 4, 4, 'Excess', 5, 90, 95, 'Extra stock found', 1, '2024-05-01'),
('ADJ005', 5, 5, 'Return', 10, 120, 130, 'Supplier replacement', 1, '2024-06-01');

-- =========================================
-- 6. EXPIRY_TRACKING
-- =========================================

INSERT INTO expiry_tracking
(batch_id, product_id, batch_number, expiry_date, days_remaining, alert_level, stock_quantity)
VALUES
(1, 1, 'PCM240101', '2026-06-30', 900, 'Green', 150),
(2, 2, 'AMX240201', '2026-02-28', 750, 'Green', 80),
(3, 3, 'AZT240301', '2026-03-31', 780, 'Green', 60),
(4, 4, 'PAN240401', '2026-12-31', 1000, 'Green', 90),
(5, 5, 'IBU240501', '2026-12-31', 1000, 'Green', 120);

-- =========================================
-- 7. ORDERS
-- =========================================

INSERT INTO orders
(order_number, orderDate, clientName, clientContact, subTotal, discount, gstn, grandTotalValue, paid, dueValue, paymentType, paymentStatus, created_by)
VALUES
('INV001', '2024-02-01', 'Rahul Sharma', '9876500001', 1500, 0, 270, 1770, 1770, 0, 'Cash', 'Paid', 2),
('INV002', '2024-02-05', 'Anita Patel', '9876500002', 2000, 100, 342, 2242, 1000, 1242, 'UPI', 'PartialPaid', 2),
('INV003', '2024-02-10', 'City Hospital', '9876500003', 5000, 0, 900, 5900, 0, 5900, 'Credit', 'Pending', 2),
('INV004', '2024-02-15', 'Sun Clinic', '9876500004', 3000, 200, 504, 3304, 3304, 0, 'Card', 'Paid', 2),
('INV005', '2024-02-20', 'Apex Lab', '9876500005', 2500, 0, 450, 2950, 1500, 1450, 'UPI', 'PartialPaid', 2);

-- =========================================
-- 8. ORDER_ITEMS
-- =========================================

INSERT INTO order_item
(order_id, product_id, batch_id, quantity, rate, total, added_date)
VALUES
(1, 1, 1, 10, 35.00, 350.00, '2024-02-01'),
(1, 2, 2, 5, 120.00, 600.00, '2024-02-01'),
(2, 3, 3, 8, 95.00, 760.00, '2024-02-05'),
(3, 4, 4, 15, 85.00, 1275.00, '2024-02-10'),
(4, 5, 5, 20, 75.00, 1500.00, '2024-02-15');

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================
-- END OF FILE
-- =========================================
