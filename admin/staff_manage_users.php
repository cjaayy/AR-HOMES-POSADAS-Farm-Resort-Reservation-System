<?php
/**
 * Staff Manage Users - UI copied from admin manage users but read-only for staff
 * Uses staff-specific session variables (separate from admin)
 */
session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../index.html'); exit;
}
$staffName = $_SESSION['staff_full_name'] ?? 'Staff Member';
$staffId = $_SESSION['staff_id'] ?? 0;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Users — Staff</title>
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
    /* Enhanced User Stats Cards */
    .users-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
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

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      opacity: 0.05;
      transform: translate(40px, -40px);
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .stat-card:nth-child(1) { border-left: 4px solid #667eea; }
    .stat-card:nth-child(2) { border-left: 4px solid #10b981; }
    .stat-card:nth-child(3) { border-left: 4px solid #f59e0b; }
    .stat-card:nth-child(4) { border-left: 4px solid #3b82f6; }

    .stat-card i {
      width: 64px;
      height: 64px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      flex-shrink: 0;
      position: relative;
      z-index: 1;
    }

    .stat-card:nth-child(1) i {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .stat-card:nth-child(2) i {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }

    .stat-card:nth-child(3) i {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }

    .stat-card:nth-child(4) i {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }

    .stat-info {
      position: relative;
      z-index: 1;
    }

    .stat-info h3 {
      font-size: 32px;
      font-weight: 700;
      color: #1e293b;
      margin: 0 0 4px 0;
    }

    .stat-info p {
      font-size: 14px;
      color: #64748b;
      margin: 0;
      font-weight: 500;
    }

    /* Enhanced Search and Filter Area */
    .users-header {
      display: flex;
      gap: 16px;
      margin-bottom: 20px;
      align-items: center;
      flex-wrap: wrap;
      padding: 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .search-box {
      flex: 1;
      min-width: 300px;
      position: relative;
    }

    .search-box i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 16px;
    }

    .search-box input {
      width: 100%;
      padding: 14px 16px 14px 48px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .search-box input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .filter-options {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .filter-options select {
      padding: 12px 40px 12px 16px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 500;
      color: #475569;
      background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E") no-repeat right 12px center;
      cursor: pointer;
      transition: all 0.3s ease;
      appearance: none;
    }

    .filter-options select:hover {
      border-color: #cbd5e1;
    }

    .filter-options select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    /* Enhanced Table Styles */
    .table-container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      overflow: hidden;
      margin-bottom: 20px;
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
      padding: 18px 16px;
      text-align: left;
      font-weight: 600;
      font-size: 13px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .users-table tbody tr {
      border-bottom: 1px solid #e2e8f0;
      transition: all 0.2s ease;
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

    .users-table tbody td strong {
      color: #1e293b;
      font-weight: 600;
    }

    /* Status Badges */
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

    .status-badge.active {
      background: #d1fae5;
      color: #065f46;
    }

    .status-badge.active::before {
      content: '';
      width: 6px;
      height: 6px;
      background: #10b981;
      border-radius: 50%;
    }

    .status-badge.inactive {
      background: #fee2e2;
      color: #991b1b;
    }

    .status-badge.inactive::before {
      content: '';
      width: 6px;
      height: 6px;
      background: #ef4444;
      border-radius: 50%;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 4px;
      justify-content: center;
    }

    .btn-action {
      width: 28px;
      height: 28px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      font-size: 12px;
    }

    .btn-action:not([disabled]):hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn-action[disabled] {
      opacity: 0.3;
      cursor: not-allowed;
    }

    .btn-view {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }

    .btn-action:not(.btn-view):not([disabled]) {
      background: #f1f5f9;
      color: #64748b;
    }

    /* Pagination Styles */
    .pagination {
      display: flex;
      gap: 8px;
      justify-content: center;
      align-items: center;
      padding: 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .pagination button {
      padding: 10px 16px;
      border: 2px solid #e2e8f0;
      background: white;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      color: #475569;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .pagination button:hover:not([disabled]) {
      border-color: #667eea;
      color: #667eea;
      background: #f8fafc;
    }

    .pagination button.active {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border-color: #667eea;
    }

    .pagination button[disabled] {
      opacity: 0.4;
      cursor: not-allowed;
    }

    /* Modal Styles */
    .user-modal {
      position: fixed;
      inset: 0;
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .user-modal-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      animation: fadeIn 0.3s ease;
    }

    .user-modal-content {
      position: relative;
      background: white;
      border-radius: 20px;
      max-width: 700px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      animation: slideUp 0.3s ease;
    }

    .user-modal-header {
      padding: 24px;
      border-bottom: 2px solid #f1f5f9;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border-radius: 20px 20px 0 0;
    }

    .user-modal-header h3 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .user-modal-header button {
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
      font-size: 16px;
    }

    .user-modal-header button:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: rotate(90deg);
    }

    .user-modal-body {
      padding: 24px;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
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

    /* Responsive */
    @media (max-width: 768px) {
      .users-header {
        flex-direction: column;
        align-items: stretch;
      }

      .search-box {
        min-width: 100%;
      }

      .filter-options {
        width: 100%;
      }

      .filter-options select {
        flex: 1;
      }

      .users-stats {
        grid-template-columns: 1fr;
      }
    }

    /* Enhanced table row hover */
    #usersTable tbody tr:hover {
      background: #f8fafc;
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
  </style>
</head>
<body>
  <div class="admin-container">
    <?php include 'staff_header.php'; ?>

    <main class="main-content" style="padding-top:100px;">
      <section class="content-section active">
        <div class="section-header" style="margin-bottom:30px;">
          <div>
            <h2 style="font-size:32px; font-weight:700; color:#1e293b; margin-bottom:8px;">User Management</h2>
            <p style="color:#64748b; font-size:16px;">View all registered users and their information. Editing and deletion are admin-only.</p>
          </div>
        </div>

        <div class="users-container">
          <div class="users-header">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input type="text" id="searchUsers" placeholder="Search users by name, email, or username..." />
            </div>
            <div class="filter-options">
              <select id="statusFilter" onchange="filterUsers()">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>

          <div class="users-stats">
            <div class="stat-card"><i class="fas fa-users"></i><div class="stat-info"><h3 id="manageTotalUsersCount">0</h3><p>Total Users</p></div></div>
            <div class="stat-card"><i class="fas fa-user-check"></i><div class="stat-info"><h3 id="manageActiveUsersCount">0</h3><p>Active Users</p></div></div>
            <div class="stat-card"><i class="fas fa-user-plus"></i><div class="stat-info"><h3 id="manageNewUsersCount">0</h3><p>New This Month</p></div></div>
          </div>

          <div class="table-container" style="background:white; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:visible;">
            <table class="users-table" id="usersTable" style="width:100%; table-layout:fixed; border-collapse:separate; border-spacing:0;">
              <thead style="background:linear-gradient(135deg, #667eea, #764ba2); color:white;">
                <tr>
                  <th style="padding:14px 10px; text-align:center; width:3%; font-size:13px;">#</th>
                  <th style="padding:14px 10px; text-align:left; width:12%; font-size:13px;"><i class="fas fa-user"></i> Full Name</th>
                  <th style="padding:14px 10px; text-align:center; width:11%; font-size:13px;"><i class="fas fa-at"></i> Username</th>
                  <th style="padding:14px 10px; text-align:center; width:15%; font-size:13px;"><i class="fas fa-envelope"></i> Email</th>
                  <th style="padding:14px 10px; text-align:left; width:10%; font-size:13px;"><i class="fas fa-phone"></i> Phone</th>
                  <th style="padding:14px 8px; text-align:center; width:8%; font-size:13px;"><i class="fas fa-toggle-on"></i> Status</th>
                  <th style="padding:14px 8px; text-align:center; width:12%; font-size:13px;"><i class="fas fa-calendar-alt"></i> Member Since</th>
                  <th style="padding:14px 8px; text-align:center; width:12%; font-size:13px;"><i class="fas fa-clock"></i> Last Login</th>
                  <th style="padding:14px 8px; text-align:center; width:19%; font-size:13px;"><i class="fas fa-cog"></i> Actions</th>
                </tr>
              </thead>
              <tbody id="usersTableBody">
                <tr><td colspan="9" style="text-align:center; padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:48px; color:#667eea;"></i><p style="margin-top:1rem; color:#64748b; font-size:16px; font-weight:600;">Loading users...</p></td></tr>
              </tbody>
            </table>
          </div>

          <div class="pagination" id="usersPagination"></div>
        </div>
      </section>
    </main>
  </div>

  <script>
    // Staff-specific fetch and render logic
    let allUsers = [];
    let filteredUsers = [];
    let currentPage = 1;
    const usersPerPage = 10;

    async function loadUsersForStaff() {
      const tbody = document.getElementById('usersTableBody');
      tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding:2rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem; color:#667eea;"></i><p style="margin-top:1rem; color:#666">Loading users...</p></td></tr>`;
      try {
        const res = await fetch('staff_get_users.php');
        const data = await res.json();
        if (!data.success) {
          tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding:2rem; color:#b00;">Failed to load users: ${data.message || 'Unknown'}</td></tr>`;
          return;
        }
        allUsers = data.users || [];
        filteredUsers = [...allUsers];
        updateUserStatistics();
        displayUsers();
      } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding:2rem; color:#b00;">Error loading users.</td></tr>`;
      }
    }

    function updateUserStatistics(){
      const totalUsers = allUsers.length;
      const activeUsers = allUsers.filter(u => u.is_active == 1).length;
      const currentDate = new Date(); const currentMonth = currentDate.getMonth(); const currentYear = currentDate.getFullYear();
      const newUsers = allUsers.filter(user => {
        const d = new Date(user.created_at); return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
      }).length;
      document.getElementById('manageTotalUsersCount').textContent = totalUsers;
      document.getElementById('manageActiveUsersCount').textContent = activeUsers;
      document.getElementById('manageNewUsersCount').textContent = newUsers;
    }

    function displayUsers(){
      const tbody = document.getElementById('usersTableBody');
      if (filteredUsers.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:3rem;"><i class="fas fa-inbox" style="font-size:48px; margin-bottom:16px; opacity:0.5; color:#94a3b8;"></i><div style="font-size:16px; font-weight:600; color:#94a3b8;">No users found</div><div style="font-size:14px; margin-top:8px; color:#cbd5e1;">Try adjusting your filters</div></td></tr>`; return;
      }
      const start = (currentPage-1)*usersPerPage; const end = start + usersPerPage; const pageUsers = filteredUsers.slice(start,end);
      
      const rows = pageUsers.map((u, idx) => {
        const statusClass = u.is_active == 1 ? 'active' : 'inactive';
        const statusText = u.is_active == 1 ? 'Active' : 'Inactive';
        const statusDot = u.is_active == 1 ? '<i class="fas fa-circle" style="font-size:6px; color:#10b981;"></i>' : '<i class="fas fa-circle" style="font-size:6px; color:#ef4444;"></i>';
        
        return `
          <tr data-user-id="${u.user_id}" style="animation:fadeIn 0.3s ease ${idx*0.05}s both; border-bottom:1px solid #f1f5f9;">
            <td style="padding:14px 10px; text-align:center; font-weight:700; color:#64748b; font-size:13px;">${u.user_id}</td>
            <td style="padding:14px 10px;">
              <div style="font-weight:600; color:#1e293b; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(u.full_name || '')}">${escapeHtml(u.full_name || '')}</div>
            </td>
            <td style="padding:14px 10px; text-align:center;">
              <div style="font-weight:500; color:#475569; font-size:13px; white-space:nowrap;" title="${escapeHtml(u.username || '')}">${escapeHtml(u.username || '')}</div>
            </td>
            <td style="padding:14px 10px; text-align:center;">
              <div style="font-size:12px; color:#64748b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(u.email || '')}">${escapeHtml(u.email || '')}</div>
            </td>
            <td style="padding:14px 10px;">
              <div style="font-weight:500; color:#475569; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(u.phone_formatted || '')}</div>
            </td>
            <td style="padding:14px 8px; text-align:center;">
              <span style="display:inline-flex; align-items:center; gap:4px; padding:5px 10px; background:${u.is_active == 1 ? 'linear-gradient(135deg,#10b981,#059669)' : 'linear-gradient(135deg,#ef4444,#dc2626)'}; color:white; border-radius:16px; font-size:11px; font-weight:600; white-space:nowrap;">
                ${statusDot} ${statusText}
              </span>
            </td>
            <td style="padding:14px 8px; text-align:center;">
              <div style="font-weight:500; color:#475569; font-size:12px; white-space:nowrap;">${escapeHtml(u.member_since || '')}</div>
            </td>
            <td style="padding:14px 8px; text-align:center;">
              <div style="font-size:12px; color:#64748b; white-space:nowrap;">${u.last_login_formatted || ''}</div>
            </td>
            <td style="padding:14px 12px; text-align:center; overflow:visible;">
              <div style="display:flex; gap:6px; justify-content:center; flex-wrap:nowrap;">
                <button onclick="viewUser('${u.user_id}')" style="width:30px; height:30px; border:none; background:#f1f5f9; color:#667eea; border-radius:6px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:13px;" title="View Details" onmouseover="this.style.background='linear-gradient(135deg,#667eea,#764ba2)'; this.style.color='white';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#667eea';"><i class="fas fa-eye"></i></button>
                <button disabled style="width:30px; height:30px; border:none; background:#e2e8f0; color:#94a3b8; border-radius:6px; cursor:not-allowed; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:13px; opacity:0.5;" title="Edit (Admin Only)"><i class="fas fa-edit"></i></button>
                <button disabled style="width:30px; height:30px; border:none; background:#e2e8f0; color:#94a3b8; border-radius:6px; cursor:not-allowed; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:13px; opacity:0.5;" title="Toggle Status (Admin Only)"><i class="fas fa-power-off"></i></button>
                <button disabled style="width:30px; height:30px; border:none; background:#e2e8f0; color:#94a3b8; border-radius:6px; cursor:not-allowed; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:13px; opacity:0.5;" title="Delete (Admin Only)"><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
      tbody.innerHTML = rows;
      updatePagination();
    }

    function escapeHtml(text){ const d=document.createElement('div'); d.textContent = text; return d.innerHTML; }

    function filterUsers(){
      const q = (document.getElementById('searchUsers').value||'').toLowerCase();
      const status = document.getElementById('statusFilter').value;
      filteredUsers = allUsers.filter(u => {
        const matchesSearch = (u.full_name||'').toLowerCase().includes(q) || (u.username||'').toLowerCase().includes(q) || (u.email||'').toLowerCase().includes(q) || (u.phone_formatted||'').includes(q);
        const matchesStatus = status === 'all' || (status === 'active' && u.is_active==1) || (status === 'inactive' && u.is_active==0);
        return matchesSearch && matchesStatus;
      }); currentPage = 1; displayUsers();
    }

    function updatePagination(){
      const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
      const p = document.getElementById('usersPagination'); if (totalPages <= 1) { p.innerHTML=''; return; }
      let html = `<button onclick="changePage(${currentPage-1})" ${currentPage===1?'disabled':''}><i class="fas fa-chevron-left"></i> Previous</button>`;
      for (let i=1;i<=totalPages;i++){ if (i===1 || i===totalPages || (i>=currentPage-2 && i<=currentPage+2)) html += `<button onclick="changePage(${i})" class="${i===currentPage?'active':''}">${i}</button>`; else if (i===currentPage-3||i===currentPage+3) html += '<span style="padding:0 0.5rem;">...</span>'; }
      html += `<button onclick="changePage(${currentPage+1})" ${currentPage===totalPages?'disabled':''}>Next <i class="fas fa-chevron-right"></i></button>`;
      p.innerHTML = html;
    }

    function changePage(page){ const totalPages = Math.ceil(filteredUsers.length / usersPerPage); if (page<1||page>totalPages) return; currentPage=page; displayUsers(); document.querySelector('.users-container').scrollIntoView({behavior:'smooth'}); }

    function viewUser(userId){ 
      const u = allUsers.find(x=>x.user_id===userId); 
      if(!u) return; 
      
      const content = `
      <div style="padding:24px;">
        <div style="text-align:center; margin-bottom:30px;">
          <div style="width:100px; height:100px; background:linear-gradient(135deg, #667eea, #764ba2); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:white; font-size:42px; font-weight:700; margin-bottom:16px;">
            ${(u.full_name||'U').charAt(0).toUpperCase()}
          </div>
          <h2 style="font-size:28px; font-weight:700; color:#1e293b; margin:0 0 8px 0;">${escapeHtml(u.full_name || 'N/A')}</h2>
          <p style="color:#64748b; font-size:14px; font-weight:600;">User #${u.user_id}</p>
          <div style="margin-top:12px;">
            <span class="status-badge ${u.is_active==1?'active':'inactive'}">${u.is_active==1?'Active':'Inactive'}</span>
          </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
          <div style="padding:20px; background:#f8fafc; border-radius:12px;">
            <div style="color:#64748b; font-size:12px; font-weight:600; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Username</div>
            <div style="color:#1e293b; font-weight:600; display:flex; align-items:center; gap:8px;">
              <i class="fas fa-user" style="color:#667eea;"></i>
              ${escapeHtml(u.username || 'N/A')}
            </div>
          </div>
          <div style="padding:20px; background:#f8fafc; border-radius:12px;">
            <div style="color:#64748b; font-size:12px; font-weight:600; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Phone Number</div>
            <div style="color:#1e293b; font-weight:600; display:flex; align-items:center; gap:8px;">
              <i class="fas fa-phone" style="color:#667eea;"></i>
              ${escapeHtml(u.phone_formatted || 'Not provided')}
            </div>
          </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr; gap:16px; margin-bottom:20px;">
          <div style="padding:20px; background:#f8fafc; border-radius:12px;">
            <div style="color:#64748b; font-size:12px; font-weight:600; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Email Address</div>
            <div style="color:#1e293b; font-weight:600; display:flex; align-items:center; gap:8px; word-break:break-all;">
              <i class="fas fa-envelope" style="color:#667eea;"></i>
              ${escapeHtml(u.email || 'N/A')}
            </div>
          </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
          <div style="padding:20px; background:#f8fafc; border-radius:12px;">
            <div style="color:#64748b; font-size:12px; font-weight:600; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Member Since</div>
            <div style="color:#1e293b; font-weight:600; display:flex; align-items:center; gap:8px;">
              <i class="fas fa-calendar-plus" style="color:#667eea;"></i>
              ${escapeHtml(u.member_since || u.created_at_formatted || 'N/A')}
            </div>
          </div>
          <div style="padding:20px; background:#f8fafc; border-radius:12px;">
            <div style="color:#64748b; font-size:12px; font-weight:600; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Last Login</div>
            <div style="color:#1e293b; font-weight:600; display:flex; align-items:center; gap:8px;">
              <i class="fas fa-clock" style="color:#667eea;"></i>
              ${u.last_login_formatted || 'Never'}
            </div>
          </div>
        </div>
        
        <div style="padding:20px; background:linear-gradient(135deg, #667eea, #764ba2); border-radius:12px; color:white; text-align:center;">
          <i class="fas fa-shield-alt" style="font-size:20px; margin-bottom:8px;"></i>
          <p style="margin:0; font-size:14px; opacity:0.95;">Editing, status changes, and deletion are admin-only actions.</p>
        </div>
      </div>
    `; 
      showModal('User Profile', content);
    }

    // Enhanced Modal helper
    function showModal(title, content){ 
      const modal = document.createElement('div'); 
      modal.className='user-modal'; 
      modal.innerHTML = `
        <div class="user-modal-overlay" onclick="closeModal()"></div>
        <div class="user-modal-content">
          <div class="user-modal-header">
            <h3><i class="fas fa-user-circle"></i> ${title}</h3>
            <button onclick="closeModal()"><i class="fas fa-times"></i></button>
          </div>
          <div class="user-modal-body">${content}</div>
        </div>
      `; 
      document.body.appendChild(modal); 
      document.body.style.overflow='hidden'; 
    }
    
    function closeModal(){ 
      const m=document.querySelector('.user-modal'); 
      if(m){ 
        m.remove(); 
        document.body.style.overflow=''; 
      } 
    }

    document.addEventListener('DOMContentLoaded', function(){ document.getElementById('searchUsers').addEventListener('input', filterUsers); loadUsersForStaff(); });
  </script>
  <script src="../js/admin-script.js"></script>
</body>
</html>

