# SALES INVOICE MODULE REFACTORING - SCHEMA INSPECTION & PLAN

**Generated:** 2026-02-18 (Post Schema Inspection)

---

## 1. LIVE SCHEMA INSPECTION RESULTS

### Current Database State

#### ✓ Tables Found

- **orders** - Main invoice table (29 fields)
- **order_item** - Line items table (9 fields) - EXISTS! Not serialized
- **customers** - Client management table (11 fields)
- **product** - Medicine inventory (15 fields)
- **purchase_invoices** - PO invoices (reference implementation)
- **purchase_invoice_items** - PO invoice items (reference implementation)

#### ✗ Issues Identified

1. **Customers table exists but underutilized**: 0 customers currently stored
2. **PTR (Purchase Rate) field**: Not in product table - using `purchase_rate` from response object (populated by fetchSelectedProduct.php)
3. **Invoice number naming broken**: Uses `INV-YY-ID` format (not annual reset) - need `INV-YY-NNNNN`
4. **Orders table has mixed fields**: Some new status columns (order_status, payment_status enums) mixed with old payment tracking

---

## 2. CURRENT ORDERS TABLE STRUCTURE

```
Field Name              | Type              | Null | Notes
---                     | ---               | ---  | ---
id                      | int(10) unsigned  | NO   | Primary key
customer_id             | int(10) unsigned  | YES  | NOT LINKED - should use this!
order_number            | varchar(50)       | NO   | Broken format (INV-YY-ID)
orderDate               | date              | NO   | Order creation date
due_date                | date              | YES  |
payment_terms           | varchar(100)      | YES  |
clientName              | varchar(255)      | NO   | REDUNDANT - should use customers.name
projectName             | varchar(100)      | YES  | Not used in pharmacy
clientContact           | varchar(20)       | YES  | REDUNDANT - should use customers.contact
address                 | text              | YES  | REDUNDANT - should use customers.address
subTotal                | decimal(12,2)     | NO   |
discount                | decimal(10,2)     | NO   |
discountPercent         | decimal(5,2)      | YES  |
gstPercent              | int(11)           | YES  | Should be decimal
gstn                    | decimal(12,2)     | NO   | GST amount
grandTotalValue         | decimal(12,2)     | NO   |
paid                    | decimal(12,2)     | YES  |
dueValue                | decimal(12,2)     | YES  |
paymentType             | varchar(50)       | YES  | Payment method
paymentStatus           | enum              | YES  | OLD: 'Pending','PartialPaid','Paid','Cancelled'
paymentPlace            | varchar(100)      | YES  | 'In India' / 'Out Of India'
delete_status           | tinyint(1)        | YES  | Soft delete flag
created_by              | int(10) unsigned  | YES  |
created_at              | timestamp         | NO   |
updated_at              | timestamp         | NO   |
order_status            | enum              | YES  | NEW: 'DRAFT','CONFIRMED','FULFILLED','CANCELLED'
payment_status          | enum              | YES  | NEW: 'UNPAID','PARTIAL','PAID'
submitted_at            | datetime          | YES  |
fulfilled_at            | datetime          | YES  |
updated_by              | int(10) unsigned  | YES  |
```

**Issue**: Table has MIXED old and new status tracking. Needs consolidation.

---

## 3. CURRENT ORDER_ITEM TABLE STRUCTURE

```
Field Name              | Type              | Null
---                     | ---               | ---
id                      | int(10) unsigned  | NO
order_id                | int(10) unsigned  | NO
product_id              | int(10) unsigned  | NO
batch_id                | int(10) unsigned  | YES
quantity                | int(10) unsigned  | NO
rate                    | decimal(10,2)     | NO
total                   | decimal(12,2)     | NO
order_item_state        | tinyint(1)        | YES
added_date              | date              | YES
```

**Current State**: Empty (0 items), structure is sound

---

## 4. CUSTOMERS TABLE (Under-utilized)

```
Field Name              | Type              | Null
---                     | ---               | ---
customer_id             | int(10) unsigned  | NO
customer_code           | varchar(50)       | YES
name                    | varchar(255)      | NO
contact                 | varchar(20)       | YES
email                   | varchar(100)      | YES
address                 | text              | YES
gstin                   | varchar(20)       | YES
credit_limit            | decimal(12,2)     | YES
outstanding_balance     | decimal(12,2)     | YES
status                  | enum              | YES - 'ACTIVE','INACTIVE'
created_at              | timestamp         | NO
updated_at              | timestamp         | NO
```

**Current State**: Empty (0 customers), but FULLY PREPARED for use
**Opportunity**: Link orders.customer_id to customers.customer_id instead of using string fields

---

## 5. PRODUCT TABLE PRICING (PTR Source Unknown)

```
Price-Related Fields:
  • expected_mrp: decimal(14,2)  - Listed MRP
  • gst_rate: decimal(5,2)       - GST %

MISSING:
  • purchase_rate / ptr:      Not in schema! But referenced in fetchSelectedProduct.php
                              → Need to ADD column for PTR
```

**Investigation Needed**: Where is PTR coming from in fetchSelectedProduct.php response?

---

## 6. REFERENCE IMPLEMENTATION - PURCHASE_INVOICE_ITEMS

The purchase_invoices module (completed successfully) has professional structure:

```
Key Fields:
  • unit_cost: decimal(14,4)        - Base cost
  • effective_rate: decimal(14,4)   - Final rate
  • mrp: decimal(14,2)              - MRP
  • our_selling_price: decimal(14,2) - Sales price (THIS is what PTR should be!)
  • margin_amount: decimal(14,2)    - Profit margin
  • margin_percent: decimal(6,2)    - Margin %
  • supplier_quoted_mrp: decimal(14,2) - Supplier MRP

  Tax Handling:
  • cgst_percent, sgst_percent, igst_percent
  • cgst_amount, sgst_amount, igst_amount
```

**Lesson**: Sales invoice items should have similar structure for professional tracking.

---

## 7. CURRENT add-order.php ISSUES

### Inventory Check

| Issue                 | Current                                     | Required                          |
| --------------------- | ------------------------------------------- | --------------------------------- |
| **Client Management** | Free text input (clientName, clientContact) | Link to customers table           |
| **Invoice Numbering** | INV-YY-ID (uses order ID)                   | INV-YY-NNNNN with annual reset    |
| **PTR Display**       | Fetched via AJAX, shown with CSS no-print   | Needs schema column to store      |
| **Validation**        | Client-side only                            | Server-side + prepared statements |
| **Structure**         | Massive single file (1561 lines)            | Modular, clean MVC                |
| **Print Page**        | invoiceprint.php (unknown structure)        | Professional print template       |
| **Search**            | Product autocomplete only                   | Clients + products + invoices     |
| **Payment Terms**     | Text field                                  | Enum or reference                 |

---

## 8. CURRENT Order.php ISSUES

| Issue            | Current             | Required                                   |
| ---------------- | ------------------- | ------------------------------------------ |
| **Sorting**      | None                | By date, amount, client name               |
| **Filtering**    | None                | Date range, client, status, payment status |
| **Columns**      | 5 basic columns     | Add status, amount, GST %, payment         |
| **Actions**      | Edit, delete, print | + View details, duplicate invoice          |
| **Bulk Actions** | None                | Export, marked paid, bulk status           |

---

## 9. REFACTORING SCOPE

### Phase 1: Schema Cleanup & Preparation [CRITICAL]

- [ ] Add `purchase_rate` column to product table (for PTR storage)
- [ ] Backup existing orders (safety measure)
- [ ] Create migration: orders → sales_invoices (new schema)
- [ ] Verify customers.customer_id relationships
- [ ] Create proper invoice_number sequence table (for YY-NNNNN generation)

### Phase 2: Clients Module [FOUNDATION]

- [ ] Create clients_list.php (list all customers)
- [ ] Create clients_form.php (CRUD form)
- [ ] Create php_action/createClient.php (prepared statements)
- [ ] Create php_action/updateClient.php
- [ ] Create php_action/deleteClient.php
- [ ] Create php_action/fetchClients.php (for AJAX search)
- [ ] Implement client search in form auto-complete

### Phase 3: Sales Invoice Refactor [CORE]

- [ ] Refactor add-order.php → sales_invoice_form.php (modern structure)
- [ ] Link client selection to customers table via dropdown + search
- [ ] Implement INV-YY-NNNNN auto-generation
- [ ] Add server-side validation (all calculations verified)
- [ ] Refactor Order.php → sales_invoice_list.php
- [ ] Add search & filter UI

### Phase 4: Backend Handlers [CRITICAL]

- [ ] Refactor php_action/order.php → createSalesInvoice.php (prepared statements)
- [ ] Refactor php_action/editOrder.php → updateSalesInvoice.php
- [ ] Refactor php_action/removeOrder.php → deleteSalesInvoice.php
- [ ] Refactor php_action/fetchOrder.php → fetch SalesInvoices.php (DataTable)
- [ ] Refactor php_action/fetchSelectedProduct.php (include PTR)
- [ ] Create php_action/getSalesInvoices.php (search/filter)

### Phase 5: Professional Print Template [PRESENTATION]

- [ ] Create print_invoice.php (mirror print_po.php structure)
- [ ] 2-column layout: Bill To | Ship To
- [ ] Company header with GST/GSTIN
- [ ] Professional B&W, A4 format
- [ ] Items table with PTR hidden (CSS display:none)
- [ ] Signature lines for authorized personnel
- [ ] Footer with payment terms, company details

### Phase 6: Advanced Features [POLISH]

- [ ] Date range filtering
- [ ] Client search & multi-filter
- [ ] Invoice status workflow (optional)
- [ ] Duplicate invoice creation
- [ ] Invoice amendment/revision tracking

---

## 10. ESTIMATED WORK BREAKDOWN

| Component                   | Est. Time      | Dependencies       |
| --------------------------- | -------------- | ------------------ |
| Schema migration            | 30 min         | None               |
| Clients CRUD module         | 45 min         | Schema migration   |
| Sales invoice form refactor | 60 min         | Clients module     |
| Sales invoice list refactor | 30 min         | Clients module     |
| Backend handlers (5 files)  | 60 min         | Schema migration   |
| Print template              | 40 min         | Sales invoice CRUD |
| Search & filters            | 45 min         | Backend handlers   |
| Testing & debugging         | 60 min         | All above          |
| **TOTAL**                   | **≈5-6 hours** | Sequential         |

---

## 11. RECOMMENDED EXECUTION ORDER

1. **Schema Migration** (create migration script)
2. **Clients Module** (CRUD only, no fancy features)
3. **Sales Invoice CRUD** (using clients module)
4. **Backend Handlers** (one handler at a time)
5. **Professional Print Template** (after CRUD stable)
6. **Search & Filters** (final polish)
7. **End-to-End Testing**

---

## 12. CRITICAL DECISIONS NEEDED

**Q1: PTR Definition**

- Is PTR = "our_selling_price" from purchase_invoices module?
- Or should PTR be added as new column to product table?
- Or should PTR be calculated from MRP?

**Q2: Invoice Number Format**

- Confirmed: `INV-YY-NNNNN` with annual reset
- Question: Reset on calendar year (Jan 1) or financial year?

**Q3: Client Data Handling**

- Use existing customers table completely?
- Or create separate "sales_invoice_clients" for pharmacy-specific data?

**Q4: Payment Workflow**

- Need "order_status" enum workflow? (DRAFT → SUBMITTED → FULFILLED → CLOSED)
- Or keep current payment_status only?

**Q5: Backward Compatibility**

- Keep OLD orders table as archive?
- Or migrate all data to new sales_invoices table?

---

## 13. DATABASE MIGRATION STRATEGY

### Safety First Approach:

```sql
-- 1. Create NEW schema
CREATE TABLE sales_invoices (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(50) UNIQUE NOT NULL,
  customer_id INT(10) UNSIGNED NOT NULL,
  invoice_date DATE NOT NULL,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  due_date DATE,

  -- Financial
  subtotal DECIMAL(14,2) NOT NULL,
  discount_amount DECIMAL(14,2),
  discount_percent DECIMAL(5,2),
  gst_percent INT,
  gst_amount DECIMAL(14,2) NOT NULL,
  grand_total DECIMAL(14,2) NOT NULL,

  -- Payment
  paid_amount DECIMAL(14,2),
  due_amount DECIMAL(14,2),
  payment_type VARCHAR(50),
  payment_status ENUM('UNPAID','PARTIAL','PAID') DEFAULT 'UNPAID',

  -- Status & Tracking
  invoice_status ENUM('DRAFT','SUBMITTED','FULFILLED','CANCELLED') DEFAULT 'DRAFT',
  payment_place VARCHAR(50),

  -- Audit
  created_by INT(10) UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_by INT(10) UNSIGNED,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  submitted_at DATETIME,
  fulfilled_at DATETIME,
  deleted_at DATETIME,

  FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_invoice_number (invoice_number),
  INDEX idx_customer_id (customer_id),
  INDEX idx_invoice_date (invoice_date),
  INDEX idx_invoice_status (invoice_status)
);

-- 2. Create invoice_items with proper PTR handling
CREATE TABLE sales_invoice_items (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT(10) UNSIGNED NOT NULL,
  product_id INT(10) UNSIGNED NOT NULL,
  batch_id INT(10) UNSIGNED,

  -- Pricing & Quantity
  quantity DECIMAL(14,3) NOT NULL,
  unit_rate DECIMAL(14,4) NOT NULL,
  mrp DECIMAL(14,2),
  purchase_rate DECIMAL(14,2),  -- THIS IS PTR - Internal cost, not shown on print

  -- Calculations
  line_subtotal DECIMAL(14,2),
  gst_rate DECIMAL(5,2),
  gst_amount DECIMAL(14,2),
  line_total DECIMAL(14,2),

  -- Audit
  added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES product(product_id),
  INDEX idx_invoice_id (invoice_id)
);

-- 3. Rename old table for safety
ALTER TABLE orders RENAME TO orders_legacy;
ALTER TABLE order_item RENAME TO order_item_legacy;
```

---

## 14. KEY TECHNICAL NOTES

### PTR (Pharmaceutical Trade Rate) Strategy

- **Display**: Shown in UI during invoice creation (read-only field)
- **Print**: Hidden with CSS `display:none` (PTR visible only to staff)
- **Storage**: In sales_invoice_items.purchase_rate column
- **Source**: Fetched from product + batch data during product selection
- **Use Case**: Internal costing, margin calculation, profitability analysis

### Auto-Invoice Number Generation

```php
// Pattern: INV-26-00001 (Year 26 = 2026, sequence 00001)
// Resets to 00001 every January 1

function generateInvoiceNumber($connect) {
  $year = date('y');
  $lastNum = $connect->query("
    SELECT MAX(CAST(SUBSTRING(invoice_number, -5) AS UNSIGNED)) as seq
    FROM sales_invoices
    WHERE invoice_number LIKE 'INV-$year-%'
  ")->fetch_assoc()['seq'] ?? 0;

  return 'INV-' . $year . '-' . str_pad($lastNum + 1, 5, '0', STR_PAD_LEFT);
}
```

### 2-Column Layout Strategy

```
┌─────────────────────────────────────┐
│       COMPANY HEADER / LOGO         │
├──────────────────┬──────────────────┤
│   BILL TO        │    SHIP TO       │
│ (Customer addr)  │  (Delivery addr) │
├──────────────────┴──────────────────┤
│ Invoice Items Table                  │
├──────────────────────────────────────┤
│  Subtotal | GST | Total (Right align)
├──────────────────────────────────────┤
│  Signature Lines:                    │
│  Prepared By | Authorized By | Date │
└──────────────────────────────────────┘
```

---

## 15. NEXT IMMEDIATE ACTION

**User must confirm before proceeding:**

1. ✅ **Schema inspection complete** - All tables identified
2. ✅ **Gap analysis complete** - 10+ issues documented
3. ✅ **Migration plan ready** - Safe backward-compatible approach
4. ⏸️ **AWAITING DECISION**:
   - Do you want to proceed with Phase 1 (Schema Migration)?
   - Estimated time: 5-6 hours for complete refactoring
   - Do you want to pause or continue immediately?

---

**SESSION NOTE**: This comprehensive plan is designed for resumption. Even if suspended, you can continue from any phase. All decisions documented above to avoid re-analysis.
