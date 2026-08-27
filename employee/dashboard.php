<?php

require_once "../includes/auth.php";

requireRole("Employee");

require_once "../config/database.php";

$pageTitle = "Dashboard";
$sidebarDashboardPath = "dashboard.php";
$sidebarTicketsPath = "../tickets.php";

/*
 * Get ticket statistics for the logged-in employee.
 */

$userId = $_SESSION["user_id"];

/* Total tickets */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tickets
    WHERE created_by = :user_id
");

$stmt->execute([
    "user_id" => $userId
]);

$totalTickets = $stmt->fetchColumn();


/* Open tickets */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tickets
    WHERE created_by = :user_id
    AND status = 'Open'
");

$stmt->execute([
    "user_id" => $userId
]);

$openTickets = $stmt->fetchColumn();


/* In Progress tickets */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tickets
    WHERE created_by = :user_id
    AND status = 'In Progress'
");

$stmt->execute([
    "user_id" => $userId
]);

$inProgressTickets = $stmt->fetchColumn();


/* Resolved tickets */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tickets
    WHERE created_by = :user_id
    AND status = 'Resolved'
");

$stmt->execute([
    "user_id" => $userId
]);

$resolvedTickets = $stmt->fetchColumn();


/*
 * Get the employee's most recent tickets.
 */

$stmt = $pdo->prepare("
    SELECT
        tickets.id,
        tickets.ticket_number,
        tickets.subject,
        tickets.description,
        tickets.priority,
        tickets.status,
        tickets.created_at,
        categories.name AS category_name
    FROM tickets
    INNER JOIN categories
        ON tickets.category_id = categories.id
    WHERE tickets.created_by = :user_id
    ORDER BY tickets.created_at DESC
    LIMIT 5
");

$stmt->execute([
    "user_id" => $userId
]);

$recentTickets = $stmt->fetchAll();


require_once "../includes/header.php";

require_once "../includes/sidebar.php";

?>

<main class="main-content">

    <!-- =========================
         TOP BAR
    ========================== -->

    <header class="topbar">

        <button
            class="mobile-menu-button"
            id="mobileMenuButton"
        >
            ☰
        </button>

        <div class="topbar-title">

            <h1>Dashboard</h1>

            <p>
                Overview of your support requests
            </p>

        </div>


        <div class="topbar-actions">

            <button class="notification-button">
                🔔

                <span class="notification-dot"></span>
            </button>


            <div class="user-menu">

                <div class="user-avatar">

                    <?php if (!empty($_SESSION["profile_image"])): ?><img class="profile-avatar-image" src="../assets/<?php echo htmlspecialchars($_SESSION["profile_image"]); ?>" alt="Profile picture"><?php else: ?><?php echo strtoupper(substr($_SESSION["first_name"], 0, 1)); ?><?php endif; ?>

                </div>


                <div class="user-info">

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $_SESSION["first_name"] .
                            " " .
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


    <!-- =========================
         DASHBOARD CONTENT
    ========================== -->

    <section class="dashboard-content">


        <!-- Welcome Section -->

        <div class="welcome-section">

            <div>

                <h2>

                    Welcome back,
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["first_name"]
                    );
                    ?>! 👋

                </h2>

                <p>

                    Need IT assistance?
                    Submit a ticket and our support team
                    will help you.

                </p>

            </div>


            <a
                href="create_ticket.php"
                class="primary-button"
            >
                + Create Ticket
            </a>

        </div>


        <!-- =========================
             STATISTICS
        ========================== -->

        <div class="stats-grid">


            <!-- Total Tickets -->

            <div class="stat-card">

                <div class="stat-icon total">
                    🎫
                </div>


                <div class="stat-info">

                    <span>
                        My Tickets
                    </span>

                    <strong>
                        <?php echo $totalTickets; ?>
                    </strong>

                    <small>
                        Total submitted
                    </small>

                </div>

            </div>


            <!-- Open -->

            <div class="stat-card">

                <div class="stat-icon open">
                    ●
                </div>


                <div class="stat-info">

                    <span>
                        Open
                    </span>

                    <strong>
                        <?php echo $openTickets; ?>
                    </strong>

                    <small>
                        Awaiting support
                    </small>

                </div>

            </div>


            <!-- In Progress -->

            <div class="stat-card">

                <div class="stat-icon progress">
                    ◔
                </div>


                <div class="stat-info">

                    <span>
                        In Progress
                    </span>

                    <strong>
                        <?php echo $inProgressTickets; ?>
                    </strong>

                    <small>
                        Being handled
                    </small>

                </div>

            </div>


            <!-- Resolved -->

            <div class="stat-card">

                <div class="stat-icon resolved">
                    ✓
                </div>


                <div class="stat-info">

                    <span>
                        Resolved
                    </span>

                    <strong>
                        <?php echo $resolvedTickets; ?>
                    </strong>

                    <small>
                        Successfully resolved
                    </small>

                </div>

            </div>

        </div>


        <!-- =========================
             RECENT TICKETS
        ========================== -->

        <div class="dashboard-card recent-tickets">


            <div class="card-header">

                <div>

                    <h3>
                        My Recent Tickets
                    </h3>

                    <p>
                        Your latest support requests
                    </p>

                </div>


                <a href="../tickets.php">
                    View all
                </a>

            </div>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Ticket
                            </th>

                            <th>
                                Subject
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Priority
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Details
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if (empty($recentTickets)): ?>


                            <tr>

                                <td
                                    colspan="7"
                                    style="
                                        text-align: center;
                                        padding: 40px;
                                        color: #9ca3af;
                                    "
                                >

                                    <div
                                        style="
                                            font-size: 30px;
                                            margin-bottom: 10px;
                                        "
                                    >
                                        🎫
                                    </div>

                                    <strong
                                        style="
                                            display: block;
                                            color: #6b7280;
                                            margin-bottom: 5px;
                                        "
                                    >
                                        No tickets yet
                                    </strong>

                                    <span>
                                        Create your first support ticket
                                        to get started.
                                    </span>

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $recentTickets
                                as $ticket
                            ): ?>


                                <tr>


                                    <!-- Ticket Number -->

                                    <td>

                                        <strong>

                                            <a
                                                href="../ticket.php?number=<?php echo urlencode($ticket["ticket_number"]); ?>"
                                                style="
                                                    color: #2563eb;
                                                "
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $ticket["ticket_number"]
                                                );
                                                ?>

                                            </a>

                                        </strong>

                                    </td>

                                    <td><a href="../ticket.php?number=<?php echo urlencode($ticket["ticket_number"]); ?>" class="ticket-details-button">View details</a></td>


                                    <!-- Subject -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $ticket["subject"]
                                        );
                                        ?>

                                    </td>


                                    <!-- Category -->

                                    <td class="ticket-description" title="<?php echo htmlspecialchars($ticket["description"]); ?>">

                                        <?php echo htmlspecialchars($ticket["description"]); ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $ticket["category_name"]
                                        );
                                        ?>

                                    </td>


                                    <!-- Priority -->

                                    <td>

                                        <?php

                                        $priorityClass =
                                            strtolower(
                                                $ticket["priority"]
                                            );

                                        ?>

                                        <span
                                            class="
                                                priority
                                                <?php
                                                echo $priorityClass;
                                                ?>
                                            "
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $ticket["priority"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <?php

                                        $statusClass = "";

                                        switch (
                                            $ticket["status"]
                                        ) {

                                            case "Open":
                                                $statusClass =
                                                    "open-status";
                                                break;

                                            case "In Progress":
                                                $statusClass =
                                                    "progress-status";
                                                break;

                                            case "Resolved":
                                                $statusClass =
                                                    "resolved-status";
                                                break;

                                            case "Pending":
                                                $statusClass =
                                                    "pending-status";
                                                break;

                                            case "Closed":
                                                $statusClass =
                                                    "closed-status";
                                                break;

                                        }

                                        ?>

                                        <span
                                            class="
                                                status
                                                <?php
                                                echo $statusClass;
                                                ?>
                                            "
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $ticket["status"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- Date and time -->

                                    <td>

                                        <?php
                                        echo date(
                                            "M d, Y g:i A",
                                            strtotime(
                                                $ticket["created_at"]
                                            )
                                        );
                                        ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


        <!-- =========================
             QUICK ACTION
        ========================== -->

        <div
            class="dashboard-grid"
            style="margin-top: 20px;"
        >


            <!-- Create Ticket -->

            <div class="dashboard-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Need Help?
                        </h3>

                        <p>
                            Report an IT issue to our support team.
                        </p>

                    </div>

                </div>


                <div
                    style="
                        padding: 20px;
                    "
                >

                    <p
                        style="
                            color: #6b7280;
                            font-size: 13px;
                            line-height: 1.6;
                            margin-bottom: 18px;
                        "
                    >

                        Having trouble with your computer,
                        software, network, email, or account?

                        Create a support ticket and provide
                        as much detail as possible.

                    </p>


                    <a
                        href="create_ticket.php"
                        class="primary-button"
                    >
                        + Submit a Ticket
                    </a>

                </div>

            </div>


            <!-- Ticket Status -->

            <div class="dashboard-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Ticket Status
                        </h3>

                        <p>
                            What the different statuses mean
                        </p>

                    </div>

                </div>


                <div
                    class="ticket-summary"
                >


                    <div class="summary-row">

                        <div class="summary-label">

                            <span
                                class="
                                    summary-dot
                                    open-dot
                                "
                            ></span>

                            Open

                        </div>

                        <span
                            style="
                                color: #6b7280;
                                font-size: 11px;
                            "
                        >
                            Waiting for support
                        </span>

                    </div>


                    <div class="summary-row">

                        <div class="summary-label">

                            <span
                                class="
                                    summary-dot
                                    progress-dot
                                "
                            ></span>

                            In Progress

                        </div>

                        <span
                            style="
                                color: #6b7280;
                                font-size: 11px;
                            "
                        >
                            Technician working
                        </span>

                    </div>


                    <div class="summary-row">

                        <div class="summary-label">

                            <span
                                class="
                                    summary-dot
                                    resolved-dot
                                "
                            ></span>

                            Resolved

                        </div>

                        <span
                            style="
                                color: #6b7280;
                                font-size: 11px;
                            "
                        >
                            Issue resolved
                        </span>

                    </div>


                </div>

            </div>


        </div>


    </section>

</main>


<?php

require_once "../includes/footer.php";

?>