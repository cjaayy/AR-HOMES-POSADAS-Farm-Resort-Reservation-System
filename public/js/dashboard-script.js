// ===== DASHBOARD JAVASCRIPT =====

// Test function to verify popup works
window.testPopup = function () {
  const popup = document.getElementById("popupContentArea");
  const content = document.getElementById("popupBody");
  const title = document.getElementById("popupTitle");

  if (!popup) {
    return false;
  }

  if (!content) {
    return false;
  }

  if (!title) {
    return false;
  }

  // Set test content
  title.textContent = "TEST POPUP";
  content.innerHTML =
    '<h2 style="color: red;">THIS IS A TEST!</h2><p>If you can see this, the popup is working!</p>';

  // Show popup
  popup.classList.add("active");
  popup.style.display = "block";

  return true;
};

// Simple popup function - MUST BE FIRST
function showPopup(section) {
  const popup = document.getElementById("popupContentArea");
  const content = document.getElementById("popupBody");
  const title = document.getElementById("popupTitle");

  if (!popup || !content || !title) {
    alert("Error: Popup elements not found. Check the console.");
    return;
  }

  // Clear existing content
  content.innerHTML = "";

  // Get content based on section
  let contentHTML = "";
  let titleText = "";

  // Simplified content loading - try to get content from templates
  const contentElement = document.getElementById(section + "-content");
  if (contentElement) {
    titleText = getTitleForSection(section);
    contentHTML = contentElement.innerHTML;
  } else {
    titleText = "Content Not Available";
    contentHTML =
      '<div style="padding: 2rem; text-align: center;"><h3>Section: ' +
      section +
      "</h3><p>Content template not found, but popup is working!</p></div>";
  }

  // Set title and content
  title.textContent = titleText;
  content.innerHTML = contentHTML;

  // Show popup with animation
  popup.classList.add("active");
  popup.style.display = "block";

  // Update active button
  document
    .querySelectorAll(".nav-btn")
    .forEach((btn) => btn.classList.remove("active"));
  const targetBtn = document.querySelector(`[data-section="${section}"]`);
  if (targetBtn) {
    targetBtn.classList.add("active");
  }
}

function getTitleForSection(section) {
  const titles = {
    dashboard: "Dashboard Overview",
    "make-reservation": "Make New Reservation",
    "my-reservations": "My Reservations",
    promotions: "Special Promotions",
    profile: "Profile Settings",
  };
  return titles[section] || "Unknown Section";
}

// Close popup function
function closePopup() {
  const popup = document.getElementById("popupContentArea");
  if (popup) {
    popup.classList.remove("active");
    popup.style.display = "none";
  }
}

// Load user data from PHP session (passed via userData global variable from dashboard.html)
function loadUserData() {
  // Check if userData exists (set by dashboard.html after session check)
  if (
    typeof window.userData !== "undefined" &&
    window.userData &&
    window.userData.user_id
  ) {
    return {
      name: window.userData.full_name,
      email: window.userData.email,
      phone: window.userData.phone_number,
      totalReservations: 0, // Will be loaded from database
      pendingReservations: 0,
      approvedReservations: 0,
      completedStays: 0,
      memberSince: window.userData.memberSince || "2023",
      loyaltyLevel: window.userData.loyaltyLevel || "Regular",
    };
  }

  // If no session data, DON'T redirect here (dashboard.html handles auth check)
  // Return default values and let dashboard.html handle authentication
  return {
    name: "Guest",
    email: "",
    phone: "",
    totalReservations: 0,
    pendingReservations: 0,
    approvedReservations: 0,
    completedStays: 0,
    memberSince: "2023",
    loyaltyLevel: "Regular",
  };
}

// Initialize user data (will be null until dashboard.html completes session check)
let userData = loadUserData();

// Function to refresh userData after session check completes
function refreshUserData() {
  const newData = loadUserData();
  if (newData) {
    userData = newData;
  }
}

// Sample reservations data
const reservationsData = [
  {
    id: 1,
    dates: "December 20-23, 2025",
    roomType: "VIP Suite",
    guests: 2,
    nights: 3,
    status: "pending",
  },
  {
    id: 2,
    dates: "January 15-19, 2026",
    roomType: "Deluxe Ocean View",
    guests: 4,
    nights: 4,
    status: "approved",
  },
  {
    id: 3,
    dates: "November 10-13, 2025",
    roomType: "Family Suite",
    guests: 3,
    nights: 3,
    status: "completed",
  },
  {
    id: 4,
    dates: "September 5-8, 2025",
    roomType: "Premium Room",
    guests: 2,
    nights: 3,
    status: "completed",
  },
];

// Sample activity data
const activityData = [
  {
    icon: "fas fa-crown",
    title: "VIP Status Renewed",
    description: "Your VIP membership has been automatically renewed for 2026.",
    date: "3 days ago",
  },
  {
    icon: "fas fa-calendar-check",
    title: "Reservation Confirmed",
    description:
      "Your VIP Suite booking for December 20-23, 2025 has been approved.",
    date: "5 days ago",
  },
  {
    icon: "fas fa-gift",
    title: "Exclusive Offer Available",
    description: "Special 25% discount on spa services - VIP member exclusive!",
    date: "1 week ago",
  },
  {
    icon: "fas fa-star",
    title: "Stay Completed",
    description:
      "Thank you for your recent stay! Your feedback helps us improve.",
    date: "2 weeks ago",
  },
  {
    icon: "fas fa-cocktail",
    title: "Complimentary Welcome Drink",
    description:
      "Enjoy a free welcome cocktail on your next arrival - VIP perk!",
    date: "3 weeks ago",
  },
];

// ===== DOM ELEMENTS =====
// Updated for new popup system - old sidebar and nav-links no longer exist
const mobileToggle = document.querySelector(".mobile-toggle");

// ===== INITIALIZATION =====
document.addEventListener("DOMContentLoaded", async function () {
  initializeDashboard();
  updateUserData();
  setupEventListeners();
  setupFormValidation();

  // Load reviews FIRST so we know which reservations have been reviewed
  await loadUserReviews();

  // Pre-load reservations for booking history (will now have review info available)
  loadMyReservations();

  // Initialize booking history filters
  initBookingHistoryFilters();

  // Restore last viewed section from localStorage, or show dashboard by default
  const savedSection = localStorage.getItem("currentDashboardSection");
  const defaultSection = savedSection || "dashboard";

  // Hide all sections first
  document.querySelectorAll(".content-section").forEach((section) => {
    section.style.display = "none";
    section.classList.remove("active");
  });

  // Show the saved/default section
  const targetSectionId = defaultSection + "-section";
  const targetSection = document.getElementById(targetSectionId);

  if (targetSection) {
    targetSection.style.display = "block";
    targetSection.classList.add("active");

    // Update navigation to highlight the correct button
    updateActiveNavigation(defaultSection);

    // Load data for specific sections
    if (defaultSection === "bookings-history") {
      loadMyReservations();
    }
    if (defaultSection === "notifications") {
      loadUserNotifications();
    }
    if (defaultSection === "rebooking-requests") {
      loadRebookingRequests();
    }
  } else {
    // Fallback to dashboard if saved section not found
    const dashboardSection = document.getElementById("dashboard-section");
    if (dashboardSection) {
      dashboardSection.style.display = "block";
      dashboardSection.classList.add("active");
      updateActiveNavigation("dashboard");
    }
  }
});

// ===== DASHBOARD INITIALIZATION =====
function initializeDashboard() {
  // The popup system will handle section management
  // Set minimum date for date inputs to today
  const today = new Date().toISOString().split("T")[0];
  const checkInDate = document.getElementById("checkIn");
  const checkOutDate = document.getElementById("checkOut");

  if (checkInDate) checkInDate.min = today;
  if (checkOutDate) checkOutDate.min = today;

  // Console welcome message
}

// ===== USER DATA MANAGEMENT =====
function updateUserData() {
  // Update user name displays
  const userNameElements = document.querySelectorAll(
    "#userName, #welcomeUserName"
  );
  userNameElements.forEach((element) => {
    if (element) element.textContent = userData.name;
  });

  // NOTE: Stats are now loaded by dashboard.html's loadDashboardStats() function
  // which fetches real data from the server. We skip updating stats here to avoid
  // overwriting the actual data with zeros from the initial userData object.
  // The loadDashboardStats() function updates window.userData with the real values.

  // Update profile form
  updateProfileForm();

  // Show demo welcome message if it's a demo user
  checkAndShowDemoWelcome();
}

function checkAndShowDemoWelcome() {
  // Check if user is demo user based on email or if stored in localStorage
  const storedUser = localStorage.getItem("currentUser");
  if (storedUser) {
    try {
      const user = JSON.parse(storedUser);
      if (
        user.email === "demo@guest.com" &&
        !sessionStorage.getItem("demoWelcomeShown")
      ) {
        // Show demo welcome notification
        setTimeout(() => {
          showNotification(
            `🎉 Welcome to the Demo Dashboard, ${user.name}! You're logged in as a ${user.loyaltyLevel} member with ${user.totalReservations} total reservations. All data shown is sample data for demonstration purposes.`,
            "info",
            8000
          );
          sessionStorage.setItem("demoWelcomeShown", "true");
        }, 1500);
      }
    } catch (e) {}
  }
}

function updateStatistic(elementId, value) {
  const element = document.getElementById(elementId);
  if (element) {
    // Animate number counting
    animateCount(element, 0, value, 1000);
  }
}

function animateCount(element, start, end, duration) {
  const startTime = Date.now();
  const difference = end - start;

  function step() {
    const elapsed = Date.now() - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const current = Math.floor(start + difference * progress);

    element.textContent = current;

    if (progress < 1) {
      requestAnimationFrame(step);
    }
  }

  requestAnimationFrame(step);
}

function updateProfileForm() {
  // Update profile form fields with user data
  const firstNameField = document.getElementById("firstName");
  const lastNameField = document.getElementById("lastName");
  const emailField = document.getElementById("email");
  const phoneField = document.getElementById("phone");

  if (firstNameField && lastNameField) {
    const nameParts = userData.name.split(" ");
    firstNameField.value = nameParts[0] || "";
    lastNameField.value = nameParts.slice(1).join(" ") || "";
  }

  if (emailField) emailField.value = userData.email;
  if (phoneField) phoneField.value = userData.phone;
}

// ===== NAVIGATION MANAGEMENT =====
function setupEventListeners() {
  // Set up navigation button click events
  const navButtons = document.querySelectorAll(".nav-btn");
  navButtons.forEach((button, index) => {
    console.log(
      `🎯 Setting up button ${index}:`,
      button.getAttribute("data-section")
    );
    button.addEventListener("click", (e) => {
      e.preventDefault();
      const section = button.getAttribute("data-section");
      if (section) {
        // Remove active class from all buttons
        navButtons.forEach((btn) => btn.classList.remove("active"));
        // Add active to clicked button
        button.classList.add("active");

        // Hide all content sections
        document.querySelectorAll(".content-section").forEach((sec) => {
          sec.classList.remove("active");
          sec.style.display = "none";
        });

        // Show selected section
        const targetSection = document.getElementById(section + "-section");
        if (targetSection) {
          targetSection.classList.add("active");
          targetSection.style.display = "block";

          // Scroll to show content while keeping navbar visible
          const navSection = document.querySelector(".navigation-section");
          if (navSection) {
            const headerHeight = 80; // Account for fixed header
            const scrollTarget = navSection.offsetTop - headerHeight;
            window.scrollTo({ top: scrollTarget, behavior: "smooth" });
          }
        } else {
        }
      }
    });
  });

  // Set up close popup button
  const closePopupBtn = document.getElementById("closePopup");
  if (closePopupBtn) {
    closePopupBtn.addEventListener("click", closePopup);
  }

  // Mobile toggle (if it exists)
  if (mobileToggle) {
    mobileToggle.addEventListener("click", toggleSidebar);
  }

  // Handle window resize for responsive behavior
  window.addEventListener("resize", function () {
    // Handle any responsive behavior if needed
    if (window.innerWidth > 768) {
      // Reset any mobile-specific states
    }
  });

  // Check-in/Check-out date validation
  const checkInDate = document.getElementById("checkIn");
  const checkOutDate = document.getElementById("checkOut");

  if (checkInDate && checkOutDate) {
    checkInDate.addEventListener("change", function () {
      checkOutDate.min = this.value;
      if (checkOutDate.value && checkOutDate.value <= this.value) {
        checkOutDate.value = "";
      }
    });
  }

  // Promo code input - Enter key support
  const promoCodeInput = document.getElementById("promoCodeInput");
  if (promoCodeInput) {
    promoCodeInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        validatePromoCode();
      }
    });
  }

  // Add ESC key support to close popup
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closePopup();
    }
  });
}

function showSection(sectionId) {
  // Save current section to localStorage for persistence on refresh
  localStorage.setItem("currentDashboardSection", sectionId);

  // Use the new popup system instead of the old section switching
  if (window.popupManager) {
    window.popupManager.showSection(sectionId);
  } else {
    // Fallback: try to initialize popup manager and then show section
    setTimeout(() => {
      if (window.popupManager) {
        window.popupManager.showSection(sectionId);
      }
    }, 100);
  }

  // Load data for specific sections
  if (sectionId === "bookings-history") {
    loadMyReservations();
  }

  if (sectionId === "notifications" || sectionId === "notifications-section") {
    loadUserNotifications();
  }
}

function updateActiveNavigation(activeSection) {
  // Save current section to localStorage for persistence on refresh
  localStorage.setItem("currentDashboardSection", activeSection);

  // Updated for new navigation system
  // Remove active class from all nav buttons
  document.querySelectorAll(".nav-btn").forEach((btn) => {
    btn.classList.remove("active");
  });

  // Add active class to current nav button
  const activeBtn = document.querySelector(
    `.nav-btn[data-section="${activeSection}"]`
  );
  if (activeBtn) {
    activeBtn.classList.add("active");
  }
}

function toggleSidebar() {
  // No longer needed with new layout, but keeping for compatibility
}

function closeSidebar() {
  // No longer needed with new layout, but keeping for compatibility
}

// ===== FORM MANAGEMENT =====
function setupFormValidation() {
  // Reservation form
  const reservationForm = document.querySelector(".reservation-form");
  if (reservationForm) {
    reservationForm.addEventListener("submit", handleReservationSubmit);
  }

  // Profile form
  const profileForm = document.querySelector(".profile-form");
  if (profileForm) {
    profileForm.addEventListener("submit", handleProfileSubmit);
  }
}

// Setup form listeners for dynamically loaded content
function setupDynamicFormListeners() {
  // Setup any forms that were just loaded in the popup
  const popupArea = document.getElementById("popupContentArea");
  if (!popupArea) return;

  // Reservation form in popup
  const reservationForm = popupArea.querySelector(".reservation-form");
  if (reservationForm) {
    // Remove any existing listeners to avoid duplicates
    reservationForm.replaceWith(reservationForm.cloneNode(true));
    const newReservationForm = popupArea.querySelector(".reservation-form");
    newReservationForm.addEventListener("submit", handleReservationSubmit);

    // Setup date validation for reservation form
    const checkInDate = newReservationForm.querySelector("#checkIn");
    const checkOutDate = newReservationForm.querySelector("#checkOut");

    if (checkInDate && checkOutDate) {
      // Set minimum dates
      const today = new Date().toISOString().split("T")[0];
      checkInDate.min = today;
      checkOutDate.min = today;

      checkInDate.addEventListener("change", function () {
        checkOutDate.min = this.value;
        if (checkOutDate.value && checkOutDate.value <= this.value) {
          checkOutDate.value = "";
        }
      });
    }
  }

  // Profile form in popup
  const profileForm = popupArea.querySelector(".profile-form");
  if (profileForm) {
    // Remove any existing listeners to avoid duplicates
    profileForm.replaceWith(profileForm.cloneNode(true));
    const newProfileForm = popupArea.querySelector(".profile-form");
    newProfileForm.addEventListener("submit", handleProfileSubmit);
  }

  // Setup any buttons with onclick handlers
  const promoButtons = popupArea.querySelectorAll(".promo-btn");
  promoButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      showNotification("Promo code applied successfully!", "success");
    });
  });

  // Setup promo code input
  const promoCodeInput = popupArea.querySelector("#promoCodeInput");
  if (promoCodeInput) {
    promoCodeInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        validatePromoCode();
      }
    });
  }

  // Setup reservation action buttons
  const reservationButtons = popupArea.querySelectorAll(
    ".reservation-actions .btn"
  );
  reservationButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      const action = this.textContent.trim();
      showNotification(`${action} action triggered`, "info");
    });
  });
}

function handleReservationSubmit(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const reservationData = {
    checkIn: formData.get("checkIn"),
    checkOut: formData.get("checkOut"),
    guests: formData.get("guests"),
    roomType: formData.get("roomType"),
    specialRequests: formData.get("specialRequests"),
  };

  // Validate form data
  if (
    !reservationData.checkIn ||
    !reservationData.checkOut ||
    !reservationData.guests ||
    !reservationData.roomType
  ) {
    showNotification("Please fill in all required fields.", "error");
    return;
  }

  // Check if check-out date is after check-in date
  if (new Date(reservationData.checkOut) <= new Date(reservationData.checkIn)) {
    showNotification("Check-out date must be after check-in date.", "error");
    return;
  }

  // Simulate form submission
  const submitBtn = e.target.querySelector(".submit-btn");
  submitBtn.classList.add("loading");
  submitBtn.textContent = "Submitting...";

  setTimeout(() => {
    submitBtn.classList.remove("loading");
    submitBtn.innerHTML =
      '<i class="fas fa-calendar-plus"></i> Submit Reservation';
    showNotification(
      "Reservation submitted successfully! We will review and confirm shortly.",
      "success"
    );
    e.target.reset();

    // Update statistics
    userData.totalReservations++;
    userData.pendingReservations++;
    updateStatistic("totalReservations", userData.totalReservations);
    updateStatistic("pendingReservations", userData.pendingReservations);
  }, 2000);
}

function handleProfileSubmit(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const profileData = {
    firstName: formData.get("firstName"),
    lastName: formData.get("lastName"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    address: formData.get("address"),
    preferences: formData.get("preferences"),
  };

  // Validate required fields
  if (
    !profileData.firstName ||
    !profileData.lastName ||
    !profileData.email ||
    !profileData.phone
  ) {
    showNotification("Please fill in all required fields.", "error");
    return;
  }

  // Simulate form submission
  const submitBtn = e.target.querySelector(".submit-btn");
  submitBtn.classList.add("loading");
  submitBtn.textContent = "Updating...";

  setTimeout(() => {
    submitBtn.classList.remove("loading");
    submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Profile';

    // Update user data
    userData.name = `${profileData.firstName} ${profileData.lastName}`;
    userData.email = profileData.email;
    userData.phone = profileData.phone;

    // Update UI
    updateUserData();
    showNotification("Profile updated successfully!", "success");
  }, 1500);
}

// ===== UTILITY FUNCTIONS =====
function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
    <div class="notification-content">
      <i class="fas ${getNotificationIcon(type)}"></i>
      <span>${message}</span>
    </div>
    <button class="notification-close" onclick="this.parentElement.remove()">
      <i class="fas fa-times"></i>
    </button>
  `;

  // Add styles
  notification.style.cssText = `
    position: fixed;
    top: 100px;
    right: 20px;
    background: ${getNotificationColor(type)};
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-width: 300px;
    max-width: 400px;
    animation: slideInRight 0.3s ease-out;
  `;

  // Add to DOM
  document.body.appendChild(notification);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentElement) {
      notification.style.animation = "slideOutRight 0.3s ease-in";
      setTimeout(() => notification.remove(), 300);
    }
  }, 5000);
}

function getNotificationIcon(type) {
  switch (type) {
    case "success":
      return "fa-check-circle";
    case "error":
      return "fa-exclamation-circle";
    case "warning":
      return "fa-exclamation-triangle";
    default:
      return "fa-info-circle";
  }
}

function getNotificationColor(type) {
  switch (type) {
    case "success":
      return "linear-gradient(135deg, #66bb6a, #43a047)";
    case "error":
      return "linear-gradient(135deg, #ff6b6b, #ee5a24)";
    case "warning":
      return "linear-gradient(135deg, #ffa726, #ff8a65)";
    default:
      return "linear-gradient(135deg, #667eea, #764ba2)";
  }
}

function logout() {
  if (confirm("Are you sure you want to logout?")) {
    showNotification("Logging out...", "info");
    setTimeout(() => {
      // Redirect to login page
      window.location.href = "index.html";
    }, 1000);
  }
}

// ===== RESERVATION MANAGEMENT (Legacy - now replaced by enhanced functions in BOOKING HISTORY section) =====
// Note: viewReservationDetails, cancelReservation are now defined in the Enhanced Booking History section

function modifyReservation(reservationId) {
  showNotification("Redirecting to modification form...", "info");
  showSection("make-reservation");
  updateActiveNavigation("make-reservation");
}

function reviewStay(reservationId) {
  // Now redirects to the enhanced write review function
  if (typeof writeReviewForReservation === "function") {
    writeReviewForReservation(reservationId);
  } else {
    showNotification("Review form will be available soon!", "info");
  }
}

// ===== GLOBAL FUNCTIONS FOR ONCLICK HANDLERS =====
window.logout = logout;
window.showSection = showSection;
window.toggleSidebar = toggleSidebar;
window.modifyReservation = modifyReservation;
window.reviewStay = reviewStay;

// ===== ADD NOTIFICATION ANIMATIONS TO CSS =====
const notificationStyles = document.createElement("style");
notificationStyles.textContent = `
  @keyframes slideInRight {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOutRight {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
  
  .notification-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .notification-close {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0;
    font-size: 0.9rem;
    opacity: 0.8;
    transition: opacity 0.3s ease;
  }
  
  .notification-close:hover {
    opacity: 1;
  }
`;
document.head.appendChild(notificationStyles);

// ===== PERFORMANCE MONITORING =====
// Log performance metrics
window.addEventListener("load", function () {
  setTimeout(() => {
    const perfData = performance.getEntriesByType("navigation")[0];
    console.log(
      `%c⚡ Page Load Time: ${Math.round(
        perfData.loadEventEnd - perfData.loadEventStart
      )}ms`,
      "color: #667eea;"
    );
  }, 100);
});

// ===== ERROR HANDLING =====
window.addEventListener("error", function (e) {
  // Don't show generic error for user-handled errors
  if (
    e.message &&
    (e.message.includes("payWithPayMongo") ||
      e.message.includes("payFullBalanceWithPayMongo") ||
      e.message.includes("proceedWithOnlinePayment") ||
      e.message.includes("openCancelModal") ||
      e.message.includes("openRebookingModal") ||
      e.message.includes("openPaymentUploadModal") ||
      e.message.includes("viewReservationDetails") ||
      e.message.includes("showReservationDetailsModal") ||
      e.message.includes("closeReservationDetailsModal") ||
      e.message.includes("closePayRemainingBalanceModal") ||
      e.message.includes("bookAgainFromReservation") ||
      e.message.includes("selectBookingType") ||
      e.message.includes("writeReviewForReservation"))
  ) {
    // Let the function handle its own errors
    return;
  }
  console.error("Global error caught:", e);
  console.error("Error details:", e.message, e.filename, e.lineno, e.colno);
  showNotification("An error occurred. Please refresh the page.", "error");
});

// Handle unhandled promise rejections
window.addEventListener("unhandledrejection", function (e) {
  console.error("Unhandled promise rejection:", e.reason);
  // Don't show generic error for these - let functions handle their own errors
  e.preventDefault();
});

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener("keydown", function (e) {
  // Alt + number keys for quick navigation
  if (e.altKey && !e.ctrlKey && !e.shiftKey) {
    switch (e.key) {
      case "1":
        e.preventDefault();
        showSection("dashboard");
        updateActiveNavigation("dashboard");
        break;
      case "2":
        e.preventDefault();
        showSection("make-reservation");
        updateActiveNavigation("make-reservation");
        break;
      case "3":
        e.preventDefault();
        showSection("my-reservations");
        updateActiveNavigation("my-reservations");
        break;
      case "4":
        e.preventDefault();
        showSection("profile");
        updateActiveNavigation("profile");
        break;
    }
  }

  // ESC to close sidebar on mobile
  if (e.key === "Escape" && window.innerWidth <= 768) {
    closeSidebar();
  }
});

// Show keyboard shortcuts info in console
console.log("ESC: Close sidebar (mobile)");

// ===== PROMOTIONS DATA =====
const promoData = {
  isRegularCustomer: true,
  loyaltyLevel: "Gold",
  availablePromos: [
    {
      code: "EARLYBIRD25",
      title: "Early Bird Special",
      discount: 25,
      description: "Book 30 days in advance and save big!",
      validUntil: "Dec 31, 2025",
      active: true,
    },
    {
      code: "WEEKEND20",
      title: "Weekend Escape",
      discount: 20,
      description: "Perfect for weekend warriors!",
      validUntil: "Ongoing",
      active: true,
    },
    {
      code: "FAMILY30",
      title: "Family Package",
      discount: 30,
      description: "Bring the whole family!",
      validUntil: "Dec 31, 2025",
      active: true,
    },
    {
      code: "VIPLOYALTY35",
      title: "VIP Loyalty Bonus",
      discount: 35,
      description: "Exclusive for regular customers!",
      validUntil: "Limited time",
      active: true,
      vipOnly: true,
    },
    {
      code: "HOLIDAY40",
      title: "Holiday Season",
      discount: 40,
      description: "Celebrate the holidays with us!",
      validUntil: "Jan 15, 2026",
      active: true,
    },
    {
      code: "ROMANCE45",
      title: "Romantic Getaway",
      discount: 45,
      description: "Perfect for couples!",
      validUntil: "Feb 14, 2026",
      active: true,
    },
  ],
  appliedPromos: [],
};

// ===== PROMOTIONS MANAGEMENT =====
function applyPromo(promoCode) {
  const promo = promoData.availablePromos.find((p) => p.code === promoCode);

  if (!promo) {
    showNotification("Invalid promo code!", "error");
    return;
  }

  if (!promo.active) {
    showNotification("This promotion is no longer active.", "error");
    return;
  }

  if (promo.vipOnly && !promoData.isRegularCustomer) {
    showNotification(
      "This promotion is only available for VIP members.",
      "error"
    );
    return;
  }

  // Check if promo is already applied
  if (promoData.appliedPromos.some((p) => p.code === promoCode)) {
    showNotification("This promotion is already applied!", "warning");
    return;
  }

  // Apply the promo
  promoData.appliedPromos.push(promo);
  showNotification(
    `🎉 ${promo.title} applied! You saved ${promo.discount}%`,
    "success"
  );

  // Update UI to show applied promo
  updateAppliedPromos();

  // Redirect to make reservation page with promo pre-applied
  setTimeout(() => {
    showSection("make-reservation");
    updateActiveNavigation("make-reservation");

    // Add promo info to the form (you could enhance this further)
    const form = document.querySelector(".reservation-form");
    if (form) {
      let promoInfo = form.querySelector(".applied-promo-info");
      if (!promoInfo) {
        promoInfo = document.createElement("div");
        promoInfo.className = "applied-promo-info";
        promoInfo.style.cssText = `
          background: linear-gradient(135deg, #66bb6a, #43a047);
          color: white;
          padding: 1rem;
          border-radius: 8px;
          margin-bottom: 1rem;
          display: flex;
          align-items: center;
          gap: 0.5rem;
        `;
        form.insertBefore(promoInfo, form.firstChild);
      }
      promoInfo.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>Promo Applied: ${promo.title} (${promo.discount}% OFF)</span>
        <button onclick="removeAppliedPromo('${promo.code}')" style="
          background: none;
          border: none;
          color: white;
          margin-left: auto;
          cursor: pointer;
          font-size: 1rem;
        ">
          <i class="fas fa-times"></i>
        </button>
      `;
    }
  }, 1000);
}

function validatePromoCode() {
  const input = document.getElementById("promoCodeInput");
  const code = input.value.trim().toUpperCase();

  if (!code) {
    showNotification("Please enter a promo code.", "warning");
    return;
  }

  const promo = promoData.availablePromos.find((p) => p.code === code);

  if (promo) {
    input.value = "";
    applyPromo(code);
  } else {
    showNotification(
      "Invalid promo code. Please check and try again.",
      "error"
    );
    input.value = "";
  }
}

function removeAppliedPromo(promoCode) {
  const index = promoData.appliedPromos.findIndex((p) => p.code === promoCode);
  if (index > -1) {
    const promo = promoData.appliedPromos[index];
    promoData.appliedPromos.splice(index, 1);
    showNotification(`${promo.title} removed.`, "info");

    // Remove from UI
    const promoInfo = document.querySelector(".applied-promo-info");
    if (promoInfo) {
      promoInfo.remove();
    }

    updateAppliedPromos();
  }
}

function updateAppliedPromos() {
  // This function could update a display of currently applied promos
  // For now, we'll just log it
}

function initializePromotions() {
  // Check if user is a regular customer and update loyalty status
  if (promoData.isRegularCustomer) {
    const loyaltyBadge = document.querySelector(".loyalty-badge");
    if (loyaltyBadge) {
      // Remove moving animation - keep badge static
      // loyaltyBadge.style.animation = "shimmer 3s linear infinite";
    }

    // Show VIP-only promotions
    const vipPromos = document.querySelectorAll(".promo-card.exclusive");
    vipPromos.forEach((card) => {
      card.style.display = "block";
    });
  } else {
    // Hide VIP-only promotions for non-regular customers
    const vipPromos = document.querySelectorAll(".promo-card.exclusive");
    vipPromos.forEach((card) => {
      card.style.display = "none";
    });

    // Hide loyalty badge
    const loyaltyStatus = document.getElementById("loyaltyStatus");
    if (loyaltyStatus) {
      loyaltyStatus.style.display = "none";
    }
  }
}

// ===== GLOBAL FUNCTIONS FOR PROMO MANAGEMENT =====
window.applyPromo = applyPromo;
window.validatePromoCode = validatePromoCode;
window.removeAppliedPromo = removeAppliedPromo;

// ===== UPDATE INITIALIZATION =====
document.addEventListener("DOMContentLoaded", function () {
  initializeDashboard();
  updateUserData();
  setupEventListeners();
  setupFormValidation();
  initializePromotions(); // Add this line
});

// ===== RESORT GALLERY FUNCTIONALITY =====
class ResortGallery {
  constructor() {
    this.currentSlide = 0;
    this.slides = [];
    this.autoPlayInterval = null;
    this.touchStartX = 0;
    this.touchEndX = 0;
    this.isTransitioning = false;
    this.isDragging = false;

    this.init();
  }

  init() {
    this.galleryWrapper = document.getElementById("galleryWrapper");
    this.prevBtn = document.getElementById("prevBtn");
    this.nextBtn = document.getElementById("nextBtn");
    this.indicators = document.getElementById("galleryIndicators");

    if (!this.galleryWrapper) {
      return;
    }

    this.slides = this.galleryWrapper.querySelectorAll(".gallery-slide");
    this.totalSlides = this.slides.length;

    if (this.totalSlides === 0) {
      return;
    }

    // Force display first slide immediately
    this.showSlide(0);
    this.setupEventListeners();
    this.startAutoPlay();
  }

  showSlide(index) {
    if (!this.galleryWrapper || this.totalSlides === 0) {
      return;
    }

    // Debug dimensions
    const wrapperWidth = this.galleryWrapper.offsetWidth;
    const wrapperScrollWidth = this.galleryWrapper.scrollWidth;
    this.currentSlide = index;
    const translateX = -this.currentSlide * 100;

    console.log(`   Transform value: translateX(${translateX}%)`);

    // Remove any inline transition first to prevent conflicts
    this.galleryWrapper.style.transition = "none";

    // Force browser reflow
    void this.galleryWrapper.offsetWidth;

    // Re-enable transition
    this.galleryWrapper.style.transition =
      "transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)";

    // Apply transform
    this.galleryWrapper.style.transform = `translateX(${translateX}%)`;

    // Verify transform was applied
    setTimeout(() => {
      const actualTransform = window.getComputedStyle(
        this.galleryWrapper
      ).transform;
      const wrapperRect = this.galleryWrapper.getBoundingClientRect();
      // Check each slide's visibility
      const slides = this.galleryWrapper.querySelectorAll(".gallery-slide");
      slides.forEach((slide, i) => {
        const slideRect = slide.getBoundingClientRect();
        const isVisible = slideRect.left >= 0 && slideRect.left < wrapperWidth;
        console.log(
          `   Slide ${i}: left=${slideRect.left.toFixed(0)}px ${
            isVisible ? "✅ VISIBLE" : "❌ hidden"
          }`
        );
      });

      // Check if transform actually moved
      if (
        actualTransform === "none" ||
        actualTransform === "matrix(1, 0, 0, 1, 0, 0)"
      ) {
        console.log("Wrapper computed styles:", {
          display: window.getComputedStyle(this.galleryWrapper).display,
          position: window.getComputedStyle(this.galleryWrapper).position,
          overflow: window.getComputedStyle(this.galleryWrapper).overflow,
          width: window.getComputedStyle(this.galleryWrapper).width,
        });
      }
    }, 100);

    this.updateIndicators();
  }

  setupEventListeners() {
    // Button controls
    if (this.prevBtn) {
      console.log("Previous button computed style:", {
        zIndex: window.getComputedStyle(this.prevBtn).zIndex,
        pointerEvents: window.getComputedStyle(this.prevBtn).pointerEvents,
        display: window.getComputedStyle(this.prevBtn).display,
      });

      this.prevBtn.addEventListener(
        "click",
        (e) => {
          e.preventDefault();
          e.stopPropagation();
          this.prevSlide();
        },
        true
      );

      // Also add mousedown for testing
      this.prevBtn.addEventListener("mousedown", () => {});
    } else {
    }

    if (this.nextBtn) {
      console.log("Next button computed style:", {
        zIndex: window.getComputedStyle(this.nextBtn).zIndex,
        pointerEvents: window.getComputedStyle(this.nextBtn).pointerEvents,
        display: window.getComputedStyle(this.nextBtn).display,
      });

      this.nextBtn.addEventListener(
        "click",
        (e) => {
          e.preventDefault();
          e.stopPropagation();
          this.nextSlide();
        },
        true
      );

      // Also add mousedown for testing
      this.nextBtn.addEventListener("mousedown", () => {});
    } else {
    }

    // Indicator controls
    if (this.indicators) {
      this.indicators.addEventListener("click", (e) => {
        if (e.target.classList.contains("indicator")) {
          const slideIndex = parseInt(e.target.dataset.slide);
          this.goToSlide(slideIndex);
        }
      });
    }

    // Touch/swipe events
    if (this.galleryWrapper) {
      this.galleryWrapper.addEventListener(
        "touchstart",
        (e) => this.handleTouchStart(e),
        { passive: true }
      );
      this.galleryWrapper.addEventListener(
        "touchmove",
        (e) => this.handleTouchMove(e),
        { passive: true }
      );
      this.galleryWrapper.addEventListener(
        "touchend",
        (e) => this.handleTouchEnd(e),
        { passive: true }
      );

      // Mouse events for desktop dragging
      this.galleryWrapper.addEventListener("mousedown", (e) =>
        this.handleMouseDown(e)
      );
      this.galleryWrapper.addEventListener("mousemove", (e) =>
        this.handleMouseMove(e)
      );
      this.galleryWrapper.addEventListener("mouseup", (e) =>
        this.handleMouseUp(e)
      );
      this.galleryWrapper.addEventListener("mouseleave", (e) =>
        this.handleMouseUp(e)
      );

      // Prevent drag on images
      this.galleryWrapper.addEventListener("dragstart", (e) =>
        e.preventDefault()
      );
    }

    // Pause auto-play on hover
    const galleryContainer = document.querySelector(".gallery-container");
    if (galleryContainer) {
      galleryContainer.addEventListener("mouseenter", () => {
        this.pauseAutoPlay();
      });
      galleryContainer.addEventListener("mouseleave", () => {
        this.startAutoPlay();
      });
    }

    // Keyboard navigation
    document.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") {
        this.prevSlide();
      }
      if (e.key === "ArrowRight") {
        this.nextSlide();
      }
    });
    // Debug: Log all setup complete
  }

  handleTouchStart(e) {
    this.touchStartX = e.touches[0].clientX;
    this.pauseAutoPlay();
  }

  handleTouchMove(e) {
    this.touchEndX = e.touches[0].clientX;
  }

  handleTouchEnd(e) {
    const swipeThreshold = 50;
    const swipeDistance = this.touchStartX - this.touchEndX;

    if (Math.abs(swipeDistance) > swipeThreshold) {
      if (swipeDistance > 0) {
        this.nextSlide();
      } else {
        this.prevSlide();
      }
    }

    this.startAutoPlay();
  }

  handleMouseDown(e) {
    this.isDragging = true;
    this.touchStartX = e.clientX;
    this.pauseAutoPlay();
    this.galleryWrapper.style.cursor = "grabbing";
  }

  handleMouseMove(e) {
    if (!this.isDragging) return;
    this.touchEndX = e.clientX;
  }

  handleMouseUp(e) {
    if (!this.isDragging) return;

    this.isDragging = false;
    this.galleryWrapper.style.cursor = "grab";

    const swipeThreshold = 50;
    const swipeDistance = this.touchStartX - this.touchEndX;

    if (Math.abs(swipeDistance) > swipeThreshold) {
      if (swipeDistance > 0) {
        this.nextSlide();
      } else {
        this.prevSlide();
      }
    }

    this.startAutoPlay();
  }

  goToSlide(index) {
    if (!this.galleryWrapper) {
      return;
    }

    // Prevent going to the same slide
    if (index === this.currentSlide) return;

    this.showSlide(index);
  }

  nextSlide() {
    const nextIndex = (this.currentSlide + 1) % this.totalSlides;
    this.goToSlide(nextIndex);
  }

  prevSlide() {
    const prevIndex =
      (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
    this.goToSlide(prevIndex);
  }

  updateIndicators() {
    if (!this.indicators) return;

    const indicators = this.indicators.querySelectorAll(".indicator");
    indicators.forEach((indicator, index) => {
      indicator.classList.toggle("active", index === this.currentSlide);
    });
  }

  startAutoPlay() {
    console.log("▶️ Starting auto-play (5 seconds per slide)");
    this.pauseAutoPlay();
    this.autoPlayInterval = setInterval(() => {
      this.nextSlide();
    }, 5000);
  }

  pauseAutoPlay() {
    if (this.autoPlayInterval) {
      clearInterval(this.autoPlayInterval);
      this.autoPlayInterval = null;
    }
  }

  destroy() {
    this.pauseAutoPlay();
    // Remove event listeners if needed
  }
}

// Backup initialization - try again after page fully loads
window.addEventListener("load", () => {
  if (!window.resortGallery) {
    setTimeout(initGallery, 100);
  }
});

// Manual test function - call testGallery() in console to test
window.testGallery = function () {
  const wrapper = document.getElementById("galleryWrapper");
  const slides = document.querySelectorAll(".gallery-slide");

  if (wrapper && slides.length > 0) {
    wrapper.style.transition = "transform 0.6s ease";
    wrapper.style.transform = "translateX(-100%)";
    console.log("✅ Transform applied: translateX(-100%)");

    setTimeout(() => {
      wrapper.style.transform = "translateX(-200%)";
      console.log("✅ Transform applied: translateX(-200%)");

      setTimeout(() => {
        wrapper.style.transform = "translateX(0%)";
        console.log("✅ Transform applied: translateX(0%)");
      }, 2000);
    }, 2000);
  } else {
  }
};

// Initialize gallery when DOM is loaded
function initGallery() {
  // Check if gallery wrapper exists
  const wrapper = document.getElementById("galleryWrapper");
  if (!wrapper) {
    setTimeout(initGallery, 500);
    return;
  }

  window.resortGallery = new ResortGallery();
}

// Gallery debug function - available immediately
window.testGalleryButtons = function () {
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");

  if (!prevBtn || !nextBtn) {
    return;
  }

  // Check if buttons are visible and clickable
  const prevRect = prevBtn.getBoundingClientRect();
  const nextRect = nextBtn.getBoundingClientRect();

  // Check z-index and pointer events
  const prevStyle = window.getComputedStyle(prevBtn);
  const nextStyle = window.getComputedStyle(nextBtn);

  // Check what element is on top
  const nextCenter = {
    x: nextRect.left + nextRect.width / 2,
    y: nextRect.top + nextRect.height / 2,
  };
  const elementAtPoint = document.elementFromPoint(nextCenter.x, nextCenter.y);
  console.log(
    "Is it the button or its child?",
    elementAtPoint === nextBtn || nextBtn.contains(elementAtPoint)
  );

  // Try to click programmatically
  nextBtn.click();
};

// Initialize immediately or wait for DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(initGallery, 300);
  });
} else {
  setTimeout(initGallery, 300);
}

// ===== POPUP CONTENT SYSTEM =====
class PopupContentManager {
  constructor() {
    this.popupArea = null;
    this.popupTitle = null;
    this.popupBody = null;
    this.closeBtn = null;
    this.navButtons = [];
    this.currentSection = null;

    this.init();
  }

  init() {
    this.popupArea = document.getElementById("popupContentArea");
    this.popupTitle = document.getElementById("popupTitle");
    this.popupBody = document.getElementById("popupBody");
    this.closeBtn = document.getElementById("closePopup");
    this.navButtons = document.querySelectorAll(".nav-btn");

    if (!this.popupArea) {
      return;
    }

    this.setupEventListeners();

    // Load saved section from localStorage or default to dashboard
    const savedSection =
      localStorage.getItem("currentDashboardSection") || "dashboard";
    this.loadSectionContent(savedSection);
    this.setActiveNavButtonBySection(savedSection);
  }

  setActiveNavButtonBySection(sectionName) {
    this.navButtons.forEach((btn) => {
      if (btn.dataset.section === sectionName) {
        this.setActiveNavButton(btn);
      }
    });
  }

  setupEventListeners() {
    // Navigation button clicks
    this.navButtons.forEach((btn, index) => {
      btn.addEventListener("click", (e) => {
        const section = btn.dataset.section;
        this.showSection(section);
        this.setActiveNavButton(btn);
      });
    });

    // Close button
    if (this.closeBtn) {
      this.closeBtn.addEventListener("click", () => {
        this.hidePopup();
      });
    } else {
    }

    // Close on overlay click (outside popup content)
    document.addEventListener("click", (e) => {
      if (e.target === this.popupArea) {
        this.hidePopup();
      }
    });

    // Close on Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.popupArea.classList.contains("active")) {
        this.hidePopup();
      }
    });
  }

  showSection(sectionName) {
    // Save current section to localStorage for persistence on refresh
    localStorage.setItem("currentDashboardSection", sectionName);

    this.loadSectionContent(sectionName);
    this.showPopup();
    this.currentSection = sectionName;

    // Scroll to show content while keeping navbar visible
    const navSection = document.querySelector(".navigation-section");
    if (navSection) {
      const headerHeight = 80; // Account for fixed header
      const scrollTarget = navSection.offsetTop - headerHeight;
      window.scrollTo({ top: scrollTarget, behavior: "smooth" });
    }
    if (this.popupBody) {
      this.popupBody.scrollTop = 0;
    }
    if (this.popupArea) {
      this.popupArea.scrollTop = 0;
    }
  }

  loadSectionContent(sectionName) {
    const contentElement = document.getElementById(`${sectionName}-content`);
    const titles = {
      dashboard: "Dashboard Overview",
      "make-reservation": "Make a Reservation",
      "my-reservations": "My Reservations",
      promotions: "Exclusive Promotions",
      profile: "Profile Settings",
    };

    if (this.popupTitle) {
      this.popupTitle.textContent = titles[sectionName] || "AR Homes Resort";
    }

    if (this.popupBody && contentElement) {
      this.popupBody.innerHTML = contentElement.innerHTML;

      // Re-initialize any interactive elements if needed
      this.reinitializeContent(sectionName);
    }
  }

  reinitializeContent(sectionName) {
    // Reinitialize form handlers, etc. based on section
    switch (sectionName) {
      case "make-reservation":
        this.initReservationForm();
        break;
      case "profile":
        this.initProfileForm();
        break;
      case "dashboard":
        this.updateDashboardStats();
        break;
    }
  }

  initReservationForm() {
    const form = this.popupBody.querySelector(".reservation-form");
    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        this.handleReservationSubmit(new FormData(form));
      });
    }
  }

  initProfileForm() {
    const form = this.popupBody.querySelector(".profile-form");
    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        this.handleProfileUpdate(new FormData(form));
      });
    }
  }

  updateDashboardStats() {
    // Update stats with current user data
    const userData = loadUserData();

    const statsElements = {
      totalReservations: this.popupBody.querySelector("#totalReservations"),
      pendingReservations: this.popupBody.querySelector("#pendingReservations"),
      approvedReservations: this.popupBody.querySelector(
        "#approvedReservations"
      ),
      completedStays: this.popupBody.querySelector("#completedStays"),
    };

    if (statsElements.totalReservations) {
      statsElements.totalReservations.textContent = userData.totalReservations;
    }
    if (statsElements.pendingReservations) {
      statsElements.pendingReservations.textContent =
        userData.pendingReservations;
    }
    if (statsElements.approvedReservations) {
      statsElements.approvedReservations.textContent =
        userData.approvedReservations;
    }
    if (statsElements.completedStays) {
      statsElements.completedStays.textContent = userData.completedStays;
    }
  }

  handleReservationSubmit(formData) {
    // Handle reservation form submission
    console.log("Reservation submitted:", Object.fromEntries(formData));

    // Show success message
    this.showSuccessMessage("Reservation submitted successfully!");

    // Optionally close popup after success
    setTimeout(() => {
      this.hidePopup();
    }, 2000);
  }

  handleProfileUpdate(formData) {
    // Handle profile update
    console.log("Profile updated:", Object.fromEntries(formData));

    // Update user name in header
    const userName = formData.get("firstName") + " " + formData.get("lastName");
    const userNameElements = document.querySelectorAll(
      "#userName, #welcomeUserName"
    );
    userNameElements.forEach((el) => {
      if (el) el.textContent = userName;
    });

    this.showSuccessMessage("Profile updated successfully!");
  }

  showSuccessMessage(message) {
    // Create and show success notification
    const notification = document.createElement("div");
    notification.className = "success-notification";
    notification.innerHTML = `
      <i class="fas fa-check-circle"></i>
      <span>${message}</span>
    `;

    // Add notification styles
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #4CAF50, #45a049);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      z-index: 9999;
      animation: slideInRight 0.3s ease;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    `;

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
      notification.style.animation = "slideOutRight 0.3s ease";
      setTimeout(() => {
        document.body.removeChild(notification);
      }, 300);
    }, 3000);
  }

  showPopup() {
    if (this.popupArea) {
      this.popupArea.classList.add("active");
      document.body.style.overflow = "hidden";
    }
  }

  hidePopup() {
    if (this.popupArea) {
      this.popupArea.classList.remove("active");
      document.body.style.overflow = "";
      this.resetActiveNavButton();
    }
  }

  setActiveNavButton(activeBtn) {
    this.navButtons.forEach((btn) => btn.classList.remove("active"));
    activeBtn.classList.add("active");
  }

  resetActiveNavButton() {
    this.navButtons.forEach((btn) => btn.classList.remove("active"));
    // Optionally set a default active button
    const dashboardBtn = document.querySelector('[data-section="dashboard"]');
    if (dashboardBtn) {
      dashboardBtn.classList.add("active");
    }
  }
}

// Note: PopupContentManager removed as navigation is now handled directly
// Navigation is managed by setupEventListeners() function

// ===== QUICK ACTIONS FUNCTIONS =====
function quickAction(action) {
  switch (action) {
    case "book":
      showSection("my-reservations");
      updateActiveNavigation("my-reservations");
      showNotification("Opening booking page...", "info");
      break;
    case "view":
      showSection("bookings-history");
      updateActiveNavigation("bookings-history");
      showNotification("Loading your reservations...", "info");
      break;
    case "promo":
      showNotification("Promotions section is currently unavailable.", "info");
      break;
    case "support":
      showNotification("Customer support: +63 917 123 4567", "info", 5000);
      break;
  }
}

// ===== BOOKING HISTORY FUNCTIONS =====
function viewBookingDetails(bookingId) {
  showNotification(`Loading details for booking ${bookingId}...`, "info");
  // In production, this would fetch and display full booking details
}

function leaveReview(bookingId) {
  // Open the write review modal for this specific booking
  if (typeof writeReviewForReservation === "function") {
    writeReviewForReservation(bookingId);
  } else {
    showSection("reviews");
    updateActiveNavigation("reviews");
    showNotification("Write a review for your stay!", "success");
  }
}

function bookAgain(packageType) {
  showSection("my-reservations");
  updateActiveNavigation("my-reservations");
  showNotification(`Pre-filling ${packageType} package...`, "info");
  // In production, this would pre-fill the booking form
}

function modifyBooking(bookingId) {
  showNotification(`Opening modification form for ${bookingId}...`, "info");
  // In production, this would open an editable booking form
}

function cancelBooking(bookingId) {
  if (
    confirm(
      "Are you sure you want to cancel this booking? This action cannot be undone."
    )
  ) {
    showNotification(`Cancelling booking ${bookingId}...`, "warning");
    // In production, this would call an API to cancel the booking
    setTimeout(() => {
      showNotification("Booking cancelled successfully.", "success");
    }, 1500);
  }
}

function completePayment(reservationId, paymentType = "downpayment") {
  // Open payment upload modal
  showPaymentUploadModal(reservationId, paymentType);
}

// ===== LOAD MY RESERVATIONS =====
async function loadMyReservations() {
  try {
    const response = await fetch("user/get_my_reservations.php");
    const result = await response.json();

    if (result.success) {
      // Store in global variable for filtering/sorting
      loadedReservations = result.reservations;
      displayReservations(result.reservations);
      updateDashboardCounts(result.reservations);
    } else {
      showNotification(
        result.message || "Failed to load reservations",
        "error"
      );
    }
  } catch (error) {
    console.error("Error loading reservations:", error);
    showNotification("Failed to load reservations", "error");
  }
}

function updateDashboardCounts(reservations) {
  // Helper function to check if reservation is effectively completed
  // (either has completed status OR is fully paid and past check-out date)
  function isEffectivelyCompleted(r) {
    // Check actual completed statuses
    if (r.status === "completed" || r.status === "checked_out") {
      return true;
    }

    // Check if fully paid and past check-out date
    const isFullyPaid =
      r.full_payment_verified == 1 ||
      (r.downpayment_verified == 1 && r.full_payment_verified == 1);

    if (isFullyPaid && r.check_out_date) {
      const now = new Date();
      const checkOutDate = new Date(r.check_out_date);

      // If check-out time is provided, use it; otherwise default to end of day
      if (r.check_out_time) {
        const timeParts = r.check_out_time.split(":");
        checkOutDate.setHours(
          parseInt(timeParts[0], 10),
          parseInt(timeParts[1], 10),
          0,
          0
        );
      } else {
        checkOutDate.setHours(23, 59, 59, 999);
      }

      // If current date/time is past check-out, consider completed
      if (now > checkOutDate) {
        return true;
      }
    }

    return false;
  }

  // Define status categories - handles ALL status values for old AND new accounts
  const pendingStatuses = [
    "pending",
    "pending_payment",
    "pending_confirmation",
    "expired",
  ];
  const approvedStatuses = ["approved", "confirmed", "checked_in", "rebooked"];

  const total = reservations.length;
  const pending = reservations.filter((r) =>
    pendingStatuses.includes(r.status)
  ).length;

  // Count completed stays using same logic as reservation history
  const completed = reservations.filter((r) =>
    isEffectivelyCompleted(r)
  ).length;

  // Approved = confirmed but NOT yet completed (exclude effectively completed)
  const confirmed = reservations.filter(
    (r) => approvedStatuses.includes(r.status) && !isEffectivelyCompleted(r)
  ).length;

  const totalEl = document.getElementById("totalReservations");
  const pendingEl = document.getElementById("pendingReservations");
  const approvedEl = document.getElementById("approvedReservations");
  const completedEl = document.getElementById("completedStays");

  if (totalEl) totalEl.textContent = total;
  if (pendingEl) pendingEl.textContent = pending;
  if (approvedEl) approvedEl.textContent = confirmed;
  if (completedEl) completedEl.textContent = completed;

  // Also update userData for consistency
  if (window.userData) {
    window.userData.totalReservations = total;
    window.userData.pendingReservations = pending;
    window.userData.approvedReservations = confirmed;
    window.userData.completedStays = completed;
  }
}

// Note: displayReservations is defined in the ENHANCED BOOKING HISTORY FUNCTIONS section below
// Note: createReservationCard is replaced by createEnhancedReservationCard in the ENHANCED section

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function getPaymentStatusHTML(r) {
  let html =
    '<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px; font-size: 13px;">';

  if (r.status === "pending_payment") {
    html +=
      '<div style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> <strong>Payment Required</strong></div>';
    html +=
      '<div style="margin-top: 5px; color: #6c757d;">Please upload downpayment proof to proceed</div>';
  } else if (r.status === "pending_confirmation") {
    html +=
      '<div style="color: #ffc107;"><i class="fas fa-clock"></i> <strong>Payment Submitted</strong></div>';
    html +=
      '<div style="margin-top: 5px; color: #6c757d;">Waiting for admin verification</div>';
  } else if (r.status === "confirmed") {
    html +=
      '<div style="color: #28a745;"><i class="fas fa-check-circle"></i> <strong>Confirmed</strong></div>';
    if (!r.full_payment_paid) {
      const remainingBalance =
        parseFloat(r.total_amount) - parseFloat(r.downpayment_amount);
      html +=
        '<div style="margin-top: 5px; color: #6c757d;">Full payment due before check-in</div>';

      // Payment Breakdown Section
      html += `<div style="margin-top: 12px; padding: 12px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px;">
          <div style="font-weight: 600; color: #333; margin-bottom: 10px; font-size: 13px;">
            <i class="fas fa-file-invoice-dollar"></i> Payment Summary
          </div>
          
          <!-- Remaining Balance -->
          <div style="display: flex; justify-content: space-between; padding: 8px; background: #fff3cd; border-radius: 6px; margin-bottom: 8px;">
            <div>
              <div style="color: #856404; font-weight: 600; font-size: 13px;">
                <i class="fas fa-wallet"></i> Remaining Balance
              </div>
              <div style="color: #6c757d; font-size: 11px; margin-top: 2px;">
                Must be paid before check-in
              </div>
            </div>
            <div style="color: #856404; font-weight: 700; font-size: 15px;">
              ₱${remainingBalance.toLocaleString("en-PH", {
                minimumFractionDigits: 2,
              })}
            </div>
          </div>
          
          <!-- Security Bond -->
          <div style="display: flex; justify-content: space-between; padding: 8px; background: #d1ecf1; border-radius: 6px;">
            <div>
              <div style="color: #0c5460; font-weight: 600; font-size: 13px;">
                <i class="fas fa-shield-alt"></i> Security Bond
              </div>
              <div style="color: #6c757d; font-size: 11px; margin-top: 2px; line-height: 1.3;">
                Collected at check-in to cover any damages or extra charges.<br>
                <strong>Fully refundable</strong> upon check-out if no issues are found.<br>
                Can be paid at check-in since it's refundable.
              </div>
            </div>
            <div style="color: #0c5460; font-weight: 700; font-size: 15px; white-space: nowrap;">
              ₱2,000.00
            </div>
          </div>
        </div>`;
    }
  } else if (r.status === "completed" || r.status === "checked_out") {
    html +=
      '<div style="color: #4caf50;"><i class="fas fa-check-double"></i> <strong>Stay Completed</strong></div>';
    html +=
      '<div style="margin-top: 5px; color: #6c757d;">Thank you for staying with us!</div>';
  } else if (r.status === "cancelled") {
    html +=
      '<div style="color: #ef5350;"><i class="fas fa-ban"></i> <strong>Cancelled</strong></div>';
  }

  html += "</div>";
  return html;
}

// ===== PAYMENT UPLOAD MODAL =====
function showPaymentUploadModal(reservationId, paymentType) {
  const modalHTML = `
    <div id="paymentModal" style="
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      animation: fadeIn 0.3s ease;
    ">
      <div style="
        background: white;
        border-radius: 20px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      ">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h2 style="margin: 0; color: #333;">
            <i class="fas fa-upload"></i> Upload Payment Proof
          </h2>
          <button onclick="closePaymentModal()" style="
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
          ">&times;</button>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
          <p style="margin: 0 0 10px 0;"><strong>Reservation ID:</strong> #${reservationId}</p>
          <p style="margin: 0;"><strong>Payment Type:</strong> ${
            paymentType === "downpayment" ? "Downpayment" : "Full Payment"
          }</p>
        </div>
        
        <form id="paymentUploadForm" enctype="multipart/form-data">
          <input type="hidden" name="reservation_id" value="${reservationId}">
          <input type="hidden" name="payment_type" value="${paymentType}">
          
          <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
              <i class="fas fa-mobile-alt"></i> Payment Method *
            </label>
            <select name="payment_method" required style="
              width: 100%;
              padding: 12px;
              border: 2px solid #e0e0e0;
              border-radius: 8px;
              font-size: 14px;
            ">
              <option value="">Select payment method</option>
              <option value="gcash">GCash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="online_banking">Online Banking</option>
              <option value="cash">Cash (OTC)</option>
            </select>
          </div>
          
          <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
              <i class="fas fa-hashtag"></i> Reference Number *
            </label>
            <input type="text" name="reference_number" required placeholder="e.g., GCash Ref# or Bank Confirmation #" style="
              width: 100%;
              padding: 12px;
              border: 2px solid #e0e0e0;
              border-radius: 8px;
              font-size: 14px;
            ">
          </div>
          
          <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
              <i class="fas fa-image"></i> Payment Screenshot/Proof *
            </label>
            <input type="file" name="payment_proof" accept="image/*,application/pdf" required style="
              width: 100%;
              padding: 12px;
              border: 2px dashed #e0e0e0;
              border-radius: 8px;
              font-size: 14px;
            ">
            <small style="color: #666; display: block; margin-top: 5px;">
              Accepted: JPG, PNG, GIF, PDF (Max 5MB)
            </small>
          </div>
          
          <div style="background: #e7f3ff; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #2196F3;">
            <p style="margin: 0; font-size: 13px; color: #555;">
              <i class="fas fa-info-circle"></i> <strong>Important:</strong> Make sure your screenshot clearly shows:
            </p>
            <ul style="margin: 10px 0 0 20px; font-size: 13px; color: #555;">
              <li>Transaction amount</li>
              <li>Reference number</li>
              <li>Date and time</li>
              <li>Recipient name/account</li>
            </ul>
          </div>
          
          <button type="submit" style="
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
          " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <i class="fas fa-check-circle"></i> Submit Payment Proof
          </button>
        </form>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Handle form submission
  document
    .getElementById("paymentUploadForm")
    .addEventListener("submit", handlePaymentUpload);
}

function closePaymentModal() {
  const modal = document.getElementById("paymentModal");
  if (modal) {
    modal.style.animation = "fadeOut 0.3s ease";
    setTimeout(() => modal.remove(), 300);
  }
}

async function handlePaymentUpload(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
  submitBtn.disabled = true;

  try {
    const response = await fetch("user/upload_payment_proof.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      showNotification(result.message, "success");
      closePaymentModal();
      // Reload reservations to show updated status
      loadMyReservations();
    } else {
      showNotification(
        result.message || "Failed to upload payment proof",
        "error"
      );
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }
  } catch (error) {
    showNotification("Failed to upload payment proof", "error");
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
}

// ===== CANCEL RESERVATION =====
async function cancelReservation(reservationId) {
  if (
    !confirm(
      "Are you sure you want to cancel this reservation? This action cannot be undone."
    )
  ) {
    return;
  }

  try {
    const response = await fetch("user/cancel_reservation.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ reservation_id: reservationId }),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Reservation cancelled successfully", "success");
      loadMyReservations();
    } else {
      showNotification(
        result.message || "Failed to cancel reservation",
        "error"
      );
    }
  } catch (error) {
    showNotification("Failed to cancel reservation", "error");
  }
}

// ===== ENHANCED BOOKING HISTORY FUNCTIONS =====

// Store loaded reservations globally for filtering/sorting
let loadedReservations = [];
let currentFilter = "all";
let currentSort = "date-desc";
let currentSearchTerm = "";

// View Reservation Details - Full Modal Implementation
async function viewReservationDetails(reservationId) {
  try {
    // Remove any existing modal first
    const existingModal = document.getElementById('reservationDetailsModal');
    if (existingModal) {
      existingModal.remove();
    }
    
    showNotification(
      `Loading details for reservation #${reservationId}...`,
      "info"
    );

    // Find reservation from loaded data or fetch fresh
    let reservation = loadedReservations.find(
      (r) => r.reservation_id == reservationId || r.reservation_id == String(reservationId)
    );

    if (!reservation) {
      // Fetch from server if not in cache
      try {
        const response = await fetch(`user/get_my_reservations.php`);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        if (result.success && result.reservations) {
          loadedReservations = result.reservations;
          reservation = loadedReservations.find(
            (r) => r.reservation_id == reservationId || r.reservation_id == String(reservationId)
          );
        }
      } catch (e) {
        console.error("Error fetching reservations:", e);
      }
    }

    // If still not found, try fetching from rebooking requests
    if (!reservation) {
      try {
        const rebookingResponse = await fetch(`user/get_my_rebooking_requests.php?status=all`);
        if (rebookingResponse.ok) {
          const rebookingResult = await rebookingResponse.json();
          if (rebookingResult.success && rebookingResult.requests) {
            const rebookingRequest = rebookingResult.requests.find(
              (r) => r.reservation_id == reservationId || r.reservation_id == String(reservationId)
            );
            if (rebookingRequest) {
              // Convert rebooking request format to reservation format for modal
              reservation = {
                ...rebookingRequest,
                // Ensure we have all required fields for the modal
                number_of_guests: rebookingRequest.number_of_guests || 1,
                group_type: rebookingRequest.group_type || 'Not specified',
                base_price: rebookingRequest.base_price || rebookingRequest.total_amount || 0,
                remaining_balance: rebookingRequest.remaining_balance || (parseFloat(rebookingRequest.total_amount || 0) - parseFloat(rebookingRequest.downpayment_amount || 0)),
                days_until_checkin: rebookingRequest.days_until_checkin || 0,
                status_label: rebookingRequest.status_label || (rebookingRequest.status ? rebookingRequest.status.charAt(0).toUpperCase() + rebookingRequest.status.slice(1).replace('_', ' ') : 'Confirmed')
              };
            }
          }
        }
      } catch (e) {
        console.error("Error fetching from rebooking requests:", e);
      }
    }

    if (!reservation) {
      showNotification("Reservation not found. Please try refreshing the page.", "error");
      return;
    }

    showReservationDetailsModal(reservation);
  } catch (error) {
    console.error("Error loading reservation details:", error);
    showNotification(
      "Failed to load reservation details: " + (error.message || 'Unknown error'),
      "error"
    );
  }
}

function showReservationDetailsModal(r) {
  try {
    const statusColors = {
      pending_payment: "#ffa726",
      pending_confirmation: "#ffb74d",
      confirmed: "#42a5f5",
      checked_in: "#66bb6a",
      checked_out: "#81c784",
      completed: "#4caf50",
      cancelled: "#ef5350",
      no_show: "#ff7043",
      forfeited: "#e57373",
      rebooked: "#7986cb",
    };

    const statusColor = statusColors[r.status] || "#999";
    const remainingBalance =
      parseFloat(r.total_amount || 0) - parseFloat(r.downpayment_amount || 0);

    const modalHTML = `
    <div id="reservationDetailsModal" class="reservation-modal-overlay" onclick="closeReservationDetailsModal(event)">
      <div class="reservation-modal-content" onclick="event.stopPropagation()">
        <div class="reservation-modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
          <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
              <h2 style="color: white; margin: 0; font-size: 1.5rem;">
                <i class="fas fa-ticket-alt"></i> Reservation #${
                  r.reservation_id
                }
              </h2>
              <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0 0; font-size: 0.9rem;">
                Created: ${r.created_at ? (typeof formatDateTime === 'function' ? formatDateTime(r.created_at) : new Date(r.created_at).toLocaleString()) : 'N/A'}
              </p>
            </div>
            <button onclick="closeReservationDetailsModal()" class="modal-close-btn">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        
        <div class="reservation-modal-body">
          <!-- Status Badge -->
          <div style="text-align: center; margin-bottom: 20px;">
            <span style="
              display: inline-block;
              padding: 10px 25px;
              background: ${statusColor};
              color: white;
              border-radius: 25px;
              font-weight: 600;
              font-size: 1rem;
              text-transform: uppercase;
              box-shadow: 0 4px 15px ${statusColor}40;
            ">
              <i class="fas fa-${getStatusIcon(r.status)}"></i> ${
      r.status_label
    }
            </span>
          </div>
          
          <!-- Security Bond Notice -->
          <div style="
            background: linear-gradient(135deg, #fff8e1, #fffde7);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
          ">
            <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 8px;">
              <i class="fas fa-shield-alt" style="color: #f57c00; font-size: 24px;"></i>
              <span style="font-weight: 700; color: #e65100; font-size: 1.1rem;">₱2,000 Security Bond</span>
            </div>
            <p style="margin: 0; color: #795548; font-size: 0.9rem; line-height: 1.5;">
              <strong>Required upon check-in (refundable)</strong><br>
              Covers potential damages or extra charges. Can be paid at check-in.
            </p>
          </div>
          
          <!-- Booking Information -->
          <div class="details-section">
            <h3><i class="fas fa-calendar-alt"></i> Booking Information</h3>
            <div class="details-grid">
              <div class="detail-item">
                <label>Booking Type</label>
                <span>${typeof formatBookingType === 'function' ? formatBookingType(r.booking_type) : (r.booking_type || 'Standard')}</span>
              </div>
              <div class="detail-item">
                <label>Package</label>
                <span>${r.package_type || "Standard"}</span>
              </div>
              <div class="detail-item">
                <label>Room/Accommodation</label>
                <span>${r.room || "All Rooms"}</span>
              </div>
              <div class="detail-item">
                <label>Number of Guests</label>
                <span>${r.number_of_guests || 1} guest(s)</span>
              </div>
              <div class="detail-item">
                <label>Group Type</label>
                <span>${r.group_type || "Not specified"}</span>
              </div>
            </div>
          </div>
          
          <!-- Date & Time Information -->
          <div class="details-section">
            <h3><i class="fas fa-clock"></i> Schedule</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
              <!-- Check-in Box -->
              <div style="
                padding: 16px;
                background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
                border-radius: 12px;
                border-left: 4px solid #4caf50;
              ">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                  <i class="fas fa-sign-in-alt" style="color: #4caf50; font-size: 18px;"></i>
                  <span style="font-weight: 700; color: #2e7d32; text-transform: uppercase; font-size: 0.85rem;">Check-in</span>
                </div>
                <div style="font-weight: 700; color: #1b5e20; font-size: 1.1rem; margin-bottom: 4px;">
                  ${formatDate(r.rebooking_approved == 1 && (r.rebooking_original_date || r.original_check_in_date) ? (r.rebooking_original_date || r.original_check_in_date) : r.check_in_date)}
                  ${r.rebooking_approved == 1 && (r.rebooking_original_date || r.original_check_in_date) ? ' <span style="font-size: 0.8rem; color: #666;">(Original)</span>' : ''}
                </div>
                <div style="color: #388e3c; font-size: 1rem; font-weight: 600;">
                  <i class="fas fa-clock" style="margin-right: 4px;"></i> ${formatTime12Hour(
                    r.check_in_time
                  )}
                </div>
              </div>
              
              <!-- Check-out Box -->
              <div style="
                padding: 16px;
                background: linear-gradient(135deg, #ffebee, #fce4ec);
                border-radius: 12px;
                border-left: 4px solid #ef5350;
              ">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                  <i class="fas fa-sign-out-alt" style="color: #ef5350; font-size: 18px;"></i>
                  <span style="font-weight: 700; color: #c62828; text-transform: uppercase; font-size: 0.85rem;">Check-out</span>
                </div>
                <div style="font-weight: 700; color: #b71c1c; font-size: 1.1rem; margin-bottom: 4px;">
                  ${formatDate(r.check_out_date)}
                </div>
                <div style="color: #d32f2f; font-size: 1rem; font-weight: 600;">
                  <i class="fas fa-clock" style="margin-right: 4px;"></i> ${formatTime12Hour(
                    r.check_out_time
                  )}
                </div>
              </div>
            </div>
            
            <div class="details-grid">
              <div class="detail-item">
                <label>Duration</label>
                <span>${(() => {
                  const days = r.number_of_days;
                  const nights = r.number_of_nights;
                  if (days && days > 0) return days + " day(s)";
                  if (nights && nights > 0) return nights + " night(s)";
                  return "1 session";
                })()}</span>
              </div>
              <div class="detail-item">
                <label>Days Until Check-in</label>
                <span style="color: ${
                  r.days_until_checkin < 0
                    ? "#ef5350"
                    : r.days_until_checkin <= 3
                    ? "#ffa726"
                    : "#4caf50"
                }">
                  ${
                    r.days_until_checkin < 0
                      ? Math.abs(r.days_until_checkin) + " days ago"
                      : r.days_until_checkin + " days"
                  }
                </span>
              </div>
            </div>
          </div>
          
          <!-- Guest Information -->
          <div class="details-section">
            <h3><i class="fas fa-user"></i> Guest Information</h3>
            <div class="details-grid">
              <div class="detail-item">
                <label>Guest Name</label>
                <span>${r.guest_name || "Not provided"}</span>
              </div>
              <div class="detail-item">
                <label>Email</label>
                <span>${r.guest_email || "Not provided"}</span>
              </div>
              <div class="detail-item">
                <label>Phone</label>
                <span>${r.guest_phone || "Not provided"}</span>
              </div>
            </div>
          </div>
          
          <!-- Payment Information -->
          <div class="details-section">
            <h3><i class="fas fa-money-bill-wave"></i> Payment Details</h3>
            <div class="payment-breakdown">
              <div class="payment-row">
                <span>Base Price</span>
                <span>₱${parseFloat(r.base_price || 0).toLocaleString("en-PH", {
                  minimumFractionDigits: 2,
                })}</span>
              </div>
              <div class="payment-row">
                <span>Total Amount</span>
                <span style="font-weight: 600;">₱${parseFloat(
                  r.total_amount
                ).toLocaleString("en-PH", { minimumFractionDigits: 2 })}</span>
              </div>
              <div class="payment-row" style="border-top: 1px dashed #ddd; padding-top: 10px; margin-top: 10px;">
                <span>
                  Downpayment (50%)
                  <span class="payment-status-badge ${
                    r.downpayment_verified == 1
                      ? "verified"
                      : r.downpayment_paid == 1
                      ? "pending"
                      : "unpaid"
                  }">
                    ${r.downpayment_status}
                  </span>
                </span>
                <span>₱${parseFloat(r.downpayment_amount).toLocaleString(
                  "en-PH",
                  { minimumFractionDigits: 2 }
                )}</span>
              </div>
              <div class="payment-row">
                <span>
                  Remaining Balance
                  <span class="payment-status-badge ${
                    r.full_payment_verified == 1
                      ? "verified"
                      : r.full_payment_paid == 1
                      ? "pending"
                      : "unpaid"
                  }">
                    ${r.full_payment_status}
                  </span>
                </span>
                <span>₱${remainingBalance.toLocaleString("en-PH", {
                  minimumFractionDigits: 2,
                })}</span>
              </div>
              <div class="payment-row" style="background: #e3f2fd; padding: 10px; border-radius: 8px; margin-top: 10px;">
                <span><i class="fas fa-shield-alt"></i> Security Bond (Refundable)</span>
                <span>₱2,000.00</span>
              </div>
              ${
                r.payment_method
                  ? `
              <div class="payment-row" style="margin-top: 10px;">
                <span>Payment Method</span>
                <span style="text-transform: capitalize;">${r.payment_method}</span>
              </div>`
                  : ""
              }
              ${
                r.downpayment_reference
                  ? `
              <div class="payment-row">
                <span>Downpayment Reference</span>
                <span>${r.downpayment_reference}</span>
              </div>`
                  : ""
              }
            </div>
          </div>
          
          ${
            r.special_requests
              ? `
          <!-- Special Requests -->
          <div class="details-section">
            <h3><i class="fas fa-sticky-note"></i> Special Requests</h3>
            <p style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 0; color: #555;">
              ${r.special_requests}
            </p>
          </div>`
              : ""
          }
          
          ${
            r.rebooking_requested == 1 || r.rebooking_approved == 1
              ? `
          <!-- Rebooking Request -->
          <div class="details-section" style="background: #fff3e0; border-left: 4px solid #ff9800;">
            <h3 style="color: #e65100;"><i class="fas fa-calendar-check"></i> Rebooking Information</h3>
            <div class="details-grid">
              ${r.rebooking_approved == 1 && (r.rebooking_original_date || r.original_check_in_date) ? `
              <div class="detail-item">
                <label>Original Date</label>
                <span>${formatDate(r.rebooking_original_date || r.original_check_in_date || r.check_in_date)}</span>
              </div>
              ` : ''}
              ${r.rebooking_new_date ? `
              <div class="detail-item">
                <label>${r.rebooking_approved == 1 ? 'New Date (Approved)' : 'Requested New Date'}</label>
                <span style="color: ${r.rebooking_approved == 1 ? '#4caf50' : '#ff9800'}; font-weight: 600;">${formatDate(r.rebooking_new_date)}</span>
              </div>
              ` : ''}
              <div class="detail-item">
                <label>Reason</label>
                <span>${r.rebooking_reason || "Not specified"}</span>
              </div>
              <div class="detail-item">
                <label>Status</label>
                <span style="color: ${
                  r.rebooking_approved == 1 ? "#4caf50" : "#ff9800"
                }; font-weight: 600;">
                  ${r.rebooking_approved == 1 ? "✓ Approved" : "⏳ Pending Approval"}
                </span>
              </div>
              ${r.rebooking_approved_at ? `
              <div class="detail-item">
                <label>Approved At</label>
                <span>${formatDateTime(r.rebooking_approved_at)}</span>
              </div>
              ` : ''}
            </div>
          </div>`
              : ""
          }
          
          ${
            r.status === "cancelled"
              ? `
          <!-- Cancellation Info -->
          <div class="details-section" style="background: #ffebee; border-left: 4px solid #ef5350;">
            <h3 style="color: #c62828;"><i class="fas fa-ban"></i> Cancellation Details</h3>
            <div class="details-grid">
              <div class="detail-item">
                <label>Cancelled At</label>
                <span>${
                  r.cancelled_at ? formatDateTime(r.cancelled_at) : "N/A"
                }</span>
              </div>
              <div class="detail-item">
                <label>Reason</label>
                <span>${r.cancellation_reason || "Not specified"}</span>
              </div>
            </div>
          </div>`
              : ""
          }
        </div>
        
        <div class="reservation-modal-footer">
          ${getModalActionButtons(r)}
        </div>
      </div>
    </div>
  `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    document.body.style.overflow = "hidden";
  } catch (error) {
    console.error("Error showing reservation details modal:", error);
    showNotification(
      "Failed to display reservation details: " + error.message,
      "error"
    );
  }
}

function getModalActionButtons(r) {
  let buttons = "";

  // Print/Download Receipt button for all confirmed or completed reservations
  if (
    ["confirmed", "checked_in", "checked_out", "completed"].includes(r.status)
  ) {
    buttons += `<button class="modal-btn secondary" onclick="printReservationReceipt('${r.reservation_id}');">
      <i class="fas fa-print"></i> Print Receipt
    </button>`;
  }

  if (r.can_upload_downpayment) {
    buttons += `<button class="modal-btn primary" onclick="closeReservationDetailsModal(); completePayment('${r.reservation_id}', 'downpayment');">
      <i class="fas fa-upload"></i> Upload Downpayment
    </button>`;
  }

  if (r.can_upload_full_payment) {
    buttons += `<button class="modal-btn primary" onclick="closeReservationDetailsModal(); completePayment('${r.reservation_id}', 'full_payment');">
      <i class="fas fa-credit-card"></i> Pay Remaining Balance
    </button>`;
  }

  if (r.can_rebook) {
    buttons += `<button class="modal-btn warning" onclick="closeReservationDetailsModal(); showRebookingModal('${r.reservation_id}');">
      <i class="fas fa-calendar-alt"></i> Request Rebooking
    </button>`;
  }

  if (r.can_cancel) {
    buttons += `<button class="modal-btn danger" onclick="closeReservationDetailsModal(); cancelReservation('${r.reservation_id}');">
      <i class="fas fa-times"></i> Cancel Reservation
    </button>`;
  }

  if (r.status === "completed" || r.status === "checked_out") {
    buttons += `<button class="modal-btn primary" onclick="closeReservationDetailsModal(); bookAgainFromReservation('${r.reservation_id}');">
      <i class="fas fa-redo"></i> Book Again
    </button>`;
  }

  buttons += `<button class="modal-btn secondary" onclick="closeReservationDetailsModal();">
    <i class="fas fa-times"></i> Close
  </button>`;

  return buttons;
}

function closeReservationDetailsModal(event) {
  if (event && event.target !== event.currentTarget) return;
  const modal = document.getElementById("reservationDetailsModal");
  if (modal) {
    modal.style.animation = "fadeOut 0.3s ease";
    setTimeout(() => {
      modal.remove();
      document.body.style.overflow = "";
    }, 300);
  }
}

// Helper functions
function getStatusIcon(status) {
  const icons = {
    pending_payment: "clock",
    pending_confirmation: "hourglass-half",
    confirmed: "check-circle",
    checked_in: "sign-in-alt",
    checked_out: "sign-out-alt",
    completed: "check-double",
    cancelled: "ban",
    no_show: "user-slash",
    forfeited: "exclamation-triangle",
    rebooked: "calendar-check",
  };
  return icons[status] || "info-circle";
}

function formatBookingType(type) {
  const types = {
    daytime: "Daytime Package (9AM - 5PM)",
    nighttime: "Nighttime Package (7PM - 7AM)",
    "22hours": "22 Hours Package (2PM - 12NN)",
    "venue-daytime": "Venue - Daytime",
    "venue-nighttime": "Venue - Nighttime",
    "venue-22hours": "Venue - 22 Hours",
  };
  return types[type] || type || "Standard";
}

function formatDateTime(dateString) {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  return date.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

function formatTime12Hour(timeString) {
  if (!timeString) return "N/A";
  // Handle time in HH:MM:SS or HH:MM format
  const timeParts = timeString.split(":");
  if (timeParts.length < 2) return timeString;

  let hours = parseInt(timeParts[0], 10);
  const minutes = timeParts[1];
  const ampm = hours >= 12 ? "PM" : "AM";

  hours = hours % 12;
  hours = hours ? hours : 12; // Convert 0 to 12

  return `${hours}:${minutes} ${ampm}`;
}

// ===== REBOOKING MODAL =====
function showRebookingModal(reservationId) {
  const reservation = loadedReservations.find(
    (r) => r.reservation_id == reservationId
  );
  if (!reservation) {
    showNotification("Reservation not found", "error");
    return;
  }

  // Calculate date constraints
  const originalDate = new Date(reservation.check_in_date);
  const minDate = new Date();
  minDate.setDate(minDate.getDate() + 7); // Minimum 7 days from now
  const maxDate = new Date(originalDate);
  maxDate.setMonth(maxDate.getMonth() + 3); // Maximum 3 months from original date

  const modalHTML = `
    <div id="rebookingModal" class="reservation-modal-overlay" onclick="closeRebookingModal(event)">
      <div class="reservation-modal-content" style="max-width: 500px;" onclick="event.stopPropagation()">
        <div class="reservation-modal-header" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);">
          <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h2 style="color: white; margin: 0;">
              <i class="fas fa-calendar-alt"></i> Request Rebooking
            </h2>
            <button onclick="closeRebookingModal()" class="modal-close-btn">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        
        <div class="reservation-modal-body">
          <div style="background: #fff3e0; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            <p style="margin: 0; color: #e65100;">
              <i class="fas fa-info-circle"></i> <strong>Rebooking Policy:</strong>
            </p>
            <ul style="margin: 10px 0 0 20px; font-size: 14px; color: #555;">
              <li>Rebooking must be requested at least 7 days before check-in</li>
              <li>New date must be within 3 months of original date</li>
              <li>Downpayment is non-refundable but will be applied to new booking</li>
              <li>Subject to date availability and admin approval</li>
            </ul>
          </div>
          
          <div style="margin-bottom: 20px;">
            <p><strong>Current Check-in Date:</strong> ${formatDate(
              reservation.check_in_date
            )}</p>
            <p><strong>Reservation #:</strong> ${reservation.reservation_id}</p>
          </div>
          
          <form id="rebookingForm" onsubmit="submitRebooking(event, ${reservationId})">
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                <i class="fas fa-calendar"></i> New Check-in Date *
              </label>
              <input type="date" name="new_date" required 
                min="${minDate.toISOString().split("T")[0]}"
                max="${maxDate.toISOString().split("T")[0]}"
                style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
              <small style="color: #666; display: block; margin-top: 5px;">
                Available dates: ${formatDate(
                  minDate.toISOString()
                )} - ${formatDate(maxDate.toISOString())}
              </small>
            </div>
            
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                <i class="fas fa-comment"></i> Reason for Rebooking
              </label>
              <textarea name="reason" rows="3" placeholder="Please explain why you need to rebook..."
                style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
            </div>
            
            <button type="submit" class="modal-btn primary" style="width: 100%;">
              <i class="fas fa-paper-plane"></i> Submit Rebooking Request
            </button>
          </form>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);
  document.body.style.overflow = "hidden";
}

function closeRebookingModal(event) {
  if (event && event.target !== event.currentTarget) return;
  const modal = document.getElementById("rebookingModal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
  }
}

async function submitRebooking(event, reservationId) {
  event.preventDefault();

  const form = event.target;
  const newDate = form.new_date.value;
  const reason = form.reason.value;
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
  submitBtn.disabled = true;

  try {
    const response = await fetch("user/request_rebooking.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        reservation_id: reservationId,
        new_date: newDate,
        reason: reason,
      }),
    });

    const result = await response.json();

    if (result.success) {
      showNotification(
        result.message || "Rebooking request submitted successfully!",
        "success"
      );
      closeRebookingModal();
      loadMyReservations(); // Refresh the list
    } else {
      showNotification(
        result.message || "Failed to submit rebooking request",
        "error"
      );
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }
  } catch (error) {
    console.error("Rebooking error:", error);
    showNotification("Failed to submit rebooking request", "error");
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
}

// ===== WRITE REVIEW FOR RESERVATION =====
function writeReviewForReservation(reservationId) {
  try {
    // Check if this reservation has already been reviewed
    const hasReview = reviewedReservationIds.includes(parseInt(reservationId));
    const reviewInfo = reviewedReservationsMap[reservationId];

    if (hasReview && reviewInfo) {
      // Reservation already reviewed, redirect to edit
      if (reviewInfo.can_edit) {
        showNotification(
          "You have already reviewed this reservation. Opening edit mode...",
          "info"
        );
        editReview(reviewInfo.review_id);
      } else {
        showNotification(
          "You have already reviewed this reservation and reached the maximum edit limit.",
          "warning"
        );
      }
      return;
    }

    const reservation = loadedReservations.find(
      (r) => r.reservation_id == reservationId
    );

    // Reset rating before showing modal
    selectedRating = 0;

    // Get reservation info for display
    const reservationInfo = reservation
      ? `
      <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: left;">
        <p style="margin: 0 0 8px 0; font-size: 0.9rem;"><strong>Reservation:</strong> #${
          reservation.reservation_id
        }</p>
        <p style="margin: 0 0 8px 0; font-size: 0.9rem;"><strong>Package:</strong> ${
          reservation.package_type || "Standard"
        }</p>
        <p style="margin: 0; font-size: 0.9rem;"><strong>Check-in Date:</strong> ${formatDate(
          reservation.check_in_date
        )}</p>
      </div>
    `
      : "";

    const modalHTML = `
      <div id="reviewModal" class="reservation-modal-overlay" onclick="closeReviewModal(event)">
        <div class="reservation-modal-content" style="max-width: 550px;" onclick="event.stopPropagation()">
          <div class="reservation-modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
              <h2 style="color: white; margin: 0;">
                <i class="fas fa-star"></i> Write a Review
              </h2>
              <button onclick="closeReviewModal()" class="modal-close-btn">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          
          <div class="reservation-modal-body">
            ${reservationInfo}
            
            <div style="text-align: center; margin-bottom: 25px;">
              <p style="color: #666; margin-bottom: 15px;">How was your stay?</p>
              <div id="starRating" style="font-size: 2.5rem; cursor: pointer;">
                <i class="far fa-star" data-rating="1" onclick="setRating(1)" style="color: #ddd; transition: all 0.2s;"></i>
                <i class="far fa-star" data-rating="2" onclick="setRating(2)" style="color: #ddd; transition: all 0.2s;"></i>
                <i class="far fa-star" data-rating="3" onclick="setRating(3)" style="color: #ddd; transition: all 0.2s;"></i>
                <i class="far fa-star" data-rating="4" onclick="setRating(4)" style="color: #ddd; transition: all 0.2s;"></i>
                <i class="far fa-star" data-rating="5" onclick="setRating(5)" style="color: #ddd; transition: all 0.2s;"></i>
              </div>
              <p id="ratingText" style="color: #f59e0b; font-weight: 600; margin-top: 10px;">Select a rating</p>
            </div>
            
            <form id="reviewForm" onsubmit="submitReview(event, '${reservationId}')">
              <input type="hidden" name="rating" id="ratingInput" value="0">
              <input type="hidden" name="reservation_id" value="${reservationId}">
              
              <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                  <i class="fas fa-heading"></i> Review Title *
                </label>
                <input type="text" name="title" placeholder="Give your review a title..." required
                  style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
              </div>
              
              <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                  <i class="fas fa-comment-alt"></i> Your Review *
                </label>
                <textarea name="content" rows="4" placeholder="Share your experience with other guests... (minimum 10 characters)" required minlength="10"
                  style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical; box-sizing: border-box;"></textarea>
              </div>
              
              <button type="submit" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;">
                <i class="fas fa-paper-plane"></i> Submit Review
              </button>
            </form>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    document.body.style.overflow = "hidden";
  } catch (error) {
    console.error("Error opening review modal:", error);
    showNotification("Failed to open review form. Please try again.", "error");
  }
}

let selectedRating = 0;
function setRating(rating) {
  selectedRating = rating;
  document.getElementById("ratingInput").value = rating;

  const stars = document.querySelectorAll("#starRating i");
  const ratingTexts = ["", "Poor", "Fair", "Good", "Very Good", "Excellent"];

  stars.forEach((star, index) => {
    if (index < rating) {
      star.classList.remove("far");
      star.classList.add("fas");
      star.style.color = "#f59e0b";
    } else {
      star.classList.remove("fas");
      star.classList.add("far");
      star.style.color = "#ddd";
    }
  });

  document.getElementById("ratingText").textContent = ratingTexts[rating];
}

function closeReviewModal(event) {
  if (event && event.target !== event.currentTarget) return;
  const modal = document.getElementById("reviewModal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
    // Reset rating when modal closes
    selectedRating = 0;
  }
}

async function submitReview(event, reservationId) {
  event.preventDefault();

  if (selectedRating === 0) {
    showNotification("Please select a rating", "warning");
    return;
  }

  const form = event.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
  submitBtn.disabled = true;

  try {
    const response = await fetch("user/submit_review.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        reservation_id: reservationId,
        rating: selectedRating,
        title: formData.get("title"),
        content: formData.get("content"),
      }),
    });

    const data = await response.json();

    if (data.success) {
      showNotification(
        "Thank you for your review! It has been submitted successfully.",
        "success"
      );

      // Close the review modal first
      closeReviewModal();

      // Reset the rating for next use
      selectedRating = 0;

      // Reload reviews to update the list
      if (typeof loadUserReviews === "function") {
        await loadUserReviews();
      }

      // Navigate to reviews section by clicking nav button
      try {
        const navBtn = document.querySelector('[data-section="reviews"]');
        if (navBtn) {
          navBtn.click();
        } else {
          showSection("reviews");
          updateActiveNavigation("reviews");
        }
      } catch (navError) {
        console.log("Navigation handled:", navError);
      }
    } else {
      // Log debug info if available
      if (data.debug) {
        console.error("Review submission failed - Debug info:", data.debug);
        console.error("Reservation status:", data.debug.status, "| Lowercase:", data.debug.status_lower, "| Checked out:", data.debug.checked_out);
      }
      showNotification(data.message || "Failed to submit review", "error");
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }
  } catch (error) {
    console.error("Error submitting review:", error);
    showNotification("Failed to submit review. Please try again.", "error");
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
}

// ===== PRINT RESERVATION RECEIPT =====
function printReservationReceipt(reservationId) {
  const reservation = loadedReservations.find(
    (r) => r.reservation_id == reservationId
  );
  if (!reservation) {
    showNotification("Reservation not found", "error");
    return;
  }

  const r = reservation;
  const remainingBalance =
    parseFloat(r.total_amount) - parseFloat(r.downpayment_amount);

  const receiptHTML = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Reservation Receipt - #${r.reservation_id}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; color: #333; background: #f5f5f5; }
        .receipt { max-width: 700px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #667eea; padding-bottom: 20px; }
        .logo { font-size: 28px; font-weight: 700; color: #667eea; margin-bottom: 5px; }
        .subtitle { color: #666; font-size: 14px; }
        .receipt-title { font-size: 22px; color: #333; margin-top: 15px; }
        .section { margin: 25px 0; }
        .section-title { font-size: 14px; color: #667eea; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-item { padding: 8px 0; }
        .info-label { font-size: 12px; color: #888; margin-bottom: 3px; }
        .info-value { font-size: 14px; font-weight: 500; color: #333; }
        .payment-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .payment-table td { padding: 12px 0; border-bottom: 1px solid #eee; }
        .payment-table td:last-child { text-align: right; font-weight: 600; }
        .total-row { background: #f8f9fa; }
        .total-row td { font-size: 16px; font-weight: 700; padding: 15px 10px; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-confirmed { background: #e3f2fd; color: #1565c0; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #e65100; }
        .footer { margin-top: 40px; text-align: center; padding-top: 20px; border-top: 1px solid #eee; }
        .footer p { font-size: 12px; color: #888; margin: 5px 0; }
        .qr-placeholder { text-align: center; margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        @media print {
          body { padding: 0; background: white; }
          .receipt { box-shadow: none; }
          .no-print { display: none; }
        }
      </style>
    </head>
    <body>
      <div class="receipt">
        <div class="header">
          <div class="logo">🏡 AR Homes Posadas Farm Resort</div>
          <div class="subtitle">Your Home Away From Home</div>
          <div class="receipt-title">Reservation Receipt</div>
        </div>
        
        <div style="text-align: center; margin-bottom: 20px;">
          <span class="status-badge status-${
            r.status === "confirmed"
              ? "confirmed"
              : r.status === "completed" || r.status === "checked_out"
              ? "completed"
              : "pending"
          }">
            ${r.status_label}
          </span>
        </div>
        
        <div class="section">
          <div class="section-title">Reservation Details</div>
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Reservation ID</div>
              <div class="info-value">#${r.reservation_id}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Booking Date</div>
              <div class="info-value">${formatDateTime(r.created_at)}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Booking Type</div>
              <div class="info-value">${formatBookingType(r.booking_type)}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Package</div>
              <div class="info-value">${r.package_type || "Standard"}</div>
            </div>
          </div>
        </div>
        
        <div class="section">
          <div class="section-title">Guest Information</div>
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Guest Name</div>
              <div class="info-value">${r.guest_name || "N/A"}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Number of Guests</div>
              <div class="info-value">${r.number_of_guests || 1} guest(s)</div>
            </div>
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value">${r.guest_email || "N/A"}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Phone</div>
              <div class="info-value">${r.guest_phone || "N/A"}</div>
            </div>
          </div>
        </div>
        
        <div class="section">
          <div class="section-title">Schedule</div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div style="padding: 16px; background: linear-gradient(135deg, #e8f5e9, #f1f8e9); border-radius: 12px; border-left: 4px solid #4caf50;">
              <div style="font-weight: 700; color: #2e7d32; text-transform: uppercase; font-size: 12px; margin-bottom: 8px;">
                ✓ Check-in
              </div>
              <div style="font-weight: 700; color: #1b5e20; font-size: 15px; margin-bottom: 4px;">
                ${formatDate(r.check_in_date)}
              </div>
              <div style="color: #388e3c; font-size: 14px; font-weight: 600;">
                ${formatTime12Hour(r.check_in_time)}
              </div>
            </div>
            <div style="padding: 16px; background: linear-gradient(135deg, #ffebee, #fce4ec); border-radius: 12px; border-left: 4px solid #ef5350;">
              <div style="font-weight: 700; color: #c62828; text-transform: uppercase; font-size: 12px; margin-bottom: 8px;">
                ✗ Check-out
              </div>
              <div style="font-weight: 700; color: #b71c1c; font-size: 15px; margin-bottom: 4px;">
                ${formatDate(r.check_out_date)}
              </div>
              <div style="color: #d32f2f; font-size: 14px; font-weight: 600;">
                ${formatTime12Hour(r.check_out_time)}
              </div>
            </div>
          </div>
        </div>
        
        <div class="section" style="background: linear-gradient(135deg, #fff8e1, #fffde7); border: 2px solid #ffc107; border-radius: 12px; padding: 16px; text-align: center;">
          <div style="font-weight: 700; color: #e65100; font-size: 16px; margin-bottom: 8px;">
            ⚠️ ₱2,000 Security Bond upon Check-in
          </div>
          <div style="color: #795548; font-size: 13px;">
            Refundable — Covers damages/extra charges. Can be paid at check-in.
          </div>
        </div>
        
        <div class="section">
          <div class="section-title">Payment Summary</div>
          <table class="payment-table">
            <tr>
              <td>Base Price</td>
              <td>₱${parseFloat(r.base_price || 0).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
              })}</td>
            </tr>
            <tr>
              <td>Downpayment (50%) - ${r.downpayment_status}</td>
              <td>₱${parseFloat(r.downpayment_amount).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
              })}</td>
            </tr>
            <tr>
              <td>Remaining Balance - ${r.full_payment_status}</td>
              <td>₱${remainingBalance.toLocaleString("en-PH", {
                minimumFractionDigits: 2,
              })}</td>
            </tr>
            <tr>
              <td>Security Bond (Refundable)</td>
              <td>₱2,000.00</td>
            </tr>
            <tr class="total-row">
              <td>Total Amount</td>
              <td>₱${parseFloat(r.total_amount).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
              })}</td>
            </tr>
          </table>
        </div>
        
        ${
          r.special_requests
            ? `
        <div class="section">
          <div class="section-title">Special Requests</div>
          <p style="font-size: 14px; color: #555; line-height: 1.6;">${r.special_requests}</p>
        </div>
        `
            : ""
        }
        
        <div class="footer">
          <p><strong>AR Homes Posadas Farm Resort</strong></p>
          <p>Thank you for choosing us for your stay!</p>
          <p style="margin-top: 10px;">For inquiries, contact us at: arhomesresort@gmail.com</p>
          <p style="margin-top: 15px; font-size: 10px; color: #aaa;">
            Receipt generated on ${new Date().toLocaleString()}
          </p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
          <button onclick="window.print()" style="
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin-right: 10px;
          ">
            🖨️ Print Receipt
          </button>
          <button onclick="window.close()" style="
            padding: 12px 30px;
            background: #e9ecef;
            color: #495057;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
          ">
            Close
          </button>
        </div>
      </div>
    </body>
    </html>
  `;

  // Open in new window for printing
  const printWindow = window.open("", "_blank");
  printWindow.document.write(receiptHTML);
  printWindow.document.close();
}

// ===== BOOK AGAIN FUNCTIONALITY =====
function bookAgainFromReservation(reservationId) {
  try {
    const reservation = loadedReservations.find(
      (r) => r.reservation_id == reservationId
    );

    if (reservation) {
      // Store the previous booking details for pre-filling
      sessionStorage.setItem(
        "bookAgainData",
        JSON.stringify({
          booking_type: reservation.booking_type,
          package_type: reservation.package_type,
          number_of_guests: reservation.number_of_guests,
          group_type: reservation.group_type,
          special_requests: reservation.special_requests,
        })
      );

      showNotification("Redirecting to Make Reservation...", "info");
    }

    // Navigate to Make Reservation section by clicking nav button directly
    const navBtn = document.querySelector('[data-section="my-reservations"]');
    if (navBtn) {
      navBtn.click();

      // Try to pre-select the booking type if the function exists
      if (reservation && reservation.booking_type) {
        setTimeout(() => {
          try {
            if (typeof selectBookingType === "function") {
              selectBookingType(reservation.booking_type);
            }
          } catch (err) {
            console.log("Could not pre-select booking type:", err);
          }
        }, 800);
      }
    } else {
      // Fallback: use showSection
      showSection("my-reservations");
      updateActiveNavigation("my-reservations");
    }
  } catch (error) {
    console.error("Book again error:", error);
    // Last resort: just navigate to the section
    const navBtn = document.querySelector('[data-section="my-reservations"]');
    if (navBtn) {
      navBtn.click();
    }
  }
}

// ===== ENHANCED FILTER & SORT FUNCTIONS =====
function initBookingHistoryFilters() {
  // Filter buttons
  const filterButtons = document.querySelectorAll(".filter-btn");
  filterButtons.forEach((button) => {
    button.addEventListener("click", function () {
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      this.classList.add("active");
      currentFilter = this.getAttribute("data-filter");
      applyFiltersAndSort();
    });
  });

  // Search input
  const searchInput = document.getElementById("bookingSearch");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      currentSearchTerm = this.value.toLowerCase();
      applyFiltersAndSort();
    });
  }

  // Sort select
  const sortSelect = document.getElementById("sortBookings");
  if (sortSelect) {
    sortSelect.addEventListener("change", function () {
      currentSort = this.value;
      applyFiltersAndSort();
    });
  }
}

function applyFiltersAndSort() {
  // First filter to only history statuses (using shouldShowInHistory for cancelled > 1 day logic)
  let filtered = loadedReservations.filter((r) => shouldShowInHistory(r));

  // Apply status filter (for Booking History, only completed/cancelled options)
  if (currentFilter !== "all") {
    const statusMap = {
      completed: ["completed", "checked_out"],
      cancelled: ["cancelled", "canceled", "no_show", "forfeited", "expired"],
    };
    const allowedStatuses = statusMap[currentFilter] || [];
    filtered = filtered.filter((r) => allowedStatuses.includes(r.status));
  }

  // Apply search filter
  if (currentSearchTerm) {
    filtered = filtered.filter((r) => {
      const searchText = `
        ${r.reservation_id}
        ${r.booking_type || ""}
        ${r.package_type || ""}
        ${r.guest_name || ""}
        ${r.status_label || ""}
        ${r.check_in_date || ""}
      `.toLowerCase();
      return searchText.includes(currentSearchTerm);
    });
  }

  // Apply sorting
  filtered.sort((a, b) => {
    switch (currentSort) {
      case "date-desc":
        return new Date(b.check_in_date) - new Date(a.check_in_date);
      case "date-asc":
        return new Date(a.check_in_date) - new Date(b.check_in_date);
      case "price-desc":
        return parseFloat(b.total_amount) - parseFloat(a.total_amount);
      case "price-asc":
        return parseFloat(a.total_amount) - parseFloat(b.total_amount);
      default:
        return new Date(b.created_at) - new Date(a.created_at);
    }
  });

  // Re-render the cards
  displayReservations(filtered);
}

// History-only statuses (past reservations)
const HISTORY_STATUSES = [
  "completed",
  "checked_out",
  "cancelled",
  "canceled",
  "no_show",
  "forfeited",
  "expired",
];

/**
 * Check if cancelled reservation is older than 1 day (24 hours)
 * After 1 day, it moves from My Reservations to Reservation History
 */
function isCancelledOlderThan1Day(r) {
  if (r.status !== "cancelled" && r.status !== "canceled") return false;
  const cancelledAt = r.cancelled_at || r.updated_at || r.created_at;
  if (!cancelledAt) return true; // If no date, assume old
  const oneDayMs = 24 * 60 * 60 * 1000;
  return new Date() - new Date(cancelledAt) > oneDayMs;
}

/**
 * Check if a reservation should be shown in history
 * (either has history status OR is fully paid and past check-out date)
 * Cancelled reservations only show in history after 1 day (24 hours)
 */
function shouldShowInHistory(r) {
  // For cancelled reservations, only show in history after 1 day
  if (r.status === "cancelled" || r.status === "canceled") {
    return isCancelledOlderThan1Day(r);
  }

  // If already has a history status (except cancelled), show in history
  if (HISTORY_STATUSES.includes(r.status)) {
    return true;
  }

  // Check if the reservation is fully paid and past check-out date
  const isFullyPaid =
    r.full_payment_verified == 1 ||
    (r.downpayment_verified == 1 && r.full_payment_verified == 1);

  if (isFullyPaid && r.check_out_date) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Parse check-out date and time
    const checkOutDate = new Date(r.check_out_date);

    // If check-out time is provided, use it; otherwise default to end of day
    if (r.check_out_time) {
      const timeParts = r.check_out_time.split(":");
      checkOutDate.setHours(
        parseInt(timeParts[0], 10),
        parseInt(timeParts[1], 10),
        0,
        0
      );
    } else {
      checkOutDate.setHours(23, 59, 59, 999);
    }

    // If current date/time is past check-out, show in history
    if (new Date() > checkOutDate) {
      return true;
    }
  }

  return false;
}

// Override displayReservations to also store data - BOOKING HISTORY ONLY (past reservations)
const originalDisplayReservations = displayReservations;
function displayReservations(reservations) {
  // Store all loaded reservations if this is a full load
  if (
    reservations.length > 0 &&
    (!loadedReservations.length || reservations[0].reservation_id)
  ) {
    loadedReservations = reservations;
  }

  const container = document.querySelector(".bookings-history-grid");
  if (!container) return;

  // Filter to show HISTORY reservations (completed statuses OR fully paid past check-out date)
  const historyReservations = reservations.filter((r) =>
    shouldShowInHistory(r)
  );

  // Update status label for auto-completed reservations (fully paid past check-out)
  historyReservations.forEach((r) => {
    if (!HISTORY_STATUSES.includes(r.status) && shouldShowInHistory(r)) {
      r.status = "completed";
      r.status_label = "Completed";
    }
  });

  // Update history stats
  updateHistoryStats(historyReservations);

  if (historyReservations.length === 0) {
    container.innerHTML = `
      <div class="history-empty-state">
        <i class="fas fa-history"></i>
        <h3>No Reservation History Found</h3>
        <p>Your completed and cancelled reservations will appear here.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = historyReservations
    .map((r) => createHistoryReservationCard(r))
    .join("");
}

// Update history stats cards
function updateHistoryStats(historyReservations) {
  const completedCount = historyReservations.filter(
    (r) => r.status === "completed" || r.status === "checked_out"
  ).length;

  const cancelledCount = historyReservations.filter(
    (r) =>
      r.status === "cancelled" ||
      r.status === "canceled" ||
      r.status === "no_show" ||
      r.status === "forfeited" ||
      r.status === "expired"
  ).length;

  const totalCount = historyReservations.length;

  // Update DOM elements
  const completedEl = document.getElementById("historyCompletedCount");
  const cancelledEl = document.getElementById("historyCancelledCount");
  const totalEl = document.getElementById("historyTotalCount");

  if (completedEl) completedEl.textContent = completedCount;
  if (cancelledEl) cancelledEl.textContent = cancelledCount;
  if (totalEl) totalEl.textContent = totalCount;
}

// Create a history-specific card (no payment actions) - REDESIGNED TO MATCH MY RESERVATIONS
function createHistoryReservationCard(r) {
  const statusColors = {
    completed: "completed",
    checked_out: "completed",
    cancelled: "cancelled",
    canceled: "cancelled",
    no_show: "cancelled",
    forfeited: "cancelled",
    expired: "cancelled",
    rebooked: "confirmed",
  };
  const statusClass = statusColors[r.status] || "pending";

  const checkInDate = new Date(r.check_in_date).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });

  // Check if this is a cancelled reservation (show refund processed notice)
  const isCancelled = r.status === "cancelled" || r.status === "canceled";
  const isCompleted = r.status === "completed" || r.status === "checked_out";

  // Border color based on status
  const borderColor = isCancelled
    ? "#ef4444"
    : isCompleted
    ? "#22c55e"
    : "#11224e";

  // Build refund notice for cancelled reservations
  const refundNotice = isCancelled
    ? `<div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 8px 12px; margin-top: 8px; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-check-circle" style="color: #22c55e;"></i>
        <div style="font-size: 0.8em;">
          <div style="font-weight: 600; color: #92400e;">Refund Processed</div>
          <div style="color: #a16207; font-size: 0.9em;">Contact staff for details</div>
        </div>
      </div>`
    : "";

  // Build action buttons
  const actionButtons = getHistoryActionButtons(r);

  return `
    <div class="booking-list-item" data-reservation-id="${
      r.reservation_id
    }" style="cursor: pointer; padding: 12px 15px; background: white; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 10px; transition: all 0.3s ease; border: 2px solid ${borderColor};">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
        <div style="flex: 1; min-width: 0;">
          <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 6px;">
            <span class="status-badge ${statusClass}" style="font-size: 0.75em; padding: 4px 8px;">${
    r.status_label || r.status
  }</span>
            <span style="font-weight: 700; color: #1e293b; font-size: 0.9em;">#${
              r.reservation_id
            }</span>
            <span style="color: #94a3b8; font-size: 0.85em;">${checkInDate}</span>
          </div>
          <div style="font-weight: 600; color: #334155; margin-bottom: 4px; font-size: 0.95em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${
            r.package_type || "Package"
          } - ${(r.booking_type || "daytime")
    .replace("-", " ")
    .toUpperCase()}</div>
          <div style="color: #64748b; font-size: 0.85em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            <i class="fas fa-users" style="font-size: 0.9em;"></i> ${
              r.number_of_guests || 1
            } Guest(s) • 
            <i class="fas fa-tag" style="font-size: 0.9em;"></i> ₱${parseFloat(
              r.total_amount || 0
            ).toLocaleString()}
          </div>
          ${refundNotice}
          <div style="display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap;">
            ${actionButtons}
          </div>
        </div>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0;" onclick="event.stopPropagation(); viewReservationDetails('${
          r.reservation_id
        }')">
          <i class="fas fa-eye" style="color: #11224e; font-size: 1.3em;"></i>
          <span style="color: #11224e; font-size: 0.7em; font-weight: 600; white-space: nowrap;">View Details</span>
        </div>
      </div>
    </div>
  `;
}

// Handle card click - opens details unless a button was clicked
function handleCardClick(event, reservationId) {
  // If a button was clicked, don't open details (button has its own handler)
  if (event.target.closest("button")) {
    return;
  }
  viewReservationDetails(reservationId);
}

// Helper function for short booking type format
function formatBookingTypeShort(type) {
  const types = {
    daytime: "Daytime",
    nighttime: "Nighttime",
    "22hours": "22 Hours",
    "venue-daytime": "Venue Day",
    "venue-nighttime": "Venue Night",
    "venue-22hours": "Venue 22H",
  };
  return types[type] || type || "Standard";
}

// History-specific action buttons with inline styles - COMPACT DESIGN
function getHistoryActionButtons(r) {
  const isCompleted = r.status === "completed" || r.status === "checked_out";
  const isCancelled = r.status === "cancelled" || r.status === "canceled";

  // Common button style
  const btnStyle =
    "padding: 6px 12px; font-size: 0.75em; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s ease;";

  let buttons = "";

  // Only show Review/Edit Review and Book Again for completed reservations
  if (isCompleted) {
    // Check if this reservation has already been reviewed (including deleted reviews)
    // reviewedReservationsMap uses reservation_id as key (string)
    const reviewInfo = reviewedReservationsMap[r.reservation_id];
    const hasReview =
      reviewInfo !== undefined ||
      reviewedReservationIds.includes(r.reservation_id);

    if (hasReview && reviewInfo) {
      // Check if review was deleted
      if (reviewInfo.status === "deleted") {
        // Review was deleted - show disabled button
        buttons += `<button style="${btnStyle} background: #e5e7eb; color: #9ca3af; cursor: not-allowed;" disabled title="Review deleted">
          <i class="fas fa-trash-alt"></i> Review Deleted
        </button>`;
      } else if (reviewInfo.can_edit) {
        // Show Edit Review button if review exists and can still be edited
        const editsRemaining = 3 - reviewInfo.edit_count;
        buttons += `<button style="${btnStyle} background: #f59e0b; color: white;" onclick="event.stopPropagation(); editReview(${reviewInfo.review_id})" title="${editsRemaining} edit(s) remaining">
          <i class="fas fa-edit"></i> Edit (${editsRemaining})
        </button>`;
      } else {
        // Show disabled button when edit limit reached
        buttons += `<button style="${btnStyle} background: #e5e7eb; color: #9ca3af; cursor: not-allowed;" disabled title="Maximum edits reached">
          <i class="fas fa-ban"></i> No Edits Left
        </button>`;
      }
    } else if (hasReview) {
      // Has review but no reviewInfo - show disabled "Reviewed" button
      buttons += `<button style="${btnStyle} background: #e5e7eb; color: #9ca3af; cursor: not-allowed;" disabled title="Already reviewed">
        <i class="fas fa-check-circle"></i> Reviewed
      </button>`;
    } else {
      // Show Write Review button if not yet reviewed
      buttons += `<button style="${btnStyle} background: #f59e0b; color: white;" onclick="event.stopPropagation(); writeReviewForReservation('${r.reservation_id}')">
        <i class="fas fa-star"></i> Review
      </button>`;
    }

    buttons += `<button style="${btnStyle} background: #11224e; color: white;" onclick="event.stopPropagation(); bookAgainFromReservation('${r.reservation_id}')">
      <i class="fas fa-redo"></i> Book Again
    </button>`;
  }

  // For cancelled reservations, only show View Details
  // The refund notice is shown in the card body

  return buttons;
}

function createEnhancedReservationCard(r) {
  const statusColors = {
    pending_payment: "pending",
    pending_confirmation: "pending",
    confirmed: "confirmed",
    checked_in: "confirmed",
    checked_out: "completed",
    completed: "completed",
    cancelled: "cancelled",
    no_show: "cancelled",
    forfeited: "cancelled",
    rebooked: "rebooked",
  };

  const statusClass = statusColors[r.status] || "pending";

  const checkInDate = new Date(r.check_in_date).toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric",
  });

  const createdDate = r.created_at
    ? new Date(r.created_at).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      })
    : "N/A";

  // Status icon mapping
  const statusIcons = {
    pending_payment: "clock",
    pending_confirmation: "hourglass-half",
    confirmed: "check-circle",
    checked_in: "sign-in-alt",
    checked_out: "check-double",
    completed: "check-double",
    cancelled: "times-circle",
    no_show: "user-slash",
    forfeited: "exclamation-triangle",
    rebooked: "calendar-check",
  };
  const statusIcon = statusIcons[r.status] || "info-circle";

  return `
    <div class="booking-history-card" data-status="${statusClass}" data-reservation-id="${
    r.reservation_id
  }" onclick="handleCardClick(event, '${r.reservation_id}')">
      <div class="history-card-content">
        <div class="history-card-header">
          <div class="history-card-id">
            <span class="reservation-number">
              <i class="fas fa-ticket-alt"></i>
              #${r.reservation_id}
            </span>
            <span class="reservation-date">Booked: ${createdDate}</span>
          </div>
          <span class="history-status-badge ${statusClass}">
            <i class="fas fa-${statusIcon}"></i>
            ${r.status_label}
          </span>
        </div>
        
        <div class="history-card-body">
          <div class="history-package-title">
            <i class="fas fa-home"></i>
            ${r.package_type || "Standard Package"} - ${(
    r.booking_type || "daytime"
  )
    .replace("-", " ")
    .toUpperCase()}
          </div>
          
          <div class="history-info-grid">
            <div class="history-info-item">
              <i class="fas fa-calendar-day"></i>
              <span>${checkInDate}</span>
            </div>
            <div class="history-info-item">
              <i class="fas fa-users"></i>
              <span>${r.number_of_guests || 1} Guest(s)</span>
            </div>
            <div class="history-info-item">
              <i class="fas fa-peso-sign"></i>
              <span>₱${parseFloat(r.total_amount || 0).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
              })}</span>
            </div>
            <div class="history-info-item">
              <i class="fas fa-clock"></i>
              <span>${formatBookingTypeShort(r.booking_type)}</span>
            </div>
          </div>
        </div>
        
        <div class="history-card-footer">
          ${getEnhancedActionButtons(r)}
        </div>
      </div>
    </div>
  `;
}

function getEnhancedActionButtons(r) {
  let buttons = `<button class="history-action-btn view-btn" onclick="event.stopPropagation(); viewReservationDetails('${r.reservation_id}')">
    <i class="fas fa-eye"></i> View Details
  </button>`;

  if (r.can_upload_downpayment) {
    buttons += `<button class="history-action-btn" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;" onclick="event.stopPropagation(); completePayment('${r.reservation_id}', 'downpayment')">
      <i class="fas fa-upload"></i> Upload Payment
    </button>`;
  }

  if (r.can_upload_full_payment) {
    buttons += `<button class="history-action-btn" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;" onclick="event.stopPropagation(); completePayment('${r.reservation_id}', 'full_payment')">
      <i class="fas fa-credit-card"></i> Pay Balance
    </button>`;
  }

  if (r.can_rebook) {
    buttons += `<button class="history-action-btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;" onclick="event.stopPropagation(); showRebookingModal('${r.reservation_id}')">
      <i class="fas fa-calendar-alt"></i> Rebook
    </button>`;
  }

  if (r.can_cancel) {
    buttons += `<button class="history-action-btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;" onclick="event.stopPropagation(); cancelReservation('${r.reservation_id}')">
      <i class="fas fa-times"></i> Cancel
    </button>`;
  }

  if (r.status === "completed" || r.status === "checked_out") {
    buttons += `<button class="history-action-btn review-btn" onclick="event.stopPropagation(); writeReviewForReservation('${r.reservation_id}')">
      <i class="fas fa-star"></i> Review
    </button>`;
    buttons += `<button class="history-action-btn book-again-btn" onclick="event.stopPropagation(); bookAgainFromReservation('${r.reservation_id}')">
      <i class="fas fa-redo"></i> Book Again
    </button>`;
  }

  return buttons;
}

function resetBookingFilters() {
  currentFilter = "all";
  currentSort = "date-desc";
  currentSearchTerm = "";

  // Reset UI
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.classList.remove("active");
    if (btn.getAttribute("data-filter") === "all") {
      btn.classList.add("active");
    }
  });

  const searchInput = document.getElementById("bookingSearch");
  if (searchInput) searchInput.value = "";

  const sortSelect = document.getElementById("sortBookings");
  if (sortSelect) sortSelect.value = "date-desc";

  // Re-display all reservations
  displayReservations(loadedReservations);
}

// ===== NOTIFICATIONS FUNCTIONS =====

// Initialize notification badge on page load
async function initNotificationBadge() {
  try {
    const response = await fetch("user/get_notifications.php?limit=1");
    if (response.ok) {
      const result = await response.json();
      if (result.success) {
        updateNotificationBadge(result.unread_count);
      }
    }
  } catch (error) {}
}

// Load user notifications from database
async function loadUserNotifications() {
  try {
    const response = await fetch("user/get_notifications.php");

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    if (result.success) {
      displayNotifications(result.notifications);
      updateNotificationBadge(result.unread_count);

      // Show message if table doesn't exist
      if (result.message) {
      }
    } else {
      const notificationsList = document.getElementById("notificationsList");
      if (notificationsList) {
        notificationsList.innerHTML = `
          <div style="text-align: center; padding: 40px;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px; color: #ef4444;"></i>
            <p style="color: #11224e; font-weight: 500;">Error loading notifications</p>
            <p style="font-size: 14px; color: #64748b;">${result.message}</p>
          </div>
        `;
      }
    }
  } catch (error) {
    const notificationsList = document.getElementById("notificationsList");
    if (notificationsList) {
      notificationsList.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #ef4444;">
          <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px;"></i>
          <p style="color: #11224e; font-weight: 500;">Failed to load notifications</p>
          <p style="font-size: 14px; color: #64748b;">${error.message}</p>
        </div>
      `;
    }
  }
}

// Display notifications in the UI
function displayNotifications(notifications) {
  const notificationsList = document.getElementById("notificationsList");
  if (!notificationsList) {
    return;
  }

  // Also show/hide the empty state element
  const emptyState = document.getElementById("emptyNotifications");

  if (!notifications || notifications.length === 0) {
    notificationsList.style.display = "none";
    if (emptyState) {
      emptyState.style.display = "block";
    }
    return;
  }

  // Show notifications list, hide empty state
  notificationsList.style.display = "flex";
  if (emptyState) {
    emptyState.style.display = "none";
  }

  // Build notifications HTML
  let notificationsHTML = "";
  notifications.forEach((notification) => {
    const isUnread = notification.is_read == 0;
    const unreadClass = isUnread ? "unread" : "";

    // Icon based on notification type - using navy blue primary theme
    let icon = "bell";
    let iconColor = "#11224e";
    if (notification.type === "booking_confirmed") {
      icon = "check-circle";
      iconColor = "#10b981"; // Green for success
    } else if (notification.type === "booking_cancelled") {
      icon = "times-circle";
      iconColor = "#ef4444"; // Red for cancelled
    } else if (notification.type === "payment_reminder") {
      icon = "credit-card";
      iconColor = "#f59e0b"; // Amber for payment
    } else if (notification.type === "promo") {
      icon = "tag";
      iconColor = "#11224e"; // Navy blue for promos
    } else if (notification.type === "booking_pending") {
      icon = "clock";
      iconColor = "#f59e0b"; // Amber for pending
    } else if (notification.type === "check_in") {
      icon = "sign-in-alt";
      iconColor = "#10b981"; // Green for check-in
    } else if (notification.type === "check_out") {
      icon = "sign-out-alt";
      iconColor = "#11224e"; // Navy for check-out
    }

    // Format date
    const createdDate = new Date(notification.created_at);
    const now = new Date();
    const diffTime = Math.abs(now - createdDate);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
    const diffMinutes = Math.floor(diffTime / (1000 * 60));

    let timeAgo = "";
    if (diffDays > 0) {
      timeAgo = `${diffDays} day${diffDays > 1 ? "s" : ""} ago`;
    } else if (diffHours > 0) {
      timeAgo = `${diffHours} hour${diffHours > 1 ? "s" : ""} ago`;
    } else if (diffMinutes > 0) {
      timeAgo = `${diffMinutes} minute${diffMinutes > 1 ? "s" : ""} ago`;
    } else {
      timeAgo = "Just now";
    }

    // Determine if this notification should have a link
    // For booking-related notifications, create a link to my-bookings section
    let notificationLink = notification.link || "";
    if (
      !notificationLink &&
      (notification.type === "booking_confirmed" ||
        notification.type === "booking_cancelled" ||
        notification.type === "booking_pending" ||
        notification.type === "payment_reminder" ||
        notification.type === "check_in" ||
        notification.type === "check_out")
    ) {
      notificationLink = "#my-bookings";
    }

    notificationsHTML += `
      <div class="notification-item ${unreadClass}" data-notification-id="${
      notification.notification_id
    }">
        <div class="notification-icon" style="background-color: ${iconColor};">
          <i class="fas fa-${icon}"></i>
        </div>
        <div class="notification-content">
          <h4>${notification.title}</h4>
          <p>${notification.message}</p>
          <span class="notification-time"><i class="fas fa-clock"></i> ${timeAgo}</span>
          ${
            notificationLink
              ? `<a href="javascript:void(0)" onclick="event.stopPropagation(); handleNotificationLink('${notificationLink}', '${notification.notification_id}')" class="notification-link"><i class="fas fa-arrow-right"></i> View Details</a>`
              : ""
          }
        </div>
        <button class="dismiss-btn" onclick="event.stopPropagation(); dismissNotification(this)" title="Dismiss">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
  });

  notificationsList.innerHTML = notificationsHTML;
}

// Update notification badge count
function updateNotificationBadge(unreadCount) {
  const badge = document.getElementById("notificationCount");
  if (badge) {
    if (unreadCount > 0) {
      badge.textContent = unreadCount;
      badge.style.display = "block";
    } else {
      badge.style.display = "none";
    }
  }

  // Update unread count in notifications section
  const unreadCountEl = document.getElementById("unreadCount");
  if (unreadCountEl) {
    if (unreadCount > 0) {
      unreadCountEl.textContent = unreadCount;
      unreadCountEl.style.display = "inline-block";
    } else {
      unreadCountEl.style.display = "none";
    }
  }
}

// Filter notifications
function filterNotifications(filter) {
  const allBtn = document.getElementById("filterAll");
  const unreadBtn = document.getElementById("filterUnread");
  const notificationItems = document.querySelectorAll(".notification-item");
  const emptyState = document.getElementById("emptyNotifications");
  const notificationsList = document.getElementById("notificationsList");

  // Update button styles
  if (filter === "all") {
    allBtn.style.fontWeight = "600";
    allBtn.style.color = "#11224e";
    allBtn.style.borderBottom = "2px solid #11224e";
    unreadBtn.style.fontWeight = "500";
    unreadBtn.style.color = "#64748b";
    unreadBtn.style.borderBottom = "none";

    // Show all notifications
    notificationItems.forEach((item) => {
      item.style.display = "flex";
    });

    // Show notifications list if there are any, otherwise show empty state
    if (notificationItems.length > 0) {
      notificationsList.style.display = "flex";
      if (emptyState) emptyState.style.display = "none";
    } else {
      notificationsList.style.display = "none";
      if (emptyState) emptyState.style.display = "block";
    }
  } else if (filter === "unread") {
    unreadBtn.style.fontWeight = "600";
    unreadBtn.style.color = "#11224e";
    unreadBtn.style.borderBottom = "2px solid #11224e";
    allBtn.style.fontWeight = "500";
    allBtn.style.color = "#64748b";
    allBtn.style.borderBottom = "none";

    // Show only unread notifications
    let hasUnread = false;
    notificationItems.forEach((item) => {
      if (item.classList.contains("unread")) {
        item.style.display = "flex";
        hasUnread = true;
      } else {
        item.style.display = "none";
      }
    });

    // Show empty state if no unread
    if (!hasUnread && emptyState) {
      notificationsList.style.display = "none";
      emptyState.style.display = "block";
    } else if (emptyState) {
      notificationsList.style.display = "flex";
      emptyState.style.display = "none";
    }
  }
}

// Mark all notifications as read
async function markAllAsRead() {
  try {
    const response = await fetch("user/mark_notifications_read.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ mark_all: true }),
    });

    const result = await response.json();

    if (result.success) {
      // Update UI
      const unreadNotifications = document.querySelectorAll(
        ".notification-item.unread"
      );
      unreadNotifications.forEach((notif) => {
        notif.classList.remove("unread");
      });

      // Update badge
      updateNotificationBadge(0);
      showNotification("All notifications marked as read.", "success");
    } else {
      showNotification("Failed to mark notifications as read.", "error");
    }
  } catch (error) {
    showNotification("Failed to mark notifications as read.", "error");
  }
}

// Mark all notifications as unread
async function markAllAsUnread() {
  try {
    const response = await fetch("user/mark_notifications_read.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ mark_all_unread: true }),
    });

    const result = await response.json();

    if (result.success) {
      // Update UI
      const allNotifications = document.querySelectorAll(".notification-item");
      allNotifications.forEach((notif) => {
        notif.classList.add("unread");
      });

      // Update badge
      updateNotificationBadge(allNotifications.length);
      showNotification("All notifications marked as unread.", "success");
    } else {
      showNotification("Failed to mark notifications as unread.", "error");
    }
  } catch (error) {
    showNotification("Failed to mark notifications as unread.", "error");
  }
}

// Clear all notifications
async function clearAllNotifications() {
  // Confirm before clearing
  if (
    !confirm(
      "Are you sure you want to clear all notifications? This action cannot be undone."
    )
  ) {
    return;
  }

  try {
    const response = await fetch("user/clear_notifications.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ clear_all: true }),
    });

    const result = await response.json();

    if (result.success) {
      // Update UI - show empty state
      const notificationsList = document.getElementById("notificationsList");
      const emptyState = document.getElementById("emptyNotifications");

      if (notificationsList) {
        notificationsList.innerHTML = "";
        notificationsList.style.display = "none";
      }
      if (emptyState) {
        emptyState.style.display = "block";
      }

      // Update badge
      updateNotificationBadge(0);
      showNotification("All notifications cleared.", "success");
    } else {
      showNotification("Failed to clear notifications.", "error");
    }
  } catch (error) {
    showNotification("Failed to clear notifications.", "error");
  }
}

// Dismiss a notification
async function dismissNotification(button) {
  const notificationItem = button.closest(".notification-item");
  const notificationId = notificationItem.dataset.notificationId;

  try {
    const response = await fetch("user/dismiss_notification.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ notification_id: notificationId }),
    });

    const result = await response.json();

    if (result.success) {
      // Animate out and remove
      notificationItem.style.animation = "slideOutRight 0.3s ease";
      setTimeout(() => {
        notificationItem.remove();

        // Update badge count
        const remainingUnread = document.querySelectorAll(
          ".notification-item.unread"
        ).length;
        updateNotificationBadge(remainingUnread);

        // Check if no notifications left
        const allNotifications =
          document.querySelectorAll(".notification-item");
        if (allNotifications.length === 0) {
          displayNotifications([]);
        }
      }, 300);
    } else {
      showNotification("Failed to dismiss notification.", "error");
    }
  } catch (error) {
    showNotification("Failed to dismiss notification.", "error");
  }
}

// Handle notification link click
async function handleNotificationLink(link, notificationId) {
  console.log("handleNotificationLink called with:", link, notificationId);

  try {
    // Mark notification as read
    fetch("user/mark_notifications_read.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ notification_id: notificationId }),
    }).catch((err) => console.log("Error marking notification as read:", err));

    // Update UI - remove unread class
    const notificationItem = document.querySelector(
      `.notification-item[data-notification-id="${notificationId}"]`
    );
    if (notificationItem) {
      notificationItem.classList.remove("unread");
    }

    // Update badge count
    const remainingUnread = document.querySelectorAll(
      ".notification-item.unread"
    ).length;
    updateNotificationBadge(remainingUnread);

    // Parse URL parameters if link contains query string
    let sectionId = null;
    let reservationId = null;

    if (link.includes("?")) {
      const urlParams = new URLSearchParams(link.split("?")[1]);
      sectionId = urlParams.get("section");
      reservationId = urlParams.get("reservation");
      console.log(
        "Parsed URL params - section:",
        sectionId,
        "reservation:",
        reservationId
      );
    }

    // Handle different link types
    if (reservationId) {
      // If there's a reservation ID, show the reservation details
      console.log("Opening reservation details for:", reservationId);

      // First navigate to the section if specified
      if (sectionId) {
        if (typeof showSection === "function") {
          showSection(sectionId);
        } else if (
          window.popupManager &&
          typeof window.popupManager.showSection === "function"
        ) {
          window.popupManager.showSection(sectionId);
        } else {
          const navBtn = document.querySelector(
            `.nav-btn[data-section="${sectionId}"]`
          );
          if (navBtn) navBtn.click();
        }
      }

      // Then open the reservation details modal
      setTimeout(() => {
        if (typeof viewReservationDetails === "function") {
          viewReservationDetails(reservationId);
        } else if (typeof window.viewReservationDetails === "function") {
          window.viewReservationDetails(reservationId);
        } else {
          showNotification("Opening reservation: " + reservationId, "info");
        }
      }, 300);
    } else if (sectionId) {
      // Navigate to section only
      console.log("Navigating to section:", sectionId);

      if (typeof showSection === "function") {
        showSection(sectionId);
      } else if (
        window.popupManager &&
        typeof window.popupManager.showSection === "function"
      ) {
        window.popupManager.showSection(sectionId);
      } else {
        const navBtn = document.querySelector(
          `.nav-btn[data-section="${sectionId}"]`
        );
        if (navBtn) {
          navBtn.click();
        } else {
          showNotification("Navigating to " + sectionId, "info");
        }
      }
    } else if (link.startsWith("#")) {
      // Internal section link (e.g., #bookings, #my-bookings)
      const hashSection = link.substring(1);
      console.log("Navigating to hash section:", hashSection);

      if (typeof showSection === "function") {
        showSection(hashSection);
      } else if (
        window.popupManager &&
        typeof window.popupManager.showSection === "function"
      ) {
        window.popupManager.showSection(hashSection);
      } else {
        // Fallback: try to find and click the nav button
        const navBtn = document.querySelector(
          `.nav-btn[data-section="${hashSection}"]`
        );
        if (navBtn) {
          navBtn.click();
        } else {
          showNotification("Navigating to " + hashSection, "info");
        }
      }
    } else if (link.includes("RES-") || link.includes("RES")) {
      // Reservation details link (old format)
      const resMatch = link.match(/RES-[\w-]+|RES\d+/);
      if (resMatch) {
        console.log("Opening reservation details for:", resMatch[0]);
        if (typeof viewReservationDetails === "function") {
          viewReservationDetails(resMatch[0]);
        }
      }
    } else if (link.startsWith("http")) {
      // External link
      window.open(link, "_blank");
    } else {
      // Default: try to navigate to link
      window.location.href = link;
    }
  } catch (error) {
    console.error("Error handling notification link:", error);
    // Still try to navigate even if marking as read fails
    if (link.startsWith("http")) {
      window.open(link, "_blank");
    } else if (!link.startsWith("#")) {
      window.location.href = link;
    }
  }
}

// Initialize notifications on page load
// Initialize notifications badge on page load
document.addEventListener("DOMContentLoaded", function () {
  initNotificationBadge();
  // Reviews are now loaded in the main initialization to ensure proper order
});

// ===== REVIEWS FUNCTIONS =====
// Global reviews data storage
let loadedReviewsData = [];
let reviewableReservations = [];
let reviewedReservationIds = []; // IDs of reservations that have been reviewed
let reviewedReservationsMap = {}; // Map of reservation_id => {review_id, edit_count, can_edit}

// Load user reviews from the server
async function loadUserReviews() {
  try {
    const response = await fetch("user/get_reviews.php");
    const data = await response.json();

    if (data.success) {
      loadedReviewsData = data.reviews || [];
      reviewableReservations = data.reviewable_reservations || [];
      reviewedReservationIds = data.reviewed_reservation_ids || [];
      reviewedReservationsMap = data.reviewed_reservations_map || {};

      // Update stats
      updateReviewStats(data.stats);

      // Render reviews list
      renderReviewsList(loadedReviewsData);

      // Update profile reviews given count
      const reviewsGivenElement = document.getElementById(
        "profileReviewsGiven"
      );
      if (reviewsGivenElement) {
        reviewsGivenElement.textContent = data.stats.total_reviews || 0;
      }

      // Re-render reservation history cards to update Review buttons
      if (typeof renderReservationHistory === "function") {
        // Only re-render if we have cached reservations
        const historyContainer = document.querySelector(
          ".booking-history-cards, .bookings-history-grid"
        );
        if (historyContainer && historyContainer.children.length > 0) {
          // Just update the buttons, don't reload entire history
          updateHistoryCardButtons();
        }
      }
    } else {
      console.error("Failed to load reviews:", data.message);
      showReviewsError();
    }
  } catch (error) {
    console.error("Error loading reviews:", error);
    showReviewsError();
  }
}

// Update review statistics in the UI
function updateReviewStats(stats) {
  const avgRatingEl = document.getElementById("avgRatingValue");
  const totalReviewsEl = document.getElementById("totalReviewsValue");
  const totalHelpfulEl = document.getElementById("totalHelpfulValue");

  if (avgRatingEl) avgRatingEl.textContent = stats.average_rating || "0.0";
  if (totalReviewsEl) totalReviewsEl.textContent = stats.total_reviews || 0;
  if (totalHelpfulEl) totalHelpfulEl.textContent = stats.total_helpful || 0;
}

// Render reviews list
function renderReviewsList(reviews) {
  const container = document.getElementById("reviewsList");
  if (!container) return;

  if (reviews.length === 0) {
    container.innerHTML = `
      <div class="no-reviews" style="text-align: center; padding: 60px 20px;">
        <i class="fas fa-star" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
        <h3 style="color: #666; margin-bottom: 10px;">No Reviews Yet</h3>
        <p style="color: #999; margin-bottom: 20px;">You haven't written any reviews yet. Complete a stay to share your experience!</p>
        ${
          reviewableReservations.length > 0
            ? `
          <button class="btn-primary" onclick="writeNewReview()">
            <i class="fas fa-pen"></i> Write Your First Review
          </button>
        `
            : ""
        }
      </div>
    `;
    return;
  }

  container.innerHTML = reviews
    .map((review) => createReviewCard(review))
    .join("");
}

// Create a review card HTML
function createReviewCard(review) {
  const stars = generateStarsHTML(review.rating);
  const formattedDate = formatReviewDate(review.created_at);
  const packageLabel = getPackageLabel(
    review.booking_type,
    review.package_type
  );

  // Get edit count info
  const editCount = parseInt(review.edit_count) || 0;
  const canEdit = editCount < 3;
  const editsRemaining = 3 - editCount;

  return `
    <div class="review-card" data-review-id="${review.review_id}">
      <div class="review-header">
        <div class="review-info">
          <h4>${escapeHtml(review.title)}</h4>
          <div class="review-rating">
            ${stars}
            <span>${review.rating}.0</span>
          </div>
        </div>
        <span class="review-date">${formattedDate}</span>
      </div>
      <p class="review-package" style="color: #667eea; font-size: 0.85rem; margin-bottom: 10px;">
        <i class="fas fa-bed"></i> ${packageLabel}
      </p>
      <p class="review-text">${escapeHtml(review.content)}</p>
      <div class="review-footer">
        <span class="helpful-count">
          <i class="fas fa-thumbs-up"></i> ${
            review.helpful_count
          } found this helpful
        </span>
        <div class="review-actions">
          ${
            canEdit
              ? `
            <button class="btn-text" onclick="editReview(${review.review_id})" title="${editsRemaining} edit(s) remaining">
              <i class="fas fa-edit"></i> Edit (${editsRemaining} left)
            </button>
          `
              : `
            <button class="btn-text" disabled style="color: #999; cursor: not-allowed;" title="Maximum edits reached">
              <i class="fas fa-ban"></i> Edit Limit Reached
            </button>
          `
          }
          <button class="btn-text text-danger" onclick="deleteReview(${
            review.review_id
          })">
            <i class="fas fa-trash"></i> Delete
          </button>
        </div>
      </div>
    </div>
  `;
}

// Generate star icons HTML based on rating
function generateStarsHTML(rating) {
  let stars = "";
  for (let i = 1; i <= 5; i++) {
    if (i <= rating) {
      stars += '<i class="fas fa-star"></i>';
    } else {
      stars += '<i class="far fa-star"></i>';
    }
  }
  return stars;
}

// Format review date
function formatReviewDate(dateString) {
  const date = new Date(dateString);
  const options = { year: "numeric", month: "short", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

// Get package label from booking type
function getPackageLabel(bookingType, packageType) {
  const types = {
    daytime: "Daytime Package",
    nighttime: "Nighttime Package",
    "22hours": "22 Hours Package",
    "venue-daytime": "Venue (Daytime)",
    "venue-nighttime": "Venue (Nighttime)",
    "venue-22hours": "Venue (22 Hours)",
  };
  return types[bookingType] || packageType || bookingType || "Resort Stay";
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// Show reviews error state
function showReviewsError() {
  const container = document.getElementById("reviewsList");
  if (container) {
    container.innerHTML = `
      <div class="reviews-error" style="text-align: center; padding: 40px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e74c3c; margin-bottom: 15px;"></i>
        <p style="color: #666;">Unable to load reviews. Please try again later.</p>
        <button class="btn-primary" onclick="loadUserReviews()" style="margin-top: 15px;">
          <i class="fas fa-refresh"></i> Retry
        </button>
      </div>
    `;
  }
}

// Update history card buttons after reviews are loaded
function updateHistoryCardButtons() {
  const historyCards = document.querySelectorAll(".booking-history-card");
  historyCards.forEach((card) => {
    const reservationId = card.dataset.reservationId;
    if (!reservationId) return;

    // Find the action buttons container
    const actionsContainer = card.querySelector(".history-card-actions");
    if (!actionsContainer) return;

    // Check if this reservation has been reviewed
    const reviewInfo = reviewedReservationsMap[reservationId];
    const hasReview = reviewedReservationIds.includes(parseInt(reservationId));

    // Only update if it's a completed reservation
    const statusBadge = card.querySelector(".status-badge");
    const isCompleted =
      statusBadge &&
      (statusBadge.textContent.toLowerCase().includes("completed") ||
        statusBadge.textContent.toLowerCase().includes("checked out"));

    if (!isCompleted) return;

    // Find the review button
    const reviewBtn = actionsContainer.querySelector(".review-btn");
    const editReviewBtn = actionsContainer.querySelector(".edit-review-btn");

    if (hasReview && reviewInfo) {
      // Remove old review button if exists
      if (reviewBtn) reviewBtn.remove();

      // Update or add edit review button
      if (!editReviewBtn) {
        const bookAgainBtn = actionsContainer.querySelector(".book-again-btn");
        const newBtn = document.createElement("button");

        if (reviewInfo.can_edit) {
          const editsRemaining = 3 - reviewInfo.edit_count;
          newBtn.className = "history-action-btn edit-review-btn";
          newBtn.onclick = function (e) {
            e.stopPropagation();
            editReview(reviewInfo.review_id);
          };
          newBtn.title = `${editsRemaining} edit(s) remaining`;
          newBtn.innerHTML = `<i class="fas fa-edit"></i> Edit Review (${editsRemaining} left)`;
        } else {
          newBtn.className = "history-action-btn edit-review-btn disabled";
          newBtn.disabled = true;
          newBtn.title = "Maximum edits reached";
          newBtn.innerHTML = '<i class="fas fa-ban"></i> Edit Limit Reached';
        }

        if (bookAgainBtn) {
          actionsContainer.insertBefore(newBtn, bookAgainBtn);
        } else {
          actionsContainer.appendChild(newBtn);
        }
      }
    }
  });
}

// Write new review - shows modal with available reservations
function writeNewReview() {
  if (reviewableReservations.length === 0) {
    showNotification("You don't have any completed stays to review.", "info");
    return;
  }

  const reservationOptions = reviewableReservations
    .map((res) => {
      const packageLabel = getPackageLabel(res.booking_type, res.package_type);
      const checkIn = formatReviewDate(res.check_in_date);
      const checkOut = formatReviewDate(res.check_out_date);
      return `<option value="${res.reservation_id}">${packageLabel} (${checkIn} - ${checkOut})</option>`;
    })
    .join("");

  const modalHTML = `
    <div id="newReviewModal" class="reservation-modal-overlay" onclick="closeNewReviewModal(event)">
      <div class="reservation-modal-content" style="max-width: 550px;" onclick="event.stopPropagation()">
        <div class="reservation-modal-header" style="background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);">
          <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h2 style="color: white; margin: 0;">
              <i class="fas fa-star"></i> Write a Review
            </h2>
            <button onclick="closeNewReviewModal()" class="modal-close-btn">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        
        <div class="reservation-modal-body">
          <form id="newReviewForm" onsubmit="submitNewReview(event)">
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                <i class="fas fa-calendar-check"></i> Select Reservation
              </label>
              <select name="reservation_id" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                <option value="">Choose a reservation to review...</option>
                ${reservationOptions}
              </select>
            </div>
            
            <div style="text-align: center; margin-bottom: 25px;">
              <p style="color: #666; margin-bottom: 15px;">How was your stay?</p>
              <div id="newReviewStarRating" style="font-size: 2.5rem; cursor: pointer;">
                <i class="far fa-star" data-rating="1" onclick="setNewReviewRating(1)"></i>
                <i class="far fa-star" data-rating="2" onclick="setNewReviewRating(2)"></i>
                <i class="far fa-star" data-rating="3" onclick="setNewReviewRating(3)"></i>
                <i class="far fa-star" data-rating="4" onclick="setNewReviewRating(4)"></i>
                <i class="far fa-star" data-rating="5" onclick="setNewReviewRating(5)"></i>
              </div>
              <p id="newReviewRatingText" style="color: #ffc107; font-weight: 600; margin-top: 10px;">Select a rating</p>
            </div>
            
            <input type="hidden" name="rating" id="newReviewRatingInput" value="0">
            
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                <i class="fas fa-heading"></i> Review Title
              </label>
              <input type="text" name="title" placeholder="Give your review a title..." required
                style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
            </div>
            
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                <i class="fas fa-comment-alt"></i> Your Review
              </label>
              <textarea name="content" rows="4" placeholder="Share your experience with other guests..." required
                style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
            </div>
            
            <button type="submit" id="newReviewSubmitBtn" class="modal-btn success" style="width: 100%;">
              <i class="fas fa-paper-plane"></i> Submit Review
            </button>
          </form>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);
  document.body.style.overflow = "hidden";
}

let newReviewSelectedRating = 0;
function setNewReviewRating(rating) {
  newReviewSelectedRating = rating;
  document.getElementById("newReviewRatingInput").value = rating;

  const stars = document.querySelectorAll("#newReviewStarRating i");
  const ratingTexts = ["", "Poor", "Fair", "Good", "Very Good", "Excellent"];

  stars.forEach((star, index) => {
    if (index < rating) {
      star.classList.remove("far");
      star.classList.add("fas");
      star.style.color = "#ffc107";
    } else {
      star.classList.remove("fas");
      star.classList.add("far");
      star.style.color = "#ddd";
    }
  });

  document.getElementById("newReviewRatingText").textContent =
    ratingTexts[rating];
}

function closeNewReviewModal(event) {
  if (event && event.target !== event.currentTarget) return;
  const modal = document.getElementById("newReviewModal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
    newReviewSelectedRating = 0;
  }
}

async function submitNewReview(event) {
  event.preventDefault();

  if (newReviewSelectedRating === 0) {
    showNotification("Please select a rating", "warning");
    return;
  }

  const form = event.target;
  const formData = new FormData(form);
  const submitBtn = document.getElementById("newReviewSubmitBtn");
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
  submitBtn.disabled = true;

  try {
    const response = await fetch("user/submit_review.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        reservation_id: formData.get("reservation_id"),
        rating: parseInt(formData.get("rating")),
        title: formData.get("title"),
        content: formData.get("content"),
      }),
    });

    const data = await response.json();

    if (data.success) {
      showNotification(
        "Thank you for your review! It has been submitted successfully.",
        "success"
      );
      closeNewReviewModal();
      loadUserReviews(); // Reload reviews
    } else {
      showNotification(data.message || "Failed to submit review", "error");
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }
  } catch (error) {
    console.error("Error submitting review:", error);
    showNotification("Failed to submit review. Please try again.", "error");
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
}

// Edit review
function editReview(reviewId) {
  const review = loadedReviewsData.find((r) => r.review_id == reviewId);
  if (!review) {
    showNotification("Review not found", "error");
    return;
  }

  // Check if edit limit has been reached
  const editCount = parseInt(review.edit_count) || 0;
  if (editCount >= 3) {
    showNotification(
      "You have reached the maximum number of edits (3) for this review.",
      "error"
    );
    return;
  }

  const editsRemaining = 3 - editCount;

  const modalHTML = `
    <div id="editReviewModal" class="reservation-modal-overlay" onclick="closeEditReviewModal(event)">
      <div class="reservation-modal-content" style="max-width: 550px;" onclick="event.stopPropagation()">
        <div class="reservation-modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
          <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h2 style="color: white; margin: 0;">
              <i class="fas fa-edit"></i> Edit Review
            </h2>
            <button onclick="closeEditReviewModal()" class="modal-close-btn">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        
        <div class="reservation-modal-body">
          <div style="background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%); padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
            <p style="color: #856404; margin: 0; font-size: 0.9rem;">
              <i class="fas fa-info-circle"></i> <strong>Edit Limit:</strong> You have <strong>${editsRemaining}</strong> edit(s) remaining for this review.
            </p>
          </div>
          
          <form id="editReviewForm" onsubmit="submitEditReview(event, ${reviewId})">
            <div style="text-align: center; margin-bottom: 25px;">
              <p style="color: #666; margin-bottom: 15px;">Update your rating</p>
              <div id="editReviewStarRating" style="font-size: 2.5rem; cursor: pointer;">
                ${[1, 2, 3, 4, 5]
                  .map(
                    (i) =>
                      `<i class="${
                        i <= review.rating ? "fas" : "far"
                      } fa-star" data-rating="${i}" onclick="setEditReviewRating(${i})" style="color: ${
                        i <= review.rating ? "#ffc107" : "#ddd"
                      };"></i>`
                  )
                  .join("")}
              </div>
              <p id="editReviewRatingText" style="color: #ffc107; font-weight: 600; margin-top: 10px;">${
                ["", "Poor", "Fair", "Good", "Very Good", "Excellent"][
                  review.rating
                ]
              }</p>
            </div>
            
            <input type="hidden" name="rating" id="editReviewRatingInput" value="${
              review.rating
            }">
            
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                <i class="fas fa-heading"></i> Review Title
              </label>
              <input type="text" name="title" value="${escapeHtml(
                review.title
              )}" required
                style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
            </div>
            
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                <i class="fas fa-comment-alt"></i> Your Review
              </label>
              <textarea name="content" rows="4" required
                style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;">${escapeHtml(
                  review.content
                )}</textarea>
            </div>
            
            <button type="submit" id="editReviewSubmitBtn" class="modal-btn success" style="width: 100%;">
              <i class="fas fa-save"></i> Update Review
            </button>
          </form>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);
  document.body.style.overflow = "hidden";
  editReviewSelectedRating = review.rating;
}

let editReviewSelectedRating = 0;
function setEditReviewRating(rating) {
  editReviewSelectedRating = rating;
  document.getElementById("editReviewRatingInput").value = rating;

  const stars = document.querySelectorAll("#editReviewStarRating i");
  const ratingTexts = ["", "Poor", "Fair", "Good", "Very Good", "Excellent"];

  stars.forEach((star, index) => {
    if (index < rating) {
      star.classList.remove("far");
      star.classList.add("fas");
      star.style.color = "#ffc107";
    } else {
      star.classList.remove("fas");
      star.classList.add("far");
      star.style.color = "#ddd";
    }
  });

  document.getElementById("editReviewRatingText").textContent =
    ratingTexts[rating];
}

function closeEditReviewModal(event) {
  if (event && event.target !== event.currentTarget) return;
  const modal = document.getElementById("editReviewModal");
  if (modal) {
    modal.remove();
    document.body.style.overflow = "";
  }
}

async function submitEditReview(event, reviewId) {
  event.preventDefault();

  if (editReviewSelectedRating === 0) {
    showNotification("Please select a rating", "warning");
    return;
  }

  const form = event.target;
  const formData = new FormData(form);
  const submitBtn = document.getElementById("editReviewSubmitBtn");
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
  submitBtn.disabled = true;

  try {
    const response = await fetch("user/update_review.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        review_id: reviewId,
        rating: parseInt(formData.get("rating")),
        title: formData.get("title"),
        content: formData.get("content"),
      }),
    });

    const data = await response.json();

    if (data.success) {
      // Show message with remaining edits info if available
      let message = data.message || "Review updated successfully!";
      showNotification(message, "success");
      closeEditReviewModal();
      loadUserReviews(); // Reload reviews to update edit counts
    } else {
      showNotification(data.message || "Failed to update review", "error");
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }
  } catch (error) {
    console.error("Error updating review:", error);
    showNotification("Failed to update review. Please try again.", "error");
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
}

// Delete review
async function deleteReview(reviewId) {
  if (
    !confirm(
      "Are you sure you want to delete this review? This action cannot be undone."
    )
  ) {
    return;
  }

  showNotification("Deleting review...", "info");

  try {
    const response = await fetch("user/delete_review.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ review_id: reviewId }),
    });

    const data = await response.json();

    if (data.success) {
      showNotification("Review deleted successfully.", "success");
      // Remove from UI immediately
      const reviewCard = document.querySelector(
        `[data-review-id="${reviewId}"]`
      );
      if (reviewCard) {
        reviewCard.style.transition = "opacity 0.3s, transform 0.3s";
        reviewCard.style.opacity = "0";
        reviewCard.style.transform = "translateX(-20px)";
        setTimeout(() => {
          reviewCard.remove();
          // Check if we need to show empty state
          if (loadedReviewsData.length <= 1) {
            loadUserReviews();
          }
        }, 300);
      }
      // Remove from data array
      loadedReviewsData = loadedReviewsData.filter(
        (r) => r.review_id != reviewId
      );
      // Update stats
      updateReviewStats({
        total_reviews: loadedReviewsData.length,
        average_rating:
          loadedReviewsData.length > 0
            ? (
                loadedReviewsData.reduce((sum, r) => sum + r.rating, 0) /
                loadedReviewsData.length
              ).toFixed(1)
            : 0,
        total_helpful: loadedReviewsData.reduce(
          (sum, r) => sum + r.helpful_count,
          0
        ),
      });
    } else {
      showNotification(data.message || "Failed to delete review", "error");
    }
  } catch (error) {
    console.error("Error deleting review:", error);
    showNotification("Failed to delete review. Please try again.", "error");
  }
}

// Make functions globally available
window.loadUserReviews = loadUserReviews;
window.writeNewReview = writeNewReview;
window.setNewReviewRating = setNewReviewRating;
window.closeNewReviewModal = closeNewReviewModal;
window.submitNewReview = submitNewReview;
window.editReview = editReview;
window.setEditReviewRating = setEditReviewRating;
window.closeEditReviewModal = closeEditReviewModal;
window.submitEditReview = submitEditReview;
window.deleteReview = deleteReview;

// ===== RESERVATION SYSTEM =====
// Global reservation state
window.reservationData = {
  bookingType: null,
  packageType: null,
  checkInDate: null,
  checkOutDate: null,
  checkInTime: null,
  checkOutTime: null,
  duration: null,
  basePrice: 0,
  totalAmount: 0,
  downpayment: 0,
  balance: 0,
};

// Booking type configurations
window.bookingTypes = {
  daytime: {
    label: "DAYTIME PACKAGE",
    checkInTime: "9:00 AM",
    checkOutTime: "5:00 PM",
    durationLabel: "day(s)",
    prices: {
      daytime: 6000,
      nighttime: 10000,
      "22hours": 18000,
      venue: 6000,
    },
  },
  nighttime: {
    label: "NIGHTTIME PACKAGE",
    checkInTime: "7:00 PM",
    checkOutTime: "7:00 AM",
    durationLabel: "night(s)",
    prices: {
      daytime: 6000,
      nighttime: 10000,
      "22hours": 18000,
      venue: 10000,
    },
  },
  "22hours": {
    label: "22 HOURS PACKAGE",
    checkInTime: "2:00 PM",
    checkOutTime: "12:00 NN",
    durationLabel: "session(s)",
    prices: {
      daytime: 6000,
      nighttime: 10000,
      "22hours": 18000,
      venue: 18000,
    },
  },
  "venue-daytime": {
    label: "VENUE FOR ALL OCCASIONS - DAYTIME",
    checkInTime: "9:00 AM",
    checkOutTime: "5:00 PM",
    durationLabel: "day(s)",
    prices: {
      venue: 6000,
    },
  },
  "venue-nighttime": {
    label: "VENUE FOR ALL OCCASIONS - NIGHTTIME",
    checkInTime: "7:00 PM",
    checkOutTime: "7:00 AM",
    durationLabel: "night(s)",
    prices: {
      venue: 10000,
    },
  },
  "venue-22hours": {
    label: "VENUE FOR ALL OCCASIONS - 22 HOURS",
    checkInTime: "2:00 PM",
    checkOutTime: "12:00 NN",
    durationLabel: "session(s)",
    prices: {
      venue: 18000,
    },
  },
};

// Select booking type and show room selection
function selectBookingType(type) {
  if (!window.bookingTypes[type]) {
    showNotification("Invalid booking type", "error");
    return;
  }

  // Store booking type
  window.reservationData.bookingType = type;
  window.reservationData.checkInTime = window.bookingTypes[type].checkInTime;
  window.reservationData.checkOutTime = window.bookingTypes[type].checkOutTime;

  // Update prices in room packages
  const prices = window.bookingTypes[type].prices;
  document.getElementById("daytime-price").textContent =
    prices["daytime"].toLocaleString();
  document.getElementById("nighttime-price").textContent =
    prices["nighttime"].toLocaleString();
  document.getElementById("22hours-price").textContent =
    prices["22hours"].toLocaleString();

  // Update price period labels
  const periodLabel =
    window.bookingTypes[type].durationLabel === "day(s)"
      ? "per day"
      : "per night";
  document.querySelectorAll(".price-period").forEach((el) => {
    el.textContent = periodLabel;
  });

  // Hide step 1, show step 2
  document.getElementById("step1").style.display = "none";
  document.getElementById("step2").style.display = "block";

  showNotification(
    `${window.bookingTypes[type].label} booking selected`,
    "success"
  );
}

// Open venue time slot selector
function openVenueTimeSelector() {
  const modalHTML = `
    <div id="venueTimeModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000;">
      <div style="background: white; padding: 30px; border-radius: 16px; max-width: 600px; width: 90%;">
        <h2 style="margin-top: 0; color: #1e293b;">Select Time Slot for Venue</h2>
        <p style="color: #64748b; margin-bottom: 25px;">Choose your preferred time slot for the event venue</p>
        
        <div style="display: grid; gap: 15px;">
          <div onclick="selectVenueTime('venue-daytime')" style="cursor: pointer; padding: 20px; border: 2px solid #ffd93d; border-radius: 12px; background: linear-gradient(135deg, #fff9e6 0%, #fff 100%); transition: all 0.3s;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 5px 0; color: #f59e0b;">☀️ Daytime</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9em;">9:00 AM - 5:00 PM</p>
              </div>
              <div style="font-size: 1.5em; font-weight: bold; color: #1e293b;">₱6,000</div>
            </div>
          </div>
          
          <div onclick="selectVenueTime('venue-nighttime')" style="cursor: pointer; padding: 20px; border: 2px solid #667eea; border-radius: 12px; background: linear-gradient(135deg, #e6e9ff 0%, #fff 100%); transition: all 0.3s;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 5px 0; color: #667eea;">🌙 Nighttime</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9em;">7:00 PM - 7:00 AM</p>
              </div>
              <div style="font-size: 1.5em; font-weight: bold; color: #1e293b;">₱10,000</div>
            </div>
          </div>
          
          <div onclick="selectVenueTime('venue-22hours')" style="cursor: pointer; padding: 20px; border: 2px solid #f093fb; border-radius: 12px; background: linear-gradient(135deg, #ffe6f7 0%, #fff 100%); transition: all 0.3s;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 5px 0; color: #f093fb;">⏰ 22 Hours</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9em;">2:00 PM - 12:00 NN (Next Day)</p>
              </div>
              <div style="font-size: 1.5em; font-weight: bold; color: #1e293b;">₱18,000</div>
            </div>
          </div>
        </div>
        
        <button onclick="closeVenueTimeModal()" style="margin-top: 20px; width: 100%; padding: 12px; background: #e2e8f0; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; color: #475569;">Cancel</button>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);
}

function closeVenueTimeModal() {
  const modal = document.getElementById("venueTimeModal");
  if (modal) modal.remove();
}

function selectVenueTime(venueType) {
  closeVenueTimeModal();
  selectRoomPackageDirect(venueType);
}

// Direct package selection (combines booking type and package in one step)
// Open inclusions modal
function openInclusionsModal(packageType) {
  const modal = document.getElementById("inclusionsModal");
  const modalTitle = document.getElementById("inclusionsModalTitle");
  const modalContent = document.getElementById("inclusionsModalContent");

  // Get the content from the hidden div
  const content = document.getElementById(`inclusions-${packageType}`);

  // Set title based on package type
  const titles = {
    daytime: "Daytime Package - Full Details",
    nighttime: "Nighttime Package - Full Details",
    "22hours": "22 Hours Package - Full Details",
    venue: "Venue for All Occasions - Full Details",
  };

  modalTitle.innerHTML = `<i class="fas fa-list-check"></i> ${
    titles[packageType] || "Package Details"
  }`;

  // Clone the content and display it in modal
  modalContent.innerHTML = content.innerHTML;

  // Show modal first
  modal.style.display = "flex";
  document.body.style.overflow = "hidden";

  // Add View Image buttons after a frame to prevent blocking
  requestAnimationFrame(function () {
    try {
      if (typeof window.addButtonsAndStyle === "function") {
        window.addButtonsAndStyle();
      }
    } catch (error) {}
  });
}

// Close inclusions modal
function closeInclusionsModal() {
  const modal = document.getElementById("inclusionsModal");
  modal.style.display = "none";
  document.body.style.overflow = "auto";
}

// Close modal when clicking outside
document.addEventListener("click", function (event) {
  const modal = document.getElementById("inclusionsModal");
  if (event.target === modal) {
    closeInclusionsModal();
  }
});

// Initialize date picker with unavailable dates
let flatpickrInstance = null;
let currentUnavailableDates = []; // Store for validation
let baseUnavailableDates = []; // Original unavailable dates from server

// Calculate which dates should be disabled based on duration
// SIMPLIFIED: Only block directly booked dates
// The server handles complex overlap validation
function calculateDisabledDates(duration) {
  if (!baseUnavailableDates || baseUnavailableDates.length === 0) {
    return [];
  }

  // Just return the base unavailable dates - server handles overlap logic
  return [...baseUnavailableDates];
}

// Refresh the calendar when duration changes
function refreshCalendarForDuration() {
  const durationSelect = document.getElementById("duration");
  const checkInDateInput = document.getElementById("checkInDate");
  if (!durationSelect || !checkInDateInput || !flatpickrInstance) return;

  const duration = parseInt(durationSelect.value) || 1;
  const newDisabledDates = calculateDisabledDates(duration);
  currentUnavailableDates = newDisabledDates;

  console.log("📅 Refreshing calendar for duration:", duration);
  console.log("📊 Disabled dates count:", newDisabledDates.length);

  // Save current selected date
  const currentSelectedDate = flatpickrInstance.selectedDates[0];
  let savedDateStr = null;
  if (currentSelectedDate) {
    const year = currentSelectedDate.getFullYear();
    const month = String(currentSelectedDate.getMonth() + 1).padStart(2, "0");
    const day = String(currentSelectedDate.getDate()).padStart(2, "0");
    savedDateStr = `${year}-${month}-${day}`;
  }

  // Destroy current instance
  flatpickrInstance.destroy();

  // Reinitialize with new disabled dates
  flatpickrInstance = flatpickr(checkInDateInput, {
    minDate: "today",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "F j, Y",
    placeholder: "Select a date",
    disable: newDisabledDates,
    defaultDate:
      savedDateStr && !newDisabledDates.includes(savedDateStr)
        ? savedDateStr
        : null,
    onReady: function (selectedDates, dateStr, instance) {
      if (instance.altInput) {
        instance.altInput.setAttribute(
          "placeholder",
          "Select a date (mm/dd/yyyy)"
        );
      }
    },
    onChange: function (selectedDates, dateStr, instance) {
      console.log("📅 Date selected:", dateStr);
      if (typeof updatePriceSummary === "function") {
        updatePriceSummary();
      }
      if (typeof validateDateRangeConflict === "function") {
        validateDateRangeConflict();
      }
    },
    onDayCreate: function (dObj, dStr, fp, dayElem) {
      const year = dayElem.dateObj.getFullYear();
      const month = String(dayElem.dateObj.getMonth() + 1).padStart(2, "0");
      const day = String(dayElem.dateObj.getDate()).padStart(2, "0");
      const dateStr = `${year}-${month}-${day}`;

      if (currentUnavailableDates.includes(dateStr)) {
        dayElem.classList.add("unavailable-date");

        if (baseUnavailableDates.includes(dateStr)) {
          // Direct booking - show X indicator
          dayElem.innerHTML += '<span class="unavailable-indicator">✕</span>';
        } else {
          dayElem.classList.add("conflict-date");
          dayElem.innerHTML +=
            '<span class="unavailable-indicator" style="color: #f59e0b;">⚠</span>';
        }
      }
    },
  });

  // Notify user if their date became unavailable
  if (savedDateStr && newDisabledDates.includes(savedDateStr)) {
    showNotification(
      "Your selected date is no longer available for this duration. Please choose another date.",
      "warning"
    );
  }
}

async function fetchUnavailableDates(bookingType) {
  try {
    const response = await fetch("user/get_unavailable_dates.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ booking_type: bookingType }),
    });

    const data = await response.json();

    if (data.success) {
      return data.unavailable_dates || [];
    }
    return [];
  } catch (error) {
    console.error("Error fetching unavailable dates:", error);
    return [];
  }
}

async function initializeDatePicker(bookingType) {
  console.log("🗓️ Initializing date picker for booking type:", bookingType);

  const checkInDateInput = document.getElementById("checkInDate");

  if (!checkInDateInput) {
    console.error("❌ Check-in date input not found");
    return;
  }

  // Destroy existing instance if any
  if (flatpickrInstance) {
    console.log("🗑️ Destroying previous flatpickr instance");
    flatpickrInstance.destroy();
    flatpickrInstance = null;
  }

  // Fetch unavailable dates for THIS specific booking type
  console.log("📅 Fetching unavailable dates for:", bookingType);
  const unavailableDates = await fetchUnavailableDates(bookingType);
  baseUnavailableDates = unavailableDates; // Store original dates from server

  // Determine duration based on booking type
  // Daytime = 1 day (same day checkout)
  // Nighttime = 2 days (spans overnight)
  // 22 Hours = 2 days (2PM to 12NN next day)
  let duration = 1;
  if (
    bookingType === "nighttime" ||
    bookingType === "22hours" ||
    bookingType === "venue-nighttime" ||
    bookingType === "venue-22hours"
  ) {
    duration = 2;
  }

  // Update the hidden duration input
  const durationInput = document.getElementById("duration");
  if (durationInput) {
    durationInput.value = duration;
    console.log(
      "📊 Duration set to:",
      duration,
      "for booking type:",
      bookingType
    );
  }

  currentUnavailableDates = calculateDisabledDates(duration);

  console.log(
    "✅ Unavailable dates fetched for " + bookingType + ":",
    unavailableDates
  );
  console.log("📊 Base dates blocked:", unavailableDates.length);
  console.log(
    "📊 Total dates blocked (with duration " + duration + "):",
    currentUnavailableDates.length
  );

  // Initialize Flatpickr with disabled dates
  flatpickrInstance = flatpickr(checkInDateInput, {
    minDate: "today",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "F j, Y",
    placeholder: "Select a date",
    disable: currentUnavailableDates,
    onReady: function (selectedDates, dateStr, instance) {
      // Set placeholder on the alt input
      if (instance.altInput) {
        instance.altInput.setAttribute(
          "placeholder",
          "Select a date (mm/dd/yyyy)"
        );
      }
    },
    onChange: function (selectedDates, dateStr, instance) {
      console.log("📅 Date selected:", dateStr);
      // Update price summary when date changes
      if (typeof updatePriceSummary === "function") {
        updatePriceSummary();
      }
      // Validate date range for conflicts
      if (typeof validateDateRangeConflict === "function") {
        validateDateRangeConflict();
      }
    },
    onDayCreate: function (dObj, dStr, fp, dayElem) {
      // Add visual styling to unavailable dates
      // Use local date string to avoid timezone issues
      const year = dayElem.dateObj.getFullYear();
      const month = String(dayElem.dateObj.getMonth() + 1).padStart(2, "0");
      const day = String(dayElem.dateObj.getDate()).padStart(2, "0");
      const dateStr = `${year}-${month}-${day}`;

      // Check if this date is in the current disabled list
      if (currentUnavailableDates.includes(dateStr)) {
        dayElem.classList.add("unavailable-date");

        // Different indicator for direct vs conflict dates
        if (baseUnavailableDates.includes(dateStr)) {
          // Direct booking - show X indicator
          dayElem.innerHTML += '<span class="unavailable-indicator">✕</span>';
        } else {
          // This date is blocked due to duration conflict
          dayElem.classList.add("conflict-date");
          dayElem.innerHTML +=
            '<span class="unavailable-indicator" style="color: #f59e0b;">⚠</span>';
        }
      }
    },
  });

  console.log(
    "✅ Flatpickr initialized with",
    currentUnavailableDates.length,
    "disabled dates"
  );

  // Attach duration change listener HERE (after Step 2 is visible)
  const durationSelectEl = document.getElementById("duration");
  if (durationSelectEl) {
    // Remove any existing listener first to avoid duplicates
    durationSelectEl.removeEventListener("change", handleDurationChange);
    durationSelectEl.addEventListener("change", handleDurationChange);
    console.log("✅ Duration change listener attached");
  }
}

// Handler for duration change - separated so we can remove/add listener
function handleDurationChange() {
  console.log("🔄 Duration changed, refreshing calendar...");
  updatePriceSummary();
  refreshCalendarForDuration();
  validateDateRangeConflict();
}

// Go back to package selection (Step 1)
function goBackToRooms() {
  const step1 = document.getElementById("step1");
  const step2 = document.getElementById("step2");

  if (step1 && step2) {
    // Hide step 2, show step 1
    step2.style.display = "none";
    step1.style.display = "block";

    // Reset form data
    if (window.reservationData) {
      window.reservationData.bookingType = null;
      window.reservationData.packageType = null;
    }

    // Scroll to top of the reservation section
    const reservationSection = document.getElementById("reservation-section");
    if (reservationSection) {
      reservationSection.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    if (typeof showNotification === "function") {
      showNotification("Returned to package selection", "info");
    }
  } else {
  }
}

function selectRoomPackageDirect(packageType) {
  // Package type is the same as booking type in the new structure
  const type = packageType;

  // Store booking type and package type
  window.reservationData.bookingType = type;
  window.reservationData.packageType = packageType;
  window.reservationData.checkInTime = window.bookingTypes[type].checkInTime;
  window.reservationData.checkOutTime = window.bookingTypes[type].checkOutTime;
  window.reservationData.basePrice =
    window.bookingTypes[type].prices[
      packageType.includes("venue") ? "venue" : packageType
    ];

  // Update summary
  const packageNames = {
    daytime: "Daytime Package",
    nighttime: "Nighttime Package",
    "22hours": "22 Hours Package",
    "venue-daytime": "Venue for All Occasions - Daytime",
    "venue-nighttime": "Venue for All Occasions - Nighttime",
    "venue-22hours": "Venue for All Occasions - 22 Hours",
  };

  document.getElementById("summaryBookingType").textContent =
    window.bookingTypes[type].label;
  document.getElementById("summaryPackage").textContent =
    packageNames[packageType];
  document.getElementById("summaryCheckInTime").textContent =
    window.bookingTypes[type].checkInTime;
  document.getElementById("summaryCheckOutTime").textContent =
    window.bookingTypes[type].checkOutTime;

  // Update price summary (single session booking)
  updatePriceSummary();

  // Hide step 1, show step 2 (booking details)
  document.getElementById("step1").style.display = "none";
  document.getElementById("step2").style.display = "block";

  // Initialize date picker with unavailable dates
  initializeDatePicker(type);

  showNotification(`${packageNames[packageType]} selected`, "success");
}

// Select room package and show booking form (legacy function, kept for compatibility)
function selectRoomPackage(packageType) {
  const type = window.reservationData.bookingType;
  if (!type) {
    showNotification("Please select a booking type first", "error");
    return;
  }

  // Store package type and price
  window.reservationData.packageType = packageType;
  window.reservationData.basePrice =
    window.bookingTypes[type].prices[packageType];

  // Update summary
  const packageNames = {
    daytime: "Daytime Package",
    nighttime: "Nighttime Package",
    "22hours": "22 Hours Package",
  };

  document.getElementById("summaryBookingType").textContent =
    window.bookingTypes[type].label;
  document.getElementById("summaryPackage").textContent =
    packageNames[packageType];
  document.getElementById("summaryCheckInTime").textContent =
    window.bookingTypes[type].checkInTime;
  document.getElementById("summaryCheckOutTime").textContent =
    window.bookingTypes[type].checkOutTime;

  // Update price summary (single session booking)
  updatePriceSummary();

  // Hide step 1, show step 2
  document.getElementById("step1").style.display = "none";
  document.getElementById("step2").style.display = "block";

  // Initialize date picker with unavailable dates
  initializeDatePicker(type);

  showNotification(`${packageNames[packageType]} selected`, "success");
}

// Go back to specific step
function goBackToStep(stepNumber) {
  // Hide all steps
  document.querySelectorAll(".booking-step").forEach((step) => {
    step.style.display = "none";
  });

  // Show requested step
  document.getElementById(`step${stepNumber}`).style.display = "block";
}

// Update price summary - Single session booking (duration = 1)
function updatePriceSummary() {
  const duration = 1; // Always 1 for single session booking
  const basePrice = window.reservationData.basePrice;
  const type = window.reservationData.bookingType;

  const totalAmount = basePrice; // No multiplication, single session only
  const downpayment = 1000;
  const balance = totalAmount - downpayment;

  // Store values
  window.reservationData.duration = duration;
  window.reservationData.totalAmount = totalAmount;
  window.reservationData.downpayment = downpayment;
  window.reservationData.balance = balance;

  // Update UI
  document.getElementById("priceTotal").textContent =
    "₱" + totalAmount.toLocaleString("en-PH", { minimumFractionDigits: 2 });
  document.getElementById("priceDownpayment").textContent =
    "₱" + downpayment.toLocaleString("en-PH", { minimumFractionDigits: 2 });
  document.getElementById("priceBalance").textContent =
    "₱" + balance.toLocaleString("en-PH", { minimumFractionDigits: 2 });
}

// Validate that selected date + duration doesn't conflict with unavailable dates
// Validate that selected check-in date is available
// Server handles complex date range overlap validation
function validateDateRangeConflict() {
  const checkInDateInput = document.getElementById("checkInDate");
  const submitBtn = document.querySelector(".btn-submit-v2");

  if (!checkInDateInput) return true;

  const checkInDate = checkInDateInput.value;

  if (!checkInDate) return true; // No date selected yet

  // Only check if the check-in date itself is blocked
  // Server handles the actual overlap logic for multi-day bookings
  const isBlocked = currentUnavailableDates.includes(checkInDate);

  // Show/hide conflict warning
  let conflictWarning = document.getElementById("dateConflictWarning");

  if (isBlocked) {
    // Create warning if doesn't exist
    if (!conflictWarning) {
      conflictWarning = document.createElement("div");
      conflictWarning.id = "dateConflictWarning";
      conflictWarning.style.cssText = `
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        color: white;
        padding: 12px 16px;
        border-radius: 10px;
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
        animation: shake 0.5s ease-out;
      `;

      // Insert after date input
      const dateCard = checkInDateInput.closest(".form-card");
      if (dateCard) {
        dateCard.appendChild(conflictWarning);
      }
    }

    const date = new Date(checkInDate + "T00:00:00");
    const formattedDate = date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
    });

    conflictWarning.innerHTML = `
      <i class="fas fa-exclamation-triangle"></i>
      <div>
        <strong>Date Unavailable!</strong><br>
        <span style="font-size: 0.85rem;">${formattedDate} is already booked. Please select a different date.</span>
      </div>
    `;
    conflictWarning.style.display = "flex";

    // Disable submit button
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = "0.5";
      submitBtn.style.cursor = "not-allowed";
    }

    return false;
  } else {
    // Remove warning if exists
    if (conflictWarning) {
      conflictWarning.style.display = "none";
    }

    // Enable submit button
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.style.opacity = "1";
      submitBtn.style.cursor = "pointer";
    }

    return true;
  }
}

// Setup reservation form listeners
document.addEventListener("DOMContentLoaded", function () {
  // Duration change listener
  const durationSelect = document.getElementById("duration");
  if (durationSelect) {
    durationSelect.addEventListener("change", function () {
      updatePriceSummary();
      refreshCalendarForDuration(); // Refresh calendar to show new conflicts
      validateDateRangeConflict();
    });
  }

  // Also validate when date changes
  const checkInDateInput = document.getElementById("checkInDate");
  if (checkInDateInput) {
    checkInDateInput.addEventListener("change", validateDateRangeConflict);
  }

  // Payment method radio button sync with hidden form input
  const paymentRadios = document.querySelectorAll(
    'input[name="paymentMethod"][type="radio"]'
  );
  const paymentMethodInput = document.getElementById("paymentMethodInput");

  paymentRadios.forEach((radio) => {
    radio.addEventListener("change", function () {
      if (paymentMethodInput) {
        paymentMethodInput.value = this.value;
        console.log("Payment method updated to:", this.value);
      }
    });
  });

  // Reservation form submission
  const reservationForm = document.getElementById("reservationForm");
  if (reservationForm) {
    reservationForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      // Validate booking data
      if (
        !window.reservationData.bookingType ||
        !window.reservationData.packageType
      ) {
        showNotification("Please complete all booking steps", "error");
        return;
      }

      // Collect form data
      const formData = new FormData(reservationForm);
      const checkInDate = formData.get("checkInDate");
      const duration = parseInt(formData.get("duration"));
      const groupType = formData.get("groupType");
      const specialRequests = formData.get("specialRequests");
      const paymentMethod = formData.get("paymentMethod");
      const agreeTerms = formData.get("agreeTerms"); // checkbox returns 'on' if checked, null if not

      // Validate required fields
      if (!checkInDate) {
        showNotification("Please select a check-in date", "error");
        return;
      }

      if (!duration || isNaN(duration) || duration < 1) {
        showNotification("Please select a valid duration", "error");
        return;
      }

      if (!groupType) {
        showNotification("Please select group type", "error");
        return;
      }

      if (!paymentMethod) {
        showNotification("Please select a payment method", "error");
        return;
      }

      if (!agreeTerms) {
        showNotification("Please agree to the terms and conditions", "error");
        return;
      }

      // Calculate check-out date based on booking type
      // Daytime: same day (check-in 8AM, check-out 6PM) = +0 days
      // Nighttime: next day (check-in 8PM, check-out 6AM) = +1 day
      // 22 Hours: next day (check-in 2PM, check-out 12NN) = +1 day
      const bookingType = window.reservationData.bookingType;
      const checkIn = new Date(checkInDate);
      const checkOut = new Date(checkIn);

      // Calculate days to add for check-out date
      let daysToAdd = 0;
      if (bookingType === "daytime" || bookingType === "venue-daytime") {
        daysToAdd = 0; // Same day checkout
      } else {
        daysToAdd = 1; // Next day checkout for nighttime and 22hours
      }

      checkOut.setDate(checkOut.getDate() + daysToAdd);
      const checkOutDate = checkOut.toISOString().split("T")[0];

      // Format package type with booking suffix (e.g., 'all-rooms-night' or 'all-rooms-day')
      const packageBase = window.reservationData.packageType;

      // For venue packages, the booking type already contains the time slot (venue-daytime, venue-nighttime, venue-22hours)
      // so we use the booking type as is and add the appropriate suffix for package_type
      let packageType;
      if (bookingType.startsWith("venue-")) {
        // For venue packages, format: venue-daytime-day, venue-nighttime-night, venue-22hours-night
        const packageSuffix =
          bookingType === "venue-daytime" ? "-day" : "-night";
        packageType = packageBase + packageSuffix;
      } else {
        // For regular packages
        const packageSuffix = bookingType === "daytime" ? "-day" : "-night";
        packageType = packageBase + packageSuffix;
      }

      // Prepare reservation data
      const reservationPayload = {
        booking_type: bookingType,
        package_type: packageType,
        check_in_date: checkInDate,
        check_out_date: checkOutDate,
        number_of_days:
          bookingType === "daytime" || bookingType === "venue-daytime"
            ? duration
            : null,
        number_of_nights:
          bookingType !== "daytime" && bookingType !== "venue-daytime"
            ? duration
            : null,
        group_type: groupType,
        special_requests: specialRequests,
        payment_method: paymentMethod,
      };

      // Show loading
      const submitBtn = reservationForm.querySelector(".btn-submit-v2");
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Processing...';
      submitBtn.disabled = true;

      try {
        // Submit reservation
        const response = await fetch("user/make_reservation.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(reservationPayload),
        });

        // Try to parse response as JSON
        let result;
        const responseText = await response.text();
        try {
          result = JSON.parse(responseText);
        } catch (parseError) {
          throw new Error(
            "Server returned invalid JSON: " + responseText.substring(0, 100)
          );
        }

        if (result.success) {
          showNotification("Reservation created successfully!", "success");

          // Check if payment method is GCash - redirect to PayMongo payment
          if (paymentMethod === "gcash") {
            // Create PayMongo payment intent for GCash
            setTimeout(async () => {
              try {
                const paymentResponse = await fetch(
                  "user/create_payment_intent.php",
                  {
                    method: "POST",
                    headers: {
                      "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                      reservation_id: result.reservation_id,
                    }),
                  }
                );

                // Log response status for debugging
                console.log("Payment response status:", paymentResponse.status);

                const paymentResult = await paymentResponse.json();
                console.log("Payment result:", paymentResult);

                if (paymentResult.success && paymentResult.checkout_url) {
                  // Log the checkout URL
                  console.log(
                    "Redirecting to PayMongo URL:",
                    paymentResult.checkout_url
                  );

                  // Show payment info before redirecting
                  showNotification("Redirecting to GCash payment...", "info");

                  // Redirect to PayMongo checkout page (pm.link URL)
                  setTimeout(() => {
                    window.location.href = paymentResult.checkout_url;
                  }, 1500);
                } else {
                  throw new Error(
                    paymentResult.message || "Failed to create payment"
                  );
                }
              } catch (paymentError) {
                showNotification(
                  "Payment setup failed. Please try again from My Reservations.",
                  "error"
                );
                console.error("Payment error:", paymentError);
                console.error("Error details:", paymentError.message);

                // Still show reservation success but redirect to bookings
                setTimeout(() => {
                  alert(
                    `Reservation Created!\n\nReservation ID: ${result.reservation_id}\n\nPayment setup encountered an issue. Please complete payment from My Reservations section.`
                  );

                  // Reset form and go to bookings
                  reservationForm.reset();
                  window.reservationData = {
                    bookingType: null,
                    packageType: null,
                    basePrice: 0,
                    totalAmount: 0,
                    downpayment: 0,
                    balance: 0,
                  };
                  goBackToStep(1);
                  showSection("bookings-history");
                  updateActiveNavigation("bookings-history");
                }, 2000);
              }
            }, 1000);
          } else {
            // For OTC payment, show success details
            setTimeout(() => {
              alert(
                `Reservation Confirmed!\n\nReservation ID: ${
                  result.reservation_id
                }\nTotal Amount: ₱${result.total_amount.toLocaleString(
                  "en-PH",
                  {
                    minimumFractionDigits: 2,
                  }
                )}\nDownpayment: ₱${result.downpayment_amount.toLocaleString(
                  "en-PH",
                  { minimumFractionDigits: 2 }
                )}\n\nPlease pay the downpayment at the resort reception to confirm your booking.\n\nReference: ${
                  result.reservation_id
                }`
              );

              // Reset form and go back to step 1
              reservationForm.reset();
              window.reservationData = {
                bookingType: null,
                packageType: null,
                basePrice: 0,
                totalAmount: 0,
                downpayment: 0,
                balance: 0,
              };
              goBackToStep(1);

              // Switch to booking history
              showSection("bookings-history");
              updateActiveNavigation("bookings-history");
            }, 1500);
          }
        } else {
          showNotification(
            result.message || "Failed to create reservation",
            "error"
          );

          // Show detailed error in alert for debugging
          if (result.error_type) {
          }
        }
      } catch (error) {
        showNotification(
          "Network error. Please check console for details.",
          "error"
        );
      } finally {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    });
  }
});

// Show booking policy modal
function showBookingPolicy() {
  const policyHTML = `
    <div class="policy-modal">
      <h2>Booking Policy</h2>
      <div class="policy-content">
        <h3>General Policies</h3>
        <ul>
          <li><strong>First to Pay, First to Reserve Policy</strong></li>
          <li><strong>₱1,000 Downpayment Required</strong> to confirm reservation</li>
          <li>Remaining balance must be paid <strong>before check-in time</strong></li>
          <li>Reservation fee is <strong>non-refundable/transferable</strong></li>
          <li>We only do <strong>rebooking within 3 months</strong></li>
          <li>Rebooking allowed <strong>7 days prior</strong> to schedule date</li>
          <li>Non-appearance on schedule date will be <strong>forfeited</strong></li>
        </ul>
        
        <h3>Security & Charges</h3>
        <ul>
          <li><strong>₱2,000 Security Bond</strong> upon check-in (refundable if no damage)</li>
          <li>Additional charges apply for exceeding hours</li>
        </ul>
        
        <h3>House Rules</h3>
        <ul>
          <li>Noise beyond 10:00 PM may be disruptive - please be considerate</li>
          <li>Videoke/loudspeaker: <strong>9AM to 10PM only</strong></li>
          <li>Penalty of <strong>₱2,000</strong> for excessive noise/unruly behavior</li>
          <li><strong>Strict No Smoking</strong> - Penalty: <strong>₱5,000</strong></li>
          <li>Small breed pets accepted (not on bed/near pool)</li>
          <li>Practice <strong>Clean As You Go</strong></li>
        </ul>
      </div>
      <button onclick="closeModal()" class="btn-primary">Close</button>
    </div>
  `;

  // Show in modal or alert
  alert(
    "BOOKING POLICY\n\n" +
      "FIRST TO PAY, FIRST TO RESERVE\n" +
      "₱1,000 DOWNPAYMENT REQUIRED\n" +
      "REMAINING BALANCE BEFORE CHECK-IN\n" +
      "NON-REFUNDABLE (Rebooking only within 3 months)\n" +
      "Rebooking allowed 7 days prior\n" +
      "₱2,000 Security Bond (refundable)\n\n" +
      "HOUSE RULES:\n" +
      "- Noise curfew at 10PM\n" +
      "- No Smoking (₱5,000 penalty)\n" +
      "- Clean as you go\n" +
      "- Respect the property"
  );
}

// ===== GLOBAL FUNCTION EXPORTS =====
window.quickAction = quickAction;
window.viewBookingDetails = viewBookingDetails;
window.leaveReview = leaveReview;
window.bookAgain = bookAgain;
window.modifyBooking = modifyBooking;
window.cancelBooking = cancelBooking;
window.completePayment = completePayment;
window.markAllAsRead = markAllAsRead;
window.markAllAsUnread = markAllAsUnread;
window.clearAllNotifications = clearAllNotifications;
window.filterNotifications = filterNotifications;
window.dismissNotification = dismissNotification;
window.handleNotificationLink = handleNotificationLink;
window.writeNewReview = writeNewReview;
window.editReview = editReview;
window.deleteReview = deleteReview;
window.selectBookingType = selectBookingType;
window.selectRoomPackage = selectRoomPackage;
window.goBackToStep = goBackToStep;
window.showBookingPolicy = showBookingPolicy;
window.refreshUserData = refreshUserData;

// ===== BOOKING HISTORY FUNCTION EXPORTS =====
window.viewReservationDetails = viewReservationDetails;
window.closeReservationDetailsModal = closeReservationDetailsModal;
window.showRebookingModal = showRebookingModal;
window.closeRebookingModal = closeRebookingModal;
window.submitRebooking = submitRebooking;
window.writeReviewForReservation = writeReviewForReservation;
window.setRating = setRating;
window.closeReviewModal = closeReviewModal;
window.submitReview = submitReview;
window.bookAgainFromReservation = bookAgainFromReservation;
window.resetBookingFilters = resetBookingFilters;
window.cancelReservation = cancelReservation;
window.printReservationReceipt = printReservationReceipt;
window.loadMyReservations = loadMyReservations;
window.applyFiltersAndSort = applyFiltersAndSort;
window.formatBookingTypeShort = formatBookingTypeShort;
window.createHistoryReservationCard = createHistoryReservationCard;
window.getHistoryActionButtons = getHistoryActionButtons;
window.displayReservations = displayReservations;
window.completePayment = completePayment;
window.handleCardClick = handleCardClick;

// Debug: Verify functions are accessible
// Test button onclick attributes
setTimeout(() => {
  const buttons = document.querySelectorAll(".select-booking-type-btn");
  buttons.forEach((btn, index) => {
    console.log(`Button ${index + 1}:`, {
      onclick: btn.getAttribute("onclick"),
      hasOnclickFunction: typeof btn.onclick === "function",
    });
  });
}, 1000);

// BACKUP: Add direct event listeners in case onclick doesn't work
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    // Find buttons by their onclick attribute
    const daytimeBtn = document.querySelector(
      '.select-booking-type-btn[onclick*="daytime"]'
    );
    const nighttimeBtn = document.querySelector(
      '.select-booking-type-btn[onclick*="nighttime"]'
    );
    const hours22Btn = document.querySelector(
      '.select-booking-type-btn[onclick*="22hours"]'
    );

    let addedListeners = 0;

    if (daytimeBtn) {
      daytimeBtn.addEventListener("click", function (e) {
        selectBookingType("daytime");
      });
      addedListeners++;
    }

    if (nighttimeBtn) {
      nighttimeBtn.addEventListener("click", function (e) {
        selectBookingType("nighttime");
      });
      addedListeners++;
    }

    if (hours22Btn) {
      hours22Btn.addEventListener("click", function (e) {
        selectBookingType("22hours");
      });
      addedListeners++;
    }
  }, 1500);
});

// ===== REBOOKING REQUESTS =====
let currentUserRebookingFilter = 'all';

async function loadRebookingRequests() {
  const container = document.getElementById('rebookingRequestsList');
  if (!container) {
    console.error("Error: rebookingRequestsList element not found.");
    return;
  }
  
  container.innerHTML = `
    <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
      <i class="fas fa-spinner fa-spin" style="font-size: 48px; margin-bottom: 16px;"></i>
      <div style="font-size: 16px;">Loading rebooking requests...</div>
    </div>
  `;
  
  try {
    const response = await fetch(`user/get_my_rebooking_requests.php?status=${currentUserRebookingFilter}`, {
      credentials: 'include'
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (!data.success) {
      container.innerHTML = `
        <div style="text-align: center; padding: 60px 20px; color: #ef4444;">
          <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i>
          <div style="font-size: 16px; font-weight: 600;">${data.message || 'Failed to load rebooking requests'}</div>
        </div>
      `;
      return;
    }
    
    // Update stats
    if (data.counts) {
      document.getElementById('rebookingPendingCount').textContent = data.counts.pending || 0;
      document.getElementById('rebookingApprovedCount').textContent = data.counts.approved || 0;
      document.getElementById('rebookingRejectedCount').textContent = data.counts.rejected || 0;
      
      // Update badge
      const badge = document.getElementById('rebookingRequestsBadge');
      if (badge) {
        const pendingCount = data.counts.pending || 0;
        if (pendingCount > 0) {
          badge.textContent = pendingCount;
          badge.style.display = 'inline-block';
        } else {
          badge.style.display = 'none';
        }
      }
    }
    
    // Render requests
    renderUserRebookingRequests(data.requests || []);
    
  } catch (error) {
    console.error('Error loading rebooking requests:', error);
    container.innerHTML = `
      <div style="text-align: center; padding: 60px 20px; color: #ef4444;">
        <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px;"></i>
        <div style="font-size: 16px; font-weight: 600;">Error: ${error.message}</div>
        <button onclick="loadRebookingRequests()" style="margin-top: 16px; padding: 10px 24px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
          <i class="fas fa-redo"></i> Retry
        </button>
      </div>
    `;
  }
}

function renderUserRebookingRequests(requests) {
  const container = document.getElementById('rebookingRequestsList');
  if (!container) return;
  
  if (!requests || requests.length === 0) {
    container.innerHTML = `
      <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
        <i class="fas fa-calendar-times" style="font-size: 64px; margin-bottom: 16px;"></i>
        <h3 style="margin-top: 20px; color: #11224e;">No Rebooking Requests</h3>
        <p style="color: #64748b;">You haven't made any rebooking requests yet.</p>
      </div>
    `;
    return;
  }
  
  container.innerHTML = requests.map((request, index) => {
    const status = request.rebooking_status || 'pending';
    const statusLabel = request.rebooking_status_label || 'Pending';
    
    let statusColor = '#f59e0b'; // pending
    let statusIcon = 'fa-clock';
    if (status === 'approved') {
      statusColor = '#10b981';
      statusIcon = 'fa-check-circle';
    } else if (status === 'rejected') {
      statusColor = '#ef4444';
      statusIcon = 'fa-times-circle';
    }
    
    return `
      <div class="booking-card" style="
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid ${statusColor};
        transition: all 0.3s ease;
      " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" 
         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
          <div>
            <h3 style="margin: 0 0 8px 0; color: #11224e; font-size: 18px;">
              Reservation #${request.reservation_id}
            </h3>
            <p style="margin: 0; color: #64748b; font-size: 14px;">
              ${request.booking_type ? request.booking_type.charAt(0).toUpperCase() + request.booking_type.slice(1) : 'N/A'} Booking
              ${request.package_type ? ' • ' + request.package_type : ''}
            </p>
          </div>
          <span style="
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: ${statusColor}15;
            color: ${statusColor};
            display: flex;
            align-items: center;
            gap: 6px;
          ">
            <i class="fas ${statusIcon}"></i>
            ${statusLabel}
          </span>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">
          <div>
            <p style="margin: 0 0 4px 0; color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase;">Original Date</p>
            <p style="margin: 0; color: #11224e; font-size: 14px; font-weight: 600;">
              <i class="fas fa-calendar" style="color: #667eea; margin-right: 6px;"></i>
              ${request.original_check_in_date_formatted || request.check_in_date_formatted || 'N/A'}
            </p>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 12px;">
              ${request.original_check_in_time_formatted || request.check_in_time_formatted || ''}
            </p>
          </div>
          ${request.rebooking_new_date ? `
          <div>
            <p style="margin: 0 0 4px 0; color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase;">Requested New Date</p>
            <p style="margin: 0; color: #11224e; font-size: 14px; font-weight: 600;">
              <i class="fas fa-calendar-plus" style="color: #10b981; margin-right: 6px;"></i>
              ${request.rebooking_new_date_formatted || 'N/A'}
            </p>
          </div>
          ` : ''}
          <div>
            <p style="margin: 0 0 4px 0; color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase;">Requested At</p>
            <p style="margin: 0; color: #11224e; font-size: 14px;">
              <i class="fas fa-clock" style="color: #667eea; margin-right: 6px;"></i>
              ${request.rebooking_requested_at ? new Date(request.rebooking_requested_at).toLocaleString() : 'N/A'}
            </p>
          </div>
        </div>
        
        ${request.rebooking_reason ? `
        <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
          <p style="margin: 0 0 4px 0; color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase;">Reason</p>
          <p style="margin: 0; color: #11224e; font-size: 14px;">${request.rebooking_reason}</p>
        </div>
        ` : ''}
        
        ${status === 'approved' && request.rebooking_approved_at ? `
        <div style="background: #d1fae5; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
          <p style="margin: 0; color: #065f46; font-size: 14px;">
            <i class="fas fa-check-circle" style="margin-right: 6px;"></i>
            Approved on ${new Date(request.rebooking_approved_at).toLocaleString()}
          </p>
        </div>
        ` : ''}
        
        ${status === 'rejected' ? `
        <div style="background: #fee2e2; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
          <p style="margin: 0 0 4px 0; color: #991b1b; font-size: 14px; font-weight: 600;">
            <i class="fas fa-times-circle" style="margin-right: 6px;"></i>
            Rejected
            ${request.rebooking_rejected_at ? ` on ${new Date(request.rebooking_rejected_at).toLocaleString()}` : ''}
          </p>
          ${request.rebooking_rejection_reason ? `
          <p style="margin: 4px 0 0 0; color: #991b1b; font-size: 13px;">
            Reason: ${request.rebooking_rejection_reason}
          </p>
          ` : ''}
        </div>
        ` : ''}
        
        <div style="display: flex; gap: 12px; margin-top: 16px;">
          <button onclick="event.stopPropagation(); viewReservationDetails('${request.reservation_id}')" style="
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
          " onmouseover="this.style.background='#5568d3'" onmouseout="this.style.background='#667eea'">
            <i class="fas fa-eye"></i> View Details
          </button>
        </div>
      </div>
    `;
  }).join('');
}

function filterUserRebookings(status) {
  currentUserRebookingFilter = status;
  
  // Update filter button styles
  document.querySelectorAll('.filter-tab').forEach(btn => {
    btn.classList.remove('active');
    btn.style.background = 'white';
    btn.style.color = btn.id.includes('pending') ? '#f59e0b' : 
                      btn.id.includes('approved') ? '#10b981' : 
                      btn.id.includes('rejected') ? '#ef4444' : '#11224e';
    btn.style.border = `2px solid ${btn.style.color}`;
  });
  
  const activeBtn = document.getElementById(`user-rebooking-filter-${status}`);
  if (activeBtn) {
    activeBtn.classList.add('active');
    activeBtn.style.background = activeBtn.style.color === '#f59e0b' ? '#f59e0b' :
                                  activeBtn.style.color === '#10b981' ? '#10b981' :
                                  activeBtn.style.color === '#ef4444' ? '#ef4444' : '#11224e';
    activeBtn.style.color = 'white';
    activeBtn.style.border = `2px solid ${activeBtn.style.background}`;
  }
  
  loadRebookingRequests();
}

// This function is for rebooking requests - it should use the main viewReservationDetails
// But we need to make sure it doesn't conflict with the one in booking history
// So we'll just call the main one if it exists, otherwise navigate
function viewReservationDetailsFromRebooking(reservationId) {
  // Check if the main viewReservationDetails function exists (from booking history)
  if (typeof viewReservationDetails === 'function' && viewReservationDetails.toString().includes('showReservationDetailsModal')) {
    // Use the main modal function
    viewReservationDetails(reservationId);
  } else {
    // Fallback: Navigate to My Reservations section
    const navBtn = document.querySelector('[data-section="my-bookings"]');
    if (navBtn) {
      navBtn.click();
      setTimeout(() => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        // Try to find and click the reservation card
        const reservationCard = document.querySelector(`[data-reservation-id="${reservationId}"]`);
        if (reservationCard) {
          reservationCard.click();
        }
      }, 500);
    }
  }
}
