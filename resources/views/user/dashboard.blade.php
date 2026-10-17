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
    <title>User Dashboard - AR Homes Posadas Farm Resort</title>

    <!-- Favicon -->
    <link
      rel="icon"
      type="image/png"
      sizes="32x32"
      href="{{ asset('images/logo/ar-homes-logo.png') }}"
    />
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="{{ asset('images/logo/ar-homes-logo.png') }}"
    />
    <link
      rel="apple-touch-icon"
      sizes="180x180"
      href="{{ asset('images/logo/ar-homes-logo.png') }}"
    />
    <link rel="shortcut icon" href="{{ asset('images/logo/ar-homes-logo.png') }}" />

    <!-- Meta tags -->
    <meta name="theme-color" content="#667eea" />
    <meta
      name="description"
      content="User Dashboard for AR Homes Posadas Farm Resort - Manage your reservations and profile."
    />
    <meta
      name="keywords"
      content="dashboard, user, resort reservations, AR Homes, Posadas Farm"
    />
    <meta name="author" content="AR Homes Posadas Farm Resort" />

    <!-- Open Graph meta tags for social sharing -->
    <meta
      property="og:title"
      content="User Dashboard - AR Homes Posadas Farm Resort"
    />
    <meta
      property="og:description"
      content="Manage your reservations and profile at AR Homes Posadas Farm Resort."
    />
    <meta property="og:image" content="images/logo/ar-homes-logo.png" />
    <meta property="og:url" content="#" />
    <meta property="og:type" content="website" />

    <!-- Twitter Card meta tags -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta
      name="twitter:title"
      content="User Dashboard - AR Homes Posadas Farm Resort"
    />
    <meta
      name="twitter:description"
      content="Manage your reservations and profile at AR Homes Posadas Farm Resort."
    />
    <meta name="twitter:image" content="images/logo/ar-homes-logo.png" />

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/dashboard-styles.css?v=20261017007') }}" />
    <link rel="stylesheet" href="{{ asset('css/checkout-styles.css?v=1.0') }}" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Bungee+Spice&family=Dancing+Script:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <!-- Font Awesome for icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css"
    />

    <!-- Flatpickr for date picker with disabled dates -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
    />
  </head>

  <body>
    <!-- Logout Functions - Load First -->
    <script>
      console.log("[LOGOUT] Loading logout functions...");

      // Define logout functions immediately
      window.showLogoutPopup = function () {
        console.log("[LOGOUT] showLogoutPopup called");
        const popup = document.getElementById("logoutPopupOverlay");
        console.log("Popup element:", popup);
        if (popup) {
          popup.style.display = "flex";
          popup.style.visibility = "visible";
          popup.style.opacity = "1";
          console.log("[LOGOUT] Logout popup should be visible now");
        } else {
          console.error("[ERROR] logoutPopupOverlay element not found");
          alert("Logout popup not found. Redirecting to logout...");
          window.location.href = "user/logout.php";
        }
      };

      window.hideLogoutPopup = function () {
        console.log("[LOGOUT] hideLogoutPopup called");
        const popup = document.getElementById("logoutPopupOverlay");
        if (popup) {
          popup.style.display = "none";
          popup.style.visibility = "hidden";
          popup.style.opacity = "0";
          console.log("[LOGOUT] Logout popup hidden");
        }
      };

      window.confirmLogout = function () {
        console.log("[LOGOUT] confirmLogout called - redirecting...");
        window.location.href = "user/logout.php";
      };

      console.log("[LOGOUT] Logout functions loaded");
      console.log("showLogoutPopup available:", typeof window.showLogoutPopup);

      // Smooth Scroll Helper for Section Indicators
      window.handleSectionScroll = function (el, direction) {
        if (!el) return;
        if (direction === "up") {
          window.scrollTo({ top: 0, behavior: "smooth" });
          return;
        }
        const targetSelector = el.getAttribute("data-scroll-target");
        if (targetSelector) {
          const target = document.querySelector(targetSelector);
          if (target) {
            target.scrollIntoView({ behavior: "smooth", block: "start" });
            return;
          }
        }
        if (direction === "bottom") {
          window.scrollBy({ top: 600, behavior: "smooth" });
          return;
        }
        window.scrollBy({ top: 450, behavior: "smooth" });
      };

      window.scrollToNextSection = function (el) {
        window.handleSectionScroll(el, "down");
      };
    </script>

    <div class="dashboard-container">
      <!-- Header -->
      <header class="dashboard-header">
        <div class="header-left">
          <div class="logo">
            <img src="{{ asset('images/logo/ar-homes-logo.png') }}" alt="AR Homes Resort Logo" />
          </div>
          <div class="resort-info">
            <h1 class="brand-title">AR Homes Posadas</h1>
            <p class="brand-subtitle">Farm Resort &bull; Guest Portal</p>
          </div>
        </div>
        <div class="header-right">
          <div class="user-profile">
            <div class="profile-info">
              <span class="user-name" id="userName" style="opacity: 0"
                >Loading...</span
              >
              <span class="user-role">Guest</span>
            </div>
            <div class="profile-avatar">
              <i class="fas fa-user"></i>
            </div>
          </div>
          <button class="logout-btn" onclick="showLogoutPopup()">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </button>
        </div>
        <div class="mobile-toggle" onclick="toggleSidebar()">
          <i class="fas fa-bars"></i>
        </div>
      </header>

      <!-- Main Layout Container -->
      <div class="main-layout">
        <!-- Resort Gallery Section -->
        <div class="resort-gallery-section">
          <div class="resort-gallery">
            <div class="gallery-container">
              <div class="gallery-wrapper" id="galleryWrapper">
                <div class="gallery-slide active">
                  <img
                    src="{{ asset('images/545601129_1082516497373944_2891607710179489901_n.jpg') }}"
                    alt="Resort View 1"
                  />
                </div>
                <div class="gallery-slide">
                  <img
                    src="{{ asset('images/545611560_1324092662718848_8811536535004180762_n.jpg') }}"
                    alt="Resort View 2"
                  />
                </div>
                <div class="gallery-slide">
                  <img
                    src="{{ asset('images/546182776_2167385133738763_7006991891791172725_n.jpg') }}"
                    alt="Resort View 3"
                  />
                </div>
                <div class="gallery-slide">
                  <img
                    src="{{ asset('images/546238992_684641691315373_7119956179300816560_n.jpg') }}"
                    alt="Resort View 4"
                  />
                </div>
                <div class="gallery-slide">
                  <img
                    src="{{ asset('images/546260243_2896334557423631_287976220203457252_n.jpg') }}"
                    alt="Resort View 5"
                  />
                </div>
                <div class="gallery-slide">
                  <img
                    src="{{ asset('images/546407920_1244121977399879_5414686772811094703_n.jpg') }}"
                    alt="Resort View 6"
                  />
                </div>
                <div class="gallery-slide">
                  <img
                    src="{{ asset('images/Swimming Pools/Big Pool.jpg') }}"
                    alt="Big Pool"
                  />
                </div>
                <div class="gallery-slide">
                  <img
                    src="{{ asset('images/Swimming Pools/Jacuzzi Pool.jpg') }}"
                    alt="Jacuzzi Pool"
                  />
                </div>
              </div>
              <div class="gallery-controls">
                <button class="gallery-btn prev-btn" id="prevBtn" aria-label="Previous Slide">
                  <i class="fas fa-chevron-left"></i>
                </button>
                <button class="gallery-btn next-btn" id="nextBtn" aria-label="Next Slide">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Section -->
        <div class="navigation-section">
          <div class="horizontal-nav">
            <nav class="nav-menu-horizontal">
              <button class="nav-btn" data-section="dashboard">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
              </button>
              <button class="nav-btn" data-section="my-reservations">
                <i class="fas fa-calendar-plus"></i>
                <span>Make Reservation</span>
              </button>
              <button
                class="nav-btn booking-nav"
                id="booking-details-tab"
                data-section="booking-details"
                style="display: none"
              >
                <i class="fas fa-credit-card"></i>
                <span>Booking & Payment</span>
              </button>
              <button class="nav-btn" data-section="profile">
                <i class="fas fa-user-cog"></i>
                <span>Profile</span>
              </button>
              <button class="nav-btn" data-section="my-bookings">
                <i class="fas fa-clipboard-list"></i>
                <span>My Reservations</span>
              </button>
              <button class="nav-btn" data-section="bookings-history">
                <i class="fas fa-history"></i>
                <span>Reservation History</span>
              </button>
              <button class="nav-btn" data-section="rebooking-requests">
                <i class="fas fa-calendar-alt"></i>
                <span>Rebooking Requests</span>
                <span
                  class="notification-badge"
                  id="rebookingRequestsBadge"
                  style="display: none"
                  >0</span
                >
              </button>
              <button class="nav-btn" data-section="notifications">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <span
                  class="notification-badge"
                  id="notificationCount"
                  style="display: none"
                  >0</span
                >
              </button>
              <button class="nav-btn" data-section="reviews">
                <i class="fas fa-star"></i>
                <span>My Reviews</span>
              </button>
            </nav>
          </div>
        </div>

        <!-- Content Area (Half View) -->
        <div class="content-area" id="contentArea">
          <!-- Dashboard Content -->
          <div class="content-section" id="dashboard-section">
            <div class="section-header-inline" style="margin-bottom: 25px">
              <h2>
                <i class="fas fa-chart-line" style="color: #facc15"></i>
                Dashboard Overview
              </h2>
            </div>

            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#statsGrid" class="section-scroll-indicator scroll-down-btn" data-scroll-target=".stats-grid" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Stats &amp; Activity</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>

            <!-- Dashboard Container - Same width as Profile section -->
            <div style="max-width: 900px; margin: 0 auto">
              <div
                class="stats-grid"
                style="grid-template-columns: repeat(2, 1fr)"
              >
                <div class="stats-card">
                  <div class="stats-icon">
                    <i class="fas fa-calendar-alt"></i>
                  </div>
                  <div class="stats-content">
                    <h3 class="stats-number" id="totalReservations">0</h3>
                    <p class="stats-label">Total Reservations</p>
                  </div>
                </div>
                <div class="stats-card">
                  <div class="stats-icon pending">
                    <i class="fas fa-clock"></i>
                  </div>
                  <div class="stats-content">
                    <h3 class="stats-number" id="pendingReservations">0</h3>
                    <p class="stats-label">Pending Reservations</p>
                  </div>
                </div>
                <div class="stats-card">
                  <div class="stats-icon approved">
                    <i class="fas fa-check-circle"></i>
                  </div>
                  <div class="stats-content">
                    <h3 class="stats-number" id="approvedReservations">0</h3>
                    <p class="stats-label">Approved Reservations</p>
                  </div>
                </div>
                <div class="stats-card">
                  <div class="stats-icon completed">
                    <i class="fas fa-star"></i>
                  </div>
                  <div class="stats-content">
                    <h3 class="stats-number" id="completedStays">0</h3>
                    <p class="stats-label">Completed Stays</p>
                  </div>
                </div>
              </div>

              <div class="recent-activity">
                <h3>Recent Activity</h3>
                <div class="activity-list" id="activityList">
                  <!-- Activity items will be loaded dynamically -->
                  <div
                    class="activity-empty"
                    style="text-align: center; padding: 2.5rem 1rem; color: rgba(255, 255, 255, 0.6)"
                  >
                    <i
                      class="fas fa-inbox"
                      style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; color: #facc15"
                    ></i>
                    <p>No recent activity to display</p>
                  </div>
                </div>
              </div>

              <div class="section-scroll-indicator-wrap bottom-indicator">
                <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                  <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                  <span class="scroll-indicator-text">Back to Top</span>
                </a>
              </div>
            </div>
          </div>

          <!-- Make Reservation Content -->
          <div class="content-section" id="my-reservations-section">
            <div class="section-header-inline" style="margin-bottom: 25px">
              <h2><i class="fas fa-calendar-plus" style="color: #facc15"></i> Make a Reservation</h2>
            </div>

            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#policyNoticeWrap" class="section-scroll-indicator scroll-down-btn" data-scroll-target=".policy-notice" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Policies &amp; Packages</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>
            <!-- Booking Policy Notice -->
            <div class="policy-notice">
              <h2><i class="fas fa-shield-alt" style="color: #facc15"></i> Exclusive Booking &amp; Resort Policy</h2>
              <div class="policy-highlights">
                <div class="policy-item">
                  <i class="fas fa-calendar-check"></i>
                  <div>
                    <strong>Single Booking Exclusivity</strong>
                    <p>
                      The entire resort is exclusively yours for the selected time slot. No sharing with other groups.
                    </p>
                  </div>
                </div>
                <div class="policy-item">
                  <i class="fas fa-exclamation-triangle"></i>
                  <div>
                    <strong>Strictly Non-Refundable Deposit</strong>
                    <p>
                      Once settled, the ₱1,000 downpayment locks your date and is non-refundable. Rebooking is permitted.
                    </p>
                  </div>
                </div>
                <div class="policy-item">
                  <i class="fas fa-history"></i>
                  <div>
                    <strong>Flexible Rebooking (3 Months)</strong>
                    <p>
                      Cancellations are not permitted. You may reschedule to any open slot within 3 months of original date.
                    </p>
                  </div>
                </div>
                <div class="policy-item">
                  <i class="fas fa-wallet"></i>
                  <div>
                    <strong>₱1,000 Initial Downpayment</strong>
                    <p>
                      Locks your chosen slot immediately. Remaining resort balance is settled during arrival check-in.
                    </p>
                  </div>
                </div>
                <div class="policy-item">
                  <i class="fas fa-shield-alt"></i>
                  <div>
                    <strong>₱2,000 Security Bond</strong>
                    <p>
                      Received upon check-in and 100% refunded at checkout when resort amenities remain in good order.
                    </p>
                  </div>
                </div>
                <div class="policy-item">
                  <i class="fas fa-users"></i>
                  <div>
                    <strong>10 Pax Minimum • No Upper Limit</strong>
                    <p>
                      Minimum group size is 10 guests. Bring your family and friends with no limit on additional attendees.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 1: Select Package (Combined Booking Type & Package) -->
            <div class="booking-step active" id="step1">
              <h2>Step 1: Choose Your Package</h2>
              <p class="step-description">
                <strong><i class="fas fa-crown"></i> ALL PACKAGES INCLUDE FULL RESORT ACCESS <i class="fas fa-crown"></i></strong><br />
                Select the package that best fits your schedule and needs.
              </p>
              <div class="room-packages-grid" id="roomPackagesGrid">
                <!-- Daytime Package -->
                <div class="room-package-card" data-package="daytime">
                  <div class="package-header">
                    <h3>Daytime Package</h3>
                    <div class="package-badge budget">Budget Friendly</div>
                  </div>
                  <div class="package-content">
                    <div class="package-price-display">
                      <div class="package-price">
                        ₱<span id="daytime-price-main">6,000</span>
                      </div>
                      <div class="package-time">9:00 AM - 5:00 PM</div>
                      <div
                        style="
                          margin-top: 8px;
                          padding: 6px 8px;
                          background: #e3f2fd;
                          border-radius: 6px;
                          font-size: 0.8em;
                          color: #1565c0;
                          border-left: 3px solid #2196f3;
                        "
                      >
                        <i
                          class="fas fa-calendar-day"
                          style="margin-right: 5px"
                        ></i>
                        <strong>Duration:</strong> 1 Day Only (Same Day)
                      </div>
                      <div
                        style="
                          margin-top: 8px;
                          padding: 8px;
                          background: #fff3cd;
                          border-radius: 6px;
                          font-size: 0.85em;
                          color: #856404;
                          border-left: 3px solid #ffc107;
                        "
                      >
                        <i class="fas fa-clock" style="margin-right: 5px"></i>
                        <strong>Exceeding Hours:</strong> ₱500.00/hour
                      </div>
                    </div>

                    <div class="included-rooms">
                      <button
                        class="view-inclusions-btn"
                        onclick="openInclusionsModal('daytime')"
                        style="
                          width: 100%;
                          padding: 12px;
                          background: #f8f9fa;
                          border: 2px solid #e2e8f0;
                          border-radius: 8px;
                          cursor: pointer;
                          font-weight: 600;
                          color: #475569;
                          display: flex;
                          align-items: center;
                          justify-content: space-between;
                          transition: all 0.3s;
                        "
                      >
                        <span><i class="fas fa-list-check"></i> View Full Inclusions &amp; Rooms</span>
                        <i
                          class="fas fa-external-link-alt"
                          style="font-size: 0.9em"
                        ></i>
                      </button>
                      <div
                        class="inclusions-content"
                        id="inclusions-daytime"
                        style="display: none"
                      >
                        <h4 style="color: #bca71b">Inclusions:</h4>
                        <ul
                          style="
                            text-align: left;
                            padding-left: 20px;
                            margin: 15px 0;
                            font-size: 0.9em;
                          "
                        >
                          <li>
                            Free unlimited use of videoke until 10:00 PM only
                          </li>
                          <li>Free purified water</li>
                          <li>Free WiFi</li>
                          <li>Access to all pools</li>
                          <li>
                            Free use of kitchen, kitchenware, and barbecue area
                          </li>
                        </ul>
                        <h4 style="margin-top: 20px; color: #bca71b">Rooms:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room with VideoOke.jpg') }}"
                              alt="Room with Videoke"
                            />
                            <div class="room-info">
                              <span class="room-name">Room with Videoke</span>
                              <span class="room-features">Included</span>
                              <button
                                class="view-image-btn"
                                onclick="openImageLightbox('images/Rooms/Room with VideoOke.jpg', 'Room with Videoke')"
                              >
                                <i class="fas fa-search-plus"></i> View Image
                              </button>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px; color: #bca71b">
                          Swimming Pools:
                        </h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Swimming Pools/Big Pool.jpg') }}"
                              alt="Big Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Big Pool</span>
                              <span class="room-features">Access included</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Swimming Pools/Jacuzzi Pool.jpg') }}"
                              alt="Jacuzzi Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Jacuzzi Pool</span>
                              <span class="room-features">Access included</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Kitchen Areas:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Kitchen/Kitchen 1.jpg') }}"
                              alt="Kitchen 1"
                            />
                            <div class="room-info">
                              <span class="room-name">Kitchen Area 1</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Kitchen/Kitchen 2.jpg') }}"
                              alt="Kitchen 2"
                            />
                            <div class="room-info">
                              <span class="room-name">Kitchen Area 2</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Barbecue Area:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Barbecue Area/Barbeque Area.jpg') }}"
                              alt="Barbecue Area"
                            />
                            <div class="room-info">
                              <span class="room-name">Barbecue Area</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                        </div>
                        <div
                          style="
                            margin-top: 15px;
                            padding: 10px;
                            background: #fff3cd;
                            border-radius: 8px;
                            border-left: 4px solid #ffc107;
                          "
                        >
                          <strong style="font-size: 0.9em"
                            >NOT Included:</strong
                          >
                          <ul
                            style="
                              margin: 5px 0;
                              padding-left: 20px;
                              font-size: 0.85em;
                            "
                          >
                            <li>Triangle Room 1 & 2</li>
                            <li>Room Under the Pool</li>
                            <li>Kubo Room</li>
                            <li>Aircon Room</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <button
                      class="select-package-btn"
                      onclick="selectRoomPackageDirect('daytime')"
                    >
                      Select Daytime Package
                    </button>
                  </div>
                </div>

                <!-- Nighttime Package -->
                <div
                  class="room-package-card featured"
                  data-package="nighttime"
                >
                  <div class="package-header">
                    <h3>Nighttime Package</h3>
                    <div class="package-badge premium">Most Popular</div>
                  </div>
                  <div class="package-content">
                    <div class="package-price-display">
                      <div class="package-price">
                        ₱<span id="nighttime-price-main">10,000</span>
                      </div>
                      <div class="package-time">7:00 PM - 7:00 AM</div>
                      <div
                        style="
                          margin-top: 8px;
                          padding: 6px 8px;
                          background: #e3f2fd;
                          border-radius: 6px;
                          font-size: 0.8em;
                          color: #1565c0;
                          border-left: 3px solid #2196f3;
                        "
                      >
                        <i
                          class="fas fa-calendar-day"
                          style="margin-right: 5px"
                        ></i>
                        <strong>Duration:</strong> 2 Days (Night + Morning)
                      </div>
                      <div
                        style="
                          margin-top: 8px;
                          padding: 8px;
                          background: #fff3cd;
                          border-radius: 6px;
                          font-size: 0.85em;
                          color: #856404;
                          border-left: 3px solid #ffc107;
                        "
                      >
                        <i class="fas fa-clock" style="margin-right: 5px"></i>
                        <strong>Exceeding Hours:</strong> ₱500.00/hour
                      </div>
                    </div>

                    <div class="included-rooms">
                      <button
                        class="view-inclusions-btn"
                        onclick="openInclusionsModal('nighttime')"
                        style="
                          width: 100%;
                          padding: 12px;
                          background: #f8f9fa;
                          border: 2px solid #e2e8f0;
                          border-radius: 8px;
                          cursor: pointer;
                          font-weight: 600;
                          color: #475569;
                          display: flex;
                          align-items: center;
                          justify-content: space-between;
                          transition: all 0.3s;
                        "
                      >
                        <span><i class="fas fa-list-check"></i> View Full Inclusions &amp; Rooms</span>
                        <i
                          class="fas fa-external-link-alt"
                          style="font-size: 0.9em"
                        ></i>
                      </button>
                      <div
                        class="inclusions-content"
                        id="inclusions-nighttime"
                        style="display: none"
                      >
                        <h4>Inclusions:</h4>
                        <ul
                          style="
                            text-align: left;
                            padding-left: 20px;
                            margin: 15px 0;
                            font-size: 0.9em;
                          "
                        >
                          <li>
                            Free unlimited use of videoke until 10:00 PM only
                          </li>
                          <li>Full access to all amenities</li>
                          <li>Access to barbecue area</li>
                          <li>Free WiFi and purified water</li>
                        </ul>
                        <h4 style="margin-top: 20px">Rooms:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Triangle 1.jpg') }}"
                              alt="Triangle Room 1"
                            />
                            <div class="room-info">
                              <span class="room-name">Triangle Room 1</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Triangle 2.jpg') }}"
                              alt="Triangle Room 2"
                            />
                            <div class="room-info">
                              <span class="room-name">Triangle Room 2</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Under Pool.jpg') }}"
                              alt="Room Under the Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Room Under Pool</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Kubo Room.jpg') }}"
                              alt="Kubo Room"
                            />
                            <div class="room-info">
                              <span class="room-name">Kubo Room</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room with VideoOke.jpg') }}"
                              alt="Room with Videoke"
                            />
                            <div class="room-info">
                              <span class="room-name">Room with Videoke</span>
                              <span
                                class="room-features"
                                style="visibility: hidden"
                                >Placeholder</span
                              >
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Aircon Room - Capacity of 12 to 15  with CR.jpg') }}"
                              alt="Aircon Room"
                            />
                            <div class="room-info">
                              <span class="room-name">Aircon Room</span>
                              <span class="room-features">12-15 pax</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Swimming Pools:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Swimming Pools/Big Pool.jpg') }}"
                              alt="Big Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Big Pool</span>
                              <span class="room-features">Access included</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Swimming Pools/Jacuzzi Pool.jpg') }}"
                              alt="Jacuzzi Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Jacuzzi Pool</span>
                              <span class="room-features">Access included</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Kitchen Areas:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Kitchen/Kitchen 1.jpg') }}"
                              alt="Kitchen 1"
                            />
                            <div class="room-info">
                              <span class="room-name">Kitchen Area 1</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Kitchen/Kitchen 2.jpg') }}"
                              alt="Kitchen 2"
                            />
                            <div class="room-info">
                              <span class="room-name">Kitchen Area 2</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Barbecue Area:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Barbecue Area/Barbeque Area.jpg') }}"
                              alt="Barbecue Area"
                            />
                            <div class="room-info">
                              <span class="room-name">Barbecue Area</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <button
                      class="select-package-btn"
                      onclick="selectRoomPackageDirect('nighttime')"
                    >
                      Select Nighttime Package
                    </button>
                  </div>
                </div>

                <!-- 22 Hours Package -->
                <div class="room-package-card" data-package="22hours">
                  <div class="package-header">
                    <h3>22 Hours Package</h3>
                    <div class="package-badge comfort">Extended Stay</div>
                  </div>
                  <div class="package-content">
                    <div class="package-price-display">
                      <div class="package-price">
                        ₱<span id="22hours-price-main">18,000</span>
                      </div>
                      <div class="package-time">
                        2:00 PM - 12:00 NN (Next Day)
                      </div>
                      <div
                        style="
                          margin-top: 8px;
                          padding: 6px 8px;
                          background: #e3f2fd;
                          border-radius: 6px;
                          font-size: 0.8em;
                          color: #1565c0;
                          border-left: 3px solid #2196f3;
                        "
                      >
                        <i
                          class="fas fa-calendar-day"
                          style="margin-right: 5px"
                        ></i>
                        <strong>Duration:</strong> 2 Days (22 Hours)
                      </div>
                      <div
                        style="
                          margin-top: 8px;
                          padding: 8px;
                          background: #fff3cd;
                          border-radius: 6px;
                          font-size: 0.85em;
                          color: #856404;
                          border-left: 3px solid #ffc107;
                        "
                      >
                        <i class="fas fa-clock" style="margin-right: 5px"></i>
                        <strong>Exceeding Hours:</strong> ₱500.00/hour
                      </div>
                    </div>

                    <div class="included-rooms">
                      <button
                        class="view-inclusions-btn"
                        onclick="openInclusionsModal('22hours')"
                        style="
                          width: 100%;
                          padding: 12px;
                          background: #f8f9fa;
                          border: 2px solid #e2e8f0;
                          border-radius: 8px;
                          cursor: pointer;
                          font-weight: 600;
                          color: #475569;
                          display: flex;
                          align-items: center;
                          justify-content: space-between;
                          transition: all 0.3s;
                        "
                      >
                        <span><i class="fas fa-list-check"></i> View Full Inclusions &amp; Rooms</span>
                        <i
                          class="fas fa-external-link-alt"
                          style="font-size: 0.9em"
                        ></i>
                      </button>
                      <div
                        class="inclusions-content"
                        id="inclusions-22hours"
                        style="display: none"
                      >
                        <h4>Inclusions:</h4>
                        <ul
                          style="
                            text-align: left;
                            padding-left: 20px;
                            margin: 15px 0;
                            font-size: 0.9em;
                          "
                        >
                          <li>
                            Free unlimited use of videoke until 10:00 PM only
                          </li>
                          <li>All-access to all amenities and all rooms</li>
                          <li>Includes barbecue area</li>
                          <li>Ideal for extended stays</li>
                          <li>Free WiFi and purified water</li>
                        </ul>
                        <h4 style="margin-top: 20px">Rooms:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Triangle 1.jpg') }}"
                              alt="Triangle Room 1"
                            />
                            <div class="room-info">
                              <span class="room-name">Triangle Room 1</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Triangle 2.jpg') }}"
                              alt="Triangle Room 2"
                            />
                            <div class="room-info">
                              <span class="room-name">Triangle Room 2</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Under Pool.jpg') }}"
                              alt="Room Under the Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Room Under Pool</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Kubo Room.jpg') }}"
                              alt="Kubo Room"
                            />
                            <div class="room-info">
                              <span class="room-name">Kubo Room</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room with VideoOke.jpg') }}"
                              alt="Room with Videoke"
                            />
                            <div class="room-info">
                              <span class="room-name">Room with Videoke</span>
                              <span
                                class="room-features"
                                style="visibility: hidden"
                                >Placeholder</span
                              >
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Aircon Room - Capacity of 12 to 15  with CR.jpg') }}"
                              alt="Aircon Room"
                            />
                            <div class="room-info">
                              <span class="room-name">Aircon Room</span>
                              <span class="room-features">12-15 pax</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Swimming Pools:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Swimming Pools/Big Pool.jpg') }}"
                              alt="Big Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Big Pool</span>
                              <span class="room-features">Access included</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Swimming Pools/Jacuzzi Pool.jpg') }}"
                              alt="Jacuzzi Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Jacuzzi Pool</span>
                              <span class="room-features">Access included</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Kitchen Areas:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Kitchen/Kitchen 1.jpg') }}"
                              alt="Kitchen 1"
                            />
                            <div class="room-info">
                              <span class="room-name">Kitchen Area 1</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Kitchen/Kitchen 2.jpg') }}"
                              alt="Kitchen 2"
                            />
                            <div class="room-info">
                              <span class="room-name">Kitchen Area 2</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Barbecue Area:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Barbecue Area/Barbeque Area.jpg') }}"
                              alt="Barbecue Area"
                            />
                            <div class="room-info">
                              <span class="room-name">Barbecue Area</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <button
                      class="select-package-btn"
                      onclick="selectRoomPackageDirect('22hours')"
                    >
                      Select 22 Hours Package
                    </button>
                  </div>
                </div>

                <!-- Venue for All Occasions Package -->
                <div class="room-package-card" data-package="venue">
                  <div class="package-header">
                    <h3>Venue for All Occasions</h3>
                    <div class="package-badge custom">Special Events</div>
                  </div>
                  <div class="package-content">
                    <div class="package-price-display">
                      <div class="package-price">
                        <span style="font-size: 0.5em; display: inline"
                          >Starting at</span
                        >
                        ₱<span id="venue-price-main">6,000</span>
                      </div>
                      <div class="package-time">Based on Time Slot</div>
                      <div
                        style="
                          margin-top: 8px;
                          padding: 6px 8px;
                          background: #e3f2fd;
                          border-radius: 6px;
                          font-size: 0.8em;
                          color: #1565c0;
                          border-left: 3px solid #2196f3;
                        "
                      >
                        <i
                          class="fas fa-calendar-day"
                          style="margin-right: 5px"
                        ></i>
                        <strong>Duration:</strong> Flexible (Based on Slot)
                      </div>
                      <div
                        style="
                          margin-top: 8px;
                          padding: 8px;
                          background: #fff3cd;
                          border-radius: 6px;
                          font-size: 0.85em;
                          color: #856404;
                          border-left: 3px solid #ffc107;
                        "
                      >
                        <i class="fas fa-clock" style="margin-right: 5px"></i>
                        <strong>Exceeding Hours:</strong> ₱500.00/hour
                      </div>
                    </div>

                    <div class="included-rooms">
                      <button
                        class="view-inclusions-btn"
                        onclick="openInclusionsModal('venue')"
                        style="
                          width: 100%;
                          padding: 12px;
                          background: #f8f9fa;
                          border: 2px solid #e2e8f0;
                          border-radius: 8px;
                          cursor: pointer;
                          font-weight: 600;
                          color: #475569;
                          display: flex;
                          align-items: center;
                          justify-content: space-between;
                          transition: all 0.3s;
                        "
                      >
                        <span><i class="fas fa-list-check"></i> View Full Inclusions &amp; Rooms</span>
                        <i
                          class="fas fa-external-link-alt"
                          style="font-size: 0.9em"
                        ></i>
                      </button>
                      <div
                        class="inclusions-content"
                        id="inclusions-venue"
                        style="display: none"
                      >
                        <h4>Perfect For:</h4>
                        <ul
                          style="
                            text-align: left;
                            padding-left: 20px;
                            margin: 15px 0;
                            font-size: 0.9em;
                          "
                        >
                          <li><i class="fas fa-birthday-cake" style="color: #facc15; margin-right: 8px;"></i> Birthday Parties</li>
                          <li><i class="fas fa-ring" style="color: #facc15; margin-right: 8px;"></i> Wedding Receptions</li>
                          <li><i class="fas fa-briefcase" style="color: #facc15; margin-right: 8px;"></i> Corporate Events</li>
                          <li><i class="fas fa-users" style="color: #facc15; margin-right: 8px;"></i> Family Reunions</li>
                          <li><i class="fas fa-graduation-cap" style="color: #facc15; margin-right: 8px;"></i> Graduation Celebrations</li>
                          <li><i class="fas fa-glass-cheers" style="color: #facc15; margin-right: 8px;"></i> Any Special Occasion</li>
                        </ul>
                        <h4 style="margin-top: 20px">Inclusions:</h4>
                        <ul
                          style="
                            text-align: left;
                            padding-left: 20px;
                            margin: 15px 0;
                            font-size: 0.9em;
                          "
                        >
                          <li>
                            Free unlimited use of videoke until 10:00 PM only
                          </li>
                          <li>Full access to all amenities</li>
                          <li>Access to barbecue area</li>
                          <li>Free WiFi and purified water</li>
                        </ul>
                        <h4 style="margin-top: 15px; font-size: 0.95em">
                          Pricing Options:
                        </h4>
                        <div
                          style="
                            background: #f8f9fa;
                            padding: 10px;
                            border-radius: 6px;
                            margin: 10px 0;
                          "
                        >
                          <div
                            style="
                              display: grid;
                              grid-template-columns: repeat(3, 1fr);
                              gap: 6px;
                              text-align: center;
                            "
                          >
                            <div
                              style="
                                padding: 6px;
                                background: white;
                                border-radius: 4px;
                                border: 2px solid #ffd93d;
                              "
                            >
                              <strong style="color: #f59e0b; font-size: 0.75em"
                                >Daytime</strong
                              >
                              <div
                                style="
                                  font-size: 0.95em;
                                  font-weight: bold;
                                  color: #1e293b;
                                  margin-top: 2px;
                                "
                              >
                                ₱6,000
                              </div>
                              <div style="font-size: 0.65em; color: #64748b">
                                9AM - 5PM
                              </div>
                            </div>
                            <div
                              style="
                                padding: 6px;
                                background: white;
                                border-radius: 4px;
                                border: 2px solid #667eea;
                              "
                            >
                              <strong style="color: #667eea; font-size: 0.75em"
                                >Nighttime</strong
                              >
                              <div
                                style="
                                  font-size: 0.95em;
                                  font-weight: bold;
                                  color: #1e293b;
                                  margin-top: 2px;
                                "
                              >
                                ₱10,000
                              </div>
                              <div style="font-size: 0.65em; color: #64748b">
                                7PM - 7AM
                              </div>
                            </div>
                            <div
                              style="
                                padding: 6px;
                                background: white;
                                border-radius: 4px;
                                border: 2px solid #f093fb;
                              "
                            >
                              <strong style="color: #f093fb; font-size: 0.75em"
                                >22 Hours</strong
                              >
                              <div
                                style="
                                  font-size: 0.95em;
                                  font-weight: bold;
                                  color: #1e293b;
                                  margin-top: 2px;
                                "
                              >
                                ₱18,000
                              </div>
                              <div style="font-size: 0.65em; color: #64748b">
                                2PM - 12NN
                              </div>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Rooms:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Triangle 1.jpg') }}"
                              alt="Triangle Room 1"
                            />
                            <div class="room-info">
                              <span class="room-name">Triangle Room 1</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Triangle 2.jpg') }}"
                              alt="Triangle Room 2"
                            />
                            <div class="room-info">
                              <span class="room-name">Triangle Room 2</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room Under Pool.jpg') }}"
                              alt="Room Under the Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Room Under Pool</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Kubo Room.jpg') }}"
                              alt="Kubo Room"
                            />
                            <div class="room-info">
                              <span class="room-name">Kubo Room</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Room with VideoOke.jpg') }}"
                              alt="Room with Videoke"
                            />
                            <div class="room-info">
                              <span class="room-name">Room with Videoke</span>
                              <span
                                class="room-features"
                                style="visibility: hidden"
                                >Placeholder</span
                              >
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Rooms/Aircon Room - Capacity of 12 to 15  with CR.jpg') }}"
                              alt="Aircon Room"
                            />
                            <div class="room-info">
                              <span class="room-name">Aircon Room</span>
                              <span class="room-features">12-15 pax</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Swimming Pools:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Swimming Pools/Big Pool.jpg') }}"
                              alt="Big Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Big Pool</span>
                              <span class="room-features">Access included</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Swimming Pools/Jacuzzi Pool.jpg') }}"
                              alt="Jacuzzi Pool"
                            />
                            <div class="room-info">
                              <span class="room-name">Jacuzzi Pool</span>
                              <span class="room-features">Access included</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Kitchen Areas:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Kitchen/Kitchen 1.jpg') }}"
                              alt="Kitchen 1"
                            />
                            <div class="room-info">
                              <span class="room-name">Kitchen Area 1</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Kitchen/Kitchen 2.jpg') }}"
                              alt="Kitchen 2"
                            />
                            <div class="room-info">
                              <span class="room-name">Kitchen Area 2</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                        </div>
                        <h4 style="margin-top: 20px">Barbecue Area:</h4>
                        <div class="room-list">
                          <div class="room-item">
                            <img
                              src="{{ asset('images/Barbecue Area/Barbeque Area.jpg') }}"
                              alt="Barbecue Area"
                            />
                            <div class="room-info">
                              <span class="room-name">Barbecue Area</span>
                              <span class="room-features">Free use</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <button
                      class="select-package-btn"
                      onclick="openVenueTimeSelector()"
                    >
                      Select Venue Package
                    </button>
                  </div>
                </div>
              </div>
              <div class="section-scroll-indicator-wrap bottom-indicator">
                <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                  <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                  <span class="scroll-indicator-text">Back to Top</span>
                </a>
              </div>
            </div>

            <!-- Step 2: Booking Details (was Step 3) -->
            <div class="booking-step" id="step2" style="display: none">
              <div class="step2-header">
                <div class="step2-header-icon">
                  <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="step2-header-text">
                  <h2>Complete Your Booking</h2>
                  <p>Review your selection and fill in the details</p>
                </div>
              </div>

              <div class="section-scroll-indicator-wrap top-indicator">
                <a href="#reservationForm" class="section-scroll-indicator scroll-down-btn" data-scroll-target="#reservationForm" onclick="handleSectionScroll(this, 'down'); return false;">
                  <span class="scroll-indicator-text">Scroll Down to Booking Form</span>
                  <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                  <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
                </a>
              </div>

              <div class="step2-container">
                <!-- Left Column - Form -->
                <div class="step2-form-column">
                  <!-- Selected Booking Summary Card -->
                  <div class="booking-summary-card" id="selectedSummary">
                    <div class="summary-card-header">
                      <i class="fas fa-bookmark"></i>
                      <span>Your Selection</span>
                    </div>
                    <div class="summary-card-body">
                      <div class="summary-badge" id="summaryBookingType">-</div>
                      <div class="summary-details">
                        <div class="summary-detail-item">
                          <i class="fas fa-box-open"></i>
                          <div class="detail-content">
                            <span class="detail-label">Package</span>
                            <span class="detail-value" id="summaryPackage"
                              >-</span
                            >
                          </div>
                        </div>
                        <div class="summary-time-row">
                          <div class="time-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <div>
                              <span class="time-label">Check-in</span>
                              <span class="time-value" id="summaryCheckInTime"
                                >-</span
                              >
                            </div>
                          </div>
                          <div class="time-divider">
                            <i class="fas fa-arrow-right"></i>
                          </div>
                          <div class="time-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <div>
                              <span class="time-label">Check-out</span>
                              <span class="time-value" id="summaryCheckOutTime"
                                >-</span
                              >
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Booking Form -->
                  <form id="reservationForm" class="reservation-form-v2">
                    <!-- Date Selection Card -->
                    <div class="form-card">
                      <div class="form-card-header">
                        <div class="form-card-icon date-icon">
                          <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Select Date</h3>
                      </div>
                      <div class="form-card-body">
                        <div class="date-field-container">
                          <label for="checkInDate"
                            >Check-in Date
                            <span class="required">*</span></label
                          >
                          <div class="date-input-wrapper">
                            <div class="custom-date-input">
                              <i class="fas fa-calendar"></i>
                              <input
                                type="date"
                                id="checkInDate"
                                name="checkInDate"
                                required
                              />
                            </div>
                          </div>
                          <div class="date-hint-box">
                            <i class="fas fa-hand-pointer"></i>
                            <span>Click the field above to open calendar</span>
                          </div>
                        </div>
                        <input
                          type="hidden"
                          id="duration"
                          name="duration"
                          value="1"
                        />
                      </div>
                    </div>

                    <!-- Group Details Card -->
                    <div class="form-card">
                      <div class="form-card-header">
                        <div class="form-card-icon group-icon">
                          <i class="fas fa-users"></i>
                        </div>
                        <h3>Group Details</h3>
                      </div>
                      <div class="form-card-body">
                        <!-- Guest Info Notice -->
                        <div class="guest-info-notice">
                          <div class="guest-info-icon">
                            <i class="fas fa-users"></i>
                          </div>
                          <div class="guest-info-content">
                            <span class="guest-info-title"
                              >Minimum: 10 pax • No maximum limit</span
                            >
                            <span class="guest-info-desc"
                              >A minimum of 10 guests is required per booking.
                              You may bring as many guests as you like.</span
                            >
                          </div>
                        </div>

                        <input
                          type="hidden"
                          id="groupSize"
                          name="groupSize"
                          value="10+"
                        />

                        <div class="form-field">
                          <label for="groupType">
                            <i class="fas fa-tag"></i>
                            Group Type <span class="required">*</span>
                          </label>
                          <div class="select-wrapper">
                            <select id="groupType" name="groupType" required>
                              <option value="">Select group type</option>
                              <option value="family">Family</option>
                              <option value="friends">Friends</option>
                              <option value="corporate">Corporate</option>
                              <option value="celebration">Celebration</option>
                              <option value="other">Other</option>
                            </select>
                            <i class="fas fa-chevron-down"></i>
                          </div>
                        </div>

                        <div
                          class="form-field full-width"
                          style="margin-top: 1rem"
                        >
                          <label for="specialRequests">
                            <i class="fas fa-comment-dots"></i>
                            Special Requests
                            <span class="optional">(Optional)</span>
                          </label>
                          <textarea
                            id="specialRequests"
                            name="specialRequests"
                            rows="3"
                            placeholder="Tell us about any special arrangements, dietary requirements, or accessibility needs..."
                          ></textarea>
                        </div>
                      </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="terms-card">
                      <label class="custom-checkbox">
                        <input
                          type="checkbox"
                          id="agreeTerms"
                          name="agreeTerms"
                          required
                        />
                        <span class="checkmark"></span>
                        <span class="terms-text">
                          <i class="fas fa-file-contract"></i>
                          I agree to the
                          <a
                            href="#"
                            onclick="showBookingPolicy(); return false;"
                            >booking policies</a
                          >
                          and house rules
                        </span>
                      </label>
                    </div>

                    <!-- Hidden payment method field -->
                    <input
                      type="hidden"
                      id="paymentMethodInput"
                      name="paymentMethod"
                      value="gcash"
                    />

                    <!-- Submit Button (inside form) -->
                    <div class="form-submit-section">
                      <button
                        type="button"
                        class="btn-back-v2"
                        onclick="goBackToRooms()"
                      >
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                      </button>
                      <button type="submit" class="btn-submit-v2">
                        <span>Submit Reservation</span>
                        <i class="fas fa-arrow-right"></i>
                      </button>
                    </div>
                  </form>
                </div>

                <!-- Right Column - Price Summary -->
                <div class="step2-price-column">
                  <div class="price-summary-card">
                    <div class="price-card-header">
                      <i class="fas fa-receipt"></i>
                      <span>Price Summary</span>
                    </div>
                    <div class="price-card-body">
                      <div class="price-line main-price">
                        <span class="price-label">
                          <i class="fas fa-tag"></i>
                          Package Price
                        </span>
                        <span class="price-value" id="priceTotal">₱0.00</span>
                      </div>

                      <div class="price-divider"></div>

                      <div class="price-line highlight-orange">
                        <span class="price-label">
                          <i class="fas fa-hand-holding-usd"></i>
                          Required Downpayment
                        </span>
                        <span class="price-value" id="priceDownpayment"
                          >₱1,000.00</span
                        >
                      </div>

                      <div class="price-line">
                        <span class="price-label">
                          <i class="fas fa-wallet"></i>
                          Balance (Pay on arrival)
                        </span>
                        <span class="price-value" id="priceBalance">₱0.00</span>
                      </div>

                      <div class="price-line highlight-teal">
                        <span class="price-label">
                          <i class="fas fa-shield-alt"></i>
                          Security Bond
                          <span
                            class="info-tooltip"
                            title="Refundable after checkout"
                          >
                            <i class="fas fa-info-circle"></i>
                          </span>
                        </span>
                        <span class="price-value">₱2,000.00</span>
                      </div>
                    </div>

                    <div class="price-card-footer">
                      <div class="total-due">
                        <span>Total Due Now</span>
                        <span class="total-amount">₱1,000.00</span>
                      </div>
                    </div>
                  </div>

                  <!-- Payment Method Card -->
                  <div class="payment-method-card">
                    <div class="payment-card-header">
                      <i class="fas fa-credit-card"></i>
                      <span>Payment Method</span>
                    </div>
                    <div class="payment-card-body">
                      <div class="secure-badge">
                        <i class="fas fa-lock"></i>
                        <span>Secure PayMongo Checkout</span>
                      </div>
                      <div class="payment-display">
                        <div class="payment-option-content selected">
                          <div class="payment-icons">
                            <div class="payment-icon gcash">
                              <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="payment-icon card">
                              <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="payment-icon bank">
                              <i class="fas fa-university"></i>
                            </div>
                          </div>
                          <div class="payment-text">
                            <span class="payment-title"
                              >GCash, Card, or Bank</span
                            >
                            <span class="payment-subtitle"
                              >Fast & secure payment</span
                            >
                          </div>
                          <div class="payment-check active">
                            <i class="fas fa-check-circle"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="section-scroll-indicator-wrap bottom-indicator">
                <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                  <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                  <span class="scroll-indicator-text">Back to Top</span>
                </a>
              </div>
            </div>
          </div>

          <!-- Booking Details & Payment Content -->
          <div class="content-section" id="booking-details-section">
            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#bookingFormWrap" class="section-scroll-indicator scroll-down-btn" data-scroll-target=".booking-form" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Booking &amp; Payment Details</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>
            <!-- Selected Room Info -->
            <div class="selected-room-info" id="selectedRoomInfo">
              <!-- Room info will be populated by JavaScript -->
            </div>

            <!-- Booking Details Form -->
            <div class="booking-form">
              <!-- All booking form content will be here -->
              <div class="exclusive-access-section">
                <h3><i class="fas fa-umbrella-beach"></i> Exclusive Resort Access for Your Group</h3>
                <p class="exclusive-description">
                  Book the entire resort exclusively for your family or group of
                  friends! Enjoy complete privacy with access to all pools,
                  facilities, and accommodations.
                </p>

                <div class="reservation-types">
                  <div class="reservation-type-option" data-type="whole-day">
                    <input
                      type="radio"
                      id="wholeDay"
                      name="reservationType"
                      value="whole-day"
                      required
                    />
                    <label for="wholeDay" class="reservation-type-label">
                      <div class="reservation-type-icon day-use">
                        <i class="fas fa-sun"></i>
                      </div>
                      <div class="reservation-type-info">
                        <h4>Whole Day Access</h4>
                        <p>8:00 AM - 10:00 PM exclusive resort access</p>
                        <p class="access-details">
                          <i class="fas fa-swimming-pool"></i> All pools &bull; <i class="fas fa-home"></i> Day rooms &bull; <i class="fas fa-utensils"></i> BBQ areas
                        </p>
                        <span class="reservation-type-price"
                          >₱15,000 per day</span
                        >
                      </div>
                    </label>
                  </div>

                  <div class="reservation-type-option" data-type="overnight">
                    <input
                      type="radio"
                      id="overnight"
                      name="reservationType"
                      value="overnight"
                      required
                    />
                    <label for="overnight" class="reservation-type-label">
                      <div class="reservation-type-icon overnight">
                        <i class="fas fa-moon"></i>
                      </div>
                      <div class="reservation-type-info">
                        <h4>Overnight Exclusive</h4>
                        <p>
                          24-hour exclusive resort access with accommodation
                        </p>
                        <p class="access-details">
                          <i class="fas fa-swimming-pool"></i> All pools &bull; <i class="fas fa-bed"></i> All rooms &bull; <i class="fas fa-utensils"></i> Kitchen access
                        </p>
                        <span class="reservation-type-price"
                          >₱25,000 per night</span
                        >
                      </div>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Duration and Date Selection -->
              <div
                class="duration-selection-section"
                id="durationSelectionSection"
                style="display: none"
              >
                <div class="form-row" id="wholeDayRow" style="display: none">
                  <div class="form-group">
                    <label for="startDate">Start Date</label>
                    <input type="date" id="startDate" required />
                  </div>
                  <div class="form-group">
                    <label for="numberOfDays">How many days?</label>
                    <select id="numberOfDays" required>
                      <option value="">Select duration</option>
                      <option value="1">1 Day</option>
                      <option value="2">2 Days</option>
                      <option value="3">3 Days</option>
                      <option value="4">4 Days</option>
                      <option value="5">5 Days</option>
                      <option value="6">6 Days</option>
                      <option value="7">1 Week</option>
                    </select>
                  </div>
                </div>

                <div class="form-row" id="overnightRow" style="display: none">
                  <div class="form-group">
                    <label for="checkInDate">Check-in Date</label>
                    <input type="date" id="checkInDate" required />
                  </div>
                  <div class="form-group">
                    <label for="numberOfNights">How many nights?</label>
                    <select id="numberOfNights" required>
                      <option value="">Select duration</option>
                      <option value="1">1 Night</option>
                      <option value="2">2 Nights</option>
                      <option value="3">3 Nights</option>
                      <option value="4">4 Nights</option>
                      <option value="5">5 Nights</option>
                      <option value="6">6 Nights</option>
                      <option value="7">1 Week</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Group Details -->
              <div
                class="group-details-section"
                id="groupDetailsSection"
                style="display: none"
              >
                <div class="form-row">
                  <div class="form-group">
                    <label for="groupSize">Total Group Size</label>
                    <select id="groupSize" required>
                      <option value="">Select group size</option>
                      <option value="5-10">5-10 people</option>
                      <option value="11-20">11-20 people</option>
                      <option value="21-30">21-30 people</option>
                      <option value="31-40">31-40 people</option>
                      <option value="41-50">41-50 people</option>
                      <option value="50+">50+ people</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="groupType">Group Type</label>
                    <select id="groupType" required>
                      <option value="">Select type</option>
                      <option value="family">Family Reunion</option>
                      <option value="friends">Friends Getaway</option>
                      <option value="corporate">Corporate Event</option>
                      <option value="celebration">Special Celebration</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label for="specialRequests"
                    >Special Requests (Optional)</label
                  >
                  <textarea
                    id="specialRequests"
                    placeholder="Any special requests, dietary requirements, or additional services needed..."
                  ></textarea>
                </div>
              </div>

              <div class="booking-summary">
                <div class="summary-item">
                  <span>Room Type:</span>
                  <span id="summaryRoom">-</span>
                </div>
                <div class="summary-item">
                  <span>Price per night:</span>
                  <span id="summaryPrice">-</span>
                </div>
                <div class="summary-item">
                  <span>Number of nights:</span>
                  <span id="summaryNights">-</span>
                </div>
                <div class="summary-item total">
                  <span>Total Amount:</span>
                  <span id="summaryTotal">-</span>
                </div>
              </div>

              <!-- Payment Method Selection -->
              <div class="payment-method-section">
                <h3><i class="fas fa-check-circle" style="color: #4ade80"></i> Payment Method (PayMongo)</h3>
                <div class="payment-methods">
                  <div class="payment-option" data-method="gcash">
                    <input
                      type="radio"
                      id="gcash"
                      name="paymentMethod"
                      value="gcash"
                      required
                      checked
                    />
                    <label for="gcash" class="payment-label">
                      <div class="payment-icon gcash">
                        <i class="fas fa-mobile-alt"></i>
                      </div>
                      <div class="payment-info">
                        <h4>GCash / Card / Online Banking</h4>
                        <p>
                          All payments are processed through PayMongo's secure
                          checkout page. After submitting your booking, you will
                          be redirected to a safe, encrypted payment page where
                          you can pay using GCash, debit/credit card, or
                          supported online banking.
                        </p>
                        <span class="payment-badge">Secure PayMongo</span>
                      </div>
                    </label>
                  </div>
                </div>
              </div>

              <div class="booking-actions">
                <button
                  type="button"
                  class="btn-back"
                  onclick="goBackToRooms()"
                >
                  Back to Rooms
                </button>
                <button
                  type="button"
                  class="btn-proceed"
                  onclick="proceedToPayment()"
                >
                  Complete Booking
                </button>
              </div>
              <div class="section-scroll-indicator-wrap bottom-indicator">
                <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                  <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                  <span class="scroll-indicator-text">Back to Top</span>
                </a>
              </div>
            </div>
          </div>

          <!-- Profile Content -->
          <div class="content-section" id="profile-section">
            <div class="section-header-inline" style="margin-bottom: 25px">
              <h2>
                <i class="fas fa-user" style="color: #11224e"></i> Profile
              </h2>
            </div>

            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#profileCard" class="section-scroll-indicator scroll-down-btn" data-scroll-target=".profile-single-card" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Profile Details</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>

            <!-- Single Profile Card -->
            <div
              class="profile-single-card"
              style="
                background: white;
                border-radius: 12px;
                padding: 25px;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
                border: 2px solid #11224e;
                max-width: 800px;
                margin: 0 auto;
              "
            >
              <!-- Profile Stats -->
              <div
                style="
                  display: flex;
                  justify-content: space-around;
                  flex-wrap: wrap;
                  gap: 20px;
                  padding-bottom: 20px;
                  border-bottom: 1px solid #e5e7eb;
                  margin-bottom: 25px;
                "
              >
                <div style="text-align: center">
                  <div
                    style="font-size: 2em; font-weight: 700; color: #11224e"
                    id="profileTotalBookings"
                  >
                    0
                  </div>
                  <div style="font-size: 0.85em; color: #64748b">
                    Total Reservations
                  </div>
                </div>
                <div style="text-align: center">
                  <div
                    style="font-size: 2em; font-weight: 700; color: #11224e"
                    id="profileReviewsGiven"
                  >
                    0
                  </div>
                  <div style="font-size: 0.85em; color: #64748b">
                    Reviews Given
                  </div>
                </div>
                <div style="text-align: center">
                  <div
                    style="font-size: 2em; font-weight: 700; color: #11224e"
                    id="profileMemberSince"
                  >
                    2024
                  </div>
                  <div style="font-size: 0.85em; color: #64748b">
                    Member Since
                  </div>
                </div>
              </div>

              <!-- Personal Information -->
              <div style="margin-bottom: 25px">
                <h3
                  style="
                    color: #11224e;
                    font-size: 1.1em;
                    margin-bottom: 15px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                  "
                >
                  <i class="fas fa-user-circle"></i> Personal Information
                </h3>
                <div
                  style="
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                  "
                >
                  <div style="display: flex; align-items: center; gap: 10px">
                    <i
                      class="fas fa-user"
                      style="color: #11224e; width: 20px"
                    ></i>
                    <div>
                      <div style="font-size: 0.75em; color: #64748b">
                        Given Name
                      </div>
                      <div
                        style="font-weight: 600; color: #1e293b"
                        id="displayGivenName"
                      >
                        -
                      </div>
                    </div>
                  </div>
                  <div style="display: flex; align-items: center; gap: 10px">
                    <i
                      class="fas fa-user"
                      style="color: #11224e; width: 20px"
                    ></i>
                    <div>
                      <div style="font-size: 0.75em; color: #64748b">
                        Middle Name
                      </div>
                      <div
                        style="font-weight: 600; color: #1e293b"
                        id="displayMiddleName"
                      >
                        -
                      </div>
                    </div>
                  </div>
                  <div style="display: flex; align-items: center; gap: 10px">
                    <i
                      class="fas fa-user"
                      style="color: #11224e; width: 20px"
                    ></i>
                    <div>
                      <div style="font-size: 0.75em; color: #64748b">
                        Last Name
                      </div>
                      <div
                        style="font-weight: 600; color: #1e293b"
                        id="displayLastName"
                      >
                        -
                      </div>
                    </div>
                  </div>
                  <div style="display: flex; align-items: center; gap: 10px">
                    <i
                      class="fas fa-user-tag"
                      style="color: #11224e; width: 20px"
                    ></i>
                    <div>
                      <div style="font-size: 0.75em; color: #64748b">
                        Username
                      </div>
                      <div
                        style="font-weight: 600; color: #1e293b"
                        id="displayUsername"
                      >
                        -
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Contact Information -->
              <div
                style="
                  margin-bottom: 25px;
                  padding-top: 20px;
                  border-top: 1px solid #e5e7eb;
                "
              >
                <h3
                  style="
                    color: #11224e;
                    font-size: 1.1em;
                    margin-bottom: 15px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                  "
                >
                  <i class="fas fa-address-book"></i> Contact Information
                </h3>
                <div
                  style="
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                  "
                >
                  <div style="display: flex; align-items: center; gap: 10px">
                    <i
                      class="fas fa-envelope"
                      style="color: #11224e; width: 20px"
                    ></i>
                    <div>
                      <div style="font-size: 0.75em; color: #64748b">
                        Email Address
                      </div>
                      <div
                        style="font-weight: 600; color: #1e293b"
                        id="displayEmail"
                      >
                        -
                      </div>
                    </div>
                  </div>
                  <div style="display: flex; align-items: center; gap: 10px">
                    <i
                      class="fas fa-phone"
                      style="color: #11224e; width: 20px"
                    ></i>
                    <div>
                      <div style="font-size: 0.75em; color: #64748b">
                        Phone Number
                      </div>
                      <div
                        style="font-weight: 600; color: #1e293b"
                        id="displayPhone"
                      >
                        -
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Account Status -->
              <div style="padding-top: 20px; border-top: 1px solid #e5e7eb">
                <h3
                  style="
                    color: #11224e;
                    font-size: 1.1em;
                    margin-bottom: 15px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                  "
                >
                  <i class="fas fa-shield-alt"></i> Account Status
                </h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap">
                  <div
                    style="
                      display: flex;
                      align-items: center;
                      gap: 8px;
                      background: #d1fae5;
                      padding: 8px 15px;
                      border-radius: 20px;
                      color: #065f46;
                    "
                  >
                    <i class="fas fa-check-circle"></i>
                    <span>Email Verified</span>
                  </div>
                  <div
                    style="
                      display: flex;
                      align-items: center;
                      gap: 8px;
                      background: #d1fae5;
                      padding: 8px 15px;
                      border-radius: 20px;
                      color: #065f46;
                    "
                  >
                    <i class="fas fa-check-circle"></i>
                    <span>Account Active</span>
                  </div>
                </div>
              </div>

              <!-- Information Note -->
              <div
                style="
                  margin-top: 20px;
                  padding: 15px;
                  background: #f0f9ff;
                  border-radius: 8px;
                  display: flex;
                  align-items: flex-start;
                  gap: 12px;
                  color: #0369a1;
                "
              >
                <i class="fas fa-info-circle" style="margin-top: 2px"></i>
                <div style="font-size: 0.9em">
                  <strong>Note:</strong> Profile information is currently
                  read-only. To update your details, please contact our support
                  team at
                  <a href="mailto:support@arhomes.com" style="color: #0369a1"
                    >support@arhomes.com</a
                  >
                  or call us at <strong>(123) 456-7890</strong>.
                </div>
              </div>
            </div>

            <!-- Hidden form fields for backward compatibility -->
            <form class="profile-form" id="profileForm" style="display: none">
              <input type="text" id="profileGivenName" name="givenName" />
              <input type="text" id="profileMiddleName" name="middleName" />
              <input type="text" id="profileLastName" name="lastName" />
              <input type="text" id="profileUsername" name="username" />
              <input type="email" id="profileEmail" name="email" />
              <input type="tel" id="profilePhone" name="phone" />
            </form>
            <div class="section-scroll-indicator-wrap bottom-indicator">
              <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                <span class="scroll-indicator-text">Back to Top</span>
              </a>
            </div>
          </div>

          <!-- My Reservations Section (Active Reservations) -->
          <div class="content-section" id="my-bookings-section">
            <div class="section-header-inline">
              <h2>
                <i class="fas fa-clipboard-list" style="color: #11224e"></i> My
                Reservations
              </h2>
              <button
                class="btn-refresh"
                onclick="loadMyBookings()"
                style="
                  background: #11224e;
                  color: white;
                  border: none;
                  padding: 10px 20px;
                  border-radius: 8px;
                  cursor: pointer;
                  font-weight: 600;
                  font-size: 0.9em;
                  display: flex;
                  align-items: center;
                  gap: 8px;
                  transition: all 0.3s ease;
                  box-shadow: 0 2px 8px rgba(17, 34, 78, 0.3);
                "
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(17, 34, 78, 0.5)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(17, 34, 78, 0.3)'"
              >
                <i class="fas fa-sync-alt"></i> Refresh
              </button>
            </div>

            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#myBookingsGrid" class="section-scroll-indicator scroll-down-btn" data-scroll-target="#myBookingsGrid" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Reservation Records</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>

            <!-- Loading State -->
            <div
              id="bookingsLoadingState"
              style="text-align: center; padding: 40px"
            >
              <i
                class="fas fa-spinner fa-spin"
                style="font-size: 48px; color: #667eea"
              ></i>
              <p style="margin-top: 20px; color: #6c757d">
                Loading your reservations...
              </p>
            </div>

            <!-- Bookings Grid -->
            <div
              id="myBookingsGrid"
              class="bookings-grid"
              style="display: none"
            ></div>

            <!-- Empty State -->
            <div
              id="bookingsEmptyState"
              style="display: none; text-align: center; padding: 60px 20px"
            >
              <i
                class="fas fa-calendar-times"
                style="font-size: 64px; color: #11224e"
              ></i>
              <h3 style="margin-top: 20px; color: #11224e">
                No Reservations Found
              </h3>
              <p style="color: whitesmoke">
                You don't have any reservations yet.
              </p>
              <!-- Make a Reservation button removed for user accounts -->
            </div>
            <div class="section-scroll-indicator-wrap bottom-indicator">
              <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                <span class="scroll-indicator-text">Back to Top</span>
              </a>
            </div>
          </div>

          <!-- Reservation History Section -->
          <div class="content-section" id="bookings-history-section">
            <div class="section-header-inline" style="margin-bottom: 25px">
              <h2>
                <i class="fas fa-history" style="color: #11224e"></i>
                Reservation History
              </h2>
            </div>

            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#historyBookingsGrid" class="section-scroll-indicator scroll-down-btn" data-scroll-target=".history-content-container" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Past Stay History</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>

            <!-- History Stats Cards - Same style as Dashboard -->
            <div class="history-stats">
              <div class="history-stat-card">
                <div class="stat-icon completed">
                  <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="historyCompletedCount">0</div>
                  <div class="stat-label">Completed Stays</div>
                </div>
              </div>
              <div class="history-stat-card">
                <div class="stat-icon cancelled">
                  <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="historyCancelledCount">0</div>
                  <div class="stat-label">Cancelled</div>
                </div>
              </div>
              <div class="history-stat-card">
                <div class="stat-icon total">
                  <i class="fas fa-history"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="historyTotalCount">0</div>
                  <div class="stat-label">Total History</div>
                </div>
              </div>
            </div>

            <!-- Filters and Search -->
            <div class="history-controls">
              <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">
                  All History
                </button>
                <button class="filter-btn" data-filter="completed">
                  Completed
                </button>
                <button class="filter-btn" data-filter="cancelled">
                  Cancelled
                </button>
              </div>
              <div class="search-sort-wrapper">
                <div class="search-box">
                  <i class="fas fa-search"></i>
                  <input
                    type="text"
                    id="bookingSearch"
                    placeholder="Search history..."
                  />
                </div>
                <select id="sortBookings" class="sort-select">
                  <option value="date-desc">Date (Newest)</option>
                  <option value="date-asc">Date (Oldest)</option>
                  <option value="price-desc">Price (High to Low)</option>
                  <option value="price-asc">Price (Low to High)</option>
                </select>
              </div>
            </div>

            <!-- History Cards Grid -->
            <div class="bookings-history-grid">
              <!-- Booking cards will be loaded dynamically from database -->
              <div
                class="history-empty-state"
                style="animation: pulse 1.5s infinite"
              >
                <i class="fas fa-spinner fa-spin" style="color: #11224e"></i>
                <h3>Loading Your Reservations...</h3>
                <p>Please wait while we fetch your booking history</p>
              </div>
            </div>
            <div class="section-scroll-indicator-wrap bottom-indicator">
              <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                <span class="scroll-indicator-text">Back to Top</span>
              </a>
            </div>
          </div>

          <!-- Rebooking Requests Section -->
          <div class="content-section" id="rebooking-requests-section">
            <div class="section-header-inline" style="margin-bottom: 25px">
              <h2>
                <i class="fas fa-calendar-alt" style="color: #11224e"></i>
                Rebooking Requests
              </h2>
              <button
                class="btn-refresh"
                onclick="loadRebookingRequests()"
                style="
                  background: #11224e;
                  color: white;
                  border: none;
                  padding: 10px 20px;
                  border-radius: 8px;
                  cursor: pointer;
                  font-weight: 600;
                  font-size: 0.9em;
                  display: flex;
                  align-items: center;
                  gap: 8px;
                  transition: all 0.3s ease;
                  box-shadow: 0 2px 8px rgba(17, 34, 78, 0.3);
                "
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(17, 34, 78, 0.5)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(17, 34, 78, 0.3)'"
              >
                <i class="fas fa-sync-alt"></i> Refresh
              </button>
            </div>

            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#rebookingRequestsList" class="section-scroll-indicator scroll-down-btn" data-scroll-target="#rebookingRequestsList" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Rebooking Details</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>

            <!-- Rebooking Stats -->
            <div class="history-stats" style="margin-bottom: 30px;">
              <div class="history-stat-card">
                <div class="stat-icon pending">
                  <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="rebookingPendingCount">0</div>
                  <div class="stat-label">Pending</div>
                </div>
              </div>
              <div class="history-stat-card">
                <div class="stat-icon approved">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="rebookingApprovedCount">0</div>
                  <div class="stat-label">Approved</div>
                </div>
              </div>
              <div class="history-stat-card">
                <div class="stat-icon cancelled">
                  <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="rebookingRejectedCount">0</div>
                  <div class="stat-label">Rejected</div>
                </div>
              </div>
            </div>

            <!-- Filter Tabs -->
            <div style="display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap;">
              <button 
                class="filter-tab active" 
                onclick="filterUserRebookings('all')" 
                id="user-rebooking-filter-all"
                style="padding: 12px 24px; background: #11224e; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                All Requests
              </button>
              <button 
                class="filter-tab" 
                onclick="filterUserRebookings('pending')" 
                id="user-rebooking-filter-pending"
                style="padding: 12px 24px; background: white; color: #f59e0b; border: 2px solid #f59e0b; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                Pending
              </button>
              <button 
                class="filter-tab" 
                onclick="filterUserRebookings('approved')" 
                id="user-rebooking-filter-approved"
                style="padding: 12px 24px; background: white; color: #10b981; border: 2px solid #10b981; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                Approved
              </button>
              <button 
                class="filter-tab" 
                onclick="filterUserRebookings('rejected')" 
                id="user-rebooking-filter-rejected"
                style="padding: 12px 24px; background: white; color: #ef4444; border: 2px solid #ef4444; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                Rejected
              </button>
            </div>

            <!-- Rebooking Requests List -->
            <div id="rebookingRequestsList" style="display: flex; flex-direction: column; gap: 20px;">
              <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px; margin-bottom: 16px;"></i>
                <div style="font-size: 16px;">Loading rebooking requests...</div>
              </div>
            </div>
            <div class="section-scroll-indicator-wrap bottom-indicator">
              <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                <span class="scroll-indicator-text">Back to Top</span>
              </a>
            </div>
          </div>

          <!-- Notifications Section -->
          <div class="content-section" id="notifications-section">
            <div class="section-header-inline" style="margin-bottom: 25px">
              <h2
                style="
                  color: #11224e;
                  display: flex;
                  align-items: center;
                  gap: 10px;
                  margin: 0;
                "
              >
                <i class="fas fa-bell" style="color: #11224e"></i> Notifications
                <span
                  id="unreadCount"
                  style="
                    background: #ef4444;
                    color: white;
                    font-size: 0.7em;
                    padding: 3px 8px;
                    border-radius: 12px;
                    font-weight: 600;
                    display: none;
                  "
                  >0</span
                >
              </h2>
            </div>

            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#notificationsContainer" class="section-scroll-indicator scroll-down-btn" data-scroll-target="#notificationsList" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Notification Details</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>

            <!-- Notifications Container Card -->
            <div
              style="
                background: white;
                border: 2px solid #11224e;
                border-radius: 8px;
                padding: 20px;
                max-width: 900px;
                margin: 0 auto;
              "
            >
              <!-- Filter Controls -->
              <div
                style="
                  display: flex;
                  gap: 10px;
                  align-items: center;
                  margin-bottom: 20px;
                  padding-bottom: 15px;
                  border-bottom: 1px solid #e2e8f0;
                  flex-wrap: wrap;
                "
              >
                <button
                  class="btn-text"
                  onclick="filterNotifications('all')"
                  id="filterAll"
                  style="
                    color: #11224e;
                    font-weight: 600;
                    border-bottom: 2px solid #11224e;
                    padding: 8px 16px;
                    border-radius: 6px;
                  "
                >
                  <i class="fas fa-list"></i> All
                </button>
                <button
                  class="btn-text"
                  onclick="filterNotifications('unread')"
                  id="filterUnread"
                  style="color: #64748b; padding: 8px 16px; border-radius: 6px"
                >
                  <i class="fas fa-envelope"></i> Unread
                </button>
                <button
                  onclick="markAllAsRead()"
                  style="
                    color: white;
                    padding: 8px 16px;
                    background: #11224e;
                    border-radius: 8px;
                    border: none;
                    cursor: pointer;
                    font-weight: 500;
                    margin-left: auto;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    transition: all 0.3s ease;
                  "
                  onmouseover="this.style.background='#1e3a5f'"
                  onmouseout="this.style.background='#11224e'"
                >
                  <i class="fas fa-check-double"></i> Mark all as read
                </button>
                <button
                  onclick="markAllAsUnread()"
                  style="
                    color: white;
                    padding: 8px 16px;
                    background: #11224e;
                    border-radius: 8px;
                    border: none;
                    cursor: pointer;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    transition: all 0.3s ease;
                  "
                  onmouseover="this.style.background='#1e3a5f'"
                  onmouseout="this.style.background='#11224e'"
                >
                  <i class="fas fa-envelope"></i> Mark all as unread
                </button>
                <button
                  onclick="clearAllNotifications()"
                  style="
                    color: white;
                    padding: 8px 16px;
                    background: #11224e;
                    border-radius: 8px;
                    border: none;
                    cursor: pointer;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    transition: all 0.3s ease;
                  "
                  onmouseover="this.style.background='#1e3a5f'"
                  onmouseout="this.style.background='#11224e'"
                >
                  <i class="fas fa-trash"></i> Clear all
                </button>
              </div>

              <!-- Notifications List -->
              <div class="notifications-list" id="notificationsList">
                <!-- Notifications will be loaded dynamically from database -->
                <div
                  style="text-align: center; padding: 60px 20px; color: #64748b"
                >
                  <div
                    style="
                      width: 80px;
                      height: 80px;
                      background: linear-gradient(
                        135deg,
                        #f0f9ff 0%,
                        #e0f2fe 100%
                      );
                      border-radius: 50%;
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      margin: 0 auto 20px;
                    "
                  >
                    <i
                      class="fas fa-spinner fa-spin"
                      style="font-size: 2em; color: #11224e"
                    ></i>
                  </div>
                  <p style="font-size: 1.1em; font-weight: 500; color: #11224e">
                    Loading notifications...
                  </p>
                </div>
              </div>

              <!-- Empty State (hidden by default) -->
              <div
                id="emptyNotifications"
                style="display: none; text-align: center; padding: 60px 20px"
              >
                <div
                  style="
                    width: 100px;
                    height: 100px;
                    background: linear-gradient(
                      135deg,
                      #f0f9ff 0%,
                      #e0f2fe 100%
                    );
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                  "
                >
                  <i
                    class="fas fa-bell-slash"
                    style="font-size: 2.5em; color: #11224e"
                  ></i>
                </div>
                <h3
                  style="color: #11224e; margin: 0 0 10px 0; font-size: 1.3em"
                >
                  No Notifications
                </h3>
                <p style="color: #64748b; margin: 0; font-size: 0.95em">
                  You're all caught up! Check back later for updates.
                </p>
              </div>
            </div>
            <div class="section-scroll-indicator-wrap bottom-indicator">
              <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                <span class="scroll-indicator-text">Back to Top</span>
              </a>
            </div>
          </div>

          <!-- Reviews Section -->
          <div class="content-section" id="reviews-section">
            <div class="section-header-inline" style="margin-bottom: 25px">
              <h2>
                <i class="fas fa-star" style="color: #11224e"></i> My Reviews
              </h2>
            </div>

            <div class="section-scroll-indicator-wrap top-indicator">
              <a href="#reviewsStatsWrap" class="section-scroll-indicator scroll-down-btn" data-scroll-target=".reviews-stats" onclick="handleSectionScroll(this, 'down'); return false;">
                <span class="scroll-indicator-text">Scroll Down for Ratings &amp; Reviews</span>
                <div class="scroll-indicator-mouse"><div class="scroll-indicator-wheel"></div></div>
                <i class="fas fa-chevron-down scroll-indicator-chevron"></i>
              </a>
            </div>
            <div class="reviews-stats">
              <div class="review-stat">
                <div class="stat-icon">
                  <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="avgRatingValue">0.0</div>
                  <div class="stat-label">Average Rating Given</div>
                </div>
              </div>
              <div class="review-stat">
                <div class="stat-icon">
                  <i class="fas fa-comment-alt"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="totalReviewsValue">0</div>
                  <div class="stat-label">Total Reviews</div>
                </div>
              </div>
              <div class="review-stat">
                <div class="stat-icon">
                  <i class="fas fa-thumbs-up"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value" id="totalHelpfulValue">0</div>
                  <div class="stat-label">Helpful Votes</div>
                </div>
              </div>
            </div>

            <div class="reviews-list" id="reviewsList">
              <!-- Reviews will be loaded dynamically -->
              <div
                class="loading-reviews"
                id="reviewsLoading"
                style="text-align: center; padding: 40px"
              >
                <i
                  class="fas fa-spinner fa-spin"
                  style="font-size: 2rem; color: #667eea"
                ></i>
                <p style="margin-top: 15px; color: #666">
                  Loading your reviews...
                </p>
              </div>
            </div>
            <div class="section-scroll-indicator-wrap bottom-indicator">
              <a href="#" class="section-scroll-indicator scroll-up-btn" onclick="handleSectionScroll(this, 'up'); return false;">
                <i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>
                <span class="scroll-indicator-text">Back to Top</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Proof Upload Modal -->
    <div class="modal-overlay" id="paymentUploadModal" style="display: none">
      <div class="modal-container">
        <div class="modal-header">
          <h3><i class="fas fa-upload"></i> Upload Payment Proof</h3>
          <button class="modal-close" onclick="closePaymentUploadModal()">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form id="paymentUploadForm" enctype="multipart/form-data">
            <input
              type="hidden"
              id="paymentReservationId"
              name="reservation_id"
            />
            <input type="hidden" id="paymentType" name="payment_type" />

            <div class="form-group">
              <label>Payment For:</label>
              <p
                id="paymentForLabel"
                style="color: #667eea; font-weight: 600"
              ></p>
            </div>

            <div class="form-group">
              <label for="paymentMethod">Payment Method *</label>
              <select id="paymentMethod" name="payment_method" required>
                <option value="">Select payment method</option>
                <option value="gcash">GCash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="online_banking">Online Banking</option>
                <option value="cash">Cash</option>
              </select>
            </div>

            <div class="form-group">
              <label for="referenceNumber">Reference Number *</label>
              <input
                type="text"
                id="referenceNumber"
                name="reference_number"
                placeholder="e.g., REF123456789"
                required
              />
            </div>

            <div class="form-group">
              <label for="paymentProofFile"
                >Payment Screenshot/Receipt * (Max 5MB)</label
              >
              <input
                type="file"
                id="paymentProofFile"
                name="payment_proof"
                accept="image/*,application/pdf"
                required
              />
              <small style="color: #6c757d; display: block; margin-top: 8px">
                <i class="fas fa-info-circle"></i> Accepted: JPG, PNG, GIF, PDF
                (Max 5MB)
              </small>
            </div>

            <div
              class="form-group"
              style="
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #667eea;
              "
            >
              <p style="margin: 0; color: #495057; font-size: 14px">
                <i class="fas fa-exclamation-circle" style="color: #667eea"></i>
                <strong>Note:</strong> After uploading, your payment will be
                verified by our admin. You'll receive a confirmation once
                approved.
              </p>
            </div>

            <div class="modal-actions">
              <button
                type="button"
                class="btn-secondary"
                onclick="closePaymentUploadModal()"
              >
                <i class="fas fa-times"></i> Cancel
              </button>
              <button type="submit" class="btn-primary">
                <i class="fas fa-upload"></i> Upload Payment Proof
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Pay at Resort Info Modal -->
    <div
      class="modal-overlay"
      id="payAtResortModal"
      style="display: none; z-index: 10001"
    >
      <div class="modal-container" style="max-width: 500px">
        <div class="modal-header" style="background: #11224e">
          <h3 style="color: white">
            <i class="fas fa-building"></i> Pay at Resort
          </h3>
          <button
            class="modal-close"
            onclick="closePayAtResortModal()"
            style="color: white"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div style="text-align: center; margin-bottom: 20px">
            <div
              style="
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #11224e 0%, #1e3a5f 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
              "
            >
              <i
                class="fas fa-money-bill-wave"
                style="font-size: 2em; color: white"
              ></i>
            </div>
            <h4 style="color: #11224e; margin: 0 0 5px 0">
              Pay Your Balance at Check-in
            </h4>
            <p style="color: #666; margin: 0; font-size: 0.9em">
              Flexible payment options available
            </p>
          </div>

          <div
            style="
              background: #f8fafc;
              padding: 20px;
              border-radius: 10px;
              margin-bottom: 20px;
            "
          >
            <h5 style="color: #11224e; margin: 0 0 15px 0; font-size: 1em">
              <i class="fas fa-credit-card" style="color: #11224e"></i> Accepted
              Payment Methods:
            </h5>
            <div style="display: grid; gap: 10px">
              <div
                style="
                  display: flex;
                  align-items: center;
                  gap: 10px;
                  padding: 10px;
                  background: white;
                  border-radius: 8px;
                  border: 1px solid #e2e8f0;
                "
              >
                <i
                  class="fas fa-money-bill-wave"
                  style="color: #10b981; font-size: 1.2em"
                ></i>
                <span style="color: #000; font-weight: 500">Cash</span>
              </div>
              <div
                style="
                  display: flex;
                  align-items: center;
                  gap: 10px;
                  padding: 10px;
                  background: white;
                  border-radius: 8px;
                  border: 1px solid #e2e8f0;
                "
              >
                <i
                  class="fab fa-cc-mastercard"
                  style="color: #3b82f6; font-size: 1.2em"
                ></i>
                <span style="color: #000; font-weight: 500"
                  >GCash / Maya / PayMaya</span
                >
              </div>
              <div
                style="
                  display: flex;
                  align-items: center;
                  gap: 10px;
                  padding: 10px;
                  background: white;
                  border-radius: 8px;
                  border: 1px solid #e2e8f0;
                "
              >
                <i
                  class="fas fa-university"
                  style="color: #8b5cf6; font-size: 1.2em"
                ></i>
                <span style="color: #000; font-weight: 500">Bank Transfer</span>
              </div>
              <div
                style="
                  display: flex;
                  align-items: center;
                  gap: 10px;
                  padding: 10px;
                  background: white;
                  border-radius: 8px;
                  border: 1px solid #e2e8f0;
                "
              >
                <i
                  class="fas fa-credit-card"
                  style="color: #f59e0b; font-size: 1.2em"
                ></i>
                <span style="color: #000; font-weight: 500"
                  >Credit / Debit Card</span
                >
              </div>
            </div>
          </div>

          <div
            style="
              background: #fef9e7;
              padding: 15px;
              border-radius: 8px;
              border-left: 4px solid #f59e0b;
              margin-bottom: 20px;
            "
          >
            <p
              style="margin: 0; color: #000; font-size: 0.9em; line-height: 1.6"
            >
              <i class="fas fa-info-circle" style="color: #f59e0b"></i>
              <strong>Important:</strong> You can pay your remaining balance
              when you arrive at the resort or during your stay. Please ensure
              to settle your balance before check-out.
            </p>
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="btn-primary"
              onclick="closePayAtResortModal()"
              style="background: #11224e; width: 100%"
            >
              <i class="fas fa-check"></i> Got It
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pay Remaining Balance Modal -->
    <div
      class="modal-overlay"
      id="payRemainingBalanceModal"
      style="display: none; z-index: 10001"
    >
      <div class="modal-container" style="max-width: 550px">
        <div class="modal-header" style="background: #11224e">
          <h3 style="color: white">
            <i class="fas fa-wallet"></i> Pay Remaining Balance
          </h3>
          <button
            class="modal-close"
            onclick="closePayRemainingBalanceModal()"
            style="color: white"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <!-- Amount Display -->
          <div
            style="
              text-align: center;
              margin-bottom: 25px;
              padding: 20px;
              background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
              border-radius: 12px;
              border: 2px solid #11224e;
            "
          >
            <div style="color: #64748b; font-size: 0.9em; margin-bottom: 5px">
              Amount to Pay
            </div>
            <div
              id="remainingBalanceAmount"
              style="color: #11224e; font-size: 2em; font-weight: 700"
            >
              ₱0
            </div>
          </div>

          <!-- Payment Method Selection -->
          <h5 style="color: #11224e; margin: 0 0 15px 0; font-size: 1.1em">
            <i class="fas fa-credit-card" style="color: #11224e"></i> Choose
            Payment Method:
          </h5>

          <div style="display: grid; gap: 12px; margin-bottom: 20px">
            <!-- PayMongo Online Payment -->
            <button
              id="payOnlineBtn"
              onclick="proceedWithOnlinePayment()"
              style="
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 18px;
                background: white;
                border: 2px solid #11224e;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.3s;
                text-align: left;
                width: 100%;
              "
              onmouseover="this.style.background='#f8fafc'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(17,34,78,0.15)';"
              onmouseout="this.style.background='white'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
            >
              <div
                style="
                  width: 50px;
                  height: 50px;
                  background: linear-gradient(135deg, #11224e 0%, #1e3a5f 100%);
                  border-radius: 50%;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  flex-shrink: 0;
                "
              >
                <i
                  class="fas fa-credit-card"
                  style="color: white; font-size: 1.3em"
                ></i>
              </div>
              <div style="flex: 1">
                <div
                  style="
                    color: #11224e;
                    font-weight: 600;
                    font-size: 1.05em;
                    margin-bottom: 3px;
                  "
                >
                  Pay Online Now
                </div>
                <div style="color: #64748b; font-size: 0.85em">
                  Via GCash, Maya, Card, or Bank Transfer
                </div>
              </div>
              <i
                class="fas fa-chevron-right"
                style="color: #11224e; font-size: 1.2em"
              ></i>
            </button>
          </div>

          <!-- Info Note -->
          <div
            style="
              background: #fef9e7;
              padding: 12px;
              border-radius: 8px;
              border-left: 4px solid #f59e0b;
              margin-bottom: 20px;
            "
          >
            <p
              style="
                margin: 0;
                color: #000;
                font-size: 0.85em;
                line-height: 1.5;
              "
            >
              <i class="fas fa-info-circle" style="color: #f59e0b"></i>
              <strong>Note:</strong> Click "Pay Online Now" to proceed with
              payment via GCash, Maya, Card, or Bank Transfer for instant
              confirmation.
            </p>
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="btn-secondary"
              onclick="closePayRemainingBalanceModal()"
              style="width: 100%"
            >
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Rebooking Request Modal -->
    <div
      class="modal-overlay"
      id="rebookingModal"
      style="display: none; z-index: 10001"
    >
      <div class="modal-container">
        <div class="modal-header">
          <h3><i class="fas fa-calendar-alt"></i> Request Rebooking</h3>
          <button class="modal-close" onclick="closeRebookingModal()">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form id="rebookingForm">
            <input
              type="hidden"
              id="rebookReservationId"
              name="reservation_id"
            />

            <div class="form-group">
              <label>Current Check-in Date:</label>
              <p
                id="currentCheckInDate"
                style="color: #667eea; font-weight: 600"
              ></p>
            </div>

            <div class="form-group">
              <label for="newCheckInDate">New Check-in Date *</label>
              <input type="date" id="newCheckInDate" name="new_date" required />
              <small style="color: #6c757d; display: block; margin-top: 8px">
                <i class="fas fa-info-circle"></i> Rebooking must be requested
                at least 7 days before your current check-in date.
              </small>
            </div>

            <div class="form-group">
              <label for="rebookReason">Reason for Rebooking *</label>
              <textarea
                id="rebookReason"
                name="reason"
                rows="4"
                placeholder="Please provide a reason for changing your reservation date..."
                required
              ></textarea>
            </div>

            <div
              class="form-group"
              style="
                background: #fff3cd;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #ffc107;
              "
            >
              <p style="margin: 0; color: #856404; font-size: 14px">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Rebooking Policy:</strong>
              </p>
              <ul style="margin: 10px 0 0 20px; color: #856404">
                <li>Must be requested 7+ days before check-in</li>
                <li>Requires admin approval</li>
                <li>No additional fees if approved</li>
                <li>Original payment will be transferred to new date</li>
              </ul>
            </div>

            <div class="modal-actions">
              <button
                type="button"
                class="btn-secondary"
                onclick="closeRebookingModal()"
              >
                <i class="fas fa-times"></i> Cancel
              </button>
              <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Request
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Package Inclusions Modal -->
    <div class="modal-overlay" id="inclusionsModal" style="display: none">
      <div class="modal-container" style="max-width: 900px">
        <div class="modal-header">
          <h3 id="inclusionsModalTitle">
            <i class="fas fa-list-check"></i> Package Details
          </h3>
          <button class="modal-close" onclick="closeInclusionsModal()">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div
          class="modal-body"
          id="inclusionsModalContent"
          style="max-height: 70vh; overflow-y: auto"
        >
          <!-- Content will be dynamically inserted here -->
        </div>
      </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div
      class="modal-overlay"
      id="imageLightbox"
      style="display: none; z-index: 10000; background: rgba(0, 0, 0, 0.9)"
      onclick="closeImageLightbox()"
    >
      <div
        style="
          position: relative;
          max-width: 95%;
          max-height: 95vh;
          display: flex;
          align-items: center;
          justify-content: center;
        "
        onclick="event.stopPropagation()"
      >
        <button
          onclick="closeImageLightbox()"
          style="
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10001;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
          "
          onmouseover="this.style.background='#fff'; this.style.transform='scale(1.15)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.4)';"
          onmouseout="this.style.background='rgba(255,255,255,0.95)'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.3)';"
        >
          <i class="fas fa-times"></i>
        </button>
        <img
          id="lightboxImage"
          src=""
          alt=""
          style="
            max-width: 100%;
            max-height: 90vh;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            object-fit: contain;
          "
        />
      </div>
    </div>

    <!-- Cancellation Confirmation Modal -->
    <div class="modal-overlay" id="cancelModal" style="display: none">
      <div class="modal-container">
        <div class="modal-header">
          <h3>
            <i class="fas fa-exclamation-triangle"></i> Cancel Reservation
          </h3>
          <button class="modal-close" onclick="closeCancelModal()">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form id="cancelForm">
            <input
              type="hidden"
              id="cancelReservationId"
              name="reservation_id"
            />

            <div
              style="
                background: #fee;
                padding: 20px;
                border-radius: 8px;
                border-left: 4px solid #dc3545;
                margin-bottom: 20px;
              "
            >
              <p
                style="
                  margin: 0;
                  color: #721c24;
                  font-size: 16px;
                  font-weight: 600;
                "
              >
                <i class="fas fa-exclamation-circle"></i> Cancellation Not
                Available
              </p>
              <ul style="margin: 15px 0 0 20px; color: #721c24">
                <li>
                  Downpayment is
                  <strong>non-refundable/non-transferable</strong>
                </li>
                <li>
                  Only <strong>rebooking</strong> is allowed (within 3 months)
                </li>
                <li>Please use the "Request Rebooking" button instead</li>
              </ul>
            </div>

            <!-- Dynamic refund info will be inserted here -->
            <div id="cancelRefundInfo"></div>

            <div class="form-group">
              <label for="cancelReason">Reason for Cancellation *</label>
              <textarea
                id="cancelReason"
                name="reason"
                rows="4"
                placeholder="Please tell us why you're cancelling..."
                required
              ></textarea>
            </div>

            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" id="acknowledgeNoRefund" required />
                <span>I understand the cancellation and refund policy</span>
              </label>
            </div>

            <div class="modal-actions">
              <button
                type="button"
                class="btn-secondary"
                onclick="closeCancelModal()"
              >
                <i class="fas fa-arrow-left"></i> Go Back
              </button>
              <button type="submit" class="btn-danger">
                <i class="fas fa-times-circle"></i> Confirm Cancellation
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Logout Popup -->
    <div class="logout-popup-overlay" id="logoutPopupOverlay">
      <div class="logout-popup">
        <div class="logout-popup-header">
          <h3>Confirm Logout</h3>
          <button class="logout-popup-close" onclick="hideLogoutPopup()">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="logout-popup-body">
          <div class="logout-popup-icon">
            <i class="fas fa-sign-out-alt"></i>
          </div>
          <p>Are you sure you want to logout from your account?</p>
          <p class="logout-popup-subtext">
            You will be redirected to the login page.
          </p>
        </div>
        <div class="logout-popup-footer">
          <button class="logout-popup-btn cancel" onclick="hideLogoutPopup()">
            <i class="fas fa-times"></i>
            Cancel
          </button>
          <button class="logout-popup-btn confirm" onclick="confirmLogout()">
            <i class="fas fa-sign-out-alt"></i>
            Logout
          </button>
        </div>
      </div>
    </div>

    <!-- JavaScript - Session Check -->
    <script>
      console.log("[SESSION] Session check executing...");

      // User data object - Make it global
      window.userData = {
        user_id: null,
        username: "Guest",
        email: "",
        full_name: "Guest User",
        given_name: "",
        middle_name: "",
        last_name: "",
        phone_number: "",
        memberSince: new Date().getFullYear(),
        totalBookings: 0,
        reviewsGiven: 0,
      };

      // Check authentication and load user data
      async function checkAuthAndLoadUser() {
        console.log("[AUTH] Checking authentication...");

        try {
          const response = await fetch("user/get_session.php", {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store",
          });

          console.log("[AUTH] Response status:", response.status);
          const data = await response.json();
          console.log("[AUTH] Session data:", data);

          if (data.success && data.authenticated && data.data) {
            console.log("[AUTH] User authenticated:", data.data);

            // Update userData object
            window.userData.user_id = data.data.user_id;
            window.userData.username = data.data.username || "Guest";
            window.userData.email = data.data.email || "";
            window.userData.full_name = data.data.full_name || "Guest User";
            window.userData.given_name = data.data.given_name || "";
            window.userData.middle_name = data.data.middle_name || "";
            window.userData.last_name = data.data.last_name || "";
            window.userData.phone_number = data.data.phone_number || "";
            window.userData.memberSince =
              data.data.memberSince || new Date().getFullYear();
            window.userData.totalBookings = data.data.totalBookings || 0;
            window.userData.reviewsGiven = data.data.reviewsGiven || 0;
            window.userData.pending_email = data.data.pending_email || null;

            // Update UI immediately
            updateUserInterface();
          } else {
            console.warn("[AUTH] Not authenticated, redirecting to logout...");
            window.location.href = "user/logout.php";
          }
        } catch (error) {
          console.error("[AUTH] Auth check failed:", error);
        }
      }

      // Update user interface with userData
      function updateUserInterface() {
        console.log("[UI] Updating UI with user data...");

        // Update header name and fade it in
        const userNameElement = document.getElementById("userName");
        if (userNameElement) {
          userNameElement.textContent = window.userData.full_name;
          userNameElement.style.opacity = "1";
          userNameElement.style.transition = "opacity 0.3s ease-in";
          console.log("[UI] Header updated:", window.userData.full_name);
        }

        // Update profile fields (hidden form)
        const profileFields = {
          profileGivenName: window.userData.given_name,
          profileMiddleName: window.userData.middle_name,
          profileLastName: window.userData.last_name,
          profileUsername: window.userData.username,
          profileEmail: window.userData.email,
          profilePhone: window.userData.phone_number,
        };

        for (const [fieldId, value] of Object.entries(profileFields)) {
          const element = document.getElementById(fieldId);
          if (element) {
            element.value = value || "";
            console.log(`[UI] ${fieldId} updated:`, value);
          }
        }

        // Update new profile display elements
        const profileDisplayFields = {
          displayGivenName: window.userData.given_name,
          displayMiddleName: window.userData.middle_name,
          displayLastName: window.userData.last_name,
          displayUsername: window.userData.username,
          displayEmail: window.userData.email,
          displayPhone: window.userData.phone_number,
        };

        for (const [fieldId, value] of Object.entries(profileDisplayFields)) {
          const element = document.getElementById(fieldId);
          if (element) {
            element.textContent = value || "-";
            console.log(`[UI] ${fieldId} display updated:`, value);
          }
        }

        // Show pending email notification if there's one
        if (window.userData.pending_email) {
          const displayEmailElement = document.getElementById("displayEmail");
          if (displayEmailElement && displayEmailElement.parentNode) {
            // Check if pending email notice already exists
            let pendingNotice = document.getElementById("pendingEmailNotice");
            if (!pendingNotice) {
              pendingNotice = document.createElement("div");
              pendingNotice.id = "pendingEmailNotice";
              pendingNotice.style.cssText =
                "margin-top: 8px; padding: 8px 12px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px; font-size: 0.85em; color: #92400e;";
              pendingNotice.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Pending Email Change:</strong><br>
                ${window.userData.pending_email}<br>
                <small>Please check your inbox to verify the new email address.</small>
              `;
              displayEmailElement.parentNode.appendChild(pendingNotice);
            }
          }
        }

        // Update profile stats (using actual data from server)
        const totalBookingsElement = document.getElementById(
          "profileTotalBookings"
        );
        const reviewsGivenElement = document.getElementById(
          "profileReviewsGiven"
        );
        const memberSinceElement =
          document.getElementById("profileMemberSince");

        if (totalBookingsElement) {
          totalBookingsElement.textContent = window.userData.totalBookings || 0;
        }
        if (reviewsGivenElement) {
          reviewsGivenElement.textContent = window.userData.reviewsGiven || 0;
        }
        if (memberSinceElement) {
          memberSinceElement.textContent =
            window.userData.memberSince || new Date().getFullYear();
        }

        // Refresh userData in dashboard-script.js if the function exists
        if (typeof window.refreshUserData === "function") {
          window.refreshUserData();
          console.log("[UI] Notified dashboard-script.js to refresh user data");
        }
      }

      // Load Dashboard Stats
      async function loadDashboardStats() {
        console.log("[STATS] Loading dashboard stats...");
        try {
          const response = await fetch("user/get_my_reservations.php", {
            method: "GET",
            credentials: "include",
          });

          console.log("[STATS] API Response status:", response.status);

          if (!response.ok) {
            throw new Error(
              "Failed to load reservations - Status: " + response.status
            );
          }

          const data = await response.json();
          console.log("[STATS] API Response data:", data);

          if (data.success && Array.isArray(data.reservations)) {
            const reservations = data.reservations;
            console.log("[STATS] Found", reservations.length, "reservations");

            // Log each reservation status for debugging
            if (reservations.length > 0) {
              console.log(
                "[STATS] Reservation statuses:",
                reservations.map((r) => r.status)
              );
            }

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

            // Calculate stats - handles ALL status values for old AND new accounts
            // Define status categories to handle both legacy and current statuses
            const pendingStatuses = [
              "pending",
              "pending_payment",
              "pending_confirmation",
              "expired",
            ];
            const approvedStatuses = [
              "approved",
              "confirmed",
              "checked_in",
              "rebooked",
            ];
            // Note: cancelled, canceled, no_show, forfeited are not counted in any positive category

            const total = reservations.length;
            const pending = reservations.filter((r) =>
              pendingStatuses.includes(r.status)
            ).length;

            // Count completed stays using the same logic as reservation history
            const completed = reservations.filter((r) =>
              isEffectivelyCompleted(r)
            ).length;

            // Approved = confirmed but NOT yet completed (exclude effectively completed)
            const approved = reservations.filter(
              (r) =>
                approvedStatuses.includes(r.status) &&
                !isEffectivelyCompleted(r)
            ).length;

            console.log("[STATS] Calculated stats:", {
              total,
              pending,
              approved,
              completed,
            });

            // Update stats display with null checks
            const totalEl = document.getElementById("totalReservations");
            const pendingEl = document.getElementById("pendingReservations");
            const approvedEl = document.getElementById("approvedReservations");
            const completedEl = document.getElementById("completedStays");

            if (totalEl) totalEl.textContent = total;
            if (pendingEl) pendingEl.textContent = pending;
            if (approvedEl) approvedEl.textContent = approved;
            if (completedEl) completedEl.textContent = completed;

            // Also update userData for dashboard-script.js compatibility
            if (window.userData) {
              window.userData.totalReservations = total;
              window.userData.pendingReservations = pending;
              window.userData.approvedReservations = approved;
              window.userData.completedStays = completed;

              // Also update profile Total Bookings to match Dashboard Total Reservations
              window.userData.totalBookings = total;
              const profileTotalBookingsEl = document.getElementById(
                "profileTotalBookings"
              );
              if (profileTotalBookingsEl)
                profileTotalBookingsEl.textContent = total;
            }

            // Load recent activity
            loadRecentActivity(reservations);

            console.log("[STATS] Dashboard stats loaded:", {
              total,
              pending,
              approved,
              completed,
            });
          } else {
            // API returned but no data or error
            console.warn(
              "[STATS] No reservations data returned:",
              data.message || "Unknown error"
            );
            // Set stats to 0 if no data
            const totalEl = document.getElementById("totalReservations");
            const pendingEl = document.getElementById("pendingReservations");
            const approvedEl = document.getElementById("approvedReservations");
            const completedEl = document.getElementById("completedStays");

            if (totalEl) totalEl.textContent = "0";
            if (pendingEl) pendingEl.textContent = "0";
            if (approvedEl) approvedEl.textContent = "0";
            if (completedEl) completedEl.textContent = "0";
          }
        } catch (error) {
          console.error("[STATS] Error loading dashboard stats:", error);
          // Set stats to show error state
          const totalEl = document.getElementById("totalReservations");
          const pendingEl = document.getElementById("pendingReservations");
          const approvedEl = document.getElementById("approvedReservations");
          const completedEl = document.getElementById("completedStays");

          if (totalEl) totalEl.textContent = "-";
          if (pendingEl) pendingEl.textContent = "-";
          if (approvedEl) approvedEl.textContent = "-";
          if (completedEl) completedEl.textContent = "-";
        }
      }

      // Load Recent Activity
      function loadRecentActivity(reservations) {
        const activityList = document.getElementById("activityList");

        // Null check for activityList element
        if (!activityList) {
          console.warn("[ACTIVITY] Activity list element not found");
          return;
        }

        if (!reservations || reservations.length === 0) {
          activityList.innerHTML = `
            <div class="activity-empty" style="text-align: center; padding: 2rem; color: #999;">
              <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
              <p>No recent activity to display</p>
            </div>
          `;
          return;
        }

        // Sort by created date (most recent first) and take top 5
        const recentReservations = reservations
          .sort(
            (a, b) =>
              new Date(b.created_at || b.booking_date) -
              new Date(a.created_at || a.booking_date)
          )
          .slice(0, 5);

        const activityHTML = recentReservations
          .map((reservation) => {
            // Map status to icons - handles ALL database status values (legacy + current)
            const statusIcons = {
              // Legacy statuses
              pending: "fa-clock",
              approved: "fa-check-circle",
              canceled: "fa-times-circle",
              // Current statuses
              pending_payment: "fa-credit-card",
              pending_confirmation: "fa-hourglass-half",
              confirmed: "fa-calendar-check",
              checked_in: "fa-door-open",
              checked_out: "fa-door-closed",
              completed: "fa-star",
              cancelled: "fa-times-circle",
              no_show: "fa-user-slash",
              forfeited: "fa-ban",
              rebooked: "fa-calendar-alt",
              expired: "fa-clock",
            };

            // Map status to labels - handles ALL database status values (legacy + current)
            const statusLabels = {
              // Legacy statuses
              pending: "Reservation Pending",
              approved: "Reservation Approved",
              canceled: "Reservation Cancelled",
              // Current statuses
              pending_payment: "Awaiting Payment",
              pending_confirmation: "Pending Verification",
              confirmed: "Reservation Confirmed",
              checked_in: "Checked In",
              checked_out: "Checked Out",
              completed: "Stay Completed",
              cancelled: "Reservation Cancelled",
              no_show: "No Show",
              forfeited: "Booking Forfeited",
              rebooked: "Rebooked",
              expired: "Reservation Expired",
            };

            const icon = statusIcons[reservation.status] || "fa-calendar";
            const label =
              statusLabels[reservation.status] ||
              reservation.status_label ||
              "Reservation Update";
            const checkIn =
              reservation.check_in_date || reservation.booking_date;
            const checkOut = reservation.check_out_date || "";
            const dateRange = checkOut ? `${checkIn} to ${checkOut}` : checkIn;
            const timeAgo = getTimeAgo(
              reservation.created_at || reservation.booking_date
            );

            return `
            <div class="activity-item">
              <div class="activity-icon">
                <i class="fas ${icon}"></i>
              </div>
              <div class="activity-content">
                <h4>${label}</h4>
                <p>Booking for ${dateRange}</p>
                <span class="activity-date">${timeAgo}</span>
              </div>
            </div>
          `;
          })
          .join("");

        activityList.innerHTML = activityHTML;
      }

      // Helper function to calculate time ago
      function getTimeAgo(dateString) {
        if (!dateString) return "Recently";

        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        const intervals = {
          year: 31536000,
          month: 2592000,
          week: 604800,
          day: 86400,
          hour: 3600,
          minute: 60,
        };

        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
          const interval = Math.floor(seconds / secondsInUnit);
          if (interval >= 1) {
            return `${interval} ${unit}${interval > 1 ? "s" : ""} ago`;
          }
        }

        return "Just now";
      }

      // Initialize session check when DOM is ready
      document.addEventListener("DOMContentLoaded", async () => {
        console.log("[DOM] DOM loaded - checking session...");
        await checkAuthAndLoadUser();
        await loadDashboardStats();
      });
    </script>

    <!-- Navigation Script -->
    <script>
      // Simple navigation system for content sections
      document.addEventListener("DOMContentLoaded", function () {
        console.log("[NAV] Setting up navigation...");

        // Get all navigation buttons
        const navButtons = document.querySelectorAll(".nav-btn");
        const contentSections = document.querySelectorAll(".content-section");

        console.log("Found nav buttons:", navButtons.length);
        console.log("Found content sections:", contentSections.length);

        // Add click handlers to navigation buttons
        navButtons.forEach((button) => {
          button.addEventListener("click", function (e) {
            e.preventDefault();
            const sectionName = this.getAttribute("data-section");
            console.log("Button clicked:", sectionName);

            // Save to localStorage for persistence
            localStorage.setItem("currentDashboardSection", sectionName);

            // Remove active class from all buttons
            navButtons.forEach((btn) => btn.classList.remove("active"));

            // Add active class to clicked button
            this.classList.add("active");

            // Hide all content sections
            contentSections.forEach((section) => {
              section.classList.remove("active");
              section.style.display = "none";
            });

            // Show the selected content section
            const targetSection = document.getElementById(
              sectionName + "-section"
            );
            if (targetSection) {
              targetSection.classList.add("active");
              targetSection.style.display = "block";
              console.log("Showing section:", sectionName);

              // Scroll to show content while keeping navbar visible
              const navSection = document.querySelector(".navigation-section");
              if (navSection) {
                const headerHeight = 80; // Account for fixed header
                const scrollTarget = navSection.offsetTop - headerHeight;
                window.scrollTo({ top: scrollTarget, behavior: "smooth" });
              }

              // Load section-specific data
              if (
                sectionName === "bookings-history" &&
                typeof loadMyReservations === "function"
              ) {
                loadMyReservations();
              }
              if (
                sectionName === "notifications" &&
                typeof loadUserNotifications === "function"
              ) {
                loadUserNotifications();
              }
              if (
                sectionName === "rebooking-requests" &&
                typeof loadRebookingRequests === "function"
              ) {
                loadRebookingRequests();
              }
            } else {
              console.error("Section not found:", sectionName + "-section");
            }
          });
        });

        // Restore saved section or show dashboard by default
        const savedSection =
          localStorage.getItem("currentDashboardSection") || "dashboard";
        const targetSection = document.getElementById(
          savedSection + "-section"
        );

        // Hide all sections first
        document.querySelectorAll(".content-section").forEach((s) => {
          s.classList.remove("active");
          s.style.display = "none";
        });

        // Show saved section
        if (targetSection) {
          targetSection.classList.add("active");
          targetSection.style.display = "block";

          // Update navigation active state for nav-btn buttons
          document.querySelectorAll(".nav-btn").forEach((btn) => {
            btn.classList.remove("active");
            if (btn.getAttribute("data-section") === savedSection) {
              btn.classList.add("active");
            }
          });

          // Load content for the restored section
          if (
            savedSection === "my-reservations" &&
            typeof loadMyReservations === "function"
          ) {
            loadMyReservations();
          } else if (
            savedSection === "notifications" &&
            typeof loadUserNotifications === "function"
          ) {
            loadUserNotifications();
          } else if (
            savedSection === "bookings-history" &&
            typeof loadEnhancedBookingHistory === "function"
          ) {
            loadEnhancedBookingHistory();
          } else if (
            savedSection === "my-bookings" &&
            typeof loadMyBookings === "function"
          ) {
            loadMyBookings();
          }
        } else {
          // Fallback to dashboard
          const dashboardSection = document.getElementById("dashboard-section");
          if (dashboardSection) {
            dashboardSection.classList.add("active");
            dashboardSection.style.display = "block";
          }
          // Set dashboard nav button active
          const dashboardBtn = document.querySelector(
            '.nav-btn[data-section="dashboard"]'
          );
          if (dashboardBtn) dashboardBtn.classList.add("active");
        }
      });

      // Room package selection functions
      function selectRoomPackage(packageType, nightPrice, dayPrice) {
        console.log("Selected package:", packageType);
        // Add your package selection logic here
        alert(
          "Package selected: " +
            packageType +
            "\nNight: " +
            nightPrice +
            " | Day: " +
            dayPrice
        );
      }

      function selectCustomRooms() {
        console.log("Custom rooms selection");
        const checkboxes = document.querySelectorAll(
          'input[name="customRoom"]:checked'
        );
        if (checkboxes.length === 0) {
          alert("Please select at least one room");
          return;
        }
        alert("Custom package selected with " + checkboxes.length + " room(s)");
      }

      function goBackToRooms() {
        // Switch to reservations tab
        const reservationsBtn = document.querySelector(
          '[data-section="my-reservations"]'
        );
        if (reservationsBtn) {
          reservationsBtn.click();
        }
      }

      function proceedToPayment() {
        alert("Proceeding to payment...");
        // Add your payment logic here
      }
      window.proceedToPayment = proceedToPayment;

      function validatePromoCode() {
        const promoCode = document.getElementById("promoCodeInput").value;
        if (promoCode) {
          alert("Validating promo code: " + promoCode);
          // Add your validation logic here
        } else {
          alert("Please enter a promo code");
        }
      }
      window.validatePromoCode = validatePromoCode;

      // Toggle sidebar for mobile
      function toggleSidebar() {
        console.log("Toggle sidebar");
        // Add mobile sidebar toggle logic if needed
      }

      // Gallery button test - defined here to ensure it loads
      window.testButtons = function () {
        console.log("\n[TEST] TESTING GALLERY BUTTONS");
        const prev = document.getElementById("prevBtn");
        const next = document.getElementById("nextBtn");
        console.log("Prev button:", prev);
        console.log("Next button:", next);

        // Check parent visibility
        const gallery = document.querySelector(".resort-gallery-section");
        const galleryContainer = document.querySelector(".gallery-container");
        if (gallery) {
          const galleryStyle = window.getComputedStyle(gallery);
          console.log("Gallery section display:", galleryStyle.display);
          console.log("Gallery section visibility:", galleryStyle.visibility);
          console.log("Gallery section opacity:", galleryStyle.opacity);
        }

        if (next) {
          const rect = next.getBoundingClientRect();
          const style = window.getComputedStyle(next);
          console.log("Next button rect:", rect);
          console.log("Next button z-index:", style.zIndex);
          console.log("Next button pointer-events:", style.pointerEvents);
          console.log("Next button position:", style.position);

          // Check parent's z-index
          const parent = next.parentElement;
          if (parent) {
            const parentStyle = window.getComputedStyle(parent);
            console.log(
              "Parent (.gallery-controls) z-index:",
              parentStyle.zIndex
            );
            console.log("Parent pointer-events:", parentStyle.pointerEvents);
          }

          // Check what's at the center
          const x = rect.left + rect.width / 2;
          const y = rect.top + rect.height / 2;
          const el = document.elementFromPoint(x, y);
          console.log("Element at button center:", el);
          console.log("Is button or child?", el === next || next.contains(el));

          // Check all elements at that point
          const elements = [];
          let current = el;
          while (current) {
            elements.push(
              current.tagName +
                (current.className
                  ? "." + current.className.split(" ").join(".")
                  : "")
            );
            current = current.parentElement;
          }
          console.log("Element stack at button:", elements);

          // Try clicking
          console.log("Clicking next button...");
          next.click();
        }
      };
      console.log("[TEST] testButtons() ready");
    </script>

    <!-- Flatpickr JavaScript - Must load BEFORE dashboard-script.js -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Checkout Validator - Client-side validation and security -->
    <script src="{{ asset('js/checkout-validator.js?v=1.0') }}"></script>

    <!-- Load Dashboard Script (for additional functionality) -->
    <script src="{{ asset('js/dashboard-script.js?v=20260101003') }}"></script>

    <!-- Load Booking Flow Script -->
    <script src="{{ asset('js/booking-flow.js?v=2.5') }}"></script>

    <!-- DEBUG: Test booking buttons -->
    <script>
      console.log(
        "%c[DEBUG] BUTTON DEBUG SCRIPT LOADED",
        "color: #FF5722; font-weight: bold; font-size: 14px;"
      );

      // Wait for DOM to be ready
      setTimeout(() => {
        console.log(
          "%c[DEBUG] Testing booking type buttons...",
          "color: #2196F3; font-weight: bold;"
        );

        // Check if function exists
        console.log(
          "[DEBUG] window.selectBookingType exists?",
          typeof window.selectBookingType
        );

        // Find all booking type buttons
        const buttons = document.querySelectorAll(".select-booking-type-btn");
        console.log("[DEBUG] Found buttons:", buttons.length);

        buttons.forEach((btn, index) => {
          const onclickAttr = btn.getAttribute("onclick");
          console.log(`Button ${index + 1} (${btn.textContent.trim()}):`, {
            hasOnclickAttribute: !!onclickAttr,
            onclickValue: onclickAttr,
            isVisible: btn.offsetParent !== null,
            dimensions: { width: btn.offsetWidth, height: btn.offsetHeight },
            position: btn.getBoundingClientRect(),
          });

          // Test if clicking manually works
          btn.addEventListener("click", function (e) {
            console.log(
              `%c[CLICK] BUTTON ${index + 1} CLICKED!`,
              "color: #4CAF50; font-weight: bold; font-size: 16px;"
            );
            console.log("Event target:", e.target);
            console.log("Current target:", e.currentTarget);
          });
        });

        // Test calling the function directly
        console.log(
          "%c[DEBUG] Testing direct function call...",
          "color: #9C27B0; font-weight: bold;"
        );
        try {
          if (typeof window.selectBookingType === "function") {
            console.log('[DEBUG] Function is accessible! Testing with "daytime"...');
            // Don't actually call it, just verify it exists
            console.log(
              "Function signature:",
              window.selectBookingType.toString().substring(0, 100) + "..."
            );
          } else {
            console.error("[ERROR] window.selectBookingType is NOT a function!");
          }
        } catch (err) {
          console.error("[ERROR] Error testing function:", err);
        }

        console.log(
          "%c[SUCCESS] Button diagnostic complete! Check console for results.",
          "color: #4CAF50; font-weight: bold;"
        );
        console.log(
          "%c[PROMPT] Try clicking a booking type button above and watch for messages!",
          "background: #FFF3CD; color: #856404; padding: 8px; font-weight: bold;"
        );
      }, 2000);
    </script>

    <!-- Image Lightbox Script -->
    <script>
      // Make it global so it can be called from anywhere
      window.openImageLightbox = function (imageSrc, imageAlt) {
        console.log("Opening lightbox for:", imageSrc);
        const lightbox = document.getElementById("imageLightbox");
        const lightboxImage = document.getElementById("lightboxImage");
        lightboxImage.src = imageSrc;
        lightboxImage.alt = imageAlt;
        lightbox.style.display = "flex";
        document.body.style.overflow = "hidden";
      };

      window.closeImageLightbox = function () {
        const lightbox = document.getElementById("imageLightbox");
        lightbox.style.display = "none";
        document.body.style.overflow = "auto";
      };

      // Make BOTH images and buttons clickable - EVENT DELEGATION on document
      document.addEventListener(
        "click",
        function (e) {
          // Check if clicked on view-image-btn or its children
          const btn = e.target.closest(".view-image-btn");
          if (btn) {
            e.preventDefault();
            e.stopPropagation();

            let imgSrc = btn.dataset.imgSrc;
            let imgAlt = btn.dataset.imgAlt;

            // If data attributes are missing, find the image in the same room-item
            if (!imgSrc) {
              const roomItem = btn.closest(".room-item");
              if (roomItem) {
                const img = roomItem.querySelector("img");
                if (img) {
                  imgSrc = img.src;
                  imgAlt = img.alt;
                }
              }
            }

            console.log("View button clicked!", imgSrc);
            if (imgSrc) {
              window.openImageLightbox(imgSrc, imgAlt || "Image");
            }
            return;
          }

          // Check if clicked on a room-item image
          if (e.target.tagName === "IMG" && e.target.closest(".room-item")) {
            e.preventDefault();
            e.stopPropagation();

            console.log("Image clicked directly!", e.target.src);
            window.openImageLightbox(e.target.src, e.target.alt);
          }
        },
        true
      ); // Use capture phase for immediate response

      // Add View Image buttons and style images
      document.addEventListener("DOMContentLoaded", function () {
        window.addButtonsAndStyle = function () {
          try {
            const roomItems = document.querySelectorAll(".room-item");

            roomItems.forEach((item, index) => {
              const img = item.querySelector("img");
              const roomInfo = item.querySelector(".room-info");

              if (!img || !roomInfo) return;

              // Style image
              img.style.cursor = "pointer";
              img.style.transition = "all 0.3s ease";
              img.title = "Click to enlarge";

              // Add hover effect to image (only once)
              if (!img.dataset.hoverAdded) {
                img.addEventListener("mouseenter", function () {
                  this.style.transform = "scale(1.05)";
                  this.style.boxShadow = "0 4px 12px rgba(102, 126, 234, 0.4)";
                });

                img.addEventListener("mouseleave", function () {
                  this.style.transform = "scale(1)";
                  this.style.boxShadow = "none";
                });
                img.dataset.hoverAdded = "true";
              }

              // Check if button already exists
              if (roomInfo.querySelector(".view-image-btn")) return;

              // Create View Image button
              const viewBtn = document.createElement("button");
              viewBtn.className = "view-image-btn";
              viewBtn.type = "button";
              viewBtn.innerHTML =
                '<i class="fas fa-search-plus"></i> View Image';

              // Store image data in button's dataset
              viewBtn.dataset.imgSrc = img.src;
              viewBtn.dataset.imgAlt = img.alt;

              // Append button to room-info
              roomInfo.appendChild(viewBtn);
            });
          } catch (error) {
            console.error("Error in addButtonsAndStyle:", error);
          }
        };

        // Initial call
        requestAnimationFrame(window.addButtonsAndStyle);

        // Re-add when modal content changes (debounced)
        let debounceTimer;
        const observer = new MutationObserver(function (mutations) {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(function () {
            requestAnimationFrame(window.addButtonsAndStyle);
          }, 50);
        });

        const inclusionsContent = document.getElementById(
          "inclusionsModalContent"
        );
        if (inclusionsContent) {
          observer.observe(inclusionsContent, {
            childList: true,
            subtree: true,
          });
        }
      });

      // Close lightbox on Escape key
      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
          window.closeImageLightbox();
        }
      });
    </script>

    <!-- Floating Back to Top Button -->
    <button class="floating-back-to-top" id="floatingBackToTop" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Back to top" aria-label="Back to top">
      <i class="fas fa-arrow-up"></i>
    </button>
  </body>
</html>

