<nav class="navbar">
    <div class="nav-container">
        <a href="{{ url('/') }}" class="brand-logo">
            <img src="{{ asset('images/logo/ar-homes-logo.png') }}" alt="AR Homes Logo" />
            <span>AR Homes Posadas</span>
        </a>
        <div class="nav-links">
            <a href="{{ url('/') }}"><i class="fas fa-home"></i> Home</a>
            @if(session('user_logged_in'))
                <a href="{{ url('/dashboard') }}"><i class="fas fa-columns"></i> Dashboard</a>
                <a href="javascript:void(0)" onclick="logoutUser()"><i class="fas fa-sign-out-alt"></i> Logout</a>
            @else
                <a href="{{ url('/registration') }}"><i class="fas fa-user-plus"></i> Register</a>
                <a href="{{ url('/') }}#login-section"><i class="fas fa-sign-in-alt"></i> Login</a>
            @endif
        </div>
    </div>
</nav>
