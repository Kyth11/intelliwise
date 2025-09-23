<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MyApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        .invalid-icon {
            color: #dc3545; /* Bootstrap danger red */
            font-size: 1.2rem;
            margin-left: 8px;
            align-self: center;
        }
        .input-group-custom {
            display: flex;
            align-items: center;
        }
        .input-group-custom .form-floating {
            flex: 1; /* Input expands full width */
        }
    </style>
</head>

<body>
    <div class="auth-wrapper">
        <!-- Left side -->
        <div class="auth-left">
            <img src="{{ asset('images/Intelliwise.png') }}" alt="Logo">
            <h3>INTELLIWISE GRACE CHRISTIAN ACADEMY Inc.</h3>
            <p>Login with your credentials to access your account.</p>
        </div>

        <!-- Right side -->
        <div class="auth-right">
            <h4 class="mb-4">Login to Your Account</h4>

            {{-- General login error --}}
            @if ($errors->has('login'))
                <div class="alert alert-danger">
                    {{ $errors->first('login') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <!-- Username -->
                <div class="input-group-custom mb-3">
                    <div class="form-floating w-100">
                        <input type="text"
                            name="username"
                            class="form-control"
                            id="username"
                            value="{{ old('username') }}"
                            placeholder="Username"
                            required autofocus>
                        <label for="username">Username</label>
                    </div>

                    {{-- Exclamation icon outside --}}
                    @if ($errors->has('login'))
                        <i class="bi bi-exclamation-circle-fill invalid-icon"></i>
                    @endif
                </div>

                <!-- Password -->
                <div class="input-group-custom mb-3">
                    <div class="form-floating w-100">
                        <input type="password"
                            name="password"
                            class="form-control"
                            id="password"
                            placeholder="Password"
                            required>
                        <label for="password">Password</label>
                        <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
                    </div>

                    {{-- Exclamation icon outside --}}
                    @if ($errors->has('login'))
                        <i class="bi bi-exclamation-circle-fill invalid-icon"></i>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-custom">Login</button>
            </form>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector("#togglePassword");
        const password = document.querySelector("#password");

        togglePassword.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);

            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
        });
    </script>
</body>

</html>
