<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include "../database/queries.php";

$flash_success = null;
$flash_error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderID   = (int)$_POST['order_id'];
    $newStatus = trim($_POST['new_status']);
    $result    = update_order_status($orderID, $newStatus);
    if ($result['success']) {
        $label = ucfirst($newStatus);
        $flash_success = 'Order #' . $orderID . ' marked as <strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong>.';
    } else {
        $flash_error = $result['error'] ?? 'Could not update order status.';
    }
}

$orders    = get_today_orders();
$pending   = array_values(array_filter($orders, fn($o) => $o['status'] === 'pending'));
$paid      = array_values(array_filter($orders, fn($o) => $o['status'] === 'paid'));
$cancelled = array_values(array_filter($orders, fn($o) => $o['status'] === 'cancelled'));

$pageTitle   = 'Active Orders';
$currentPage = 'active_orders';
include './partials/header.php';
?>

<div class="page-header">
  <div>
    <h1>Active Orders</h1>
    <p>Today &mdash; <?= date('l, F j, Y') ?></p>
  </div>
  <div class="page-header-actions">
    <a href="./new_order.php" class="btn">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Prescription
    </a>
    <a href="./register_customer.php" class="btn btn-ghost">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Register Customer
    </a>
  </div>
</div>

<?php if ($flash_success): ?>
  <div class="alert alert-success">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <span><?= $flash_success ?></span>
  </div>
<?php endif; ?>
<?php if ($flash_error): ?>
  <div class="alert alert-error">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span><?= htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
<?php endif; ?>

<!-- Stats -->
<div class="ao-stats">
  <div class="ao-stat ao-stat-pending">
    <span class="ao-stat-count"><?= count($pending) ?></span>
    <span class="ao-stat-label">Pending</span>
  </div>
  <div class="ao-stat ao-stat-paid">
    <span class="ao-stat-count"><?= count($paid) ?></span>
    <span class="ao-stat-label">Paid</span>
  </div>
  <div class="ao-stat ao-stat-cancelled">
    <span class="ao-stat-count"><?= count($cancelled) ?></span>
    <span class="ao-stat-label">Cancelled</span>
  </div>
  <div class="ao-stat ao-stat-total">
    <span class="ao-stat-count"><?= count($orders) ?></span>
    <span class="ao-stat-label">Total Today</span>
  </div>
</div>

<?php if (empty($orders)): ?>
  <div class="ao-empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    <p>No orders have been placed today yet.</p>
    <a href="./new_order.php" class="btn">Create the first prescription</a>
  </div>
<?php else: ?>

  <?php
  $sections = [
    ['key' => 'pending',   'label' => 'Pending',   'orders' => $pending,   'color' => 'warning'],
    ['key' => 'paid',      'label' => 'Paid',       'orders' => $paid,      'color' => 'success'],
    ['key' => 'cancelled', 'label' => 'Cancelled',  'orders' => $cancelled, 'color' => 'danger'],
  ];
  foreach ($sections as $section):
    if (empty($section['orders'])) continue;
  ?>

  <div class="ao-section ao-section-<?= $section['color'] ?>">
    <div class="ao-section-heading">
      <span class="badge badge-<?= $section['key'] ?>"><?= $section['label'] ?></span>
      <span class="ao-section-count"><?= count($section['orders']) ?> order<?= count($section['orders']) !== 1 ? 's' : '' ?></span>
    </div>

    <div class="ao-grid">
      <?php foreach ($section['orders'] as $order):
        $total = array_sum(array_column($order['items'], 'price'));
        $time  = date('g:i A', strtotime($order['created_at']));
      ?>
      <div class="ao-card">
        <div class="ao-card-header">
          <div class="ao-card-id">#<?= $order['orderID'] ?></div>
          <div class="ao-card-time"><?= $time ?></div>
        </div>

        <div class="ao-card-customer">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <?= htmlspecialchars($order['customer'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <ul class="ao-items">
          <?php foreach ($order['items'] as $item): ?>
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.5 3.5a6 6 0 0 1 6 6v5a6 6 0 0 1-12 0v-5a6 6 0 0 1 6-6z"/><line x1="4.5" y1="12" x2="16.5" y2="12"/></svg>
              <span><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
              <span class="ao-item-price"><?= number_format($item['price'], 2) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="ao-card-footer">
          <div class="ao-total">Total &nbsp;<strong><?= number_format($total, 2) ?></strong></div>

          <?php if ($order['status'] === 'pending'): ?>
          <div class="ao-actions">
            <form method="POST" action="">
              <input type="hidden" name="order_id"   value="<?= $order['orderID'] ?>">
              <input type="hidden" name="new_status" value="paid">
              <button class="btn btn-sm ao-btn-paid" type="submit">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Mark Paid
              </button>
            </form>
            <form method="POST" action="">
              <input type="hidden" name="order_id"   value="<?= $order['orderID'] ?>">
              <input type="hidden" name="new_status" value="cancelled">
              <button class="btn btn-sm btn-danger" type="submit">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Cancel
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php endforeach; ?>
<?php endif; ?>

<?php include './partials/footer.php'; ?>
