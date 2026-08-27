<?php

session_start();

require_once "config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {

        $sql = "
            SELECT 
                users.id,
                users.first_name,
                users.last_name,
                users.email,
                users.profile_image,
                users.dark_mode,
                users.password,
                users.status,
                roles.name AS role
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            WHERE users.email = :email
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "email" => $email
        ]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {

            if ($user["status"] !== "Active") {
                $error = "Your account is currently inactive.";
            } else {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["first_name"] = $user["first_name"];
                $_SESSION["last_name"] = $user["last_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["profile_image"] = $user["profile_image"];
                $_SESSION["dark_mode"] = (bool) $user["dark_mode"];
                $_SESSION["role"] = $user["role"];

                switch ($user["role"]) {

                    case "Admin":
                        header("Location: admin/dashboard.php");
                        break;

                    case "Technician":
                        header("Location: technician/dashboard.php");
                        break;

                    case "Employee":
                        header("Location: employee/dashboard.php");
                        break;

                    default:
                        header("Location: index.php");
                        break;
                }

                exit;
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

    <title>Login | HelpSync</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body class="login-page">

    <div class="login-shell">

        <div class="login-brand">

            <div class="brand-badge"><img src="assets/images/logo.png" alt="HelpSync logo"></div>

            <div>
                <h1>HelpSync</h1>
                <p>
                    Smart support for your team. Manage IT issues,
                    resolve tickets faster, and keep operations running smoothly.
                </p>
            </div>

            <div class="login-features">

                <div class="login-feature">
                    <div class="login-feature-icon">✓</div>
                    <div>
                        <strong>Fast</strong>
                        <span>Ticket flow</span>
                    </div>
                </div>

                <div class="login-feature">
                    <div class="login-feature-icon">⚡</div>
                    <div>
                        <strong>Secure</strong>
                        <span>Role access</span>
                    </div>
                </div>

                <div class="login-feature">
                    <div class="login-feature-icon">📊</div>
                    <div>
                        <strong>Track</strong>
                        <span>Incident status</span>
                    </div>
                </div>

                <div class="login-feature">
                    <div class="login-feature-icon">🔧</div>
                    <div>
                        <strong>Support</strong>
                        <span>IT requests</span>
                    </div>
                </div>

            </div>

        </div>

        <div class="login-panel">

            <div class="login-card">

                <div class="login-header">
                    <h1>Welcome back</h1>
                    <p>Sign in to your HelpSync account</p>
                </div>

                <?php if (!empty($error)): ?>

                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>

                <form class="login-form" method="POST" action="">

                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="login-button">
                        Sign In
                    </button>
                </form>

                <div class="login-meta">
                    <span>Need help?</span>
                    <a href="mailto:support@helpdesk.local?subject=HelpSync%20Support%20Request">Contact IT support</a>
                </div>

            </div>

        </div>

    </div>

</body>

</html>