<?php

require_once "includes/auth.php";
requireLogin();

require_once "config/database.php";

$role = $_SESSION["role"];
$rolePath = strtolower($role);
$pageTitle = "Profile";
$assetPath = "assets";
$sidebarDashboardPath = $rolePath . "/dashboard.php";
$sidebarTicketsPath = "tickets.php";
$sidebarProfilePath = "profile.php";
$sidebarSettingsPath = "includes/coming_soon.php?section=Settings";
$sidebarLogoutPath = "logout.php";
$activeNav = "profile";
$error = "";
$success = "";

$stmt = $pdo->prepare("SELECT first_name, last_name, email, profile_image FROM users WHERE id = :id LIMIT 1");
$stmt->execute(["id" => $_SESSION["user_id"]]);
$profile = $stmt->fetch();

if (!$profile) {
    header("Location: {$rolePath}/dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $newPassword = $_POST["new_password"] ?? "";
    $profileImage = $profile["profile_image"];

    if ($firstName === "" || $lastName === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid first name, last name, and email address.";
    } elseif ($newPassword !== "" && strlen($newPassword) < 6) {
        $error = "The new password must be at least 6 characters.";
    } elseif (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] !== UPLOAD_ERR_NO_FILE) {
        $image = $_FILES["profile_image"];
        $imageInfo = $image["error"] === UPLOAD_ERR_OK ? getimagesize($image["tmp_name"]) : false;

        if ($image["error"] !== UPLOAD_ERR_OK || !$imageInfo || $image["size"] > 2 * 1024 * 1024) {
            $error = "Choose a valid image smaller than 2 MB.";
        } else {
            $uploadDirectory = __DIR__ . "/assets/uploads/profiles";
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            $extension = image_type_to_extension($imageInfo[2], false);
            $filename = "user-" . (int) $_SESSION["user_id"] . "-" . bin2hex(random_bytes(8)) . "." . $extension;
            if (!move_uploaded_file($image["tmp_name"], $uploadDirectory . "/" . $filename)) {
                $error = "The profile image could not be saved.";
            } else {
                $profileImage = "uploads/profiles/" . $filename;
            }
        }
    }

    if ($error === "") {
        $emailStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1");
        $emailStmt->execute(["email" => $email, "id" => $_SESSION["user_id"]]);

        if ($emailStmt->fetch()) {
            $error = "That email address is already in use.";
        } else {
            $updateSql = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, profile_image = :profile_image";
            $parameters = [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "email" => $email,
                "profile_image" => $profileImage,
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
            $_SESSION["profile_image"] = $profileImage;
            $profile["first_name"] = $firstName;
            $profile["last_name"] = $lastName;
            $profile["email"] = $email;
            $profile["profile_image"] = $profileImage;
            $success = "Your profile was updated successfully.";
        }
    }
}

require_once "includes/header.php";
require_once "includes/sidebar.php";

?>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-title">
            <h1>My Profile</h1>
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
            <form method="post" enctype="multipart/form-data">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <p class="form-section-description">These details identify you in the HelpSync workspace.</p>
                </div>

                <div class="profile-photo-field">
                    <?php if (!empty($profile["profile_image"])): ?><img class="profile-photo" src="assets/<?php echo htmlspecialchars($profile["profile_image"]); ?>" alt="Profile picture"><?php else: ?><div class="profile-photo profile-photo-placeholder"><?php echo strtoupper(substr($profile["first_name"], 0, 1)); ?></div><?php endif; ?>
                    <div class="form-group"><label for="profile_image">Profile picture</label><input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp"><small class="input-help">JPG, PNG, GIF, or WebP. Maximum 2 MB.</small></div>
                </div>

                <div class="form-grid">
                    <div class="form-group"><label for="first_name">First name</label><input id="first_name" name="first_name" type="text" value="<?php echo htmlspecialchars($profile["first_name"]); ?>" required></div>
                    <div class="form-group"><label for="last_name">Last name</label><input id="last_name" name="last_name" type="text" value="<?php echo htmlspecialchars($profile["last_name"]); ?>" required></div>
                </div>

                <div class="form-group"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?php echo htmlspecialchars($profile["email"]); ?>" required></div>

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

<?php require_once "includes/footer.php"; ?>