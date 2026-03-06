# Professional PO Print Module - Complete Implementation

## ✓ Print Page Features

### 📋 Company Header Section

- **Company Name**: SATYAM CLINICAL SUPPLIES (in professional branding)
- **Company Type**: Pharmacy & Medical Distributors
- **Head Office Details**: Complete address with phone and email
- **GST Details**: GST Registration Number (27AABCT1234A1Z0)
- **PAN**: Professional tax identification (AABCT1234A)

### 📌 PO Header Information (Right Aligned)

- PO Number (prominently displayed)
- Status Badge (color-coded: Draft/Submitted/Approved/PartialReceived/Received/Closed)
- PO Date
- Expected Delivery Date

### 📍 Supplier Information Box

- Supplier Name
- Contact Person
- Phone Number
- Email Address
- Complete Address

### 🚚 Delivery Information Box

- Delivery Location (Main Warehouse or custom)
- Expected Delivery Date
- Total Items Count
- Quantity Ordered (aggregated)
- Quantity Received (aggregated)

### 📦 Ordered Items Table (Professional Format)

Columns:

- Sr. No. (sequence)
- Product Name / Description (40% width)
- Qty Ordered (right-aligned)
- Qty Received (right-aligned)
- Unit Price (₹, right-aligned)
- GST % (right-aligned)
- Total (bold, right-aligned)

Table styling:

- Dark header (dark gray background, white text)
- Alternating row colors (white/light gray) for readability
- Professional borders
- Print-optimized contrast

### 💰 Financial Summary Box

Right-aligned comprehensive totals:

- **Subtotal**: Base amount before any deductions
- **Discount**: Applied percentage with amount deducted
- **GST**: Goods and Services Tax with percentage
- **Other Charges**: If applicable
- **GRAND TOTAL**: Highlighted in bold with green color

### 📝 Terms & Conditions Section

Professional terms box with:

- Payment Terms reference
- Delivery schedule requirements
- Quality standards requirements
- GRN (Goods Receipt Note) requirement
- Deviation notification process
- Tax inclusivity clarification

### ✍️ Signature Section (3-column layout)

1. **Prepared By** (Warehouse/Procurement)
   - Signature line
   - Date/Time placeholder

2. **Approved By** (Manager/Director)
   - Signature line
   - Authorized representative

3. **Supplier Signature** (Authorized Representative)
   - Signature line
   - Acceptance line

### 🏦 Professional Footer (3-column grid)

- **Bank Details**: Account information for payments
- **Company Details**: Registration, License, Establishment date
- **System Info**: Generation timestamp, PO ID, ERP System name

## 🖨️ Print Format & Features

### Page Setup

- **Size**: A4 (210mm × 297mm)
- **Orientation**: Portrait
- **Margins**: 15mm on all sides (professional standard)
- **Font**: Segoe UI (modern, readable)
- **Color Scheme**: Professional blue/green with proper contrast

### Screen vs. Print

**Screen View**:

- Fixed action bar at top with Print & Back buttons
- Print button triggers browser print dialog
- Scrollable content area
- Professional white background on gray page background

**Print Output**:

- All UI buttons automatically hidden (@media print CSS)
- Clean, professional layout optimized for paper
- Company header spans full width
- Page breaks handled automatically
- Signature lines properly spaced for handwritten approval

### Print CSS Features

```css
- Removes max-width constraint for full page use
- Sets A4 page size
- Applies 15mm margins
- Hides all .no-print class elements
- Optimizes colors for printing
- Maintains proper spacing and alignment
- Removes shadows and unnecessary styling
```

## 🔗 Integration Points

### From PO List (po_list.php)

Users see "View" button → Opens po_view.php

### From PO Detail (po_view.php)

Users see "Print PO" button → Opens print_po.php?id=<po_id> in new tab

### Direct Access

URL: `http://localhost/Satyam_Clinical/print_po.php?id=<po_id>`

## 📱 Usage Workflow

### Step 1: Create or View a PO

- Go to [po_list.php](http://localhost/Satyam_Clinical/po_list.php)
- Click "View" on any PO or create a new one

### Step 2: Access Print Page

- Click "Print PO" button in po_view.php
- Opens professional print page in new browser tab

### Step 3: Review Print Preview

- Company details visible at top
- Supplier information clearly displayed
- All line items with quantities and pricing
- Professional totals section
- Signature lines ready for approval

### Step 4: Print or Export

**Option A - Browser Print**:

- Press Ctrl+P or use browser Print menu
- Select printer (HP LaserJet, PDF printer, etc.)
- Click Print
- Buttons automatically hidden in print output

**Option B - Save as PDF**:

- Press Ctrl+P
- Change printer to "Print to File" or "Save as PDF"
- Choose location and filename
- Click Print/Save
- Professional PDF generated

**Option C - Email**:

- Print to PDF first
- Attach to email
- Send to supplier/stakeholders

## 📊 Database Tables Referenced

**purchase_orders**:

- po_id, po_number, po_date, supplier_id
- expected_delivery_date, delivery_location
- subtotal, discount_amount, gst_amount, grand_total
- po_status, notes, created_at

**po_items**:

- po_item_id, po_id, product_id
- quantity_ordered, quantity_received
- unit_price, gst_percentage, total_price

**suppliers**:

- supplier_id, supplier_name, contact_person
- phone, email, address

## 🎨 Professional Design Elements

### Color Scheme

- **Header**: Professional dark blue-gray (#2c3e50)
- **Accent**: Green success color (#27ae60) for totals
- **Status Badges**: Color-coded workflow
  - Draft: Light gray
  - Submitted: Orange
  - Approved: Blue
  - PartialReceived: Purple
  - Received: Green
  - Closed: Dark

### Typography

- **Headings**: Bold, 12-14px, professional spacing
- **Body**: 11px, optimized for readability
- **Data**: Right-aligned for financial values
- **Currency**: Indian Rupee (₹) formatting

### Spacing & Layout

- Professional padding and margins
- Clear section separation with borders
- Alternating row colors in tables
- Proper whitespace for readability
- Grid-based layout for consistent formatting

## ✅ Quality Assurance

### Tested Features

✓ Database connection working
✓ PO data retrieves correctly
✓ Supplier information displays
✓ Items table renders properly
✓ Financial calculations accurate
✓ Print CSS hides UI elements
✓ A4 page size formatting correct
✓ Professional branding visible
✓ All fields populate correctly
✓ Print preview accurate

### Browser Support

- Chrome: Full support
- Firefox: Full support
- Safari: Full support
- Edge: Full support
- Print to PDF: All browsers

## 🚀 How to Use

### For Pharmacy Staff:

1. Open PO in system
2. Review details
3. Click "Print PO" button
4. Print to paper or PDF
5. Get signatures from approvers
6. Archive hard copy

### For Management:

1. Access print_po.php directly via URL
2. Review all PO details
3. Check financial summary
4. Print for records
5. File in compliance folder

### For Suppliers:

1. Receive printed PO
2. Sign approval section
3. Return to pharmacy
4. File in their records

## 📌 Important Notes

- **No Web-Only Content**: Print output is clean professional document
- **Company Branding**: Pharmacy logo/name visible on every print
- **Regulatory Ready**: Includes all required tax and registration details
- **Audit Trail**: Footer contains generation timestamp and PO ID
- **Easy Updates**: Company details easily customizable in header section
- **International Standard**: Uses A4 paper format (worldwide standard)

---

**Status**: ✓ Professional PO Print System Fully Implemented & Ready
**Last Updated**: February 23, 2026
**Module**: Print Purchase Order (print_po.php)
