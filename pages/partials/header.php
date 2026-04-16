<?php
/**
 * Shared page header + sidebar navigation.
 * Set $pageTitle and $currentPage before including.
 *
 * $currentPage values:
 *   dashboard | register_customer | newdrug | new_order
 *   prescription_history | all_orders
 */
$pageTitle   = $pageTitle   ?? 'Drug 4U';
$currentPage = $currentPage ?? '';

function nav_active(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — Drug 4U</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/main.css" />
</head>
<body>
<div class="layout">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon">
        <!-- Pill / pharmacy icon -->
        <svg viewBox="0 0 24 24">
          <path d="M10.5 3.5a6 6 0 0 1 6 6v5a6 6 0 0 1-12 0v-5a6 6 0 0 1 6-6z"/>
          <line x1="4.5" y1="12" x2="16.5" y2="12"/>
        </svg>
      </div>
      <div>
        <div class="brand-name">Drug 4U</div>
        <div class="brand-sub">Pharmacy</div>
      </div>
    </div>

    <nav class="nav-section">
      <div class="nav-label">Overview</div>

      <a href="./dashboard.php" class="nav-link<?= nav_active('dashboard', $currentPage) ?>">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </a>

      <div class="nav-label">Pharmacy</div>

      <a href="./register_customer.php" class="nav-link<?= nav_active('register_customer', $currentPage) ?>">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Register Customer
      </a>

      <a href="./newdrug.php" class="nav-link<?= nav_active('newdrug', $currentPage) ?>">
        <svg viewBox="0 0 24 24"><path d="M10.5 3.5a6 6 0 0 1 6 6v5a6 6 0 0 1-12 0v-5a6 6 0 0 1 6-6z"/><line x1="4.5" y1="12" x2="16.5" y2="12"/></svg>
        Add Drug
      </a>

      <a href="./new_order.php" class="nav-link<?= nav_active('new_order', $currentPage) ?>">
        <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        New Order
      </a>

      <div class="nav-label">Records</div>

      <a href="./prescription_history.php" class="nav-link<?= nav_active('prescription_history', $currentPage) ?>">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Rx History
      </a>

      <a href="./all_orders.php" class="nav-link<?= nav_active('all_orders', $currentPage) ?>">
        <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        All Orders
      </a>
    </nav>

    <div class="nav-footer">
      <a href="./logout.php" class="nav-link">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </a>
    </div>
  </aside>

  <!-- ── Main content ── -->
  <main class="main">
