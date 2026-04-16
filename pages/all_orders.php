<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once '../database/queries.php';

$orders = all_presc_history();

$pageTitle   = 'All Orders';
$currentPage = 'all_orders';
include './partials/header.php';
?>

<div class="page-header">
  <h1>All Orders</h1>
  <p><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> in the system</p>
</div>

<?php if (empty($orders)): ?>
  <div class="empty-state">No orders found.</div>
<?php else: ?>
  <?php foreach ($orders as $order): ?>
    <div class="order-card">
      <div class="order-card-header">
        <div class="order-card-left">
          <div class="order-id">Order #<?= htmlspecialchars($order['orderID'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="order-meta">
            <span><?= htmlspecialchars($order['customer'], ENT_QUOTES, 'UTF-8') ?></span>
            <span><?= htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        </div>
        <span class="badge badge-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars(ucfirst($order['status']), ENT_QUOTES, 'UTF-8') ?>
        </span>
      </div>
      <div class="order-items-list">
        <?php foreach ($order['items'] as $item): ?>
          <div class="order-item-row">
            <span class="order-item-name"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="order-item-price">£<?= number_format($item['price'], 2) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include './partials/footer.php'; ?>
