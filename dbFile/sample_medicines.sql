-- ========================================
-- SAMPLE MEDICINES DATA
-- ========================================

INSERT IGNORE INTO `medicine_details` 
(`medicine_code`, `medicine_name`, `pack_size`, `manufacturer_name`, `hsn_code`, `gst_rate`, 
 `current_batch_number`, `current_expiry_date`, `mrp`, `ptr`, `reorder_level`, `reorder_quantity`, 
 `current_stock`, `is_active`) 
VALUES
('MED001', 'Alafex AM Tablet (500mg)', '10 Tablets', 'Alverta Ltd', '3004611', 18.00, 
 'AB25400', '2025-02-28', 150.00, 85.00, 50, 100, 200, 1),

('MED002', 'Paracetamol 500mg Tablet', 'Strip of 10', 'Cipla Ltd', '3004611', 12.00, 
 'CIP12345', '2025-03-15', 45.00, 22.50, 100, 200, 500, 1),

('MED003', 'Amoxicillin 500mg Capsule', 'Blister of 10', 'Alembic Pharma', '3002199', 12.00, 
 'ALP56789', '2025-04-10', 80.00, 40.00, 75, 150, 300, 1),

('MED004', 'Vitamin C 500mg Tablet', 'Bottle of 30', 'Abbott Healthcare', '2106902', 12.00, 
 'ABT78901', '2025-05-20', 120.00, 60.00, 40, 80, 150, 1),

('MED005', 'Ibuprofen 400mg Tablet', 'Strip of 15', 'Mankind Pharma', '3002202', 12.00, 
 'MAN23456', '2025-06-01', 90.00, 45.00, 60, 120, 250, 1),

('MED006', 'Omeprazole 20mg Capsule', 'Blister of 10', 'Dr Reddys Labs', '3004611', 12.00, 
 'DRL34567', '2025-07-12', 110.00, 55.00, 50, 100, 180, 1),

('MED007', 'Aspirin 75mg Tablet', 'Bottle of 30', 'Bayer Healthcare', '3002201', 12.00, 
 'BAY45678', '2025-08-25', 65.00, 32.50, 70, 140, 320, 1),

('MED008', 'Metformin 500mg Tablet', 'Strip of 10', 'Lupin Limited', '3002199', 12.00, 
 'LUP56789', '2025-09-10', 70.00, 35.00, 80, 160, 400, 1),

('MED009', 'Atorvastatin 10mg Tablet', 'Strip of 10', 'Sun Pharma', '3004611', 12.00, 
 'SUN67890', '2025-10-15', 95.00, 47.50, 45, 90, 200, 1),

('MED010', 'Cough Syrup 100ml', 'Bottle of 100ml', 'Benadryl', '3002203', 12.00, 
 'BEN78901', '2025-11-20', 85.00, 42.50, 30, 60, 100, 1),

('MED011', 'Antibiotic Ointment 10gm', 'Tube', 'Savlon', '3005901', 18.00, 
 'SAV89012', '2025-12-31', 120.00, 60.00, 25, 50, 75, 1),

('MED012', 'Insulin Injection 100IU/ml', 'Vial of 10ml', 'Novo Nordisk', '3002206', 5.00, 
 'NOV90123', '2025-01-15', 450.00, 225.00, 20, 40, 50, 1);
