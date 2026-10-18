/**
 * AR Homes Booking Flow - Frontend Integration
 * Handles date availability, payment uploads, rebooking, and cancellation
 */

// ===========================
// MY BOOKINGS MANAGEMENT
// ===========================

// Statuses that should go to history, not active bookings
// Note: 'cancelled' has special handling - stays in My Reservations for 1 day for potential re-approval
const HISTORY_STATUSES_FLOW = [
  "completed",
  "checked_out",
  "no_show",
  "forfeited",
  "expired",
];

/**
 * Check if a canceled reservation is older than 1 day (should move to history)
 */
function isCancelledOlderThan1Day(booking) {
  if (booking.status !== "cancelled" && booking.status !== "canceled") {
    return false;
  }

  // Check cancelled_at or updated_at timestamp
  const cancelledAt = booking.cancelled_at || booking.updated_at;
  if (!cancelledAt) {
    return false; // Keep in My Reservations if we don't know when it was cancelled
  }

  const cancelDate = new Date(cancelledAt);
  const now = new Date();
  const oneDayMs = 24 * 60 * 60 * 1000; // 1 day in milliseconds

  return now - cancelDate > oneDayMs;
}

/**
 * Check if a reservation should be in history (past check-out date and fully paid)
 */
function isReservationCompleted(booking) {
  // If already has a history status, it's completed
  if (HISTORY_STATUSES_FLOW.includes(booking.status)) {
    return true;
  }

  // For cancelled reservations: stay in My Reservations for 1 day, then move to history
  if (booking.status === "cancelled" || booking.status === "canceled") {
    return isCancelledOlderThan1Day(booking);
  }

  // Check if the reservation is fully paid and past check-out date
  const isFullyPaid =
    booking.full_payment_verified == 1 ||
    (booking.downpayment_verified == 1 && booking.full_payment_verified == 1);

  if (isFullyPaid && booking.check_out_date) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Parse check-out date and time
    const checkOutDate = new Date(booking.check_out_date);

    // If check-out time is provided, use it; otherwise default to end of day
    if (booking.check_out_time) {
      const timeParts = booking.check_out_time.split(":");
      checkOutDate.setHours(
        parseInt(timeParts[0], 10),
        parseInt(timeParts[1], 10),
        0,
        0
      );
    } else {
      checkOutDate.setHours(23, 59, 59, 999);
    }

    // If current date/time is past check-out, it's completed
    if (new Date() > checkOutDate) {
      return true;
    }
  }

  return false;
}

/**
 * Load user's reservations
 */
async function loadMyBookings() {
  const loadingState = document.getElementById("bookingsLoadingState");
  const emptyState = document.getElementById("bookingsEmptyState");
  const bookingsGrid = document.getElementById("myBookingsGrid");

  // Show loading
  if (loadingState) loadingState.style.display = "block";
  if (emptyState) emptyState.style.display = "none";
  if (bookingsGrid) {
    bookingsGrid.style.display = "none";
    bookingsGrid.innerHTML = "";
  }

  try {
    const response = await fetch("user/get_my_reservations.php", {
      method: "GET",
      credentials: "same-origin",
    });

    const data = await response.json();
    if (loadingState) loadingState.style.display = "none";

    if (data.success && data.reservations && data.reservations.length > 0) {
      // Filter out reservations that are completed (past check-out date and fully paid)
      // These should go to Reservation History, not My Reservations
      const activeReservations = data.reservations.filter(
        (booking) => !isReservationCompleted(booking)
      );

      // Display only active bookings
      if (bookingsGrid) {
        if (activeReservations.length > 0) {
          bookingsGrid.style.display = "grid";
          renderBookings(activeReservations);
        } else {
          // All reservations are completed - show empty state
          if (emptyState) emptyState.style.display = "block";
        }
      }
    } else {
      // Show empty state
      if (emptyState) emptyState.style.display = "block";
    }
  } catch (error) {
    if (loadingState) loadingState.style.display = "none";
    if (emptyState) {
      emptyState.style.display = "block";
      emptyState.innerHTML = `
        <div style="background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 20px; padding: 40px 20px; max-width: 600px; margin: 0 auto; box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45); text-align: center;">
          <i class="fas fa-exclamation-triangle" style="font-size: 56px; color: #f87171; filter: drop-shadow(0 0 12px rgba(248, 113, 113, 0.4));"></i>
          <h3 style="margin-top: 20px; color: #ffffff; font-size: 1.35rem; font-weight: 700;">Error Loading Bookings</h3>
          <p style="color: rgba(255, 255, 255, 0.75); margin-top: 8px;">${error.message}</p>
          <button class="btn-submit-v2" onclick="loadMyBookings()" style="margin-top: 20px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-sync-alt"></i> Try Again
          </button>
        </div>
      `;
    }
  }
}

/**
 * Render bookings grid - Compact list view
 */
function renderBookings(bookings) {
  const grid = document.getElementById("myBookingsGrid");
  if (!grid) return;

  grid.innerHTML = bookings
    .map((booking, index) => {
      const statusClass = getStatusClass(booking.status);
      const statusLabel = booking.status_label || booking.status;

      // Check if this is a cancelled reservation (show refund notice)
      const isCancelled =
        booking.status === "cancelled" || booking.status === "canceled";
      const refundNotice = isCancelled
        ? `
        <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(251, 191, 36, 0.35); border-radius: 10px; padding: 8px 12px; margin-top: 10px; display: flex; align-items: center; gap: 10px;">
          <i class="fas fa-exclamation-triangle" style="color: #facc15;"></i>
          <div style="font-size: 0.82em;">
            <div style="font-weight: 700; color: #fde047;">Payment Refundable</div>
            <div style="color: rgba(255, 255, 255, 0.75); font-size: 0.9em;">Staff may re-approve within 24 hours</div>
          </div>
        </div>
      `
        : "";

      return `
        <div class="booking-list-item" onclick="openBookingModal(${index})" style="cursor: pointer; padding: 18px 20px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-radius: 18px; box-shadow: 0 15px 35px rgba(0,0,0,0.45); margin-bottom: 14px; transition: all 0.3s ease; border: 1px solid ${
        isCancelled ? "rgba(239, 68, 68, 0.45)" : "rgba(255, 255, 255, 0.14)"
      };" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='rgba(250, 204, 21, 0.55)'; this.style.boxShadow='0 20px 45px rgba(0,0,0,0.6), 0 0 25px rgba(250, 204, 21, 0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='${isCancelled ? "rgba(239, 68, 68, 0.45)" : "rgba(255, 255, 255, 0.14)"}'; this.style.boxShadow='0 15px 35px rgba(0,0,0,0.45)';">
          <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px;">
            <div style="flex: 1; min-width: 0;">
              <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                <span class="status-badge ${statusClass}" style="font-size: 0.75em; padding: 4px 10px; border-radius: 999px;">${statusLabel}</span>
                <span style="font-weight: 700; color: #facc15; font-size: 0.92em;">#${
                  booking.reservation_id
                }</span>
                <span style="color: rgba(255, 255, 255, 0.6); font-size: 0.85em;">• ${formatDate(
                  booking.check_in_date
                )}</span>
              </div>
              <div style="font-weight: 700; color: #ffffff; margin-bottom: 6px; font-size: 1.05em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${
                booking.package_name || "Package"
              } - ${getBookingTypeLabel(booking.booking_type)}</div>
              <div style="color: rgba(255, 255, 255, 0.75); font-size: 0.88em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fas fa-user" style="font-size: 0.9em; color: #facc15; margin-right: 4px;"></i> ${
                  booking.guest_name
                } <span style="margin: 0 6px; color: rgba(255,255,255,0.3);">|</span> 
                <i class="fas fa-tag" style="font-size: 0.9em; color: #facc15; margin-right: 4px;"></i> <span style="color: #fde047; font-weight: 700;">₱${parseFloat(
                  booking.total_amount
                ).toLocaleString()}</span>
              </div>
              ${refundNotice}
            </div>
            <div style="display: flex; flex-direction: column; align-items: center; gap: 6px; flex-shrink: 0; background: rgba(250, 204, 21, 0.1); border: 1px solid rgba(250, 204, 21, 0.3); padding: 10px 14px; border-radius: 12px; transition: all 0.2s ease;">
              <i class="fas fa-eye" style="color: #facc15; font-size: 1.25em;"></i>
              <span style="color: #fef08a; font-size: 0.72em; font-weight: 700; white-space: nowrap; text-transform: uppercase; letter-spacing: 0.5px;">View Details</span>
            </div>
          </div>
        </div>
        
        <!-- Hidden data for modal (stored in data attributes) -->
        <div id="booking-data-${index}" style="display: none;" data-booking='${JSON.stringify(
        booking
      ).replace(/'/g, "&apos;")}'></div>
      `;
    })
    .join("");

  // Attach event listeners to payment buttons after rendering
  setTimeout(() => {
    // Downpayment buttons
    const paymentButtons = grid.querySelectorAll(".payment-button");
    console.log("Found downpayment payment buttons:", paymentButtons.length);
    paymentButtons.forEach((btn) => {
      const reservationId = btn.getAttribute("data-reservation-id");
      console.log(
        "Attaching listener to button for reservation:",
        reservationId
      );
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Downpayment payment button clicked!", reservationId);
        payWithPayMongo(reservationId, this);
      });
    });

    // Full payment buttons
    const fullPaymentButtons = grid.querySelectorAll(".payment-button-full");
    console.log("Found full payment buttons:", fullPaymentButtons.length);
    fullPaymentButtons.forEach((btn) => {
      const reservationId = btn.getAttribute("data-reservation-id");
      const amount = btn.getAttribute("data-amount");
      console.log(
        "Attaching listener to full payment button for reservation:",
        reservationId,
        "Amount:",
        amount
      );
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Full payment button clicked!", reservationId);
        payFullBalanceWithPayMongo(reservationId, amount, this);
      });
    });
  }, 100);
}

/**
 * Open booking details modal
 */
function openBookingModal(index) {
  const bookingDataDiv = document.getElementById(`booking-data-${index}`);
  if (!bookingDataDiv) return;

  const booking = JSON.parse(bookingDataDiv.getAttribute("data-booking"));

  const modalHTML = createBookingModalHTML(booking);

  // Remove existing modal if any
  const existingModal = document.getElementById("bookingDetailsModal");
  if (existingModal) {
    existingModal.remove();
  }

  // Add modal to body
  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Show modal with animation
  setTimeout(() => {
    const modal = document.getElementById("bookingDetailsModal");
    if (modal) {
      modal.style.display = "flex";

      // Attach payment button listeners
      attachModalPaymentListeners(booking);
    }
  }, 10);
}

/**
 * Close booking details modal
 */
function closeBookingModal() {
  const modal = document.getElementById("bookingDetailsModal");
  if (modal) {
    modal.style.opacity = "0";
    setTimeout(() => {
      modal.remove();
    }, 300);
  }
}

/**
 * Create booking modal HTML
 */
function createBookingModalHTML(booking) {
  const statusClass = getStatusClass(booking.status);
  const statusLabel = booking.status_label || booking.status;

  return `
    <div id="bookingDetailsModal" style="
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(10, 15, 29, 0.85);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10000;
      opacity: 0;
      transition: opacity 0.3s ease;
      padding: 20px;
    " onclick="if(event.target === this) closeBookingModal()">
      <div style="
        background: rgba(15, 23, 42, 0.96);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 24px;
        max-width: 720px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 35px rgba(250, 204, 21, 0.08);
        animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        color: #ffffff;
      " onclick="event.stopPropagation()">
        
        <!-- Modal Header -->
        <div style="
          position: sticky;
          top: 0;
          background: rgba(15, 23, 42, 0.98);
          border-bottom: 1px solid rgba(255, 255, 255, 0.12);
          color: white;
          padding: 20px 24px;
          border-radius: 24px 24px 0 0;
          display: flex;
          justify-content: space-between;
          align-items: center;
          z-index: 10;
        ">
          <div>
            <h2 style="margin: 0; font-size: 1.45em; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 10px;">
              <i class="fas fa-receipt" style="color: #facc15;"></i> Booking Details
            </h2>
            <p style="margin: 5px 0 0 0; color: #facc15; font-weight: 700; font-size: 0.95em;">#${
              booking.reservation_id
            }</p>
          </div>
          <button onclick="closeBookingModal()" style="
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            color: #ffffff;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
          " onmouseover="this.style.background='#facc15'; this.style.color='#0f172a'; this.style.borderColor='#facc15';" onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; this.style.borderColor='rgba(255,255,255,0.15)';">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Modal Body -->
        <div style="padding: 26px;">
          
          ${
            booking.status === "confirmed" &&
            booking.downpayment_verified == 1 &&
            booking.full_payment_verified == 1
              ? `
          <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.25), rgba(16, 185, 129, 0.12)); border: 1px solid rgba(74, 222, 128, 0.4); color: #4ade80; padding: 14px 18px; display: flex; align-items: center; gap: 12px; border-radius: 14px; font-weight: 600; margin-bottom: 20px;">
            <i class="fas fa-check-double" style="font-size: 1.4em;"></i>
            <span>Fully Paid &amp; Verified ${
              booking.payment_method
                ? "via " + formatPaymentMethod(booking.payment_method)
                : ""
            }</span>
          </div>
          `
              : booking.status === "confirmed" &&
                booking.downpayment_verified == 1
              ? `
          <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.25), rgba(16, 185, 129, 0.12)); border: 1px solid rgba(74, 222, 128, 0.4); color: #4ade80; padding: 14px 18px; display: flex; align-items: center; gap: 12px; border-radius: 14px; font-weight: 600; margin-bottom: 20px;">
            <i class="fas fa-check-circle" style="font-size: 1.4em;"></i>
            <span>Downpayment Paid &amp; Verified ${
              booking.payment_method
                ? "via " + formatPaymentMethod(booking.payment_method)
                : ""
            }</span>
          </div>
          `
              : booking.downpayment_paid == 1 &&
                booking.status === "pending_confirmation"
              ? `
          <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.25), rgba(217, 119, 6, 0.12)); border: 1px solid rgba(251, 191, 36, 0.4); color: #fbbf24; padding: 14px 18px; display: flex; align-items: center; gap: 12px; border-radius: 14px; font-weight: 600; margin-bottom: 20px;">
            <i class="fas fa-clock" style="font-size: 1.4em;"></i>
            <span>Payment Received ${
              booking.payment_method
                ? "via " + formatPaymentMethod(booking.payment_method)
                : ""
            } - Awaiting Admin Approval</span>
          </div>
          `
              : ""
          }
          
          <!-- Status Badge -->
          <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span class="status-badge ${statusClass}" style="font-size: 0.9em; padding: 6px 14px; border-radius: 999px;">${statusLabel}</span>
            <span class="booking-type-badge ${
              booking.booking_type
            }" style="font-size: 0.85em; padding: 6px 12px; border-radius: 999px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); color: #ffffff;">
              <i class="fas ${getBookingTypeIcon(booking.booking_type)}" style="color: #facc15; margin-right: 5px;"></i>
              ${getBookingTypeLabel(booking.booking_type)}
            </span>
          </div>
          
          <!-- Package Name -->
          <h3 style="color: #ffffff; margin-bottom: 20px; font-size: 1.3em; font-weight: 700; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-box-open" style="color: #facc15;"></i> ${
              booking.package_name || "Package"
            }
          </h3>
          
          <!-- Booking Details Grid -->
          <div style="
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 20px;
            border-radius: 16px;
          ">
            <!-- Check-in Box -->
            <div style="
              padding: 14px;
              background: rgba(34, 197, 94, 0.12);
              border: 1px solid rgba(74, 222, 128, 0.3);
              border-radius: 12px;
              border-left: 4px solid #4ade80;
            ">
              <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                <i class="fas fa-sign-in-alt" style="color: #4ade80;"></i>
                <span style="font-weight: 700; color: #4ade80; text-transform: uppercase; font-size: 0.78em; letter-spacing: 0.5px;">Check-in</span>
              </div>
              <div style="font-weight: 700; color: #ffffff; font-size: 1.05em; margin-bottom: 4px;">
                ${formatDate(booking.check_in_date)}
              </div>
              <div style="color: #a7f3d0; font-size: 0.88em; font-weight: 600;">
                <i class="fas fa-clock" style="margin-right: 4px;"></i> ${formatTime12Hour(
                  booking.check_in_time
                )}
              </div>
            </div>
            
            <!-- Check-out Box -->
            <div style="
              padding: 14px;
              background: rgba(239, 68, 68, 0.12);
              border: 1px solid rgba(248, 113, 113, 0.3);
              border-radius: 12px;
              border-left: 4px solid #f87171;
            ">
              <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                <i class="fas fa-sign-out-alt" style="color: #f87171;"></i>
                <span style="font-weight: 700; color: #f87171; text-transform: uppercase; font-size: 0.78em; letter-spacing: 0.5px;">Check-out</span>
              </div>
              <div style="font-weight: 700; color: #ffffff; font-size: 1.05em; margin-bottom: 4px;">
                ${formatDate(booking.check_out_date)}
              </div>
              <div style="color: #fecaca; font-size: 0.88em; font-weight: 600;">
                <i class="fas fa-clock" style="margin-right: 4px;"></i> ${formatTime12Hour(
                  booking.check_out_time
                )}
              </div>
            </div>
            
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06); padding: 12px 14px; border-radius: 12px;">
              <div style="color: rgba(255, 255, 255, 0.65); font-size: 0.78em; margin-bottom: 4px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                <i class="fas fa-user" style="color: #facc15; margin-right: 4px;"></i> Guest Name
              </div>
              <div style="color: #ffffff; font-weight: 600;">${
                booking.guest_name
              }</div>
            </div>
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06); padding: 12px 14px; border-radius: 12px;">
              <div style="color: rgba(255, 255, 255, 0.65); font-size: 0.78em; margin-bottom: 4px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                <i class="fas fa-calendar-day" style="color: #facc15; margin-right: 4px;"></i> Duration
              </div>
              <div style="color: #ffffff; font-weight: 600;">${
                booking.booking_type === "daytime"
                  ? booking.number_of_days
                  : booking.number_of_nights
              } ${
    booking.booking_type === "daytime" ? "Day(s)" : "Night(s)"
  }</div>
            </div>
            <div style="grid-column: 1/-1; background: rgba(250, 204, 21, 0.08); border: 1px solid rgba(250, 204, 21, 0.25); padding: 14px 18px; border-radius: 14px;">
              <div style="color: rgba(255, 255, 255, 0.75); font-size: 0.8em; margin-bottom: 4px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                <i class="fas fa-tag" style="color: #facc15; margin-right: 4px;"></i> Total Amount
              </div>
              <div style="color: #fde047; font-weight: 800; font-size: 1.4em; text-shadow: 0 0 14px rgba(250, 204, 21, 0.3);">
                ₱${parseFloat(booking.total_amount).toLocaleString()}
              </div>
              <div style="color: rgba(255, 255, 255, 0.65); font-size: 0.82em; margin-top: 4px;">
                ₱${parseFloat(booking.base_price).toLocaleString()} × ${
    booking.booking_type === "daytime"
      ? booking.number_of_days
      : booking.number_of_nights
  } ${booking.booking_type === "daytime" ? "day(s)" : "night(s)"}
              </div>
            </div>
          </div>
          
          ${renderPaymentStatus(booking)}
          ${renderBookingActions(booking)}
          
        </div>
      </div>
    </div>
    
    <style>
      @keyframes slideUp {
        from {
          transform: translateY(40px);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }
      #bookingDetailsModal {
        opacity: 1 !important;
      }
    </style>
  `;
}

/**
 * Attach payment listeners in modal
 */
function attachModalPaymentListeners(booking) {
  setTimeout(() => {
    // Downpayment buttons
    const paymentButtons = document.querySelectorAll(
      "#bookingDetailsModal .payment-button"
    );
    paymentButtons.forEach((btn) => {
      const reservationId = btn.getAttribute("data-reservation-id");
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        payWithPayMongo(reservationId, this);
      });
    });

    // Full payment buttons
    const fullPaymentButtons = document.querySelectorAll(
      "#bookingDetailsModal .payment-button-full"
    );
    fullPaymentButtons.forEach((btn) => {
      const reservationId = btn.getAttribute("data-reservation-id");
      const amount = btn.getAttribute("data-amount");
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        payFullBalanceWithPayMongo(reservationId, amount, this);
      });
    });
  }, 100);
}

/**
 * Render payment status indicators
 */
function renderPaymentStatus(booking) {
  if (booking.status === "cancelled" || booking.status === "canceled") {
    return "";
  }

  let html = '<div class="payment-status-section" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">';

  // Downpayment status
  const downStatus = booking.downpayment_status || "Not Paid";
  const downClass =
    downStatus === "Verified"
      ? "verified"
      : downStatus === "Pending Verification"
      ? "pending"
      : "unpaid";

  const downpaymentAmount = parseFloat(
    booking.downpayment_amount || 0
  ).toLocaleString();
  const paymentMethod = booking.payment_method
    ? ` via ${formatPaymentMethod(booking.payment_method)}`
    : "";
  const paidAt = booking.downpayment_paid_at
    ? ` on ${new Date(booking.downpayment_paid_at).toLocaleDateString()}`
    : "";

  const isDownVerified = downClass === "verified";
  const isDownPending = downClass === "pending";

  html += `
    <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: ${
      isDownVerified
        ? "rgba(34, 197, 94, 0.12)"
        : isDownPending
        ? "rgba(245, 158, 11, 0.12)"
        : "rgba(239, 68, 68, 0.12)"
    }; border: 1px solid ${
      isDownVerified
        ? "rgba(74, 222, 128, 0.35)"
        : isDownPending
        ? "rgba(251, 191, 36, 0.35)"
        : "rgba(248, 113, 113, 0.35)"
    }; border-radius: 12px;">
      <i class="fas ${
        isDownVerified
          ? "fa-check-circle"
          : isDownPending
          ? "fa-clock"
          : "fa-times-circle"
      }" style="color: ${
        isDownVerified ? "#4ade80" : isDownPending ? "#fbbf24" : "#f87171"
      }; font-size: 1.3em;"></i>
      <div style="flex: 1;">
        <strong style="color: #ffffff; display: block; font-size: 0.95em;">Downpayment - ₱${downpaymentAmount}</strong>
        <span style="color: rgba(255, 255, 255, 0.75); font-size: 0.85em;">${downStatus}${
    isDownVerified || isDownPending ? paymentMethod + paidAt : ""
  }</span>
      </div>
    </div>
  `;

  // Full payment status
  if (booking.downpayment_verified == 1) {
    const fullStatus = booking.full_payment_status || "Not Paid";
    const fullClass =
      fullStatus === "Verified"
        ? "verified"
        : fullStatus === "Pending Verification"
        ? "pending"
        : "unpaid";

    const remainingBalance = booking.total_amount - booking.downpayment_amount;
    const remainingBalanceFormatted =
      parseFloat(remainingBalance).toLocaleString();

    const fullPaidAt = booking.full_payment_paid_at
      ? ` on ${new Date(booking.full_payment_paid_at).toLocaleDateString()}`
      : "";

    const isFullVerified = fullClass === "verified";
    const isFullPending = fullClass === "pending";

    html += `
      <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: ${
        isFullVerified
          ? "rgba(34, 197, 94, 0.12)"
          : isFullPending
          ? "rgba(245, 158, 11, 0.12)"
          : "rgba(239, 68, 68, 0.12)"
      }; border: 1px solid ${
        isFullVerified
          ? "rgba(74, 222, 128, 0.35)"
          : isFullPending
          ? "rgba(251, 191, 36, 0.35)"
          : "rgba(248, 113, 113, 0.35)"
      }; border-radius: 12px;">
        <i class="fas ${
          isFullVerified
            ? "fa-check-circle"
            : isFullPending
            ? "fa-clock"
            : "fa-times-circle"
        }" style="color: ${
          isFullVerified ? "#4ade80" : isFullPending ? "#fbbf24" : "#f87171"
        }; font-size: 1.3em;"></i>
        <div style="flex: 1;">
          <strong style="color: #ffffff; display: block; font-size: 0.95em;">Remaining Balance - ₱${remainingBalanceFormatted}</strong>
          <span style="color: rgba(255, 255, 255, 0.75); font-size: 0.85em;">${fullStatus}${
      isFullVerified || isFullPending ? fullPaidAt : ""
    }</span>
        </div>
      </div>
    `;
  }

  // Security Bond Information - always show for confirmed reservations
  html += `
    <div style="background: rgba(250, 204, 21, 0.08); padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(250, 204, 21, 0.25); border-left: 4px solid #facc15; margin-top: 6px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-shield-alt" style="color: #facc15; font-size: 1.2em;"></i>
        <div style="flex: 1; color: #ffffff;">
          <strong style="color: #fde047;">₱2,000 Security Bond</strong> upon check-in (refundable)<br>
          <span style="font-size: 0.85em; color: rgba(255, 255, 255, 0.75);">Covers damages/extra charges. Can be settled at check-in.</span>
        </div>
      </div>
    </div>
  `;

  html += "</div>";
  return html;
}

/**
 * Render booking action buttons
 */
function renderBookingActions(booking) {
  console.log("Rendering actions for booking:", booking.reservation_id);
  console.log("can_upload_downpayment:", booking.can_upload_downpayment);
  console.log("payment_method:", booking.payment_method);

  let html = '<div class="booking-actions" style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">';

  // Show cancellation notice for cancelled bookings (check both spellings)
  // Cancelled by admin/staff only - users cannot cancel their own reservations
  const isCancelledStatus =
    booking.status === "cancelled" || booking.status === "canceled";
  if (isCancelledStatus) {
    html += `
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(248, 113, 113, 0.35); border-left: 4px solid #f87171; padding: 14px 16px; border-radius: 12px; margin: 10px 0;">
        <div style="display: flex; align-items: start; gap: 10px;">
          <i class="fas fa-ban" style="color: #f87171; font-size: 1.2em; margin-top: 2px;"></i>
          <div>
            <strong style="color: #ffffff; display: block; margin-bottom: 4px;">Reservation Cancelled by Admin/Staff</strong>
            <p style="margin: 0; color: rgba(255, 255, 255, 0.75); font-size: 0.88em;">This reservation has been cancelled by the admin or staff.</p>
          </div>
        </div>
      </div>
      <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(74, 222, 128, 0.35); border-left: 4px solid #4ade80; padding: 14px 16px; border-radius: 12px; margin: 10px 0;">
        <div style="display: flex; align-items: start; gap: 10px;">
          <i class="fas fa-coins" style="color: #4ade80; font-size: 1.2em; margin-top: 2px;"></i>
          <div>
            <strong style="color: #ffffff; display: block; margin-bottom: 4px;"><i class="fas fa-wallet" style="color: #facc15; margin-right: 4px;"></i> Payment Refundable</strong>
            <p style="margin: 0; color: rgba(255, 255, 255, 0.75); font-size: 0.88em;">Your payment is eligible for refund. Admin/Staff may re-approve this reservation within 24 hours, or contact us for refund processing.</p>
          </div>
        </div>
      </div>
    `;
    html += "</div>";
    return html;
  }

  // Upload downpayment
  if (booking.can_upload_downpayment) {
    const onlinePaymentMethods = [
      "gcash",
      "paymaya",
      "grab_pay",
      "card",
      "dob_bpi",
      "dob_ubp",
      "atome",
      "otc",
    ];

    if (
      !booking.payment_method ||
      booking.payment_method === "" ||
      onlinePaymentMethods.includes(booking.payment_method)
    ) {
      console.log(
        "Rendering PayMongo button for reservation:",
        booking.reservation_id
      );
      html += `
        <button 
          class="btn-submit-v2 payment-button" 
          type="button" 
          data-reservation-id="${booking.reservation_id}"
          style="pointer-events: auto !important; cursor: pointer !important; z-index: 10; position: relative; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;"
        >
          <i class="fas fa-credit-card"></i> Pay Now via PayMongo
        </button>
      `;
    } else {
      html += `
        <button class="btn-submit-v2" type="button" onclick="openPaymentUploadModal('${booking.reservation_id}', 'downpayment', ${booking.downpayment_amount})" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
          <i class="fas fa-upload"></i> Upload Downpayment Proof
        </button>
      `;
    }
  }

  // Full payment options - pay online or at resort
  if (booking.can_upload_full_payment) {
    const remainingBalance = booking.total_amount - booking.downpayment_amount;
    html += `
      <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.12); padding: 16px; border-radius: 14px; border-left: 4px solid #facc15; margin: 12px 0;">
        <div style="display: flex; align-items: start; gap: 12px;">
          <i class="fas fa-wallet" style="color: #facc15; font-size: 1.3em; margin-top: 2px;"></i>
          <div style="flex: 1;">
            <strong style="color: #ffffff; display: block; margin-bottom: 6px; font-size: 1.05em;">Remaining Balance: <span style="color: #fde047;">₱${remainingBalance.toLocaleString()}</span></strong>
            <p style="margin: 0 0 12px 0; color: rgba(255, 255, 255, 0.75); font-size: 0.88em;">Choose your payment option:</p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
              <button 
                class="btn-submit-v2 payment-button-full" 
                type="button" 
                data-reservation-id="${booking.reservation_id}"
                data-amount="${remainingBalance}"
                style="padding: 12px 18px; font-size: 0.92em; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-credit-card"></i> Pay Remaining Balance Online
              </button>
              <button 
                class="btn-back-v2" 
                type="button" 
                onclick="showPayAtResortInfo()"
                style="padding: 12px 18px; font-size: 0.92em; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;">
                <i class="fas fa-building"></i> Pay at Resort
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Security Bond Information -->
      <div style="background: rgba(250, 204, 21, 0.08); padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(250, 204, 21, 0.25); border-left: 3px solid #facc15; margin: 10px 0; font-size: 0.85em;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <i class="fas fa-shield-alt" style="color: #facc15; font-size: 1.1em;"></i>
          <div style="flex: 1; color: #ffffff;">
            <strong style="color: #fde047;">₱2,000 Security Bond</strong> upon check-in (refundable) — Covers damages/extra charges. Can be paid at check-in.
          </div>
        </div>
      </div>
    `;
  }

  // Request rebooking - available once DOWNPAYMENT is paid (within 3 months)
  if (booking.can_rebook) {
    html += `
      <button class="btn-back-v2" onclick="openRebookingModal('${booking.reservation_id}', '${booking.check_in_date}')" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; margin-top: 8px; cursor: pointer;">
        <i class="fas fa-calendar-alt"></i> Request Rebooking (Within 3 Months)
      </button>
      <div style="background: rgba(34, 197, 94, 0.1); border-left: 4px solid #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); padding: 12px 14px; border-radius: 10px; margin-top: 8px; font-size: 0.85em; color: #e0f2fe;">
        <i class="fas fa-info-circle" style="color: #4ade80; margin-right: 6px;"></i>
        <strong style="color: #ffffff;">Downpayment is Non-Refundable:</strong>
        <span style="color: rgba(255, 255, 255, 0.8);"> Since downpayment cannot be refunded, you can rebook to another date within 3 months. Original date will be released when approved.</span>
      </div>
    `;
  }

  // Rebooking Policy Notice for paid reservations (no cancellation allowed)
  const isBookingCancelled =
    booking.status === "cancelled" || booking.status === "canceled";
  if (
    booking.downpayment_verified == 1 &&
    !booking.can_rebook &&
    !isBookingCancelled
  ) {
    html += `
      <div style="background: rgba(250, 204, 21, 0.08); border: 1px solid rgba(250, 204, 21, 0.25); border-left: 4px solid #facc15; padding: 14px 16px; border-radius: 12px; margin-top: 12px;">
        <strong style="color: #fde047; display: block; margin-bottom: 6px;">
          <i class="fas fa-info-circle"></i> No Cancellation Policy
        </strong>
        <p style="margin: 0; color: rgba(255, 255, 255, 0.85); font-size: 0.88em; line-height: 1.6;">
          • Downpayment is <strong style="color: #ffffff;">non-refundable/non-transferable</strong><br>
          • <strong style="color: #ffffff;">Rebooking available</strong> 7 days before check-in (within 3 months)<br>
          • Cancellation is <strong style="color: #ffffff;">not allowed</strong> once confirmed
        </p>
      </div>
    `;
  }

  // Days until check-in indicator
  if (!booking.is_past_checkin && booking.status === "confirmed") {
    html += `
      <div class="checkin-countdown" style="margin-top: 8px;">
        <i class="fas fa-calendar-day" style="color: #facc15; margin-right: 6px;"></i>
        <span>${booking.days_until_checkin} days until check-in</span>
      </div>
    `;
  }

  // Print Receipt button for confirmed/fully paid reservations
  if (
    booking.status === "confirmed" ||
    booking.status === "completed" ||
    booking.status === "checked_out"
  ) {
    html += `
      <button class="btn-back-v2" type="button" onclick="printBookingReceipt('${booking.reservation_id}')" style="margin-top: 10px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;">
        <i class="fas fa-print"></i> Print Receipt
      </button>
    `;
  }

  html += "</div>";
  return html;
}

// ===========================
// PAYMENT UPLOAD
// ===========================

/**
 * Open payment upload modal
 */
function openPaymentUploadModal(reservationId, paymentType, amount) {
  document.getElementById("paymentReservationId").value = reservationId;
  document.getElementById("paymentType").value = paymentType;

  const label =
    paymentType === "downpayment"
      ? `Downpayment - ₱${parseFloat(amount).toLocaleString()}`
      : `Full Payment - ₱${parseFloat(amount).toLocaleString()}`;

  document.getElementById("paymentForLabel").textContent = label;
  document.getElementById("paymentUploadModal").style.display = "flex";
}

/**
 * Close payment upload modal
 */
function closePaymentUploadModal() {
  document.getElementById("paymentUploadModal").style.display = "none";
  document.getElementById("paymentUploadForm").reset();
}

/**
 * Handle payment upload form submission
 */
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("paymentUploadForm");
  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Uploading...';

      const formData = new FormData(form);

      try {
        const response = await fetch("user/upload_payment_proof.php", {
          method: "POST",
          credentials: "same-origin",
          body: formData,
        });

        const data = await response.json();
        if (data.success) {
          alert("✅ " + data.message);
          closePaymentUploadModal();
          loadMyBookings(); // Reload bookings
        } else {
          alert("❌ " + data.message);
        }
      } catch (error) {
        alert("Error uploading payment proof: " + error.message);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });
  }
});

// ===========================
// PRINT BOOKING RECEIPT
// ===========================

/**
 * Print booking receipt
 */
function printBookingReceipt(reservationId) {
  // Find the booking data from the hidden div
  let booking = null;
  let index = 0;
  while (true) {
    const bookingDataDiv = document.getElementById(`booking-data-${index}`);
    if (!bookingDataDiv) break;
    const data = JSON.parse(bookingDataDiv.getAttribute("data-booking"));
    if (data.reservation_id === reservationId) {
      booking = data;
      break;
    }
    index++;
  }

  if (!booking) {
    alert("Booking data not found. Please refresh and try again.");
    return;
  }

  const remainingBalance =
    parseFloat(booking.total_amount) - parseFloat(booking.downpayment_amount);

  const receiptHTML = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Reservation Receipt - #${booking.reservation_id}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
        .receipt { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #11224e, #1e3a6e); color: white; padding: 25px; text-align: center; }
        .logo { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .subtitle { font-size: 12px; opacity: 0.9; }
        .receipt-title { margin-top: 15px; font-size: 18px; font-weight: 600; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-top: 10px; }
        .status-confirmed { background: #e3f2fd; color: #1565c0; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .section { padding: 20px; border-bottom: 1px solid #eee; }
        .section-title { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .info-item { }
        .info-label { font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 4px; }
        .info-value { font-size: 14px; color: #333; font-weight: 500; }
        .schedule-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .schedule-box { padding: 15px; border-radius: 10px; }
        .schedule-box.checkin { background: linear-gradient(135deg, #e8f5e9, #f1f8e9); border-left: 4px solid #4caf50; }
        .schedule-box.checkout { background: linear-gradient(135deg, #ffebee, #fce4ec); border-left: 4px solid #ef5350; }
        .schedule-label { font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
        .schedule-date { font-weight: 700; font-size: 14px; margin-bottom: 4px; }
        .schedule-time { font-size: 13px; font-weight: 600; }
        .payment-table { width: 100%; border-collapse: collapse; }
        .payment-table td { padding: 10px 0; font-size: 14px; }
        .payment-table td:last-child { text-align: right; font-weight: 500; }
        .total-row { background: #f8f9fa; }
        .total-row td { font-size: 16px; font-weight: 700; padding: 15px 10px; }
        .security-bond { background: #fef9e7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .footer { padding: 25px; text-align: center; background: #f8f9fa; }
        .footer p { font-size: 12px; color: #888; margin: 5px 0; }
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
          <span class="status-badge status-${
            booking.status === "confirmed" ? "confirmed" : "completed"
          }">
            ${booking.status_label || booking.status}
          </span>
        </div>
        
        <div class="section">
          <div class="section-title">Reservation Details</div>
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Reservation ID</div>
              <div class="info-value">#${booking.reservation_id}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Booking Type</div>
              <div class="info-value">${getBookingTypeLabel(
                booking.booking_type
              )}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Package</div>
              <div class="info-value">${
                booking.package_name || "Standard"
              }</div>
            </div>
            <div class="info-item">
              <div class="info-label">Duration</div>
              <div class="info-value">${
                booking.booking_type === "daytime"
                  ? booking.number_of_days + " Day(s)"
                  : booking.number_of_nights + " Night(s)"
              }</div>
            </div>
          </div>
        </div>
        
        <div class="section">
          <div class="section-title">Guest Information</div>
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Guest Name</div>
              <div class="info-value">${booking.guest_name || "N/A"}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Number of Guests</div>
              <div class="info-value">${
                booking.number_of_guests || 1
              } guest(s)</div>
            </div>
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value">${booking.guest_email || "N/A"}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Phone</div>
              <div class="info-value">${booking.guest_phone || "N/A"}</div>
            </div>
          </div>
        </div>
        
        <div class="section">
          <div class="section-title">Schedule</div>
          <div class="schedule-grid">
            <div class="schedule-box checkin">
              <div class="schedule-label" style="color: #2e7d32;">✓ Check-in</div>
              <div class="schedule-date" style="color: #1b5e20;">${formatDate(
                booking.check_in_date
              )}</div>
              <div class="schedule-time" style="color: #388e3c;">${formatTime12Hour(
                booking.check_in_time
              )}</div>
            </div>
            <div class="schedule-box checkout">
              <div class="schedule-label" style="color: #c62828;">✗ Check-out</div>
              <div class="schedule-date" style="color: #b71c1c;">${formatDate(
                booking.check_out_date
              )}</div>
              <div class="schedule-time" style="color: #d32f2f;">${formatTime12Hour(
                booking.check_out_time
              )}</div>
            </div>
          </div>
        </div>
        
        <div class="section">
          <div class="section-title">Payment Summary</div>
          <table class="payment-table">
            <tr>
              <td>Base Price</td>
              <td>₱${parseFloat(booking.base_price || 0).toLocaleString(
                "en-PH",
                { minimumFractionDigits: 2 }
              )}</td>
            </tr>
            <tr>
              <td>Downpayment (50%) - ${
                booking.downpayment_status || "Pending"
              }</td>
              <td>₱${parseFloat(booking.downpayment_amount).toLocaleString(
                "en-PH",
                { minimumFractionDigits: 2 }
              )}</td>
            </tr>
            <tr>
              <td>Remaining Balance - ${
                booking.full_payment_status || "Pending"
              }</td>
              <td>₱${remainingBalance.toLocaleString("en-PH", {
                minimumFractionDigits: 2,
              })}</td>
            </tr>
            <tr class="total-row">
              <td>Total Amount</td>
              <td>₱${parseFloat(booking.total_amount).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
              })}</td>
            </tr>
          </table>
          
          <div class="security-bond">
            <div style="display: flex; align-items: center; gap: 10px;">
              <span style="font-size: 1.2em;">🛡️</span>
              <div>
                <strong style="color: #e65100;">₱2,000 Security Bond upon Check-in</strong><br>
                <span style="font-size: 12px; color: #795548;">Refundable — Covers damages/extra charges. Can be paid at check-in.</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="footer">
          <p><strong>AR Homes Posadas Farm Resort</strong></p>
          <p>Thank you for choosing us for your stay!</p>
          <p style="margin-top: 10px;">For inquiries, contact us at: arhomesresort@gmail.com</p>
          <p style="margin-top: 15px; font-size: 10px; color: #aaa;">
            Receipt generated on ${new Date().toLocaleString()}
          </p>
        </div>
        
        <div class="no-print" style="text-align: center; padding: 20px;">
          <button onclick="window.print()" style="
            padding: 12px 30px;
            background: linear-gradient(135deg, #11224e, #1e3a6e);
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

// ===========================
// REBOOKING REQUEST
// ===========================

/**
 * Open rebooking modal
 */
function openRebookingModal(reservationId, currentCheckInDate) {
  try {
    const reservationIdInput = document.getElementById("rebookReservationId");
    const currentCheckInElement = document.getElementById("currentCheckInDate");
    const newCheckInElement = document.getElementById("newCheckInDate");
    const rebookingModal = document.getElementById("rebookingModal");

    if (
      !reservationIdInput ||
      !currentCheckInElement ||
      !newCheckInElement ||
      !rebookingModal
    ) {
      console.error("Rebooking modal elements not found");
      alert("Error: Rebooking form not found. Please refresh the page.");
      return;
    }

    // Check if within 7-day restriction
    const checkInDate = new Date(currentCheckInDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    checkInDate.setHours(0, 0, 0, 0);

    const daysUntilCheckIn = Math.floor(
      (checkInDate - today) / (1000 * 60 * 60 * 24)
    );

    if (daysUntilCheckIn < 0) {
      alert("❌ Cannot rebook a reservation with a past check-in date.");
      return;
    }

    if (daysUntilCheckIn < 7) {
      alert(
        `❌ Rebooking must be requested at least 7 days before check-in.\n\nYou have only ${daysUntilCheckIn} day(s) remaining.\n\nPlease contact us directly for assistance.`
      );
      return;
    }

    reservationIdInput.value = reservationId;
    currentCheckInElement.textContent = formatDate(currentCheckInDate);

    // Set date constraints: min = today, max = 3 months from original date
    const todayStr = new Date().toISOString().split("T")[0];
    const originalDate = new Date(currentCheckInDate);
    const threeMonthsLater = new Date(originalDate);
    threeMonthsLater.setMonth(threeMonthsLater.getMonth() + 3);
    const maxDateStr = threeMonthsLater.toISOString().split("T")[0];

    newCheckInElement.setAttribute("min", todayStr);
    newCheckInElement.setAttribute("max", maxDateStr);

    // Show date range info
    const dateRangeInfo = document.createElement("div");
    dateRangeInfo.id = "dateRangeInfo";
    dateRangeInfo.style.cssText =
      "background: #e3f2fd; padding: 10px; border-radius: 6px; margin-top: 10px; font-size: 0.9em; color: #1976d2;";
    dateRangeInfo.innerHTML = `<i class="fas fa-calendar-check"></i> <strong>Valid Date Range:</strong> ${formatDate(
      todayStr
    )} to ${formatDate(maxDateStr)} (within 3 months)`;

    // Remove old date range info if exists
    const oldInfo = document.getElementById("dateRangeInfo");
    if (oldInfo) oldInfo.remove();

    // Insert after new date input
    newCheckInElement.parentElement.appendChild(dateRangeInfo);

    rebookingModal.style.display = "flex";
  } catch (error) {
    console.error("Error opening rebooking modal:", error);
    alert("An error occurred. Please refresh the page and try again.");
  }
}

/**
 * Close rebooking modal
 */
function closeRebookingModal() {
  try {
    const rebookingModal = document.getElementById("rebookingModal");
    const rebookingForm = document.getElementById("rebookingForm");

    if (rebookingModal) {
      rebookingModal.style.display = "none";
    }

    if (rebookingForm) {
      rebookingForm.reset();
    }
  } catch (error) {
    console.error("Error closing rebooking modal:", error);
  }
}

/**
 * Handle rebooking form submission
 */
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("rebookingForm");
  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Submitting...';

      const formData = {
        reservation_id: document.getElementById("rebookReservationId").value,
        new_date: document.getElementById("newCheckInDate").value,
        reason: document.getElementById("rebookReason").value,
      };

      try {
        const response = await fetch("user/request_rebooking.php", {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(formData),
        });

        const data = await response.json();
        if (data.success) {
          alert(
            "✅ " +
              data.message +
              "\n\nOriginal: " +
              data.original_date +
              "\nNew Date: " +
              data.new_date
          );
          closeRebookingModal();
          loadMyBookings(); // Reload bookings
        } else {
          alert("❌ " + data.message);
        }
      } catch (error) {
        alert("Error submitting rebooking request: " + error.message);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });
  }
});

// ===========================
// CANCELLATION
// ===========================

/**
 * Open cancel modal
 */
function openCancelModal(reservationId) {
  try {
    const reservationIdInput = document.getElementById("cancelReservationId");
    const cancelModal = document.getElementById("cancelModal");

    if (!reservationIdInput) {
      console.error("cancelReservationId input not found");
      alert("Error: Cancel form not found. Please refresh the page.");
      return;
    }

    if (!cancelModal) {
      console.error("cancelModal not found");
      alert("Error: Cancel modal not found. Please refresh the page.");
      return;
    }

    reservationIdInput.value = reservationId;

    // Show cancellation policy warning
    const refundInfo = document.getElementById("cancelRefundInfo");
    if (refundInfo) {
      refundInfo.innerHTML = `
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 6px; margin: 15px 0;">
          <strong style="color: #92400e; display: block; margin-bottom: 5px;">
            <i class="fas fa-exclamation-triangle"></i> Cancellation Policy
          </strong>
          <p style="margin: 0; color: #78350f; font-size: 0.9em;">
            • Downpayment is <strong>non-refundable</strong> as per booking policy<br>
            • Once confirmed by admin, cancellation is <strong>no longer allowed</strong>
          </p>
        </div>
      `;
    }

    cancelModal.style.display = "flex";
  } catch (error) {
    console.error("Error opening cancel modal:", error);
    alert(
      "Error opening cancel form: " +
        error.message +
        ". Please refresh the page."
    );
  }
}

/**
 * Close cancel modal
 */
function closeCancelModal() {
  document.getElementById("cancelModal").style.display = "none";
  document.getElementById("cancelForm").reset();
}

/**
 * Handle cancellation form submission
 */
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("cancelForm");
  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const reservationIdInput = document.getElementById("cancelReservationId");
      const reasonInput = document.getElementById("cancelReason");

      if (!reservationIdInput || !reasonInput) {
        alert("Error: Form elements not found. Please refresh the page.");
        return;
      }

      // Double confirmation
      if (
        !confirm(
          "⚠️ FINAL CONFIRMATION\n\nAre you absolutely sure you want to cancel?\nYour downpayment will NOT be refunded."
        )
      ) {
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      if (!submitBtn) {
        alert("Error: Submit button not found. Please refresh the page.");
        return;
      }

      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Cancelling...';

      const formData = {
        reservation_id: reservationIdInput.value,
        reason: reasonInput.value,
      };

      try {
        const response = await fetch("user/cancel_reservation.php", {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(formData),
        });

        const data = await response.json();
        if (data.success) {
          alert("✅ " + data.message);
          closeCancelModal();
          loadMyBookings(); // Reload bookings
        } else {
          alert("❌ " + data.message);
        }
      } catch (error) {
        console.error("Cancellation error:", error);
        alert("Error cancelling reservation: " + error.message);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });
  }
});

// ===========================
// DATE AVAILABILITY CHECKER
// ===========================

/**
 * Check date availability before booking
 */
async function checkDateAvailability(checkInDate, bookingType) {
  try {
    const response = await fetch("user/check_availability.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        check_in_date: checkInDate,
        booking_type: bookingType,
      }),
    });

    const data = await response.json();
    if (data.locked) {
      return {
        available: false,
        message: `❌ This date is already booked by ${data.booked_by}`,
        locked: true,
      };
    }

    if (data.pending_reservations > 0) {
      return {
        available: true,
        message: `⚠️ Warning: ${data.pending_reservations} other user(s) are also trying to book this date. First to pay, first to reserve!`,
        warning: true,
      };
    }

    return {
      available: true,
      message: "✅ This date is available!",
      locked: false,
    };
  } catch (error) {
    return {
      available: false,
      message: "Error checking availability: " + error.message,
      error: true,
    };
  }
}

/**
 * Attach availability checker to booking form
 */
document.addEventListener("DOMContentLoaded", function () {
  const checkInInput = document.getElementById("checkInDate");
  const bookingTypeInputs = document.querySelectorAll(
    'input[name="bookingType"]'
  );

  if (checkInInput && bookingTypeInputs.length > 0) {
    // Check on date change
    checkInInput.addEventListener("change", async function () {
      const selectedDate = this.value;
      const selectedType = document.querySelector(
        'input[name="bookingType"]:checked'
      );

      if (selectedDate && selectedType) {
        const result = await checkDateAvailability(
          selectedDate,
          selectedType.value
        );
        displayAvailabilityMessage(result);
      }
    });

    // Check on booking type change
    bookingTypeInputs.forEach((input) => {
      input.addEventListener("change", async function () {
        const selectedDate = checkInInput.value;
        if (selectedDate) {
          const result = await checkDateAvailability(selectedDate, this.value);
          displayAvailabilityMessage(result);
        }
      });
    });
  }
});

/**
 * Display availability message
 */
function displayAvailabilityMessage(result) {
  // Remove existing message
  const existing = document.getElementById("availabilityMessage");
  if (existing) existing.remove();

  // Create new message
  const messageDiv = document.createElement("div");
  messageDiv.id = "availabilityMessage";
  messageDiv.style.cssText = `
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    font-weight: 600;
    border-left: 4px solid;
  `;

  if (result.locked) {
    messageDiv.style.background = "#fee";
    messageDiv.style.borderColor = "#dc3545";
    messageDiv.style.color = "#721c24";
    messageDiv.innerHTML = `<i class="fas fa-times-circle"></i> ${result.message}`;
  } else if (result.warning) {
    messageDiv.style.background = "#fff3cd";
    messageDiv.style.borderColor = "#ffc107";
    messageDiv.style.color = "#856404";
    messageDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${result.message}`;
  } else if (result.available) {
    messageDiv.style.background = "#d4edda";
    messageDiv.style.borderColor = "#28a745";
    messageDiv.style.color = "#155724";
    messageDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${result.message}`;
  } else {
    messageDiv.style.background = "#fee";
    messageDiv.style.borderColor = "#dc3545";
    messageDiv.style.color = "#721c24";
    messageDiv.innerHTML = `<i class="fas fa-times-circle"></i> ${result.message}`;
  }

  // Insert after check-in date input
  const checkInInput = document.getElementById("checkInDate");
  if (checkInInput && checkInInput.parentElement) {
    checkInInput.parentElement.insertAdjacentElement("afterend", messageDiv);
  }
}

// ===========================
// UTILITY FUNCTIONS
// ===========================

/**
 * Get status badge class
 */
function getStatusClass(status) {
  const statusMap = {
    pending_payment: "pending",
    pending_confirmation: "warning",
    confirmed: "confirmed",
    checked_in: "success",
    checked_out: "success",
    completed: "completed",
    cancelled: "cancelled",
    canceled: "cancelled",
    no_show: "cancelled",
    forfeited: "cancelled",
    rebooked: "info",
  };
  return statusMap[status] || "default";
}

/**
 * Get booking type icon
 */
function getBookingTypeIcon(bookingType) {
  const iconMap = {
    daytime: "fa-sun",
    nighttime: "fa-moon",
    "22hours": "fa-clock",
    "venue-daytime": "fa-sun",
    "venue-nighttime": "fa-moon",
    "venue-22hours": "fa-clock",
  };
  return iconMap[bookingType] || "fa-calendar";
}

/**
 * Get formatted booking type label
 */
function getBookingTypeLabel(bookingType) {
  const labelMap = {
    daytime: "DAYTIME",
    nighttime: "NIGHTTIME",
    "22hours": "22 HOURS",
    "venue-daytime": "VENUE - DAYTIME",
    "venue-nighttime": "VENUE - NIGHTTIME",
    "venue-22hours": "VENUE - 22 HOURS",
  };
  return labelMap[bookingType] || bookingType.toUpperCase();
}

/**
 * Format payment method name for display
 */
function formatPaymentMethod(method) {
  if (!method) return "";

  const methodMap = {
    card: "Credit/Debit Card",
    gcash: "GCash",
    grab_pay: "GrabPay",
    otc: "OTC/Coins.ph",
    paymaya: "Maya",
    dob_bpi: "BPI Online",
    dob_ubp: "UnionBank Online",
    atome: "Atome",
    bank_transfer: "Bank Transfer",
  };

  return methodMap[method.toLowerCase()] || method.toUpperCase();
}

/**
 * Format date for display
 */
function formatDate(dateString) {
  const date = new Date(dateString);
  const options = { year: "numeric", month: "short", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

/**
 * Format time to 12-hour AM/PM format
 */
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

// ===========================
// GCASH PAYMENT VIA PAYMONGO
// ===========================

/**
 * Pay with PayMongo (supports GCash, Maya, Card, etc.)
 */
async function payWithPayMongo(reservationId, btnElement) {
  console.log("payWithPayMongo called with:", reservationId, btnElement);

  if (!confirm("You will be redirected to the payment page. Continue?")) {
    return;
  }

  // Determine button element
  let btn = btnElement;
  if (!btn && typeof event !== "undefined" && event && event.target) {
    btn = event.target;
  }

  let originalText = "";
  if (btn) {
    originalText = btn.innerHTML;
  }

  try {
    // Show loading state
    if (btn) {
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
      btn.disabled = true;
    }

    console.log("Creating payment intent for reservation:", reservationId);

    // Create payment intent
    const response = await fetch("user/create_payment_intent.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        reservation_id: reservationId,
      }),
    });

    if (!response.ok) {
      const result = await response.json();
      console.error("Payment API error response:", result);
      throw new Error(result.message || `HTTP error ${response.status}`);
    }

    const result = await response.json();
    console.log("Payment intent result:", result);

    if (result.success && result.checkout_url) {
      // Redirect to PayMongo checkout page
      window.location.href = result.checkout_url;
    } else {
      throw new Error(result.message || "Failed to create payment");
    }
  } catch (error) {
    console.error("Payment error:", error);

    // Show user-friendly error message
    let errorMsg = error.message;
    if (errorMsg.includes("Failed to fetch")) {
      errorMsg =
        "Unable to connect to payment server. Please check your internet connection and try again.";
    }

    alert("Payment Error: " + errorMsg);

    // Restore button
    if (btn && originalText) {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  }
}

// Keep the old function name for backward compatibility
async function payWithGCash(reservationId) {
  return payWithPayMongo(reservationId);
}

// Store current payment details globally
let currentPaymentDetails = {
  reservationId: null,
  amount: 0,
  btnElement: null,
};

/**
 * Pay full remaining balance with PayMongo - Opens modal first
 */
async function payFullBalanceWithPayMongo(reservationId, amount, btnElement) {
  try {
    console.log(
      "payFullBalanceWithPayMongo called with:",
      reservationId,
      amount,
      btnElement
    );

    // Store payment details
    currentPaymentDetails = {
      reservationId: reservationId,
      amount: amount,
      btnElement: btnElement,
    };

    // Show payment options modal with null checks
    const balanceElement = document.getElementById("remainingBalanceAmount");
    const modalElement = document.getElementById("payRemainingBalanceModal");

    if (!balanceElement || !modalElement) {
      console.error("Pay remaining balance modal elements not found:", {
        balanceElement: !!balanceElement,
        modalElement: !!modalElement,
      });
      alert("Payment modal not found. Please refresh the page and try again.");
      return;
    }

    balanceElement.textContent = `₱${parseFloat(amount).toLocaleString()}`;
    modalElement.style.display = "flex";
  } catch (error) {
    console.error("Error in payFullBalanceWithPayMongo:", error);
    alert("Error opening payment modal: " + error.message);
  }
}

/**
 * Close remaining balance payment modal
 */
function closePayRemainingBalanceModal() {
  const modal = document.getElementById("payRemainingBalanceModal");
  if (modal) {
    modal.style.display = "none";
  }
  currentPaymentDetails = { reservationId: null, amount: 0, btnElement: null };
}

/**
 * Proceed with online payment (PayMongo)
 */
async function proceedWithOnlinePayment() {
  const { reservationId, amount, btnElement } = currentPaymentDetails;

  if (!reservationId) {
    alert("Payment details not found. Please try again.");
    return;
  }

  // Close modal
  closePayRemainingBalanceModal();

  try {
    // Show loading indicator
    showLoadingOverlay("Processing payment...");

    console.log("Creating payment intent for full balance:", reservationId);

    // Create payment intent for full balance
    const response = await fetch("user/create_payment_intent.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        reservation_id: reservationId,
        payment_type: "full_payment",
      }),
    });

    if (!response.ok) {
      const result = await response.json();
      console.error("Payment API error response:", result);
      throw new Error(result.message || `HTTP error ${response.status}`);
    }

    const result = await response.json();
    console.log("Payment intent result:", result);

    if (result.success && result.checkout_url) {
      // Redirect to PayMongo checkout page
      window.location.href = result.checkout_url;
    } else {
      throw new Error(result.message || "Failed to create payment");
    }
  } catch (error) {
    console.error("Payment error:", error);
    hideLoadingOverlay();

    // Show user-friendly error message
    let errorMsg = error.message;
    if (errorMsg.includes("Failed to fetch")) {
      errorMsg =
        "Unable to connect to payment server. Please check your internet connection and try again.";
    }

    alert("Payment Error: " + errorMsg);
  }
}

/**
 * Show loading overlay
 */
function showLoadingOverlay(message = "Loading...") {
  let overlay = document.getElementById("loadingOverlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.id = "loadingOverlay";
    overlay.style.cssText =
      "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 99999;";
    overlay.innerHTML = `
      <div style="background: white; padding: 30px 40px; border-radius: 12px; text-align: center;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2.5em; color: #11224e; margin-bottom: 15px;"></i>
        <div style="color: #11224e; font-weight: 600; font-size: 1.1em;">${message}</div>
      </div>
    `;
    document.body.appendChild(overlay);
  }
  overlay.style.display = "flex";
}

/**
 * Hide loading overlay
 */
function hideLoadingOverlay() {
  const overlay = document.getElementById("loadingOverlay");
  if (overlay) {
    overlay.style.display = "none";
  }
}

/**
 * Show information about paying at resort
 */
function showPayAtResortInfo() {
  const modal = document.getElementById("payAtResortModal");
  if (modal) {
    modal.style.display = "flex";
  } else {
    console.error("payAtResortModal element not found");
  }
}

/**
 * Close "Pay at Resort" information modal
 */
function closePayAtResortModal() {
  const modal = document.getElementById("payAtResortModal");
  if (modal) {
    modal.style.display = "none";
  }
}

// ===========================
// AUTO-LOAD BOOKINGS
// ===========================

// Load bookings when "My Bookings" section is opened
document.addEventListener("DOMContentLoaded", function () {
  const myBookingsBtn = document.querySelector('[data-section="my-bookings"]');
  if (myBookingsBtn) {
    myBookingsBtn.addEventListener("click", function () {
      loadMyBookings();
    });
  }

  // Auto-switch to My Reservations if requested via URL
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get("section") === "my-bookings" && myBookingsBtn) {
    setTimeout(() => {
      myBookingsBtn.click();
    }, 150);
  }
});

// Make modal functions globally accessible
window.openBookingModal = openBookingModal;
window.closeBookingModal = closeBookingModal;
window.payFullBalanceWithPayMongo = payFullBalanceWithPayMongo;
window.proceedWithOnlinePayment = proceedWithOnlinePayment;
window.closePayRemainingBalanceModal = closePayRemainingBalanceModal;
window.showPayAtResortInfo = showPayAtResortInfo;
window.closePayAtResortModal = closePayAtResortModal;
window.loadMyBookings = loadMyBookings;
window.openCancelModal = openCancelModal;
window.closeCancelModal = closeCancelModal;
window.openRebookingModal = openRebookingModal;
window.closeRebookingModal = closeRebookingModal;
window.openPaymentUploadModal = openPaymentUploadModal;
window.closePaymentUploadModal = closePaymentUploadModal;
window.printBookingReceipt = printBookingReceipt;
window.payWithPayMongo = payWithPayMongo;
window.payWithGCash = payWithGCash;
window.checkDateAvailability = checkDateAvailability;
