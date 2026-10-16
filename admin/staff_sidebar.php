<?php
/**
 * Staff sidebar include - Navigation for staff users (3 sections only)
 * Matches admin dashboard design but limited to Dashboard, Reservations, Users
 */
// Must be included from files under admin/
?>
<?php
// Highlight active item based on current script or hash
$current = basename($_SERVER['PHP_SELF'] ?? '');
function activeClass($file, $current) {
    return $file === $current ? 'nav-item active' : 'nav-item';
}
?>
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
    </ul>
  </nav>
</aside>

