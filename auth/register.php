<?php

session_start();

require_once "../config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $phone = trim($_POST['phone'] ?? "");
    $password = $_POST['password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | Check Existing Email
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $check->bind_param("s", $email);

        $check->execute();

        $existing = $check->get_result();


        if ($existing->num_rows > 0) {

            $error = "An account with this email already exists.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | Create Customer Account
            |--------------------------------------------------------------------------
            */

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $role = "customer";

            $status = 1;


            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    name,
                    email,
                    password,
                    phone,
                    role,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");


            $stmt->bind_param(
                "sssssi",
                $name,
                $email,
                $hashed_password,
                $phone,
                $role,
                $status
            );


            if ($stmt->execute()) {

                $success =
                    "Account created successfully. You can now sign in.";

            } else {

                $error =
                    "Unable to create your account. Please try again.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Create Account | Tour & Travel</title>

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


/* Banner */

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

    font-size: 30px;

    line-height: 1.3;

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

    padding: 45px;

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

    margin-bottom: 25px;
}


/* Messages */

.error-message {

    background: #fee2e2;

    border: 1px solid #fecaca;

    color: #991b1b;

    padding: 11px;

    border-radius: 7px;

    font-size: 13px;

    margin-bottom: 18px;
}

.success-message {

    background: #dcfce7;

    border: 1px solid #bbf7d0;

    color: #166534;

    padding: 11px;

    border-radius: 7px;

    font-size: 13px;

    margin-bottom: 18px;
}


/* Form */

.form-group {

    margin-bottom: 15px;
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

    padding: 11px 12px;

    border: 1px solid #d1d5db;

    border-radius: 7px;

    font-size: 14px;

    outline: none;
}

.form-group input:focus {

    border-color: #2563eb;
}


/* Button */

.register-button {

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

.register-button:hover {

    background: #1d4ed8;
}


/* Login */

.login-link {

    text-align: center;

    margin-top: 20px;

    font-size: 13px;

    color: #6b7280;
}

.login-link a {

    color: #2563eb;

    text-decoration: none;

    font-weight: bold;
}

.login-link a:hover {

    text-decoration: underline;
}


/* Home */

.home-link {

    text-align: center;

    margin-top: 14px;

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

        padding: 35px 30px;
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
            Start Your Journey
        </h1>


        <p>
            Create your account and discover destinations,
            tour packages, and easy booking options.
        </p>


        <div class="features">

            <div class="feature">
                ✓ Browse available tour packages
            </div>

            <div class="feature">
                ✓ Make and manage bookings
            </div>

            <div class="feature">
                ✓ View your payment history
            </div>

        </div>

    </div>


    <!-- Register Form -->

    <div class="auth-form">

        <h2>
            Create Account
        </h2>

        <div class="subtitle">
            Register as a customer to start booking tours.
        </div>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($success)): ?>

            <div class="success-message">

                <?php echo htmlspecialchars($success); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="Enter your phone number"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
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
                    placeholder="Minimum 6 characters"
                    required
                >

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter your password"
                    required
                >

            </div>


            <button
                type="submit"
                class="register-button"
            >
                Create Account
            </button>


        </form>


        <div class="login-link">

            Already have an account?

            <a href="login.php">
                Sign In
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