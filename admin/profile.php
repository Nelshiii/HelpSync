<?php

require_once "../includes/auth.php";
requireRole("Admin");

header("Location: ../profile.php");
exit;

require_once "../config/database.php";

$pageTitle = "Profile";
$sidebarDashboardPath = "dashboard.php";
$sidebarTicketsPath = "../tickets.php";
$sidebarProfilePath = "profile.php";
$activeNav = "profile";
$error = "";
$success = "";

$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = :id LIMIT 1");
$stmt->execute(["id" => $_SESSION["user_id"]]);
$admin = $stmt->fetch();

if (!$admin) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $newPassword = $_POST["new_password"] ?? "";

    if ($firstName === "" || $lastName === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid first name, last name, and email address.";
    } elseif ($newPassword !== "" && strlen($newPassword) < 6) {
        $error = "The new password must be at least 6 characters.";
    } else {
        $emailStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1");
        $emailStmt->execute(["email" => $email, "id" => $_SESSION["user_id"]]);

        if ($emailStmt->fetch()) {
            $error = "That email address is already in use.";
        } else {
            $updateSql = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email";
            $parameters = [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "email" => $email,
                "id" => $_SESSION["user_id"]
            ];

            if ($newPassword !== "") {
                $updateSql .= ", password = :password";
                $parameters["password"] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $updateSql .= " WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($parameters);

            $_SESSION["first_name"] = $firstName;
            $_SESSION["last_name"] = $lastName;
            $_SESSION["email"] = $email;
            $admin["first_name"] = $firstName;
            $admin["last_name"] = $lastName;
            $admin["email"] = $email;
            $success = "Your profile was updated successfully.";
        }
    }
}

$assetPath = "../assets";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-title">
            <h1>Admin Profile</h1>
            <p>Customize your account information</p>
        </div>
    </header>

    <section class="dashboard-content">
        <div class="page-header">
            <div>
                <h2>Profile Settings</h2>
                <p>Update the details used across your HelpSync account.</p>
            </div>
        </div>

        <?php if ($success !== ""): ?><div class="success-message"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error !== ""): ?><div class="error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="form-card">
            <form method="post">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <p class="form-section-description">These details identify you in the admin workspace.</p>
                </div>

                <div class="form-grid">
                    <div class="form-group"><label for="first_name">First name</label><input id="first_name" name="first_name" type="text" value="<?php echo htmlspecialchars($admin["first_name"]); ?>" required></div>
                    <div class="form-group"><label for="last_name">Last name</label><input id="last_name" name="last_name" type="text" value="<?php echo htmlspecialchars($admin["last_name"]); ?>" required></div>
                </div>

                <div class="form-group"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?php echo htmlspecialchars($admin["email"]); ?>" required></div>

                <div class="form-section">
                    <h3>Change Password</h3>
                    <p class="form-section-description">Leave blank to keep your current password.</p>
                </div>

                <div class="form-group"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="6" autocomplete="new-password"></div>

                <div class="form-actions"><button type="submit" class="primary-button">Save Profile</button></div>
            </form>
        </div>
    </section>
</main>

<?php require_once "../includes/footer.php"; ?>