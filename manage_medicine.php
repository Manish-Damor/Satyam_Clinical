<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
// $sql = "
// SELECT 
//   p.product_id,
//   p.product_name,
//   p.product_image,
//   p.quantity,
//   p.brand_id,
//   p.categories_id,
//   p.active,
//   p.status,
//   MIN(pb.expiry_date) AS nearest_expiry
// FROM product p
// LEFT JOIN product_batches pb 
//   ON pb.product_id = p.product_id 
//  AND pb.status = 'Active'
// WHERE p.status = 1
// GROUP BY p.product_id
// ORDER BY p.product_name ASC
// ";

// $sql = "
// SELECT 
//   p.product_id,
//   p.product_name,
//   p.product_image,
//   p.quantity,
//   p.active,
//   p.status,

//   b.brand_name,
//   c.categories_name,

//   MIN(pb.expiry_date) AS nearest_expiry

// FROM product p

// LEFT JOIN brands b 
//   ON b.brand_id = p.brand_id

// LEFT JOIN categories c 
//   ON c.categories_id = p.categories_id

// LEFT JOIN product_batches pb 
//   ON pb.product_id = p.product_id 
//  AND pb.status = 'Active'

// WHERE p.status = 1

// GROUP BY p.product_id

// ORDER BY p.product_name ASC
// ";


$sql = "
SELECT 
  p.product_id,
  p.product_name,
  p.product_image,
  p.quantity,
  p.active,
  p.status,

  b.brand_name,
  c.categories_name,

  (
    SELECT MIN(pb.expiry_date)
    FROM product_batches pb
    WHERE pb.product_id = p.product_id
      AND pb.status = 'Active'
  ) AS nearest_expiry

FROM product p
LEFT JOIN brands b 
  ON b.brand_id = p.brand_id
LEFT JOIN categories c 
  ON c.categories_id = p.categories_id

ORDER BY p.product_name ASC
";

$result = $connect->query($sql);
?>


<div class="page-wrapper">

  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary">Manage Medicines</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
        <li class="breadcrumb-item active">Manage Medicines</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">

    <div class="card">
      <div class="card-body">

        <a href="add_medicine.php" class="btn btn-primary mb-3">
          Add Medicine
        </a>

        <div class="table-responsive">
          <table id="myTable" class="table table-bordered table-striped">
            <thead class="bg-light">
              <tr>
                <th>#</th>
                <!-- <th>Photo</th> -->
                <th>Medicine Name</th>
                <th>Total Stock</th>
                <!-- <th>Nearest Expiry</th> -->
                <th>Manufacturer</th>
                <th>Category</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <?php 
              while ($row = $result->fetch_assoc()) {

              //   // Manufacturer
              //   $brand = $connect->query(
              //     "SELECT brand_name FROM brands WHERE brand_id = '{$row['brand_id']}'"
              //   )->fetch_assoc();

              //   // Category
              //   $category = $connect->query(
                //     "SELECT categories_name FROM categories WHERE categories_id = '{$row['categories_id']}'"
              //   )->fetch_assoc();
              ?>
              <tr>

                <td><?php echo $row['product_id']; ?></td>

                <!-- <td>
                  <img src="assets/myimages/<?php //echo $row['product_image']; ?>"
                       style="width:80px;height:80px;">
                </td> -->

                <td>
                  <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                </td>

                <!-- TOTAL STOCK -->
                <td class="text-center">
                  <?php
                  if ($row['quantity'] > 0) {
                    echo "<span class='label label-success'>{$row['quantity']}</span>";
                    } else {
                    echo "<span class='label label-danger'>Out</span>";
                  }
                  ?>
                </td>

                <!-- NEAREST EXPIRY -->
                <!-- <td>
                  <?php
                  // if ($row['nearest_expiry']) {
                  //   if ($row['nearest_expiry'] < date('Y-m-d')) {
                  //     echo "<span class='label label-danger'>Expired</span>";
                  //     } else {
                  //     echo date('d-m-Y', strtotime($row['nearest_expiry']));
                  //   }
                  // } else {
                  //   echo "-";
                  // }
                  ?>
                </td> -->

                <td><?php echo $row['brand_name'] ?? '-'; ?></td>
                <td><?php echo $row['categories_name'] ?? '-'; ?></td>

                <td>
                  <?php
                  if ($row['active'] == 1) {
                    echo "<span class='label label-success'>Available</span>";
                  } else {
                    echo "<span class='label label-danger'>Not Available</span>";
                  }
                  ?>
                </td>

                <td>
                  <a href="editproduct.php?id=<?php echo $row['product_id']; ?>"
                     class="btn btn-xs btn-primary"
                     title="Edit Medicine">
                     <i class="fa fa-pencil"></i>
                  </a>

                  <a href="viewStock.php?id=<?php echo $row['product_id']; ?>"
                     class="btn btn-xs btn-info"
                     title="View Stock">
                     <i class="fa fa-cubes"></i>
                  </a>
                </td>

              </tr>
              <?php 
              } ?>
            </tbody>

          </table>
        </div>

      </div>
    </div>

  </div>
</div>

<?php include('./constant/layout/footer.php'); ?>
              <?php
                // $test = $connect->query("SELECT COUNT(*) FROM product WHERE status = 1;")->fetch_assoc();
                // echo $test;
                
                // exit();
              ?>
