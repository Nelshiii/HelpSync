<?php

$host = "localhost";
$dbname = "helpdesk_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $columnStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if (!$columnStmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD profile_image VARCHAR(255) NULL AFTER email");
    }

    $themeColumnStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'dark_mode'");
    if (!$themeColumnStmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD dark_mode TINYINT(1) NOT NULL DEFAULT 0 AFTER profile_image");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        old_status VARCHAR(30) NULL,
        new_status VARCHAR(30) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (ticket_id)
    )");

    ensureDefaultRolesAndUsers($pdo);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function ensureDefaultRolesAndUsers(PDO $pdo): void
{
    $roles = ["Admin", "Technician", "Employee"];

    foreach ($roles as $roleName) {
        $stmt = $pdo->prepare(
            "INSERT INTO roles (name) VALUES (:name) 
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );

        $stmt->execute([
            "name" => $roleName
        ]);
    }

    $defaultUsers = [
        [
            "email" => "admin@helpdesk.local",
            "first_name" => "System",
            "last_name" => "Administrator",
            "role" => "Admin",
            "password" => "admin123",
            "status" => "Active"
        ],
        [
            "email" => "technician@helpdesk.local",
            "first_name" => "Support",
            "last_name" => "Technician",
            "role" => "Technician",
            "password" => "tech123",
            "status" => "Active"
        ],
        [
            "email" => "employee@helpdesk.local",
            "first_name" => "Sample",
            "last_name" => "Employee",
            "role" => "Employee",
            "password" => "employee123",
            "status" => "Active"
        ]
    ];

    foreach ($defaultUsers as $user) {
        $roleStmt = $pdo->prepare(
            "SELECT id FROM roles WHERE name = :role LIMIT 1"
        );
        $roleStmt->execute(["role" => $user["role"]]);
        $role = $roleStmt->fetch();

        if (!$role) {
            continue;
        }

        $userStmt = $pdo->prepare(
            "SELECT id, password FROM users WHERE email = :email LIMIT 1"
        );
        $userStmt->execute(["email" => $user["email"]]);
        $existingUser = $userStmt->fetch();

        if ($existingUser) {
            if (!password_verify($user["password"], $existingUser["password"])) {
                $updateStmt = $pdo->prepare(
                    "UPDATE users SET password = :password WHERE id = :id"
                );

                $updateStmt->execute([
                    "password" => password_hash($user["password"], PASSWORD_DEFAULT),
                    "id" => $existingUser["id"]
                ]);
            }
        } else {
            $insertStmt = $pdo->prepare(
                "INSERT INTO users (
                    first_name,
                    last_name,
                    email,
                    password,
                    role_id,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :first_name,
                    :last_name,
                    :email,
                    :password,
                    :role_id,
                    :status,
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                )"
            );

            $insertStmt->execute([
                "first_name" => $user["first_name"],
                "last_name" => $user["last_name"],
                "email" => $user["email"],
                "password" => password_hash($user["password"], PASSWORD_DEFAULT),
                "role_id" => $role["id"],
                "status" => $user["status"]
            ]);
        }
    }

    $categories = [
        "Hardware",
        "Software",
        "Network",
        "Account & Access",
        "Email",
        "Other"
    ];

    foreach ($categories as $categoryName) {
        $categoryStmt = $pdo->prepare(
            "INSERT INTO categories (name, status) VALUES (:name, 'Active') 
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );

        $categoryStmt->execute([
            "name" => $categoryName
        ]);
    }

    $technicianStmt = $pdo->query("
        SELECT users.id
        FROM users
        INNER JOIN roles ON roles.id = users.role_id
        WHERE roles.name = 'Technician'
        AND users.status = 'Active'
        ORDER BY users.id ASC
        LIMIT 1
    ");

    $technicianId = $technicianStmt->fetchColumn();

    if ($technicianId) {
        $assignStmt = $pdo->prepare(
            "UPDATE tickets
             SET assigned_to = :technician_id
             WHERE assigned_to IS NULL"
        );

        $assignStmt->execute([
            "technician_id" => $technicianId
        ]);
    }

    $pdo->exec("INSERT INTO ticket_updates (ticket_id, user_id, action, new_status)
        SELECT t.id, t.created_by, 'Ticket created', t.status
        FROM tickets t
        WHERE NOT EXISTS (
            SELECT 1 FROM ticket_updates tu WHERE tu.ticket_id = t.id
        )");
}