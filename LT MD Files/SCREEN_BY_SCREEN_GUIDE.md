# 🔧 Screen-by-Screen Data Entry Quick Reference

## PURCHASING FLOW - Step by Step

### SCREEN 1: CREATE PURCHASE ORDER

```
File: add-purchase-order.php

┌─────────────────────────────────────────────────┐
│ PURCHASE ORDER ENTRY FORM                       │
├─────────────────────────────────────────────────┤
│                                                 │
│ Order Number*        [AUTO: PO-2024-00001]     │
│ Order Date*          [TODAY]                    │
│ Supplier Name*       [SELECT DROPDOWN] ▼        │
│                                                 │
│ ── SUPPLIER AUTO-FILLED DETAILS ──             │
│ Company:             ABC Pharma Ltd             │
│ GST No:              27AABCT1234H1Z0            │
│ Contact:             +91-9999999999             │
│ Email:               contact@abc.com            │
│                                                 │
│ ── ORDER DETAILS ──                            │
│ Delivery Date*       [____/____/____]           │
│ Terms*               [NET30] ▼                  │
│ Special Instructions [                    ]    │
│                                                 │
│ ┌─ ADD ITEMS TABLE ─────────────────────────┐  │
│ │ Product  │Qty│Unit Price│Discount│Total  │  │
│ ├──────────┼───┼──────────┼────────┼────────┤  │
│ │ Paracet..│100│  45.00   │  10%  │4050.00│  │
│ │ Aspirin  │ 50│  12.00   │  5%   | 570.00│  │
│ │[+ADD ROW]│   │          │       │       │  │
│ └────────────────────────────────────────────┘  │
│                                                 │
│ ── SUMMARY ──                                  │
│ Subtotal:            ₹ 4620.00                │
│ Total Discount:      ₹  462.00                │
│ Freight:             ₹    0.00                │
│ Tax (18%):           ₹  829.20                │
│ GRAND TOTAL:         ₹ 4987.20                │
│                                                 │
│ [SAVE AS DRAFT]  [SUBMIT FOR APPROVAL]        │
└─────────────────────────────────────────────────┘
```

**Key Fields to Fill:**

- Supplier: REQUIRED (dropdown of active suppliers)
- Delivery Date: REQUIRED (must be >= today)
- Product: REQUIRED (search & select)
- Quantity: REQUIRED (positive number)
- Unit Price: REQUIRED (from supplier master)
- Discount: OPTIONAL (% or amount)

**Actions After Save:**

1. PO status = "Draft"
2. Email notification to approver
3. Can edit until submitted
4. Cannot edit after approval

---

### SCREEN 2: GOODS RECEIPT (GRN)

```
File: Need to create manage_grn.php

┌──────────────────────────────────────────────┐
│ GOODS RECEIPT NOTE (GRN)                     │
├──────────────────────────────────────────────┤
│                                              │
│ Link to PO*         [PO-2024-00001] ▼       │
│                                              │
│ ── AUTO-FILLED FROM PO ──                   │
│ Supplier:           ABC Pharma               │
│ Supplier Ref No:    [TEXT]                  │
│ PO Date:            15-Feb-2024              │
│ PO Amt:             ₹ 4987.20               │
│                                              │
│ ── RECEIPT DETAILS ──                       │
│ GRN Number:         [AUTO: GRN-2024-0001]   │
│ Receipt Date:       [TODAY]                 │
│ Received By*        [Current User]          │
│ Quality Check:      [PASS / FAIL / PENDING]│
│                                              │
│ ┌─ ITEMS RECEIVED ────────────────────────┐ │
│ │ Product    │Expected│Received│Rejected  │ │
│ ├────────────┼────────┼────────┼──────────┤ │
│ │ Paracet..  │  100   │  100   │    0     │ │
│ │ Aspirin    │   50   │   48   │    2     │ │
│ │            │        │ [Edit] │          │ │
│ └────────────────────────────────────────┘ │
│                                              │
│ ┌─ BATCH CREATION ───────────────────────┐ │
│ │ For: Paracetamol 650mg                 │ │
│ ├───────────────────────────────────────┤ │
│ │ Batch Number*      [BAT-24-00154]      │ │
│ │ Mfg Date*          [____/____/____]    │ │
│ │ Expiry Date*       [____/____/____]    │ │
│ │ Qty Received*      [    100    ]       │ │
│ │ Storage Location   [Shelf-A-01] ▼     │ │
│ │ [ADD BATCH]                            │ │
│ └───────────────────────────────────────┘ │
│                                              │
│ [SAVE AS DRAFT]  [SUBMIT FOR APPROVAL]    │
└──────────────────────────────────────────────┘
```

**Key Fields:**

- PO Reference: REQUIRED (link to existing PO)
- Quantity Received: REQUIRED (vs. PO qty)
- Batch Number: REQUIRED, UNIQUE
- Mfg Date: REQUIRED
- Expiry Date: REQUIRED (must be >6 months)
- Quality Check: REQUIRED

**Validations:**
✓ Expiry date > Manufacturing date
✓ Batch number not duplicate
✓ Received qty <= PO qty (unless over-receipt allowed)

---

### SCREEN 3: PURCHASE INVOICE

```
File: purchase_invoice.php (Already redesigned!)

┌─────────────────────────────────────────────┐
│ PURCHASE INVOICE ENTRY                      │
├─────────────────────────────────────────────┤
│                                              │
│ ── INVOICE HEADER ──                       │
│ Invoice Number*      [INV-00001]            │
│ Invoice Date*        [____/____/____]       │
│ Supplier*            [SELECT] ▼             │
│ GRN Reference        [GRN-2024-0001] ▼     │
│ PO Reference         [PO-2024-00001] ▼     │
│                                              │
│ ── SUPPLIER DETAILS (Auto-filled) ──       │
│ ┌──────────────────────────┐               │
│ │ ABC Pharma Ltd           │               │
│ │ Contact: XYZ Person      │               │
│ │ Email: contact@abc.com   │               │
│ │ GST: 27AABCT1234H1Z0    │               │
│ │ Address: xyz, City       │               │
│ │ Credit Days: 30          │               │
│ └──────────────────────────┘               │
│                                              │
│ ── PAYMENT TERMS ──                        │
│ Payment Terms*       [NET30] ▼              │
│ Due Date*            [Auto-filled]          │
│ Currency*            [INR] ▼                │
│                                              │
│ ┌─ LINE ITEMS ──────────────────────────┐ │
│ │Product│Batch│Qty │Rate│Disc%│Tax%│Total│ │
│ ├────────┼─────┼────┼────┼─────┼────┼──────┤ │
│ │Paracet │B001 │100 │45  │10%  │18% │4050  │ │
│ │Aspirin │B002 │ 50 │12  │ 5%  │18% │ 570  │ │
│ │[+ADD]  │     │    │    │     │    │      │ │
│ └──────────────────────────────────────┘ │
│                                              │
│ ── SUMMARY ──                              │
│ Subtotal:            ₹ 4620.00             │
│ Total Discount:      ₹  462.00             │
│ Tax (18%):           ₹  829.20             │
│ Freight:             ₹    0.00             │
│ Round-off:           ₹    0.80             │
│ ═════════════════════════════════════════ │
│ GRAND TOTAL:         ₹ 4987.00             │
│                                              │
│ Page Notes:          [                 ]   │
│                                              │
│ [SAVE DRAFT]  [SAVE & APPROVE]  [CANCEL] │
└─────────────────────────────────────────────┘
```

**Key Fields:**

- Supplier: REQUIRED (dropdown)
- Invoice Number: REQUIRED, UNIQUE
- Invoice Date: REQUIRED
- GRN Reference: RECOMMENDED (for traceability)
- Payment Terms: REQUIRED
- Product, Qty, Rate: REQUIRED for each item

**Auto-Calculations:**
✓ Line Total = Qty × Rate - Discount + Tax
✓ Subtotal = Sum of all quantities × rates
✓ Grand Total = Subtotal - Discount + Tax + Freight ± RoundOff

---

## SALES FLOW - Step by Step

### SCREEN 4: CREATE SALES ORDER

```
File: add-order.php

┌──────────────────────────────────────────────┐
│ CREATE SALES ORDER / INVOICE                 │
├──────────────────────────────────────────────┤
│                                              │
│ ── INVOICE HEADER ──                       │
│ Invoice Number*      [AUTO: INV-2024-00001] │
│ Invoice Date*        [02-Feb-2024]          │
│                                              │
│ ── CUSTOMER DETAILS ──                      │
│ Customer Name*       [                  ]   │
│ Phone Number*        [____-__________]      │
│ Contact Person       [                  ]   │
│ Delivery Address     [                  ]   │
│                                              │
│ ── PAYMENT TYPE ──                          │
│ Payment Type*        [CASH / CREDIT / CARD] │
│ GST No (if Reg.)     [27AABCT1234H1Z0]     │
│                                              │
│ ┌─ PRODUCT SELECTION ───────────────────┐  │
│ │ Medicine Search: [Paracet___________] │  │
│ │                                       │  │
│ │ Search Results:                       │  │
│ │ □ Paracetamol 650mg (100 in stock)   │  │
│ │ □ Paracetamol 500mg ( 45 in stock)   │  │
│ └───────────────────────────────────────┘  │
│                                              │
│ ┌─ ORDER ITEMS TABLE ──────────────────┐   │
│ │ Medicine │Batch│Qty│Price│Disc│Amt  │   │
│ ├──────────┼─────┼───┼─────┼───┼─────────┤   │
│ │ Paracet  │BT01 │10 │150  │10%│1350 │   │
│ │ Aspirin  │BT02 │ 5 │ 45  │ 0%│ 225 │   │
│ │ [+ADD]   │     │   │     │   │     │   │
│ └──────────────────────────────────────┘   │
│                                              │
│ ── PAYMENT DETAILS ──                      │
│ Subtotal:            ₹ 1575.00             │
│ Discount:            ₹  157.50             │
│ GST (18%):           ₹  252.02             │
│ TOTAL:               ₹ 1669.52             │
│                                              │
│ Amount Paid (Cash)*  [    ₹ 1669.52    ]   │
│ Change (if any)      [        0.00     ]   │
│                                              │
│ [SAVE ORDER]  [PRINT INVOICE]  [CANCEL]   │
└──────────────────────────────────────────────┘
```

**Key Fields:**

- Customer Name: REQUIRED
- Phone: REQUIRED
- Product: REQUIRED (search & select)
- Batch: AUTO-SELECTED (best available)
- Quantity: REQUIRED (must be ≤ available stock)
- Price: AUTO-FILLED (MRP)
- Payment Amount: REQUIRED (must equal total)

**Validations:**
✓ Order quantity ≤ Available stock
✓ Customer information complete
✓ Payment amount = Total (for cash)
✓ At least 1 item required

---

### SCREEN 5: ORDER FULFILLMENT & PICKING

```
File: Need to create order_fulfillment.php

┌──────────────────────────────────────────────┐
│ ORDER FULFILLMENT / PICKING SLIP             │
├──────────────────────────────────────────────┤
│                                              │
│ Order Number        [INV-2024-00001]        │
│ Customer            [ABC Medical Store]     │
│ Order Date          [02-Feb-2024]           │
│                                              │
│ ┌─ PICKING LIST ────────────────────────┐  │
│ │ Medicine    │Ordered│Batch│Picked│✓  │  │
│ ├─────────────┼───────┼─────┼──────┼───┤  │
│ │ Paracetamol │  10   │BT01 │  10  │ ✓ │  │
│ │ Aspirin     │   5   │BT02 │   5  │ ✓ │  │
│ │             │       │     │      │   │  │
│ └────────────────────────────────────────┘  │
│                                              │
│ ── VERIFICATION ──                          │
│ □ All items picked                          │
│ □ Batch numbers verified                    │
│ □ Expiry dates checked                      │
│ □ Product quality OK                        │
│ □ Customer details matched                  │
│ □ Total amount verified (₹1669.52)         │
│                                              │
│ ── PACKING ──                               │
│ Packed By*          [Current User]          │
│ Packing Date        [02-Feb-2024]           │
│ Delivery Mode       [COUNTER / DELIVERY]    │
│                                              │
│ [GENERATE INVOICE] [PRINT RECEIPT] [DONE]  │
└──────────────────────────────────────────────┘
```

**Workflow:**

1. Print picking slip
2. Physically pick items from shelf using batch numbers
3. Verify batch expiry dates
4. Count quantity matches ordered
5. Mark items as picked (checkboxes)
6. All items verify → Generate final invoice
7. Print invoice with date & signature
8. Hand to customer or dispatch

---

## INVENTORY MANAGEMENT - Key Screens

### SCREEN 6: STOCK LEVEL DASHBOARD

```
File: viewStock.php / inventory_dashboard.php

┌─────────────────────────────────────────────┐
│ INVENTORY DASHBOARD - STOCK LEVELS           │
├─────────────────────────────────────────────┤
│                                              │
│ SEARCH: [Medicine Name_____________] [GO]   │
│ VIEW:  [All / Low Stock / Expiring]        │
│                                              │
│ ┌─ PRODUCT STOCK SUMMARY ──────────────┐   │
│ │ Product    │Current│Reorder│Status    │   │
│ │            │Stock  │Level  │          │   │
│ ├────────────┼───────┼───────┼──────────┤   │
│ │ Paracetamo │  150  │  100  │ ✓ OK    │   │
│ │ Aspirin    │   45  │   50  │ ⚠ LOW   │   │
│ │ Ibuprofen  │    5  │   20  │ 🔴 CRIT  │   │
│ │ Cough Syru │   78  │   50  │ ✓ OK    │   │
│ └────────────┴───────┴───────┴──────────┘   │
│                                              │
│ ┌─ BATCH DETAILS - PARACETAMOL ────────┐   │
│ │ Batch    │Mfg Date │Exp Date │Qty│Notes│   │
│ ├──────────┼─────────┼─────────┼───┼─────┤   │
│ │ BT-001   │15-Dec-23│15-Dec-25│ 75│ ✓   │   │
│ │ BT-002   │02-Jan-24│02-Jan-26│ 75│ ✓   │   │
│ │ BT-003   │01-Sep-24│01-Sep-25│ 50│⚠ 90d│   │
│ └──────────────────────────────────────┘   │
│                                              │
│ [VIEW DETAILS] [PURCHASE MORE] [AUDIT]    │
└─────────────────────────────────────────────┘
```

**Status Indicators:**

- ✓ GREEN: Stock OK (above reorder level, not expiring)
- ⚠ YELLOW: Low stock (below reorder level) or expiring soon (90 days)
- 🔴 RED: Critical (below half of reorder level) or critical expiry (<30 days)

---

### SCREEN 7: STOCK ADJUSTMENT

```
File: Create stock_adjustments.php

┌──────────────────────────────────────────┐
│ STOCK ADJUSTMENT ENTRY                  │
├──────────────────────────────────────────┤
│                                          │
│ ── ADJUSTMENT TYPE ──                  │
│ Adjustment Type*     [STOCK IN] ▼      │
│                      (Stock In / Out)   │
│                                          │
│ ── SELECT PRODUCT ──                  │
│ Product*             [Paracetamol] ▼  │
│ Batch*               [BT-001] ▼       │
│ Current Stock        [75 Units]       │
│                                          │
│ ── ADJUSTMENT DETAILS ──               │
│ Adjustment Date*     [____/____/____]  │
│ Reason*              [SELECT] ▼        │
│                      - Return Damaged  │
│                      - Expired Stock   │
│                      - Physical Diff   │
│                      - Transfer Out    │
│                      - Transfer In     │
│                                          │
│ Quantity to Adjust*  [    5    ]       │
│                                          │
│ ── VERIFICATION ──                     │
│ Previous Qty:        75 Units          │
│ Adjustment:          -5 Units          │
│ New Expected Qty:    70 Units          │
│                                          │
│ Notes                [              ]  │
│ Attachment (Proof)   [UPLOAD]          │
│                                          │
│ Approved By         [            ]    │
│                                          │
│ [SAVE & APPROVE] [CANCEL]              │
└──────────────────────────────────────────┘
```

**Reasons Available:**

- Stock In: Received, Transfer In, Return from Damage, Correction
- Stock Out: Sale, Transfer Out, Damage, Expired, Physical Diff

---

## QUICK REFERENCE - FIELD FORMATS

```
┌─────────────────────────────────────────────────┐
│ FIELD FORMATS & VALIDATION RULES               │
├─────────────────────────────────────────────────┤
│                                                 │
│ 📱 Phone Number: 10 digits                     │
│    Format: 9876543210 or +91 9876543210       │
│                                                 │
│ 🔐 GST Number: 15 digits                      │
│    Format: 27AABCT1234H1Z0 (India)            │
│    Validation: State Code(2) + Business(8) +   │
│               Business Type(1) + Check(1)      │
│                                                 │
│ 🏭 HSN Code: 6-8 digits                       │
│    Examples: 300110, 300130                   │
│    Need to match with GST rate                │
│                                                 │
│ 💰 GST Rate: 0%, 5%, 12%, 18%, 28%           │
│    Standard tax for medicines: 5% or 12%      │
│                                                 │
│ 📦 Batch Number: Alphanumeric, UNIQUE        │
│    Examples: BAT-24-00154, C123D45             │
│                                                 │
│ 📅 Dates: YYYY-MM-DD or DD-MM-YYYY            │
│    Example: 2024-12-31 or 31-12-2024          │
│                                                 │
│ 💵 Currency: Always INR with ₹ symbol        │
│    Format: ₹ 1,234.50 (with 2 decimals)      │
│                                                 │
│ 📊 Quantities: Positive integers/decimals     │
│    Example: 100, 50.5, 0.25                   │
│                                                 │
│ 📈 Percentages: 0-100%                        │
│    Example: 5.50 (for 5.5%)                   │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## 🎓 TYPICAL USER WORKFLOW - ONE DAY EXAMPLE

```
TIME    ACTIVITY                      SCREEN         STATUS
─────────────────────────────────────────────────────────────

09:00   Morning Inventory Check       Dashboard      Check stock
        Read: Stock Levels           Inventory      alerts
        Note: Low Stock Alert
        Action: Paracetamol & Aspirin need reorder

09:30   Create Purchase Order        Add PO         Create PO
        Select: ABC Pharma Supplier  Fulfillment
        Items: Paracet (200), Aspirin (150)
        Approve: Amount ₹9,500

10:00   Customer Orders              Create Order   Record sales
        Paracet (10), Aspirin (5)    Processing
        Amount: ₹1,670
        Payment: Cash collected ✓

11:00   Receive GRN from ABC Pharma  Goods Receipt  Record
        Paracet (200), Aspirin (150) Processing     delivery
        Create Batches with expiry dates

13:00   Lunch Break

14:00   Record Supplier Invoice      Invoice Entry  Link to GRN
        Amount: ₹9,500
        Payment Terms: NET30
        Due Date: Auto-calculated

15:00   Stock Adjustment             Adjust Stock   Correct
        Return 2 damaged Paracetamol Loss tracking
        Physical count variance: +5 Aspirin

16:00   Generate Reports             Analytics      Review
        Daily Sales: ₹1,670          Reports        performance
        Inventory Health: 2 items low
        Expiry Alert: 1 batch <90 days

17:00   Exit                         Dashboard      Day review
        Print Summary Report                        complete

```

---

**Next: Refer to PROFESSIONAL_DATA_FLOW_GUIDE.md for detailed field descriptions**
