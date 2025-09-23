<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MyApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at center, white 0%, white 20%, rgb(120, 120, 248) 80%, rgb(125, 125, 255) 100%);
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            font-family: Arial, sans-serif;
        }
        .auth-wrapper {
            display: flex;
            width: 900px;
            max-width: 95%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #a5b3cb, #2a5298);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
        }
        .auth-left img {
            width: 100px;
            margin-bottom: 20px;
        }
        .auth-left h3 {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .auth-left p {
            font-size: 14px;
            opacity: 0.9;
        }
        .auth-right {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .btn-custom {
            border-radius: 8px;
        }
        .form-floating label {
            color: #6c757d;
        }
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <!-- Left side -->
        <div class="auth-left">
            <img src="{{ asset('images/Intelliwise.png') }}" alt="Logo">
            <h3>INTELLIWISE GRACE CHRISTIAN ACADEMY Inc.</h3>
            <p>Create your account to get started.</p>
        </div>

        <!-- Right side -->
        <div class="auth-right">
            <h4 class="mb-4">Create an Account</h4>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST">
                @csrf

                <!-- Select Role -->
                <div class="form-floating mb-3">
                    <select name="role" class="form-select" id="role" required>
                        <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select Role</option>
                        <option value="guardian" {{ old('role') == 'guardian' ? 'selected' : '' }}>Guardian</option>
                        <option value="faculty" {{ old('role') == 'faculty' ? 'selected' : '' }}>Faculty</option>
                    </select>
                    <label for="role">Register As</label>
                </div>

                <!-- Floating Email -->
                <div class="form-floating mb-3">
                    <input type="username" name="username" class="form-control" id="username" placeholder="Username" required value="{{ old('email') }}">
                    <label for="username">Username</label>
                </div>

                <!-- Floating Password with toggle -->
                <div class="form-floating mb-3 password-wrapper">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>

                <!-- Floating Confirm Password -->
                <div class="form-floating mb-3">
                    <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" placeholder="Confirm Password" required>
                    <label for="password_confirmation">Confirm Password</label>
                </div>

                <button type="submit" class="btn btn-success w-100 btn-custom">Register</button>
            </form>

            <div class="text-center mt-3">
                <small>Already have an account?</small>
                <a href="{{ route('login') }}" class="btn btn-link">Login</a>
            </div>
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
