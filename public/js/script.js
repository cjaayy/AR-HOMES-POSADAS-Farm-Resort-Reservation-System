// DOM Elements
const loginForm = document.getElementById("loginForm");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const loginBtn = document.querySelector(".login-btn");
const toggleIcon = document.getElementById("toggleIcon");

// Slideshow Elements
const slides = document.querySelectorAll(".slide");
const indicators = document.querySelectorAll(".indicator");
let currentSlide = 0;
let slideInterval;

// Initialize slideshow
function initSlideshow() {
  // Start automatic slideshow
  startSlideshow();

  // Add click handlers to indicators (if they exist)
  if (indicators.length > 0) {
    indicators.forEach((indicator, index) => {
      indicator.addEventListener("click", () => {
        goToSlide(index);
      });
    });
  }
}

// Start automatic slideshow
function startSlideshow() {
  slideInterval = setInterval(() => {
    nextSlide();
  }, 4000); // Change slide every 4 seconds
}

// Stop slideshow
function stopSlideshow() {
  clearInterval(slideInterval);
}

// Go to specific slide
function goToSlide(index) {
  // Remove active class from current slide and indicator
  slides[currentSlide].classList.remove("active");
  if (indicators.length > 0) {
    indicators[currentSlide].classList.remove("active");
  }

  // Add flash transition effect
  slides[currentSlide].classList.add("flash-transition");

  // Remove flash transition after animation
  setTimeout(() => {
    slides[currentSlide].classList.remove("flash-transition");
  }, 1000);

  // Update current slide
  currentSlide = index;

  // Add active class to new slide and indicator
  slides[currentSlide].classList.add("active");
  if (indicators.length > 0) {
    indicators[currentSlide].classList.add("active");
  }

  // Restart slideshow timer
  stopSlideshow();
  startSlideshow();
}

// Go to next slide
function nextSlide() {
  const nextIndex = (currentSlide + 1) % slides.length;
  goToSlide(nextIndex);
}

// Go to previous slide
function prevSlide() {
  const prevIndex = (currentSlide - 1 + slides.length) % slides.length;
  goToSlide(prevIndex);
}

// Add keyboard navigation for slideshow
document.addEventListener("keydown", (e) => {
  if (e.key === "ArrowLeft") {
    prevSlide();
  } else if (e.key === "ArrowRight") {
    nextSlide();
  }
});

// Pause slideshow on hover
const imageSection = document.querySelector(".image-section");
if (imageSection) {
  imageSection.addEventListener("mouseenter", stopSlideshow);
  imageSection.addEventListener("mouseleave", startSlideshow);
}

// Initialize slideshow when page loads
document.addEventListener("DOMContentLoaded", initSlideshow);

// Demo credentials auto-fill function
function fillDemoCredentials(type) {
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");

  if (type === "demo") {
    emailInput.value = "demo@guest.com";
    passwordInput.value = "demo123";
  } else if (type === "admin") {
    emailInput.value = "admin@resort.com";
    passwordInput.value = "admin123";
  }

  // Add visual feedback
  emailInput.style.background = "rgba(102, 126, 234, 0.1)";
  passwordInput.style.background = "rgba(102, 126, 234, 0.1)";

  // Reset background after animation
  setTimeout(() => {
    emailInput.style.background = "";
    passwordInput.style.background = "";
  }, 1000);

  // Focus on login button
  document.querySelector(".login-btn").focus();
}

// Form validation and submission
if (loginForm) {
loginForm.addEventListener("submit", function (e) {
  e.preventDefault();

  // Get form values
  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();

  // Reset error states
  clearErrors();

  // Validation
  let isValid = true;

  if (!email) {
    showError(emailInput, "Please enter your username or email");
    isValid = false;
  } else if (!isValidEmail(email) && !isValidUsername(email)) {
    showError(emailInput, "Please enter a valid email or username");
    isValid = false;
  }

  if (!password) {
    showError(passwordInput, "Please enter your password");
    isValid = false;
  } else if (password.length < 6) {
    showError(passwordInput, "Password must be at least 6 characters");
    isValid = false;
  }

  if (!isValid) {
    showLoginFailedModal("Please fill in all required fields with valid credentials.", "Incomplete Fields");
    return;
  }

  // If validation passes, process login
  handleLogin(email, password);
});
}

// Variables for success redirection timer
let loginRedirectTimer = null;
let loginRedirectProgressInterval = null;
let currentRedirectUrl = null;

// Show Login Success Modal
function showLoginSuccessModal(options) {
  const modal = document.getElementById("loginSuccessModal");
  if (!modal) {
    window.location.href = options.redirectUrl || "dashboard.html";
    return;
  }

  const titleEl = document.getElementById("loginSuccessTitle");
  const messageEl = document.getElementById("loginSuccessMessage");
  const userInfoEl = document.getElementById("loginSuccessUserInfo");
  const roleBadgeEl = document.getElementById("loginSuccessRoleBadge");
  const userNameEl = document.getElementById("loginSuccessUserName");
  const progressFill = document.getElementById("loginSuccessProgress");
  const countdownEl = document.getElementById("loginSuccessCountdown");

  currentRedirectUrl = options.redirectUrl || "dashboard.html";

  if (titleEl) titleEl.textContent = options.title || "Login Successful!";
  if (messageEl) messageEl.textContent = options.message || "Authentication verified. Access granted to AR Homes Posadas Farm Resort.";

  if (userInfoEl && options.name) {
    if (roleBadgeEl) roleBadgeEl.textContent = options.role || "User";
    if (userNameEl) userNameEl.textContent = options.name;
    userInfoEl.style.display = "inline-flex";
  } else if (userInfoEl) {
    userInfoEl.style.display = "none";
  }

  // Update button state
  if (loginBtn) {
    loginBtn.classList.remove("loading", "error");
    loginBtn.classList.add("success");
    loginBtn.innerHTML = '<i class="fas fa-check"></i> ' + (options.btnText || "Access Granted");
    loginBtn.disabled = true;
  }

  // Open modal
  modal.classList.add("show");
  modal.style.display = "flex";
  document.body.style.overflow = "hidden";

  // Progress and countdown
  const duration = options.duration || 1800;
  let remainingMs = duration;
  const intervalMs = 30;

  if (progressFill) progressFill.style.width = "0%";
  if (countdownEl) countdownEl.textContent = Math.ceil(remainingMs / 1000);

  clearInterval(loginRedirectProgressInterval);
  clearTimeout(loginRedirectTimer);

  loginRedirectProgressInterval = setInterval(() => {
    remainingMs -= intervalMs;
    const progressPercent = Math.min(100, Math.max(0, ((duration - remainingMs) / duration) * 100));
    if (progressFill) progressFill.style.width = progressPercent + "%";
    if (countdownEl) countdownEl.textContent = Math.max(0, Math.ceil(remainingMs / 1000));

    if (remainingMs <= 0) {
      clearInterval(loginRedirectProgressInterval);
    }
  }, intervalMs);

  loginRedirectTimer = setTimeout(() => {
    proceedLoginRedirect();
  }, duration);
}

// Proceed with redirection
function proceedLoginRedirect() {
  clearTimeout(loginRedirectTimer);
  clearInterval(loginRedirectProgressInterval);
  if (currentRedirectUrl) {
    window.location.href = currentRedirectUrl;
  }
}

// Close login success modal (proceeds immediately)
function closeLoginSuccessModal() {
  proceedLoginRedirect();
}

// Show Login Failed Modal
function showLoginFailedModal(message, title) {
  const modal = document.getElementById("loginFailedModal");
  if (!modal) {
    alert(message || "Login failed. Please try again.");
    return;
  }

  const titleEl = document.getElementById("loginFailedTitle");
  const messageEl = document.getElementById("loginFailedMessage");

  if (titleEl) titleEl.textContent = title || "Login Failed";
  if (messageEl) messageEl.textContent = message || "The credentials provided do not match our records. Please verify your username or password and try again.";

  // Reset login button from loading
  if (loginBtn) {
    loginBtn.classList.remove("loading", "success");
    loginBtn.classList.add("error");
    loginBtn.disabled = false;
    loginBtn.innerHTML = '<span>Sign In</span><i class="fas fa-arrow-right"></i>';

    setTimeout(() => {
      loginBtn.classList.remove("error");
    }, 2000);
  }

  // Open modal
  modal.classList.add("show");
  modal.style.display = "flex";
  document.body.style.overflow = "hidden";

  // Re-trigger shake animation
  const content = modal.querySelector(".auth-feedback-content");
  if (content) {
    content.style.animation = "none";
    void content.offsetHeight;
    content.style.animation = "";
  }
}

// Close Login Failed Modal
function closeLoginFailedModal() {
  const modal = document.getElementById("loginFailedModal");
  if (modal) {
    modal.classList.remove("show");
    modal.style.display = "none";
    document.body.style.overflow = "auto";
  }

  // Focus password or email field for quick correction
  if (passwordInput && (!passwordInput.value || emailInput.value)) {
    passwordInput.focus();
    passwordInput.select();
  } else if (emailInput) {
    emailInput.focus();
    emailInput.select();
  }
}

// Switch from Login Failed Modal directly to Forgot Password Modal
function switchToForgotPasswordModal() {
  closeLoginFailedModal();

  // Pre-fill email in forgot password modal if user entered an email
  const enteredEmail = emailInput ? emailInput.value.trim() : "";
  const resetEmailInput = document.getElementById("resetEmail");
  if (resetEmailInput && enteredEmail && isValidEmail(enteredEmail)) {
    resetEmailInput.value = enteredEmail;
  }

  openForgotPasswordModal();
}

// Show login error message via modal
function showLoginError(message, title) {
  showLoginFailedModal(message, title || "Authentication Error");
}

// Handle login with database authentication
function handleLogin(email, password) {
  // Add loading state
  loginBtn.classList.add("loading");
  loginBtn.disabled = true;
  loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';

  const cleanEmail = email.trim();

  // Try admin login first (admin only, not staff)
  fetch("admin/login.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ username: cleanEmail, password: password }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        // Check if this is a staff user - redirect to staff login endpoint
        if (data.data && data.data.role === "staff") {
          return fetch("admin/staff_login.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              username: cleanEmail,
              password: password,
            }),
          })
            .then((r) => r.json())
            .then((staffData) => {
              if (staffData.success) {
                showLoginSuccessModal({
                  title: "Welcome, Staff Member!",
                  message: "Staff authentication successful. Opening your workspace...",
                  role: "Staff",
                  name: staffData.data?.username || "Staff Member",
                  btnText: "Access Granted",
                  redirectUrl: "admin/staff_dashboard.php",
                });
              } else {
                showLoginFailedModal(staffData.message || "Staff login failed. Please verify credentials.", "Staff Access Denied");
              }
            });
        } else {
          // Admin login succeeded
          showLoginSuccessModal({
            title: "Welcome, Administrator!",
            message: "Administrative privileges confirmed. Opening management dashboard...",
            role: "Administrator",
            name: data.data?.username || "Administrator",
            btnText: "Access Granted",
            redirectUrl: "admin/dashboard.php",
          });
        }
        return;
      } else {
        // Check if it's an inactive account error - show immediately without trying guest login
        if (data.message && data.message.toLowerCase().includes("inactive")) {
          showLoginFailedModal(data.message, "Account Inactive");
          return;
        }

        // Admin login failed; try guest login
        return fetch("user/login.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            usernameOrEmail: cleanEmail,
            password: password,
          }),
        })
          .then((res) => res.text())
          .then((text) => {
            let guestData;
            try {
              guestData = JSON.parse(text);
            } catch (e) {
              throw new Error("Failed to parse guest login response");
            }

            if (guestData.success) {
              const displayName = guestData.data?.full_name || 
                (guestData.data?.given_name ? `${guestData.data.given_name} ${guestData.data.last_name || ''}`.trim() : null) || 
                guestData.data?.username || "Valued Guest";

              showLoginSuccessModal({
                title: "Welcome Back!",
                message: "Authentication verified. Access granted to AR Homes Posadas Farm Resort.",
                role: "Guest",
                name: displayName,
                btnText: "Welcome Back",
                redirectUrl: "dashboard.html",
              });
            } else {
              showLoginFailedModal(guestData.message || "Invalid username/email or password.", "Login Failed");
            }
          });
      }
    })
    .catch((err) => {
      showLoginFailedModal("Unable to connect to the server. Please check your internet connection and try again.", "Network Error");
    });
}

// Email validation
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Username validation (alphanumeric and underscore)
function isValidUsername(username) {
  const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
  return usernameRegex.test(username);
}

// Show error state
function showError(input, message) {
  const wrapper = input.closest(".input-wrapper");
  wrapper.classList.add("error");

  // Create error message element if it doesn't exist
  let errorMsg = wrapper.nextElementSibling;
  if (!errorMsg || !errorMsg.classList.contains("error-message")) {
    errorMsg = document.createElement("div");
    errorMsg.className = "error-message";
    errorMsg.style.color = "#ff6b6b";
    errorMsg.style.fontSize = "0.8rem";
    errorMsg.style.marginTop = "5px";
    errorMsg.style.marginLeft = "5px";
    wrapper.parentNode.insertBefore(errorMsg, wrapper.nextSibling);
  }
  errorMsg.textContent = message;

  // Remove error state when user starts typing
  input.addEventListener(
    "input",
    function () {
      wrapper.classList.remove("error");
      if (errorMsg) {
        errorMsg.remove();
      }
    },
    { once: true }
  );
}

// Clear all error states
function clearErrors() {
  const errorWrappers = document.querySelectorAll(".input-wrapper.error");
  errorWrappers.forEach((wrapper) => {
    wrapper.classList.remove("error");
  });

  const errorMessages = document.querySelectorAll(".error-message");
  errorMessages.forEach((msg) => msg.remove());
}

// Password toggle functionality
function togglePassword() {
  const passwordField = document.getElementById("password");
  const toggleIcon = document.getElementById("toggleIcon");

  if (passwordField.type === "password") {
    passwordField.type = "text";
    toggleIcon.classList.remove("fa-eye");
    toggleIcon.classList.add("fa-eye-slash");
  } else {
    passwordField.type = "password";
    toggleIcon.classList.remove("fa-eye-slash");
    toggleIcon.classList.add("fa-eye");
  }
}

// Input focus effects
document.querySelectorAll(".input-wrapper input").forEach((input) => {
  input.addEventListener("focus", function () {
    this.closest(".input-wrapper").style.transform = "translateY(-1px)";
    this.closest(".input-wrapper").style.boxShadow =
      "0 4px 12px rgba(0,0,0,0.1)";
  });

  input.addEventListener("blur", function () {
    this.closest(".input-wrapper").style.transform = "translateY(0)";
    this.closest(".input-wrapper").style.boxShadow = "none";
  });
});

// Remember me functionality
const rememberCheckbox = document.getElementById("remember");
if (rememberCheckbox) {
  rememberCheckbox.addEventListener("change", function () {
    if (this.checked) {
      // In a real application, you would set localStorage or cookie preferences here
    } else {
      // Remember me disabled
    }
  });
}

// Handle "Forgot Password" link
const forgotLink = document.querySelector(".forgot-link");
if (forgotLink) {
  forgotLink.addEventListener("click", function (e) {
    e.preventDefault();
    openForgotPasswordModal();
  });
}

// Register link now works with standard HTML navigation to registration.html

// Add keyboard navigation
document.addEventListener("keydown", function (e) {
  if (e.key === "Enter" && e.target.tagName !== "BUTTON") {
    loginForm.dispatchEvent(new Event("submit"));
  }
});

// Auto-focus first input on page load
window.addEventListener("load", function () {
  emailInput.focus();
});

// Add smooth scrolling for mobile keyboards
window.addEventListener("resize", function () {
  if (window.innerWidth <= 768) {
    const activeElement = document.activeElement;
    if (activeElement && activeElement.tagName === "INPUT") {
      setTimeout(() => {
        activeElement.scrollIntoView({ behavior: "smooth", block: "center" });
      }, 100);
    }
  }
});

// Add a subtle animation to the logo
const logo = document.querySelector(".logo");
if (logo) {
  logo.addEventListener("mouseenter", function () {
    this.style.transform = "scale(1.1) rotate(5deg)";
    this.style.transition = "all 0.3s ease";
  });

  logo.addEventListener("mouseleave", function () {
    this.style.transform = "scale(1) rotate(0deg)";
  });
}

// Prevent form submission with Enter key on password toggle button
document
  .querySelector(".password-toggle")
  .addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      togglePassword();
    }
  });

// ===== MAP MODAL FUNCTIONALITY =====

// Open location map modal
function openLocationMap() {
  const modal = document.getElementById("mapModal");
  if (!modal) return;

  modal.style.display = "flex";
  // Force reflow to enable transition
  modal.offsetHeight;
  modal.classList.add("show");

  // Add escape key listener
  document.addEventListener("keydown", handleMapModalEscape);

  // Load map with a small delay for better animation
  setTimeout(() => {
    loadResortMap();
  }, 300);
}

// Close location map modal
function closeLocationMap() {
  const modal = document.getElementById("mapModal");
  if (!modal) return;

  modal.classList.remove("show");

  // Remove escape key listener
  document.removeEventListener("keydown", handleMapModalEscape);

  // Hide modal after animation
  setTimeout(() => {
    modal.style.display = "none";
  }, 300);
}

// Handle escape key for modal
function handleMapModalEscape(e) {
  if (e.key === "Escape") {
    closeLocationMap();
  }
}

// Load resort map (you can customize the coordinates)
function loadResortMap() {
  const mapFrame = document.getElementById("resortMap");

  // 🎯 AR HOMES POSADAS FARM RESORT EXACT COORDINATES
  const latitude = 14.439585;
  const longitude = 120.461482;

  // Google Maps embed with exact marker on location
  const mapUrl = `https://maps.google.com/maps?q=${latitude},${longitude}&hl=en&z=17&output=embed`;

  mapFrame.src = mapUrl;

  // Add load event listener to show when map is ready
  mapFrame.onload = function () {
    // Map loaded successfully
  };
}

// Open directions to resort
function openDirections() {
  // 🎯 AR HOMES POSADAS FARM RESORT EXACT LOCATION
  const directionsUrl = "https://maps.app.goo.gl/SFm3VyoNMJQ2MLik7";

  // Open in new tab
  window.open(directionsUrl, "_blank");

  // Show success message
  showMapNotification("Opening directions in Google Maps...", "success");
}

// Share resort location
function shareLocation() {
  const mapsUrl = "https://maps.app.goo.gl/SFm3VyoNMJQ2MLik7";
  const resortInfo = {
    name: "AR Homes Posadas Farm Resort",
    address: "2488 Maangay, Balon-Anito, Mariveles, Bataan, Philippines",
    phone: "09509448599",
    website: mapsUrl,
  };

  // Try to use Web Share API if available
  if (navigator.share) {
    navigator
      .share({
        title: resortInfo.name,
        text: `Check out ${resortInfo.name} - ${resortInfo.address}`,
        url: mapsUrl,
      })
      .then(() => {
        showMapNotification("Location shared successfully!", "success");
      })
      .catch((error) => {
        console.log("Error sharing:", error);
        fallbackShare(resortInfo);
      });
  } else {
    // Fallback for browsers that don't support Web Share API
    fallbackShare(resortInfo);
  }
}

// Fallback share method
function fallbackShare(resortInfo) {
  const shareText = `${resortInfo.name}\n${resortInfo.address}\nPhone: ${resortInfo.phone}\nWebsite: ${resortInfo.website}`;

  // Copy to clipboard
  if (navigator.clipboard) {
    navigator.clipboard
      .writeText(shareText)
      .then(() => {
        showMapNotification("Resort location copied to clipboard!", "success");
      })
      .catch(() => {
        showMapNotification("Please copy manually: " + shareText, "info");
      });
  } else {
    // Older browser fallback
    const textArea = document.createElement("textarea");
    textArea.value = shareText;
    document.body.appendChild(textArea);
    textArea.select();

    try {
      document.execCommand("copy");
      showMapNotification("Resort location copied to clipboard!", "success");
    } catch (err) {
      showMapNotification("Please copy manually: " + shareText, "info");
    }

    document.body.removeChild(textArea);
  }
}

// Show map notification
function showMapNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `map-notification map-notification-${type}`;
  notification.textContent = message;

  // Style the notification
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${getNotificationColor(type)};
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    z-index: 10001;
    font-weight: 500;
    font-size: 0.9rem;
    max-width: 300px;
    animation: slideInRight 0.3s ease-out;
  `;

  // Add to DOM
  document.body.appendChild(notification);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = "slideOutRight 0.3s ease-in";
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

// Get notification color based on type
function getNotificationColor(type) {
  switch (type) {
    case "success":
      return "linear-gradient(135deg, #28a745, #20c997)";
    case "error":
      return "linear-gradient(135deg, #dc3545, #e74c3c)";
    case "warning":
      return "linear-gradient(135deg, #ffc107, #f39c12)";
    default:
      return "linear-gradient(135deg, #667eea, #764ba2)";
  }
}

// Close modal when clicking outside
document.addEventListener("click", function (e) {
  const mapModal = document.getElementById("mapModal");
  const contactModal = document.getElementById("contactModal");
  const reviewsModal = document.getElementById("reviewsModal");

  if (mapModal && e.target === mapModal) {
    closeLocationMap();
  }
  if (contactModal && e.target === contactModal) {
    closeContactModal();
  }
  if (reviewsModal && e.target === reviewsModal) {
    closeReviewsModal();
  }
});

// Add CSS animation for notifications
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
`;
document.head.appendChild(notificationStyles);

// Add map icon hover effect
const mapIconBtn = document.querySelector(".map-icon-btn");
if (mapIconBtn) {
  mapIconBtn.addEventListener("mouseenter", function () {
    this.style.animation = "pulse 1s infinite";
  });

  mapIconBtn.addEventListener("mouseleave", function () {
    this.style.animation = "";
  });
}

// Add pulse animation
const pulseAnimation = document.createElement("style");
pulseAnimation.textContent = `
  @keyframes pulse {
    0% { box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2); }
    50% { box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4); }
    100% { box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2); }
  }
`;
document.head.appendChild(pulseAnimation);

// Make functions globally available
window.openLocationMap = openLocationMap;
window.closeLocationMap = closeLocationMap;
window.openDirections = openDirections;
window.shareLocation = shareLocation;

// ===== CONTACT MODAL FUNCTIONALITY =====

// Open contact modal
function openContactModal() {
  const modal = document.getElementById("contactModal");
  if (!modal) return;

  modal.style.display = "flex";
  // Force reflow to enable transition
  modal.offsetHeight;
  modal.classList.add("show");

  // Add escape key listener
  document.addEventListener("keydown", handleContactModalEscape);
}

// Close contact modal
function closeContactModal() {
  const modal = document.getElementById("contactModal");
  if (!modal) return;

  modal.classList.remove("show");

  // Remove escape key listener
  document.removeEventListener("keydown", handleContactModalEscape);

  // Hide modal after animation
  setTimeout(() => {
    modal.style.display = "none";
  }, 300);
}

// Handle escape key for contact modal
function handleContactModalEscape(e) {
  if (e.key === "Escape") {
    closeContactModal();
  }
}

// Make contact functions globally available
window.openContactModal = openContactModal;
window.closeContactModal = closeContactModal;

// ===== REVIEWS MODAL FUNCTIONALITY =====

// Load public reviews from API
async function loadPublicReviews() {
  const reviewsList = document.getElementById("publicReviewsList");
  const avgRating = document.getElementById("publicAvgRating");
  const ratingStars = document.getElementById("publicRatingStars");
  const reviewCount = document.getElementById("publicReviewCount");

  if (!reviewsList) return;

  try {
    const res = await fetch("user/get_public_reviews.php?limit=10");
    const data = await res.json();

    if (data.success) {
      // Update stats
      const avg = data.stats?.average_rating || 0;
      const total = data.stats?.total_reviews || 0;

      if (avgRating) avgRating.textContent = avg.toFixed(1);
      if (reviewCount)
        reviewCount.textContent = `Based on ${total} review${
          total !== 1 ? "s" : ""
        }`;

      // Update stars
      if (ratingStars) {
        const fullStars = Math.floor(avg);
        const hasHalf = avg - fullStars >= 0.5;
        let starsHtml = "";
        for (let i = 1; i <= 5; i++) {
          if (i <= fullStars) {
            starsHtml += '<i class="fas fa-star"></i>';
          } else if (i === fullStars + 1 && hasHalf) {
            starsHtml += '<i class="fas fa-star-half-alt"></i>';
          } else {
            starsHtml += '<i class="far fa-star"></i>';
          }
        }
        ratingStars.innerHTML = starsHtml;
      }

      // Render reviews
      if (data.reviews && data.reviews.length > 0) {
        reviewsList.innerHTML = data.reviews
          .map((review) => {
            const date = new Date(review.created_at);
            const monthYear = date.toLocaleDateString("en-US", {
              month: "short",
              year: "numeric",
            });
            const stars = generateStarsHtml(review.rating);
            const initial = (review.guest_name || "G")[0].toUpperCase();

            return `
              <div class="review-card">
                <div class="review-header">
                  <div class="reviewer-avatar" style="background:linear-gradient(135deg, #667eea, #764ba2); color:white; width:45px; height:45px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:18px;">
                    ${initial}
                  </div>
                  <div class="reviewer-info">
                    <h5>${escapeHtmlPublic(review.guest_name || "Guest")}</h5>
                    <div class="review-stars">${stars}</div>
                  </div>
                  <span class="review-date">${monthYear}</span>
                </div>
                ${
                  review.title
                    ? `<h6 style="margin:12px 0 8px; font-weight:600; color:#1e293b;">"${escapeHtmlPublic(
                        review.title
                      )}"</h6>`
                    : ""
                }
                <p class="review-text">${escapeHtmlPublic(
                  review.content || ""
                )}</p>
              </div>
            `;
          })
          .join("");
      } else {
        reviewsList.innerHTML = `
          <div style="text-align:center; padding:40px; color:#64748b;">
            <i class="fas fa-comments" style="font-size:48px; margin-bottom:16px; opacity:0.5;"></i>
            <p style="font-size:16px; margin:0;">No reviews yet. Be the first to share your experience!</p>
          </div>
        `;
      }
    } else {
      reviewsList.innerHTML = `
        <div style="text-align:center; padding:40px; color:#ef4444;">
          <i class="fas fa-exclamation-triangle" style="font-size:32px; margin-bottom:12px;"></i>
          <p>Failed to load reviews</p>
        </div>
      `;
    }
  } catch (err) {
    console.error("Error loading reviews:", err);
    reviewsList.innerHTML = `
      <div style="text-align:center; padding:40px; color:#ef4444;">
        <i class="fas fa-exclamation-triangle" style="font-size:32px; margin-bottom:12px;"></i>
        <p>Error loading reviews</p>
      </div>
    `;
  }
}

// Generate star icons HTML
function generateStarsHtml(rating) {
  let html = "";
  for (let i = 1; i <= 5; i++) {
    if (i <= rating) {
      html += '<i class="fas fa-star"></i>';
    } else {
      html += '<i class="far fa-star"></i>';
    }
  }
  return html;
}

// Escape HTML for public display
function escapeHtmlPublic(str) {
  if (!str) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

// Open reviews modal
function openReviewsModal() {
  const modal = document.getElementById("reviewsModal");
  if (!modal) return;

  modal.style.display = "flex";
  // Force reflow to enable transition
  modal.offsetHeight;
  modal.classList.add("show");

  // Load reviews
  loadPublicReviews();

  // Add escape key listener
  document.addEventListener("keydown", handleReviewsModalEscape);
}

// Close reviews modal
function closeReviewsModal() {
  const modal = document.getElementById("reviewsModal");
  if (!modal) return;

  modal.classList.remove("show");

  // Remove escape key listener
  document.removeEventListener("keydown", handleReviewsModalEscape);

  // Hide modal after animation
  setTimeout(() => {
    modal.style.display = "none";
  }, 300);
}

// Handle escape key for reviews modal
function handleReviewsModalEscape(e) {
  if (e.key === "Escape") {
    closeReviewsModal();
  }
}

// Make reviews functions globally available
window.openReviewsModal = openReviewsModal;
window.closeReviewsModal = closeReviewsModal;

// ===== FORGOT PASSWORD MODAL FUNCTIONALITY =====

// Open forgot password modal
function openForgotPasswordModal() {
  const modal = document.getElementById("forgotPasswordModal");
  modal.style.display = "flex";

  // Prevent body scrolling
  document.body.style.overflow = "hidden";

  // Focus on email input
  setTimeout(() => {
    document.getElementById("resetEmail").focus();
  }, 100);

  // Add escape key listener
  document.addEventListener("keydown", handleForgotPasswordEscape);
}

// Close forgot password modal
function closeForgotPasswordModal() {
  const modal = document.getElementById("forgotPasswordModal");
  modal.style.display = "none";

  // Restore body scrolling
  document.body.style.overflow = "auto";

  // Clear form
  document.getElementById("forgotPasswordForm").reset();

  // Remove escape key listener
  document.removeEventListener("keydown", handleForgotPasswordEscape);

  // Reset button state
  const sendResetBtn = document.getElementById("sendResetBtn");
  sendResetBtn.disabled = false;
  sendResetBtn.innerHTML =
    '<span>Send Reset Link</span><i class="fas fa-paper-plane"></i>';
}

// Handle escape key for forgot password modal
function handleForgotPasswordEscape(e) {
  if (e.key === "Escape") {
    closeForgotPasswordModal();
  }
}

// Handle forgot password form submission
const forgotPasswordForm = document.getElementById("forgotPasswordForm");
if (forgotPasswordForm) {
  forgotPasswordForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const resetEmail = document.getElementById("resetEmail").value.trim();
    const sendResetBtn = document.getElementById("sendResetBtn");

    // Validation
    if (!resetEmail) {
      alert("Please enter your email address");
      return;
    }

    if (!isValidEmail(resetEmail)) {
      alert("Please enter a valid email address");
      return;
    }

    // Disable button and show loading state
    sendResetBtn.disabled = true;
    sendResetBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Sending...';

    // Send password reset request
    fetch("user/forgot_password.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ email: resetEmail }),
    })
      .then((response) => response.json())
      .then((data) => {
        sendResetBtn.disabled = false;
        sendResetBtn.innerHTML =
          '<span>Send Reset Link</span><i class="fas fa-paper-plane"></i>';

        if (data.success) {
          // Show success modal only
          showResetSuccessModal();
          closeForgotPasswordModal();
        } else {
          alert(data.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Failed to send reset link. Please try again.");
        sendResetBtn.disabled = false;
        sendResetBtn.innerHTML =
          '<span>Send Reset Link</span><i class="fas fa-paper-plane"></i>';
      });
  });
}

// Show reset link modal for development
// Show reset success modal
function showResetSuccessModal() {
  // Remove existing modal if present
  const existing = document.getElementById("resetSuccessModal");
  if (existing) existing.remove();

  const modalHtml = `
    <div id="resetSuccessModal" style="
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10001;
    ">
      <div style="
        background: #fff;
        padding: 32px 28px 24px 28px;
        border-radius: 16px;
        max-width: 400px;
        width: 90vw;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        text-align: center;
        position: relative;
        animation: popInModal 0.3s cubic-bezier(.68,-0.55,.27,1.55);
      ">
        <div style="margin-bottom: 18px;">
          <div style="
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #fff;
            font-size: 28px;
          ">
            <i class="fas fa-envelope-open-text"></i>
          </div>
          <h3 style="color: #222; margin-bottom: 8px;">Check Your Email</h3>
          <p style="color: #555; font-size: 0.97rem;">Password reset instructions have been sent to your email address.</p>
          <p style="color: #888; font-size: 0.85rem; margin-top: 8px;">If you don't see the email, check your spam or junk folder.</p>
        </div>
        <button onclick="closeResetSuccessModal()" style="
          background: linear-gradient(135deg, #667eea, #764ba2);
          color: #fff;
          border: none;
          padding: 12px 32px;
          border-radius: 8px;
          cursor: pointer;
          font-weight: 600;
          font-size: 1rem;
          margin-top: 10px;
        ">OK</button>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML("beforeend", modalHtml);
}

// Close reset success modal
function closeResetSuccessModal() {
  const modal = document.getElementById("resetSuccessModal");
  if (modal) modal.remove();
}

// Add pop-in animation for modal
const resetSuccessModalStyle = document.createElement("style");
resetSuccessModalStyle.textContent = `
  @keyframes popInModal {
    0% { transform: scale(0.8); opacity: 0; }
    80% { transform: scale(1.05); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
  }
`;
document.head.appendChild(resetSuccessModalStyle);

// Allow closing modal by clicking outside
document.addEventListener("click", function (e) {
  const modal = document.getElementById("resetSuccessModal");
  if (modal && e.target === modal) {
    closeResetSuccessModal();
  }
});
function showResetLinkModal(resetLink) {
  const modalHtml = `
    <div id="resetLinkModal" style="
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
    ">
      <div style="
        background: white;
        padding: 30px;
        border-radius: 15px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      ">
        <div style="text-align: center; margin-bottom: 20px;">
          <div style="
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 30px;
          ">
            <i class="fas fa-envelope"></i>
          </div>
          <h3 style="color: #333; margin-bottom: 10px;">Password Reset Link Generated</h3>
          <p style="color: #666; font-size: 0.9rem;">
            For development: Use this link to reset your password
          </p>
        </div>
        
        <div style="
          background: #f8f9fa;
          padding: 15px;
          border-radius: 8px;
          margin-bottom: 20px;
          word-break: break-all;
          font-size: 0.85rem;
          color: #333;
        ">
          ${resetLink}
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: center;">
          <button onclick="copyResetLink('${resetLink}')" style="
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
          ">
            <i class="fas fa-copy"></i> Copy Link
          </button>
          <button onclick="window.location.href='${resetLink}'" style="
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
          ">
            <i class="fas fa-arrow-right"></i> Go to Reset Page
          </button>
        </div>
        
        <button onclick="closeResetLinkModal()" style="
          background: transparent;
          border: 2px solid #ddd;
          color: #666;
          padding: 10px 24px;
          border-radius: 8px;
          cursor: pointer;
          font-weight: 600;
          width: 100%;
          margin-top: 10px;
        ">
          Close
        </button>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHtml);
}

// Copy reset link to clipboard
function copyResetLink(link) {
  navigator.clipboard
    .writeText(link)
    .then(() => {
      alert("Reset link copied to clipboard!");
    })
    .catch((err) => {
      alert("Failed to copy link. Please copy it manually.");
    });
}

// Close reset link modal
function closeResetLinkModal() {
  const modal = document.getElementById("resetLinkModal");
  if (modal) {
    modal.remove();
  }
}

// Close modal when clicking outside
document.addEventListener("click", function (e) {
  const forgotModal = document.getElementById("forgotPasswordModal");
  if (e.target === forgotModal) {
    closeForgotPasswordModal();
  }

  const failedModal = document.getElementById("loginFailedModal");
  if (e.target === failedModal) {
    closeLoginFailedModal();
  }

  const successModal = document.getElementById("loginSuccessModal");
  if (e.target === successModal) {
    proceedLoginRedirect();
  }
});

// Escape key listener for modals
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    const failedModal = document.getElementById("loginFailedModal");
    if (failedModal && (failedModal.classList.contains("show") || failedModal.style.display === "flex")) {
      closeLoginFailedModal();
    }
  }
});

// Make functions globally available
window.openForgotPasswordModal = openForgotPasswordModal;
window.closeForgotPasswordModal = closeForgotPasswordModal;
window.copyResetLink = copyResetLink;
window.closeResetLinkModal = closeResetLinkModal;
window.showLoginSuccessModal = showLoginSuccessModal;
window.closeLoginSuccessModal = closeLoginSuccessModal;
window.proceedLoginRedirect = proceedLoginRedirect;
window.showLoginFailedModal = showLoginFailedModal;
window.closeLoginFailedModal = closeLoginFailedModal;
window.switchToForgotPasswordModal = switchToForgotPasswordModal;

