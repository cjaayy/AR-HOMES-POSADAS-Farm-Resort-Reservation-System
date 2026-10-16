/**
 * Checkout Reservation Validator
 * AR Homes Posadas Farm Resort Reservation System
 *
 * Comprehensive client-side validation for checkout reservations
 * with CSRF protection and duplicate submission prevention
 */

const CheckoutValidator = (function () {
  "use strict";

  // ===========================
  // CONFIGURATION
  // ===========================

  const CONFIG = {
    // Booking type configurations
    bookingTypes: {
      daytime: {
        checkInTime: "09:00",
        checkOutTime: "17:00",
        durationType: "days",
        basePrice: 6000,
        securityBond: 2000,
        minDuration: 1,
        maxDuration: 7,
        label: "Daytime Package",
      },
      nighttime: {
        checkInTime: "19:00",
        checkOutTime: "07:00",
        durationType: "nights",
        basePrice: 10000,
        securityBond: 2000,
        minDuration: 1,
        maxDuration: 7,
        label: "Nighttime Package",
      },
      "22hours": {
        checkInTime: "14:00",
        checkOutTime: "12:00",
        durationType: "nights",
        basePrice: 18000,
        securityBond: 2000,
        minDuration: 1,
        maxDuration: 7,
        label: "22 Hours Package",
      },
      "venue-daytime": {
        checkInTime: "09:00",
        checkOutTime: "17:00",
        durationType: "days",
        basePrice: 6000,
        securityBond: 2000,
        minDuration: 1,
        maxDuration: 3,
        label: "Venue - Daytime",
      },
      "venue-nighttime": {
        checkInTime: "19:00",
        checkOutTime: "07:00",
        durationType: "nights",
        basePrice: 10000,
        securityBond: 2000,
        minDuration: 1,
        maxDuration: 3,
        label: "Venue - Nighttime",
      },
      "venue-22hours": {
        checkInTime: "14:00",
        checkOutTime: "12:00",
        durationType: "nights",
        basePrice: 18000,
        securityBond: 2000,
        minDuration: 1,
        maxDuration: 3,
        label: "Venue - 22 Hours",
      },
    },

    // Payment methods
    validPaymentMethods: [
      "gcash",
      "paymaya",
      "grab_pay",
      "card",
      "dob_bpi",
      "dob_ubp",
      "atome",
      "otc",
      "bank_transfer",
      "cash",
    ],

    // Fixed amounts
    downpaymentAmount: 1000,

    // Constraints
    maxAdvanceBookingDays: 365,
    minGuestCount: 1,
    maxGuestCount: 100,

    // API endpoints
    endpoints: {
      csrfToken: "user/get_csrf_token.php",
      checkAvailability: "user/check_availability.php",
      processCheckout: "user/process_checkout.php",
    },
  };

  // ===========================
  // STATE MANAGEMENT
  // ===========================

  let state = {
    csrfToken: null,
    formToken: null,
    isSubmitting: false,
    lastAvailabilityCheck: null,
  };

  // ===========================
  // UTILITY FUNCTIONS
  // ===========================

  /**
   * Generate unique form token for duplicate submission prevention
   */
  function generateFormToken() {
    const array = new Uint8Array(16);
    crypto.getRandomValues(array);
    return Array.from(array, (byte) => byte.toString(16).padStart(2, "0")).join(
      ""
    );
  }

  /**
   * Sanitize string input
   */
  function sanitizeString(input) {
    if (!input) return "";
    return String(input).trim().replace(/[<>]/g, "").substring(0, 1000); // Limit length
  }

  /**
   * Validate date format (YYYY-MM-DD)
   */
  function isValidDateFormat(dateStr) {
    if (!dateStr) return false;
    const regex = /^\d{4}-\d{2}-\d{2}$/;
    if (!regex.test(dateStr)) return false;

    const date = new Date(dateStr);
    return date instanceof Date && !isNaN(date);
  }

  /**
   * Check if date is not in the past
   */
  function isDateNotPast(dateStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const checkDate = new Date(dateStr);
    checkDate.setHours(0, 0, 0, 0);

    return checkDate >= today;
  }

  /**
   * Check if date is within allowed booking window
   */
  function isDateWithinWindow(dateStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const maxDate = new Date(today);
    maxDate.setDate(maxDate.getDate() + CONFIG.maxAdvanceBookingDays);

    const checkDate = new Date(dateStr);
    checkDate.setHours(0, 0, 0, 0);

    return checkDate <= maxDate;
  }

  /**
   * Format currency
   */
  function formatCurrency(amount) {
    return new Intl.NumberFormat("en-PH", {
      style: "currency",
      currency: "PHP",
      minimumFractionDigits: 2,
    }).format(amount);
  }

  /**
   * Format date for display
   */
  function formatDate(dateStr) {
    const options = {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    };
    return new Date(dateStr).toLocaleDateString("en-US", options);
  }

  // ===========================
  // CSRF TOKEN MANAGEMENT
  // ===========================

  /**
   * Fetch CSRF token from server
   */
  async function fetchCSRFToken() {
    try {
      const response = await fetch(CONFIG.endpoints.csrfToken, {
        method: "GET",
        credentials: "same-origin",
      });

      if (!response.ok) {
        throw new Error("Failed to fetch CSRF token");
      }

      const data = await response.json();
      if (data.success && data.csrf_token) {
        state.csrfToken = data.csrf_token;
        return data.csrf_token;
      }

      throw new Error("Invalid CSRF token response");
    } catch (error) {
      console.error("CSRF Token Error:", error);
      throw error;
    }
  }

  /**
   * Get current CSRF token (fetch if needed)
   */
  async function getCSRFToken() {
    if (!state.csrfToken) {
      await fetchCSRFToken();
    }
    return state.csrfToken;
  }

  // ===========================
  // VALIDATION FUNCTIONS
  // ===========================

  /**
   * Validate booking type
   */
  function validateBookingType(bookingType) {
    const errors = [];

    if (!bookingType) {
      errors.push("Please select a booking type");
      return { valid: false, errors };
    }

    if (!CONFIG.bookingTypes[bookingType]) {
      errors.push("Invalid booking type selected");
      return { valid: false, errors };
    }

    return {
      valid: true,
      errors: [],
      config: CONFIG.bookingTypes[bookingType],
    };
  }

  /**
   * Validate check-in date
   */
  function validateCheckInDate(dateStr, bookingType = null) {
    const errors = [];

    if (!dateStr) {
      errors.push("Please select a check-in date");
      return { valid: false, errors };
    }

    if (!isValidDateFormat(dateStr)) {
      errors.push("Invalid date format. Please use the date picker.");
      return { valid: false, errors };
    }

    if (!isDateNotPast(dateStr)) {
      errors.push("Check-in date cannot be in the past");
      return { valid: false, errors };
    }

    if (!isDateWithinWindow(dateStr)) {
      errors.push(
        `Cannot book more than ${CONFIG.maxAdvanceBookingDays} days in advance`
      );
      return { valid: false, errors };
    }

    return { valid: true, errors: [] };
  }

  /**
   * Validate duration (days/nights)
   */
  function validateDuration(duration, bookingType) {
    const errors = [];

    const typeConfig = CONFIG.bookingTypes[bookingType];
    if (!typeConfig) {
      errors.push("Invalid booking type");
      return { valid: false, errors };
    }

    const parsedDuration = parseInt(duration, 10);

    if (isNaN(parsedDuration) || parsedDuration < 1) {
      errors.push("Please enter a valid duration");
      return { valid: false, errors };
    }

    if (parsedDuration < typeConfig.minDuration) {
      errors.push(
        `Minimum duration is ${typeConfig.minDuration} ${typeConfig.durationType}`
      );
      return { valid: false, errors };
    }

    if (parsedDuration > typeConfig.maxDuration) {
      errors.push(
        `Maximum duration is ${typeConfig.maxDuration} ${typeConfig.durationType}`
      );
      return { valid: false, errors };
    }

    return { valid: true, errors: [], duration: parsedDuration };
  }

  /**
   * Validate number of guests
   */
  function validateGuestCount(count) {
    const errors = [];

    // Handle range format like "31-40"
    let guestCount = count;
    if (typeof count === "string" && count.includes("-")) {
      const parts = count.split("-").map((p) => parseInt(p.trim(), 10));
      if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
        guestCount = Math.floor((parts[0] + parts[1]) / 2);
      }
    } else {
      guestCount = parseInt(count, 10);
    }

    if (isNaN(guestCount) || guestCount < CONFIG.minGuestCount) {
      errors.push(`Minimum guest count is ${CONFIG.minGuestCount}`);
      return { valid: false, errors };
    }

    if (guestCount > CONFIG.maxGuestCount) {
      errors.push(`Maximum guest count is ${CONFIG.maxGuestCount}`);
      return { valid: false, errors };
    }

    return { valid: true, errors: [], guestCount };
  }

  /**
   * Validate payment method
   */
  function validatePaymentMethod(method) {
    const errors = [];

    if (!method) {
      errors.push("Please select a payment method");
      return { valid: false, errors };
    }

    if (!CONFIG.validPaymentMethods.includes(method)) {
      errors.push("Invalid payment method selected");
      return { valid: false, errors };
    }

    return { valid: true, errors: [] };
  }

  /**
   * Calculate pricing
   */
  function calculatePricing(bookingType, duration) {
    const typeConfig = CONFIG.bookingTypes[bookingType];
    if (!typeConfig) {
      return null;
    }

    const totalAmount = typeConfig.basePrice * duration;
    const downpayment = CONFIG.downpaymentAmount;
    const remainingBalance = totalAmount - downpayment;

    return {
      basePrice: typeConfig.basePrice,
      duration: duration,
      durationType: typeConfig.durationType,
      totalAmount: totalAmount,
      downpayment: downpayment,
      remainingBalance: remainingBalance,
      securityBond: typeConfig.securityBond,
      checkInTime: typeConfig.checkInTime,
      checkOutTime: typeConfig.checkOutTime,
    };
  }

  /**
   * Calculate check-out date
   */
  function calculateCheckOutDate(checkInDate, bookingType, duration) {
    const typeConfig = CONFIG.bookingTypes[bookingType];
    if (!typeConfig) return null;

    const checkIn = new Date(checkInDate);

    if (typeConfig.durationType === "days") {
      // Daytime: check-out same day or (duration - 1) days later
      checkIn.setDate(checkIn.getDate() + (duration - 1));
    } else {
      // Nighttime/22hours: check-out is duration days later
      checkIn.setDate(checkIn.getDate() + duration);
    }

    return checkIn.toISOString().split("T")[0];
  }

  /**
   * Full form validation
   */
  function validateCheckoutForm(formData) {
    const errors = [];
    const sanitizedData = {};

    // Validate booking type
    const bookingTypeResult = validateBookingType(formData.booking_type);
    if (!bookingTypeResult.valid) {
      errors.push(...bookingTypeResult.errors);
    } else {
      sanitizedData.booking_type = formData.booking_type;
    }

    // Validate check-in date
    const dateResult = validateCheckInDate(
      formData.check_in_date,
      formData.booking_type
    );
    if (!dateResult.valid) {
      errors.push(...dateResult.errors);
    } else {
      sanitizedData.check_in_date = formData.check_in_date;
    }

    // Validate duration
    const typeConfig = CONFIG.bookingTypes[formData.booking_type];
    if (typeConfig) {
      const durationKey =
        typeConfig.durationType === "days"
          ? "number_of_days"
          : "number_of_nights";
      const duration = formData[durationKey] || formData.duration || 1;

      const durationResult = validateDuration(duration, formData.booking_type);
      if (!durationResult.valid) {
        errors.push(...durationResult.errors);
      } else {
        sanitizedData[durationKey] = durationResult.duration;
        sanitizedData.duration = durationResult.duration;
      }
    }

    // Validate payment method
    const paymentResult = validatePaymentMethod(formData.payment_method);
    if (!paymentResult.valid) {
      errors.push(...paymentResult.errors);
    } else {
      sanitizedData.payment_method = formData.payment_method;
    }

    // Validate guest count (optional but if provided)
    if (formData.group_size || formData.number_of_guests) {
      const guestResult = validateGuestCount(
        formData.group_size || formData.number_of_guests
      );
      if (!guestResult.valid) {
        errors.push(...guestResult.errors);
      } else {
        sanitizedData.number_of_guests = guestResult.guestCount;
      }
    }

    // Sanitize optional fields
    sanitizedData.special_requests = sanitizeString(
      formData.special_requests || ""
    );
    sanitizedData.group_type = sanitizeString(formData.group_type || "");
    sanitizedData.package_type = sanitizeString(
      formData.package_type || formData.booking_type + "-day"
    );

    // Calculate derived values if form is valid
    if (errors.length === 0 && typeConfig) {
      sanitizedData.check_out_date = calculateCheckOutDate(
        sanitizedData.check_in_date,
        sanitizedData.booking_type,
        sanitizedData.duration
      );

      const pricing = calculatePricing(
        sanitizedData.booking_type,
        sanitizedData.duration
      );
      if (pricing) {
        sanitizedData.pricing = pricing;
      }
    }

    return {
      valid: errors.length === 0,
      errors: errors,
      data: sanitizedData,
    };
  }

  // ===========================
  // AVAILABILITY CHECK
  // ===========================

  /**
   * Check date availability via API
   */
  async function checkAvailability(checkInDate, bookingType) {
    try {
      const response = await fetch(CONFIG.endpoints.checkAvailability, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "same-origin",
        body: JSON.stringify({
          check_in_date: checkInDate,
          booking_type: bookingType,
        }),
      });

      const data = await response.json();
      state.lastAvailabilityCheck = {
        date: checkInDate,
        bookingType: bookingType,
        result: data,
        checkedAt: new Date(),
      };

      return data;
    } catch (error) {
      console.error("Availability check error:", error);
      return {
        success: false,
        available: false,
        message: "Failed to check availability. Please try again.",
      };
    }
  }

  // ===========================
  // CHECKOUT SUBMISSION
  // ===========================

  /**
   * Submit checkout reservation
   */
  async function submitCheckout(formData, callbacks = {}) {
    // Prevent duplicate submissions
    if (state.isSubmitting) {
      console.warn("Submission already in progress");
      return {
        success: false,
        message: "A submission is already in progress. Please wait.",
      };
    }

    state.isSubmitting = true;

    try {
      // Validate form
      const validation = validateCheckoutForm(formData);
      if (!validation.valid) {
        state.isSubmitting = false;
        if (callbacks.onValidationError) {
          callbacks.onValidationError(validation.errors);
        }
        return {
          success: false,
          message: validation.errors[0],
          errors: validation.errors,
        };
      }

      // Get CSRF token
      const csrfToken = await getCSRFToken();

      // Generate form token for duplicate prevention
      state.formToken = generateFormToken();

      // Prepare request data
      const requestData = {
        ...validation.data,
        csrf_token: csrfToken,
        form_token: state.formToken,
      };

      // Notify start
      if (callbacks.onStart) {
        callbacks.onStart();
      }

      // Submit to server
      const response = await fetch(CONFIG.endpoints.processCheckout, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
        },
        credentials: "same-origin",
        body: JSON.stringify(requestData),
      });

      const result = await response.json();

      // Update CSRF token if new one provided
      if (result.new_csrf_token) {
        state.csrfToken = result.new_csrf_token;
      }

      // Handle response
      if (result.success) {
        if (callbacks.onSuccess) {
          callbacks.onSuccess(result);
        }
      } else {
        if (callbacks.onError) {
          callbacks.onError(result);
        }
      }

      return result;
    } catch (error) {
      console.error("Checkout submission error:", error);
      const errorResult = {
        success: false,
        message: "An error occurred. Please try again.",
        error: error.message,
      };

      if (callbacks.onError) {
        callbacks.onError(errorResult);
      }

      return errorResult;
    } finally {
      state.isSubmitting = false;
      if (callbacks.onComplete) {
        callbacks.onComplete();
      }
    }
  }

  // ===========================
  // UI HELPERS
  // ===========================

  /**
   * Show validation errors on form
   */
  function showFormErrors(errors, containerSelector = ".validation-errors") {
    const container = document.querySelector(containerSelector);
    if (!container) {
      console.warn("Error container not found:", containerSelector);
      // Fallback to alert
      if (errors.length > 0) {
        alert("Validation Errors:\n" + errors.join("\n"));
      }
      return;
    }

    container.innerHTML = errors
      .map(
        (error) => `
            <div class="error-item">
                <i class="fas fa-exclamation-circle"></i>
                <span>${sanitizeString(error)}</span>
            </div>
        `
      )
      .join("");

    container.style.display = errors.length > 0 ? "block" : "none";
  }

  /**
   * Clear validation errors
   */
  function clearFormErrors(containerSelector = ".validation-errors") {
    const container = document.querySelector(containerSelector);
    if (container) {
      container.innerHTML = "";
      container.style.display = "none";
    }
  }

  /**
   * Show loading state on button
   */
  function setButtonLoading(button, isLoading) {
    if (!button) return;

    if (isLoading) {
      button.disabled = true;
      button.dataset.originalText = button.innerHTML;
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    } else {
      button.disabled = false;
      if (button.dataset.originalText) {
        button.innerHTML = button.dataset.originalText;
      }
    }
  }

  /**
   * Show success message
   */
  function showSuccessMessage(message, containerId = "successMessage") {
    const container = document.getElementById(containerId);
    if (container) {
      container.innerHTML = `
                <div class="success-alert">
                    <i class="fas fa-check-circle"></i>
                    <span>${sanitizeString(message)}</span>
                </div>
            `;
      container.style.display = "block";
    }
  }

  /**
   * Update pricing display
   */
  function updatePricingDisplay(bookingType, duration, selectors = {}) {
    const pricing = calculatePricing(bookingType, duration);
    if (!pricing) return;

    const defaults = {
      basePrice: "#basePriceDisplay",
      totalAmount: "#totalAmountDisplay",
      downpayment: "#downpaymentDisplay",
      remainingBalance: "#remainingBalanceDisplay",
      securityBond: "#securityBondDisplay",
    };

    const s = { ...defaults, ...selectors };

    const updates = [
      { selector: s.basePrice, value: formatCurrency(pricing.basePrice) },
      { selector: s.totalAmount, value: formatCurrency(pricing.totalAmount) },
      { selector: s.downpayment, value: formatCurrency(pricing.downpayment) },
      {
        selector: s.remainingBalance,
        value: formatCurrency(pricing.remainingBalance),
      },
      { selector: s.securityBond, value: formatCurrency(pricing.securityBond) },
    ];

    updates.forEach(({ selector, value }) => {
      const el = document.querySelector(selector);
      if (el) el.textContent = value;
    });

    return pricing;
  }

  // ===========================
  // PUBLIC API
  // ===========================

  return {
    // Configuration
    CONFIG: CONFIG,

    // Token management
    fetchCSRFToken,
    getCSRFToken,
    generateFormToken,

    // Validation
    validateBookingType,
    validateCheckInDate,
    validateDuration,
    validateGuestCount,
    validatePaymentMethod,
    validateCheckoutForm,

    // Calculations
    calculatePricing,
    calculateCheckOutDate,

    // API calls
    checkAvailability,
    submitCheckout,

    // UI helpers
    showFormErrors,
    clearFormErrors,
    setButtonLoading,
    showSuccessMessage,
    updatePricingDisplay,

    // Utilities
    sanitizeString,
    formatCurrency,
    formatDate,
    isValidDateFormat,
    isDateNotPast,
    isDateWithinWindow,

    // State access
    getState: () => ({ ...state }),
    isSubmitting: () => state.isSubmitting,
  };
})();

// Export for module systems
if (typeof module !== "undefined" && module.exports) {
  module.exports = CheckoutValidator;
}

// Make available globally
window.CheckoutValidator = CheckoutValidator;
