<?php

use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\PasswordResetController;

// User Feature Controllers
use App\Http\Controllers\User\ReservationController as UserReservationController;
use App\Http\Controllers\User\PaymentController as UserPaymentController;
use App\Http\Controllers\User\PaymentWebhookController;
use App\Http\Controllers\User\ReviewController as UserReviewController;
use App\Http\Controllers\User\NotificationController as UserNotificationController;

// Admin Feature Controllers
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;

// Staff Feature Controllers
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffReservationController;
use App\Http\Controllers\Staff\StaffGuestController;
use App\Http\Controllers\Staff\StaffTaskController;
use App\Http\Controllers\Staff\StaffReportController;
use App\Http\Controllers\Staff\StaffUserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Clean RESTful routes with full backward-compatible native PHP aliases.
|
*/

// =========================================================================
// 1. Guest & User Authentication Flow
// =========================================================================
Route::prefix('user')->group(function () {
    Route::post('/login', [UserAuthController::class, 'login']);
    Route::post('/register', [UserAuthController::class, 'register']);
    Route::post('/logout', [UserAuthController::class, 'logout']);
    Route::get('/session', [UserAuthController::class, 'session']);

    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/verify-reset-token', [PasswordResetController::class, 'verifyResetToken']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

// Legacy User Auth Aliases
Route::post('/user/login.php', [UserAuthController::class, 'login']);
Route::post('/user/register.php', [UserAuthController::class, 'register']);
Route::post('/user/logout.php', [UserAuthController::class, 'logout']);
Route::get('/user/get_session.php', [UserAuthController::class, 'session']);
Route::post('/user/forgot_password.php', [PasswordResetController::class, 'forgotPassword']);
Route::post('/user/verify_reset_token.php', [PasswordResetController::class, 'verifyResetToken']);
Route::post('/user/reset_password.php', [PasswordResetController::class, 'resetPassword']);

// =========================================================================
// 2. User Reservation & Booking Endpoints
// =========================================================================
Route::prefix('user')->group(function () {
    Route::post('/check-availability', [UserReservationController::class, 'checkAvailability']);
    Route::post('/unavailable-dates', [UserReservationController::class, 'getUnavailableDates']);
    Route::post('/process-checkout', [UserReservationController::class, 'processCheckout']);
    Route::post('/make-reservation', [UserReservationController::class, 'makeReservation']);
    Route::get('/my-reservations', [UserReservationController::class, 'myReservations']);
    Route::post('/cancel-reservation', [UserReservationController::class, 'cancel']);
    Route::post('/request-rebooking', [UserReservationController::class, 'requestRebooking']);
    Route::get('/my-rebooking-requests', [UserReservationController::class, 'myRebookingRequests']);
    Route::post('/accept-terms', [UserReservationController::class, 'acceptTerms']);

    // Payments
    Route::post('/create-payment-intent', [UserPaymentController::class, 'createPaymentIntent']);
    Route::post('/upload-payment-proof', [UserPaymentController::class, 'uploadPaymentProof']);
    Route::get('/payment-success', [UserPaymentController::class, 'paymentSuccess']);
    Route::get('/payment-failed', [UserPaymentController::class, 'paymentFailed']);

    // Reviews
    Route::get('/reviews', [UserReviewController::class, 'index']);
    Route::post('/submit-review', [UserReviewController::class, 'store']);
    Route::post('/update-review', [UserReviewController::class, 'update']);
    Route::post('/delete-review', [UserReviewController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [UserNotificationController::class, 'index']);
    Route::post('/mark-notifications-read', [UserNotificationController::class, 'markRead']);
    Route::post('/dismiss-notification', [UserNotificationController::class, 'dismiss']);
    Route::post('/clear-notifications', [UserNotificationController::class, 'clear']);
});

// Legacy User Action Aliases
Route::match(['GET', 'POST'], '/user/check_availability.php', [UserReservationController::class, 'checkAvailability']);
Route::match(['GET', 'POST'], '/user/get_unavailable_dates.php', [UserReservationController::class, 'getUnavailableDates']);
Route::post('/user/process_checkout.php', [UserReservationController::class, 'processCheckout']);
Route::post('/user/make_reservation.php', [UserReservationController::class, 'makeReservation']);
Route::get('/user/get_my_reservations.php', [UserReservationController::class, 'myReservations']);
Route::post('/user/cancel_reservation.php', [UserReservationController::class, 'cancel']);
Route::post('/user/request_rebooking.php', [UserReservationController::class, 'requestRebooking']);
Route::get('/user/get_my_rebooking_requests.php', [UserReservationController::class, 'myRebookingRequests']);
Route::post('/user/accept_terms.php', [UserReservationController::class, 'acceptTerms']);

Route::post('/user/create_payment_intent.php', [UserPaymentController::class, 'createPaymentIntent']);
Route::post('/user/create_paymongo_payment.php', [UserPaymentController::class, 'createPaymentIntent']);
Route::get('/user/get_reservations.php', [UserReservationController::class, 'myReservations']);
Route::get('/user/get_session.php', [UserAuthController::class, 'getSession']);
Route::get('/user/get_csrf_token.php', fn() => response()->json(['csrf_token' => csrf_token()]));
Route::post('/user/upload_payment_proof.php', [UserPaymentController::class, 'uploadPaymentProof']);
Route::get('/user/payment_success.php', [UserPaymentController::class, 'paymentSuccess']);
Route::get('/user/payment_failed.php', [UserPaymentController::class, 'paymentFailed']);

Route::get('/user/get_reviews.php', [UserReviewController::class, 'index']);
Route::get('/user/get_public_reviews.php', [UserReviewController::class, 'publicReviews']);
Route::post('/user/submit_review.php', [UserReviewController::class, 'store']);
Route::post('/user/update_review.php', [UserReviewController::class, 'update']);
Route::post('/user/delete_review.php', [UserReviewController::class, 'destroy']);

Route::get('/user/get_notifications.php', [UserNotificationController::class, 'index']);
Route::post('/user/mark_notifications_read.php', [UserNotificationController::class, 'markRead']);
Route::post('/user/dismiss_notification.php', [UserNotificationController::class, 'dismiss']);
Route::post('/user/clear_notifications.php', [UserNotificationController::class, 'clear']);

// Public Reviews Endpoint
Route::get('/reviews/public', [UserReviewController::class, 'publicReviews']);

// Webhook
Route::post('/webhooks/paymongo', [PaymentWebhookController::class, 'handle']);
Route::post('/user/paymongo_webhook.php', [PaymentWebhookController::class, 'handle']);

// =========================================================================
// 3. Admin Operations & Management
// =========================================================================
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'adminLogin']);
    Route::post('/staff-login', [AdminAuthController::class, 'staffLogin']);
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/check-session', [AdminAuthController::class, 'checkSession']);

    Route::get('/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/calendar', [AdminReservationController::class, 'calendar']);
    Route::post('/check-in', [AdminReservationController::class, 'checkIn']);
    Route::post('/check-out', [AdminReservationController::class, 'checkOut']);
    Route::post('/mark-no-show', [AdminReservationController::class, 'markNoShow']);
    Route::post('/verify-payment', [AdminReservationController::class, 'verifyPayment']);
    Route::post('/approve-rebooking', [AdminReservationController::class, 'approveRebooking']);
    Route::get('/rebooking-requests', [AdminReservationController::class, 'rebookingRequests']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users/update', [AdminUserController::class, 'update']);
    Route::post('/users/delete', [AdminUserController::class, 'destroy']);
    Route::post('/users/status', [AdminUserController::class, 'updateStatus']);
    Route::post('/update-profile', [AdminUserController::class, 'updateProfile']);

    Route::get('/staff', [AdminStaffController::class, 'index']);
    Route::post('/staff/create', [AdminStaffController::class, 'store']);
    Route::post('/staff/update', [AdminStaffController::class, 'update']);
    Route::post('/staff/reset-password', [AdminStaffController::class, 'resetPassword']);

    Route::get('/settings', [AdminSettingsController::class, 'index']);
    Route::post('/settings/update', [AdminSettingsController::class, 'update']);
});

// Legacy Admin Aliases
Route::post('/admin/login.php', [AdminAuthController::class, 'adminLogin']);
Route::post('/admin/staff_login.php', [AdminAuthController::class, 'staffLogin']);
Route::post('/admin/logout.php', [AdminAuthController::class, 'logout']);
Route::post('/admin/staff_logout.php', [AdminAuthController::class, 'logout']);
Route::get('/admin/check_session.php', [AdminAuthController::class, 'checkSession']);

Route::get('/admin/get_dashboard_stats.php', [AdminDashboardController::class, 'stats']);
Route::get('/admin/get_calendar_data.php', [AdminReservationController::class, 'calendar']);
Route::get('/admin/get_reservations.php', [StaffReservationController::class, 'getReservations']);
Route::post('/admin/update_reservation_status.php', [StaffReservationController::class, 'handleAction']);
Route::post('/admin/check_in.php', [AdminReservationController::class, 'checkIn']);
Route::post('/admin/check_out.php', [AdminReservationController::class, 'checkOut']);
Route::post('/admin/mark_no_show.php', [AdminReservationController::class, 'markNoShow']);
Route::post('/admin/verify_payment.php', [AdminReservationController::class, 'verifyPayment']);
Route::post('/admin/approve_rebooking.php', [AdminReservationController::class, 'approveRebooking']);
Route::get('/admin/get_rebooking_requests.php', [AdminReservationController::class, 'rebookingRequests']);

Route::get('/admin/get_users.php', [AdminUserController::class, 'index']);
Route::post('/admin/update_user.php', [AdminUserController::class, 'update']);
Route::post('/admin/delete_user.php', [AdminUserController::class, 'destroy']);
Route::post('/admin/update_user_status.php', [AdminUserController::class, 'updateStatus']);
Route::post('/admin/update_admin_profile.php', [AdminUserController::class, 'updateProfile']);

Route::get('/admin/get_staff.php', [AdminStaffController::class, 'index']);
Route::post('/admin/create_staff.php', [AdminStaffController::class, 'store']);
Route::post('/admin/edit_staff.php', [AdminStaffController::class, 'update']);
Route::post('/admin/reset_staff_pw.php', [AdminStaffController::class, 'resetPassword']);

Route::get('/admin/get_settings.php', [AdminSettingsController::class, 'index']);
Route::post('/admin/update_settings.php', [AdminSettingsController::class, 'update']);

// =========================================================================
// 4. Staff Operations Endpoints
// =========================================================================
Route::prefix('staff')->group(function () {
    Route::get('/stats', [StaffDashboardController::class, 'getStats']);
    Route::get('/room-stats', [StaffDashboardController::class, 'getRoomStats']);
    Route::get('/activity', [StaffDashboardController::class, 'getActivity']);

    Route::get('/reservations', [StaffReservationController::class, 'getReservations']);
    Route::post('/reservation-actions', [StaffReservationController::class, 'handleAction']);

    Route::get('/guests', [StaffGuestController::class, 'getGuests']);

    Route::get('/tasks', [StaffTaskController::class, 'getTasks']);
    Route::post('/task-actions', [StaffTaskController::class, 'handleAction']);

    Route::get('/report-data', [StaffReportController::class, 'getReportData']);

    Route::get('/users', [StaffUserController::class, 'getUsers']);
    Route::post('/user-actions', [StaffUserController::class, 'actions']);
    Route::post('/change-password', [StaffUserController::class, 'changePassword']);
});

// Legacy Staff Aliases
Route::get('/admin/staff_get_stats.php', [StaffDashboardController::class, 'getStats']);
Route::get('/admin/staff_get_room_stats.php', [StaffDashboardController::class, 'getRoomStats']);
Route::get('/admin/staff_get_activity.php', [StaffDashboardController::class, 'getActivity']);

Route::get('/admin/staff_get_reservations.php', [StaffReservationController::class, 'getReservations']);
Route::post('/admin/staff_reservation_actions.php', [StaffReservationController::class, 'handleAction']);

Route::get('/admin/staff_get_guests.php', [StaffGuestController::class, 'getGuests']);

Route::get('/admin/staff_get_tasks.php', [StaffTaskController::class, 'getTasks']);
Route::post('/admin/staff_task_actions.php', [StaffTaskController::class, 'handleAction']);

Route::get('/admin/staff_get_report_data.php', [StaffReportController::class, 'getReportData']);

Route::get('/admin/staff_get_users.php', [StaffUserController::class, 'getUsers']);
Route::post('/admin/staff_actions.php', [StaffUserController::class, 'actions']);
Route::post('/admin/staff_change_password.php', [StaffUserController::class, 'changePassword']);
