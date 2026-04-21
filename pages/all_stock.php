<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once '../database/queries.php';

$stock = get_all_stock();

$pageTitle   = 'All Stock';
$currentPage = 'all_stock';
include './partials/header.php';
?>

<div class="page-header">
  <h1>All Stock</h1>
  <p><?= count($stock) ?> batch<?= count($stock) !== 1 ? 'es' : '' ?> in inventory</p>
</div>

<?php if (empty($stock)): ?>
  <div class="empty-state">No stock entries found. <a href="./add_stock.php">Add stock</a></div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Drug</th>
          <th>Description</th>
          <th>Batch No.</th>
          <th>Qty</th>
          <th>Buy / pack</th>
          <th>Sell / pack</th>
          <th>Expiry</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stock as $row):
          $expired = $row['expiry_date'] && $row['expiry_date'] < date('Y-m-d');
          $lowStock = (int)$row['quantity'] === 0;
        ?>
          <tr>
            <td style="color:var(--muted)"><?= (int)$row['stockID'] ?></td>
            <td><?= htmlspecialchars($row['drug_name'],  ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['stock_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-family:var(--mono, monospace)"><?= htmlspecialchars($row['batch_number'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if ($lowStock): ?>
                <span class="badge badge-cancelled"><?= (int)$row['quantity'] ?></span>
              <?php else: ?>
                <?= (int)$row['quantity'] ?>
              <?php endif; ?>
            </td>
            <td>£<?= number_format((float)$row['buying_price_per_pack'],  2) ?></td>
            <td>£<?= number_format((float)$row['selling_price_per_pack'], 2) ?></td>
            <td>
              <?php if ($expired): ?>
                <span class="badge badge-cancelled"><?= htmlspecialchars($row['expiry_date'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php else: ?>
                <?= htmlspecialchars($row['expiry_date'], ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include './partials/footer.php'; ?>
