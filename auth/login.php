<?php

session_start();

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare("
            SELECT id, name, email, password, role, status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if ($user['status'] != 1) {

                $error = "Your account is currently inactive.";

            } elseif (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {

                    header("Location: ../admin/dashboard.php");
                    exit();

                } elseif ($user['role'] === 'customer') {

                    header("Location: ../customer/dashboard.php");
                    exit();

                } else {

                    header("Location: ../index.php");
                    exit();
                }

            } else {

                $error = "Invalid email or password.";
            }

        } else {

            $error = "Invalid email or password.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | Tour & Travel</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family: Arial, Helvetica, sans-serif;

    min-height: 100vh;

    background: #f4f7fb;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #111827;
}


/* Main */

.auth-wrapper {

    width: 100%;
    max-width: 950px;

    margin: 30px;

    display: grid;
    grid-template-columns: 1fr 1fr;

    background: white;

    border-radius: 16px;

    overflow: hidden;

    box-shadow: 0 15px 40px rgba(0,0,0,0.08);

}


/* Left Side */

.auth-banner {

    background: #1f2937;

    color: white;

    padding: 55px 45px;

    display: flex;
    flex-direction: column;
    justify-content: center;
}

.logo {

    font-size: 28px;
    font-weight: bold;

    margin-bottom: 35px;
}

.logo span {

    display: block;

    font-size: 12px;

    font-weight: normal;

    color: #cbd5e1;

    margin-top: 5px;
}

.auth-banner h1 {

    font-size: 32px;

    line-height: 1.25;

    margin-bottom: 15px;
}

.auth-banner p {

    color: #d1d5db;

    font-size: 14px;

    line-height: 1.7;

    max-width: 350px;
}

.features {

    margin-top: 30px;
}

.feature {

    margin-bottom: 15px;

    font-size: 13px;

    color: #e5e7eb;
}


/* Form */

.auth-form {

    padding: 50px 45px;

    display: flex;
    flex-direction: column;
    justify-content: center;
}

.auth-form h2 {

    font-size: 27px;

    margin-bottom: 8px;
}

.subtitle {

    color: #6b7280;

    font-size: 13px;

    margin-bottom: 28px;
}


/* Error */

.error-message {

    background: #fee2e2;

    border: 1px solid #fecaca;

    color: #991b1b;

    padding: 11px;

    border-radius: 7px;

    font-size: 13px;

    margin-bottom: 18px;
}


/* Form Group */

.form-group {

    margin-bottom: 18px;
}

.form-group label {

    display: block;

    font-size: 13px;

    font-weight: bold;

    color: #374151;

    margin-bottom: 7px;
}

.form-group input {

    width: 100%;

    padding: 12px;

    border: 1px solid #d1d5db;

    border-radius: 7px;

    font-size: 14px;

    outline: none;

    transition: border-color 0.2s;
}

.form-group input:focus {

    border-color: #2563eb;
}


/* Button */

.login-button {

    width: 100%;

    border: none;

    background: #2563eb;

    color: white;

    padding: 13px;

    border-radius: 7px;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;

    margin-top: 5px;
}

.login-button:hover {

    background: #1d4ed8;
}


/* Register */

.register-link {

    text-align: center;

    margin-top: 22px;

    font-size: 13px;

    color: #6b7280;
}

.register-link a {

    color: #2563eb;

    text-decoration: none;

    font-weight: bold;
}

.register-link a:hover {

    text-decoration: underline;
}


/* Home */

.home-link {

    text-align: center;

    margin-top: 15px;

    font-size: 12px;
}

.home-link a {

    color: #6b7280;

    text-decoration: none;
}


/* Responsive */

@media (max-width: 750px) {

    .auth-wrapper {

        grid-template-columns: 1fr;

        max-width: 500px;
    }

    .auth-banner {

        display: none;
    }

    .auth-form {

        padding: 40px 30px;
    }

}

</style>

</head>


<body>


<div class="auth-wrapper">


    <!-- Left Banner -->

    <div class="auth-banner">

        <div class="logo">

            Tour & Travel

            <span>
                Explore • Travel • Enjoy
            </span>

        </div>


        <h1>
            Welcome Back!
        </h1>


        <p>
            Sign in to manage your bookings, explore tour packages,
            and continue your travel journey.
        </p>


        <div class="features">

            <div class="feature">
                ✓ Explore tour packages
            </div>

            <div class="feature">
                ✓ Manage your bookings
            </div>

            <div class="feature">
                ✓ Secure account access
            </div>

        </div>

    </div>


    <!-- Login Form -->

    <div class="auth-form">

        <h2>
            Sign In
        </h2>

        <div class="subtitle">
            Enter your account details to continue.
        </div>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <button
                type="submit"
                class="login-button"
            >
                Sign In
            </button>


        </form>


        <div class="register-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>


        <div class="home-link">

            <a href="../index.php">
                ← Back to Home
            </a>

        </div>

    </div>


</div>


</body>

</html>