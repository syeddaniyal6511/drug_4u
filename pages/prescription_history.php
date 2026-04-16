<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once '../database/queries.php';

$customers = get_all_customers();
$selectedCustomerID = isset($_GET['customerID']) ? (int)$_GET['customerID'] : null;
$orders = [];
$customerName = '';

if ($selectedCustomerID) {
    $orders = presc_history($selectedCustomerID);
    // Find customer name for heading
    foreach ($customers as $c) {
        if ((int)$c['customerID'] === $selectedCustomerID) {
            $customerName = htmlspecialchars($c['firstname'] . ' ' . $c['lastname'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
}

$pageTitle   = 'Rx History';
$currentPage = 'prescription_history';
include './partials/header.php';
?>

<div class="page-header">
  <h1>Prescription History</h1>
  <p>Review order history for a specific customer</p>
</div>

<!-- Customer selector -->
<div class="card card-sm mb-16" style="margin-bottom:24px">
  <form method="GET" action="">
    <div class="form-grid cols-1">
      <div class="field">
        <label for="customerID">Select customer</label>
        <select name="customerID" id="customerID" required>
          <option value="">— choose a customer —</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= htmlspecialchars($c['customerID'], ENT_QUOTES, 'UTF-8') ?>"
              <?= ($selectedCustomerID === (int)$c['customerID']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['firstname'] . ' ' . $c['lastname'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">View History</button>
    </div>
  </form>
</div>

<?php if ($selectedCustomerID): ?>

  <div class="page-header" style="margin-bottom:16px">
    <h2 style="font-size:17px;font-weight:600">
      <?= $customerName ?>'s Orders
    </h2>
    <p><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> found</p>
  </div>

  <?php if (empty($orders)): ?>
    <div class="empty-state">No orders found for this customer.</div>
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

<?php endif; ?>

<?php include './partials/footer.php'; ?>
