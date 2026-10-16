@extends('layouts.app')

@section('title', 'Reset Password - AR Homes Posadas Farm Resort')

@push('styles')
<style>
  body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .reset-container {
    max-width: 500px;
    margin: 40px auto;
    padding: 40px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  }
  .reset-header {
    text-align: center;
    margin-bottom: 30px;
  }
  .reset-header .logo img {
    width: 80px;
    height: 80px;
    margin-bottom: 20px;
  }
  .reset-header h1 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 10px;
  }
  .reset-header p {
    color: #666;
    font-size: 0.95rem;
  }
  .loading-container, .error-container, .success-container {
    text-align: center;
    padding: 40px;
  }
  .loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
  }
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  .error-icon {
    font-size: 4rem;
    color: #ff6b6b;
    margin-bottom: 20px;
  }
  .success-icon {
    font-size: 4rem;
    color: #00b894;
    margin-bottom: 20px;
  }
  .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
  }
</style>
@endpush

@section('content')
<div class="reset-container">
  <!-- Loading State -->
  <div id="loadingState" class="loading-container">
    <div class="loading-spinner"></div>
    <p>Verifying reset token...</p>
  </div>

  <!-- Error State -->
  <div id="errorState" class="error-container" style="display: none">
    <div class="error-icon"><i class="fas fa-times-circle"></i></div>
    <h2 style="color: #ff6b6b; margin-bottom: 10px">Invalid or Expired Link</h2>
    <p id="errorMessage" class="error-message">This password reset link is invalid or has expired.</p>
    <a href="{{ url('/') }}" class="btn-primary">
      <i class="fas fa-arrow-left"></i> Back to Login
    </a>
  </div>

  <!-- Success State -->
  <div id="successState" class="success-container" style="display: none">
    <div class="success-icon"><i class="fas fa-check-circle"></i></div>
    <h2 style="color: #00b894; margin-bottom: 10px">Password Reset Successful!</h2>
    <p class="success-message">Your password has been reset successfully. You can now login with your new password.</p>
    <a href="{{ url('/') }}" class="btn-primary">
      <i class="fas fa-sign-in-alt"></i> Go to Login
    </a>
  </div>

  <!-- Reset Form -->
  <div id="resetForm" style="display: none">
    <div class="reset-header">
      <div class="logo">
        <img src="{{ asset('images/logo/ar-homes-logo.png') }}" alt="AR Homes Resort Logo" />
      </div>
      <h1>Reset Password</h1>
      <p>Enter your new password below</p>
    </div>

    <form id="passwordResetForm">
      @csrf
      <div class="input-group">
        <div class="input-wrapper">
          <i class="fas fa-lock"></i>
          <input type="password" id="newPassword" name="newPassword" placeholder="New Password" required minlength="6" />
          <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
            <i class="fas fa-eye" id="toggleIconNew"></i>
          </button>
        </div>
        <div id="passwordStrength" class="password-strength" style="display: none"></div>
      </div>

      <div class="input-group">
        <div class="input-wrapper">
          <i class="fas fa-lock"></i>
          <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm New Password" required minlength="6" />
          <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
            <i class="fas fa-eye" id="toggleIconConfirm"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="login-btn" id="resetBtn">
        <span>Reset Password</span>
        <i class="fas fa-arrow-right"></i>
      </button>
    </form>

    <div class="signup-link" style="margin-top: 20px; text-align: center">
      <p><a href="{{ url('/') }}"><i class="fas fa-arrow-left"></i> Back to Login</a></p>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const urlParams = new URLSearchParams(window.location.search);
  const resetToken = urlParams.get("token");

  const loadingState = document.getElementById("loadingState");
  const errorState = document.getElementById("errorState");
  const successState = document.getElementById("successState");
  const resetFormDiv = document.getElementById("resetForm");
  const passwordResetForm = document.getElementById("passwordResetForm");
  const newPasswordInput = document.getElementById("newPassword");
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const resetBtn = document.getElementById("resetBtn");

  window.addEventListener("load", function () {
    if (!resetToken) {
      showError("No reset token provided. Please request a new password reset link.");
      return;
    }

    fetch("{{ url('/api/user/verify-reset-token') }}", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": window.csrfToken
      },
      body: JSON.stringify({ token: resetToken }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          showForm();
        } else {
          showError(data.message || "Invalid or expired reset token.");
        }
      })
      .catch((err) => {
        showError("Failed to verify reset token. Please try again.");
      });
  });

  passwordResetForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (newPassword !== confirmPassword) {
      alert("Passwords do not match!");
      return;
    }

    resetBtn.disabled = true;
    resetBtn.innerHTML = '<span>Resetting...</span><i class="fas fa-spinner fa-spin"></i>';

    fetch("{{ url('/api/user/reset-password') }}", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": window.csrfToken
      },
      body: JSON.stringify({ token: resetToken, password: newPassword, confirm_password: confirmPassword }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          showSuccess();
        } else {
          alert(data.message || "Failed to reset password.");
          resetBtn.disabled = false;
          resetBtn.innerHTML = '<span>Reset Password</span><i class="fas fa-arrow-right"></i>';
        }
      })
      .catch((err) => {
        alert("An error occurred. Please try again.");
        resetBtn.disabled = false;
        resetBtn.innerHTML = '<span>Reset Password</span><i class="fas fa-arrow-right"></i>';
      });
  });

  function showForm() {
    loadingState.style.display = "none";
    errorState.style.display = "none";
    successState.style.display = "none";
    resetFormDiv.style.display = "block";
  }

  function showError(msg) {
    loadingState.style.display = "none";
    resetFormDiv.style.display = "none";
    successState.style.display = "none";
    document.getElementById("errorMessage").textContent = msg;
    errorState.style.display = "block";
  }

  function showSuccess() {
    loadingState.style.display = "none";
    resetFormDiv.style.display = "none";
    errorState.style.display = "none";
    successState.style.display = "block";
  }

  function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const iconId = fieldId === "newPassword" ? "toggleIconNew" : "toggleIconConfirm";
    const icon = document.getElementById(iconId);
    if (input.type === "password") {
      input.type = "text";
      icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
      input.type = "password";
      icon.classList.replace("fa-eye-slash", "fa-eye");
    }
  }
</script>
@endpush
