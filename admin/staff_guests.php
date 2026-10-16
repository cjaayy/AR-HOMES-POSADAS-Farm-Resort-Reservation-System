<?php
/**
 * Staff - Guest Management (limited)
 * Read-only view of guest profiles and reservation count
 */
session_start();
// Accept both admin session (with staff role) OR staff-specific session
$isAdminAsStaff = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_role'] ?? '') === 'staff';
$isStaffSession = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminAsStaff && !$isStaffSession) {
    header('Location: ../index.html'); exit;
}
require_once '../config/connection.php';

try {
    $db = new Database(); $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, created_at FROM users ORDER BY created_at DESC LIMIT 500");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}

$staffName = $isStaffSession ? ($_SESSION['staff_full_name'] ?? 'Staff Member') : ($_SESSION['admin_full_name'] ?? 'Staff Member');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Guests — Staff</title>
  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="../logo/ar-homes-logo.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="../logo/ar-homes-logo.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="../logo/ar-homes-logo.png" />
  <link rel="stylesheet" href="../css/admin-styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&family=Bungee+Spice&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
  <style>
    /* Stats Overview */
    .stats-overview-guests {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card-guest {
      background: white;
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      display: flex;
      align-items: center;
      gap: 20px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card-guest::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 120px;
      height: 120px;
      background: inherit;
      border-radius: 50%;
      opacity: 0.1;
      transform: translate(40px, -40px);
    }

    .stat-card-guest:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .stat-card-guest.purple {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .stat-card-guest.green {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }

    .stat-card-guest.blue {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }

    .stat-card-guest.orange {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }

    .stat-card-guest-icon {
      width: 64px;
      height: 64px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      flex-shrink: 0;
    }

    .stat-card-guest-content {
      flex: 1;
      position: relative;
      z-index: 1;
    }

    .stat-card-guest-value {
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .stat-card-guest-label {
      font-size: 14px;
      opacity: 0.95;
      font-weight: 500;
    }

    /* Guest Cards View */
    .guests-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .guest-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      transition: all 0.3s ease;
      border: 2px solid #f1f5f9;
    }

    .guest-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
      border-color: #667eea;
    }

    .guest-card-header {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 2px solid #f1f5f9;
    }

    .guest-avatar {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .guest-info {
      flex: 1;
      min-width: 0;
    }

    .guest-name {
      font-size: 18px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 4px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .guest-id {
      font-size: 12px;
      color: #64748b;
      font-weight: 600;
    }

    .guest-details {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .guest-detail-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px;
      background: #f8fafc;
      border-radius: 8px;
      font-size: 14px;
    }

    .guest-detail-icon {
      width: 32px;
      height: 32px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 14px;
      flex-shrink: 0;
    }

    .guest-detail-text {
      flex: 1;
      color: #475569;
      font-weight: 500;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .guest-footer {
      margin-top: 16px;
      padding-top: 16px;
      border-top: 2px solid #f1f5f9;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .guest-date {
      font-size: 12px;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .guest-action-btn {
      padding: 6px 12px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .guest-action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    /* View Toggle */
    .view-toggle {
      display: flex;
      gap: 8px;
      background: white;
      padding: 6px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .view-toggle-btn {
      padding: 10px 20px;
      background: transparent;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      color: #64748b;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .view-toggle-btn.active {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    /* Table View Styles */
    .guests-table-container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      overflow: hidden;
      display: none;
    }

    .guests-table-container.active {
      display: block;
    }

    .guests-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    .guests-table thead {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .guests-table thead th {
      padding: 18px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      letter-spacing: 0.5px;
    }

    .guests-table tbody tr {
      border-bottom: 1px solid #e2e8f0;
      transition: all 0.2s ease;
    }

    .guests-table tbody tr:hover {
      background: #f8fafc;
      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }

    .guests-table tbody td {
      padding: 16px 18px;
      font-size: 14px;
      color: #475569;
    }

    .table-avatar {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 16px;
    }

    /* Action Bar */
    .action-bar-guests {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding: 16px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      flex-wrap: wrap;
      gap: 12px;
    }

    .search-export-group {
      display: flex;
      gap: 12px;
      align-items: center;
      flex: 1;
    }

    .search-box-guests {
      flex: 1;
      max-width: 400px;
      position: relative;
    }

    .search-box-guests i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
    }

    .search-box-guests input {
      width: 100%;
      padding: 12px 16px 12px 44px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.2s ease;
    }

    .search-box-guests input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-export {
      padding: 12px 20px;
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-export:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-refresh {
      padding: 12px 20px;
      background: white;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      color: #475569;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-refresh:hover {
      border-color: #667eea;
      color: #667eea;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    .btn-refresh i {
      transition: transform 0.3s ease;
    }

    .btn-refresh:hover i {
      transform: rotate(180deg);
    }

    @keyframes fadeInGuest {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .guest-card {
      animation: fadeInGuest 0.4s ease backwards;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .guests-grid {
        grid-template-columns: 1fr;
      }

      .action-bar-guests {
        flex-direction: column;
        align-items: stretch;
      }

      .search-export-group {
        flex-direction: column;
      }

      .search-box-guests {
        max-width: 100%;
      }
    }
  </style>
  <script>
    window.logout = window.logout || function(){
      try{
        fetch('staff_logout.php', { method: 'POST', credentials: 'include' })
          .then(res => res.json().catch(() => null))
          .then(() => {
            const isInAdmin = window.location.pathname.includes('/admin/');
            window.location.href = isInAdmin ? '../index.html' : 'index.html';
          })
          .catch(() => { const isInAdmin = window.location.pathname.includes('/admin/'); window.location.href = isInAdmin ? '../index.html' : 'index.html'; });
      }catch(e){ window.location.href = 'staff_logout.php'; }
    };
  </script>
</head>
<body>
  <div class="admin-container">
    <?php include 'staff_header.php'; ?>

    <main class="main-content" style="padding-top:100px;">
      <section class="content-section active">
        <!-- Page Header -->
        <div class="section-header" style="margin-bottom:30px;">
          <div>
            <h2 style="font-size:32px; font-weight:700; color:#1e293b; margin-bottom:8px;">Guest Management</h2>
            <p style="color:#64748b; font-size:16px;">View guest profiles and contact information. Editing is admin-only.</p>
          </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-overview-guests">
          <div class="stat-card-guest purple">
            <div class="stat-card-guest-icon">
              <i class="fas fa-users"></i>
            </div>
            <div class="stat-card-guest-content">
              <div class="stat-card-guest-value" id="totalGuests"><?php echo count($users); ?></div>
              <div class="stat-card-guest-label">Total Guests</div>
            </div>
          </div>
          <div class="stat-card-guest green">
            <div class="stat-card-guest-icon">
              <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-card-guest-content">
              <div class="stat-card-guest-value" id="verifiedGuests">0</div>
              <div class="stat-card-guest-label">Verified Accounts</div>
            </div>
          </div>
          <div class="stat-card-guest blue">
            <div class="stat-card-guest-icon">
              <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-card-guest-content">
              <div class="stat-card-guest-value" id="newThisMonth">0</div>
              <div class="stat-card-guest-label">New This Month</div>
            </div>
          </div>
          <div class="stat-card-guest orange">
            <div class="stat-card-guest-icon">
              <i class="fas fa-star"></i>
            </div>
            <div class="stat-card-guest-content">
              <div class="stat-card-guest-value" id="activeGuests">0</div>
              <div class="stat-card-guest-label">Active Guests</div>
            </div>
          </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar-guests">
          <div class="search-export-group">
            <div class="search-box-guests">
              <i class="fas fa-search"></i>
              <input id="searchGuest" placeholder="Search by name, email, or phone..." />
            </div>
            <button class="btn-export" onclick="exportGuestsCSV()">
              <i class="fas fa-file-excel"></i> Export
            </button>
          </div>
          <div style="display:flex; gap:12px; align-items:center;">
            <button class="btn-refresh" onclick="location.reload()">
              <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <div class="view-toggle">
              <button class="view-toggle-btn active" onclick="switchView('grid')" id="viewGridBtn">
                <i class="fas fa-th-large"></i> Cards
              </button>
              <button class="view-toggle-btn" onclick="switchView('table')" id="viewTableBtn">
                <i class="fas fa-table"></i> Table
              </button>
            </div>
          </div>
        </div>

        <!-- Cards View -->
        <div class="guests-grid" id="guestsGrid">
          <?php if (empty($users)): ?>
            <div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:#94a3b8;">
              <i class="fas fa-users" style="font-size:64px; margin-bottom:20px; opacity:0.3;"></i>
              <p style="font-size:18px; font-weight:600;">No guests found</p>
              <p style="font-size:14px; margin-top:8px;">Guest profiles will appear here</p>
            </div>
          <?php else: foreach ($users as $index => $u): ?>
            <div class="guest-card" style="animation-delay:<?php echo ($index * 0.05); ?>s" data-search="<?php echo strtolower(htmlspecialchars($u['full_name'] . ' ' . $u['email'] . ' ' . ($u['phone'] ?? ''))); ?>">
              <div class="guest-card-header">
                <div class="guest-avatar">
                  <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                </div>
                <div class="guest-info">
                  <div class="guest-name"><?php echo htmlspecialchars($u['full_name']); ?></div>
                  <div class="guest-id">Guest #<?php echo htmlspecialchars($u['id']); ?></div>
                </div>
              </div>
              <div class="guest-details">
                <div class="guest-detail-item">
                  <div class="guest-detail-icon">
                    <i class="fas fa-envelope"></i>
                  </div>
                  <div class="guest-detail-text"><?php echo htmlspecialchars($u['email']); ?></div>
                </div>
                <div class="guest-detail-item">
                  <div class="guest-detail-icon">
                    <i class="fas fa-phone"></i>
                  </div>
                  <div class="guest-detail-text"><?php echo htmlspecialchars($u['phone'] ?? 'Not provided'); ?></div>
                </div>
              </div>
              <div class="guest-footer">
                <div class="guest-date">
                  <i class="fas fa-calendar-plus"></i>
                  Member since <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                </div>
                <button class="guest-action-btn" onclick="viewGuestDetails(<?php echo $u['id']; ?>)">
                  <i class="fas fa-eye"></i> View
                </button>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

        <!-- Table View -->
        <div class="guests-table-container" id="guestsTableView">
          <table class="guests-table">
            <thead>
              <tr>
                <th style="width:60px; text-align:center;">#</th>
                <th>Guest</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Member Since</th>
                <th style="text-align:center;">Actions</th>
              </tr>
            </thead>
            <tbody id="guestsTableBody">
              <?php if (empty($users)): ?>
                <tr><td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">No guests found</td></tr>
              <?php else: foreach ($users as $u): ?>
                <tr data-search="<?php echo strtolower(htmlspecialchars($u['full_name'] . ' ' . $u['email'] . ' ' . ($u['phone'] ?? ''))); ?>">
                  <td style="text-align:center; font-weight:600; color:#64748b;">#<?php echo htmlspecialchars($u['id']); ?></td>
                  <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                      <div class="table-avatar">
                        <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                      </div>
                      <span style="font-weight:600; color:#1e293b;"><?php echo htmlspecialchars($u['full_name']); ?></span>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($u['email']); ?></td>
                  <td><?php echo htmlspecialchars($u['phone'] ?? 'Not provided'); ?></td>
                  <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                  <td style="text-align:center;">
                    <button class="guest-action-btn" onclick="viewGuestDetails(<?php echo $u['id']; ?>)">
                      <i class="fas fa-eye"></i> View
                    </button>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
  <script>
    let currentView = 'grid';

    // Initialize stats
    document.addEventListener('DOMContentLoaded', function() {
      calculateStats();
    });

    // Calculate statistics
    function calculateStats() {
      const total = <?php echo count($users); ?>;
      const users = <?php echo json_encode($users); ?>;
      
      // Calculate verified accounts (users with email)
      const verified = users.filter(u => u.email && u.email.length > 0).length;
      document.getElementById('verifiedGuests').textContent = verified;
      
      // Calculate new this month
      const now = new Date();
      const thisMonth = now.getMonth();
      const thisYear = now.getFullYear();
      const newThisMonth = users.filter(u => {
        const created = new Date(u.created_at);
        return created.getMonth() === thisMonth && created.getFullYear() === thisYear;
      }).length;
      document.getElementById('newThisMonth').textContent = newThisMonth;
      
      // Calculate active guests (has phone and email)
      const active = users.filter(u => u.email && u.phone).length;
      document.getElementById('activeGuests').textContent = active;
    }

    // Search functionality
    document.getElementById('searchGuest').addEventListener('input', function(e) {
      const q = e.target.value.toLowerCase();
      
      if (currentView === 'grid') {
        const cards = document.querySelectorAll('.guest-card');
        let visibleCount = 0;
        cards.forEach(card => {
          const searchData = card.getAttribute('data-search');
          if (searchData.includes(q)) {
            card.style.display = '';
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });
        
        // Show empty state if no results
        if (visibleCount === 0 && q !== '') {
          showNoResults();
        }
      } else {
        const rows = document.querySelectorAll('#guestsTableBody tr');
        rows.forEach(row => {
          const searchData = row.getAttribute('data-search');
          if (searchData && searchData.includes(q)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }
    });

    // Switch between grid and table view
    function switchView(view) {
      currentView = view;
      const gridView = document.getElementById('guestsGrid');
      const tableView = document.getElementById('guestsTableView');
      const gridBtn = document.getElementById('viewGridBtn');
      const tableBtn = document.getElementById('viewTableBtn');
      
      if (view === 'grid') {
        gridView.style.display = 'grid';
        tableView.classList.remove('active');
        gridBtn.classList.add('active');
        tableBtn.classList.remove('active');
      } else {
        gridView.style.display = 'none';
        tableView.classList.add('active');
        gridBtn.classList.remove('active');
        tableBtn.classList.add('active');
      }
      
      // Reapply search filter
      document.getElementById('searchGuest').dispatchEvent(new Event('input'));
    }

    // View guest details
    function viewGuestDetails(guestId) {
      const users = <?php echo json_encode($users); ?>;
      const guest = users.find(u => u.id == guestId);
      
      if (!guest) {
        showNotification('Guest not found', 'error');
        return;
      }
      
      const modalHTML = `
        <div style="padding:24px;">
          <div style="text-align:center; margin-bottom:30px;">
            <div style="width:100px; height:100px; background:linear-gradient(135deg, #667eea, #764ba2); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:white; font-size:42px; font-weight:700; margin-bottom:16px;">
              ${guest.full_name.charAt(0).toUpperCase()}
            </div>
            <h2 style="font-size:28px; font-weight:700; color:#1e293b; margin:0 0 8px 0;">${escapeHtml(guest.full_name)}</h2>
            <p style="color:#64748b; font-size:14px; font-weight:600;">Guest #${guest.id}</p>
          </div>
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
            <div style="padding:20px; background:#f8fafc; border-radius:12px;">
              <div style="color:#64748b; font-size:12px; font-weight:600; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Email Address</div>
              <div style="color:#1e293b; font-weight:600; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-envelope" style="color:#667eea;"></i>
                ${escapeHtml(guest.email)}
              </div>
            </div>
            <div style="padding:20px; background:#f8fafc; border-radius:12px;">
              <div style="color:#64748b; font-size:12px; font-weight:600; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Phone Number</div>
              <div style="color:#1e293b; font-weight:600; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-phone" style="color:#667eea;"></i>
                ${escapeHtml(guest.phone || 'Not provided')}
              </div>
            </div>
          </div>
          
          <div style="padding:20px; background:#f8fafc; border-radius:12px; margin-bottom:24px;">
            <div style="color:#64748b; font-size:12px; font-weight:600; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Member Since</div>
            <div style="color:#1e293b; font-weight:600; display:flex; align-items:center; gap:8px;">
              <i class="fas fa-calendar-plus" style="color:#667eea;"></i>
              ${formatDate(guest.created_at)}
            </div>
          </div>
          
          <div style="padding:20px; background:linear-gradient(135deg, #667eea, #764ba2); border-radius:12px; color:white; text-align:center;">
            <i class="fas fa-info-circle" style="font-size:20px; margin-bottom:8px;"></i>
            <p style="margin:0; font-size:14px; opacity:0.9;">Editing and deletion of guest accounts is admin-only.</p>
          </div>
        </div>
      `;
      
      showModal('Guest Profile', modalHTML);
    }

    // Export guests to CSV
    function exportGuestsCSV() {
      const users = <?php echo json_encode($users); ?>;
      if (users.length === 0) {
        showNotification('No guests to export', 'warning');
        return;
      }
      
      const headers = ['ID', 'Full Name', 'Email', 'Phone', 'Member Since'];
      const rows = users.map(u => [
        u.id,
        u.full_name,
        u.email,
        u.phone || 'N/A',
        u.created_at
      ]);
      
      const csv = [headers.join(',')]
        .concat(rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')))
        .join('\n');
      
      const blob = new Blob([csv], { type: 'text/csv' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `guests_export_${new Date().toISOString().split('T')[0]}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
      
      showNotification('Guests exported successfully', 'success');
    }

    // Helper functions
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function formatDate(dateString) {
      const date = new Date(dateString);
      const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return date.toLocaleDateString('en-US', options);
    }

    function showModal(title, content) {
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.3s ease;
      `;
      
      modal.innerHTML = `
        <div style="background:white; border-radius:20px; max-width:600px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:slideUp 0.3s ease; max-height:90vh; overflow-y:auto;">
          <div style="padding:24px; border-bottom:2px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #667eea, #764ba2); color:white; border-radius:20px 20px 0 0;">
            <h3 style="margin:0; font-size:20px; font-weight:600; display:flex; align-items:center; gap:10px;">
              <i class="fas fa-user-circle"></i> ${title}
            </h3>
            <button onclick="this.closest('[style*=fixed]').remove()" style="width:36px; height:36px; border:none; background:rgba(255,255,255,0.2); color:white; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s;">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div>${content}</div>
        </div>
      `;
      
      document.body.appendChild(modal);
      modal.addEventListener('click', function(e) {
        if (e.target === modal) modal.remove();
      });
    }

    function showNotification(msg, type = 'info') {
      const colors = {
        success: 'linear-gradient(135deg, #10b981, #059669)',
        error: 'linear-gradient(135deg, #ef4444, #dc2626)',
        info: 'linear-gradient(135deg, #3b82f6, #2563eb)',
        warning: 'linear-gradient(135deg, #f59e0b, #d97706)'
      };
      const icons = {
        success: 'check-circle',
        error: 'times-circle',
        info: 'info-circle',
        warning: 'exclamation-triangle'
      };
      
      const notification = document.createElement('div');
      notification.style.cssText = `
        position: fixed;
        right: 20px;
        bottom: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 10001;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
      `;
      notification.innerHTML = `<i class="fas fa-${icons[type]}"></i> ${msg}`;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }
  </script>
  <style>
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideInRight {
      from { opacity: 0; transform: translateX(100px); }
      to { opacity: 1; transform: translateX(0); }
    }
    @keyframes slideOutRight {
      from { opacity: 1; transform: translateX(0); }
      to { opacity: 0; transform: translateX(100px); }
    }
  </style>
  <script src="../js/admin-script.js"></script>
</body>
</html>
