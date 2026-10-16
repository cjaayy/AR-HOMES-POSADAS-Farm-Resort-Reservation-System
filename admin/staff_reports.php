<?php
/**
 * Staff Reports & Analytics - View reports and statistics
 */

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
// Accept both admin session (with staff role) OR staff-specific session
$isAdminAsStaff = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_role'] ?? '') === 'staff';
$isStaffSession = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminAsStaff && !$isStaffSession) {
    header('Location: ../index.html');
    exit;
}
$staffName = $isStaffSession ? ($_SESSION['staff_full_name'] ?? 'Staff Member') : ($_SESSION['admin_full_name'] ?? 'Staff Member');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reports & Analytics - Staff</title>
  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="../images/logo/ar-homes-logo.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="../images/logo/ar-homes-logo.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="../images/logo/ar-homes-logo.png" />
  <link rel="stylesheet" href="../css/admin-styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&family=Bungee+Spice&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- PDF and Excel Export Libraries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script>
    window.logout = window.logout || function(){
      try{
        fetch('staff_logout.php', { method: 'POST', credentials: 'include' })
          .then(res => res.json().catch(() => null))
          .then(() => window.location.href = '../index.html')
          .catch(() => window.location.href = '../index.html');
      }catch(e){ window.location.href = 'staff_logout.php'; }
    };
  </script>
  <style>
    .report-card {
      background: #fff;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      margin-bottom: 24px;
    }
    
    .report-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .report-title {
      font-size: 20px;
      font-weight: 700;
      color: #1e293b;
    }
    
    .date-selector {
      display: flex;
      gap: 12px;
      align-items: center;
    }
    
    .date-selector select,
    .date-selector input {
      padding: 8px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 14px;
    }
    
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    
    .stat-box {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #fff;
      padding: 20px;
      border-radius: 12px;
      text-align: center;
    }
    
    .stat-box.green {
      background: linear-gradient(135deg, #10b981, #059669);
    }
    
    .stat-box.orange {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    
    .stat-box.red {
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    
    .stat-label {
      font-size: 14px;
      opacity: 0.9;
      margin-bottom: 8px;
    }
    
    .stat-value {
      font-size: 32px;
      font-weight: 700;
    }
    
    .chart-container {
      position: relative;
      height: 300px;
      margin-top: 20px;
    }
    
    .table-wrapper {
      overflow-x: auto;
      margin-top: 20px;
    }
    
    .report-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .report-table th {
      background: #f8fafc;
      padding: 12px;
      text-align: left;
      font-weight: 600;
      color: #64748b;
      border-bottom: 2px solid #e2e8f0;
    }
    
    .report-table td {
      padding: 12px;
      border-bottom: 1px solid #f1f5f9;
      color: #1e293b;
    }
    
    .report-table tr:hover {
      background: #f8fafc;
    }
    
    .positive {
      color: #10b981;
      font-weight: 600;
    }
    
    .negative {
      color: #ef4444;
      font-weight: 600;
    }
    
    .export-buttons {
      display: flex;
      gap: 12px;
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <?php include 'staff_header.php'; ?>

    <main class="main-content" style="padding-top:100px;">
      <section class="content-section active">
        <div class="section-header" style="margin-bottom:24px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
              <h2 style="font-size:28px; font-weight:700; color:#1e293b; margin-bottom:8px;">Reports & Analytics</h2>
              <p style="color:#64748b;">View detailed reports and insights</p>
            </div>
            <div class="export-buttons">
              <button onclick="exportPDF()" class="btn-secondary" style="display:flex; align-items:center; gap:8px;">
                <i class="fas fa-file-pdf"></i> Export PDF
              </button>
              <button onclick="exportExcel()" class="btn-primary" style="display:flex; align-items:center; gap:8px;">
                <i class="fas fa-file-excel"></i> Export Excel
              </button>
            </div>
          </div>
        </div>

        <!-- Period Selector -->
        <div class="report-card">
          <div class="date-selector">
            <label style="font-weight:600; color:#1e293b;">Report Period:</label>
            <select id="periodSelector" onchange="updateReports()">
              <option value="today">Today</option>
              <option value="week" selected>This Week</option>
              <option value="month">This Month</option>
              <option value="year">This Year</option>
              <option value="custom">Custom Range</option>
            </select>
            <input type="date" id="startDate" style="display:none;">
            <input type="date" id="endDate" style="display:none;">
            <button onclick="applyCustomDate()" id="applyDateBtn" class="btn-primary" style="display:none;">Apply</button>
          </div>
        </div>

        <!-- Key Metrics -->
        <div class="stats-row">
          <div class="stat-box">
            <div class="stat-label">Total Reservations</div>
            <div class="stat-value" id="totalReservations">127</div>
          </div>
          <div class="stat-box green">
            <div class="stat-label">Revenue</div>
            <div class="stat-value" id="totalRevenue">$45,230</div>
          </div>
          <div class="stat-box orange">
            <div class="stat-label">Occupancy Rate</div>
            <div class="stat-value" id="occupancyRate">78%</div>
          </div>
          <div class="stat-box red">
            <div class="stat-label">Cancellations</div>
            <div class="stat-value" id="totalCancellations">8</div>
          </div>
        </div>

        <!-- Reservations Trend Chart -->
        <div class="report-card">
          <div class="report-header">
            <h3 class="report-title">Reservations Trend</h3>
          </div>
          <div class="chart-container">
            <canvas id="trendChart"></canvas>
          </div>
        </div>

        <!-- Revenue Chart -->
        <div class="report-card">
          <div class="report-header">
            <h3 class="report-title">Revenue Analysis</h3>
          </div>
          <div class="chart-container">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>

        <!-- Package Distribution -->
        <div class="report-card">
          <div class="report-header">
            <h3 class="report-title">Package Distribution</h3>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
            <div class="chart-container" style="height:250px;">
              <canvas id="roomTypeChart"></canvas>
            </div>
            <div class="table-wrapper">
              <table class="report-table data-table">
                <thead>
                  <tr>
                    <th>Package Type</th>
                    <th>Bookings</th>
                    <th>Revenue</th>
                  </tr>
                </thead>
                <tbody id="roomTypeTable">
                  <tr>
                    <td colspan="3" style="text-align:center; color:#64748b;">Loading...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Top Performers -->
        <div class="report-card">
          <div class="report-header">
            <h3 class="report-title">Guest Statistics</h3>
          </div>
          <div class="table-wrapper">
            <table class="report-table guest-stats-table">
              <thead>
                <tr>
                  <th>Metric</th>
                  <th>This Week</th>
                  <th>Last Week</th>
                  <th>Change</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="4" style="text-align:center; color:#64748b;">Loading...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Performance Metrics -->
        <div class="report-card">
          <div class="report-header">
            <h3 class="report-title">Performance Metrics</h3>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
            <div>
              <h4 style="color:#64748b; font-size:14px; margin-bottom:12px;">CHECK-IN EFFICIENCY</h4>
              <div class="checkin-time" style="background:#f8fafc; padding:16px; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                  <span>Average Time</span>
                  <strong>—</strong>
                </div>
                <div style="background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                  <div style="background:#10b981; width:0%; height:100%;"></div>
                </div>
              </div>
            </div>
            <div>
              <h4 style="color:#64748b; font-size:14px; margin-bottom:12px;">RESPONSE TIME</h4>
              <div class="response-time" style="background:#f8fafc; padding:16px; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                  <span>Average Response</span>
                  <strong>—</strong>
                </div>
                <div style="background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                  <div style="background:#667eea; width:0%; height:100%;"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </section>
    </main>
  </div>

  <script>
    let trendChart, revenueChart, roomTypeChart;
    let currentReportData = null; // Store current report data for export

    function initCharts() {
      // Trend Chart
      const trendCtx = document.getElementById('trendChart').getContext('2d');
      trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [{
            label: 'Reservations',
            data: [12, 19, 15, 25, 22, 30, 28],
            borderColor: '#11224e',
            backgroundColor: 'rgba(17, 34, 78, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: '#f1f5f9'
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          }
        }
      });

      // Revenue Chart
      const revenueCtx = document.getElementById('revenueChart').getContext('2d');
      revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [{
            label: 'Revenue',
            data: [4200, 5800, 4500, 7200, 6800, 9500, 8600],
            backgroundColor: 'rgba(17, 34, 78, 0.8)',
            borderRadius: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: '#f1f5f9'
              },
              ticks: {
                callback: function(value) {
                  return '$' + value.toLocaleString();
                }
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          }
        }
      });

      // Room Type Chart
      const roomTypeCtx = document.getElementById('roomTypeChart').getContext('2d');
      roomTypeChart = new Chart(roomTypeCtx, {
        type: 'doughnut',
        data: {
          labels: ['Deluxe Room', 'Standard Room', 'Suite', 'Family Room'],
          datasets: [{
            data: [45, 52, 20, 10],
            backgroundColor: [
              '#667eea',
              '#10b981',
              '#f59e0b',
              '#ef4444'
            ],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    }

    function updateReports() {
      const period = document.getElementById('periodSelector').value;
      if (period === 'custom') {
        document.getElementById('startDate').style.display = 'block';
        document.getElementById('endDate').style.display = 'block';
        document.getElementById('applyDateBtn').style.display = 'block';
      } else {
        document.getElementById('startDate').style.display = 'none';
        document.getElementById('endDate').style.display = 'none';
        document.getElementById('applyDateBtn').style.display = 'none';
        loadReportData(period);
      }
    }

    function applyCustomDate() {
      const start = document.getElementById('startDate').value;
      const end = document.getElementById('endDate').value;
      if (start && end) {
        loadReportData('custom', start, end);
        showToast('Custom date range applied', 'success');
      } else {
        showToast('Please select both start and end dates', 'warning');
      }
    }

    async function loadReportData(period, startDate = null, endDate = null) {
      showToast('Loading report data...', 'info');
      
      try {
        let url = `staff_get_report_data.php?period=${period}`;
        if (startDate && endDate) {
          url += `&start_date=${startDate}&end_date=${endDate}`;
        }
        
        const res = await fetch(url);
        const data = await res.json();
        
        if (!data.success) {
          showToast(data.message || 'Failed to load report data', 'error');
          return;
        }
        
        // Store the data globally for export
        currentReportData = data;
        
        // Update metrics
        const m = data.metrics;
        document.getElementById('totalReservations').textContent = m.total_reservations || 0;
        document.getElementById('totalRevenue').textContent = '$' + (m.total_revenue || 0).toLocaleString();
        document.getElementById('occupancyRate').textContent = (m.occupancy_rate || 0) + '%';
        document.getElementById('totalCancellations').textContent = m.cancellations || 0;
        
        // Update trend chart
        if (trendChart && data.trend_data) {
          trendChart.data.labels = data.trend_data.labels;
          trendChart.data.datasets[0].data = data.trend_data.values;
          trendChart.update();
        }
        
        // Update revenue chart
        if (revenueChart && data.revenue_data) {
          revenueChart.data.labels = data.revenue_data.labels;
          revenueChart.data.datasets[0].data = data.revenue_data.values;
          revenueChart.update();
        }
        
        // Update room type (package type) chart
        if (roomTypeChart && data.room_type_data && data.room_type_data.length > 0) {
          const labels = data.room_type_data.map(item => item.room_type);
          const values = data.room_type_data.map(item => parseInt(item.bookings));
          roomTypeChart.data.labels = labels;
          roomTypeChart.data.datasets[0].data = values;
          roomTypeChart.update();
          
          // Update room type table
          const tbody = document.querySelector('.data-table tbody');
          tbody.innerHTML = '';
          data.room_type_data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
              <td>${item.room_type || 'N/A'}</td>
              <td>${item.bookings || 0}</td>
              <td>₱${parseFloat(item.revenue || 0).toFixed(2)}</td>
            `;
            tbody.appendChild(row);
          });
        }
        
        // Update guest statistics
        if (data.guest_stats) {
          const gs = data.guest_stats;
          const statsTable = document.querySelector('.guest-stats-table tbody');
          if (statsTable) {
            statsTable.innerHTML = '';
            
            // Helper function to calculate change display
            function getChangeDisplay(current, previous) {
              const change = current - previous;
              let changeText, changeClass;
              
              if (previous === 0 && current > 0) {
                changeText = '▲ New';
                changeClass = 'positive';
              } else if (previous === 0 && current === 0) {
                changeText = '— 0%';
                changeClass = '';
              } else if (previous > 0) {
                const percent = ((change / previous) * 100).toFixed(1);
                const icon = change >= 0 ? '▲' : '▼';
                changeText = `${icon} ${Math.abs(percent)}%`;
                changeClass = change >= 0 ? 'positive' : 'negative';
              } else {
                changeText = '— 0%';
                changeClass = '';
              }
              
              return { text: changeText, class: changeClass };
            }
            
            // Unique Guests
            if (gs.unique_guests) {
              const changeInfo = getChangeDisplay(gs.unique_guests.current, gs.unique_guests.previous);
              statsTable.innerHTML += `
                <tr>
                  <td>Unique Guests</td>
                  <td>${gs.unique_guests.current}</td>
                  <td>${gs.unique_guests.previous}</td>
                  <td class="${changeInfo.class}">${changeInfo.text}</td>
                </tr>
              `;
            }
            
            // Total Bookings
            if (gs.total_bookings) {
              const changeInfo = getChangeDisplay(gs.total_bookings.current, gs.total_bookings.previous);
              statsTable.innerHTML += `
                <tr>
                  <td>Total Bookings</td>
                  <td>${gs.total_bookings.current}</td>
                  <td>${gs.total_bookings.previous}</td>
                  <td class="${changeInfo.class}">${changeInfo.text}</td>
                </tr>
              `;
            }
            
            // Total Guests
            if (gs.total_guests) {
              const changeInfo = getChangeDisplay(gs.total_guests.current, gs.total_guests.previous);
              statsTable.innerHTML += `
                <tr>
                  <td>Total Guests</td>
                  <td>${gs.total_guests.current}</td>
                  <td>${gs.total_guests.previous}</td>
                  <td class="${changeInfo.class}">${changeInfo.text}</td>
                </tr>
              `;
            }
            
            // Average Booking Value
            if (gs.avg_booking_value) {
              const changeInfo = getChangeDisplay(gs.avg_booking_value.current, gs.avg_booking_value.previous);
              statsTable.innerHTML += `
                <tr>
                  <td>Avg. Booking Value</td>
                  <td>₱${gs.avg_booking_value.current.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                  <td>₱${gs.avg_booking_value.previous.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                  <td class="${changeInfo.class}">${changeInfo.text}</td>
                </tr>
              `;
            }
          }
        }
        
        // Update performance metrics
        if (data.performance_metrics) {
          const pm = data.performance_metrics;
          const checkinTimeEl = document.querySelector('.checkin-time');
          const responseTimeEl = document.querySelector('.response-time');
          
          if (checkinTimeEl && pm.avg_checkin_time !== undefined) {
            const minutes = Math.floor(pm.avg_checkin_time);
            checkinTimeEl.innerHTML = `
              <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span>Average Time</span>
                <strong>${minutes > 0 ? minutes + ' minutes' : 'N/A'}</strong>
              </div>
              <div style="background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                <div style="background:#10b981; width:${Math.min(100, Math.max(0, 100 - (minutes / 60 * 100)))}%; height:100%;"></div>
              </div>
            `;
          }
          
          if (responseTimeEl && pm.avg_response_time !== undefined) {
            const minutes = Math.floor(pm.avg_response_time);
            responseTimeEl.innerHTML = `
              <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span>Average Response</span>
                <strong>${minutes > 0 ? minutes + ' minutes' : 'N/A'}</strong>
              </div>
              <div style="background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                <div style="background:#667eea; width:${Math.min(100, Math.max(0, 100 - (minutes / 120 * 100)))}%; height:100%;"></div>
              </div>
            `;
          }
        }
        
        showToast('Reports updated successfully!', 'success');
      } catch (err) {
        console.error('Error loading report data:', err);
        showToast('Failed to load report data', 'error');
      }
    }

    function exportPDF() {
      if (!currentReportData) {
        showToast('Please wait for report data to load first', 'warning');
        return;
      }
      
      showToast('Generating PDF report...', 'info');
      
      try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const data = currentReportData;
        
        // Header
        doc.setFontSize(20);
        doc.setTextColor(17, 34, 78);
        doc.text('AR Homes Posadas Farm Resort', 105, 20, { align: 'center' });
        doc.setFontSize(14);
        doc.text('Staff Reports & Analytics', 105, 30, { align: 'center' });
        
        // Period info
        const period = document.getElementById('periodSelector').value;
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text(`Report Period: ${period.charAt(0).toUpperCase() + period.slice(1)} (${data.start_date} to ${data.end_date})`, 105, 38, { align: 'center' });
        doc.text(`Generated: ${new Date().toLocaleString()}`, 105, 44, { align: 'center' });
        
        // Key Metrics
        doc.setFontSize(14);
        doc.setTextColor(17, 34, 78);
        doc.text('Key Metrics', 14, 55);
        
        const m = data.metrics || {};
        const metricsData = [
          ['Total Reservations', String(m.total_reservations || 0)],
          ['Total Revenue', 'P' + (m.total_revenue || 0).toLocaleString()],
          ['Occupancy Rate', (m.occupancy_rate || 0) + '%'],
          ['Cancellations', String(m.cancellations || 0)],
          ['Confirmed Reservations', String(m.confirmed_reservations || 0)]
        ];
        
        doc.autoTable({
          startY: 60,
          head: [['Metric', 'Value']],
          body: metricsData,
          theme: 'striped',
          headStyles: { fillColor: [17, 34, 78] },
          styles: { fontSize: 11 }
        });
        
        // Reservations Trend
        if (data.trend_data && data.trend_data.labels && data.trend_data.labels.length > 0) {
          const trendY = doc.lastAutoTable.finalY + 15;
          doc.setFontSize(14);
          doc.setTextColor(17, 34, 78);
          doc.text('Reservations Trend', 14, trendY);
          
          const trendTableData = data.trend_data.labels.map((label, i) => [
            label,
            String(data.trend_data.values[i] || 0)
          ]);
          
          doc.autoTable({
            startY: trendY + 5,
            head: [['Date', 'Reservations']],
            body: trendTableData,
            theme: 'striped',
            headStyles: { fillColor: [17, 34, 78] },
            styles: { fontSize: 10 }
          });
        }
        
        // Revenue Analysis
        if (data.revenue_data && data.revenue_data.labels && data.revenue_data.labels.length > 0) {
          const revenueY = doc.lastAutoTable.finalY + 15;
          doc.setFontSize(14);
          doc.setTextColor(17, 34, 78);
          doc.text('Revenue Analysis', 14, revenueY);
          
          const revenueTableData = data.revenue_data.labels.map((label, i) => [
            label,
            'P' + (data.revenue_data.values[i] || 0).toLocaleString()
          ]);
          
          doc.autoTable({
            startY: revenueY + 5,
            head: [['Date', 'Revenue']],
            body: revenueTableData,
            theme: 'striped',
            headStyles: { fillColor: [17, 34, 78] },
            styles: { fontSize: 10 }
          });
        }
        
        // Package Distribution - New page if needed
        if (data.room_type_data && data.room_type_data.length > 0) {
          if (doc.lastAutoTable.finalY > 200) {
            doc.addPage();
          }
          const packageY = doc.lastAutoTable.finalY > 200 ? 20 : doc.lastAutoTable.finalY + 15;
          doc.setFontSize(14);
          doc.setTextColor(17, 34, 78);
          doc.text('Package Distribution', 14, packageY);
          
          const packageTableData = data.room_type_data.map(r => [
            r.room_type || 'N/A',
            String(r.bookings || 0),
            'P' + (r.revenue || 0).toLocaleString()
          ]);
          
          doc.autoTable({
            startY: packageY + 5,
            head: [['Package Type', 'Bookings', 'Revenue']],
            body: packageTableData,
            theme: 'striped',
            headStyles: { fillColor: [17, 34, 78] },
            styles: { fontSize: 10 }
          });
        }
        
        // Guest Statistics
        if (data.guest_stats) {
          const gs = data.guest_stats;
          if (doc.lastAutoTable.finalY > 220) {
            doc.addPage();
          }
          const guestY = doc.lastAutoTable.finalY > 220 ? 20 : doc.lastAutoTable.finalY + 15;
          doc.setFontSize(14);
          doc.setTextColor(17, 34, 78);
          doc.text('Guest Statistics', 14, guestY);
          
          const guestTableData = [];
          if (gs.unique_guests) {
            const change = gs.unique_guests.previous > 0 
              ? (((gs.unique_guests.current - gs.unique_guests.previous) / gs.unique_guests.previous) * 100).toFixed(1) + '%'
              : (gs.unique_guests.current > 0 ? 'New' : '0%');
            guestTableData.push(['Unique Guests', String(gs.unique_guests.current), String(gs.unique_guests.previous), change]);
          }
          if (gs.total_bookings) {
            const change = gs.total_bookings.previous > 0 
              ? (((gs.total_bookings.current - gs.total_bookings.previous) / gs.total_bookings.previous) * 100).toFixed(1) + '%'
              : (gs.total_bookings.current > 0 ? 'New' : '0%');
            guestTableData.push(['Total Bookings', String(gs.total_bookings.current), String(gs.total_bookings.previous), change]);
          }
          if (gs.avg_booking_value) {
            const change = gs.avg_booking_value.previous > 0 
              ? (((gs.avg_booking_value.current - gs.avg_booking_value.previous) / gs.avg_booking_value.previous) * 100).toFixed(1) + '%'
              : (gs.avg_booking_value.current > 0 ? 'New' : '0%');
            guestTableData.push(['Avg. Booking Value', 'P' + gs.avg_booking_value.current.toLocaleString(undefined, {minimumFractionDigits: 2}), 'P' + gs.avg_booking_value.previous.toLocaleString(undefined, {minimumFractionDigits: 2}), change]);
          }
          
          if (guestTableData.length > 0) {
            doc.autoTable({
              startY: guestY + 5,
              head: [['Metric', 'Current Period', 'Previous Period', 'Change']],
              body: guestTableData,
              theme: 'striped',
              headStyles: { fillColor: [17, 34, 78] },
              styles: { fontSize: 10 }
            });
          }
        }
        
        // Performance Metrics
        if (data.performance_metrics) {
          const pm = data.performance_metrics;
          if (doc.lastAutoTable.finalY > 240) {
            doc.addPage();
          }
          const perfY = doc.lastAutoTable.finalY > 240 ? 20 : doc.lastAutoTable.finalY + 15;
          doc.setFontSize(14);
          doc.setTextColor(17, 34, 78);
          doc.text('Performance Metrics', 14, perfY);
          
          const perfTableData = [
            ['Average Check-in Time', pm.avg_checkin_time > 0 ? pm.avg_checkin_time + ' minutes' : 'N/A'],
            ['Average Response Time', pm.avg_response_time > 0 ? pm.avg_response_time + ' minutes' : 'N/A']
          ];
          
          doc.autoTable({
            startY: perfY + 5,
            head: [['Metric', 'Value']],
            body: perfTableData,
            theme: 'striped',
            headStyles: { fillColor: [17, 34, 78] },
            styles: { fontSize: 10 }
          });
        }
        
        // Footer
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
          doc.setPage(i);
          doc.setFontSize(8);
          doc.setTextColor(150);
          doc.text(`Page ${i} of ${pageCount} | AR Homes Posadas Farm Resort`, 105, 290, { align: 'center' });
        }
        
        // Save the PDF
        doc.save(`AR_Homes_Staff_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        showToast('PDF report downloaded successfully!', 'success');
      } catch (err) {
        console.error('PDF Export Error:', err);
        showToast('Failed to generate PDF: ' + err.message, 'error');
      }
    }

    function exportExcel() {
      if (!currentReportData) {
        showToast('Please wait for report data to load first', 'warning');
        return;
      }
      
      showToast('Generating comprehensive Excel report...', 'info');
      
      try {
        const wb = XLSX.utils.book_new();
        const data = currentReportData;
        const period = document.getElementById('periodSelector').value;
        const m = data.metrics || {};
        
        // Calculate additional metrics for summary
        const totalRes = m.total_reservations || 0;
        const totalRev = m.total_revenue || 0;
        const avgRevenuePerRes = totalRes > 0 ? (totalRev / totalRes).toFixed(2) : 0;
        const numDays = data.trend_data && data.trend_data.labels ? data.trend_data.labels.length : 1;
        const avgDailyRes = numDays > 0 ? (totalRes / numDays).toFixed(2) : 0;
        const avgDailyRev = numDays > 0 ? (totalRev / numDays).toFixed(2) : 0;
        
        // Comprehensive Summary Sheet
        const summaryData = [
          ['AR Homes Posadas Farm Resort - Comprehensive Staff Report'],
          [''],
          ['REPORT INFORMATION'],
          ['Report Period:', period.charAt(0).toUpperCase() + period.slice(1)],
          ['Date Range:', `${data.start_date} to ${data.end_date}`],
          ['Number of Days:', numDays],
          ['Generated:', new Date().toLocaleString()],
          [''],
          ['KEY METRICS'],
          ['Metric', 'Value', 'Description'],
          ['Total Reservations', totalRes, 'All confirmed reservations in period'],
          ['Total Revenue', totalRev, 'Total revenue from fully paid reservations'],
          ['Occupancy Rate', (m.occupancy_rate || 0) + '%', 'Percentage of capacity utilized'],
          ['Cancellations', m.cancellations || 0, 'Number of cancelled reservations'],
          ['Confirmed Reservations', m.confirmed_reservations || 0, 'Active confirmed reservations'],
          [''],
          ['CALCULATED STATISTICS'],
          ['Metric', 'Value', 'Formula'],
          ['Avg Revenue per Reservation', avgRevenuePerRes, 'Total Revenue / Total Reservations'],
          ['Avg Daily Reservations', avgDailyRes, 'Total Reservations / Number of Days'],
          ['Avg Daily Revenue', avgDailyRev, 'Total Revenue / Number of Days'],
          ['Cancellation Rate', totalRes > 0 ? ((m.cancellations / totalRes) * 100).toFixed(1) + '%' : '0%', 'Cancellations / Total Reservations × 100']
        ];
        
        const summaryWs = XLSX.utils.aoa_to_sheet(summaryData);
        summaryWs['!cols'] = [{ wch: 30 }, { wch: 20 }, { wch: 40 }];
        XLSX.utils.book_append_sheet(wb, summaryWs, 'Summary');
        
        // Trend Data Sheet with Cumulative Totals and Statistics
        if (data.trend_data && data.trend_data.labels && data.trend_data.labels.length > 0) {
          const values = data.trend_data.values || [];
          const totalReservations = values.reduce((a, b) => a + (b || 0), 0);
          const maxRes = Math.max(...values.map(v => v || 0));
          const minRes = Math.min(...values.map(v => v || 0));
          const avgRes = values.length > 0 ? (totalReservations / values.length).toFixed(2) : 0;
          
          const trendData = [
            ['RESERVATIONS TREND ANALYSIS'],
            [''],
            ['Date', 'Reservations', 'Cumulative Total', '% of Total']
          ];
          
          let cumulative = 0;
          data.trend_data.labels.forEach((label, i) => {
            const val = values[i] || 0;
            cumulative += val;
            const pctOfTotal = totalReservations > 0 ? ((val / totalReservations) * 100).toFixed(1) + '%' : '0%';
            trendData.push([label, val, cumulative, pctOfTotal]);
          });
          
          trendData.push(['', '', '', '']);
          trendData.push(['STATISTICS', '', '', '']);
          trendData.push(['Total (Sum)', totalReservations, '', '100%']);
          trendData.push(['Average', avgRes, '', '']);
          trendData.push(['Maximum', maxRes, '', '']);
          trendData.push(['Minimum', minRes, '', '']);
          trendData.push(['Count (Days)', values.length, '', '']);
          
          const trendWs = XLSX.utils.aoa_to_sheet(trendData);
          trendWs['!cols'] = [{ wch: 18 }, { wch: 15 }, { wch: 18 }, { wch: 12 }];
          XLSX.utils.book_append_sheet(wb, trendWs, 'Trend Analysis');
        }
        
        // Revenue Data Sheet with Cumulative Totals and Statistics
        if (data.revenue_data && data.revenue_data.labels && data.revenue_data.labels.length > 0) {
          const values = data.revenue_data.values || [];
          const totalRevenue = values.reduce((a, b) => a + (b || 0), 0);
          const maxRev = Math.max(...values.map(v => v || 0));
          const minRev = Math.min(...values.map(v => v || 0));
          const avgRev = values.length > 0 ? (totalRevenue / values.length).toFixed(2) : 0;
          
          const revenueData = [
            ['REVENUE ANALYSIS'],
            [''],
            ['Date', 'Revenue', 'Cumulative Total', '% of Total']
          ];
          
          let cumulative = 0;
          data.revenue_data.labels.forEach((label, i) => {
            const val = values[i] || 0;
            cumulative += val;
            const pctOfTotal = totalRevenue > 0 ? ((val / totalRevenue) * 100).toFixed(1) + '%' : '0%';
            revenueData.push([label, val, cumulative, pctOfTotal]);
          });
          
          revenueData.push(['', '', '', '']);
          revenueData.push(['STATISTICS', '', '', '']);
          revenueData.push(['Total (Sum)', totalRevenue, '', '100%']);
          revenueData.push(['Average', parseFloat(avgRev), '', '']);
          revenueData.push(['Maximum', maxRev, '', '']);
          revenueData.push(['Minimum', minRev, '', '']);
          revenueData.push(['Count (Days)', values.length, '', '']);
          
          const revenueWs = XLSX.utils.aoa_to_sheet(revenueData);
          revenueWs['!cols'] = [{ wch: 18 }, { wch: 18 }, { wch: 18 }, { wch: 12 }];
          XLSX.utils.book_append_sheet(wb, revenueWs, 'Revenue Analysis');
        }
        
        // Package Distribution Sheet with Percentages
        if (data.room_type_data && data.room_type_data.length > 0) {
          let totalBookings = 0;
          let totalRevenue = 0;
          data.room_type_data.forEach(r => {
            totalBookings += r.bookings || 0;
            totalRevenue += r.revenue || 0;
          });
          
          const packageData = [
            ['PACKAGE DISTRIBUTION ANALYSIS'],
            [''],
            ['Package Type', 'Bookings', '% of Bookings', 'Revenue', '% of Revenue', 'Avg Revenue/Booking']
          ];
          
          data.room_type_data.forEach(r => {
            const bookings = r.bookings || 0;
            const revenue = r.revenue || 0;
            const pctBookings = totalBookings > 0 ? ((bookings / totalBookings) * 100).toFixed(1) + '%' : '0%';
            const pctRevenue = totalRevenue > 0 ? ((revenue / totalRevenue) * 100).toFixed(1) + '%' : '0%';
            const avgRevPerBooking = bookings > 0 ? (revenue / bookings).toFixed(2) : 0;
            packageData.push([r.room_type || 'N/A', bookings, pctBookings, revenue, pctRevenue, avgRevPerBooking]);
          });
          
          packageData.push(['', '', '', '', '', '']);
          packageData.push(['TOTALS', totalBookings, '100%', totalRevenue, '100%', totalBookings > 0 ? (totalRevenue / totalBookings).toFixed(2) : 0]);
          
          const packageWs = XLSX.utils.aoa_to_sheet(packageData);
          packageWs['!cols'] = [{ wch: 30 }, { wch: 12 }, { wch: 14 }, { wch: 15 }, { wch: 14 }, { wch: 18 }];
          XLSX.utils.book_append_sheet(wb, packageWs, 'Package Distribution');
        }
        
        // Guest Statistics Sheet with Enhanced Data
        if (data.guest_stats) {
          const gs = data.guest_stats;
          const guestData = [
            ['GUEST STATISTICS COMPARISON'],
            [''],
            ['Metric', 'Current Period', 'Previous Period', 'Change Amount', 'Change %', 'Trend']
          ];
          
          const addGuestRow = (name, current, previous) => {
            const changeAmt = current - previous;
            let changePct = '0%';
            let trend = '→ Stable';
            
            if (previous > 0) {
              const pct = ((changeAmt / previous) * 100).toFixed(1);
              changePct = pct + '%';
              if (parseFloat(pct) > 0) trend = '↑ Increase';
              else if (parseFloat(pct) < 0) trend = '↓ Decrease';
            } else if (current > 0) {
              changePct = 'New';
              trend = '↑ New';
            }
            
            guestData.push([name, current, previous, changeAmt, changePct, trend]);
          };
          
          if (gs.unique_guests) {
            addGuestRow('Unique Guests', gs.unique_guests.current || 0, gs.unique_guests.previous || 0);
          }
          if (gs.total_bookings) {
            addGuestRow('Total Bookings', gs.total_bookings.current || 0, gs.total_bookings.previous || 0);
          }
          if (gs.avg_booking_value) {
            addGuestRow('Avg. Booking Value', gs.avg_booking_value.current || 0, gs.avg_booking_value.previous || 0);
          }
          
          const guestWs = XLSX.utils.aoa_to_sheet(guestData);
          guestWs['!cols'] = [{ wch: 20 }, { wch: 15 }, { wch: 15 }, { wch: 15 }, { wch: 12 }, { wch: 12 }];
          XLSX.utils.book_append_sheet(wb, guestWs, 'Guest Statistics');
        }
        
        // Performance Metrics Sheet with Status Indicators
        if (data.performance_metrics) {
          const pm = data.performance_metrics;
          const checkinTime = pm.avg_checkin_time || 0;
          const responseTime = pm.avg_response_time || 0;
          
          // Determine performance status
          const checkinStatus = checkinTime <= 5 ? 'Excellent' : (checkinTime <= 10 ? 'Good' : 'Needs Improvement');
          const responseStatus = responseTime <= 30 ? 'Excellent' : (responseTime <= 60 ? 'Good' : 'Needs Improvement');
          
          const perfData = [
            ['PERFORMANCE METRICS ANALYSIS'],
            [''],
            ['Metric', 'Value', 'Unit', 'Status', 'Benchmark'],
            ['Average Check-in Time', checkinTime, 'minutes', checkinStatus, '≤5 min = Excellent, ≤10 min = Good'],
            ['Average Response Time', responseTime, 'minutes', responseStatus, '≤30 min = Excellent, ≤60 min = Good'],
            [''],
            ['PERFORMANCE SUMMARY'],
            ['Total Metrics Measured:', 2, '', '', ''],
            ['Excellent Ratings:', (checkinStatus === 'Excellent' ? 1 : 0) + (responseStatus === 'Excellent' ? 1 : 0), '', '', ''],
            ['Good Ratings:', (checkinStatus === 'Good' ? 1 : 0) + (responseStatus === 'Good' ? 1 : 0), '', '', ''],
            ['Needs Improvement:', (checkinStatus === 'Needs Improvement' ? 1 : 0) + (responseStatus === 'Needs Improvement' ? 1 : 0), '', '', '']
          ];
          
          const perfWs = XLSX.utils.aoa_to_sheet(perfData);
          perfWs['!cols'] = [{ wch: 25 }, { wch: 12 }, { wch: 10 }, { wch: 18 }, { wch: 40 }];
          XLSX.utils.book_append_sheet(wb, perfWs, 'Performance');
        }
        
        // Download the Excel file
        XLSX.writeFile(wb, `AR_Homes_Staff_Report_${new Date().toISOString().split('T')[0]}.xlsx`);
        showToast('Comprehensive Excel report downloaded successfully!', 'success');
      } catch (err) {
        console.error('Excel Export Error:', err);
        showToast('Failed to generate Excel: ' + err.message, 'error');
      }
    }

    function showToast(message, type = 'info') {
      const colors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#3b82f6',
        warning: '#f59e0b'
      };
      
      const toast = document.createElement('div');
      toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: ${colors[type]};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 500;
      `;
      toast.textContent = message;
      document.body.appendChild(toast);
      
      setTimeout(() => toast.remove(), 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
      initCharts();
      loadReportData('week'); // Load initial data
    });
  </script>
  <script src="../js/admin-script.js"></script>
</body>
</html>

