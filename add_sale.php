<?php
  require_once('includes/load.php');

  // Only allow logged-in users
  if (!$session->isUserLoggedIn(true)) {
    redirect('index.php', false);
  }

  if (isset($_POST['add_sale'])) {
    // Sanitize and collect input
    $p_id    = (int)remove_junk($db->escape($_POST['s_id']));
    $s_qty   = (int)remove_junk($db->escape($_POST['quantity']));
    $s_price = (float)remove_junk($db->escape($_POST['price']));
    $s_total = (float)remove_junk($db->escape($_POST['total']));
    $s_date  = make_date();

    // Validate product ID
    if (!$p_id || $p_id <= 0) {
      $session->msg('d', 'Error: Invalid product selected.');
      redirect('add_sale.php', false);
    }

    // Step 1: Fetch current stock for the selected product
    $sql = "SELECT quantity FROM products WHERE id = {$p_id}";
    $result = $db->query($sql);

    if (!$result) {
      $session->msg('d', 'Database error while checking stock.');
      redirect('add_sale.php', false);
    }

    $current_stock = $db->fetch_assoc($result);

    if ($current_stock['quantity'] < $s_qty) {
      $session->msg('d', 'Sale quantity exceeds available stock!');
      redirect('add_sale.php', false);
    }

    // Step 2: Insert the sale
    $sql_insert = "INSERT INTO sales (product_id, qty, price, date)";
    $sql_insert .= " VALUES ('{$p_id}', '{$s_qty}', '{$s_total}', '{$s_date}')";

    if ($db->query($sql_insert)) {
      // Step 3: Update product stock
      $new_qty = $current_stock['quantity'] - $s_qty;
      $sql_update = "UPDATE products SET quantity = '{$new_qty}' WHERE id = '{$p_id}'";
      $db->query($sql_update);

      $session->msg('s', "Sale recorded successfully. Stock updated.");
      redirect('add_sale.php', false);
    } else {
      $session->msg('d', "Failed to record the sale.");
      redirect('add_sale.php', false);
    }
  }
?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-6">
    <?php echo display_msg($msg); ?>
    <form method="post" action="ajax.php" autocomplete="off" id="sug-form">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-btn">
              <button type="submit" class="btn btn-primary">Find It</button>
            </span>
            <input type="text" id="sug_input" class="form-control" name="title"  placeholder="Search for product name">
         </div>
         <div id="result" class="list-group"></div>
        </div>
    </form>
  </div>
</div>
<div class="row">

  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Sale Eidt</span>
       </strong>
      </div>
      <div class="panel-body">
        <form method="post" action="add_sale.php">
         <table class="table table-bordered">
           <thead>
            <th> Item </th>
            <th> Price </th>
            <th> Qty </th>
            <th> Total </th>
            <th> Date</th>
            <th> Action</th>
           </thead>
             <tbody  id="product_info"> </tbody>
         </table>
       </form>
      </div>
    </div>
  </div>

</div>

<?php include_once('layouts/footer.php'); ?>
