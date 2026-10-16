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
      @if(session('admin_role') === 'super_admin' || session('admin_role') === 'admin')
      <li class="nav-item">
        <a href="#staff" class="nav-link" data-section="staff">
          <i class="fas fa-user-shield"></i>
          <span>Staff Management</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="#settings" class="nav-link" data-section="settings">
          <i class="fas fa-cog"></i>
          <span>System Settings</span>
        </a>
      </li>
      @endif
    </ul>
  </nav>
</aside>
