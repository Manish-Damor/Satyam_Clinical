 <?php 
 require_once('./constant/connect.php');
  

 ?>

 
        <div class="left-sidebar">
            
            <div class="scroll-sidebar">
                
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                        <li class="nav-devider"></li>
                        <li class="nav-label">Home</li>
                        <li><a href="dashboard.php" aria-expanded="false"><i class="fa fa-tachometer"></i>Dashboard</a>
                        </li> 
                 
                         <?php if(isset($_SESSION['userId'])) { ?>
                        <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-industry"></i><span class="hide-menu">Manufacturer</span></a>
                            <ul aria-expanded="false" class="collapse">
                           
                                <li><a href="add-brand.php">Add Manufacturer</a></li>
                           
                                <li><a href="brand.php">Manage Manufacturer</a></li>
                                 <!-- <li><a href="importbrand.php">Import Manufacturer</a></li> -->
                            </ul>
                        </li>
                    <?php }?>
                        <?php if(isset($_SESSION['userId'])) { ?>
                        <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-list"></i><span class="hide-menu">Categories</span></a>
                            <ul aria-expanded="false" class="collapse">
                           
                                <li><a href="add-category.php">Add Category</a></li>
                           
                                <li><a href="categories.php">Manage Categories</a></li>
                            </ul>
                        </li>
                    <?php }?>
                    <?php if(isset($_SESSION['userId'])) { ?>
                        <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-medkit"></i><span class="hide-menu">Medicine</span></a>
                            <ul aria-expanded="false" class="collapse">
                           
                                <li><a href="addProductStock.php">Add Medicine Stock</a></li>
                                <li><a href="manage_batches.php">Manage Batches</a></li>
                                <li><a href="add_medicine.php">Add Medicine</a></li>
                           
                                <li><a href="manage_medicine.php">Manage Medicine</a></li>
                            </ul>
                        </li>
                    <?php }?>
                        <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-file"></i><span class="hide-menu">Sales Invoice</span></a>
                            <ul aria-expanded="false" class="collapse">
                           
                                <li><a href="add-order.php">Add Sales Invoice</a></li>
                           
                                <li><a href="Order.php">Manage Sales Invoices</a></li>
                            </ul>
                        </li>

                        <?php if(isset($_SESSION['userId'])) { ?>
                        <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-file"></i><span class="hide-menu">Purchase Invoice</span></a>
                            <ul aria-expanded="false" class="collapse">
                           
                                <li><a href="purchase_invoice.php">Create Invoice</a></li>
                           
                                <li><a href="invoice_list.php">Manage PIs</a></li>
                            </ul>
                        </li>

                    <?php }?>
                        <?php if(isset($_SESSION['userId'])) { ?>
                        <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-file"></i><span class="hide-menu">Purchase Order</span></a>
                            <ul aria-expanded="false" class="collapse">
                           
                                <li><a href="create_po.php">Create PO</a></li>
                           
                                <li><a href="po_list.php">Manage POs</a></li>
                            </ul>
                        </li>

                    <?php }
                    // add GRN link available to same roles
                    if(isset($_SESSION['userId'])) { ?>
                        <li> <a href="grn_list.php" aria-expanded="false"><i class="fa fa-check-square"></i><span class="hide-menu">Goods Received</span></a></li>
                    <?php }?>
                        <?php if(isset($_SESSION['userId'])) { ?>
                        <li> <a class="has-arrow" href="suppliers.php" aria-expanded="false"><i class="fa fa-file"></i><span class="hide-menu">Suppliers</span></a>
                            <!-- <ul aria-expanded="false" class="collapse">
                           
                                <li><a href="supplier.php">Suppliers</a></li>
                           
                            </ul> -->
                        </li>

                    <?php }?>
                         
                        <?php if(isset($_SESSION['userId'])) { ?>
                         <!-- <li><a href="report.php" href="#" aria-expanded="false"><i class="fa fa-print"></i><span class="hide-menu">Reports</span></a></li> -->
                        



                  

<li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-flag"></i><span class="hide-menu">Reports</span></a>
                            <ul aria-expanded="false" class="collapse">
                           
                                <!-- <li><a href="report.php">Order Report</a></li> -->
                           <li><a href="sales_report.php">Sales Report</a></li>
                                <li><a href="productreport.php">Product Report</a></li>
                                <li><a href="expreport.php">Expired Product Report</a></li>                                <li><a href="inventory_reports.php">Inventory Reports</a></li>                            </ul>
                        </li>
                  <?php }?>


    
                    </ul>   
                </nav>
                
            </div>
            
        </div>
        