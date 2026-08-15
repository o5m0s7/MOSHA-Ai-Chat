<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MOSHA AI</title>

    @vite(['resources/css/app.css'])
</head>
<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-header">
            <h1>MOSHA AI</h1>
            <p>Welcome back!</p>
        </div>

        @if (session('status'))
            <div class="auth-success">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">

            @csrf

            <div class="input-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >

                @error('email')
                    <small class="error">
                        {{ $message }}
                    </small>
                @enderror

            </div>

            <div class="input-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >

                @error('password')
                    <small class="error">
                        {{ $message }}
                    </small>
                @enderror

            </div>

            <div class="remember-box">

                <label>

                    <input
                        type="checkbox"
                        name="remember"
                    >

                    Remember me

                </label>

            </div>

            <button
                type="submit"
                class="auth-btn"
            >
                Log In
            </button>

            <div class="auth-links">

                <a href="{{ route('register') }}">
                    Create an account
                </a>

                @if(Route::has('password.request'))

                    <a href="{{ route('password.request') }}">
                        Forgot password?
                    </a>

                @endif

            </div>

        </form>

    </div>

</div>

</body>
</html>