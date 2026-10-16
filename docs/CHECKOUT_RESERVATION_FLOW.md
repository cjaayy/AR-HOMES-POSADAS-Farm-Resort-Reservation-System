# Checkout Reservation System Documentation

## AR Homes Posadas Farm Resort Reservation System

### Overview

This document describes the complete checkout reservation flow, including business logic, security measures, and implementation details.

---

## Table of Contents

1. [Reservation Flow](#reservation-flow)
2. [Reservation States](#reservation-states)
3. [Security Features](#security-features)
4. [API Endpoints](#api-endpoints)
5. [Frontend Integration](#frontend-integration)
6. [Database Schema](#database-schema)
7. [Configuration](#configuration)
8. [Scalability Recommendations](#scalability-recommendations)

---

## Reservation Flow

### Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        CHECKOUT RESERVATION FLOW                              │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌──────────┐     ┌──────────────┐     ┌─────────────────┐
    │   User   │────▶│ Select Date  │────▶│ Check Availability│
    │  Login   │     │ & Package    │     │   (Real-time)   │
    └──────────┘     └──────────────┘     └────────┬────────┘
                                                    │
                     ┌──────────────────────────────┴─────────────────┐
                     │                                                 │
                     ▼                                                 ▼
              ┌─────────────┐                                  ┌─────────────┐
              │  Available  │                                  │ Not Available│
              └──────┬──────┘                                  └─────────────┘
                     │                                                 │
                     ▼                                                 │
              ┌─────────────────┐                                     │
              │ Submit Booking  │◀────────────────────────────────────┘
              │ (with CSRF)     │      (Select Different Date)
              └────────┬────────┘
                       │
                       ▼
              ┌─────────────────┐
              │  PENDING_PAYMENT │ ─────── Timer: 24 hours ────────┐
              │  (Reservation    │                                  │
              │   Created)       │                                  ▼
              └────────┬────────┘                           ┌──────────────┐
                       │                                    │   EXPIRED    │
                       │ Pay Downpayment                    │  (Auto)      │
                       ▼                                    └──────────────┘
              ┌─────────────────────┐
              │ PENDING_CONFIRMATION │
              │ (Payment Received)   │
              └────────┬────────────┘
                       │
                       │ Admin Verifies Payment
                       ▼
              ┌─────────────────┐
              │   CONFIRMED     │ ◀──────────────────────┐
              │ (Date Locked)   │                        │
              └────────┬────────┘                        │
                       │                                 │
         ┌─────────────┼─────────────┐                   │
         │             │             │                   │
         ▼             ▼             ▼                   │
    ┌─────────┐  ┌──────────┐  ┌──────────┐             │
    │ REBOOKED│  │CHECK-IN  │  │ NO_SHOW  │             │
    │(7+ days │  │(On Date) │  │          │             │
    │ notice) │──┘          │  │          │             │
    └─────────┘   └────┬─────┘  └──────────┘             │
                       │                                 │
                       ▼                                 │
              ┌─────────────────┐                        │
              │   CHECKED_IN    │                        │
              └────────┬────────┘                        │
                       │                                 │
                       │ Check-Out                       │
                       ▼                                 │
              ┌─────────────────┐                        │
              │   COMPLETED     │                        │
              │  (Calculate     │                        │
              │   Overtime &    │                        │
              │   Return Bond)  │                        │
              └─────────────────┘                        │
                                                         │
         ┌─────────────────────────────────────────────┘
         │  Cancellation (only before payment verification)
         ▼
    ┌──────────────┐
    │  CANCELLED   │
    │ (Downpayment │
    │  Forfeited)  │
    └──────────────┘
```

### Step-by-Step Process

#### 1. User Authentication

- User must be logged in to make reservations
- Session validation on all API endpoints
- User ID format: `USR-YYYYMMDD-XXXX`

#### 2. Date Selection & Availability Check

- User selects booking type, date, and duration
- Real-time availability check via AJAX
- Shows warning if pending (unpaid) reservations exist
- **"First to pay, first to reserve"** policy

#### 3. Form Submission

- Client-side validation before submission
- CSRF token verification
- Form token for duplicate prevention
- Rate limiting (5 attempts per minute)

#### 4. Reservation Creation

- Server-side validation of all inputs
- Atomic database transaction with row locking
- Generates unique reservation ID: `RES-YYYYMMDD-XXXXX`
- Sets expiry timer (24 hours)

#### 5. Payment Processing

- Fixed downpayment: ₱1,000
- Supports online payments (PayMongo) and manual upload
- Payment proof stored securely
- Status changes to `pending_confirmation`

#### 6. Admin Verification

- Admin reviews payment proof
- Verifies and confirms reservation
- Date becomes locked for that booking type
- Guest receives confirmation email

#### 7. Check-In/Check-Out

- Staff performs check-in on arrival
- Collects security bond (₱2,000)
- Check-out calculates overtime charges
- Returns security bond minus deductions

---

## Reservation States

| Status                 | Description                      | Next States                                      | Actions Available       |
| ---------------------- | -------------------------------- | ------------------------------------------------ | ----------------------- |
| `pending_payment`      | Awaiting downpayment             | `pending_confirmation`, `cancelled`, `expired`   | Pay, Cancel             |
| `pending_confirmation` | Payment received, awaiting admin | `confirmed`, `cancelled`                         | -                       |
| `confirmed`            | Booking confirmed, date locked   | `checked_in`, `cancelled`, `no_show`, `rebooked` | Rebook (7+ days before) |
| `checked_in`           | Guest has arrived                | `completed`                                      | Check-out               |
| `completed`            | Stay finished                    | -                                                | Leave review            |
| `cancelled`            | Cancelled by user/admin          | -                                                | -                       |
| `no_show`              | Guest didn't show up             | -                                                | -                       |
| `expired`              | Payment timeout (24h)            | `pending_payment`                                | Retry                   |
| `rebooked`             | Date changed                     | `confirmed`, `cancelled`                         | -                       |

### State Transition Rules

```php
const VALID_TRANSITIONS = [
    'pending_payment' => ['pending_confirmation', 'cancelled', 'expired'],
    'pending_confirmation' => ['confirmed', 'cancelled'],
    'confirmed' => ['checked_in', 'cancelled', 'no_show', 'rebooked'],
    'checked_in' => ['completed'],
    'completed' => [],  // Final state
    'cancelled' => [],  // Final state
    'no_show' => [],    // Final state
    'expired' => ['pending_payment'],  // Can retry
    'rebooked' => ['confirmed', 'cancelled'],
];
```

---

## Security Features

### 1. CSRF Protection

- Token generated per session
- Validated on all POST requests
- 1-hour expiration
- Automatic regeneration

```javascript
// Frontend usage
const token = await CheckoutValidator.getCSRFToken();
```

```php
// Backend validation
if (!Security::validateCSRFToken($token)) {
    // Reject request
}
```

### 2. Input Sanitization

- All inputs sanitized on both client and server
- Email validation with filter_var
- Phone number sanitization (digits only)
- SQL injection prevention via prepared statements

```php
// Example
$bookingType = Security::sanitizeString($data['booking_type']);
$email = Security::sanitizeEmail($data['email']);
$date = Security::sanitizeDate($data['check_in_date']);
```

### 3. Rate Limiting

- 5 checkout attempts per minute per session
- Prevents abuse and brute force

```php
if (Security::isRateLimited('checkout_reservation', 5, 60)) {
    // Block request
}
```

### 4. Duplicate Submission Prevention

- Unique form token per submission
- Server tracks processed tokens
- Prevents double-charging

### 5. SQL Injection Prevention

- All queries use PDO prepared statements
- No direct string concatenation in queries

### 6. XSS Prevention

- HTML special characters escaped
- Content Security Policy headers

---

## API Endpoints

### User Endpoints

| Endpoint                         | Method | Description              |
| -------------------------------- | ------ | ------------------------ |
| `/user/process_checkout.php`     | POST   | Create new reservation   |
| `/user/check_availability.php`   | POST   | Check date availability  |
| `/user/get_csrf_token.php`       | GET    | Get CSRF token           |
| `/user/get_my_reservations.php`  | GET    | List user's reservations |
| `/user/upload_payment_proof.php` | POST   | Upload payment proof     |
| `/user/cancel_reservation.php`   | POST   | Cancel reservation       |
| `/user/request_rebooking.php`    | POST   | Request date change      |

### Admin Endpoints

| Endpoint                         | Method  | Description        |
| -------------------------------- | ------- | ------------------ |
| `/admin/verify_payment.php`      | POST    | Verify payment     |
| `/admin/check_in.php`            | POST    | Check-in guest     |
| `/admin/check_out.php`           | POST    | Check-out guest    |
| `/admin/expire_reservations.php` | GET/CLI | Auto-expire unpaid |

### Request/Response Examples

#### Create Reservation

**Request:**

```json
{
  "booking_type": "daytime",
  "package_type": "daytime-day",
  "check_in_date": "2026-01-15",
  "number_of_days": 1,
  "payment_method": "gcash",
  "group_size": 10,
  "special_requests": "Late check-in",
  "csrf_token": "abc123...",
  "form_token": "def456..."
}
```

**Response:**

```json
{
  "success": true,
  "message": "Reservation created successfully!",
  "reservation_id": "RES-20260101-A7K9M",
  "booking_details": {
    "check_in_date": "2026-01-15",
    "check_out_date": "2026-01-15",
    "check_in_time": "09:00:00",
    "check_out_time": "17:00:00",
    "booking_type": "daytime",
    "duration": 1,
    "duration_type": "days"
  },
  "pricing": {
    "base_price": 6000,
    "total_amount": 6000,
    "downpayment_amount": 1000,
    "remaining_balance": 5000,
    "security_bond": 2000
  },
  "expires_at": "2026-01-02 12:00:00",
  "new_csrf_token": "xyz789..."
}
```

---

## Frontend Integration

### Include Required Files

```html
<script src="js/checkout-validator.js"></script>
```

### Usage Example

```javascript
// Initialize on page load
document.addEventListener("DOMContentLoaded", async () => {
  // Fetch CSRF token
  await CheckoutValidator.fetchCSRFToken();
});

// Handle form submission
async function handleCheckout(event) {
  event.preventDefault();

  const formData = {
    booking_type: document.getElementById("bookingType").value,
    check_in_date: document.getElementById("checkInDate").value,
    number_of_days: document.getElementById("duration").value,
    payment_method: document.getElementById("paymentMethod").value,
    group_size: document.getElementById("groupSize").value,
    special_requests: document.getElementById("specialRequests").value,
  };

  // Validate
  const validation = CheckoutValidator.validateCheckoutForm(formData);
  if (!validation.valid) {
    CheckoutValidator.showFormErrors(validation.errors);
    return;
  }

  // Submit
  const result = await CheckoutValidator.submitCheckout(formData, {
    onStart: () => {
      CheckoutValidator.setButtonLoading(submitBtn, true);
    },
    onSuccess: (data) => {
      showSuccessModal(data);
      // Redirect to payment
    },
    onError: (error) => {
      CheckoutValidator.showFormErrors([error.message]);
    },
    onComplete: () => {
      CheckoutValidator.setButtonLoading(submitBtn, false);
    },
  });
}

// Check availability on date change
async function checkDateAvailability() {
  const date = document.getElementById("checkInDate").value;
  const type = document.getElementById("bookingType").value;

  const result = await CheckoutValidator.checkAvailability(date, type);

  if (!result.available) {
    showWarning(result.message);
  } else if (result.warning) {
    showInfo(result.warning);
  }
}
```

---

## Database Schema

### Key Tables

#### `reservations`

Main reservation table with all booking details.

```sql
CREATE TABLE reservations (
    reservation_id VARCHAR(20) PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100),
    guest_phone VARCHAR(20),
    booking_type VARCHAR(30) NOT NULL,
    package_type VARCHAR(50),
    check_in_date DATE NOT NULL,
    check_out_date DATE,
    check_in_time TIME,
    check_out_time TIME,
    number_of_days INT,
    number_of_nights INT,
    number_of_guests INT,
    base_price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    downpayment_amount DECIMAL(10,2),
    remaining_balance DECIMAL(10,2),
    security_bond DECIMAL(10,2),
    status VARCHAR(30) DEFAULT 'pending_payment',
    -- Payment fields
    downpayment_paid TINYINT(1) DEFAULT 0,
    downpayment_verified TINYINT(1) DEFAULT 0,
    full_payment_paid TINYINT(1) DEFAULT 0,
    full_payment_verified TINYINT(1) DEFAULT 0,
    -- Check-in/out tracking
    checked_in TINYINT(1) DEFAULT 0,
    checked_out TINYINT(1) DEFAULT 0,
    date_locked TINYINT(1) DEFAULT 0,
    locked_until DATETIME,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### `reservation_audit_log`

Tracks all status changes for audit purposes.

```sql
CREATE TABLE reservation_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id VARCHAR(20) NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_status VARCHAR(30),
    new_status VARCHAR(30),
    actor_id VARCHAR(20),
    actor_type ENUM('user', 'admin', 'staff', 'system'),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Configuration

### Booking Types & Pricing

| Type            | Check-in | Check-out | Base Price | Max Duration |
| --------------- | -------- | --------- | ---------- | ------------ |
| daytime         | 09:00    | 17:00     | ₱6,000     | 7 days       |
| nighttime       | 19:00    | 07:00     | ₱10,000    | 7 nights     |
| 22hours         | 14:00    | 12:00     | ₱18,000    | 7 nights     |
| venue-daytime   | 09:00    | 17:00     | ₱6,000     | 3 days       |
| venue-nighttime | 19:00    | 07:00     | ₱10,000    | 3 nights     |
| venue-22hours   | 14:00    | 12:00     | ₱18,000    | 3 nights     |

### Fixed Amounts

- **Downpayment:** ₱1,000 (non-refundable)
- **Security Bond:** ₱2,000 (refundable)
- **Overtime Rate:** ₱500-1,000/hour

### Policies

- **Payment Deadline:** 24 hours from reservation
- **Rebooking Notice:** 7 days minimum
- **Rebooking Window:** Within 3 months
- **Cancellation:** Only before payment verification

---

## Scalability Recommendations

### 1. Caching

- Implement Redis for session storage
- Cache frequently accessed pricing data
- Cache availability results (short TTL)

### 2. Queue System

- Use job queue for email notifications
- Async processing for payment verification
- Background task for expiry processing

### 3. Database Optimization

- Add indexes on frequently queried columns
- Partition reservations table by year
- Archive old reservations annually

### 4. API Rate Limiting

- Implement per-IP rate limiting
- Add API key authentication for integrations

### 5. Payment Integration

- Implement webhooks for payment status
- Add payment retry mechanism
- Support multiple payment providers

### 6. Email Notifications

- Confirmation emails on booking
- Reminder emails before check-in
- Expiry warning emails
- Review request after checkout

### 7. Monitoring

- Add logging for all transactions
- Implement error tracking (Sentry)
- Dashboard for reservation metrics

### 8. Future Features

- Calendar sync (Google, Outlook)
- SMS notifications
- Loyalty points system
- Group booking management
- Multi-language support
- Mobile app API

---

## Cron Jobs

### Auto-Expire Reservations

Run every 15 minutes:

```bash
*/15 * * * * php /path/to/admin/expire_reservations.php
```

### Cleanup Old Tokens

Run daily:

```bash
0 2 * * * mysql -u user -p db -e "CALL cleanup_expired_records()"
```

### Send Reminder Emails

Run every hour:

```bash
0 * * * * php /path/to/cron/send_reminders.php
```

---

## Testing

### Unit Tests

Test individual validation functions:

```javascript
// Test date validation
console.assert(
  CheckoutValidator.isValidDateFormat("2026-01-15") === true,
  "Valid date format"
);

// Test booking type validation
const result = CheckoutValidator.validateBookingType("daytime");
console.assert(result.valid === true, "Valid booking type");
```

### Integration Tests

Test complete checkout flow:

1. Login
2. Select date and check availability
3. Fill form with valid data
4. Submit and verify reservation created
5. Verify expiry timer set

---

## Support

For technical issues, check:

1. PHP error logs: `error_log`
2. Browser console for JavaScript errors
3. Network tab for API responses
4. Database for reservation state

---

**Version:** 1.0.0  
**Last Updated:** January 1, 2026  
**Author:** AR Homes Development Team
