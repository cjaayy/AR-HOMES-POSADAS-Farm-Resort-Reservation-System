<?php
/**
 * Shared header + sidebar for staff pages
 * Uses staff-specific session variables (separate from admin)
 * Design matches admin dashboard exactly
 */
if (session_status() === PHP_SESSION_NONE) session_start();

// Only allow staff with staff-specific session
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}

$staffName = $_SESSION['staff_full_name'] ?? 'Staff Member';
?>
<!-- Header - Same design as Admin -->
<header class="admin-header">
  <div class="header-left">
    <div class="logo">
      <img src="../images/logo/ar-homes-logo.png" alt="AR Homes Resort Logo" />
    </div>
    <div class="resort-info">
      <h1>AR Homes Posadas Farm Resort</h1>
    </div>
  </div>
  <div class="header-right">
    <div class="admin-profile">
      <div class="profile-info">
        <span class="admin-name"><?php echo htmlspecialchars($staffName); ?></span>
        <span class="admin-role">Staff</span>
      </div>
      <div class="profile-avatar">
        <i class="fas fa-user-tie"></i>
      </div>
    </div>
    <button class="logout-btn" id="logoutButton" onclick="showLogoutModal()" style="z-index: 1000; position: relative;">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </button>
  </div>
  <div class="mobile-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
  </div>
</header>

<?php include 'staff_sidebar.php'; ?>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
  <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); padding: 32px 28px; border-radius: 20px; max-width: 420px; width: 90vw; box-shadow: 0 12px 40px rgba(0,0,0,0.2); text-align: center; animation: modalSlideIn 0.3s ease-out; border: 1px solid rgba(255,255,255,0.3);" onclick="event.stopPropagation()">
    <div style="width: 64px; height: 64px; background: linear-gradient(180deg, #f8b500 0%, #ff6f00 35%, #e53935 65%, #6a1b9a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: #fff; font-size: 32px;">
      <i class="fas fa-sign-out-alt"></i>
    </div>
    <h3 style="color: #1e293b; margin-bottom: 12px; font-size: 24px; font-weight: 700;">Confirm Logout</h3>
    <p style="color: #64748b; font-size: 15px; margin-bottom: 28px; line-height: 1.6;">Are you sure you want to logout from your staff account?</p>
    <div style="display: flex; gap: 12px; justify-content: center;">
      <button onclick="closeLogoutModal()" style="flex: 1; padding: 12px 24px; border: 2px solid #e2e8f0; background: #fff; color: #64748b; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 15px; transition: all 0.2s;">
        Cancel
      </button>
      <button onclick="confirmLogout()" style="flex: 1; padding: 12px 24px; border: none; background: linear-gradient(180deg, #f8b500 0%, #ff6f00 35%, #e53935 65%, #6a1b9a 100%); color: #fff; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 15px; transition: all 0.2s;">
        Logout
      </button>
    </div>
  </div>
</div>

<style>
  @keyframes modalSlideIn {
    from {
      opacity: 0;
      transform: translateY(-20px) scale(0.95);
    }
    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }
  
  #logoutModal button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
</style>

<script>
  function showLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.style.display = 'flex';
  }
  
  function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.style.display = 'none';
  }
  
  function confirmLogout() {
    // Use staff-specific logout (doesn't affect admin session)
    fetch('staff_logout.php', { method: 'POST', credentials: 'include' })
      .then(() => {
        window.location.href = '../index.html';
      })
      .catch(() => {
        window.location.href = '../index.html';
      });
  }
  
  // Close modal when clicking outside
  document.getElementById('logoutModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
      closeLogoutModal();
    }
  });
  
  // Close modal with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeLogoutModal();
    }
  });
</script>

