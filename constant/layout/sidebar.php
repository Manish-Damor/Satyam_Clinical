 <?php
 require_once('./constant/connect.php');

 $currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
 if ($currentPage === '') {
     $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
 }

 function isActivePage($pages, $currentPage)
 {
     return in_array($currentPage, $pages, true);
 }

 $clientsOpen = isActivePage(['clients_form.php', 'clients_list.php'], $currentPage);
 $brandOpen = isActivePage(['add-brand.php', 'brand.php', 'editbrand.php'], $currentPage);
 $categoryOpen = isActivePage(['add-category.php', 'categories.php', 'editcategory.php'], $currentPage);
 $medicineOpen = isActivePage(['add_medicine.php', 'manage_medicine.php', 'viewStock.php', 'editproduct.php'], $currentPage);
 $poOpen = isActivePage(['create_po.php', 'po_list.php', 'po_view.php', 'print_po.php', 'edit-purchase-order.php'], $currentPage);
 $piOpen = isActivePage(['purchase_invoice.php', 'invoice_list.php', 'invoice_view.php', 'invoice_edit.php'], $currentPage);
 $siOpen = isActivePage(['sales_invoice_form.php', 'sales_invoice_list.php', 'sales_invoice_edit.php', 'print_invoice.php'], $currentPage);
 $reportsOpen = isActivePage(['sales_report.php', 'productreport.php', 'expreport.php', 'inventory_reports.php'], $currentPage);
 ?>

<link rel="stylesheet" href="assets/css/sidebar-refactor.css">

        <div class="left-sidebar sc-sidebar">
            <div class="scroll-sidebar">
                
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                        <li class="nav-devider"></li>
                        <li class="nav-label">Home</li>
                        <li class="<?php echo isActivePage(['dashboard.php'], $currentPage) ? 'active' : ''; ?>"><a href="dashboard.php" aria-expanded="false" class="<?php echo isActivePage(['dashboard.php'], $currentPage) ? 'active' : ''; ?>"><i class="fa fa-home icon-dashboard"></i><span class="hide-menu">Dashboard</span></a>
                        </li> 
                 
                         <?php if(isset($_SESSION['userId'])) { ?>
                        <li class="<?php echo $clientsOpen ? 'active' : ''; ?>"> <a class="has-arrow <?php echo $clientsOpen ? 'active' : ''; ?>" href="javascript:void(0)" aria-expanded="<?php echo $clientsOpen ? 'true' : 'false'; ?>"><i class="fa fa-users icon-clients"></i><span class="hide-menu">Clients</span></a>
                            <ul aria-expanded="<?php echo $clientsOpen ? 'true' : 'false'; ?>" class="collapse<?php echo $clientsOpen ? ' in' : ''; ?>">
                           
                                <li><a href="clients_form.php" class="<?php echo isActivePage(['clients_form.php'], $currentPage) ? 'active' : ''; ?>">Add Client</a></li>
                           
                                <li><a href="clients_list.php" class="<?php echo isActivePage(['clients_list.php'], $currentPage) ? 'active' : ''; ?>">Manage Clients</a></li>
                            </ul>
                        </li>
                    <?php }?>
                        <?php if(isset($_SESSION['userId'])) { ?>
                        <li class="<?php echo $brandOpen ? 'active' : ''; ?>"> <a class="has-arrow <?php echo $brandOpen ? 'active' : ''; ?>" href="javascript:void(0)" aria-expanded="<?php echo $brandOpen ? 'true' : 'false'; ?>"><i class="fa fa-industry icon-manufacturer"></i><span class="hide-menu">Manufacturer</span></a>
                            <ul aria-expanded="<?php echo $brandOpen ? 'true' : 'false'; ?>" class="collapse<?php echo $brandOpen ? ' in' : ''; ?>">
                           
                                <li><a href="add-brand.php" class="<?php echo isActivePage(['add-brand.php'], $currentPage) ? 'active' : ''; ?>">Add Manufacturer</a></li>
                           
                                <li><a href="brand.php" class="<?php echo isActivePage(['brand.php', 'editbrand.php'], $currentPage) ? 'active' : ''; ?>">Manage Manufacturer</a></li>
                                 <!-- <li><a href="importbrand.php">Import Manufacturer</a></li> -->
                            </ul>
                        </li>
                    <?php }?>
                        <?php if(isset($_SESSION['userId'])) { ?>
                        <li class="<?php echo $categoryOpen ? 'active' : ''; ?>"> <a class="has-arrow <?php echo $categoryOpen ? 'active' : ''; ?>" href="javascript:void(0)" aria-expanded="<?php echo $categoryOpen ? 'true' : 'false'; ?>"><i class="fa fa-tags icon-categories"></i><span class="hide-menu">Categories</span></a>
                            <ul aria-expanded="<?php echo $categoryOpen ? 'true' : 'false'; ?>" class="collapse<?php echo $categoryOpen ? ' in' : ''; ?>">
                           
                                <li><a href="add-category.php" class="<?php echo isActivePage(['add-category.php'], $currentPage) ? 'active' : ''; ?>">Add Category</a></li>
                           
                                <li><a href="categories.php" class="<?php echo isActivePage(['categories.php', 'editcategory.php'], $currentPage) ? 'active' : ''; ?>">Manage Categories</a></li>
                            </ul>
                        </li>
                    <?php }?>
                    <?php if(isset($_SESSION['userId'])) { ?>
                        <li class="<?php echo $medicineOpen ? 'active' : ''; ?>"> <a class="has-arrow <?php echo $medicineOpen ? 'active' : ''; ?>" href="javascript:void(0)" aria-expanded="<?php echo $medicineOpen ? 'true' : 'false'; ?>"><i class="fa fa-medkit icon-medicine"></i><span class="hide-menu">Medicine</span></a>
                            <ul aria-expanded="<?php echo $medicineOpen ? 'true' : 'false'; ?>" class="collapse<?php echo $medicineOpen ? ' in' : ''; ?>">
                           
                                <li><a href="add_medicine.php" class="<?php echo isActivePage(['add_medicine.php'], $currentPage) ? 'active' : ''; ?>">Add Medicine</a></li>
                           
                                <li><a href="manage_medicine.php" class="<?php echo isActivePage(['manage_medicine.php'], $currentPage) ? 'active' : ''; ?>">Manage Medicine</a></li>
                            </ul>
                        </li>
                    <?php }?>
                    <?php if(isset($_SESSION['userId'])) { ?>
                    <li class="<?php echo $poOpen ? 'active' : ''; ?>"> <a class="has-arrow <?php echo $poOpen ? 'active' : ''; ?>" href="javascript:void(0)" aria-expanded="<?php echo $poOpen ? 'true' : 'false'; ?>"><i class="fa fa-shopping-basket icon-po"></i><span class="hide-menu">Purchase Order</span></a>
                        <ul aria-expanded="<?php echo $poOpen ? 'true' : 'false'; ?>" class="collapse<?php echo $poOpen ? ' in' : ''; ?>">
                       
                            <li><a href="create_po.php" class="<?php echo isActivePage(['create_po.php', 'edit-purchase-order.php'], $currentPage) ? 'active' : ''; ?>">Create PO</a></li>
                       
                            <li><a href="po_list.php" class="<?php echo isActivePage(['po_list.php', 'po_view.php', 'print_po.php'], $currentPage) ? 'active' : ''; ?>">Manage POs</a></li>
                        </ul>
                    </li>

                <?php } ?>
                <?php if(isset($_SESSION['userId'])) { ?>
                <li class="<?php echo $piOpen ? 'active' : ''; ?>"> <a class="has-arrow <?php echo $piOpen ? 'active' : ''; ?>" href="javascript:void(0)" aria-expanded="<?php echo $piOpen ? 'true' : 'false'; ?>"><i class="fa fa-file-text-o icon-pi"></i><span class="hide-menu">Purchase Invoice</span></a>
                    <ul aria-expanded="<?php echo $piOpen ? 'true' : 'false'; ?>" class="collapse<?php echo $piOpen ? ' in' : ''; ?>">
                   
                        <li><a href="purchase_invoice.php" class="<?php echo isActivePage(['purchase_invoice.php', 'invoice_edit.php'], $currentPage) ? 'active' : ''; ?>">Create Invoice</a></li>
                   
                        <li><a href="invoice_list.php" class="<?php echo isActivePage(['invoice_list.php', 'invoice_view.php'], $currentPage) ? 'active' : ''; ?>">Manage PIs</a></li>
                    </ul>
                </li>

            <?php }?>
                        <li class="<?php echo $siOpen ? 'active' : ''; ?>"> <a class="has-arrow <?php echo $siOpen ? 'active' : ''; ?>" href="javascript:void(0)" aria-expanded="<?php echo $siOpen ? 'true' : 'false'; ?>"><i class="fa fa-line-chart icon-si"></i><span class="hide-menu">Sales Invoice</span></a>
                            <ul aria-expanded="<?php echo $siOpen ? 'true' : 'false'; ?>" class="collapse<?php echo $siOpen ? ' in' : ''; ?>">
                           
                                <li><a href="sales_invoice_form.php" class="<?php echo isActivePage(['sales_invoice_form.php', 'sales_invoice_edit.php'], $currentPage) ? 'active' : ''; ?>">Add Sales Invoice</a></li>
                           
                                <li><a href="sales_invoice_list.php" class="<?php echo isActivePage(['sales_invoice_list.php', 'print_invoice.php'], $currentPage) ? 'active' : ''; ?>">Manage Sales Invoices</a></li>
                            </ul>
                        </li>

                        <?php if(isset($_SESSION['userId'])) { ?>
                        <li class="<?php echo isActivePage(['manage_suppliers.php', 'add_supplier.php', 'suppliers.php'], $currentPage) ? 'active' : ''; ?>"> <a href="manage_suppliers.php" aria-expanded="false" class="<?php echo isActivePage(['manage_suppliers.php', 'add_supplier.php', 'suppliers.php'], $currentPage) ? 'active' : ''; ?>"><i class="fa fa-truck icon-supplier"></i><span class="hide-menu">Suppliers</span></a>
                            <!-- <ul aria-expanded="false" class="collapse">
                           
                                <li><a href="supplier.php">Suppliers</a></li>
                           
                            </ul> -->
                        </li>

                    <?php }?>
                         
                        <?php if(isset($_SESSION['userId'])) { ?>
                         <!-- <li><a href="report.php" href="#" aria-expanded="false"><i class="fa fa-print"></i><span class="hide-menu">Reports</span></a></li> -->
                        



                  

<li class="<?php echo $reportsOpen ? 'active' : ''; ?>"> <a class="has-arrow <?php echo $reportsOpen ? 'active' : ''; ?>" href="javascript:void(0)" aria-expanded="<?php echo $reportsOpen ? 'true' : 'false'; ?>"><i class="fa fa-area-chart icon-reports"></i><span class="hide-menu">Reports</span></a>
                       <ul aria-expanded="<?php echo $reportsOpen ? 'true' : 'false'; ?>" class="collapse<?php echo $reportsOpen ? ' in' : ''; ?>">
                           
                                <!-- <li><a href="report.php">Order Report</a></li> -->
                      <li><a href="sales_report.php" class="<?php echo isActivePage(['sales_report.php'], $currentPage) ? 'active' : ''; ?>">Sales Report</a></li>
                          <li><a href="productreport.php" class="<?php echo isActivePage(['productreport.php'], $currentPage) ? 'active' : ''; ?>">Product Report</a></li>
                          <li><a href="expreport.php" class="<?php echo isActivePage(['expreport.php'], $currentPage) ? 'active' : ''; ?>">Expired Product Report</a></li>
                          <li><a href="inventory_reports.php" class="<?php echo isActivePage(['inventory_reports.php'], $currentPage) ? 'active' : ''; ?>">Inventory Reports</a></li>
                       </ul>
                        </li>
                  <?php }?>


    
                    </ul>   
                </nav>
                
            </div>
            
        </div>
        <!-- sidebar hide/show toggle (moved outside to avoid being clipped by transform) -->
        <div class="sidebar-collapse-toggle"><i class="fa fa-chevron-left"></i></div>
<script>
// only handle the sidebar collapse/expand toggle button – submenu behavior
// is managed by the MetisMenu plugin (
// see assets/js/scripts.js which calls $("#sidebarnav").metisMenu()
// and provides proper show/hide animations and sibling collapsing).

function initSidebarToggle() {
    var toggle = document.querySelector('.sidebar-collapse-toggle');
    if (!toggle) return;
    var storageKey = 'sc_sidebar_collapsed';

    // sync icon state when page loads
    var icon = toggle.querySelector('i');
    var sidebar = document.querySelector('.left-sidebar');
    var stored = null;
    try {
        stored = localStorage.getItem(storageKey);
    } catch (e) {}

    if (stored === '1') {
        sidebar.classList.add('collapsed');
        document.body.classList.add('body-with-collapsed-sidebar');
    } else {
        document.body.classList.remove('body-with-collapsed-sidebar');
    }

    document.body.classList.remove('sidebar-hide');

    if (sidebar && sidebar.classList.contains('collapsed')) {
        icon.classList.replace('fa-chevron-left', 'fa-chevron-right');
        document.body.classList.add('body-with-collapsed-sidebar');
    }

    function setCollapsedState(collapsed) {
        var sidebar = document.querySelector('.left-sidebar');
        if (!sidebar) return;

        sidebar.classList.toggle('collapsed', collapsed);
        document.body.classList.toggle('body-with-collapsed-sidebar', collapsed);
        document.body.classList.remove('sidebar-hide');
        try {
            localStorage.setItem(storageKey, collapsed ? '1' : '0');
        } catch (e) {}
        if (collapsed) {
            icon.classList.replace('fa-chevron-left', 'fa-chevron-right');
        } else {
            icon.classList.replace('fa-chevron-right', 'fa-chevron-left');
        }
    }

    function toggleCollapsedState() {
        var sidebar = document.querySelector('.left-sidebar');
        if (!sidebar) return;
        setCollapsedState(!sidebar.classList.contains('collapsed'));
    }

    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleCollapsedState();
    });
}

document.addEventListener('DOMContentLoaded', initSidebarToggle);
</script>        