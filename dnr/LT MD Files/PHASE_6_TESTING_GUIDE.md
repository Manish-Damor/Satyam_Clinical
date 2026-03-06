# Phase 6: End-to-End Testing & Validation

**Date:** February 2026  
**Status:** ACTIVE  
**Objective:** Complete workflow testing and documentation for Sales Invoice Refactor

---

## 1. System Testing Checklist

### 1.1 Database Integrity Tests

- [ ] **Clients Table**: Verify all 23 columns exist and 4 sample clients loaded
  - Test Query: `SELECT * FROM clients WHERE status != 'Inactive' LIMIT 5;`
  - Expected: 4 records (Sunrise Pharmacy, Apollo Distribution, City Hospital, Dr. Sharma Clinic)

- [ ] **Sales Invoices Table**: Schema validation
  - Test Query: `DESCRIBE sales_invoices;`
  - Expected: 32 columns with all audit fields (created_by, updated_by, submitted_at, fulfilled_at)

- [ ] **Sales Invoice Items**: PTR column storage
  - Test Query: `DESCRIBE sales_invoice_items;`
  - Expected: 12 columns including `purchase_rate` for PTR storage

- [ ] **Invoice Sequence Table**: Auto-number support
  - Test Query: `SELECT * FROM invoice_sequence;`
  - Expected: Current year row with next_number starting point

- [ ] **Product Table**: PTR purchase_rate column
  - Test Query: `SHOW COLUMNS FROM product WHERE Field = 'purchase_rate';`
  - Expected: DECIMAL(14,4) column present

### 1.2 Clients Module Testing

#### Create Client Workflow

- [ ] **Test 1: Add New Client**
  - Steps: Navigate to Clients > Add New > Fill all fields > Save
  - Expected Outputs:
    - Client code auto-generated (CL001 format)
    - All 23 fields saved correctly
    - Redirect to clients list on success
    - New client appears in list instantly

- [ ] **Test 2: Validate Required Fields**
  - Steps: Try to save with missing name/email/phone
  - Expected: Form validation prevents save, error message displayed

- [ ] **Test 3: Client Search**
  - Steps: Filter clients by name, code, business type
  - Expected: Results refresh correctly, inactive clients hidden

- [ ] **Test 4: Update Existing Client**
  - Steps: Click edit on existing client, modify fields, save
  - Expected: Changes reflect immediately in list, updated_at timestamp changes

- [ ] **Test 5: Delete Client**
  - Steps: Click delete on client with no invoices
  - Expected: Client soft-deleted (status -> Inactive)
  - Steps: Try to delete client with invoices
  - Expected: Error message showing invoice count

#### Client Business Logic

- [ ] **Credit Terms Calculation**: Outstanding balance tracks properly
- [ ] **GSTIN Validation**: Only saved if format matches XX99XXXXX9999X9X9
- [ ] **Address Validation**: Delivery address different from billing works correctly

### 1.3 Sales Invoice Form Testing

#### Invoice Creation Workflow

- [ ] **Test 1: Create Invoice - Happy Path**
  - Steps:
    1. Invoice > New Invoice
    2. Invoice automatically shows: INV-26-00001 (or next sequence)
    3. Select Client: "Sunrise Pharmacy"
    4. Add Item: Select product, batch, quantity, rate
    5. Verify PTR displays in form (red box with label "Purchase Rate")
    6. Verify GST calculates correctly
    7. Save invoice
  - Expected Outcomes:
    - Invoice number follows INV-YY-NNNNN format
    - PTR field visible with value from product.purchase_rate
    - All calculations correct (subtotal, discount, GST, total)
    - Redirect to invoice list with new invoice shown
    - Status shows "DRAFT"

- [ ] **Test 2: Auto-Generated Invoice Numbers**
  - Steps: Create 5 invoices in succession
  - Expected: Numbers increment (00001, 00002, 00003, 00004, 00005)
  - Steps: Wait until next year (simulate), create first invoice of next year
  - Expected: Number resets to INV-26-00001 (or next calendar year)

- [ ] **Test 3: Client Selection Auto-Complete**
  - Steps: Start typing client name in dropdown
  - Expected: Select2 filters list, shows matching clients only

- [ ] **Test 4: Product Search Auto-Complete**
  - Steps: Click "Add Item" > Type product name/HSN
  - Expected: Dropdown shows matching products with HSN and price

- [ ] **Test 5: Batch Selection**
  - Steps: Select product > Batch dropdown appears with available batches
  - Expected: Only unexpired batches shown with expiry dates

- [ ] **Test 6: Financial Calculations**
  - Test Case A: No discount
    - Input: 10 units @ Rs 100, GST 18%
    - Expected: Subtotal=1000, GST=180, Total=1180
  - Test Case B: With discount %
    - Input: 10 units @ Rs 100, 10% discount, GST 18%
    - Expected: Subtotal=1000, Discount=100, After Discount=900, GST=162, Total=1062
  - Test Case C: Multiple items
    - Input: Item1 (5 x 100, GST 18%), Item2 (10 x 50, GST 5%)
    - Expected: Correct aggregated GST calculation

- [ ] **Test 7: Form Validation**
  - Steps: Try to save without client
  - Expected: Error validation message
  - Steps: Try to save with no items
  - Expected: Error validation message
  - Steps: Try to save with negative quantity
  - Expected: Form prevents submission

- [ ] **Test 8: Edit Existing Invoice**
  - Steps: Open DRAFT invoice > Modify items > Save
  - Expected: Items replaced correctly, calculations updated
  - Steps: Try to edit non-DRAFT invoice
  - Expected: Form shows read-only or appropriate restrictions

- [ ] **Test 9: Payment Tracking**
  - Steps: Create invoice for Rs 1000 > Enter paid amount Rs 600
  - Expected:
    - Due amount = Rs 400 (auto-calculated)
    - Payment status shows "PARTIAL"
  - Steps: Enter paid amount Rs 1000
  - Expected: Payment status changes to "PAID"

### 1.4 Sales Invoice List Testing

#### Listing & Filtering

- [ ] **Test 1: Search by Invoice Number**
  - Steps: Type "INV-26-000" in search box
  - Expected: Filter shows matching invoices only

- [ ] **Test 2: Search by Client Name**
  - Steps: Type client name fragment
  - Expected: Invoices for that client shown

- [ ] **Test 3: Date Range Filter**
  - Steps: Select start date and end date
  - Expected: Only invoices within range shown

- [ ] **Test 4: Status Filter**
  - Steps: Select "DRAFT" status
  - Expected: Only DRAFT invoices shown
  - Steps: Select multiple statuses
  - Expected: Union of all selected statuses shown

- [ ] **Test 5: Payment Status Filter**
  - Steps: Filter by "UNPAID"
  - Expected: Invoices with payment_status='UNPAID' shown
  - Steps: Filter by "PAID"
  - Expected: Only fully paid invoices shown

- [ ] **Test 6: Combined Filters**
  - Steps: Status=SUBMITTED AND Payment=UNPAID AND Date Range
  - Expected: All filters applied cumulatively

#### Listing Actions

- [ ] **Test 7: Edit Button**
  - Steps: Click edit on DRAFT invoice
  - Expected: Opens edit form with all data populated

- [ ] **Test 8: Print Button**
  - Steps: Click print on invoice
  - Expected: Opens print_invoice.php with professional layout

- [ ] **Test 9: Delete Button**
  - Steps: Click delete on invoice
  - Expected: Soft delete (data preserved, deleted_at set)
  - Expected: Invoice no longer shown in list (unless filter includes deleted)

- [ ] **Test 10: Export Button** (if implemented)
  - Steps: Click export to PDF/Excel
  - Expected: File downloads with invoice data

### 1.5 Print Invoice Testing

#### Visual Layout Verification

- [ ] **Test 1: 2-Column Layout**
  - Visual Check: Bill To on left, Ship To on right
  - Alignment: Columns should be equal width
  - Content: Both should display address correctly

- [ ] **Test 2: Company Header**
  - Elements Present: Company name, address, phone, email
  - Right Side: Invoice Number, Date, Due Date, Status
  - Professional Appearance: Clean 2-column grid

- [ ] **Test 3: Items Table**
  - Columns: SL, Medicine, HSN, Qty, Rate, PTR (HIDDEN), GST %, Total
  - Content: All items from invoice displayed
  - Borders: Professional 1px solid borders

- [ ] **Test 4: PTR Column Hidden**
  - Screen View: PTR column visible with value
  - Print Preview: PTR column NOT visible (hidden)
  - Print Output: When printed to paper/PDF, PTR column absent

- [ ] **Test 5: Financial Summary Box**
  - Display:
    - Subtotal correct
    - Discount shown if applicable
    - GST amount and rate displayed
    - Grand Total in bold/prominent
  - Location: Bottom of items table, properly aligned

- [ ] **Test 6: Signature Lines**
  - 3-Column Layout: Prepared By | Authorized By | Received By
  - Each: Line for signature, space for stamp
  - Professional appearance with underlines

#### Print Output Quality

- [ ] **Test 7: A4 Paper Size**
  - Physical Print: Prints on one A4 page (210mm x 297mm)
  - Fit: No content cut off, no blank pages

- [ ] **Test 8: Black & White Rendering**
  - Colors: No colors in printed output, pure B&W
  - Text: All text legible in B&W
  - Borders: Clear, not gray

- [ ] **Test 9: Font Rendering**
  - Font: Courier New monospace (professional, consistent)
  - Size: All text legible
  - Alignment: Properly aligned tables

- [ ] **Test 10: No Sidebars/Headers**
  - Print: Header/footer not included
  - Margins: Minimal (invoice content fills page)
  - Buttons: "Print" and "Back" buttons hidden

- [ ] **Test 11: Browser Print Dialog**
  - Steps: Click Print button > Print dialog opens
  - Expected: Print preview shows invoice correctly
  - Expected: Print to PDF produces clean output

#### Data Accuracy on Print

- [ ] **Test 12: Invoice Number Correct**
- [ ] **Test 13: Client Details Correct**
- [ ] **Test 14: All Items Present**
- [ ] **Test 15: Calculations Match Form**

### 1.6 Database Backend Testing

#### Security Testing

- [ ] **Test 1: SQL Injection Prevention**
  - SQL Injection Attempt: `"; DROP TABLE clients; --`
  - In field: Client search, product search, etc.
  - Expected: No SQL executed, input treated as literal string
  - Verification: Check prepared statements in all handlers

- [ ] **Test 2: XSS Prevention**
  - XSS Attempt: `<script>alert('XSS')</script>` in client name
  - Expected: Script not executed, displayed as text
  - Verification: Check htmlspecialchars() usage

- [ ] **Test 3: Unauthorized Access Prevention**
  - Steps: Try to directly access AJAX handlers without session
  - Expected: Handlers check $\_SESSION or redirect to login

- [ ] **Test 4: CSRF Prevention**
  - Steps: Submit forms from external sites
  - Expected: Validation prevents submission (if CSRF token implemented)

#### Transaction Testing

- [ ] **Test 1: Invoice Creation Transaction**
  - Scenario: Create invoice with 5 items
  - Recovery Test: Simulate database error mid-transaction
  - Expected: Either all records inserted or none (no partial data)

- [ ] **Test 2: Invoice Update Transaction**
  - Steps: Update invoice items list
  - Expected: Old items deleted and new items added atomically

- [ ] **Test 3: Rollback on Error**
  - Scenario: Insert invoice, then item FK foreign key fails
  - Expected: Entire transaction rolled back, invoice not created

#### Data Integrity Testing

- [ ] **Test 1: Audit Trail Completeness**
  - Created Invoice Check: created_by, created_at populated
  - Updated Invoice Check: updated_by, updated_at changed
  - Submitted Invoice Check: submitted_by, submitted_at populated
  - Verification: Audit columns tracking all changes

- [ ] **Test 2: Soft Delete Preservation**
  - Steps: Delete invoice > Query database
  - Expected: Record still exists with deleted_at timestamp
  - Expected: Query filters with `WHERE deleted_at IS NULL`

- [ ] **Test 3: Foreign Key Integrity**
  - Steps: Try to delete client with active invoice
  - Expected: Handler checks and prevents deletion (business logic)

### 1.7 Performance Testing

- [ ] **Test 1: Invoice List Loading**
  - Scenario: 100+ invoices in database
  - Expected: List loads in <2 seconds

- [ ] **Test 2: Form Auto-Complete**
  - Scenario: 1000+ products in database
  - Steps: Type in product search
  - Expected: Results show in <500ms

- [ ] **Test 3: Print Generation**
  - Steps: Click print on invoice with 50 items
  - Expected: Print page loads in <1 second

### 1.8 Cross-Browser Compatibility

- [ ] **Chrome**: Test all workflows
- [ ] **Firefox**: Test all workflows
- [ ] **Edge**: Test all workflows
- [ ] **Safari**: Test all workflows (if applicable)

---

## 2. Sample Test Data

### Test Client 1: Sunrise Pharmacy

```
Code: CL001
Name: Sunrise Pharmacy
Contact: 9876543210
Email: sunrise@pharmacy.com
Business Type: Retail
Credit Limit: 500,000
Payment Terms: Net 30
Status: Active
```

### Test Client 2: Apollo Distribution

```
Code: CL002
Name: Apollo Distribution
Contact: 8765432109
Email: apollo@dist.com
Business Type: Distributor
Credit Limit: 1,000,000
Payment Terms: 2/10 Net 30
Status: Active
```

### Test Client 3: City Hospital

```
Code: CL003
Name: City Hospital
Contact: 7654321098
Email: procurement@cityhospital.com
Business Type: Hospital
Credit Limit: 2,000,000
Payment Terms: Net 60
Status: Active
```

### Test Client 4: Dr. Sharma Clinic

```
Code: CL004
Name: Dr. Sharma Clinic
Contact: 6543210987
Email: clinic@sharma.com
Business Type: Clinic
Credit Limit: 250,000
Payment Terms: Net 15
Status: Active
```

### Test Product Invoice Workflow

1. Select any product from existing inventory
2. Quantity: 10 units
3. Unit Rate (Selling Price): Rs 100-500 (varies by product)
4. PTR (Purchase Rate): Should auto-populate from product.purchase_rate
5. GST Rate: 5% or 18% (varies by product)

---

## 3. Test Execution Procedure

### Step 1: Pre-Flight Checks

```sql
-- Verify all tables exist
SHOW TABLES LIKE 'clients';
SHOW TABLES LIKE 'sales_invoices';
SHOW TABLES LIKE 'sales_invoice_items';
SHOW TABLES LIKE 'invoice_sequence';

-- Verify sample data loaded
SELECT COUNT(*) as client_count FROM clients WHERE status='Active';
-- Expected: 4

-- Verify columns exist
DESCRIBE clients;
DESCRIBE sales_invoices;
DESCRIBE sales_invoice_items;
```

### Step 2: Functional Testing

1. Open browser to `http://localhost/Satyam_Clinical/clients_list.php`
2. Run all 5 test cases for Clients Module
3. Open `http://localhost/Satyam_Clinical/sales_invoice_form.php`
4. Run all 9 test cases for Invoice Creation
5. Open `http://localhost/Satyam_Clinical/sales_invoice_list.php`
6. Run all 10 test cases for Invoice Listing
7. Create sample invoice and click Print
8. Run all 12 test cases for Print Output

### Step 3: Security Testing

1. Run SQL injection tests on all user input fields
2. Verify XSS prevention on client names/product names
3. Verify session/authorization checks

### Step 4: Data Validation

1. Create 5 invoices
2. Verify invoice sequence incremented correctly
3. Print each invoice
4. Verify PTR hidden only on print, visible on form
5. Compare calculations with manual math

---

## 4. Expected Results Summary

| Test Area          | Total Tests | Expected Pass | Priority |
| ------------------ | ----------- | ------------- | -------- |
| Database Integrity | 5           | 5             | CRITICAL |
| Clients Module     | 5           | 5             | CRITICAL |
| Invoice Form       | 9           | 9             | CRITICAL |
| Invoice List       | 10          | 10            | CRITICAL |
| Print Template     | 12          | 12            | CRITICAL |
| Security           | 4           | 4             | HIGH     |
| Transactions       | 3           | 3             | HIGH     |
| Data Integrity     | 3           | 3             | HIGH     |
| Performance        | 3           | 3             | MEDIUM   |
| Compatibility      | 4           | 4             | MEDIUM   |
| **TOTAL**          | **58**      | **58**        | -        |

---

## 5. Sign-Off

**Testing Initiated:** [Date to be filled]  
**Testing Completed:** [Date to be filled]  
**Total Tests Run:** [Count to be filled]  
**Tests Passed:** [Count to be filled]  
**Tests Failed:** [Count to be filled]  
**Tester Name:** [Name to be filled]  
**Approved By:** [Name to be filled]

**Status:** ⏳ TESTING IN PROGRESS

---

## 6. Open Action Items

- [ ] Execute all tests and document results
- [ ] Address any failing tests
- [ ] Performance optimization if needed
- [ ] Update end-user documentation
- [ ] Create system admin guide
- [ ] Plan for production deployment

---

**Next Phase:** System goes live for production use after 100% test pass and sign-off
