# SALES INVOICE REFACTOR - MASTER INDEX & NAVIGATION

**Last Updated:** February 2026  
**Project Status:** Phases 1-5 COMPLETE ✅ | Phase 6 TESTING READY  
**Total Deliverables:** 25+ files | 5500+ lines code | 5 database tables

---

## 📋 QUICK NAVIGATION

**I need to...**

### 👤 **Manage Clients (Create/Edit/Delete Customers)**

→ Start here: [clients_list.php](clients_list.php)  
→ Add new: [clients_form.php](clients_form.php)  
→ Backend API: [php_action/createClient.php](php_action/createClient.php) [updateClient.php](php_action/updateClient.php) [deleteClient.php](php_action/deleteClient.php)  
→ Documentation: [COMPLETE_SALES_INVOICE_DOCUMENTATION.md#phase-2](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#phase-2-clients-crud-module-✅)

---

### 📄 **Create or Manage Sales Invoices**

→ Create invoice: [sales_invoice_form.php](sales_invoice_form.php)  
→ View all invoices: [sales_invoice_list.php](sales_invoice_list.php)  
→ Print invoice: [print_invoice.php](print_invoice.php)  
→ Backend APIs:

- Generate invoice number: [php_action/getInvoiceNumber.php](php_action/getInvoiceNumber.php)
- Search products: [php_action/searchProductsInvoice.php](php_action/searchProductsInvoice.php)
- Fetch product details: [php_action/fetchProductInvoice.php](php_action/fetchProductInvoice.php)
- Fetch invoices: [php_action/fetchSalesInvoices.php](php_action/fetchSalesInvoices.php)
- Create invoice: [php_action/createSalesInvoice.php](php_action/createSalesInvoice.php)
- Update invoice: [php_action/updateSalesInvoice.php](php_action/updateSalesInvoice.php)
- Delete invoice: [php_action/deleteSalesInvoice.php](php_action/deleteSalesInvoice.php)  
  → Documentation: [COMPLETE_SALES_INVOICE_DOCUMENTATION.md#phase-3](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#phase-3-sales-invoice-form--listing-✅)

---

### 🖨️ **Print Professional Invoices**

→ Print formatter: [print_invoice.php](print_invoice.php)  
→ Features: 2-column layout, A4 format, B&W, PTR hidden  
→ Documentation: [COMPLETE_SALES_INVOICE_DOCUMENTATION.md#phase-5](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#phase-5-professional-print-template-✅)

---

### 🧪 **Test the System**

→ Quick test (45 min): [PHASE_6_QUICK_TEST.md](PHASE_6_QUICK_TEST.md)  
→ Full test suite (3-4 hrs): [PHASE_6_TESTING_GUIDE.md](PHASE_6_TESTING_GUIDE.md)  
→ Deployment checklist: [PROJECT_DELIVERY_SUMMARY.md](PROJECT_DELIVERY_SUMMARY.md)

---

### 📚 **Understand the Architecture**

→ Complete documentation: [COMPLETE_SALES_INVOICE_DOCUMENTATION.md](COMPLETE_SALES_INVOICE_DOCUMENTATION.md)  
→ Database schema: [COMPLETE_SALES_INVOICE_DOCUMENTATION.md#phase-1](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#phase-1-database-schema-migration-✅)  
→ Technical specs: [COMPLETE_SALES_INVOICE_DOCUMENTATION.md#technical-specifications-summary](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#technical-specifications-summary)

---

### 🚀 **Deploy to Production**

→ Deployment guide: [PROJECT_DELIVERY_SUMMARY.md#deployment-instructions](PROJECT_DELIVERY_SUMMARY.md#deployment-instructions)  
→ Sign-off checklist: [PROJECT_DELIVERY_SUMMARY.md#pre-deployment-verification-checklist](PROJECT_DELIVERY_SUMMARY.md#pre-deployment-verification-checklist)  
→ Rollback plan: [PROJECT_DELIVERY_SUMMARY.md#rollback-plan-if-issues-found-post-deployment](PROJECT_DELIVERY_SUMMARY.md#rollback-plan-if-issues-found-post-deployment)

---

### 🔧 **Troubleshoot or Debug**

→ Common issues & fixes: [COMPLETE_SALES_INVOICE_DOCUMENTATION.md#4-problem-resolution](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#4-problem-resolution)  
→ Database queries to verify: [PHASE_6_TESTING_GUIDE.md#1-1-database-integrity-tests](PHASE_6_TESTING_GUIDE.md#1-1-database-integrity-tests)  
→ Expected database structure: [COMPLETE_SALES_INVOICE_DOCUMENTATION.md#1-clients-table-new---renamed-from-customers](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#table-1-clients-new---renamed-from-customers)

---

## 📂 FILE STRUCTURE & LOCATIONS

### **Core UI Files** (Main entry points - User-facing)

```
ROOT/
├── clients_list.php                   # List all clients with search/filter
├── clients_form.php                   # Add/edit client form (5 sections)
├── sales_invoice_form.php             # Create/edit invoice (main form)
├── sales_invoice_list.php             # List all invoices with filters
└── print_invoice.php                  # Professional invoice print template
```

### **Backend AJAX Handlers** (API endpoints)

```
ROOT/php_action/
├── createClient.php                   # INSERT new client
├── updateClient.php                   # UPDATE existing client
├── deleteClient.php                   # DELETE/soft-delete client
├── fetchClients.php                   # FETCH all clients (for dropdown/list)
├── getInvoiceNumber.php               # Generate INV-YY-NNNNN
├── searchProductsInvoice.php          # Autocomplete product search
├── fetchProductInvoice.php            # Get product details with PTR
├── fetchSalesInvoices.php             # Fetch invoice list
├── createSalesInvoice.php             # INSERT new invoice with items
├── updateSalesInvoice.php             # UPDATE invoice and items
└── deleteSalesInvoice.php             # Soft delete invoice
```

### **Database Migration & Setup**

```
ROOT/
├── php_action/complete_sales_invoice_schema.php  # Main migration (run once)
└── add_sample_clients.php                        # Load 4 test clients (run once)
```

### **Documentation Files** (Reference & Testing)

```
ROOT/
├── PROJECT_DELIVERY_SUMMARY.md                   # Executive summary, deployment
├── PHASE_6_QUICK_TEST.md                         # 45-min hands-on testing guide
├── PHASE_6_TESTING_GUIDE.md                      # 58-point comprehensive test
├── COMPLETE_SALES_INVOICE_DOCUMENTATION.md       # Architecture & technical specs
└── (This File) MASTER_INDEX.md                   # Navigation guide
```

### **Legacy/Backup Files**

```
DATABASE (Auto-created):
├── clients_legacy_2026-02-23           # Backup of old customers table
├── orders_legacy_2026-02-23            # Backup of old orders table
└── order_item_legacy_2026-02-23        # Backup of old order_item table
```

---

## 🗃️ DATABASE SCHEMA QUICK REFERENCE

### **5 Main Tables**

| Table                   | Rows      | Purpose                      | Key Columns                                                                        |
| ----------------------- | --------- | ---------------------------- | ---------------------------------------------------------------------------------- |
| **clients**             | 4 test    | Customer/pharmacy management | client_id, client_code, name, business_type, credit_limit                          |
| **sales_invoices**      | 0 (ready) | Main invoice records         | invoice_id, invoice_number, client_id, grand_total, invoice_status, payment_status |
| **sales_invoice_items** | 0 (ready) | Line items per invoice       | item_id, invoice_id, product_id, quantity, unit_rate, **purchase_rate** (PTR)      |
| **invoice_sequence**    | 1         | Auto-number generation       | year, next_number, last_reset                                                      |
| **product** (modified)  | Existing  | Product master data          | ...existing fields... + **purchase_rate** (PTR column added)                       |

### **Quick DB Queries**

```sql
-- Verify clients loaded (should show 4)
SELECT COUNT(*) FROM clients WHERE status='Active';

-- Verify invoice sequence initialized
SELECT * FROM invoice_sequence;

-- Check all invoices created so far
SELECT invoice_number, client_id, grand_total, invoice_status
FROM sales_invoices
WHERE deleted_at IS NULL;

-- View all line items for specific invoice
SELECT sii.*, p.name, sii.purchase_rate as PTR
FROM sales_invoice_items sii
LEFT JOIN product p ON sii.product_id = p.product_id
WHERE sii.invoice_id = [INVOICE_ID];
```

---

## 🔐 SECURITY IMPLEMENTATION

**SQL Injection Prevention:**

- ✅ ALL 11+ database queries use prepared statements
- ✅ User input never concatenated into SQL
- Check: Every php_action file uses `$stmt->bind_param()`

**XSS Prevention:**

- ✅ Output escaped with `htmlspecialchars()`
- ✅ FormData sanitized before insertion
- Check: print_invoice.php uses `htmlspecialchars()` for all dynamic content

**Data Integrity:**

- ✅ Soft deletes with `deleted_at` timestamp
- ✅ Audit trail (created_by, updated_by, timestamps)
- ✅ Transaction support (BEGIN → COMMIT/ROLLBACK)
- Check: All CREATE/UPDATE operations wrapped in transactions

---

## 🎯 KEY FEATURE IMPLEMENTATION DETAILS

### **PTR (Purchase Trade Rate) Management**

**Where PTR is stored:**

- `product.purchase_rate` - Master rate for each product
- `sales_invoice_items.purchase_rate` - Specific rate for each line item

**Where PTR is visible/hidden:**

- ✅ **VISIBLE** in [sales_invoice_form.php#L...](sales_invoice_form.php) (red background, "For Internal Use Only")
- ✅ **HIDDEN** in [print_invoice.php](print_invoice.php) (CSS `.ptr-column { display:none }` in @media print)

**Implementation verification:**

```php
// In sales_invoice_form.php - PTR visible
<td class="ptr-column">
  <input type="number" name="purchase_rate"
         value="<?php echo $row['purchase_rate']; ?>"
         style="background-color: #ffe6e6;" readonly>
  <small>For Internal Use Only</small>
</td>

// In print_invoice.php - PTR hidden on print
@media print {
  .ptr-column { display:none !important; }
}
```

### **Invoice Number Generation (INV-YY-NNNNN)**

**Format breakdown:**

- `INV` - Static prefix
- `YY` - Last 2 digits of year (26 for 2026)
- `NNNNN` - 5-digit sequential (00001-99999)

**Example sequence:**

```
INV-26-00001  (First invoice of 2026)
INV-26-00002  (Second invoice of 2026)
INV-26-00003  (Third invoice of 2026)
...
INV-27-00001  (First invoice of 2027, auto-reset on Jan 1)
```

**Implementation location:** [php_action/getInvoiceNumber.php](php_action/getInvoiceNumber.php)

### **2-Column Address Layout (Professional Pharmacy Standard)**

**Visual layout (print_invoice.php):**

```
┌─────────────────────────┬──────────────────────────┐
│                         │                          │
│ BILL TO                 │ SHIP TO                  │
│ Sunrise Pharmacy        │ Aone Pharmacy - Branch 1 │
│ Ground Floor, Main Rd   │ First Floor, Sub Rd      │
│ Mumbai, Maharashtra     │ Pune, Maharashtra        │
│ 400001                  │ 411001                   │
│                         │                          │
└─────────────────────────┴──────────────────────────┘
```

---

## 📊 TESTING QUICK REFERENCE

### **Quick Test (45 minutes) - Start Here First**

**File:** [PHASE_6_QUICK_TEST.md](PHASE_6_QUICK_TEST.md)  
**Workflow:**

1. Pre-flight database checks (5 min)
2. Create first invoice (10 min)
3. Verify database storage (2 min)
4. Print invoice and verify PTR hidden (3 min)
5. Create second invoice (5 min)
6. Search & filter validation (3 min)

**Success Criteria:** 17/17 checkpoints pass ✅

### **Full Test Suite (3-4 hours) - Before Production**

**File:** [PHASE_6_TESTING_GUIDE.md](PHASE_6_TESTING_GUIDE.md)  
**Test Categories:**

- Database integrity (5 tests)
- Clients module (5 tests)
- Invoice form (9 tests)
- Invoice listing (10 tests)
- Print template (12 tests)
- Security (4 tests)
- Transactions (3 tests)
- Data integrity (3 tests)

**Success Criteria:** 58/58 tests pass ✅

### **Critical Checkpoints (Must Pass)**

1. ✅ Invoice number auto-generates as INV-26-00001
2. ✅ PTR visible in form, HIDDEN on print
3. ✅ Financial calculations accurate
4. ✅ Database queries all prepared statements
5. ✅ Print template renders on A4, B&W
6. ✅ 4 sample clients loaded
7. ✅ Invoice sequence increments correctly
8. ✅ Soft delete preserves audit trail

---

## 🚀 DEPLOYMENT QUICK START

### **For New Installation (First Time):**

```bash
# 1. Copy files to web root
# All 25+ PHP files go to /var/www/html or equivalent

# 2. Create database and run migration
# Execute: http://localhost/Satyam_Clinical/php_action/complete_sales_invoice_schema.php

# 3. Load sample test data
# Execute: http://localhost/Satyam_Clinical/add_sample_clients.php

# 4. Verify setup
# Open: http://localhost/Satyam_Clinical/sales_invoice_form.php
# Should show: Invoice Number = INV-26-00001 (auto-filled)
# Should show: 4 clients in dropdown (Sunrise, Apollo, City Hospital, Dr. Sharma)

# Success = Ready for Phase 6 testing!
```

### **For Updates (After Initial Deployment):**

```bash
# 1. Backup existing database
BACKUP: customers, orders, order_item tables

# 2. Copy ONLY modified/new PHP files
# Don't need to re-run schema migration (already exists)

# 3. Test in staging first
# Run PHASE_6_QUICK_TEST.md

# 4. Deploy to production
```

---

## 📞 SUPPORT & FAQ

### **Q: Invoice number shows wrong format?**

A: Check [php_action/getInvoiceNumber.php](php_action/getInvoiceNumber.php)  
 Should format as: `sprintf("INV-%02d-%05d", $year_digits, $next_number)`

### **Q: PTR is visible on print (should be hidden)?**

A: Check [print_invoice.php](print_invoice.php) for CSS rule:

```css
@media print {
  .ptr-column {
    display: none !important;
  }
}
```

### **Q: Codes, changes, or ideas before going live?**

A: Check [PHASE_6_TESTING_GUIDE.md](PHASE_6_TESTING_GUIDE.md) for complete validation

### **Q: Where do I report issues during testing?**

A: Document in test results, reference checkpoint where it failed (1-58)

### **Q: Need to customize something?**

A: Reference [COMPLETE_SALES_INVOICE_DOCUMENTATION.md](COMPLETE_SALES_INVOICE_DOCUMENTATION.md) for architecture details

---

## 🎓 LEARNING PATH FOR NEW DEVELOPERS

### **If you're new to this project, read in this order:**

1. **Start (10 min):** This file (MASTER_INDEX.md) - Get overview
2. **Understand (30 min):** [PROJECT_DELIVERY_SUMMARY.md](PROJECT_DELIVERY_SUMMARY.md#executive-summary) - Understand what was built
3. **Deep Dive (1 hour):** [COMPLETE_SALES_INVOICE_DOCUMENTATION.md](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#executive-summary) - Learn full architecture
4. **Hands-On (45 min):** [PHASE_6_QUICK_TEST.md](PHASE_6_QUICK_TEST.md) - Create sample invoice
5. **Test (3-4 hours):** [PHASE_6_TESTING_GUIDE.md](PHASE_6_TESTING_GUIDE.md) - Run full test suite
6. **Deploy:** Follow [PROJECT_DELIVERY_SUMMARY.md#deployment-instructions](PROJECT_DELIVERY_SUMMARY.md#deployment-instructions)

**Total Learning Time:** ~5-6 hours to fully understand and be production-ready

---

## ✅ PROJECT STATUS SUMMARY

| Component             | Status      | Completion | Files          |
| --------------------- | ----------- | ---------- | -------------- |
| **Phase 1: Schema**   | ✅ COMPLETE | 100%       | 3              |
| **Phase 2: Clients**  | ✅ COMPLETE | 100%       | 6              |
| **Phase 3: Forms**    | ✅ COMPLETE | 100%       | 9              |
| **Phase 4: Handlers** | ✅ COMPLETE | 100%       | 7 (in Phase 3) |
| **Phase 5: Print**    | ✅ COMPLETE | 100%       | 1              |
| **Phase 6: Testing**  | ⏳ READY    | 0%         | 2 guides       |
| **TOTAL PROJECT**     | **95%**     | -          | **25+**        |

---

## 🎯 IMMEDIATE NEXT STEPS

### **NOW (Next 30-45 minutes):**

1. Open [PHASE_6_QUICK_TEST.md](PHASE_6_QUICK_TEST.md)
2. Follow the quick test workflow
3. Create first sample invoice
4. Verify print output (PTR should be hidden)
5. If 17/17 checkpoints pass → Ready for full test

### **WITHIN 1-2 DAYS:**

1. Open [PHASE_6_TESTING_GUIDE.md](PHASE_6_TESTING_GUIDE.md)
2. Run complete 58-point test suite
3. Document all results
4. Get QA sign-off

### **THEN:**

1. Deploy to production
2. Celebrate launch! 🎉

---

**Need help?** Reference the appropriate file above or check error messages against [COMPLETE_SALES_INVOICE_DOCUMENTATION.md#4-problem-resolution](COMPLETE_SALES_INVOICE_DOCUMENTATION.md#4-problem-resolution)

**Ready to test?** → Start with [PHASE_6_QUICK_TEST.md](PHASE_6_QUICK_TEST.md)

---

_Master Index Created: February 2026 | Last Updated: [Current Date]_  
_Project: Pharmacy ERP Sales Invoice Module Refactor - PHASES 1-5 COMPLETE ✅_
