<?php

require_once "../includes/auth.php";
requireRole("Employee");

require_once "../config/database.php";

$pageTitle = "Create Ticket";
$sidebarDashboardPath = "dashboard.php";
$sidebarTicketsPath = "../tickets.php";
$activeNav = "tickets";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $subject = trim($_POST["subject"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $priority = $_POST["priority"] ?? "";
    $description = trim($_POST["description"] ?? "");

    if (
        empty($subject) ||
        empty($category_id) ||
        empty($priority) ||
        empty($description)
    ) {

        $error = "Please complete all required fields.";

    } else {

        try {

            /*
             * Get the latest ticket number.
             */
            $stmt = $pdo->query("
                SELECT ticket_number
                FROM tickets
                ORDER BY id DESC
                LIMIT 1
            ");

            $lastTicket = $stmt->fetch();

            if ($lastTicket) {

                $lastNumber = (int) str_replace(
                    "HD-",
                    "",
                    $lastTicket["ticket_number"]
                );

                $newNumber = $lastNumber + 1;

            } else {

                $newNumber = 1;

            }

            $ticketNumber = "HD-" . str_pad(
                $newNumber,
                6,
                "0",
                STR_PAD_LEFT
            );

            $technicianStmt = $pdo->query("
                SELECT users.id
                FROM users
                INNER JOIN roles ON roles.id = users.role_id
                WHERE roles.name = 'Technician'
                AND users.status = 'Active'
                ORDER BY users.id ASC
                LIMIT 1
            ");

            $assignedTo = $technicianStmt->fetchColumn() ?: null;

            /*
             * Insert the ticket.
             */
            $stmt = $pdo->prepare("
                INSERT INTO tickets
                (
                    ticket_number,
                    subject,
                    description,
                    category_id,
                    priority,
                    status,
                    created_by,
                    assigned_to
                )
                VALUES
                (
                    :ticket_number,
                    :subject,
                    :description,
                    :category_id,
                    :priority,
                    'Open',
                    :created_by,
                    :assigned_to
                )
            ");

            $stmt->execute([
                "ticket_number" => $ticketNumber,
                "subject" => $subject,
                "description" => $description,
                "category_id" => $category_id,
                "priority" => $priority,
                "created_by" => $_SESSION["user_id"],
                "assigned_to" => $assignedTo
            ]);

            $ticketId = $pdo->lastInsertId();
            $historyStmt = $pdo->prepare("INSERT INTO ticket_updates (ticket_id, user_id, action, new_status) VALUES (:ticket_id, :user_id, 'Ticket created', 'Open')");
            $historyStmt->execute(["ticket_id" => $ticketId, "user_id" => $_SESSION["user_id"]]);

            $success = "Ticket {$ticketNumber} was created successfully.";

            /*
             * Clear form values after successful submission.
             */
            $subject = "";
            $description = "";

        } catch (PDOException $e) {

            $error = "Something went wrong while creating the ticket.";

        }
    }
}


/*
 * Get active categories.
 */
$stmt = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE status = 'Active'
    ORDER BY name ASC
");

$categories = $stmt->fetchAll();

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="main-content">

    <!-- Top Bar -->
    <header class="topbar">

        <div class="topbar-title">

            <h1>Create Ticket</h1>

            <p>
                Submit a new IT support request
            </p>

        </div>

        <div class="topbar-actions">

            <div class="user-menu">

                <div class="user-avatar">
                    <?php echo strtoupper(
                        substr($_SESSION["first_name"], 0, 1)
                    ); ?>
                </div>

                <div class="user-info">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["first_name"] . " " .
                            $_SESSION["last_name"]
                        );
                        ?>
                    </strong>

                    <span>
                        Employee
                    </span>

                </div>

            </div>

        </div>

    </header>


    <!-- Page Content -->
    <section class="dashboard-content">

        <div class="page-header">

            <div>
                <h2>Submit a Support Request</h2>

                <p>
                    Tell us what you need help with and our IT team
                    will assist you.
                </p>
            </div>

            <a
                href="dashboard.php"
                class="secondary-button"
            >
                ← Back to Dashboard
            </a>

        </div>


        <?php if (!empty($success)): ?>

            <div class="success-message">

                ✓
                <?php echo htmlspecialchars($success); ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <div class="form-card">

            <form method="POST" action="">

                <div class="form-section">

                    <h3>Ticket Information</h3>

                    <p class="form-section-description">
                        Provide details about the issue you're experiencing.
                    </p>

                </div>


                <div class="form-group">

                    <label for="subject">
                        Subject <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        value="<?php echo htmlspecialchars($subject ?? ""); ?>"
                        placeholder="e.g. Unable to connect to Wi-Fi"
                        maxlength="255"
                        required
                    >

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="category">
                            Category <span class="required">*</span>
                        </label>

                        <select
                            id="category"
                            name="category_id"
                            required
                        >

                            <option value="">
                                Select a category
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?php echo $category["id"]; ?>"
                                    <?php
                                    if (
                                        isset($category_id) &&
                                        $category_id == $category["id"]
                                    ) {
                                        echo "selected";
                                    }
                                    ?>
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $category["name"]
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="priority">
                            Priority <span class="required">*</span>
                        </label>

                        <select
                            id="priority"
                            name="priority"
                            required
                        >

                            <option value="">
                                Select priority
                            </option>

                            <option value="Low">
                                Low
                            </option>

                            <option value="Medium">
                                Medium
                            </option>

                            <option value="High">
                                High
                            </option>

                            <option value="Critical">
                                Critical
                            </option>

                        </select>

                    </div>

                </div>


                <div class="form-group">

                    <label for="description">
                        Description <span class="required">*</span>
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="7"
                        placeholder="Please describe the issue in detail..."
                        required
                    ><?php echo htmlspecialchars($description ?? ""); ?></textarea>

                    <small class="input-help">
                        Include any error messages, what you were doing
                        when the problem occurred, and other useful details.
                    </small>

                </div>


                <div class="form-actions">

                    <a
                        href="dashboard.php"
                        class="secondary-button"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Submit Ticket
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>