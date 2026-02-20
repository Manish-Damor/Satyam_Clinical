<?php 
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;
$grnDate = date('Y-m-d');

// Get list of pending POs (not yet received)
$poSql = "SELECT po_id, po_number, po_date, supplier_name, grand_total, po_status 
          FROM purchase_order 
          WHERE po_status IN ('submitted', 'approved') 
          ORDER BY po_date DESC";
$poResult = $connect->query($poSql);

?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-8 align-self-center">
            <h3 class="text-primary">Create Goods Received Note (GRN)</h3>
        </div>
        <div class="col-md-4 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="grn_list.php">GRN List</a></li>
                <li class="breadcrumb-item active">Create GRN</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div id="grnMessages"></div>

        <form id="grnForm" method="POST" action="php_action/createGRN.php">
            
            <!-- GRN Header -->
            <div class="card">
                <div class="card-header bg-primary">
                    <h5 class="text-white m-0">GRN Header Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>GRN Date *</label>
                                <input type="date" class="form-control" id="grnDate" name="grn_date" value="<?php echo $grnDate; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Select Purchase Order *</label>
                                <select class="form-control" id="poId" name="po_id" required onchange="loadPODetails()">
                                    <option value="">-- Choose PO --</option>
                                    <?php
                                    if ($poResult && $poResult->num_rows > 0) {
                                        while($poRow = $poResult->fetch_assoc()) {
                                            echo "<option value='" . intval($poRow['po_id']) . "'>" 
                                                . htmlspecialchars($poRow['po_number']) . " - " 
                                                . htmlspecialchars($poRow['supplier_name']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Warehouse *</label>
                                <select class="form-control" id="warehouseId" name="warehouse_id">
                                    <option value="1">Main Warehouse</option>
                                    <option value="2">Branch 1</option>
                                    <option value="3">Branch 2</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Add any remarks..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PO Items Section -->
            <div class="card mt-3">
                <div class="card-header bg-info">
                    <h5 class="text-white m-0">Items Received</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="itemsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Batch</th>
                                    <th>Expiry</th>
                                    <th>Ordered Qty</th>
                                    <th>Received Qty *</th>
                                    <th>Quality Check *</th>
                                    <th>Quality Notes</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Select a PO to load items</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="card mt-3">
                <div class="card-body">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-check"></i> Create GRN & Quality Check
                    </button>
                    <a href="grn_list.php" class="btn btn-secondary btn-lg">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

        </form>
    </div>
</div>

<!-- Load PO Items Script -->
<script>
function loadPODetails() {
    const poId = document.getElementById('poId').value;
    if (!poId) {
        document.getElementById('itemsBody').innerHTML = '<tr><td colspan="7" class="text-center text-muted">Select a PO to load items</td></tr>';
        return;
    }

    // Fetch PO items via AJAX
    fetch('php_action/getPOItems.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'po_id=' + poId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateItemsTable(data.items);
        } else {
            alert(data.message || 'Failed to load items');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading items');
    });
}

function populateItemsTable(items) {
    const tbody = document.getElementById('itemsBody');
    let html = '';

    items.forEach((item, index) => {
        html += `
            <tr>
                <td>${item.medicine_name}</td>
                <td>
                    <input type="hidden" name="po_item_id[${index}]" value="${item.po_item_id}">
                    <input type="text" class="form-control form-control-sm" name="batch_number[${index}]" value="${item.batch_number || ''}">
                </td>
                <td>
                    <input type="date" class="form-control form-control-sm" name="expiry_date[${index}]" value="${item.expiry_date || ''}">
                </td>
                <td class="text-center text-weight-bold">${item.quantity_ordered}</td>
                <td>
                    <input type="number" class="form-control form-control-sm" name="quantity_received[${index}]" value="0" min="0" max="${item.quantity_ordered}" required>
                </td>
                <td>
                    <select class="form-control form-control-sm" name="check_result[${index}]" required>
                        <option value="passed">Passed ✓</option>
                        <option value="failed">Failed ✗</option>
                        <option value="hold">Hold ⏸</option>
                    </select>
                </td>
                <td>
                    <textarea class="form-control form-control-sm" name="quality_notes[${index}]" rows="1" placeholder="Notes..."></textarea>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html || '<tr><td colspan="7" class="text-center text-danger">No items found</td></tr>';
}

// Form submission
document.getElementById('grnForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const grnData = new FormData(this);
    const messageDiv = document.getElementById('grnMessages');

    fetch('php_action/createGRN.php', {
        method: 'POST',
        body: grnData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> ${data.message}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            `;
            setTimeout(() => {
                window.location.href = 'grn_list.php';
            }, 2000);
        } else {
            let errorHtml = `<strong>Error!</strong> ${data.message}`;
            if (data.errors && data.errors.length) {
                errorHtml += '<ul>';
                data.errors.forEach(err => errorHtml += `<li>${err}</li>`);
                errorHtml += '</ul>';
            }
            messageDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${errorHtml}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageDiv.innerHTML = `
            <div class="alert alert-danger">Error submitting GRN: ${error.message}</div>
        `;
    });
});
</script>

<?php
include('./constant/layout/footer.php');
?>
