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

<?php include './partials/footer.php'; ?>