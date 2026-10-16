@extends('layouts.app')

@section('title', 'AR Homes Posadas Farm Resort - Registration')

@section('content')
<div class="registration-container">
  <!-- Registration Form -->
  <main class="main-content">
    <div class="form-container">
      <div class="header">
        <div class="logo">
          <img src="{{ asset('images/logo/ar-homes-logo.png') }}" alt="AR Homes Posadas Farm Resort Logo" />
        </div>
        <div class="brand-text">
          <h1 class="title">AR Homes Posadas</h1>
          <p class="subtitle">Farm Resort &bull; Reservation System</p>
        </div>
      </div>

      <div class="form-header">
        <h2>Create Account</h2>
        <p>Please fill in your details to register</p>
      </div>

      <!-- Error Container -->
      <div id="errorContainer" class="error-container" style="display: none">
        <div class="error-header">
          <i class="fas fa-exclamation-circle"></i>
          <span>Please fix the following errors:</span>
        </div>
        <ul id="errorList"></ul>
      </div>

      <!-- Registration Form -->
      <form id="registrationForm" class="registration-form" novalidate>
        @csrf
        <!-- Name Fields Row -->
        <div class="form-row">
          <div class="form-group">
            <label for="givenName"> Given Name * </label>
            <div class="input-wrapper">
              <i class="fas fa-user"></i>
              <input type="text" id="givenName" name="givenName" class="form-input" placeholder="Given name" required />
            </div>
            <span id="givenNameError" class="error-message"></span>
          </div>

          <div class="form-group">
            <label for="lastName"> Last Name * </label>
            <div class="input-wrapper">
              <i class="fas fa-user"></i>
              <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Last name" required />
            </div>
            <span id="lastNameError" class="error-message"></span>
          </div>
        </div>

        <!-- Middle Name & Username Row -->
        <div class="form-row">
          <div class="form-group">
            <label for="middleName"> Middle Name * </label>
            <div class="input-wrapper">
              <i class="fas fa-user"></i>
              <input type="text" id="middleName" name="middleName" class="form-input" placeholder="Middle name" required />
            </div>
            <span id="middleNameError" class="error-message"></span>
          </div>

          <div class="form-group">
            <label for="username"> Username * </label>
            <div class="input-wrapper">
              <i class="fas fa-user-circle"></i>
              <input type="text" id="username" name="username" class="form-input" placeholder="Username (5+ characters)" required />
            </div>
            <span id="usernameError" class="error-message"></span>
          </div>
        </div>

        <!-- Email & Phone Row -->
        <div class="form-row">
          <div class="form-group">
            <label for="email"> Email Address * </label>
            <div class="input-wrapper">
              <i class="fas fa-envelope"></i>
              <input type="email" id="email" name="email" class="form-input" placeholder="name@example.com" required />
            </div>
            <span id="emailError" class="error-message"></span>
          </div>

          <div class="form-group">
            <label for="phoneNumber"> Phone Number * </label>
            <div class="input-wrapper">
              <i class="fas fa-phone"></i>
              <input type="tel" id="phoneNumber" name="phoneNumber" class="form-input" placeholder="09XX XXX XXXX" required />
            </div>
            <span id="phoneNumberError" class="error-message"></span>
          </div>
        </div>

        <!-- Password Row -->
        <div class="form-row">
          <div class="form-group">
            <label for="password"> Password * </label>
            <div class="input-wrapper">
              <i class="fas fa-lock"></i>
              <input type="password" id="password" name="password" class="form-input" placeholder="Minimum 6 characters" required />
              <button type="button" class="password-toggle" id="passwordToggle">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <span id="passwordError" class="error-message"></span>
          </div>

          <div class="form-group">
            <label for="confirmPassword"> Confirm Password * </label>
            <div class="input-wrapper">
              <i class="fas fa-lock"></i>
              <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Re-enter password" required />
              <button type="button" class="password-toggle" id="confirmPasswordToggle">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <span id="confirmPasswordError" class="error-message"></span>
          </div>
        </div>

        <button type="submit" class="register-btn">
          <span>Register</span>
          <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <div class="signin-link login-link">
        <p>Already have an account? <a href="{{ url('/') }}">Sign In</a></p>
      </div>
    </div>
  </main>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/registration-script.js') }}"></script>
@endpush
