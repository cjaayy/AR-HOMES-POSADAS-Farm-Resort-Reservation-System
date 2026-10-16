// ===== NAVIGATION AND SIDEBAR FUNCTIONALITY =====

// Get DOM elements
// Support both admin (`#sidebar`) and staff (`#staff-sidebar`) markup
const sidebar =
  document.getElementById("sidebar") ||
  document.getElementById("staff-sidebar");
const navLinks = document.querySelectorAll(".nav-link");
const contentSections = document.querySelectorAll(".content-section");

// Initialize dashboard
document.addEventListener("DOMContentLoaded", function () {
  // Only initialize the single-page 'dashboard' sections on pages that
  // actually contain the admin dashboard sections (id="dashboard").
  // Staff pages use a different layout and rely on server-side PHP to
  // mark the active nav item and content; running the SPA initializer
  // there caused all content sections to be hidden on staff pages.
  if (document.getElementById("dashboard")) {
    // Check if there's a saved section in localStorage
    const savedSection = localStorage.getItem("adminActiveSection");
    const targetSection =
      savedSection && document.getElementById(savedSection)
        ? savedSection
        : "dashboard";

    // Show the saved section or dashboard by default (admin dashboard layout)
    showSection(targetSection, true); // Pass true to skip saving to localStorage on initial load
  } else {
    // If this is a non-admin/staff page, ensure at least one content
    // section remains visible. Many staff pages already render a
    // <section class="content-section active"> server-side.
    const existingActive = document.querySelector(".content-section.active");
    if (!existingActive) {
      const first = document.querySelector(".content-section");
      if (first) first.classList.add("active");
    }
  }
});

// Show specific content section
function showSection(sectionId, skipSave = false) {
  // If the requested sectionId doesn't exist on this page, bail out.
  // This avoids accidentally hiding server-rendered content on staff pages
  // when the admin SPA behavior isn't applicable.
  const targetSection = document.getElementById(sectionId);
  if (!targetSection) return;

  // Hide all sections and show the target
  contentSections.forEach((section) => {
    section.classList.remove("active");
  });
  targetSection.classList.add("active");

  // Save current section to localStorage (unless it's the initial page load)
  if (!skipSave) {
    localStorage.setItem("adminActiveSection", sectionId);
  }

  // Update active navigation item
  setActiveNavItem(sectionId);

  // Load data when switching to specific sections
  if (sectionId === "users") {
    console.log("👥 Switching to users section");
    // Load users when users section is opened
    if (typeof loadUsers === "function") {
      console.log("✅ loadUsers function found, calling it...");
      loadUsers();
    } else {
      console.error("❌ loadUsers function not found!");
    }
  }

  // Close mobile sidebar if open
  if (window.innerWidth <= 768) {
    closeSidebar();
  }
}

// Set active navigation item
function setActiveNavItem(sectionId) {
  // Remove active class from all nav items
  document.querySelectorAll(".nav-item").forEach((item) => {
    item.classList.remove("active");
  });

  // Add active class to current nav item
  const activeLink = document.querySelector(`[data-section="${sectionId}"]`);
  if (activeLink) {
    const navItemEl = activeLink.closest(".nav-item");
    if (navItemEl) navItemEl.classList.add("active");
  }
}

// Navigation link click handlers
// Only intercept links that explicitly declare a `data-section` attribute
// (single-page app behavior). Leave normal anchors (page navigations)
// untouched so links like staff_manage_users.php navigate as expected.
navLinks.forEach((link) => {
  const sectionId = link.getAttribute("data-section");
  if (!sectionId) return; // no SPA section -> allow normal navigation

  link.addEventListener("click", function (e) {
    e.preventDefault();
    showSection(sectionId);
  });
});

// ===== MOBILE SIDEBAR FUNCTIONALITY =====

// Toggle sidebar for mobile
function toggleSidebar() {
  if (!sidebar) return;
  sidebar.classList.toggle("open");

  // Create or remove overlay
  if (sidebar.classList.contains("open")) {
    createSidebarOverlay();
  } else {
    removeSidebarOverlay();
  }
}

// Close sidebar
function closeSidebar() {
  if (!sidebar) return;
  sidebar.classList.remove("open");
  removeSidebarOverlay();
}

// Create sidebar overlay for mobile
function createSidebarOverlay() {
  if (window.innerWidth <= 768) {
    let overlay = document.querySelector(".sidebar-overlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.className = "sidebar-overlay";
      document.body.appendChild(overlay);

      // Close sidebar when overlay is clicked
      overlay.addEventListener("click", closeSidebar);
    }
    overlay.classList.add("active");
  }
}

// Remove sidebar overlay
function removeSidebarOverlay() {
  const overlay = document.querySelector(".sidebar-overlay");
  if (overlay) {
    overlay.classList.remove("active");
    setTimeout(() => {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }, 300);
  }
}

// ===== LOGOUT FUNCTIONALITY =====

// Make functions globally accessible
window.logout = function logout() {
  // Show logout modal instead of confirm dialog
  showLogoutModal();
};

// Show logout modal
window.showLogoutModal = function showLogoutModal() {
  const modal = document.getElementById("logoutModal");

  if (!modal) {
    // If modal doesn't exist, logout directly
    confirmLogout();
    return;
  }

  modal.classList.add("show");
  modal.style.display = "flex";

  // Add event listener to overlay for closing modal
  const overlay = modal.querySelector(".logout-modal-overlay");
  if (overlay) {
    overlay.onclick = hideLogoutModal;
  }

  // Prevent body scroll when modal is open
  document.body.style.overflow = "hidden";
};

// Hide logout modal
window.hideLogoutModal = function hideLogoutModal() {
  const modal = document.getElementById("logoutModal");

  if (!modal) {
    return;
  }

  // Add hide animation
  modal.classList.add("hide");
  modal.classList.remove("show");

  // Remove modal after animation
  setTimeout(() => {
    modal.classList.remove("hide");
    modal.style.display = "none";
    modal.style.visibility = "hidden";
    modal.style.opacity = "0";
    modal.style.pointerEvents = "none";

    // Reset body scroll
    document.body.style.overflow = "auto";
    document.body.style.pointerEvents = "auto";

    // Remove the overlay click listener to prevent duplicates
    const overlay = modal.querySelector(".logout-modal-overlay");
    if (overlay) {
      overlay.onclick = null;
    }
  }, 300);
};

// Confirm logout action
window.confirmLogout = function confirmLogout() {
  // Hide modal first (if it exists)
  const modal = document.getElementById("logoutModal");
  if (modal) {
    hideLogoutModal();
  }

  // Add logout animation to entire page
  setTimeout(() => {
    document.body.style.opacity = "0";
    document.body.style.transition = "opacity 0.5s ease";

    // Show loading state
    const confirmBtn = document.querySelector(".logout-confirm-btn");
    if (confirmBtn) {
      confirmBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> <span>Logging out...</span>';
      confirmBtn.disabled = true;
    }

    // Call logout API - use relative path from where the page is loaded
    // Determine the correct path based on current location
    const currentPath = window.location.pathname;
    const isInAdminFolder = currentPath.includes("/admin/");
    const logoutPath = isInAdminFolder ? "logout.php" : "admin/logout.php";

    fetch(logoutPath, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((data) => {
        // Clear any stored session data
        localStorage.removeItem("adminSession");
        localStorage.removeItem("adminActiveSection"); // Clear saved section so Dashboard shows on next login
        sessionStorage.clear();

        // Redirect to login page - adjust path based on current location
        const indexPath = isInAdminFolder ? "../index.html" : "index.html";
        setTimeout(() => {
          window.location.href = indexPath;
        }, 500);
      })
      .catch((error) => {
        // Redirect anyway - adjust path based on current location
        const indexPath = isInAdminFolder ? "../index.html" : "index.html";
        window.location.href = indexPath;
      });
  }, 100);
};

// Handle ESC key to close modal
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    const modal = document.getElementById("logoutModal");
    if (modal && modal.classList.contains("show")) {
      hideLogoutModal();
    }
  }
});

// ===== RESPONSIVE FUNCTIONALITY =====

// Handle window resize
window.addEventListener("resize", function () {
  if (window.innerWidth > 768) {
    // Desktop view - ensure sidebar is visible and remove mobile overlay
    if (sidebar) sidebar.classList.remove("open");
    removeSidebarOverlay();
  }
});

// ===== DASHBOARD INTERACTIONS =====

// Quick action button handlers
document.addEventListener("DOMContentLoaded", function () {
  // Add click handlers for action buttons if they exist
  const actionButtons = document.querySelectorAll(".action-btn");
  actionButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Add click animation
      this.style.transform = "translateY(-3px) scale(0.98)";
      setTimeout(() => {
        this.style.transform = "";
      }, 150);
    });
  });

  // Add hover effects for stat cards
  const statCards = document.querySelectorAll(".stat-card");
  statCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-5px) scale(1.02)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "";
    });
  });
});

// ===== KEYBOARD NAVIGATION =====

document.addEventListener("keydown", function (e) {
  // ESC key closes mobile sidebar
  if (e.key === "Escape" && window.innerWidth <= 768) {
    closeSidebar();
  }

  // Alt + number keys for quick navigation
  if (e.altKey) {
    switch (e.key) {
      case "1":
        e.preventDefault();
        showSection("dashboard");
        break;
      case "2":
        e.preventDefault();
        showSection("reservations");
        break;
      case "3":
        e.preventDefault();
        showSection("users");
        break;
      case "4":
        e.preventDefault();
        showSection("rooms");
        break;
      case "5":
        e.preventDefault();
        showSection("reports");
        break;
      case "6":
        e.preventDefault();
        showSection("settings");
        break;
    }
  }
});

// ===== UTILITY FUNCTIONS =====

// Smooth scroll to top when switching sections
function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
}

// Format numbers for display
function formatNumber(num) {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + "M";
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + "K";
  }
  return num.toString();
}

// Format currency for display
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    minimumFractionDigits: 0,
  }).format(amount);
}

// ===== ANIMATION UTILITIES =====

// Add entrance animation to elements
function animateIn(element, delay = 0) {
  setTimeout(() => {
    element.style.opacity = "0";
    element.style.transform = "translateY(20px)";
    element.style.transition = "all 0.5s ease";

    setTimeout(() => {
      element.style.opacity = "1";
      element.style.transform = "translateY(0)";
    }, 50);
  }, delay);
}

// Stagger animations for multiple elements
function staggerAnimations(elements, delayBetween = 100) {
  elements.forEach((element, index) => {
    animateIn(element, index * delayBetween);
  });
}

// ===== DASHBOARD DATA SIMULATION =====

// Simulate real-time updates (in a real app, this would be WebSocket or polling)
function simulateDataUpdates() {
  setInterval(() => {
    // Update random stat
    const statNumbers = document.querySelectorAll(".stat-info h3");
    if (statNumbers.length > 0) {
      const randomStat =
        statNumbers[Math.floor(Math.random() * statNumbers.length)];
      const currentValue = parseInt(
        randomStat.textContent.replace(/[^\d]/g, "")
      );
      const change = Math.floor(Math.random() * 3) - 1; // -1, 0, or 1
      const newValue = Math.max(0, currentValue + change);

      if (randomStat.textContent.includes("₱")) {
        randomStat.textContent = formatCurrency(newValue);
      } else {
        randomStat.textContent = newValue.toString();
      }
    }
  }, 30000); // Update every 30 seconds
}

// Start data simulation
setTimeout(simulateDataUpdates, 5000);

// ===== ACCESSIBILITY ENHANCEMENTS =====

// Focus management for keyboard navigation
document.addEventListener("keydown", function (e) {
  // Tab navigation enhancement
  if (e.key === "Tab") {
    const focusableElements = document.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    if (e.shiftKey) {
      // Shift + Tab (backwards)
      if (document.activeElement === focusableElements[0]) {
        e.preventDefault();
        focusableElements[focusableElements.length - 1].focus();
      }
    } else {
      // Tab (forwards)
      if (
        document.activeElement ===
        focusableElements[focusableElements.length - 1]
      ) {
        e.preventDefault();
        focusableElements[0].focus();
      }
    }
  }
});

// Add focus indicators for better accessibility
document.addEventListener("DOMContentLoaded", function () {
  const focusableElements = document.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  );

  focusableElements.forEach((element) => {
    // Skip programmatic outline for password toggle buttons so CSS :focus-visible
    // can control the accessible ring for keyboard users. This prevents a box
    // appearing when the eye is clicked with mouse.
    if (element.classList && element.classList.contains("password-toggle")) {
      return;
    }

    element.addEventListener("focus", function () {
      this.style.outline = "2px solid #667eea";
      this.style.outlineOffset = "2px";
    });

    element.addEventListener("blur", function () {
      this.style.outline = "";
      this.style.outlineOffset = "";
    });
  });
});

// ===== ERROR HANDLING =====

// Global error handler
window.addEventListener("error", function (e) {
  // Show user-friendly error message
  const errorMessage = document.createElement("div");
  errorMessage.className = "error-toast";
  errorMessage.textContent = "An error occurred. Please refresh the page.";
  errorMessage.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ff6b6b;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;

  document.body.appendChild(errorMessage);

  setTimeout(() => {
    errorMessage.remove();
  }, 5000);
});

// ===== PERFORMANCE OPTIMIZATION =====

// Debounce function for resize events
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Debounced resize handler
const debouncedResize = debounce(() => {
  if (window.innerWidth > 768) {
    if (sidebar) sidebar.classList.remove("open");
    removeSidebarOverlay();
  }
}, 250);

window.addEventListener("resize", debouncedResize);

// ===== SETTINGS FUNCTIONALITY =====

// Toggle settings panel visibility
function toggleSettingsPanel(panelId) {
  const panelElement = document.querySelector(`#${panelId}-panel`);
  if (!panelElement) return; // nothing to toggle
  const option = panelElement.closest(".settings-option");
  const panel = document.getElementById(`${panelId}-panel`);
  const arrow = option ? option.querySelector(".option-arrow") : null;

  // Close all other panels first
  const allOptions = document.querySelectorAll(".settings-option");
  const allPanels = document.querySelectorAll(".settings-panel");

  allOptions.forEach((opt) => {
    if (opt !== option) {
      opt.classList.remove("active");
      opt.querySelector(".option-arrow").style.transform = "rotate(0deg)";
    }
  });

  allPanels.forEach((p) => {
    if (p !== panel) {
      p.classList.remove("active");
    }
  });

  // Toggle current panel
  const isActive = option.classList.contains("active");

  if (isActive) {
    option.classList.remove("active");
    panel.classList.remove("active");
    arrow.style.transform = "rotate(0deg)";
  } else {
    option.classList.add("active");
    panel.classList.add("active");
    arrow.style.transform = "rotate(90deg)";
  }
}

// Prevent settings panels from closing when clicking inside them
document.addEventListener("DOMContentLoaded", function () {
  const settingsPanels = document.querySelectorAll(".settings-panel");

  settingsPanels.forEach((panel) => {
    panel.addEventListener("click", function (e) {
      e.stopPropagation();
    });
  });

  // Also prevent form elements from closing the panel
  const formElements = document.querySelectorAll(
    ".settings-panel input, .settings-panel button, .settings-panel label"
  );

  formElements.forEach((element) => {
    element.addEventListener("click", function (e) {
      e.stopPropagation();
    });
  });
});

// Toggle password visibility in settings
function toggleSettingsPassword(inputId) {
  const input = document.getElementById(inputId);
  const toggleBtn = input.parentElement.querySelector(".password-toggle i");

  if (input.type === "password") {
    input.type = "text";
    toggleBtn.className = "fas fa-eye-slash";
  } else {
    input.type = "password";
    toggleBtn.className = "fas fa-eye";
  }

  // If the toggle was clicked with a mouse, remove focus from the input
  // to prevent the browser's focus outline on the input. Keyboard users
  // who use Enter/Space will still keep focus (no flag set).
  if (window.__passwordToggleClickedWithMouse) {
    try {
      input.blur();
    } finally {
      // reset flag after handling
      window.__passwordToggleClickedWithMouse = false;
    }
  }
}

// Toggle readonly/edit mode for a specific input field (used for adminFullName)
function toggleEditField(fieldId) {
  const input = document.getElementById(fieldId);
  if (!input) return;

  const btn = input.parentElement.querySelector(".field-edit-btn");
  const label = input.parentElement.querySelector(".field-edit-label");

  if (input.hasAttribute("readonly")) {
    // Enter edit mode
    input.removeAttribute("readonly");
    input.focus();
    if (btn) btn.classList.add("is-saving");
    if (label) label.textContent = "Save";
  } else {
    // Save mode - blur and set back to readonly
    input.setAttribute("readonly", "");
    input.blur();
    if (btn) btn.classList.remove("is-saving");
    if (label) label.textContent = "Edit";

    // Update header display name immediately
    const headerName = document.querySelector(".admin-name");
    if (headerName) {
      headerName.textContent = input.value || "Administrator";
    }

    // Optionally, you could auto-submit the form or mark form dirty so user can Save Changes
    const saveBtn = document.querySelector(
      '#adminProfileForm button[type="submit"]'
    );
    if (saveBtn) {
      saveBtn.disabled = false;
    }
  }
}

// Track mouse interactions on password toggle buttons so we can distinguish
// between mouse clicks and keyboard activation. This helps prevent showing
// a focus ring on the input when the eye is clicked with mouse.
document.addEventListener("DOMContentLoaded", function () {
  document.body.addEventListener("mousedown", function (e) {
    const toggle = e.target.closest && e.target.closest(".password-toggle");
    if (toggle) {
      window.__passwordToggleClickedWithMouse = true;
    }
  });

  // Clear flag on keydown so keyboard activations won't be treated as mouse
  document.body.addEventListener("keydown", function () {
    window.__passwordToggleClickedWithMouse = false;
  });
});

// Store original profile values for reset
let originalProfileValues = {
  fullName: "",
  email: "",
};

// Reset profile form
function resetProfileForm() {
  const form = document.getElementById("adminProfileForm");
  if (form) {
    // Reset to original values (from when page loaded or last saved)
    const fullNameInput = document.getElementById("adminFullName");
    const emailInput = document.getElementById("adminEmail");

    if (fullNameInput && originalProfileValues.fullName) {
      fullNameInput.value = originalProfileValues.fullName;
    }
    if (emailInput && originalProfileValues.email) {
      emailInput.value = originalProfileValues.email;
    }

    // Clear password fields
    document.getElementById("currentPassword").value = "";
    document.getElementById("newPassword").value = "";
    document.getElementById("confirmPassword").value = "";

    // Reset any error states
    clearFormErrors();

    // Show confirmation
    showNotification("Form reset to original values", "info");
  }
}

// Handle admin profile form submission
document.addEventListener("DOMContentLoaded", function () {
  const profileForm = document.getElementById("adminProfileForm");
  if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
      e.preventDefault();
      handleProfileUpdate();
    });

    // Store original values
    const fullNameInput = document.getElementById("adminFullName");
    const emailInput = document.getElementById("adminEmail");
    if (fullNameInput) originalProfileValues.fullName = fullNameInput.value;
    if (emailInput) originalProfileValues.email = emailInput.value;
  }

  // Load settings data when navigating to settings section
  const settingsNavLink = document.querySelector('a[data-section="settings"]');
  if (settingsNavLink) {
    settingsNavLink.addEventListener("click", function () {
      setTimeout(loadSettingsData, 100);
    });
  }
});

// Load settings data from server
async function loadSettingsData() {
  try {
    const res = await fetch("get_settings.php");
    const data = await res.json();

    if (data.success) {
      // Update stats
      animateSettingsCount("settingsStaffCount", data.stats.total_staff || 0);
      animateSettingsCount("settingsUsersCount", data.stats.total_users || 0);
      animateSettingsCount(
        "settingsReservationsCount",
        data.stats.total_reservations || 0
      );
      animateSettingsCount(
        "settingsActiveSessions",
        data.stats.active_sessions || 0
      );

      // Update system settings display
      if (data.settings) {
        updateSettingDisplay("resort-name", data.settings.resort_name);
        updateSettingDisplay("contact-email", data.settings.contact_email);
        updateSettingDisplay("contact-phone", data.settings.contact_phone);
        updateSettingDisplay("language", data.settings.language);
        updateSettingDisplay("timezone", data.settings.timezone);
        updateSettingDisplay("session-timeout", data.settings.session_timeout);
      }

      // Update last update time
      const lastUpdateEl = document.getElementById("settingsLastUpdate");
      if (lastUpdateEl) {
        lastUpdateEl.textContent =
          "Last updated: " + new Date().toLocaleTimeString();
      }
    }
  } catch (error) {
    console.error("Error loading settings:", error);
  }
}

function animateSettingsCount(elementId, value) {
  const element = document.getElementById(elementId);
  if (!element) return;

  const startTime = Date.now();
  const duration = 800;
  const startValue = parseInt(element.textContent) || 0;
  const difference = value - startValue;

  function step() {
    const elapsed = Date.now() - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const current = Math.floor(startValue + difference * progress);

    element.textContent = current.toLocaleString();

    if (progress < 1) {
      requestAnimationFrame(step);
    }
  }

  requestAnimationFrame(step);
}

function updateSettingDisplay(settingId, value) {
  const element = document.getElementById("setting-" + settingId);
  if (element && value) {
    element.textContent = value;
  }
}

// Handle profile update with real API call
async function handleProfileUpdate() {
  const form = document.getElementById("adminProfileForm");
  const formData = new FormData(form);

  // Get form values
  const fullName = formData.get("fullName").trim();
  const email = formData.get("email").trim();
  const currentPassword = formData.get("currentPassword").trim();
  const newPassword = formData.get("newPassword").trim();
  const confirmPassword = formData.get("confirmPassword").trim();

  // Clear previous errors
  clearFormErrors();

  // Validation
  let isValid = true;

  if (!fullName) {
    showFieldError("adminFullName", "Full name is required");
    isValid = false;
  }

  if (!email || !isValidEmail(email)) {
    showFieldError("adminEmail", "Valid email is required");
    isValid = false;
  }

  if (!currentPassword) {
    showFieldError(
      "currentPassword",
      "Current password is required to save changes"
    );
    isValid = false;
  }

  // If new password is provided, validate it
  if (newPassword) {
    if (newPassword.length < 6) {
      showFieldError(
        "newPassword",
        "New password must be at least 6 characters"
      );
      isValid = false;
    }

    if (newPassword !== confirmPassword) {
      showFieldError("confirmPassword", "Passwords do not match");
      isValid = false;
    }
  }

  if (!isValid) {
    showNotification("Please fix the errors below", "error");
    return;
  }

  // Submit to server
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  submitBtn.disabled = true;

  try {
    const response = await fetch("update_admin_profile.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        fullName: fullName,
        email: email,
        currentPassword: currentPassword,
        newPassword: newPassword,
        confirmPassword: confirmPassword,
      }),
    });

    const result = await response.json();

    if (result.success) {
      submitBtn.innerHTML = '<i class="fas fa-check"></i> Saved!';

      // Update header display name
      const headerName = document.querySelector(".admin-name");
      if (headerName) {
        headerName.textContent = fullName;
      }

      // Update original values for reset
      originalProfileValues.fullName = fullName;
      originalProfileValues.email = email;

      // Clear password fields for security
      document.getElementById("currentPassword").value = "";
      document.getElementById("newPassword").value = "";
      document.getElementById("confirmPassword").value = "";

      showNotification(
        result.message || "Profile updated successfully!",
        "success"
      );
    } else {
      submitBtn.innerHTML = '<i class="fas fa-times"></i> Failed';
      showNotification(result.message || "Failed to update profile", "error");
    }
  } catch (error) {
    console.error("Profile update error:", error);
    submitBtn.innerHTML = '<i class="fas fa-times"></i> Error';
    showNotification("An error occurred while updating profile", "error");
  }

  setTimeout(() => {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }, 2000);
}

// Edit system setting
function editSystemSetting(key, label) {
  const currentValue =
    document.getElementById("setting-" + key.replace(/_/g, "-"))?.textContent ||
    "";

  // Create modal
  const modal = document.createElement("div");
  modal.style.cssText =
    "position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:10000; animation:fadeIn 0.2s ease;";
  modal.onclick = (e) => {
    if (e.target === modal) modal.remove();
  };

  modal.innerHTML = `
    <div style="background:white; border-radius:16px; max-width:500px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:slideInDown 0.3s ease;" onclick="event.stopPropagation();">
      <div style="background:linear-gradient(135deg,#11224e,#1e3a8a); padding:24px; border-radius:16px 16px 0 0; color:white;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <div>
            <h3 style="font-size:20px; font-weight:700; margin:0;">Edit ${label}</h3>
            <p style="opacity:0.9; margin:4px 0 0 0; font-size:14px;">Update system setting</p>
          </div>
          <button onclick="this.closest('[style*=fixed]').remove()" style="width:36px; height:36px; border:none; background:rgba(255,255,255,0.2); color:white; border-radius:8px; cursor:pointer; font-size:18px;">×</button>
        </div>
      </div>
      <div style="padding:24px;">
        <div style="margin-bottom:20px;">
          <label style="display:block; font-weight:600; color:#1e293b; margin-bottom:8px;">${label}</label>
          <input type="text" id="editSettingInput" value="${currentValue}" style="width:100%; padding:14px 16px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; transition:all 0.3s; box-sizing:border-box;" onfocus="this.style.borderColor='#11224e';" onblur="this.style.borderColor='#e2e8f0';">
        </div>
        <div style="display:flex; gap:12px;">
          <button onclick="saveSystemSetting('${key}', '${label}')" style="flex:1; padding:14px; background:linear-gradient(135deg,#11224e,#1e3a8a); color:white; border:none; border-radius:10px; font-weight:600; cursor:pointer; font-size:14px;">
            <i class="fas fa-save" style="margin-right:8px;"></i>Save
          </button>
          <button onclick="this.closest('[style*=fixed]').remove()" style="flex:1; padding:14px; background:#f1f5f9; color:#64748b; border:none; border-radius:10px; font-weight:600; cursor:pointer; font-size:14px;">
            Cancel
          </button>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  // Focus the input
  setTimeout(() => {
    const input = document.getElementById("editSettingInput");
    if (input) {
      input.focus();
      input.select();
    }
  }, 100);
}

// Save system setting
async function saveSystemSetting(key, label) {
  const input = document.getElementById("editSettingInput");
  const value = input?.value.trim() || "";

  if (!value) {
    showNotification("Please enter a value", "warning");
    return;
  }

  try {
    const response = await fetch("update_settings.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ key, value }),
    });

    const result = await response.json();

    if (result.success) {
      // Update display
      const displayId = "setting-" + key.replace(/_/g, "-");
      const displayEl = document.getElementById(displayId);
      if (displayEl) {
        displayEl.textContent = value;
      }

      // Close modal
      const modal = document.querySelector('[style*="position:fixed"]');
      if (modal) modal.remove();

      showNotification(label + " updated successfully!", "success");
    } else {
      showNotification(result.message || "Failed to update setting", "error");
    }
  } catch (error) {
    console.error("Settings update error:", error);
    showNotification("An error occurred while saving", "error");
  }
}

// Email validation function
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Show field error
function showFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const wrapper = field.closest(".input-wrapper");

  // Remove existing error
  const existingError = wrapper.parentElement.querySelector(".error-message");
  if (existingError) {
    existingError.remove();
  }

  // Add error styling
  wrapper.style.borderColor = "#e74c3c";
  field.style.borderColor = "#e74c3c";

  // Add error message
  const errorDiv = document.createElement("div");
  errorDiv.className = "error-message";
  errorDiv.style.cssText = `
    color: #e74c3c;
    font-size: 0.8rem;
    margin-top: 0.3rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
  `;
  errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;

  wrapper.parentElement.appendChild(errorDiv);
}

// Clear form errors
function clearFormErrors() {
  const errorMessages = document.querySelectorAll(".error-message");
  errorMessages.forEach((error) => error.remove());

  const inputs = document.querySelectorAll("#adminProfileForm input");
  inputs.forEach((input) => {
    input.style.borderColor = "";
    const wrapper = input.closest(".input-wrapper");
    if (wrapper) {
      wrapper.style.borderColor = "";
    }
  });
}

// Show notification
function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;

  const colors = {
    success: "#27ae60",
    error: "#e74c3c",
    info: "#3498db",
    warning: "#f39c12",
  };

  const icons = {
    success: "check-circle",
    error: "exclamation-circle",
    info: "info-circle",
    warning: "exclamation-triangle",
  };

  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${colors[type]};
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    z-index: 10000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    max-width: 300px;
    animation: slideIn 0.3s ease;
  `;

  notification.innerHTML = `<i class="fas fa-${icons[type]}"></i> ${message}`;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = "slideOut 0.3s ease";
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

// Add CSS animations for notifications
const notificationStyles = document.createElement("style");
notificationStyles.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
`;
document.head.appendChild(notificationStyles);

// Attach logout button event listener when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // Try several selectors so the script works across admin/staff templates
  const logoutSelectors = [
    "#logoutButton",
    "#logoutBtn",
    "[data-logout]",
    ".logout-btn",
    'a[href*="logout.php"]',
  ];

  let logoutButton = null;
  let usedSelector = null;

  for (const sel of logoutSelectors) {
    const el = document.querySelector(sel);
    if (el) {
      logoutButton = el;
      usedSelector = sel;
      break;
    }
  }

  if (logoutButton) {
    // Remove any existing listeners and add new one
    logoutButton.addEventListener("click", function (e) {
      // If it's an anchor, prevent default navigation to allow modal flow
      if (this.tagName.toLowerCase() === "a") {
        e.preventDefault();
      }
      e.stopPropagation();

      if (window.__debugAdminScript) {
      }

      // Ensure button is not disabled
      if (this.disabled) {
        if (window.__debugAdminScript) return;
      }

      logout();
    });

    // Ensure button is always clickable when present
    try {
      logoutButton.disabled = false;
      logoutButton.style.pointerEvents = "auto";
      logoutButton.style.cursor = "pointer";
    } catch (e) {
      // in case element doesn't support disabled prop
    }

    if (window.__debugAdminScript) {
      console.log(
        "✅ Logout button event listener attached (selector: " +
          usedSelector +
          ")"
      );
    }
  } else {
    // Avoid noisy console warnings in production; only notify when debugging is enabled
    if (window.__debugAdminScript) {
      console.warn(
        "⚠️ Logout button not found (tried selectors):",
        logoutSelectors.join(", ")
      );
    }
  }
});

// ===== USER MANAGEMENT FUNCTIONALITY =====

let allUsers = [];
let filteredUsers = [];
let currentPage = 1;
const usersPerPage = 10;

// Load users when users section is shown
document.addEventListener("DOMContentLoaded", function () {
  // Add click handler for users nav link
  const usersNavLink = document.querySelector('[data-section="users"]');
  if (usersNavLink) {
    usersNavLink.addEventListener("click", function () {
      loadUsers();
    });
  }

  // Add search functionality
  const searchInput = document.getElementById("searchUsers");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      filterUsers();
    });
  }
});

// Fetch users from the server
async function loadUsers() {
  const tableBody = document.getElementById("usersTableBody");

  // Show loading state
  tableBody.innerHTML = `
    <tr>
      <td colspan="9" style="text-align: center; padding: 2rem;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
        <p style="margin-top: 1rem; color: #666;">Loading users...</p>
      </td>
    </tr>
  `;

  try {
    const response = await fetch("get_users.php");
    const data = await response.json();

    if (data.success) {
      allUsers = data.users;
      filteredUsers = [...allUsers];

      // Update statistics
      updateUserStatistics();

      // Update filter counts
      updateUserFilterCounts();

      // Display users
      displayUsers();
    } else {
      showError("Failed to load users: " + data.message);
    }
  } catch (error) {
    showError("Error loading users. Please check the console for details.");
  }
}

// Update user statistics
function updateUserStatistics() {
  const totalUsers = allUsers.length;
  const activeUsers = allUsers.filter((u) => u.is_active == 1).length;

  // Count new users this month
  const currentDate = new Date();
  const currentMonth = currentDate.getMonth();
  const currentYear = currentDate.getFullYear();
  const newUsers = allUsers.filter((user) => {
    const createdDate = new Date(user.created_at);
    return (
      createdDate.getMonth() === currentMonth &&
      createdDate.getFullYear() === currentYear
    );
  }).length;

  // Update UI in Manage Users section with animation
  animateCountUp("manageTotalUsersCount", totalUsers);
  animateCountUp("manageActiveUsersCount", activeUsers);
  animateCountUp("manageNewUsersCount", newUsers);
}

// Animate counter from current value to target value
function animateCountUp(elementId, targetValue) {
  const element = document.getElementById(elementId);
  if (!element) return;

  const currentValue = parseInt(element.textContent) || 0;
  const duration = 1000; // 1 second
  const steps = 30;
  const increment = (targetValue - currentValue) / steps;
  const stepDuration = duration / steps;

  let currentStep = 0;

  const timer = setInterval(() => {
    currentStep++;
    const newValue = Math.round(currentValue + increment * currentStep);

    if (currentStep >= steps) {
      element.textContent = targetValue;
      clearInterval(timer);
    } else {
      element.textContent = newValue;
    }
  }, stepDuration);
}

// Quick filter for user status with chip UI
function userQuickFilter(status) {
  // Update active chip styling
  const chips = ["all", "active", "inactive"];
  chips.forEach((s) => {
    const chip = document.getElementById(`user-chip-${s}`);
    if (chip) {
      if (s === status) {
        chip.style.background = "#11224e";
        chip.style.boxShadow = "0 4px 16px rgba(17,34,78,0.3)";
        chip.querySelector("span").style.color = "white";
        chip.querySelector('div[style*="background"]').style.background =
          "rgba(255,255,255,0.25)";
        chip.querySelector('div[style*="background"]').style.color = "white";
        chip.querySelectorAll("div")[2].style.color = "white";
      } else {
        chip.style.background = "white";
        chip.style.boxShadow = "none";
        chip.querySelector("span").style.color = "#11224e";
        chip.querySelector('div[style*="background"]').style.background =
          "rgba(17,34,78,0.1)";
        chip.querySelector('div[style*="background"]').style.color = "#11224e";
        chip.querySelectorAll("div")[2].style.color = "#11224e";
      }
    }
  });

  // Apply filter
  if (status === "all") {
    filteredUsers = [...allUsers];
  } else if (status === "active") {
    filteredUsers = allUsers.filter((u) => u.is_active == 1);
  } else if (status === "inactive") {
    filteredUsers = allUsers.filter((u) => u.is_active == 0);
  }

  // Also apply search if present
  const searchTerm = document
    .getElementById("searchUsers")
    ?.value.toLowerCase();
  if (searchTerm) {
    filteredUsers = filteredUsers.filter((user) => {
      return (
        user.full_name.toLowerCase().includes(searchTerm) ||
        user.username.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm) ||
        user.phone_number.includes(searchTerm)
      );
    });
  }

  currentPage = 1;
  displayUsers();
  updateUserFilterCounts();
}

// Clear all user filters
function userClearFilters() {
  document.getElementById("searchUsers").value = "";
  userQuickFilter("all");
}

// Update filter count badges
function updateUserFilterCounts() {
  const totalCount = allUsers.length;
  const activeCount = allUsers.filter((u) => u.is_active == 1).length;
  const inactiveCount = allUsers.filter((u) => u.is_active == 0).length;

  const allBadge = document.getElementById("user-count-all");
  const activeBadge = document.getElementById("user-count-active");
  const inactiveBadge = document.getElementById("user-count-inactive");

  if (allBadge) allBadge.textContent = totalCount;
  if (activeBadge) activeBadge.textContent = activeCount;
  if (inactiveBadge) inactiveBadge.textContent = inactiveCount;
}

// Display users in the table
function displayUsers() {
  const tableBody = document.getElementById("usersTableBody");

  if (filteredUsers.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align: center; padding: 2rem;">
          <i class="fas fa-users" style="font-size: 2rem; color: #999;"></i>
          <p style="margin-top: 1rem; color: #666;">No users found</p>
        </td>
      </tr>
    `;
    return;
  }

  // Calculate pagination
  const startIndex = (currentPage - 1) * usersPerPage;
  const endIndex = startIndex + usersPerPage;
  const usersToDisplay = filteredUsers.slice(startIndex, endIndex);

  // Generate table rows
  const rows = usersToDisplay
    .map((user, idx) => {
      const statusText = user.is_active == 1 ? "Active" : "Inactive";
      const statusDot =
        user.is_active == 1
          ? '<i class="fas fa-circle" style="font-size:6px; color:#10b981;"></i>'
          : "";

      return `
      <tr data-user-id="${user.user_id}" style="animation:fadeIn 0.3s ease ${
        idx * 0.05
      }s both; border-bottom:1px solid #f1f5f9;">
        <td style="padding:14px 12px; text-align:center; font-weight:700; color:#64748b; font-size:13px;">${
          user.user_id
        }</td>
        <td style="padding:14px 12px;">
          <div style="font-weight:600; color:#1e293b; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(
            user.full_name
          )}">${escapeHtml(user.full_name)}</div>
        </td>
        <td style="padding:14px 12px; text-align:center;">
          <div style="font-weight:500; color:#475569; font-size:13px; white-space:nowrap;" title="${escapeHtml(
            user.username
          )}">${escapeHtml(user.username)}</div>
        </td>
        <td style="padding:14px 12px; text-align:center;">
          <div style="font-size:12px; color:#64748b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(
            user.email
          )}">${escapeHtml(user.email)}</div>
        </td>
        <td style="padding:14px 12px;">
          <div style="font-weight:500; color:#475569; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(
            user.phone_formatted
          )}</div>
        </td>
        <td style="padding:14px 8px; text-align:center;">
          <span style="display:inline-flex; align-items:center; gap:${
            statusDot ? "4px" : "0"
          }; padding:5px 10px; background:${
        user.is_active == 1
          ? "linear-gradient(135deg,#10b981,#059669)"
          : "linear-gradient(135deg,#ef4444,#dc2626)"
      }; color:white; border-radius:16px; font-size:11px; font-weight:600; white-space:nowrap;">
            ${statusDot}${statusDot ? " " : ""}${statusText}
          </span>
        </td>
        <td style="padding:14px 10px; text-align:center;">
          <div style="font-weight:500; color:#475569; font-size:12px; white-space:nowrap;">${
            user.member_since
          }</div>
        </td>
        <td style="padding:14px 10px; text-align:center;">
          <div style="font-size:12px; color:#64748b; white-space:nowrap;">${
            user.last_login_formatted
          }</div>
        </td>
        <td style="padding:14px 16px 14px 8px; text-align:center;">
          <div style="display:flex; gap:4px; justify-content:center; flex-wrap:nowrap;">
            <button onclick="viewUser('${
              user.user_id
            }')" style="width:28px; height:28px; border:none; background:#f1f5f9; color:#667eea; border-radius:6px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px;" title="View Details" onmouseover="this.style.background='linear-gradient(135deg,#667eea,#764ba2)'; this.style.color='white';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#667eea';"><i class="fas fa-eye"></i></button>
            <button onclick="editUser('${
              user.user_id
            }')" style="width:28px; height:28px; border:none; background:#f1f5f9; color:#3b82f6; border-radius:6px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px;" title="Edit User" onmouseover="this.style.background='linear-gradient(135deg,#3b82f6,#2563eb)'; this.style.color='white';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#3b82f6';"><i class="fas fa-edit"></i></button>
            <button onclick="toggleUserStatus('${user.user_id}', ${
        user.is_active
      })" style="width:28px; height:28px; border:none; background:${
        user.is_active == 1
          ? "#f1f5f9"
          : "linear-gradient(135deg,#ef4444,#dc2626)"
      }; color:${
        user.is_active == 1 ? "#10b981" : "white"
      }; border-radius:6px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px;" title="${
        user.is_active == 1 ? "Deactivate User" : "Activate User"
      }" onmouseover="this.style.background='linear-gradient(135deg,${
        user.is_active == 1 ? "#10b981,#059669" : "#10b981,#059669"
      })'; this.style.color='white';" onmouseout="this.style.background='${
        user.is_active == 1
          ? "#f1f5f9"
          : "linear-gradient(135deg,#ef4444,#dc2626)"
      }'; this.style.color='${
        user.is_active == 1 ? "#10b981" : "white"
      }';"><i class="fas fa-power-off"></i></button>
            <button onclick="deleteUser('${
              user.user_id
            }')" style="width:28px; height:28px; border:none; background:#f1f5f9; color:#ef4444; border-radius:6px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px;" title="Delete User" onmouseover="this.style.background='linear-gradient(135deg,#ef4444,#dc2626)'; this.style.color='white';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#ef4444';"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>
    `;
    })
    .join("");

  tableBody.innerHTML = rows;

  // Update pagination
  updatePagination();
}

// Filter users based on search (status is handled by chips)
function filterUsers() {
  const searchTerm = document.getElementById("searchUsers").value.toLowerCase();

  // Get the currently active status filter from chips
  let statusFilter = "all";
  if (
    document.getElementById("user-chip-active")?.style.background ===
    "rgb(17, 34, 78)"
  ) {
    statusFilter = "active";
  } else if (
    document.getElementById("user-chip-inactive")?.style.background ===
    "rgb(17, 34, 78)"
  ) {
    statusFilter = "inactive";
  }

  filteredUsers = allUsers.filter((user) => {
    // Search filter
    const matchesSearch =
      !searchTerm ||
      user.full_name.toLowerCase().includes(searchTerm) ||
      user.username.toLowerCase().includes(searchTerm) ||
      user.email.toLowerCase().includes(searchTerm) ||
      user.phone_number.includes(searchTerm);

    // Status filter
    const matchesStatus =
      statusFilter === "all" ||
      (statusFilter === "active" && user.is_active == 1) ||
      (statusFilter === "inactive" && user.is_active == 0);

    return matchesSearch && matchesStatus;
  });

  // Reset to first page
  currentPage = 1;

  // Display filtered users
  displayUsers();
}

// Update pagination controls
function updatePagination() {
  const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
  const startIndex = (currentPage - 1) * usersPerPage + 1;
  const endIndex = Math.min(currentPage * usersPerPage, filteredUsers.length);

  // Update pagination info
  const paginationInfo = document.getElementById("usersPaginationInfo");
  if (paginationInfo) {
    paginationInfo.textContent = `Showing ${startIndex}-${endIndex} of ${filteredUsers.length} users`;
  }

  // Update prev/next buttons
  const prevBtn = document.getElementById("usersPrevPage");
  const nextBtn = document.getElementById("usersNextPage");

  if (prevBtn) {
    prevBtn.disabled = currentPage === 1;
    prevBtn.style.opacity = currentPage === 1 ? "0.5" : "1";
    prevBtn.style.cursor = currentPage === 1 ? "not-allowed" : "pointer";
  }

  if (nextBtn) {
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
    nextBtn.style.opacity =
      currentPage === totalPages || totalPages === 0 ? "0.5" : "1";
    nextBtn.style.cursor =
      currentPage === totalPages || totalPages === 0
        ? "not-allowed"
        : "pointer";
  }
}

// Change page
function usersChangePage(delta) {
  const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
  const newPage = currentPage + delta;

  if (newPage < 1 || newPage > totalPages) return;

  currentPage = newPage;
  displayUsers();

  // Scroll to top of table
  document
    .querySelector(".users-container")
    ?.scrollIntoView({ behavior: "smooth" });
}

// View user details
function viewUser(userId) {
  const user = allUsers.find((u) => u.user_id === userId);
  if (!user) return;

  // Check for pending email
  let pendingEmailHtml = "";
  if (user.has_pending_email && user.pending_email) {
    const isPendingExpired = user.pending_email_expired;
    pendingEmailHtml = `
      <div style="grid-column: 1 / -1; background: ${
        isPendingExpired ? "#fee2e2" : "#fef3c7"
      }; padding: 0.75rem; border-radius: 8px; border-left: 4px solid ${
      isPendingExpired ? "#ef4444" : "#f59e0b"
    };">
        <strong><i class="fas fa-envelope-open-text"></i> Pending Email Change:</strong><br>
        <span style="color: ${isPendingExpired ? "#dc2626" : "#d97706"};">
          ${escapeHtml(user.pending_email)}
          ${
            isPendingExpired
              ? ' <span style="color: #dc2626;">(Expired)</span>'
              : ""
          }
        </span>
        ${
          user.pending_email_expires_formatted
            ? `<br><small>Expires: ${user.pending_email_expires_formatted}</small>`
            : ""
        }
      </div>
    `;
  }

  const modalContent = `
    <div style="padding: 1rem;">
      <h3 style="color: #667eea; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-user-circle"></i> User Details
      </h3>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div>
          <strong>User ID:</strong><br>
          <span>#${user.user_id}</span>
        </div>
        <div>
          <strong>Status:</strong><br>
          <span class="status-badge ${
            user.is_active == 1 ? "active" : "inactive"
          }">
            ${user.is_active == 1 ? "Active" : "Inactive"}
          </span>
        </div>
        <div>
          <strong>Full Name:</strong><br>
          <span>${escapeHtml(user.full_name)}</span>
        </div>
        <div>
          <strong>Username:</strong><br>
          <span>${escapeHtml(user.username)}</span>
        </div>
        <div>
          <strong>Email:</strong><br>
          <span>${escapeHtml(user.email)}</span>
        </div>
        <div>
          <strong>Phone:</strong><br>
          <span>${escapeHtml(user.phone_number)}</span>
        </div>
        ${pendingEmailHtml}
        <div>
          <strong>Member Since:</strong><br>
          <span>${user.member_since}</span>
        </div>
        <div>
          <strong>Last Login:</strong><br>
          <span>${user.last_login_formatted}</span>
        </div>
        <div>
          <strong>Account Created:</strong><br>
          <span>${user.created_at_formatted}</span>
        </div>
        <div style="grid-column: 1 / -1;">
          <strong>Last Updated:</strong><br>
          <span>${user.updated_at_formatted}</span>
        </div>
      </div>
    </div>
  `;

  showModal("User Details", modalContent);
}

// Edit user
function editUser(userId) {
  const user = allUsers.find((u) => u.user_id === userId);
  if (!user) return;

  const modalContent = `
    <form id="editUserForm" style="padding: 1rem;">
      <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
        <div>
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Full Name:</label>
          <input type="text" id="edit_full_name" value="${escapeHtml(
            user.full_name
          )}" 
                 style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem;">
        </div>
        <div>
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Username:</label>
          <input type="text" id="edit_username" value="${escapeHtml(
            user.username
          )}" 
                 style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem;">
        </div>
        <div>
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email:</label>
          <input type="email" id="edit_email" value="${escapeHtml(user.email)}" 
                 style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem;">
          <small style="display: block; margin-top: 0.25rem; color: #f59e0b; font-size: 0.8rem;">
            <i class="fas fa-exclamation-triangle"></i> Changing email requires verification. A verification email will be sent to the new address.
          </small>
        </div>
        <div>
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Phone:</label>
          <input type="text" id="edit_phone" value="${escapeHtml(
            user.phone_number
          )}" 
                 style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem;">
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
          <button type="button" onclick="closeUserModal()" 
                  style="flex: 1; padding: 0.75rem; background: #e0e0e0; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
            Cancel
          </button>
          <button type="submit" 
                  style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
            Save Changes
          </button>
        </div>
      </div>
    </form>
  `;

  showModal("Edit User", modalContent);

  // Handle form submission
  document
    .getElementById("editUserForm")
    .addEventListener("submit", async (e) => {
      e.preventDefault();

      const newEmail = document.getElementById("edit_email").value;
      const originalEmail = user.email;
      const emailChanged = newEmail !== originalEmail;

      // If email is being changed, show a confirmation dialog
      if (emailChanged) {
        const confirmChange = confirm(
          `⚠️ Email Change Warning\n\n` +
            `You are changing the email from:\n${originalEmail}\n\nTo:\n${newEmail}\n\n` +
            `The user will need to verify the new email address before it becomes active. ` +
            `A verification email will be sent to the new address.\n\n` +
            `The old email will continue to work until the new one is verified.\n\n` +
            `Do you want to proceed?`
        );
        if (!confirmChange) {
          return;
        }
      }

      const updatedData = {
        user_id: userId,
        full_name: document.getElementById("edit_full_name").value,
        username: document.getElementById("edit_username").value,
        email: newEmail,
        phone_number: document.getElementById("edit_phone").value,
      };

      try {
        const response = await fetch("update_user.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(updatedData),
        });

        const result = await response.json();

        if (result.success) {
          if (result.email_changed && result.verification_required) {
            showNotification(
              "User updated! A verification email has been sent to the new email address. " +
                "The email will only change after verification.",
              "success"
            );
          } else {
            showNotification("User updated successfully!", "success");
          }
          closeUserModal();
          loadUsers(); // Reload the users list
        } else {
          showNotification("Error: " + result.message, "error");
        }
      } catch (error) {
        showNotification("Failed to update user. Please try again.", "error");
      }
    });
}

// Toggle user status
function toggleUserStatus(userId, currentStatus) {
  const newStatus = currentStatus == 1 ? 0 : 1;
  const statusText = newStatus == 1 ? "activate" : "deactivate";

  if (confirm(`Are you sure you want to ${statusText} this user?`)) {
    fetch("update_user_status.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        user_id: userId,
        status: newStatus,
      }),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.success) {
          showNotification(`User ${statusText}d successfully!`, "success");
          loadUsers(); // Reload the users list
        } else {
          showNotification("Error: " + result.message, "error");
        }
      })
      .catch((error) => {
        showNotification(
          "Failed to update user status. Please try again.",
          "error"
        );
      });
  }
}

// Delete user
function deleteUser(userId) {
  const user = allUsers.find((u) => u.user_id === userId);
  if (!user) return;

  if (
    confirm(
      `Are you sure you want to delete user "${user.full_name}"? This action cannot be undone.`
    )
  ) {
    fetch("delete_user.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        user_id: userId,
      }),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.success) {
          showNotification("User deleted successfully!", "success");
          loadUsers(); // Reload the users list
        } else {
          showNotification("Error: " + result.message, "error");
        }
      })
      .catch((error) => {
        showNotification("Failed to delete user. Please try again.", "error");
      });
  }
}

// Show modal
function showModal(title, content) {
  // Create modal overlay
  const modal = document.createElement("div");
  modal.className = "user-modal";
  modal.innerHTML = `
    <div class="user-modal-overlay" onclick="closeUserModal()"></div>
    <div class="user-modal-content">
      <div class="user-modal-header">
        <h3>${title}</h3>
        <button onclick="closeUserModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="user-modal-body">
        ${content}
      </div>
    </div>
  `;

  // Add modal styles
  const style = document.createElement("style");
  style.textContent = `
    .user-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .user-modal-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
    }
    .user-modal-content {
      position: relative;
      background: white;
      border-radius: 12px;
      max-width: 600px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      animation: modalSlideIn 0.3s ease;
    }
    .user-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      border-bottom: 1px solid #e0e0e0;
    }
    .user-modal-body {
      padding: 1.5rem;
    }
    @keyframes modalSlideIn {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
  `;

  document.head.appendChild(style);
  document.body.appendChild(modal);
  document.body.style.overflow = "hidden";
}

// Close modal
function closeUserModal() {
  const modal = document.querySelector(".user-modal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
  }
}

// Show error message
function showError(message) {
  const tableBody = document.getElementById("usersTableBody");
  tableBody.innerHTML = `
    <tr>
      <td colspan="10" style="text-align: center; padding: 2rem;">
        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #f44336;"></i>
        <p style="margin-top: 1rem; color: #f44336;">${message}</p>
        <button onclick="loadUsers()" style="
          margin-top: 1rem;
          padding: 0.75rem 1.5rem;
          background: #667eea;
          color: white;
          border: none;
          border-radius: 8px;
          cursor: pointer;
          font-weight: 600;
        ">
          <i class="fas fa-redo"></i> Retry
        </button>
      </td>
    </tr>
  `;
}

// Utility function to escape HTML
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// Make functions globally available
window.loadUsers = loadUsers;
window.filterUsers = filterUsers;
window.userQuickFilter = userQuickFilter;
window.userClearFilters = userClearFilters;
window.usersChangePage = usersChangePage;
window.exportUsersCSV = exportUsersCSV;
window.viewUser = viewUser;
window.editUser = editUser;
window.toggleUserStatus = toggleUserStatus;
window.deleteUser = deleteUser;
window.closeUserModal = closeUserModal;

// Export users to CSV
function exportUsersCSV() {
  if (!filteredUsers || filteredUsers.length === 0) {
    alert("No users to export");
    return;
  }

  const headers = [
    "User ID",
    "Full Name",
    "Username",
    "Email",
    "Phone",
    "Status",
    "Member Since",
    "Last Login",
  ];
  const rows = filteredUsers.map((u) => [
    u.user_id,
    u.full_name,
    u.username,
    u.email,
    u.phone_formatted,
    u.is_active == 1 ? "Active" : "Inactive",
    u.member_since,
    u.last_login_formatted,
  ]);

  const csvContent = [
    headers.join(","),
    ...rows.map((row) => row.map((cell) => `"${cell}"`).join(",")),
  ].join("\n");

  const blob = new Blob([csvContent], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `users_export_${new Date().toISOString().split("T")[0]}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}
