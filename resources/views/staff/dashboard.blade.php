<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function() {
            var token = "{{ csrf_token() }}";
            var origFetch = window.fetch;
            window.fetch = function(url, opt) {
                opt = opt || {};
                opt.headers = opt.headers || {};
                if (opt.headers instanceof Headers) {
                    if (!opt.headers.has('X-CSRF-TOKEN')) opt.headers.append('X-CSRF-TOKEN', token);
                } else if (Array.isArray(opt.headers)) {
                    opt.headers.push(['X-CSRF-TOKEN', token]);
                } else {
                    opt.headers['X-CSRF-TOKEN'] = opt.headers['X-CSRF-TOKEN'] || token;
                }
                return origFetch(url, opt);
            };
        })();
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Staff Dashboard - AR Homes Posadas Farm Resort</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/ar-homes-logo.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo/ar-homes-logo.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo/ar-homes-logo.png') }}" />

    <link rel="stylesheet" href="{{ asset('css/admin-styles.css') }}" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&family=Bungee+Spice&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      #staffFilterFrom:focus,
      #staffFilterTo:focus {
        outline: none;
        border-color: #11224e !important;
        box-shadow: 0 0 0 3px rgba(17, 34, 78, 0.1);
      }
    </style>
</head>
<body>
    <div class="admin-container">
      <!-- Header -->
      <header class="admin-header">
        <div class="header-left">
          <div class="logo">
            <img src="{{ asset('images/logo/ar-homes-logo.png') }}" alt="AR Homes Resort Logo" />
          </div>
          <div class="resort-info">
            <h1>AR Homes Posadas Farm Resort</h1>
          </div>
        </div>
        <div class="header-right">
          <div class="admin-profile">
            <div class="profile-info">
              <span class="admin-name">{{ session('staff_full_name', 'Staff Member') }}</span>
              <span class="admin-role">Staff</span>
            </div>
            <div class="profile-avatar">
              <i class="fas fa-user-tie"></i>
            </div>
          </div>
          <button class="logout-btn" id="logoutButton" onclick="handleLogoutClick(event)" style="z-index: 1000; position: relative;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </button>
        </div>
        <div class="mobile-toggle" onclick="toggleSidebar()">
          <i class="fas fa-bars"></i>
        </div>
      </header>

      <!-- Sidebar - 3 Sections Only -->
      <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="#dashboard" class="nav-link" data-section="dashboard">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="#reservations" class="nav-link" data-section="reservations">
                <i class="fas fa-calendar-check"></i>
                <span>Manage Reservations</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="#users" class="nav-link" data-section="users">
                <i class="fas fa-users"></i>
                <span>View Users</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="#settings" class="nav-link" data-section="settings">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
              </a>
            </li>
          </ul>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="main-content">
        <!-- Dashboard Section -->
        <section id="dashboard" class="content-section active">
          <div class="section-header" style="margin-bottom:30px;">
            <h2 style="color:#333; font-size:32px; font-weight:700; margin:0 0 8px 0;">Dashboard Overview</h2>
            <p style="color:#666; margin:0; font-size:16px;">
              Welcome back, {{ session('staff_full_name', 'Staff Member') }}! Here's what's happening at the resort today.
            </p>
          </div>

          <!-- Dashboard Container -->
          <div style="max-width: 800px; margin: 0 auto;">
            <!-- Dashboard Statistics Cards -->
            <div class="dashboard-stats-grid">
              <!-- Total Users Card -->
              <div class="stat-card primary">
                <div class="stat-icon">
                  <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="totalUsersCount">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                  <div class="stat-label">Total Users</div>
                  <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span id="newUsersMonth">0</span> this month
                  </div>
                </div>
              </div>

              <!-- Active Users Card -->
              <div class="stat-card success">
                <div class="stat-icon">
                  <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="activeUsersCount">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                  <div class="stat-label">Active Users</div>
                  <div class="stat-change">
                    <i class="fas fa-info-circle"></i>
                    <span id="activePercentage">0%</span> of total
                  </div>
                </div>
              </div>

              <!-- Total Reservations Card -->
              <div class="stat-card primary">
                <div class="stat-icon">
                  <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="totalReservationsCount">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                  <div class="stat-label">Total Reservations</div>
                  <div class="stat-change">
                    <i class="fas fa-chart-line"></i>
                    All time
                  </div>
                </div>
              </div>

              <!-- Pending Reservations Card -->
              <div class="stat-card warning">
                <div class="stat-icon">
                  <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="pendingReservationsCount">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                  <div class="stat-label">Pending</div>
                  <div class="stat-change">
                    <i class="fas fa-hourglass-half"></i>
                    Awaiting approval
                  </div>
                </div>
              </div>

              <!-- Confirmed Reservations Card -->
              <div class="stat-card success">
                <div class="stat-icon">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="confirmedReservationsCount">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                  <div class="stat-label">Confirmed</div>
                  <div class="stat-change positive">
                    <i class="fas fa-thumbs-up"></i>
                    Approved bookings
                  </div>
                </div>
              </div>

              <!-- Completed Reservations Card -->
              <div class="stat-card info">
                <div class="stat-icon">
                  <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="completedReservationsCount">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                  <div class="stat-label">Completed</div>
                  <div class="stat-change">
                    <i class="fas fa-history"></i>
                    Past stays
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent Activities Section -->
            <div class="recent-activities-section" style="max-width: 800px; margin: 0 auto;">
              <div class="section-header-inline">
                <h3><i class="fas fa-history"></i> Recent Activities</h3>
                <button class="refresh-btn" onclick="loadDashboardStats()" title="Refresh data">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
              <div class="activities-container" id="recentActivitiesContainer">
                <div class="loading-activities">
                  <i class="fas fa-spinner fa-spin"></i>
                  <p>Loading recent activities...</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Reservations Section -->
        <section id="reservations" class="content-section">
          <div class="section-header" style="margin-bottom:30px;">
            <h2 style="color:#333; font-size:32px; font-weight:700; margin:0 0 8px 0;">Reservations Management</h2>
            <p style="color:#666; margin:0; font-size:16px;">View and manage all resort reservations efficiently</p>
          </div>

          <!-- Stats Overview -->
          <div class="stats-overview" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:30px;">
            <div class="stat-card-res" style="background:white; color:#11224e; padding:24px; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; align-items:center; gap:20px; transition:all 0.3s ease; border:2px solid #11224e;">
              <div style="width:64px; height:64px; background:rgba(17,34,78,0.1); border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#11224e;"><i class="fas fa-calendar-check"></i></div>
              <div>
                <div style="font-size:32px; font-weight:700; margin-bottom:4px; color:#11224e;" id="staffTotalReservations">0</div>
                <div style="font-size:14px; font-weight:500; color:#11224e;">Total Reservations</div>
              </div>
            </div>
            <div class="stat-card-res" style="background:white; color:#11224e; padding:24px; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; align-items:center; gap:20px; transition:all 0.3s ease; border:2px solid #11224e;">
              <div style="width:64px; height:64px; background:rgba(17,34,78,0.1); border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#11224e;"><i class="fas fa-check-circle"></i></div>
              <div>
                <div style="font-size:32px; font-weight:700; margin-bottom:4px; color:#11224e;" id="staffConfirmedCount">0</div>
                <div style="font-size:14px; font-weight:500; color:#11224e;">Confirmed</div>
              </div>
            </div>
            <div class="stat-card-res" style="background:white; color:#11224e; padding:24px; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; align-items:center; gap:20px; transition:all 0.3s ease; border:2px solid #11224e;">
              <div style="width:64px; height:64px; background:rgba(17,34,78,0.1); border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#11224e;"><i class="fas fa-clock"></i></div>
              <div>
                <div style="font-size:32px; font-weight:700; margin-bottom:4px; color:#11224e;" id="staffPendingCount">0</div>
                <div style="font-size:14px; font-weight:500; color:#11224e;">Pending</div>
              </div>
            </div>
            <div class="stat-card-res" style="background:white; color:#11224e; padding:24px; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; align-items:center; gap:20px; transition:all 0.3s ease; border:2px solid #11224e;">
              <div style="width:64px; height:64px; background:rgba(17,34,78,0.1); border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#11224e;"><i class="fas fa-times-circle"></i></div>
              <div>
                <div style="font-size:32px; font-weight:700; margin-bottom:4px; color:#11224e;" id="staffCanceledCount">0</div>
                <div style="font-size:14px; font-weight:500; color:#11224e;">Canceled</div>
              </div>
            </div>
          </div>

          <!-- Enhanced Filters Section -->
          <div style="background:white; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
              <h3 style="margin:0; font-size:16px; font-weight:600; color:#1e293b; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-filter" style="color:#11224e;"></i> Filter Reservations
              </h3>
              <button onclick="staffClearFilters()" style="padding:6px 14px; background:#f1f5f9; border:none; border-radius:6px; font-size:13px; font-weight:600; color:#64748b; cursor:pointer; transition:all 0.2s;">
                <i class="fas fa-redo"></i> Clear Filters
              </button>
            </div>

            <!-- Quick Status Filters -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:12px; margin-bottom:16px;">
              <div class="filter-chip-enhanced active" onclick="staffQuickFilter('all')" id="staff-chip-all" style="cursor:pointer; padding:14px 16px; background:#11224e; border:2px solid #11224e; border-radius:12px; transition:all 0.3s ease; box-shadow:0 4px 16px rgba(17,34,78,0.3);">
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:36px; height:36px; background:rgba(255,255,255,0.25); border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:16px;"><i class="fas fa-list"></i></div>
                  <span style="flex:1; font-size:14px; font-weight:600; color:white;">All</span>
                  <div style="padding:4px 10px; font-size:12px; font-weight:700; color:white; min-width:32px; text-align:center;" id="staff-count-all">0</div>
                </div>
              </div>
              <div class="filter-chip-enhanced" onclick="staffQuickFilter('pending')" id="staff-chip-pending" style="cursor:pointer; padding:14px 16px; background:white; border:2px solid #11224e; border-radius:12px; transition:all 0.3s ease;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:36px; height:36px; background:rgba(17,34,78,0.1); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#11224e; font-size:16px;"><i class="fas fa-clock"></i></div>
                  <span style="flex:1; font-size:14px; font-weight:600; color:#11224e;">Pending</span>
                  <div style="padding:4px 10px; font-size:12px; font-weight:700; color:#11224e; min-width:32px; text-align:center;" id="staff-count-pending">0</div>
                </div>
              </div>
              <div class="filter-chip-enhanced" onclick="staffQuickFilter('confirmed')" id="staff-chip-confirmed" style="cursor:pointer; padding:14px 16px; background:white; border:2px solid #11224e; border-radius:12px; transition:all 0.3s ease;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:36px; height:36px; background:rgba(17,34,78,0.1); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#11224e; font-size:16px;"><i class="fas fa-check"></i></div>
                  <span style="flex:1; font-size:14px; font-weight:600; color:#11224e;">Confirmed</span>
                  <div style="padding:4px 10px; font-size:12px; font-weight:700; color:#11224e; min-width:32px; text-align:center;" id="staff-count-confirmed">0</div>
                </div>
              </div>
              <div class="filter-chip-enhanced" onclick="staffQuickFilter('completed')" id="staff-chip-completed" style="cursor:pointer; padding:14px 16px; background:white; border:2px solid #11224e; border-radius:12px; transition:all 0.3s ease;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:36px; height:36px; background:rgba(17,34,78,0.1); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#11224e; font-size:16px;"><i class="fas fa-flag-checkered"></i></div>
                  <span style="flex:1; font-size:14px; font-weight:600; color:#11224e;">Completed</span>
                  <div style="padding:4px 10px; font-size:12px; font-weight:700; color:#11224e; min-width:32px; text-align:center;" id="staff-count-completed">0</div>
                </div>
              </div>
              <div class="filter-chip-enhanced" onclick="staffQuickFilter('canceled')" id="staff-chip-canceled" style="cursor:pointer; padding:14px 16px; background:white; border:2px solid #11224e; border-radius:12px; transition:all 0.3s ease;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:36px; height:36px; background:rgba(17,34,78,0.1); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#11224e; font-size:16px;"><i class="fas fa-ban"></i></div>
                  <span style="flex:1; font-size:14px; font-weight:600; color:#11224e;">Canceled</span>
                  <div style="padding:4px 10px; font-size:12px; font-weight:700; color:#11224e; min-width:32px; text-align:center;" id="staff-count-canceled">0</div>
                </div>
              </div>
            </div>

            <!-- Date Range Filter -->
            <div style="display:flex; gap:12px; padding-top:16px; border-top:2px solid #f1f5f9;">
              <div style="flex:1; display:flex; flex-direction:column; gap:8px;">
                <label style="font-size:13px; font-weight:600; color:#64748b; display:flex; align-items:center; gap:6px;"><i class="fas fa-calendar-day" style="color:#11224e;"></i> From Date</label>
                <input type="date" id="staffFilterFrom" onchange="staffApplyFilters()" style="padding:12px 16px; border:2px solid #11224e; border-radius:10px; font-size:14px; font-weight:500; color:#475569; background:white; transition:all 0.3s ease; cursor:pointer;">
              </div>
              <div style="flex:1; display:flex; flex-direction:column; gap:8px;">
                <label style="font-size:13px; font-weight:600; color:#64748b; display:flex; align-items:center; gap:6px;"><i class="fas fa-calendar-day" style="color:#11224e;"></i> To Date</label>
                <input type="date" id="staffFilterTo" onchange="staffApplyFilters()" style="padding:12px 16px; border:2px solid #11224e; border-radius:10px; font-size:14px; font-weight:500; color:#475569; background:white; transition:all 0.3s ease; cursor:pointer;">
              </div>
            </div>
          </div>

          <div class="users-container">
            <div class="users-header" style="padding:20px; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px;">
              <div class="search-box" style="flex:1; max-width:400px; position:relative;">
                <i class="fas fa-search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:16px;"></i>
                <input type="text" id="staffSearchBox" placeholder="Search guest, room, or contact..." oninput="staffApplyFilters()" style="width:100%; padding:14px 16px 14px 48px; border:2px solid #11224e; border-radius:12px; font-size:14px; transition:all 0.3s ease;" />
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding:16px; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); flex-wrap:wrap; gap:12px;">
              <div style="display:flex; align-items:center; gap:12px;">
                <button onclick="staffFetchAllReservations()" style="padding:12px 20px; background:white; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; font-weight:600; color:#11224e; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:8px;">
                  <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <span style="color:#64748b; font-size:14px;" id="staffLastUpdate">Last updated: Just now</span>
              </div>
            </div>

            <div class="table-container" style="background:white; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow-x:auto; margin-bottom:20px;">
              <table class="users-table" id="staffReservationsTable" style="width:100%; border-collapse:separate; border-spacing:0;">
                <thead style="background:#11224e; color:white;">
                  <tr>
                    <th style="padding:18px 16px; text-align:center; width:60px;">#</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-user"></i> Guest</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-cube"></i> Booking Details</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-calendar-check"></i> Check-in</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-calendar-times"></i> Check-out</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-money-bill-wave"></i> Payment</th>
                    <th style="padding:18px 16px; text-align:left; min-width:180px;"><i class="fas fa-tag"></i> Status</th>
                    <th style="padding:18px 16px; text-align:center;"><i class="fas fa-cog"></i> Actions</th>
                  </tr>
                </thead>
                <tbody id="staffReservationsBody"><tr><td colspan="8" style="text-align:center;padding:2rem;">Loading...</td></tr></tbody>
              </table>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; padding:20px; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); flex-wrap:wrap; gap:12px;">
              <div id="staffPaginationInfo" style="font-size:14px; color:#64748b; font-weight:500;"></div>
              <div style="display:flex; gap:8px;">
                <button id="staffPrevPage" onclick="staffChangePage(-1)" disabled style="padding:10px 16px; border:2px solid #e2e8f0; background:white; border-radius:8px; font-size:14px; font-weight:600; color:#475569; cursor:pointer; transition:all 0.2s;">&larr; Prev</button>
                <button id="staffNextPage" onclick="staffChangePage(1)" disabled style="padding:10px 16px; border:2px solid #e2e8f0; background:white; border-radius:8px; font-size:14px; font-weight:600; color:#475569; cursor:pointer; transition:all 0.2s;">Next &rarr;</button>
              </div>
            </div>
          </div>
        </section>

        <!-- Users Section -->
        <section id="users" class="content-section">
          <div class="section-header" style="margin-bottom:30px;">
            <h2 style="color:#333; font-size:32px; font-weight:700; margin:0 0 8px 0;">View Users</h2>
            <p style="color:#666; margin:0; font-size:16px;">View registered users information</p>
          </div>

          <!-- User Stats -->
          <div class="users-stats" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:30px;">
            <div style="background:white; padding:24px; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; align-items:center; gap:20px; border:2px solid #11224e;">
              <div style="width:64px; height:64px; background:rgba(17,34,78,0.1); border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#11224e;"><i class="fas fa-users"></i></div>
              <div>
                <div style="font-size:32px; font-weight:700; color:#11224e;" id="usersTotalCount">0</div>
                <div style="font-size:14px; font-weight:500; color:#11224e;">Total Users</div>
              </div>
            </div>
            <div style="background:white; padding:24px; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; align-items:center; gap:20px; border:2px solid #10b981;">
              <div style="width:64px; height:64px; background:rgba(16,185,129,0.1); border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#10b981;"><i class="fas fa-user-check"></i></div>
              <div>
                <div style="font-size:32px; font-weight:700; color:#10b981;" id="usersActiveCount">0</div>
                <div style="font-size:14px; font-weight:500; color:#10b981;">Active Users</div>
              </div>
            </div>
            <div style="background:white; padding:24px; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; align-items:center; gap:20px; border:2px solid #f59e0b;">
              <div style="width:64px; height:64px; background:rgba(245,158,11,0.1); border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#f59e0b;"><i class="fas fa-user-plus"></i></div>
              <div>
                <div style="font-size:32px; font-weight:700; color:#f59e0b;" id="usersNewCount">0</div>
                <div style="font-size:14px; font-weight:500; color:#f59e0b;">New This Month</div>
              </div>
            </div>
          </div>

          <div class="users-container">
            <div class="users-header" style="padding:20px; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px;">
              <div class="search-box" style="flex:1; max-width:400px; position:relative;">
                <i class="fas fa-search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:16px;"></i>
                <input type="text" id="usersSearchBox" placeholder="Search by name, email, or phone..." oninput="filterUsers()" style="width:100%; padding:14px 16px 14px 48px; border:2px solid #11224e; border-radius:12px; font-size:14px; transition:all 0.3s ease;" />
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding:16px; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
              <button onclick="loadUsers()" style="padding:12px 20px; background:white; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; font-weight:600; color:#11224e; cursor:pointer; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-sync-alt"></i> Refresh
              </button>
            </div>

            <div class="table-container" style="background:white; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow-x:auto;">
              <table class="users-table" style="width:100%; border-collapse:separate; border-spacing:0;">
                <thead style="background:#11224e; color:white;">
                  <tr>
                    <th style="padding:18px 16px; text-align:center; width:60px;">#</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-user"></i> Name</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-envelope"></i> Email</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-phone"></i> Phone</th>
                    <th style="padding:18px 16px; text-align:left;"><i class="fas fa-calendar"></i> Registered</th>
                    <th style="padding:18px 16px; text-align:center;"><i class="fas fa-info-circle"></i> Status</th>
                  </tr>
                </thead>
                <tbody id="usersTableBody"><tr><td colspan="6" style="text-align:center;padding:2rem;">Loading...</td></tr></tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Settings Section -->
        <section id="settings" class="content-section">
          <div class="section-header" style="margin-bottom:30px;">
            <h2 style="color:#333; font-size:32px; font-weight:700; margin:0 0 8px 0;">Account Settings</h2>
            <p style="color:#666; margin:0; font-size:16px;">Manage your account preferences and security</p>
          </div>

          <div style="max-width: 600px;">
            <!-- Profile Card -->
            <div style="background:white; border-radius:20px; box-shadow:0 4px 20px rgba(0,0,0,0.08); overflow:hidden; margin-bottom:24px;">
              <div style="background:linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding:32px; text-align:center;">
                <div style="width:80px; height:80px; background:rgba(255,255,255,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:32px; color:white; font-weight:700;">
                  <?php echo strtoupper(substr($staffFullName, 0, 1)); ?>
                </div>
                <h3 style="color:white; font-size:22px; font-weight:700; margin-bottom:4px;">{{ session('staff_full_name', 'Staff Member') }}</h3>
                <p style="color:rgba(255,255,255,0.8); font-size:14px;">@{{ session('staff_username', 'staff') }}</p>
                <div style="display:inline-flex; align-items:center; gap:6px; margin-top:12px; padding:8px 16px; background:rgba(255,255,255,0.15); border-radius:20px;">
                  <i class="fas fa-user-tie" style="color:#ffd700;"></i>
                  <span style="color:white; font-size:13px; font-weight:600;">Front Desk / Reception Staff</span>
                </div>
              </div>
              <div style="padding:24px;">
                <div style="display:flex; align-items:center; gap:12px; padding:16px; background:#f8fafc; border-radius:12px; margin-bottom:12px;">
                  <div style="width:40px; height:40px; background:#e2e8f0; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#64748b;">
                    <i class="fas fa-envelope"></i>
                  </div>
                  <div>
                    <div style="font-size:12px; color:#64748b; font-weight:500;">Email Address</div>
                    <div style="font-size:14px; color:#1e293b; font-weight:600;">{{ session('staff_email', '') }}</div>
                  </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; padding:16px; background:#f8fafc; border-radius:12px;">
                  <div style="width:40px; height:40px; background:#e2e8f0; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#64748b;">
                    <i class="fas fa-id-badge"></i>
                  </div>
                  <div>
                    <div style="font-size:12px; color:#64748b; font-weight:500;">Staff ID</div>
                    <div style="font-size:14px; color:#1e293b; font-weight:600;">#<?php echo $staffId; ?></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Security Settings -->
            <div style="background:white; border-radius:20px; box-shadow:0 4px 20px rgba(0,0,0,0.08); padding:24px;">
              <h3 style="font-size:18px; font-weight:700; color:#1e293b; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-shield-alt" style="color:#3a7ca5;"></i>
                Security Settings
              </h3>
              
              <a href="staff_change_password.php" style="display:flex; align-items:center; gap:16px; padding:20px; background:#f8fafc; border-radius:14px; text-decoration:none; transition:all 0.3s ease; border:2px solid transparent;" onmouseover="this.style.borderColor='#3a7ca5'; this.style.background='white';" onmouseout="this.style.borderColor='transparent'; this.style.background='#f8fafc';">
                <div style="width:50px; height:50px; background:linear-gradient(135deg, #f59e0b, #d97706); border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:20px;">
                  <i class="fas fa-key"></i>
                </div>
                <div style="flex:1;">
                  <div style="font-size:16px; font-weight:600; color:#1e293b;">Change Password</div>
                  <div style="font-size:13px; color:#64748b;">Update your account password for better security</div>
                </div>
                <i class="fas fa-chevron-right" style="color:#94a3b8; font-size:16px;"></i>
              </a>

              <div style="margin-top:24px; padding:16px; background:linear-gradient(135deg, #dbeafe, #eff6ff); border-radius:12px; border-left:4px solid #3b82f6;">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                  <i class="fas fa-info-circle" style="color:#3b82f6; font-size:18px; margin-top:2px;"></i>
                  <div>
                    <div style="font-size:14px; font-weight:600; color:#1e3a8a; margin-bottom:4px;">Security Tip</div>
                    <div style="font-size:13px; color:#1e40af;">Change your password regularly and use a strong combination of letters, numbers, and symbols.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
      <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); padding: 32px 28px; border-radius: 20px; max-width: 420px; width: 90vw; box-shadow: 0 12px 40px rgba(0,0,0,0.2); text-align: center;">
        <div style="width: 64px; height: 64px; background: linear-gradient(180deg, #f8b500 0%, #ff6f00 35%, #e53935 65%, #6a1b9a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: #fff; font-size: 32px;">
          <i class="fas fa-sign-out-alt"></i>
        </div>
        <h3 style="color: #1e293b; margin-bottom: 12px; font-size: 24px; font-weight: 700;">Confirm Logout</h3>
        <p style="color: #64748b; font-size: 15px; margin-bottom: 28px;">Are you sure you want to logout from your staff account?</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
          <button onclick="closeLogoutModal()" style="flex: 1; padding: 12px 24px; border: 2px solid #e2e8f0; background: #fff; color: #64748b; border-radius: 12px; cursor: pointer; font-weight: 600;">Cancel</button>
          <button onclick="confirmLogout()" style="flex: 1; padding: 12px 24px; border: none; background: linear-gradient(180deg, #f8b500 0%, #ff6f00 35%, #e53935 65%, #6a1b9a 100%); color: #fff; border-radius: 12px; cursor: pointer; font-weight: 600;">Logout</button>
        </div>
      </div>
    </div>

    <script>
      // ============================
      // NAVIGATION & UI
      // ============================
      function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('active');
      }

      // Section Navigation
      document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const section = this.dataset.section;
          
          // Update active nav item
          document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
          this.closest('.nav-item').classList.add('active');
          
          // Show section
          document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
          document.getElementById(section).classList.add('active');
          
          // Update URL hash
          window.location.hash = section;
          
          // Load section data
          if (section === 'reservations') staffFetchAllReservations();
          if (section === 'users') loadUsers();
          if (section === 'settings') { /* Settings section is static */ }
        });
      });

      // Handle hash on load
      window.addEventListener('load', function() {
        const hash = window.location.hash.replace('#', '') || 'dashboard';
        const link = document.querySelector(`[data-section="${hash}"]`);
        if (link) link.click();
      });

      // ============================
      // LOGOUT FUNCTIONS
      // ============================
      function handleLogoutClick(e) {
        e.preventDefault();
        document.getElementById('logoutModal').style.display = 'flex';
      }

      function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
      }

      function confirmLogout() {
        fetch('staff_logout.php', { method: 'POST', credentials: 'include' })
          .then(() => window.location.href = '../index.html')
          .catch(() => window.location.href = '../index.html');
      }

      // ============================
      // DASHBOARD STATS
      // ============================
      async function loadDashboardStats() {
        try {
          const res = await fetch('get_dashboard_stats.php', { credentials: 'include' });
          const data = await res.json();
          
          if (data.success && data.stats) {
            const s = data.stats;
            document.getElementById('totalUsersCount').textContent = s.total_users || 0;
            document.getElementById('activeUsersCount').textContent = s.active_users || 0;
            document.getElementById('totalReservationsCount').textContent = s.total_reservations || 0;
            document.getElementById('pendingReservationsCount').textContent = s.pending_reservations || 0;
            document.getElementById('confirmedReservationsCount').textContent = s.confirmed_reservations || 0;
            document.getElementById('completedReservationsCount').textContent = s.completed_reservations || 0;
            document.getElementById('newUsersMonth').textContent = s.new_users_month || 0;
            document.getElementById('activePercentage').textContent = s.total_users > 0 ? Math.round((s.active_users / s.total_users) * 100) + '%' : '0%';
          }
          
          // Load recent activities
          loadRecentActivities();
        } catch (err) {
          console.error('Error loading dashboard stats:', err);
        }
      }

      async function loadRecentActivities() {
        const container = document.getElementById('recentActivitiesContainer');
        try {
          const res = await fetch('staff_get_reservations.php?limit=5', { credentials: 'include' });
          const data = await res.json();
          
          if (data.success && data.reservations && data.reservations.length > 0) {
            container.innerHTML = data.reservations.map(r => `
              <div class="activity-item" style="display:flex; align-items:center; gap:16px; padding:16px; background:#f8fafc; border-radius:12px; margin-bottom:12px;">
                <div style="width:48px; height:48px; background:#11224e; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white;">
                  <i class="fas fa-calendar-check"></i>
                </div>
                <div style="flex:1;">
                  <div style="font-weight:600; color:#1e293b;">${escapeHtml(r.guest_name || 'Guest')}</div>
                  <div style="font-size:13px; color:#64748b;">${r.check_in_date} - ${r.status}</div>
                </div>
                <span style="padding:4px 12px; background:${getStatusColor(r.status)}20; color:${getStatusColor(r.status)}; border-radius:20px; font-size:12px; font-weight:600; text-transform:capitalize;">${r.status}</span>
              </div>
            `).join('');
          } else {
            container.innerHTML = '<div style="text-align:center; padding:40px; color:#64748b;"><i class="fas fa-inbox" style="font-size:48px; margin-bottom:16px; opacity:0.3;"></i><p>No recent activities</p></div>';
          }
        } catch (err) {
          container.innerHTML = '<div style="text-align:center; padding:40px; color:#ef4444;">Failed to load activities</div>';
        }
      }

      function getStatusColor(status) {
        const colors = {
          'pending': '#f59e0b',
          'pending_payment': '#f59e0b',
          'pending_confirmation': '#f59e0b',
          'confirmed': '#10b981',
          'completed': '#3b82f6',
          'checked_out': '#3b82f6',
          'canceled': '#ef4444',
          'cancelled': '#ef4444',
          'checked_in': '#8b5cf6'
        };
        return colors[status] || '#64748b';
      }

      // ============================
      // RESERVATIONS MANAGEMENT
      // ============================
      let staffAllReservations = [];
      let staffFilteredReservations = [];
      let staffCurrentPage = 1;
      const staffPageSize = 12;
      let staffCurrentQuickFilter = 'all';

      async function staffFetchAllReservations() {
        try {
          const res = await fetch('staff_get_reservations.php?limit=1000', { credentials: 'include' });
          const data = await res.json();
          
          if (!data.success) {
            document.getElementById('staffReservationsBody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:#ef4444;">Failed to load reservations</td></tr>';
            return;
          }
          
          staffAllReservations = data.reservations || [];
          staffUpdateStatsCards();
          staffApplyFilters();
          staffUpdateLastUpdateTime();
        } catch (err) {
          console.error(err);
          document.getElementById('staffReservationsBody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:#ef4444;">Error loading reservations</td></tr>';
        }
      }

      function staffUpdateStatsCards() {
        const total = staffAllReservations.length;
        const confirmed = staffAllReservations.filter(r => r.status === 'confirmed').length;
        const pending = staffAllReservations.filter(r => r.status === 'pending' || r.status === 'pending_payment' || r.status === 'pending_confirmation').length;
        const canceled = staffAllReservations.filter(r => r.status === 'canceled' || r.status === 'cancelled').length;
        const completed = staffAllReservations.filter(r => r.status === 'completed' || r.status === 'checked_out').length;
        
        document.getElementById('staffTotalReservations').textContent = total;
        document.getElementById('staffConfirmedCount').textContent = confirmed;
        document.getElementById('staffPendingCount').textContent = pending;
        document.getElementById('staffCanceledCount').textContent = canceled;
        
        document.getElementById('staff-count-all').textContent = total;
        document.getElementById('staff-count-pending').textContent = pending;
        document.getElementById('staff-count-confirmed').textContent = confirmed;
        document.getElementById('staff-count-completed').textContent = completed;
        document.getElementById('staff-count-canceled').textContent = canceled;
      }

      function staffUpdateLastUpdateTime() {
        const now = new Date();
        document.getElementById('staffLastUpdate').textContent = `Last updated: ${now.toLocaleTimeString()}`;
      }

      function staffQuickFilter(status) {
        staffCurrentQuickFilter = status;
        
        // Update chip styles
        document.querySelectorAll('[id^="staff-chip-"]').forEach(chip => {
          chip.style.background = 'white';
          chip.style.boxShadow = 'none';
          chip.querySelectorAll('span, div[id^="staff-count-"]').forEach(el => el.style.color = '#11224e');
          const iconDiv = chip.querySelector('div > div:first-child');
          if (iconDiv) {
            iconDiv.style.background = 'rgba(17,34,78,0.1)';
            iconDiv.style.color = '#11224e';
          }
        });
        
        const activeChip = document.getElementById(`staff-chip-${status}`);
        if (activeChip) {
          activeChip.style.background = '#11224e';
          activeChip.style.boxShadow = '0 4px 16px rgba(17,34,78,0.3)';
          activeChip.querySelectorAll('span, div[id^="staff-count-"]').forEach(el => el.style.color = 'white');
          const iconDiv = activeChip.querySelector('div > div:first-child');
          if (iconDiv) {
            iconDiv.style.background = 'rgba(255,255,255,0.25)';
            iconDiv.style.color = 'white';
          }
        }
        
        staffApplyFilters();
      }

      function staffApplyFilters() {
        const search = (document.getElementById('staffSearchBox').value || '').toLowerCase();
        const fromDate = document.getElementById('staffFilterFrom').value;
        const toDate = document.getElementById('staffFilterTo').value;
        
        staffFilteredReservations = staffAllReservations.filter(r => {
          // Status filter
          if (staffCurrentQuickFilter !== 'all') {
            if (staffCurrentQuickFilter === 'pending' && !['pending', 'pending_payment', 'pending_confirmation'].includes(r.status)) return false;
            if (staffCurrentQuickFilter === 'confirmed' && r.status !== 'confirmed') return false;
            if (staffCurrentQuickFilter === 'completed' && !['completed', 'checked_out'].includes(r.status)) return false;
            if (staffCurrentQuickFilter === 'canceled' && !['canceled', 'cancelled'].includes(r.status)) return false;
          }
          
          // Search filter
          if (search) {
            const searchStr = `${r.guest_name} ${r.guest_email} ${r.guest_phone} ${r.room} ${r.reservation_id}`.toLowerCase();
            if (!searchStr.includes(search)) return false;
          }
          
          // Date filter
          if (fromDate && r.check_in_date < fromDate) return false;
          if (toDate && r.check_in_date > toDate) return false;
          
          return true;
        });
        
        staffCurrentPage = 1;
        staffRenderReservations();
      }

      function staffClearFilters() {
        document.getElementById('staffSearchBox').value = '';
        document.getElementById('staffFilterFrom').value = '';
        document.getElementById('staffFilterTo').value = '';
        staffQuickFilter('all');
      }

      function staffRenderReservations() {
        const tbody = document.getElementById('staffReservationsBody');
        const start = (staffCurrentPage - 1) * staffPageSize;
        const end = start + staffPageSize;
        const pageData = staffFilteredReservations.slice(start, end);
        
        if (pageData.length === 0) {
          tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:#64748b;"><i class="fas fa-inbox" style="font-size:48px;margin-bottom:16px;opacity:0.3;display:block;"></i>No reservations found</td></tr>';
          return;
        }
        
        tbody.innerHTML = pageData.map((r, i) => `
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:16px; text-align:center; color:#64748b;">${start + i + 1}</td>
            <td style="padding:16px;">
              <div style="font-weight:600; color:#1e293b;">${escapeHtml(r.guest_name || 'N/A')}</div>
              <div style="font-size:12px; color:#64748b;">${escapeHtml(r.guest_phone || '')}</div>
            </td>
            <td style="padding:16px;">
              <div style="font-weight:500; color:#1e293b;">${escapeHtml(r.room || 'TBD')}</div>
              <div style="font-size:12px; color:#64748b;">${escapeHtml(r.package_type || r.booking_type || 'N/A')}</div>
            </td>
            <td style="padding:16px; color:#1e293b;">${r.check_in_date || 'N/A'}</td>
            <td style="padding:16px; color:#1e293b;">${r.check_out_date || 'N/A'}</td>
            <td style="padding:16px;">
              <div style="font-weight:600; color:#10b981;">₱${parseFloat(r.total_amount || 0).toLocaleString()}</div>
            </td>
            <td style="padding:16px;">
              <span style="padding:6px 12px; background:${getStatusColor(r.status)}20; color:${getStatusColor(r.status)}; border-radius:20px; font-size:12px; font-weight:600; text-transform:capitalize;">${(r.status || '').replace('_', ' ')}</span>
            </td>
            <td style="padding:16px; text-align:center;">
              <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                <button onclick="staffViewReservation('${r.reservation_id}')" style="padding:6px 12px; background:#11224e; color:white; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                ${['pending', 'pending_payment', 'pending_confirmation'].includes(r.status) ? `
                <button onclick="staffApproveReservation('${r.reservation_id}')" style="padding:6px 12px; background:#10b981; color:white; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;" title="Approve">
                  <i class="fas fa-check"></i>
                </button>
                <button onclick="staffCancelReservation('${r.reservation_id}')" style="padding:6px 12px; background:#ef4444; color:white; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;" title="Cancel">
                  <i class="fas fa-times"></i>
                </button>` : ''}
                ${r.status === 'confirmed' ? `
                <button onclick="staffCancelReservation('${r.reservation_id}')" style="padding:6px 12px; background:#ef4444; color:white; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;" title="Cancel">
                  <i class="fas fa-times"></i>
                </button>` : ''}
                ${['canceled', 'cancelled'].includes(r.status) ? `
                <button onclick="staffApproveReservation('${r.reservation_id}')" style="padding:6px 12px; background:#10b981; color:white; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;" title="Re-approve">
                  <i class="fas fa-redo"></i>
                </button>` : ''}
              </div>
            </td>
          </tr>
        `).join('');
        
        // Update pagination
        const totalPages = Math.ceil(staffFilteredReservations.length / staffPageSize);
        document.getElementById('staffPaginationInfo').textContent = `Showing ${start + 1}-${Math.min(end, staffFilteredReservations.length)} of ${staffFilteredReservations.length}`;
        document.getElementById('staffPrevPage').disabled = staffCurrentPage <= 1;
        document.getElementById('staffNextPage').disabled = staffCurrentPage >= totalPages;
      }

      function staffChangePage(delta) {
        staffCurrentPage += delta;
        staffRenderReservations();
      }

      function staffViewReservation(id) {
        const r = staffAllReservations.find(x => String(x.reservation_id) === String(id));
        if (!r) return alert('Reservation not found');
        
        const canApprove = ['pending', 'pending_payment', 'pending_confirmation'].includes(r.status);
        const canReapprove = ['canceled', 'cancelled'].includes(r.status);
        const canCancel = ['pending', 'pending_payment', 'pending_confirmation', 'confirmed'].includes(r.status);
        const isCanceled = ['canceled', 'cancelled'].includes(r.status);
        
        const refundNotice = isCanceled ? `
          <div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; gap:10px;">
            <i class="fas fa-info-circle" style="color:#f59e0b; font-size:18px;"></i>
            <div>
              <div style="font-weight:600; color:#92400e;">Payment Refundable</div>
              <div style="font-size:13px; color:#a16207;">This reservation was canceled. Payment of ₱${parseFloat(r.total_amount || 0).toLocaleString()} is eligible for refund.</div>
            </div>
          </div>
        ` : '';
        
        const actionButtons = `
          <div style="display:flex; gap:12px; justify-content:center; margin-top:20px; padding-top:20px; border-top:1px solid #e2e8f0;">
            ${canApprove ? `<button onclick="staffApproveReservation('${id}'); document.getElementById('staffModal').remove();" style="padding:12px 24px; background:#10b981; color:white; border:none; border-radius:10px; cursor:pointer; font-size:14px; font-weight:600;"><i class="fas fa-check"></i> Approve</button>` : ''}
            ${canReapprove ? `<button onclick="staffApproveReservation('${id}'); document.getElementById('staffModal').remove();" style="padding:12px 24px; background:#10b981; color:white; border:none; border-radius:10px; cursor:pointer; font-size:14px; font-weight:600;"><i class="fas fa-redo"></i> Re-approve</button>` : ''}
            ${canCancel ? `<button onclick="staffCancelReservation('${id}'); document.getElementById('staffModal').remove();" style="padding:12px 24px; background:#ef4444; color:white; border:none; border-radius:10px; cursor:pointer; font-size:14px; font-weight:600;"><i class="fas fa-times"></i> Cancel</button>` : ''}
          </div>
        `;
        
        const html = `
          <div style="padding:20px;">
            ${refundNotice}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
              <div><strong>Guest:</strong> ${escapeHtml(r.guest_name || 'N/A')}</div>
              <div><strong>Phone:</strong> ${escapeHtml(r.guest_phone || 'N/A')}</div>
              <div><strong>Email:</strong> ${escapeHtml(r.guest_email || 'N/A')}</div>
              <div><strong>Room:</strong> ${escapeHtml(r.room || 'TBD')}</div>
              <div><strong>Check-in:</strong> ${r.check_in_date || 'N/A'}</div>
              <div><strong>Check-out:</strong> ${r.check_out_date || 'N/A'}</div>
              <div><strong>Total:</strong> ₱${parseFloat(r.total_amount || 0).toLocaleString()}</div>
              <div><strong>Status:</strong> <span style="color:${getStatusColor(r.status)}; text-transform:capitalize;">${(r.status || '').replace('_', ' ')}</span></div>
            </div>
            ${actionButtons}
          </div>
        `;
        showModal('Reservation #' + id, html);
      }

      async function staffApproveReservation(id) {
        if (!confirm('Are you sure you want to approve this reservation?')) return;
        
        try {
          const formData = new FormData();
          formData.append('reservation_id', id);
          formData.append('action', 'approve');
          
          const res = await fetch('staff_reservation_actions.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
          });
          const data = await res.json();
          
          if (data.success) {
            alert('Reservation approved successfully!');
            staffFetchAllReservations();
          } else {
            alert('Failed to approve: ' + (data.message || 'Unknown error'));
          }
        } catch (err) {
          console.error(err);
          alert('Error approving reservation');
        }
      }

      async function staffCancelReservation(id) {
        if (!confirm('Are you sure you want to cancel this reservation?\n\nNote: Payment will be marked as refundable.')) return;
        
        try {
          const formData = new FormData();
          formData.append('reservation_id', id);
          formData.append('action', 'cancel');
          
          const res = await fetch('staff_reservation_actions.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
          });
          const data = await res.json();
          
          if (data.success) {
            if (data.refundable && data.total_amount > 0) {
              alert(`Reservation canceled successfully!\n\n⚠️ Payment of ₱${parseFloat(data.total_amount).toLocaleString()} is REFUNDABLE.\nPlease process the refund if payment was already made.`);
            } else {
              alert('Reservation canceled successfully!');
            }
            staffFetchAllReservations();
            // Also reload users to update cancellation badges
            loadUsers();
          } else {
            alert('Failed to cancel: ' + (data.message || 'Unknown error'));
          }
        } catch (err) {
          console.error(err);
          alert('Error canceling reservation');
        }
      }

      // ============================
      // USER MANAGEMENT
      // ============================
      let allUsers = [];

      async function loadUsers() {
        try {
          const res = await fetch('staff_get_users.php', { credentials: 'include' });
          const data = await res.json();
          
          if (!data.success) {
            document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="6" style="text-align:center;color:#ef4444;">Failed to load users</td></tr>';
            return;
          }
          
          allUsers = data.users || [];
          
          // Update stats
          document.getElementById('usersTotalCount').textContent = allUsers.length;
          document.getElementById('usersActiveCount').textContent = allUsers.filter(u => u.status === 'active').length;
          
          // Count new users this month
          const now = new Date();
          const thisMonth = allUsers.filter(u => {
            const created = new Date(u.created_at);
            return created.getMonth() === now.getMonth() && created.getFullYear() === now.getFullYear();
          }).length;
          document.getElementById('usersNewCount').textContent = thisMonth;
          
          renderUsers(allUsers);
        } catch (err) {
          console.error(err);
          document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="6" style="text-align:center;color:#ef4444;">Error loading users</td></tr>';
        }
      }

      function filterUsers() {
        const search = (document.getElementById('usersSearchBox').value || '').toLowerCase();
        const filtered = allUsers.filter(u => {
          const str = `${u.full_name} ${u.email} ${u.phone}`.toLowerCase();
          return str.includes(search);
        });
        renderUsers(filtered);
      }

      function renderUsers(users) {
        const tbody = document.getElementById('usersTableBody');
        
        if (users.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:#64748b;">No users found</td></tr>';
          return;
        }
        
        tbody.innerHTML = users.slice(0, 50).map((u, i) => {
          const cancelCount = parseInt(u.cancellation_count) || 0;
          const cancelBadge = cancelCount > 0 ? `<span style="margin-left:8px; padding:2px 8px; background:#fef2f2; color:#ef4444; border-radius:12px; font-size:11px; font-weight:600;" title="${cancelCount} canceled reservation(s)"><i class="fas fa-times-circle"></i> ${cancelCount}</span>` : '';
          
          return `
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:16px; text-align:center; color:#64748b;">${i + 1}</td>
            <td style="padding:16px;">
              <span style="font-weight:600; color:#1e293b;">${escapeHtml(u.full_name || 'N/A')}</span>
              ${cancelBadge}
            </td>
            <td style="padding:16px; color:#64748b;">${escapeHtml(u.email || 'N/A')}</td>
            <td style="padding:16px; color:#64748b;">${escapeHtml(u.phone_number || u.phone || 'N/A')}</td>
            <td style="padding:16px; color:#64748b;">${u.created_at ? new Date(u.created_at).toLocaleDateString() : 'N/A'}</td>
            <td style="padding:16px; text-align:center;">
              <span style="padding:6px 12px; background:${u.is_active ? '#10b98120' : '#ef444420'}; color:${u.is_active ? '#10b981' : '#ef4444'}; border-radius:20px; font-size:12px; font-weight:600;">${u.is_active ? 'Active' : 'Inactive'}</span>
            </td>
          </tr>
        `}).join('');
      }

      // ============================
      // UTILITY FUNCTIONS
      // ============================
      function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      function showModal(title, content) {
        const existing = document.getElementById('staffModal');
        if (existing) existing.remove();
        
        const modal = document.createElement('div');
        modal.id = 'staffModal';
        modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;';
        modal.innerHTML = `
          <div style="background:white;border-radius:16px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="padding:20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
              <h3 style="margin:0;font-size:18px;font-weight:700;color:#1e293b;">${title}</h3>
              <button onclick="document.getElementById('staffModal').remove()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;">&times;</button>
            </div>
            ${content}
          </div>
        `;
        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
        document.body.appendChild(modal);
      }

      // ============================
      // INITIALIZATION
      // ============================
      document.addEventListener('DOMContentLoaded', function() {
        loadDashboardStats();
        
        // Auto-refresh every 60 seconds
        setInterval(loadDashboardStats, 60000);
      });
    </script>
    <script src="{{ asset('js/admin-script.js') }}"></script>
</body>
</html>

