<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once '../database/queries.php';

$customers = get_all_customers();

$pageTitle   = 'All Customers';
$currentPage = 'all_customers';
include './partials/header.php';
?>

<div class="page-header">
  <h1>All Customers</h1>
  <p><?= count($customers) ?> customer<?= count($customers) !== 1 ? 's' : '' ?> registered</p>
</div>

<?php if (empty($customers)): ?>
  <div class="empty-state">No customers found. <a href="./register_customer.php">Register a customer</a></div>
<?php else: ?>

  <div class="search-wrap">
    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input id="searchInput" type="text" class="search-input" placeholder="Search by name, postcode, allergy…">
    <span id="searchCount" class="search-count"></span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Gender</th>
          <th>Date of Birth</th>
          <th>Postcode</th>
          <th>Allergies</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $row): ?>
          <tr>
            <td style="color:var(--muted)"><?= (int)$row['customerID'] ?></td>
            <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(ucfirst($row['gender']), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['dob'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-family:var(--mono, monospace)"><?= htmlspecialchars($row['postcode'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if ($row['allergies']): ?>
                <?= htmlspecialchars($row['allergies'], ENT_QUOTES, 'UTF-8') ?>
              <?php else: ?>
                <span style="color:var(--muted)">None</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<script>
(function () {
  var input   = document.getElementById('searchInput');
  var counter = document.getElementById('searchCount');
  var rows    = document.querySelectorAll('tbody tr');
  if (!input) return;
  input.addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    var visible = 0;
    rows.forEach(function (r) {
      var show = !q || r.textContent.toLowerCase().includes(q);
      r.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    counter.textContent = q ? visible + ' result' + (visible !== 1 ? 's' : '') : '';
  });
})();
</script>

<?php include './partials/footer.php'; ?>