<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
// if ?id= is provided, we are editing an existing supplier
$editing = false;
$supplier = [];
if (isset($_GET['id']) && ($id = intval($_GET['id'])) > 0) {
    $stmt = $connect->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $supplier = $res->fetch_assoc();
        $editing = true;
    }
    $stmt->close();
}
?>

<div class="page-wrapper">
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary"><?php echo $editing ? 'Edit Supplier' : 'Add Supplier'; ?></h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="manage_suppliers.php">Suppliers</a></li>
        <li class="breadcrumb-item active"><?php echo $editing ? 'Edit Supplier' : 'Add Supplier'; ?></li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card">
          <div class="card-body">

            <form method="POST"
                  id="addSupplierForm"
                  action="php_action/createSupplier.php"
                  class="row">
              <?php if($editing): ?>
                  <input type="hidden" name="supplier_id" value="<?php echo intval($supplier['supplier_id']); ?>">
              <?php endif; ?>

              <!-- ================= COMPANY INFORMATION ================= -->
              <div class="col-md-12">
                <h5 class="mb-3 text-primary">Company Information</h5>
              </div>

              <!-- Supplier Code -->
              <div class="form-group col-md-6">
                <label>Supplier Code</label>
                <input type="text"
                       class="form-control"
                       name="supplier_code"
                       placeholder="e.g. SUP001"
                       value="<?php echo htmlspecialchars($supplier['supplier_code'] ?? ''); ?>"
                       required>
              </div>

              <!-- Supplier Name -->
              <div class="form-group col-md-6">
                <label>Supplier Name *</label>
                <input type="text"
                       class="form-control"
                       name="supplier_name"
                       placeholder="e.g. ABC Pharmaceuticals"
                       value="<?php echo htmlspecialchars($supplier['supplier_name'] ?? ''); ?>"
                       required>
              </div>

              <!-- Company Name -->
              <div class="form-group col-md-6">
                <label>Company Name</label>
                <input type="text"
                       class="form-control"
                       name="company_name"
                       placeholder="e.g. ABC Pharma Ltd."
                       value="<?php echo htmlspecialchars($supplier['company_name'] ?? ''); ?>">
              </div>

              <!-- Contact Person -->
              <div class="form-group col-md-6">
                <label>Contact Person</label>
                <input type="text"
                       class="form-control"
                       name="contact_person"
                       placeholder="Name of contact person"
                       value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>">
              </div>

              <!-- ================= CONTACT INFORMATION ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Contact Information</h5>
              </div>

              <!-- Email -->
              <div class="form-group col-md-6">
                <label>Email</label>
                <input type="email"
                       class="form-control"
                       name="email"
                       placeholder="supplier@company.com"
                       value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>">
              </div>

              <!-- Phone -->
              <div class="form-group col-md-6">
                <label>Phone *</label>
                <input type="tel"
                       class="form-control"
                       name="phone"
                       placeholder="10-digit phone number"
                       pattern="[0-9]{10}"
                       value="<?php echo htmlspecialchars($supplier['phone'] ?? ''); ?>"
                       required>
              </div>

              <!-- Alternate Phone -->
              <div class="form-group col-md-6">
                <label>Alternate Phone</label>
                <input type="tel"
                       class="form-control"
                       name="alternate_phone"
                       placeholder="Alternate phone number"
                       pattern="[0-9]{10}"
                       value="<?php echo htmlspecialchars($supplier['alternate_phone'] ?? ''); ?>">
              </div>

              <!-- ================= ADDRESS ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Address</h5>
              </div>

              <!-- Address -->
              <div class="form-group col-md-12">
                <label>Address *</label>
                <textarea class="form-control"
                          name="address"
                          rows="3"
                          placeholder="Street address"
                          required><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
              </div>

              <!-- City -->
              <div class="form-group col-md-4">
                <label>City</label>
                <input type="text"
                       class="form-control"
                       name="city"
                       placeholder="City"
                       value="<?php echo htmlspecialchars($supplier['city'] ?? ''); ?>">
              </div>

              <!-- State -->
              <div class="form-group col-md-4">
                <label>State</label>
                <input type="text"
                       class="form-control"
                       name="state"
                       placeholder="State"
                       value="<?php echo htmlspecialchars($supplier['state'] ?? ''); ?>">
              </div>

              <!-- Pincode -->
              <div class="form-group col-md-4">
                <label>Pincode</label>
                <input type="text"
                       class="form-control"
                       name="pincode"
                       placeholder="6-digit pincode"
                       pattern="[0-9]{6}"
                       value="<?php echo htmlspecialchars($supplier['pincode'] ?? ''); ?>">
              </div>

              <!-- ================= TAX & COMPLIANCE ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Tax & Compliance</h5>
              </div>

              <!-- GST Number -->
              <div class="form-group col-md-6">
                <label>GST Number</label>
                <input type="text"
                       class="form-control"
                       name="gst_number"
                       placeholder="15-digit GST number"
                       pattern="[0-9A-Z]{15}"
                       value="<?php echo htmlspecialchars($supplier['gst_number'] ?? ''); ?>">
              </div>

              <!-- PAN Number -->
              <div class="form-group col-md-6">
                <label>PAN Number</label>
                <input type="text"
                       class="form-control"
                       name="pan_number"
                       placeholder="10-character PAN"
                       pattern="[A-Z0-9]{10}"
                       value="<?php echo htmlspecialchars($supplier['pan_number'] ?? ''); ?>">
              </div>

              <!-- ================= PAYMENT TERMS ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Payment Terms</h5>
              </div>

              <!-- Credit Days -->
              <div class="form-group col-md-6">
                <label>Credit Days</label>
                <input type="number"
                       class="form-control"
                       name="credit_days"
                       value="<?php echo htmlspecialchars($supplier['credit_days'] ?? 30); ?>"
                       min="0"
                       placeholder="Number of credit days">
              </div>

              <!-- Payment Terms -->
              <div class="form-group col-md-6">
                <label>Payment Terms</label>
                <input type="text"
                       class="form-control"
                       name="payment_terms"
                       placeholder="e.g. Net 30, COD"
                       value="<?php echo htmlspecialchars($supplier['payment_terms'] ?? ''); ?>">
              </div>

              <!-- ================= STATUS ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Status</h5>
              </div>

              <!-- Supplier Status -->
              <div class="form-group col-md-6">
                <label>Status</label>
                <select class="form-control" name="supplier_status" required>
                  <option value="Active" <?php echo (!empty($supplier) && $supplier['supplier_status']=='Active') ? 'selected' : ''; ?>>Active</option>
                  <option value="Inactive" <?php echo (!empty($supplier) && $supplier['supplier_status']=='Inactive') ? 'selected' : ''; ?>>Inactive</option>
                  <option value="Blocked" <?php echo (!empty($supplier) && $supplier['supplier_status']=='Blocked') ? 'selected' : ''; ?>>Blocked</option>
                </select>
              </div>

              <!-- Is Verified -->
              <div class="form-group col-md-6">
                <label>&nbsp;</label>
                <div class="form-check mt-2">
                  <input type="checkbox" 
                         class="form-check-input" 
                         id="is_verified" 
                         name="is_verified"
                         value="1" <?php echo (!empty($supplier) && $supplier['is_verified']) ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="is_verified">
                    Mark as Verified
                  </label>
                </div>
              </div>

              <!-- Submit -->
              <div class="col-md-12 text-center mt-4">
                <button type="submit"
                        name="submit"
                        class="btn btn-primary">
                  <?php echo $editing ? 'Update Supplier' : 'Save Supplier'; ?>
                </button>
                <a href="manage_suppliers.php" class="btn btn-secondary">
                  Cancel
                </a>
              </div>

            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('./constant/layout/footer.php'); ?>
