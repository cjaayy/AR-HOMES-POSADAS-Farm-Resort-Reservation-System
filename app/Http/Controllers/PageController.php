<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Public Landing / Login Page
     */
    public function index()
    {
        if (session('user_logged_in') === true) {
            return redirect()->route('user.dashboard');
        }

        if (session('admin_logged_in') === true) {
            $role = strtolower(session('admin_role', ''));
            if ($role === 'staff') {
                return redirect()->route('staff.dashboard');
            }
            return redirect()->route('admin.dashboard');
        }

        if (session('staff_logged_in') === true) {
            return redirect()->route('staff.dashboard');
        }

        return view('pages.index');
    }

    /**
     * Registration Page
     */
    public function registration()
    {
        if (session('user_logged_in') === true) {
            return redirect()->route('user.dashboard');
        }

        return view('pages.registration');
    }

    /**
     * Password Reset Page
     */
    public function resetPassword(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        return view('pages.reset_password', compact('token', 'email'));
    }

    /**
     * User / Guest Dashboard
     */
    public function userDashboard()
    {
        if (!session('user_logged_in') || !session('user_id')) {
            return redirect()->route('home');
        }

        return view('user.dashboard');
    }

    /**
     * Admin Dashboard
     */
    public function adminDashboard()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('home');
        }

        $role = strtolower(session('admin_role', ''));
        if ($role === 'staff') {
            return redirect()->route('staff.dashboard');
        }

        return view('admin.dashboard');
    }

    /**
     * Staff Dashboard
     */
    public function staffDashboard()
    {
        if (!session('staff_logged_in') && !(session('admin_logged_in') && strtolower(session('admin_role', '')) === 'staff')) {
            return redirect()->route('home');
        }

        return view('staff.dashboard');
    }
}
