<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<div class="page-wrapper">
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary">Add Supplier</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="manage_suppliers.php">Suppliers</a></li>
        <li class="breadcrumb-item active">Add Supplier</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card">
          <div class="card-body">

            <form method="POST"
                  id="addSupplierForm"
                  action="php_action/createSupplier.php"
                  class="row">

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
                       required>
              </div>

              <!-- Supplier Name -->
              <div class="form-group col-md-6">
                <label>Supplier Name *</label>
                <input type="text"
                       class="form-control"
                       name="supplier_name"
                       placeholder="e.g. ABC Pharmaceuticals"
                       required>
              </div>

              <!-- Company Name -->
              <div class="form-group col-md-6">
                <label>Company Name</label>
                <input type="text"
                       class="form-control"
                       name="company_name"
                       placeholder="e.g. ABC Pharma Ltd.">
              </div>

              <!-- Contact Person -->
              <div class="form-group col-md-6">
                <label>Contact Person</label>
                <input type="text"
                       class="form-control"
                       name="contact_person"
                       placeholder="Name of contact person">
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
                       placeholder="supplier@company.com">
              </div>

              <!-- Phone -->
              <div class="form-group col-md-6">
                <label>Phone *</label>
                <input type="tel"
                       class="form-control"
                       name="phone"
                       placeholder="10-digit phone number"
                       pattern="[0-9]{10}"
                       required>
              </div>

              <!-- Alternate Phone -->
              <div class="form-group col-md-6">
                <label>Alternate Phone</label>
                <input type="tel"
                       class="form-control"
                       name="alternate_phone"
                       placeholder="Alternate phone number"
                       pattern="[0-9]{10}">
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
                          required></textarea>
              </div>

              <!-- City -->
              <div class="form-group col-md-4">
                <label>City</label>
                <input type="text"
                       class="form-control"
                       name="city"
                       placeholder="City">
              </div>

              <!-- State -->
              <div class="form-group col-md-4">
                <label>State</label>
                <input type="text"
                       class="form-control"
                       name="state"
                       placeholder="State">
              </div>

              <!-- Pincode -->
              <div class="form-group col-md-4">
                <label>Pincode</label>
                <input type="text"
                       class="form-control"
                       name="pincode"
                       placeholder="6-digit pincode"
                       pattern="[0-9]{6}">
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
                       pattern="[0-9A-Z]{15}">
              </div>

              <!-- PAN Number -->
              <div class="form-group col-md-6">
                <label>PAN Number</label>
                <input type="text"
                       class="form-control"
                       name="pan_number"
                       placeholder="10-character PAN"
                       pattern="[A-Z0-9]{10}">
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
                       value="30"
                       min="0"
                       placeholder="Number of credit days">
              </div>

              <!-- Payment Terms -->
              <div class="form-group col-md-6">
                <label>Payment Terms</label>
                <input type="text"
                       class="form-control"
                       name="payment_terms"
                       placeholder="e.g. Net 30, COD">
              </div>

              <!-- ================= STATUS ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Status</h5>
              </div>

              <!-- Supplier Status -->
              <div class="form-group col-md-6">
                <label>Status</label>
                <select class="form-control" name="supplier_status" required>
                  <option value="Active" selected>Active</option>
                  <option value="Inactive">Inactive</option>
                  <option value="Blocked">Blocked</option>
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
                         value="1">
                  <label class="form-check-label" for="is_verified">
                    Mark as Verified
                  </label>
                </div>
              </div>

              <!-- Submit -->
              <div class="col-md-12 text-center mt-4">
                <button type="submit"
                        name="create"
                        class="btn btn-primary">
                  Save Supplier
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
