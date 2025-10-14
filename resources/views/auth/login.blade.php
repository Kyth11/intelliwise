<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/png" href="{{ asset('images/intelliwise.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IGCA - Login </title>
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
        .contact-info {
            margin-top: 20px;
            text-align: left;
        }
        .contact-info p {
            margin: 8px 0;
            font-size: 0.80rem;
            display: flex;
            align-items: center;

        }
        .contact-info i {
            font-size: .9rem;
            margin-right: 10px;
            color: #c0d9ff; /* Bootstrap primary blue */
        }
        .contact-info a {
            color: inherit;
            text-decoration: none;
        }
        .contact-info a:hover {
            text-decoration: underline darkkhaki;
        }
    </style>
</head>

<body>
    <div class="auth-wrapper">
        <!-- Left side -->
        <div class="auth-left">
            <img src="{{ asset('images/Intelliwise.png') }}" alt="Logo" style="width: 200px;">
            <h3>INTELLIWISE GRACE CHRISTIAN ACADEMY Inc.</h3>
            <p style="font-style: italic">Login with your credentials to access your account.</p>

         <!-- Contact Information -->
<div class="contact-info">
    <p>
        <a href="https://www.google.com/maps/place/Opol+Grace+Christian+School/@8.5225281,124.5619181,15z/data=!4m6!3m5!1s0x32fff45c930f3c53:0x6d5ca5433afe5147!8m2!3d8.5225476!4d124.5706889!16s%2Fg%2F11c5xvtmx0?entry=ttu&g_ep=EgoyMDI1MDkyMS4wIKXMDSoASAFQAw%3D%3D" target="_blank">
            <i class="bi bi-geo-alt-fill"></i>
            Zone 3, Bonbon, Opol, 9000 Misamis Oriental
        </a>
    </p>
    <p>
        <a href="tel:+639161808738">
            <i class="bi bi-telephone-fill"></i>
            +63 916 180 8738
        </a>
    </p>
    <p>
        <a href="mailto:intelliwiseacademy2020@gmail.com">
            <i class="bi bi-envelope-fill"></i>
            intelliwiseacademy2020@gmail.com
        </a>
    </p>
    <p>
        <a href="https://www.facebook.com/IntelliwiseGrace2020" target="_blank">
            <i class="bi bi-facebook"></i>
            Intelliwise Grace Christian Academy
        </a>
    </p>
</div>

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
