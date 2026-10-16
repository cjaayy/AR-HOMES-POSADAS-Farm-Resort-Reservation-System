// Registration Form Validation and Functionality
console.log("🔧 registration-script.js loaded at", new Date().toISOString());

document.addEventListener("DOMContentLoaded", function () {
  // Get form elements
  const form = document.getElementById("registrationForm");
  const errorContainer = document.getElementById("errorContainer");
  const errorList = document.getElementById("errorList");

  // Get input fields
  const lastName = document.getElementById("lastName");
  const givenName = document.getElementById("givenName");
  const middleName = document.getElementById("middleName");
  const email = document.getElementById("email");
  const phoneNumber = document.getElementById("phoneNumber");
  const username = document.getElementById("username");
  const password = document.getElementById("password");
  const confirmPassword = document.getElementById("confirmPassword");

  // Get password toggle buttons
  const passwordToggle = document.getElementById("passwordToggle");
  const confirmPasswordToggle = document.getElementById(
    "confirmPasswordToggle",
  );

  // Get submit button
  const submitBtn = form.querySelector(".register-btn");

  // Validation patterns
  const patterns = {
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    phone: /^[\+]?[0-9]{10,15}$/,
    username: /^[a-zA-Z0-9_-]{5,}$/,
    name: /^[a-zA-Z\s'-]{2,}$/,
  };

  // Error messages
  const errorMessages = {
    lastName:
      "Last name is required and must contain only letters, spaces, hyphens, or apostrophes.",
    givenName:
      "Given name is required and must contain only letters, spaces, hyphens, or apostrophes.",
    middleName:
      "Middle name is required and must contain only letters, spaces, hyphens, or apostrophes.",
    email: "Please enter a valid email address.",
    phoneNumber: "Phone number must be 10-15 digits and may include a + sign.",
    username:
      "Username must be at least 5 characters long and contain no spaces.",
    password: "Password is required.",
    confirmPassword: "Passwords do not match.",
    required: "This field is required.",
  };

  // Initialize password toggles
  initializePasswordToggles();

  // Add real-time validation
  addRealTimeValidation();

  // Handle form submission
  form.addEventListener("submit", handleFormSubmission);
  /**
   * Initialize password toggle functionality
   */
  function initializePasswordToggles() {
    passwordToggle.addEventListener("click", function () {
      togglePasswordVisibility(password, passwordToggle);
    });

    confirmPasswordToggle.addEventListener("click", function () {
      togglePasswordVisibility(confirmPassword, confirmPasswordToggle);
    });
  }

  /**
   * Toggle password visibility
   * @param {HTMLInputElement} inputField
   * @param {HTMLButtonElement} toggleBtn
   */
  function togglePasswordVisibility(inputField, toggleBtn) {
    const icon = toggleBtn.querySelector("i");

    if (inputField.type === "password") {
      inputField.type = "text";
      icon.className = "fas fa-eye-slash";
      toggleBtn.setAttribute("aria-label", "Hide password");
    } else {
      inputField.type = "password";
      icon.className = "fas fa-eye";
      toggleBtn.setAttribute("aria-label", "Show password");
    }
  }

  /**
   * Add real-time validation to all form fields
   */
  function addRealTimeValidation() {
    const fields = [
      lastName,
      givenName,
      middleName,
      email,
      phoneNumber,
      username,
      password,
      confirmPassword,
    ];

    fields.forEach((field) => {
      // Validate on blur (when user leaves the field)
      field.addEventListener("blur", function () {
        validateField(field);
      });

      // Validate on input for immediate feedback
      field.addEventListener("input", function () {
        clearFieldError(field);

        // For password confirmation, validate when typing
        if (field === confirmPassword || field === password) {
          setTimeout(() => validatePasswordMatch(), 100);
        }
      });
    });
  }

  /**
   * Validate individual field
   * @param {HTMLInputElement} field
   * @returns {boolean}
   */
  function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = "";

    // Check if field is required and empty
    if (!value) {
      isValid = false;
      errorMessage = errorMessages.required;
    } else {
      // Field-specific validation
      switch (fieldName) {
        case "lastName":
        case "givenName":
        case "middleName":
          if (!patterns.name.test(value)) {
            isValid = false;
            errorMessage = errorMessages[fieldName];
          }
          break;

        case "email":
          if (!patterns.email.test(value)) {
            isValid = false;
            errorMessage = errorMessages.email;
          }
          break;

        case "phoneNumber":
          // Remove all non-digit characters except +
          const cleanPhone = value.replace(/[^\d+]/g, "");
          if (!patterns.phone.test(cleanPhone)) {
            isValid = false;
            errorMessage = errorMessages.phoneNumber;
          }
          break;

        case "username":
          if (!patterns.username.test(value) || value.includes(" ")) {
            isValid = false;
            errorMessage = errorMessages.username;
          }
          break;

        case "password":
          if (value.length < 6) {
            isValid = false;
            errorMessage = "Password must be at least 6 characters long.";
          }
          // Also validate password match if confirm password has value
          if (confirmPassword.value) {
            validatePasswordMatch();
          }
          break;

        case "confirmPassword":
          if (value !== password.value) {
            isValid = false;
            errorMessage = errorMessages.confirmPassword;
          }
          break;
      }
    }

    // Update field UI
    if (isValid) {
      setFieldSuccess(field);
    } else {
      setFieldError(field, errorMessage);
    }

    return isValid;
  }

  /**
   * Validate password match specifically
   */
  function validatePasswordMatch() {
    const passwordValue = password.value;
    const confirmPasswordValue = confirmPassword.value;

    if (confirmPasswordValue && passwordValue !== confirmPasswordValue) {
      setFieldError(confirmPassword, errorMessages.confirmPassword);
      return false;
    } else if (confirmPasswordValue && passwordValue === confirmPasswordValue) {
      setFieldSuccess(confirmPassword);
      return true;
    }

    return true;
  }

  /**
   * Set field error state
   * @param {HTMLInputElement} field
   * @param {string} message
   */
  function setFieldError(field, message) {
    field.classList.remove("success");
    field.classList.add("error");

    const errorElement = document.getElementById(field.name + "Error");
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = "block";
    }
  }

  /**
   * Set field success state
   * @param {HTMLInputElement} field
   */
  function setFieldSuccess(field) {
    field.classList.remove("error");
    field.classList.add("success");

    const errorElement = document.getElementById(field.name + "Error");
    if (errorElement) {
      errorElement.textContent = "";
      errorElement.style.display = "none";
    }
  }

  /**
   * Clear field error state
   * @param {HTMLInputElement} field
   */
  function clearFieldError(field) {
    field.classList.remove("error");

    const errorElement = document.getElementById(field.name + "Error");
    if (errorElement) {
      errorElement.textContent = "";
      errorElement.style.display = "none";
    }
  }

  /**
   * Handle form submission
   * @param {Event} e
   */
  function handleFormSubmission(e) {
    e.preventDefault();

    // Clear previous error container
    hideErrorContainer();

    // Validate all fields
    const fields = [
      lastName,
      givenName,
      middleName,
      email,
      phoneNumber,
      username,
      password,
      confirmPassword,
    ];
    let isFormValid = true;
    const errors = [];

    fields.forEach((field) => {
      if (!validateField(field)) {
        isFormValid = false;
        const fieldLabel = field.previousElementSibling.textContent
          .replace("*", "")
          .trim();
        const errorElement = document.getElementById(field.name + "Error");
        if (errorElement && errorElement.textContent) {
          errors.push(`${fieldLabel}: ${errorElement.textContent}`);
        }
      }
    });

    // Additional validation for password match
    if (!validatePasswordMatch()) {
      isFormValid = false;
    }

    if (!isFormValid) {
      showErrorContainer(errors);
      // Scroll to the first error
      const firstErrorField = form.querySelector(".form-input.error");
      if (firstErrorField) {
        firstErrorField.scrollIntoView({ behavior: "smooth", block: "center" });
        firstErrorField.focus();
      }
      return;
    }

    // If form is valid, simulate registration process
    handleSuccessfulRegistration();
  }

  /**
   * Show error container with list of errors
   * @param {Array} errors
   */
  function showErrorContainer(errors) {
    if (errors.length === 0) return;

    errorList.innerHTML = "";
    errors.forEach((error) => {
      const li = document.createElement("li");
      li.textContent = error;
      errorList.appendChild(li);
    });

    errorContainer.style.display = "block";
    errorContainer.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  /**
   * Hide error container
   */
  function hideErrorContainer() {
    errorContainer.style.display = "none";
    errorList.innerHTML = "";
  }

  /**
   * Handle successful registration
   */
  function handleSuccessfulRegistration() {
    // Show loading state
    submitBtn.classList.add("loading");
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Registering...';

    // Prepare registration data
    const registrationData = {
      lastName: lastName.value.trim(),
      givenName: givenName.value.trim(),
      middleName: middleName.value.trim(),
      email: email.value.trim(),
      phoneNumber: phoneNumber.value.trim(),
      username: username.value.trim(),
      password: password.value,
    };

    // Send registration request to backend
    // Always use the full Apache-served URL
    const registerUrl = "user/register.php";
    fetch(registerUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(registrationData),
    })
      .then((response) => {
        return response.text();
      })
      .then((responseText) => {
        let data;
        try {
          data = JSON.parse(responseText);
        } catch (e) {
          throw new Error("Invalid JSON response from server");
        }

        return data;
      })
      .then((data) => {
        // Reset button state
        submitBtn.classList.remove("loading");
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Register';

        if (data.success) {
          // Show success message
          showSuccessMessage();

          // Redirect to login after short delay
          setTimeout(() => {
            window.location.href = "index.html";
          }, 1500);
        } else {
          // Show error message
          const errors = data.errors || [
            data.message || "Registration failed. Please try again.",
          ];
          showErrorContainer(errors);
        }
      })
      .catch((error) => {
        // Reset button state
        submitBtn.classList.remove("loading");
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Register';

        // Show error message
        showErrorContainer([
          "Network error. Please check your connection and try again.",
        ]);
      });
  }

  /**
   * Show success message
   */
  function showSuccessMessage() {
    // Create success message
    const successMessage = document.createElement("div");
    successMessage.className = "success-message-container";
    successMessage.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #38a169;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        `;
    successMessage.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle"></i>
                <span>Registration successful! Redirecting to login...</span>
            </div>
        `;

    // Add keyframe animation
    if (!document.querySelector("#successAnimation")) {
      const style = document.createElement("style");
      style.id = "successAnimation";
      style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
      document.head.appendChild(style);
    }

    document.body.appendChild(successMessage);

    // Remove message after 3 seconds
    setTimeout(() => {
      if (successMessage.parentNode) {
        successMessage.parentNode.removeChild(successMessage);
      }
    }, 3000);
  }

  /**
   * Store registration data (frontend only - for demo purposes)
   */
  function storeRegistrationData() {
    const userData = {
      lastName: lastName.value.trim(),
      givenName: givenName.value.trim(),
      middleName: middleName.value.trim(),
      email: email.value.trim(),
      phoneNumber: phoneNumber.value.trim(),
      username: username.value.trim(),
      registrationDate: new Date().toISOString(),
    };

    // Store in localStorage (in real app, this would be sent to backend)
    try {
      localStorage.setItem("registeredUser", JSON.stringify(userData));
    } catch (error) {}
  }

  /**
   * Format phone number as user types
   */
  phoneNumber.addEventListener("input", function () {
    let value = this.value.replace(/\D/g, ""); // Remove non-digits

    // Format as (XXX) XXX-XXXX for US numbers
    if (value.length >= 6) {
      value = value.replace(/(\d{3})(\d{3})(\d{4})/, "($1) $2-$3");
    } else if (value.length >= 3) {
      value = value.replace(/(\d{3})(\d{1,3})/, "($1) $2");
    }

    this.value = value;
  });

  /**
   * Prevent spaces in username
   */
  username.addEventListener("keydown", function (e) {
    if (e.key === " ") {
      e.preventDefault();
    }
  });

  /**
   * Auto-focus first field on page load
   */
  setTimeout(() => {
    lastName.focus();
  }, 500);

  /**
   * Handle form reset (if needed)
   */
  function resetForm() {
    form.reset();

    // Clear all error states
    const allFields = form.querySelectorAll(".form-input");
    allFields.forEach((field) => {
      field.classList.remove("error", "success");
    });

    // Clear all error messages
    const allErrors = form.querySelectorAll(".error-message");
    allErrors.forEach((error) => {
      error.textContent = "";
      error.style.display = "none";
    });

    hideErrorContainer();
  }

  // Expose reset function globally if needed
  window.resetRegistrationForm = resetForm;

  // Add keyboard navigation support
  form.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && e.target.type !== "submit") {
      e.preventDefault();
      const fields = Array.from(form.querySelectorAll(".form-input"));
      const currentIndex = fields.indexOf(e.target);
      const nextField = fields[currentIndex + 1];

      if (nextField) {
        nextField.focus();
      } else {
        submitBtn.focus();
      }
    }
  });
});
