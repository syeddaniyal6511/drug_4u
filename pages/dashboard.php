<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
include './partials/header.php';
?>

<div class="page-header">
  <h1>Dashboard</h1>
  <p>Welcome back — Pharmacy Management System</p>
</div>

<div class="dash-grid">

  <a href="./register_customer.php" class="dash-card">
    <div class="dash-card-icon" style="background:rgba(79,142,247,0.15);color:var(--accent)">
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <div>
      <div class="dash-card-label">Register Customer</div>
      <div class="dash-card-sub">Add a new patient to the system</div>
    </div>
  </a>

  <a href="./newdrug.php" class="dash-card">
    <div class="dash-card-icon" style="background:rgba(52,211,153,0.15);color:#34d399">
      <svg viewBox="0 0 24 24"><path d="M10.5 3.5a6 6 0 0 1 6 6v5a6 6 0 0 1-12 0v-5a6 6 0 0 1 6-6z"/><line x1="4.5" y1="12" x2="16.5" y2="12"/></svg>
    </div>
    <div>
      <div class="dash-card-label">Add Drug</div>
      <div class="dash-card-sub">Register a new drug in inventory</div>
    </div>
  </a>

  <a href="./all_stock.php" class="dash-card">
    <div class="dash-card-icon" style="background:rgba(45,212,191,0.12);color:#2dd4bf">
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
    </div>
    <div>
      <div class="dash-card-label">All Stock</div>
      <div class="dash-card-sub">View all stock batches &amp; expiry dates</div>
    </div>
  </a>

  <a href="./add_stock.php" class="dash-card">
    <div class="dash-card-icon" style="background:rgba(45,212,191,0.15);color:#2dd4bf">
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
    </div>
    <div>
      <div class="dash-card-label">Add Stock</div>
      <div class="dash-card-sub">Record a new stock batch for a drug</div>
    </div>
  </a>

  <a href="./new_order.php" class="dash-card">
    <div class="dash-card-icon" style="background:rgba(251,191,36,0.15);color:#fbbf24">
      <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    </div>
    <div>
      <div class="dash-card-label">New Order</div>
      <div class="dash-card-sub">Create a cashier order for a customer</div>
    </div>
  </a>

  <a href="./prescription_history.php" class="dash-card">
    <div class="dash-card-icon" style="background:rgba(167,139,250,0.15);color:#a78bfa">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    </div>
    <div>
      <div class="dash-card-label">Rx History</div>
      <div class="dash-card-sub">Review a customer's prescription history</div>
    </div>
  </a>

  <a href="./all_orders.php" class="dash-card">
    <div class="dash-card-icon" style="background:rgba(248,113,113,0.15);color:#f87171">
      <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div>
      <div class="dash-card-label">All Orders</div>
      <div class="dash-card-sub">Browse all prescriptions &amp; orders</div>
    </div>
  </a>

</div>

<?php include './partials/footer.php'; ?>
