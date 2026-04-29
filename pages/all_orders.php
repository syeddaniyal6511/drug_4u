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

  <div class="search-wrap">
    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input id="searchInput" type="text" class="search-input" placeholder="Search by order, customer, drug, status…">
    <span id="searchCount" class="search-count"></span>
  </div>
  <div id="noResults" class="empty-state" style="display:none">No orders match your search.</div>

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

<script>
(function () {
  var input   = document.getElementById('searchInput');
  var counter = document.getElementById('searchCount');
  var noRes   = document.getElementById('noResults');
  var cards   = document.querySelectorAll('.order-card');
  if (!input) return;
  input.addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    var visible = 0;
    cards.forEach(function (c) {
      var show = !q || c.textContent.toLowerCase().includes(q);
      c.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    counter.textContent = q ? visible + ' result' + (visible !== 1 ? 's' : '') : '';
    noRes.style.display = (q && visible === 0) ? '' : 'none';
  });
})();
</script>

<?php include './partials/footer.php'; ?>
