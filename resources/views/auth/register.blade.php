<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | MOSHA AI</title>

    @vite(['resources/css/app.css'])
</head>
<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-header">
            <h1>MOSHA AI</h1>
            <p>Create your account</p>
        </div>

        <form method="POST" action="{{ route('register') }}">

            @csrf

            <div class="input-group">

                <label for="name">
                    First Name
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                >

                @error('name')
                    <small class="error">{{ $message }}</small>
                @enderror

            </div>

            <div class="input-group">

                <label for="last_name">
                    Last Name
                </label>

                <input
                    id="last_name"
                    type="text"
                    name="last_name"
                    value="{{ old('last_name') }}"
                    required
                >

                @error('last_name')
                    <small class="error">{{ $message }}</small>
                @enderror

            </div>

            <div class="input-group">

                <label for="email">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                >

                @error('email')
                    <small class="error">{{ $message }}</small>
                @enderror

            </div>

            <div class="input-group">

                <label for="password">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                >

                @error('password')
                    <small class="error">{{ $message }}</small>
                @enderror

            </div>

            <div class="input-group">

                <label for="password_confirmation">
                    Confirm Password
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                >

                @error('password_confirmation')
                    <small class="error">{{ $message }}</small>
                @enderror

            </div>

            <button
                class="auth-btn"
                type="submit">
                Create Account
            </button>

            <div class="auth-links">

                <span>
                    Already have an account?
                </span>

                <a href="{{ route('login') }}">
                    Login
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>