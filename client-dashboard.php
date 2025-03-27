<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$client_id = $_SESSION['user_id'];

// Function to handle database errors
function handle_db_error($conn, $sql) {
    $_SESSION['error_message'] = "Database error: " . $conn->error . " (Query: " . $sql . ")";
    header("Location: client-dashboard.php");
    exit();
}

// Delete event
if (isset($_POST['delete_event'])) {
    $event_id = $_POST['ID'];

    $sql_delete_expenses = "DELETE FROM expenses WHERE event_id IN (SELECT ID FROM events WHERE client_id = '$client_id' AND ID = '$event_id')";
    if ($conn->query($sql_delete_expenses)) {
        $sql_delete_event = "DELETE FROM events WHERE ID = '$event_id' AND client_id = '$client_id'";
        if ($conn->query($sql_delete_event)) {
            unset($_SESSION['event_id']);
            $_SESSION['success_message'] = "Event deleted successfully.";
        } else {
            handle_db_error($conn, $sql_delete_event);
        }
    } else {
        handle_db_error($conn, $sql_delete_expenses);
    }
    header("Location: client-dashboard.php");
    exit();
}

// Process Event Update
if (isset($_POST['update_event'])) {
    $event_id = $_POST['event_id'];
    $event_name = $_POST['event_name'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $type = $_POST['type'];
    $budget = $_POST['budget'];

    $sql_update_event = "UPDATE events SET event_name = '$event_name', date = '$date', time = '$time', type = '$type', budget = '$budget' WHERE ID = '$event_id' AND client_id = '$client_id'";

    if ($conn->query($sql_update_event)) {
        $_SESSION['success_message'] = "Event updated successfully.";
    } else {
        handle_db_error($conn, $sql_update_event);
    }
    header("Location: client-dashboard.php");
    exit();
}

// Fetch events created by the logged-in client
$sql_events = "SELECT ID, event_name, date, time, type FROM events WHERE client_id = '$client_id' ORDER BY date DESC, time DESC";
$result_events = $conn->query($sql_events);
$clients_events = []; // Initialize as an empty array
if ($result_events && $result_events->num_rows > 0) {
    while ($row = $result_events->fetch_assoc()) {
        $clients_events[] = $row; // Append each row to the array
    }
}

// Fetch expenses grouped by event
$sql_expenses_summary = "SELECT
                                    ev.ID AS event_id,
                                    ev.event_name,
                                    e.expense_id,
                                    e.category,
                                    e.budget_limit
                                FROM expenses e
                                JOIN events ev ON e.event_id = ev.ID
                                WHERE ev.client_id = '$client_id'
                                ORDER BY ev.event_name, e.category";
$result_expenses_summary = $conn->query($sql_expenses_summary);
$expenses_summary = []; // Initialize as an empty array
if ($result_expenses_summary && $result_expenses_summary->num_rows > 0) {
    while ($row = $result_expenses_summary->fetch_assoc()) {
        $event_id = $row['event_id'];
        if (!isset($expenses_summary[$event_id])) {
            $expenses_summary[$event_id] = [
                'event_name' => htmlspecialchars($row['event_name']),
                'expenses' => [] // Initialize the 'expenses' array for each event
            ];
        }
        $expenses_summary[$event_id]['expenses'][] = [
            'expense_id' => $row['expense_id'],
            'category' => htmlspecialchars($row['category']),
            'budget_limit' => htmlspecialchars($row['budget_limit'])
        ];
    }
}

// Fetch the client's events and their expenses for the report
$sql = "SELECT
            ev.event_name,
            ev.date,
            ev.type,
            ev.budget AS event_budget,
            e.category AS expense_category,
            e.budget_limit AS expense_budget_limit,
            te.actual_cost AS expense_actual_cost,
            te.description AS expense_description
        FROM events ev
        LEFT JOIN expenses e ON ev.ID = e.event_id
        LEFT JOIN tracked_expenses te ON e.event_id = te.event_id AND e.category = te.category
        WHERE ev.client_id = '$client_id'
        ORDER BY ev.event_name, e.category";

$result = $conn->query($sql);
$client_report_data = []; // Initialize as an empty array
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $client_report_data[] = $row; // Append each row to the array
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .event-card {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1rem;
        }

        .event-actions a {
            margin-right: 0.5rem;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .expense-table th,
        .expense-table td {
            border: 1px solid #dee2e6;
            padding: 0.5rem;
            text-align: left;
        }

        .expense-table th {
            background-color: #f8f9fa;
        }

        .variance.positive {
            color: green;
        }

        .variance.negative {
            color: red;
        }

        .logout {
            background-color: rgb(239, 20, 4);
            color: white;
            margin-left: 1100px;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
        }

        .logout:hover {
            background-color: darkred;
        }
        header {
    background-color: #0b315a;
    color: white;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 10px;
}
nav{
    padding-top: -190px;
    margin-bottom: 60px;
}
header{
    height: 100px;
}

nav ul {
    list-style: none;
    display: flex;
}

nav ul li {
    margin-left: 20px;
}

nav ul li a {
    color: white;
    text-decoration: none;
}
header {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr; /* Adjust grid columns as needed */
    align-items: center;
}
nav{
   margin-right: 30px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    header {
        grid-template-columns: 1fr; /* Stack elements on smaller screens */
        text-align: center;
    }
}

.logo{
    margin-top: -20px;
}
    </style>
</head>

<body>
<header>
        <div class="logo">
            <img src="aj - Copy-Photoroom.png" class="logo">
        </div>
    
    
        <nav>
            <ul>
                <li><a href="event.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="service.php">Services</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="getStarted.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <div class="container mt-4">
        <h2 class="mb-4">Client Dashboard</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $_SESSION['success_message']; ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $_SESSION['error_message']; ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <section class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Your Events</h3>
                <a href="create-event.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Event
                </a>
            </div>

            <div class="row">
                <?php if (!empty($clients_events)): ?>
                    <?php foreach ($clients_events as $event): ?>
                        <div class="col-md-6">
                            <div class="card event-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                                    <p class="card-text">
                                        <i class="fas fa-calendar-alt"></i> Date:
                                        <?php echo htmlspecialchars($event['date']); ?><br>
                                        <i class="fas fa-clock"></i> Time:
                                        <?php echo htmlspecialchars($event['time']); ?><br>
                                        <i class="fas fa-tags"></i> Type:
                                        <?php echo htmlspecialchars($event['type']); ?>
                                    </p>
                                    <div class="event-actions">
                                        <a href="expenses.php?event_id=<?php echo $event['ID']; ?>"
                                            class="btn btn-sm btn-info">
                                            <i class="fas fa-money-bill-wave"></i> Budget
                                        </a>
                                        <a href="?edit_event=<?php echo $event['ID']; ?>"
                                            class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="ID" value="<?php echo $event['ID']; ?>">
                                            <button type="submit" name="delete_event"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this event? This will also delete all associated expenses.');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted">You haven't created any events yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (isset($_GET['edit_event'])): ?>
            <?php
            $edit_event_id = $_GET['edit_event'];
            $sql_edit_event = "SELECT ID, event_name, date, time, type, budget FROM events WHERE ID = '$edit_event_id' AND client_id = '$client_id'";
            $result_edit_event = $conn->query($sql_edit_event);
            if ($result_edit_event && $result_edit_event->num_rows > 0) {
                $event_to_edit = $result_edit_event->fetch_assoc();
                ?>
                <section class="mb-4">
                    <h3>Edit Event Details</h3>
                    <form method="post">
                        <input type="hidden" name="event_id" value="<?php echo $event_to_edit['ID']; ?>">
                        <div class="mb-3">
                            <label for="event_name" class="form-label">Event Name:</label>
                            <input type="text" name="event_name" id="event_name" class="form-control"
                                value="<?php echo htmlspecialchars($event_to_edit['event_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date:</label>
                            <input type="date" name="date" id="date" class="form-control"
                                value="<?php echo htmlspecialchars($event_to_edit['date']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="time" class="form-label">Time:</label>
                            <input type="time" name="time" id="time" class="form-control"
                                value="<?php echo htmlspecialchars($event_to_edit['time']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Type:</label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="Wedding" <?php if ($event_to_edit['type'] == 'Wedding')
                                    echo 'selected'; ?>>Wedding</option>
                                <option value="Birthday" <?php if ($event_to_edit['type'] == 'Birthday')
                                    echo 'selected'; ?>>Birthday</option>
                                <option value="Conference" <?php if ($event_to_edit['type'] == 'Conference')
                                echo 'selected'; ?>>Conference</option>
                                <option value="Other" <?php if ($event_to_edit['type'] == 'Other')
                                    echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="budget" class="form-label">Budget:</label>
                            <input type="number" name="budget" id="budget" class="form-control"
                                value="<?php echo htmlspecialchars($event_to_edit['budget']); ?>" required>
                        </div>
                        <button type="submit" name="update_event" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Event
                        </button>
                    </form>
                </section>
                <?php
            } else {
                echo "<p class='alert alert-warning'>Event not found or you do not have permission to edit it.</p>";
            }
            ?>
        <?php endif; ?>

        <section class="mb-4">
            <h3>Your Expenses Summary</h3>
            <?php if (!empty($expenses_summary)): ?>
                <?php foreach ($expenses_summary as $event_id => $event_data): ?>
                    <h4><?php echo $event_data['event_name']; ?></h4>
                    <?php if (!empty($event_data['expenses'])): ?>
                        <table class="expense-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Budget Limit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($event_data['expenses'] as $expense): ?>
                                    <tr>
                                        <td><?php echo $expense['category']; ?></td>
                                        <td><?php echo $expense['budget_limit']; ?></td>
                                        <td>
                                            <a href="modify-expense.php?expense_id=<?php echo $expense['expense_id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-pencil-alt"></i> Modify
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No expenses added for this event.</p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No expenses recorded for any of your events yet.</p>
            <?php endif; ?>
        </section>

        </section>

        <section class="mb-4">
            <h3>Report</h3>
            <form method="post" action="generate_report.php" target="_blank">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-download"></i> Generate PDF Report
                </button>
            </form>
            <a href="logout.php" class="logout btn btn-danger" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            </section>
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h4>Contact Us</h4>
                    <form class="contact">
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name" id="name">
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" id="email">
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" placeholder="Your Message"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary submit">Send</button>
                    </form>
                </div>
                <div class="col-md-4">
                    <h4>Sitemap</h4>
                    <ul class="list-unstyled">
                        <li><a href="event.html" class="nav-link p-0 text-muted">Home</a></li>
                        <li><a href="feature.html" class="nav-link p-0 text-muted">Features</a></li>
                        <li><a href="service.php" class="nav-link p-0 text-muted">Services</a></li>
                        <li><a href="find_venue.php" class="nav-link p-0 text-muted">Find Venues</a></li>
                        <li><a href="login.php" class="nav-link p-0 text-muted">Login</a></li>
                        <li><a href="getStarted.php" class="nav-link p-0 text-muted">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h4>Company Info</h4>
                    <div class="logo mb-2">
                        <img src="aj - Copy-Photoroom.png" height="40">
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-phone text-muted me-2"></i>
                        <p class="mb-0 text-muted">672073759</p>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-envelope text-muted me-2"></i>
                        <p class="mb-0 text-muted">AJEvenemential@gmail.com</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <div class="bg-light text-center py-3">
        <p class="mb-0 text-muted">&copy; 2025 Event Management System. All rights reserved. AJ EVENEMENTIAL</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// Close the connection at the very end of the PHP script
$conn->close();
?>