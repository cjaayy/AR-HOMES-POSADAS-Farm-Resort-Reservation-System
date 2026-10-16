<?php
/**
 * Staff Reservations Management
 * Uses staff-specific session variables (separate from admin)
 */
session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}
$staffName = $_SESSION['staff_full_name'] ?? 'Staff Member';
$staffId = $_SESSION['staff_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Reservations - Staff</title>
  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="../images/logo/ar-homes-logo.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="../images/logo/ar-homes-logo.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="../images/logo/ar-homes-logo.png" />
  <link rel="stylesheet" href="../css/admin-styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&family=Bungee+Spice&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
  <script>
    // Staff-specific logout function - only clears staff session, not admin
    window.logout = function(){
      try{
        fetch('staff_logout.php', { method: 'POST', credentials: 'include' })
          .then(res => res.json().catch(() => null))
          .then(() => {
            window.location.href = '../index.html';
          })
          .catch(() => {
            window.location.href = '../index.html';
          });
      }catch(e){ window.location.href='staff_logout.php'; }
    };
  </script>
  <style>
    /* Enhanced Reservations Page Styles */
    .reservations-actions{display:flex;gap:8px;align-items:center}.small{font-size:13px}
    
    /* Stats Cards */
    .stats-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card-res {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
      position: relative;
      overflow: hidden;
    }
    
    .stat-card-res::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 100px;
      height: 100px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      transform: translate(30px, -30px);
    }
    
    .stat-card-res.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 24px rgba(16, 185, 129, 0.25); }
    .stat-card-res.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 8px 24px rgba(245, 158, 11, 0.25); }
    .stat-card-res.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 8px 24px rgba(239, 68, 68, 0.25); }
    
    .stat-card-res-icon {
      font-size: 32px;
      opacity: 0.9;
      margin-bottom: 12px;
    }
    
    .stat-card-res-value {
      font-size: 36px;
      font-weight: 700;
      line-height: 1;
      margin-bottom: 8px;
    }
    
    .stat-card-res-label {
      font-size: 14px;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Enhanced Table */
    .users-table tbody tr {
      transition: all 0.2s ease;
    }
    
    .users-table tbody tr:hover {
      background: #f8fafc;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    /* Status Badges with Icons */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .status-badge::before {
      content: '';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
    }
    
    .status-badge.pending::before { content: '\f017'; }
    .status-badge.confirmed::before { content: '\f058'; }
    .status-badge.completed::before { content: '\f00c'; }
    .status-badge.canceled::before { content: '\f057'; }
    
    /* Enhanced Action Buttons */
    .btn-action {
      width: 36px;
      height: 36px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      transition: all 0.2s ease;
      transform: scale(1);
    }
    
    .btn-action:hover {
      transform: scale(1.1);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    /* Enhanced Modal */
    #createModal .form-group input,
    #createModal .form-group select {
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      padding: 10px 14px;
      transition: all 0.2s ease;
    }
    
    #createModal .form-group input:focus,
    #createModal .form-group select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    /* Filter Section Enhancement */
    .filter-section {
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      padding: 20px;
      border-radius: 16px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    /* Action Buttons Enhancement */
    .action-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 12px;
    }
    
    .btn-group {
      display: flex;
      gap: 10px;
    }
    
    /* Loading Animation */
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    
    .loading-row {
      animation: pulse 1.5s ease-in-out infinite;
    }
    
    /* Quick Filter Chips */
    /* Enhanced Filter Chips */
    .quick-filters-enhanced {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 12px;
    }
    
    .filter-chip-enhanced {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .filter-chip-enhanced::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 0;
      height: 100%;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
      transition: width 0.3s ease;
    }

    .filter-chip-enhanced:hover::before {
      width: 100%;
    }

    .filter-chip-enhanced:hover {
      border-color: #667eea;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }

    .filter-chip-enhanced.active {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-color: #667eea;
      box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
    }

    .filter-chip-enhanced.active::before {
      display: none;
    }

    .filter-chip-enhanced .chip-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 16px;
      position: relative;
      z-index: 1;
      transition: transform 0.3s ease;
    }

    .filter-chip-enhanced:hover .chip-icon {
      transform: scale(1.1) rotate(5deg);
    }

    .filter-chip-enhanced span {
      flex: 1;
      font-size: 14px;
      font-weight: 600;
      color: #475569;
      position: relative;
      z-index: 1;
      transition: color 0.3s ease;
    }

    .filter-chip-enhanced.active span {
      color: white;
    }

    .filter-chip-enhanced .chip-count {
      padding: 4px 10px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      font-size: 12px;
      font-weight: 700;
      color: #64748b;
      position: relative;
      z-index: 1;
      min-width: 32px;
      text-align: center;
      transition: all 0.3s ease;
    }

    .filter-chip-enhanced.active .chip-count {
      background: rgba(255, 255, 255, 0.25);
      color: white;
    }

    /* Date Filter Styles */
    .date-filter-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .date-filter-wrapper label {
      font-size: 13px;
      font-weight: 600;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .date-filter-wrapper label i {
      color: #667eea;
    }

    .date-filter-wrapper input[type="date"] {
      padding: 12px 16px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 500;
      color: #475569;
      background: white;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .date-filter-wrapper input[type="date"]:hover {
      border-color: #cbd5e1;
    }

    .date-filter-wrapper input[type="date"]:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .date-filter-wrapper input[type="date"]::-webkit-calendar-picker-indicator {
      cursor: pointer;
      padding: 4px;
      border-radius: 4px;
      transition: background 0.2s ease;
    }

    .date-filter-wrapper input[type="date"]::-webkit-calendar-picker-indicator:hover {
      background: rgba(102, 126, 234, 0.1);
    }

    /* Responsive Design for Filters */
    @media (max-width: 768px) {
      .quick-filters-enhanced {
        grid-template-columns: repeat(2, 1fr);
      }

      .filter-chip-enhanced {
        padding: 12px;
      }

      .filter-chip-enhanced .chip-icon {
        width: 32px;
        height: 32px;
        font-size: 14px;
      }

      .filter-chip-enhanced span {
        font-size: 13px;
      }
    }

    @media (max-width: 480px) {
      .quick-filters-enhanced {
        grid-template-columns: 1fr;
      }
    }

    .action-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding: 16px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .btn-group {
      display: flex;
      gap: 8px;
    }

    .btn-secondary {
      padding: 10px 20px;
      background: white;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      color: #475569;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-secondary:hover {
      border-color: #667eea;
      color: #667eea;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    .btn-secondary i {
      transition: transform 0.3s ease;
    }

    .btn-secondary:hover i {
      transform: rotate(180deg);
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(100px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes slideOutRight {
      from {
        opacity: 1;
        transform: translateX(0);
      }
      to {
        opacity: 0;
        transform: translateX(100px);
      }
    }

    .table-container {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      overflow: hidden;
    }

    .users-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    .users-table thead {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .users-table thead th {
      padding: 16px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      letter-spacing: 0.5px;
    }

    .users-table tbody tr {
      border-bottom: 1px solid #e2e8f0;
      transition: all 0.3s ease;
    }

    .users-table tbody tr:hover {
      background: #f8fafc;
      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }

    .users-table tbody td {
      padding: 16px;
      font-size: 14px;
      color: #475569;
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: capitalize;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .status-badge.pending {
      background: #fef3c7;
      color: #92400e;
    }

    .status-badge.confirmed {
      background: #d1fae5;
      color: #065f46;
    }

    .status-badge.completed {
      background: #dbeafe;
      color: #1e40af;
    }

    .status-badge.canceled {
      background: #fee2e2;
      color: #991b1b;
    }

    .action-buttons {
      display: flex;
      gap: 6px;
      justify-content: center;
    }

    .btn-action {
      width: 36px;
      height: 36px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      font-size: 14px;
    }

    .btn-action:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn-approve {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }

    .btn-cancel {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
    }

    .btn-view {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }

    /* Modal Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      align-items: center;
      justify-content: center;
      z-index: 1000;
      animation: fadeIn 0.3s ease;
    }

    .modal-content {
      background: white;
      border-radius: 16px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .modal-header {
      padding: 24px;
      border-bottom: 2px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border-radius: 16px 16px 0 0;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .modal-close {
      width: 36px;
      height: 36px;
      border: none;
      background: rgba(255, 255, 255, 0.2);
      color: white;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }

    .modal-close:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: rotate(90deg);
    }

    .modal-body {
      padding: 24px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #475569;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-group label i {
      color: #667eea;
      width: 16px;
    }

    .form-group input {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.2s ease;
    }

    .form-group input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .modal-footer {
      padding: 20px 24px;
      border-top: 2px solid #e2e8f0;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      background: #f8fafc;
      border-radius: 0 0 16px 16px;
    }

    .modal-footer .btn-primary {
      display: flex;
      align-items: center;
      gap: 8px;
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <?php include 'staff_header.php'; ?>

    <main class="main-content" style="padding-top:100px;">
      <section class="content-section active">
        <div class="section-header" style="margin-bottom:30px;">
          <div>
            <h2 style="font-size:32px; font-weight:700; color:#1e293b; margin-bottom:8px;">Reservations Management</h2>
            <p style="color:#64748b; font-size:16px;">View and manage all resort reservations efficiently</p>
          </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-overview">
          <div class="stat-card-res">
            <div class="stat-card-res-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-card-res-value" id="totalReservations">0</div>
            <div class="stat-card-res-label">Total Reservations</div>
          </div>
          <div class="stat-card-res green">
            <div class="stat-card-res-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-res-value" id="confirmedCount">0</div>
            <div class="stat-card-res-label">Confirmed</div>
          </div>
          <div class="stat-card-res orange">
            <div class="stat-card-res-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-card-res-value" id="pendingCount">0</div>
            <div class="stat-card-res-label">Pending</div>
          </div>
          <div class="stat-card-res" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); box-shadow: 0 8px 24px rgba(139, 92, 246, 0.25);">
            <div class="stat-card-res-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-card-res-value" id="rebookingCount">0</div>
            <div class="stat-card-res-label">Rebooking Requests</div>
          </div>
        </div>

        <!-- Enhanced Filters Section -->
        <div style="background:white; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-size:16px; font-weight:600; color:#1e293b; display:flex; align-items:center; gap:8px;">
              <i class="fas fa-filter" style="color:#667eea;"></i> Filter Reservations
            </h3>
            <button onclick="clearFilters()" style="padding:6px 14px; background:#f1f5f9; border:none; border-radius:6px; font-size:13px; font-weight:600; color:#64748b; cursor:pointer; transition:all 0.2s;">
              <i class="fas fa-redo"></i> Clear Filters
            </button>
          </div>

          <!-- Quick Status Filters -->
          <div class="quick-filters-enhanced">
            <div class="filter-chip-enhanced active" onclick="quickFilter('all')" id="chip-all">
              <div class="chip-icon" style="background:linear-gradient(135deg, #667eea, #764ba2);">
                <i class="fas fa-list"></i>
              </div>
              <span>All</span>
              <div class="chip-count" id="count-all">0</div>
            </div>
            <div class="filter-chip-enhanced" onclick="quickFilter('pending')" id="chip-pending">
              <div class="chip-icon" style="background:linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="fas fa-clock"></i>
              </div>
              <span>Pending</span>
              <div class="chip-count" id="count-pending">0</div>
            </div>
            <div class="filter-chip-enhanced" onclick="quickFilter('confirmed')" id="chip-confirmed">
              <div class="chip-icon" style="background:linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-check"></i>
              </div>
              <span>Confirmed</span>
              <div class="chip-count" id="count-confirmed">0</div>
            </div>
            <div class="filter-chip-enhanced" onclick="quickFilter('completed')" id="chip-completed">
              <div class="chip-icon" style="background:linear-gradient(135deg, #3b82f6, #2563eb);">
                <i class="fas fa-flag-checkered"></i>
              </div>
              <span>Completed</span>
              <div class="chip-count" id="count-completed">0</div>
            </div>
            <div class="filter-chip-enhanced" onclick="quickFilter('canceled')" id="chip-canceled">
              <div class="chip-icon" style="background:linear-gradient(135deg, #ef4444, #dc2626);">
                <i class="fas fa-ban"></i>
              </div>
              <span>Canceled</span>
              <div class="chip-count" id="count-canceled">0</div>
            </div>
          </div>

          <!-- Date Range Filter -->
          <div style="display:flex; gap:12px; margin-top:16px; padding-top:16px; border-top:2px solid #f1f5f9;">
            <div class="date-filter-wrapper">
              <label><i class="fas fa-calendar-day"></i> From Date</label>
              <input type="date" id="filterFrom" onchange="applyFilters()">
            </div>
            <div class="date-filter-wrapper">
              <label><i class="fas fa-calendar-day"></i> To Date</label>
              <input type="date" id="filterTo" onchange="applyFilters()">
            </div>
          </div>
        </div>

        <div class="users-container">
          <div class="users-header">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input id="searchBox" placeholder="Search guest, room, or contact" oninput="applyFilters()" />
            </div>
          </div>

          <div class="action-bar">
            <div style="display:flex; align-items:center; gap:12px;">
              <button class="btn-secondary" onclick="fetchAllReservations()" style="display:flex; align-items:center; gap:8px;">
                <i class="fas fa-sync-alt"></i> Refresh
              </button>
              <span style="color:#64748b; font-size:14px;" id="lastUpdate">Last updated: Just now</span>
            </div>
            <div class="btn-group">
              <button class="btn-primary" onclick="showCreateForm()" style="display:flex; align-items:center; gap:8px;">
                <i class="fas fa-plus"></i> Add Walk-in
              </button>
              <button class="btn-primary" onclick="exportCSV()" style="background:linear-gradient(135deg, #10b981, #059669); display:flex; align-items:center; gap:8px;">
                <i class="fas fa-file-excel"></i> Export
              </button>
            </div>
          </div>

          <div class="table-container">
            <table class="users-table" id="reservationsTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Guest</th>
                  <th>Booking Details</th>
                  <th>Check-in</th>
                  <th>Check-out</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="reservationsBody"><tr><td colspan="8" style="text-align:center;padding:2rem;">Loading...</td></tr></tbody>
            </table>
          </div>

          <div style="display:flex; justify-content:flex-end; margin-top:10px; gap:8px; align-items:center;">
            <div id="paginationInfo" style="font-size:13px;color:#666"></div>
            <button class="btn-secondary" id="prevPage" onclick="changePage(-1)" disabled>&larr; Prev</button>
            <button class="btn-secondary" id="nextPage" onclick="changePage(1)" disabled>Next &rarr;</button>
          </div>
        </div>

        <!-- Enhanced modal/create form -->
        <div id="createModal" class="modal-overlay">
          <div class="modal-content">
            <div class="modal-header">
              <h3><i class="fas fa-calendar-plus"></i> Create New Reservation</h3>
              <button type="button" onclick="hideCreateForm()" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <form id="createForm" onsubmit="return submitCreate(event)">
              <div class="modal-body">
                <div class="form-group">
                  <label><i class="fas fa-user"></i> Guest Name *</label>
                  <input name="guest_name" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                  <label><i class="fas fa-phone"></i> Phone Number</label>
                  <input name="guest_phone" placeholder="Enter phone number">
                </div>
                <div class="form-group">
                  <label><i class="fas fa-door-open"></i> Room Assignment</label>
                  <input name="room" placeholder="e.g., Room 101">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label><i class="fas fa-calendar-check"></i> Check-in Date</label>
                    <input type="date" name="check_in_date">
                  </div>
                  <div class="form-group">
                    <label><i class="fas fa-calendar-times"></i> Check-out Date</label>
                    <input type="date" name="check_out_date">
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" onclick="hideCreateForm()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Create Reservation</button>
              </div>
            </form>
          </div>
        </div>

      </section>
    </main>
  </div>

  <script>
    // Client-side state for reservations
    let allReservations = [];
    let filteredReservations = [];
    let currentPage = 1;
    const pageSize = 12;
    let currentQuickFilter = 'all';

    // Quick filter function
    function quickFilter(status) {
      currentQuickFilter = status;
      
      // Update chip active states
      document.querySelectorAll('.filter-chip-enhanced').forEach(chip => chip.classList.remove('active'));
      document.getElementById('chip-' + status).classList.add('active');
      
      // Update the search based on status
      if(status === 'all') {
        // Show all reservations
        applyFilters();
      } else {
        // Filter by specific status
        applyFilters();
      }
    }

    // Clear all filters
    function clearFilters() {
      // Reset quick filter
      currentQuickFilter = 'all';
      document.querySelectorAll('.filter-chip-enhanced').forEach(chip => chip.classList.remove('active'));
      document.getElementById('chip-all').classList.add('active');
      
      // Clear search box
      document.getElementById('searchBox').value = '';
      
      // Clear date filters
      document.getElementById('filterFrom').value = '';
      document.getElementById('filterTo').value = '';
      
      // Reapply filters
      applyFilters();
      
      showNotification('Filters cleared', 'info');
    }

    // Update stats cards with actual data
    function updateStatsCards() {
      const total = allReservations.length;
      const confirmed = allReservations.filter(r => r.status === 'confirmed').length;
      const pending = allReservations.filter(r => r.status === 'pending' || r.status === 'pending_confirmation').length;
      const canceled = allReservations.filter(r => r.status === 'cancelled' || r.status === 'canceled').length;
      const completed = allReservations.filter(r => r.status === 'completed').length;
      const rebooking = allReservations.filter(r => r.rebooking_requested == 1 && !r.rebooking_approved).length;
      
      // Update main stats cards
      document.getElementById('totalReservations').textContent = total;
      document.getElementById('confirmedCount').textContent = confirmed;
      document.getElementById('pendingCount').textContent = pending;
      document.getElementById('rebookingCount').textContent = rebooking;
      
      // Update filter chip counts
      document.getElementById('count-all').textContent = total;
      document.getElementById('count-pending').textContent = pending;
      document.getElementById('count-confirmed').textContent = confirmed;
      document.getElementById('count-completed').textContent = completed;
      document.getElementById('count-canceled').textContent = canceled;
    }

    // Update last update time
    function updateLastUpdateTime() {
      const now = new Date();
      const hours = now.getHours().toString().padStart(2, '0');
      const minutes = now.getMinutes().toString().padStart(2, '0');
      document.getElementById('lastUpdate').textContent = `Last updated: ${hours}:${minutes}`;
    }

    async function fetchAllReservations(){
      const url = `staff_get_reservations.php?limit=1000`;
      try{
        const res = await fetch(url, { credentials: 'include' });
        const data = await res.json();
        if(!data.success){ console.error('Failed to load reservations', data.message); return; }
        allReservations = data.reservations || [];
        updateStatsCards();
        updateLastUpdateTime();
        applyFilters();
      }catch(err){ console.error('Error fetching reservations', err); document.getElementById('reservationsBody').innerHTML = `<tr><td colspan="9" style="text-align:center;color:#b00;">Error loading reservations</td></tr>`; }
    }

    function applyFilters(){
      const from = document.getElementById('filterFrom').value;
      const to = document.getElementById('filterTo').value;
      const q = (document.getElementById('searchBox').value || '').toLowerCase();

      filteredReservations = allReservations.filter(r => {
        // Apply quick filter status
        if(currentQuickFilter !== 'all' && String(r.status) !== currentQuickFilter) return false;
        
        // Apply date filters
        if(from && r.check_in_date && r.check_in_date < from) return false;
        if(to && r.check_in_date && r.check_in_date > to) return false;
        
        // Apply search query
        if(q){
          const hay = ((r.guest_name||'') + ' ' + (r.room||'') + ' ' + (r.guest_phone||'') + ' ' + (r.guest_email||'')).toLowerCase();
          if(!hay.includes(q)) return false;
        }
        return true;
      });

      // reset to first page
      currentPage = 1;
      renderPage();
    }

    function renderPage(){
      const tbody = document.getElementById('reservationsBody');
      if(!filteredReservations || filteredReservations.length===0){
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:2rem; color:#666;">No reservations found</td></tr>`;
        document.getElementById('paginationInfo').textContent = '';
        document.getElementById('prevPage').disabled = true;
        document.getElementById('nextPage').disabled = true;
        return;
      }

      const total = filteredReservations.length;
      const totalPages = Math.ceil(total / pageSize);
      const start = (currentPage - 1) * pageSize;
      const pageRows = filteredReservations.slice(start, start + pageSize);

      const bookingTypeLabels = {
        'daytime': { icon: 'fa-sun', label: 'DAYTIME', color: '#f59e0b' },
        'nighttime': { icon: 'fa-moon', label: 'NIGHTTIME', color: '#6366f1' },
        '22hours': { icon: 'fa-clock', label: '22 HOURS', color: '#8b5cf6' }
      };
      
      const packageLabels = {
        'all_rooms': 'All Rooms',
        'aircon': 'Aircon',
        'basic': 'Basic'
      };
      
      const rowsHtml = pageRows.map((r, index) => {
        const bookingType = bookingTypeLabels[r.booking_type] || { icon: 'fa-calendar', label: 'N/A', color: '#64748b' };
        const packageType = packageLabels[r.package_type] || r.package_type || 'N/A';
        
        return `
        <tr style="animation: fadeIn 0.3s ease-in-out ${index * 0.05}s backwards;">
          <td style="text-align:center; font-weight:600; color:#64748b;">#${r.reservation_id}</td>
          <td>
            <div style="display:flex; align-items:center; gap:10px;">
              <div style="width:40px; height:40px; background:linear-gradient(135deg, #667eea, #764ba2); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:16px;">
                ${escapeHtml((r.guest_name||'U')[0].toUpperCase())}
              </div>
              <div>
                <div style="font-weight:600; color:#1e293b;">${escapeHtml(r.guest_name||'')}</div>
                <div style="font-size:12px; color:#64748b;"><i class="fas fa-phone"></i> ${escapeHtml(r.guest_phone||'N/A')}</div>
              </div>
            </div>
          </td>
          <td>
            <div style="display:flex; flex-direction:column; gap:4px;">
              <div style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:${bookingType.color}20; border-radius:12px; font-size:11px; font-weight:700; color:${bookingType.color}; width:fit-content;">
                <i class="fas ${bookingType.icon}"></i>
                ${bookingType.label}
              </div>
              <div style="font-size:12px; color:#64748b;">
                <i class="fas fa-cube" style="color:#667eea;"></i> ${packageType}
              </div>
              <div style="font-size:11px; color:#94a3b8;">
                <i class="fas fa-bed"></i> ${escapeHtml(r.room||'TBD')}
              </div>
            </div>
          </td>
          <td>
            <div style="font-weight:600; color:#1e293b; margin-bottom:2px;">${r.check_in_date||'N/A'}</div>
            <div style="font-size:11px; color:#64748b;"><i class="fas fa-clock" style="color:#10b981;"></i> ${r.check_in_time||'N/A'}</div>
          </td>
          <td>
            <div style="font-weight:600; color:#1e293b; margin-bottom:2px;">${r.check_out_date||'N/A'}</div>
            <div style="font-size:11px; color:#64748b;"><i class="fas fa-clock" style="color:#ef4444;"></i> ${r.check_out_time||'N/A'}</div>
          </td>
          <td>
            <div style="font-weight:700; color:#1e293b; font-size:15px;">₱${parseFloat(r.total_amount||0).toLocaleString('en-US', {minimumFractionDigits:2})}</div>
            <div style="font-size:11px; color:#64748b;">Down: ₱${parseFloat(r.downpayment_amount||0).toLocaleString('en-US', {minimumFractionDigits:2})}</div>
            <div style="font-size:10px; color:${r.downpayment_paid ? '#10b981' : '#ef4444'}; font-weight:600;">
              ${r.downpayment_paid ? '✓ Paid' : '✗ Unpaid'}
            </div>
          </td>
          <td>
            <span class="status-badge ${r.status||''}">${escapeHtml(r.status||'').replace('_', ' ')}</span>
            ${r.rebooking_requested == 1 && !r.rebooking_approved ? '<div style="margin-top:4px;"><span style="background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:8px;font-size:10px;font-weight:600;"><i class="fas fa-calendar-alt"></i> Rebooking Request</span></div>' : ''}
          </td>
          <td style="text-align:center">
            <div class="action-buttons">
              ${r.rebooking_requested == 1 && !r.rebooking_approved ? `
                <button onclick="approveRebooking('${r.reservation_id}')" class="btn-action" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;" title="Approve Rebooking" aria-label="Approve rebooking">
                  <i class="fas fa-calendar-check"></i>
                </button>
                <button onclick="rejectRebooking('${r.reservation_id}')" class="btn-action" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;" title="Reject Rebooking" aria-label="Reject rebooking">
                  <i class="fas fa-calendar-times"></i>
                </button>
              ` : `
                <button onclick="updateStatus('${r.reservation_id}', 'confirmed')" class="btn-action btn-approve" title="Approve" aria-label="Approve reservation"><i class="fas fa-check"></i></button>
                <button onclick="updateStatus('${r.reservation_id}', 'cancelled')" class="btn-action btn-cancel" title="Cancel" aria-label="Cancel reservation"><i class="fas fa-times"></i></button>
              `}
              <button onclick="viewReservation('${r.reservation_id}')" class="btn-action btn-view" title="View" aria-label="View reservation"><i class="fas fa-eye"></i></button>
            </div>
          </td>
        </tr>
      `}).join('');

      tbody.innerHTML = rowsHtml;

      document.getElementById('paginationInfo').textContent = `Page ${currentPage} of ${totalPages} — ${total} reservations`;
      document.getElementById('prevPage').disabled = currentPage <= 1;
      document.getElementById('nextPage').disabled = currentPage >= totalPages;
    }

    function changePage(direction){
      currentPage += direction;
      if(currentPage < 1) currentPage = 1;
      renderPage();
    }

    function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    async function showCreateForm(){ document.getElementById('createModal').style.display='flex'; }
    function hideCreateForm(){ document.getElementById('createModal').style.display='none'; }

    async function submitCreate(e){ e.preventDefault(); const form = new FormData(e.target); form.append('action','create');
      try{
        const res = await fetch('staff_actions.php',{method:'POST', body: form, credentials: 'include'});
        const data = await res.json();
        if(data.success){ hideCreateForm(); await fetchAllReservations(); showNotification('Reservation created', 'success'); } else { showNotification('Error: '+(data.message||''),'error'); }
      }catch(err){ console.error(err); showNotification('Failed to create reservation','error'); }
    }

    async function updateStatus(id, status){ if(!confirm('Change status?')) return; const form = new FormData(); form.append('action','update_status'); form.append('reservation_id', id); form.append('status', status); try{ const res = await fetch('staff_actions.php',{method:'POST', body: form, credentials: 'include'}); const data = await res.json(); if(data.success){ await fetchAllReservations(); showNotification('Status updated','success'); } else { showNotification('Error: '+(data.message||''),'error'); } }catch(err){ console.error(err); showNotification('Failed to update status','error'); } }

    function viewReservation(id){
      const r = allReservations.find(x => String(x.reservation_id) === String(id));
      if(!r) return showNotification('Reservation not found','error');
      
      const bookingTypeLabels = {
        'daytime': { icon: 'fa-sun', label: 'DAYTIME (9AM-5PM)', color: '#f59e0b' },
        'nighttime': { icon: 'fa-moon', label: 'NIGHTTIME (7PM-7AM)', color: '#6366f1' },
        '22hours': { icon: 'fa-clock', label: '22 HOURS (2PM-12NN)', color: '#8b5cf6' }
      };
      
      const packageLabels = {
        'daytime': 'Daytime Package',
        'nighttime': 'Nighttime Package',
        '22hours': '22 Hours Package',
        'venue-daytime': 'Venue - Daytime',
        'venue-nighttime': 'Venue - Nighttime',
        'venue-22hours': 'Venue - 22 Hours',
        // Legacy support
        'all_rooms': 'Nighttime Package',
        'aircon': 'Daytime Package',
        'basic': '22 Hours Package'
      };
      
      const bookingType = bookingTypeLabels[r.booking_type] || { icon: 'fa-calendar', label: 'N/A', color: '#64748b' };
      const packageType = packageLabels[r.package_type] || r.package_type || 'N/A';
      
      const html = `
        <div style="padding:0;">
          <div style="background:linear-gradient(135deg, ${bookingType.color}ee, ${bookingType.color}); color:white; padding:28px 24px; margin:-24px -24px 24px; border-radius:16px 16px 0 0;">
            <div style="display:flex; align-items:center; gap:20px;">
              <div style="width:72px; height:72px; background:rgba(255,255,255,0.25); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:700;">
                ${escapeHtml((r.guest_name||'U')[0].toUpperCase())}
              </div>
              <div style="flex:1;">
                <div style="font-size:24px; font-weight:700; margin-bottom:6px;">${escapeHtml(r.guest_name||'')}</div>
                <div style="font-size:14px; opacity:0.95; display:flex; align-items:center; gap:8px;">
                  <i class="fas fa-bookmark"></i> Reservation #${r.reservation_id}
                </div>
              </div>
            </div>
          </div>
          
          <div style="margin-bottom:20px; padding:16px; background:${bookingType.color}10; border-left:4px solid ${bookingType.color}; border-radius:8px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
              <i class="fas ${bookingType.icon}" style="font-size:24px; color:${bookingType.color};"></i>
              <div>
                <div style="font-weight:700; color:#1e293b; font-size:16px;">${bookingType.label}</div>
                <div style="font-size:13px; color:#64748b;">${packageType}</div>
              </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
              <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Duration</div>
                <div style="font-weight:600; color:#1e293b;">${r.number_of_days || r.number_of_nights ? (r.number_of_days ? r.number_of_days + ' day(s)' : r.number_of_nights + ' night(s)') : 'N/A'}</div>
              </div>
              <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Room</div>
                <div style="font-weight:600; color:#1e293b;"><i class="fas fa-bed" style="color:#667eea; margin-right:6px;"></i>${escapeHtml(r.room||'TBD')}</div>
              </div>
            </div>
          </div>
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
            <div style="padding:16px; background:#f8fafc; border-radius:12px; border-left:4px solid #10b981;">
              <div style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <i class="fas fa-calendar-check" style="color:#10b981;"></i> Check-in
              </div>
              <div style="color:#1e293b; font-weight:700; font-size:15px; margin-bottom:4px;">${r.check_in_date||'N/A'}</div>
              <div style="color:#64748b; font-size:12px;"><i class="fas fa-clock"></i> ${r.check_in_time||'N/A'}</div>
            </div>
            <div style="padding:16px; background:#f8fafc; border-radius:12px; border-left:4px solid #ef4444;">
              <div style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <i class="fas fa-calendar-times" style="color:#ef4444;"></i> Check-out
              </div>
              <div style="color:#1e293b; font-weight:700; font-size:15px; margin-bottom:4px;">${r.check_out_date||'N/A'}</div>
              <div style="color:#64748b; font-size:12px;"><i class="fas fa-clock"></i> ${r.check_out_time||'N/A'}</div>
            </div>
          </div>
          
          <div style="background:#f8fafc; padding:20px; border-radius:12px; margin-bottom:20px;">
            <div style="font-weight:700; color:#1e293b; margin-bottom:16px; font-size:15px; display:flex; align-items:center; gap:8px;">
              <i class="fas fa-money-bill-wave" style="color:#10b981;"></i> Payment Details
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
              <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Total Amount</div>
                <div style="font-size:18px; font-weight:700; color:#1e293b;">₱${parseFloat(r.total_amount||0).toLocaleString('en-US', {minimumFractionDigits:2})}</div>
              </div>
              <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Downpayment</div>
                <div style="font-size:18px; font-weight:700; color:#f59e0b;">₱${parseFloat(r.downpayment_amount||0).toLocaleString('en-US', {minimumFractionDigits:2})}</div>
                <div style="font-size:11px; font-weight:600; color:${r.downpayment_paid ? '#10b981' : '#ef4444'}; margin-top:4px;">
                  ${r.downpayment_paid ? '✓ Paid' : '✗ Unpaid'}
                </div>
              </div>
              <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Remaining Balance</div>
                <div style="font-size:16px; font-weight:700; color:#667eea;">₱${parseFloat(r.remaining_balance||0).toLocaleString('en-US', {minimumFractionDigits:2})}</div>
              </div>
              <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Security Bond</div>
                <div style="font-size:16px; font-weight:700; color:#8b5cf6;">₱${parseFloat(r.security_bond||2000).toLocaleString('en-US', {minimumFractionDigits:2})}</div>
                <div style="font-size:11px; font-weight:600; color:${r.security_bond_paid ? '#10b981' : '#ef4444'}; margin-top:4px;">
                  ${r.security_bond_paid ? '✓ Paid' : '✗ Unpaid'}
                </div>
              </div>
            </div>
            <div style="margin-top:16px; padding-top:16px; border-top:2px solid #e2e8f0;">
              <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Payment Method</div>
              <div style="font-weight:600; color:#1e293b; text-transform:capitalize;"><i class="fas fa-credit-card" style="color:#667eea; margin-right:6px;"></i>${r.payment_method||'N/A'}</div>
            </div>
          </div>
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
            <div style="padding:16px; background:#f8fafc; border-radius:12px;">
              <div style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase; margin-bottom:8px;"><i class="fas fa-phone"></i> Phone</div>
              <div style="color:#1e293b; font-weight:600; font-size:14px;">${escapeHtml(r.guest_phone||'N/A')}</div>
            </div>
            <div style="padding:16px; background:#f8fafc; border-radius:12px;">
              <div style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase; margin-bottom:8px;"><i class="fas fa-envelope"></i> Email</div>
              <div style="color:#1e293b; font-weight:600; font-size:14px; word-break:break-all;">${escapeHtml(r.guest_email||'N/A')}</div>
            </div>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; padding:16px; background:#f8fafc; border-radius:12px; border-top:3px solid ${bookingType.color};">
            <div>
              <div style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase; margin-bottom:6px;">Status</div>
              <span class="status-badge ${r.status||''}" style="font-size:13px;">${escapeHtml(r.status||'').replace('_', ' ')}</span>
            </div>
            <div style="text-align:right;">
              <div style="color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase; margin-bottom:6px;">Created</div>
              <div style="color:#475569; font-size:12px; font-weight:600;">${r.created_at||'N/A'}</div>
            </div>
          </div>
          
          ${r.rebooking_requested == 1 && !r.rebooking_approved ? `
            <div style="margin-top:16px; padding:16px; background:#fef3c7; border-left:4px solid #f59e0b; border-radius:12px;">
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <i class="fas fa-calendar-alt" style="color:#f59e0b; font-size:20px;"></i>
                <div style="font-weight:700; color:#92400e; font-size:15px;">Rebooking Request</div>
              </div>
              <div style="background:#fff; padding:12px; border-radius:8px; margin-bottom:12px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:8px;">
                  <div>
                    <div style="font-size:11px; color:#78716c; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Original Date</div>
                    <div style="font-weight:700; color:#1e293b;">${r.check_in_date||'N/A'}</div>
                  </div>
                  <div>
                    <div style="font-size:11px; color:#78716c; font-weight:600; text-transform:uppercase; margin-bottom:4px;">New Date</div>
                    <div style="font-weight:700; color:#10b981;">${r.rebooking_new_date||'N/A'}</div>
                  </div>
                </div>
                <div style="font-size:11px; color:#78716c; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Reason</div>
                <div style="color:#475569; font-size:13px;">${escapeHtml(r.rebooking_reason||'No reason provided')}</div>
              </div>
              <div style="display:flex; gap:8px;">
                <button onclick="approveRebooking('${r.reservation_id}')" style="flex:1; padding:10px; background:linear-gradient(135deg,#10b981,#059669); color:#fff; border:none; border-radius:10px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                  <i class="fas fa-check"></i> Approve
                </button>
                <button onclick="rejectRebooking('${r.reservation_id}')" style="flex:1; padding:10px; background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; border:none; border-radius:10px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                  <i class="fas fa-times"></i> Reject
                </button>
              </div>
            </div>
          ` : ''}
        </div>
      `;
      showModal('Reservation Details', html);
    }

    function showModal(title, htmlContent) {
      const existingModal = document.getElementById('viewModal');
      if (existingModal) existingModal.remove();
      
      const modal = document.createElement('div');
      modal.id = 'viewModal';
      modal.className = 'modal-overlay';
      modal.style.display = 'flex';
      modal.innerHTML = `
        <div class="modal-content">
          <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> ${title}</h3>
            <button type="button" onclick="document.getElementById('viewModal').remove()" class="modal-close">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            ${htmlContent}
          </div>
        </div>
      `;
      document.body.appendChild(modal);
      
      // Close on background click
      modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
      });
    }

    async function approveRebooking(reservationId) {
      if (!confirm('Approve this rebooking request? The original date will be released and the new date will be locked.')) return;
      
      const form = new FormData();
      form.append('reservation_id', reservationId);
      form.append('action', 'approve');
      
      try {
        const res = await fetch('approve_rebooking.php', {
          method: 'POST',
          body: form,
          credentials: 'include'
        });
        
        const data = await res.json();
        
        if (data.success) {
          await fetchAllReservations();
          showNotification('✅ Rebooking approved successfully!', 'success');
        } else {
          showNotification('❌ ' + (data.message || 'Failed to approve rebooking'), 'error');
        }
      } catch (error) {
        console.error('Rebooking approval error:', error);
        showNotification('❌ Failed to approve rebooking', 'error');
      }
    }
    
    async function rejectRebooking(reservationId) {
      const reason = prompt('Reason for rejection (optional):');
      if (reason === null) return; // User cancelled
      
      const form = new FormData();
      form.append('reservation_id', reservationId);
      form.append('action', 'reject');
      if (reason) form.append('rejection_reason', reason);
      
      try {
        const res = await fetch('approve_rebooking.php', {
          method: 'POST',
          body: form,
          credentials: 'include'
        });
        
        const data = await res.json();
        
        if (data.success) {
          await fetchAllReservations();
          showNotification('✅ Rebooking request rejected', 'success');
        } else {
          showNotification('❌ ' + (data.message || 'Failed to reject rebooking'), 'error');
        }
      } catch (error) {
        console.error('Rebooking rejection error:', error);
        showNotification('❌ Failed to reject rebooking', 'error');
      }
    }

    function exportCSV(){
      if(!filteredReservations || filteredReservations.length===0) return showNotification('No reservations to export','warning');
      const rows = filteredReservations.map(r => ({
        id: r.reservation_id,
        guest: r.guest_name,
        phone: r.guest_phone,
        email: r.guest_email,
        room: r.room,
        check_in: r.check_in_date,
        check_out: r.check_out_date,
        status: r.status,
        created_at: r.created_at
      }));
      const csv = [Object.keys(rows[0]).join(',')].concat(rows.map(r => Object.values(r).map(v => '"'+String((v||'')).replace(/"/g,'""')+'"').join(','))).join('\n');
      const blob = new Blob([csv], { type: 'text/csv' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a'); a.href = url; a.download = 'reservations_export.csv'; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
    }

    // Enhanced toast notification helper
    function showNotification(msg, type='info'){
      const colors = { 
        success: 'linear-gradient(135deg, #10b981, #059669)', 
        error: 'linear-gradient(135deg, #ef4444, #dc2626)', 
        info: 'linear-gradient(135deg, #3b82f6, #2563eb)', 
        warning: 'linear-gradient(135deg, #f59e0b, #d97706)' 
      };
      const icons = { success: 'check-circle', error: 'times-circle', info: 'info-circle', warning: 'exclamation-triangle' };
      const n = document.createElement('div'); 
      n.style.cssText = `
        position:fixed; right:20px; bottom:20px; 
        background:${colors[type]||colors.info}; 
        color:#fff; padding:16px 20px; border-radius:12px; 
        box-shadow:0 8px 24px rgba(0,0,0,0.2); 
        z-index:9999; display:flex; align-items:center; gap:12px;
        animation: slideInRight 0.3s ease;
      `; 
      n.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}" style="font-size:20px;"></i><span style="font-weight:600;">${msg}</span>`;
      document.body.appendChild(n); 
      setTimeout(()=>{
        n.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(()=>n.remove(), 300);
      }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function(){ fetchAllReservations(); });
  </script>
  <script src="../js/admin-script.js"></script>
</body>
</html>

