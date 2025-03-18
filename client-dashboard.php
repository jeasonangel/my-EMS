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

// Delete event
if (isset($_POST['delete_event'])) {
    $sql_delete_expenses = "DELETE FROM expenses WHERE event_id IN (SELECT ID FROM events WHERE client_id = '$client_id' AND ID = '" . $_POST['ID'] . "')";
    if ($conn->query($sql_delete_expenses)) {
        $sql_delete_event = "DELETE FROM events WHERE ID = '" . $_POST['ID'] . "' AND client_id = '$client_id'";
        if ($conn->query($sql_delete_event)) {
            unset($_SESSION['event_id']);
            $_SESSION['success_message'] = "Event deleted successfully.";
            header("Location: client-dashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error deleting event: " . $conn->error;
            header("Location: client-dashboard.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Error deleting associated expenses: " . $conn->error;
        header("Location: client-dashboard.php");
        exit();
    }
}

// Fetch events created by the logged-in client
$sql_events = "SELECT ID, event_name, date, time, type FROM events WHERE client_id = '$client_id' ORDER BY date DESC, time DESC";
$result_events = $conn->query($sql_events);
$clients_events = [];
if ($result_events && $result_events->num_rows > 0) {
    while ($row = $result_events->fetch_assoc()) {
        $clients_events[] = $row;
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
$expenses_summary = [];
if ($result_expenses_summary && $result_expenses_summary->num_rows > 0) {
    while ($row = $result_expenses_summary->fetch_assoc()) {
        $event_id = $row['event_id'];
        if (!isset($expenses_summary[$event_id])) {
            $expenses_summary[$event_id] = [
                'event_name' => htmlspecialchars($row['event_name']),
                'expenses' => []
            ];
        }
        $expenses_summary[$event_id]['expenses'][] = [
            'expense_id' => $row['expense_id'],
            'category' => htmlspecialchars($row['category']),
            'budget_limit' => htmlspecialchars($row['budget_limit'])
        ];
    }
}

$client_id = $_SESSION['user_id']; 

// Fetch the client's events and their expenses
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
$client_report_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $client_report_data[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .expense-summary-section {
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .expense-summary-section h3 {
            color: #007bff;
            margin-bottom: 15px;
        }

        .expense-summary-section h4 {
            color: #333;
            margin-top: 15px;
            margin-bottom: 10px;
        }

        .expense-summary-section table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .expense-summary-section th, .expense-summary-section td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .expense-summary-section th {
            background-color: #f9f9f9;
        }

        .modify-expense-btn {
            background-color: #ffc107; 
            color: #333;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.8em;
            margin-right: 5px;
        }

        .modify-expense-btn:hover {
            background-color: #e0a800;
        }
                body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            color: #333;
            }
            header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            }
            .logo img {
            height: 50px; 
            }
            nav ul {
            list-style: none;
            display: flex;
            }
            nav ul li {
            margin-left: 20px;
            }
            nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            }
            nav ul li a:hover {
            color: #007bff;
            }
            .dashboard-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: 1fr;
            grid-gap: 20px;
            }
            /* Event Section */
            .events-section {
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            }
            .events-section h2 {
            color: #007bff;
            margin-bottom: 20px;
            }
            .event-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            grid-gap: 15px;
            }
            .event-item {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            }
            .event-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
            }
            .event-details h4 {
            margin-top: 0;
            color: #555;
            }
            .event-details p {
            margin-bottom: 5px;
            font-size: 0.9em;
            color: #777;
            }
            .event-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            column-gap: 10px;
            } 
            .event-actions a {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            font-size: 0.85em;
            margin-left: 5px;
            transition: background-color 0.2s;
            }
            .event-actions a:hover {
            background-color: #0056b3;
          
            }
            .no-events {
            text-align: center;
            color: #777;
            font-style: italic;
            }
            /* Expenses Section */
            .expenses-section {
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            }
            .expenses-section h3 {
            color: #007bff;
            margin-bottom: 20px;
            }
            table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            }
            th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            }
            th {
            background-color: #f9f9f9;
            }
            /* Buttons */
            button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
            }
            button:hover {
            background-color: #0056b3;
            }
            .btn {
            color: white;
            text-decoration: none;
            }
            /* Footer */
            footer {
            background-color: #fff;
            padding: 30px 20px;
            text-align: center;
            margin-top: 30px;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
            }
            .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            grid-gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            }
            .contact-form h4, .sitemap h4, .company-info h4 {
            color: #007bff;
            margin-bottom: 15px;
            }
            .contact-form input, .contact-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            }
            .sitemap ul {
            list-style: none;
            padding: 0;
            }
            .sitemap ul li {
            margin-bottom: 5px;
            }
            .sitemap ul li a {
            text-decoration: none;
            color: #555;
            }
            .sitemap ul li a:hover {
            color: #007bff;
            }
            .company-info .logo img {
            height: 40px;
            margin-bottom: 10px;
            }
            .company-info .address {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            }
            .company-info .icons {
            height: 20px;
            margin-right: 10px;
            }
            .company-info .info {
            font-size: 0.9em;
            color: #777;
            }
            .copyright {
            background-color: #f9f9f9;
            padding: 15px 20px;
            text-align: center;
            font-size: 0.85em;
            color: #555;
            }
            /* Responsive Design */
            @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr; /* Stack on smaller screens */
            }
            
            nav ul {
                flex-direction: column;
                align-items: center;
            }
            nav ul li {
                margin: 5px 0;
            }
            } 
    </style>
</head>
<body>
<header>
    <div class="logo">
        <img src="EMS.jpg" alt="Event Management System Logo">
    </div>
    <nav>
        <ul>
        <li><a href="event.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="#footer">Contact Us</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
        </ul>
    </nav>
</header>
<div class="dashboard-container">
    <section class="events-section">
        <h2>Welcome to Your Dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <?php if (isset($_SESSION['success_message'])): ?>
            <p style="color: green;"><?php echo $_SESSION['success_message']; ?></p>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <p style="color: red;"><?php echo $_SESSION['error_message']; ?></p>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <h3>Your Created Events:</h3>
        <div class="event-list">
            <?php if (!empty($clients_events)): ?>
                <?php foreach ($clients_events as $event): ?>
                    <div class="event-item">
                        <div class="event-details">
                            <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                            <p>Date: <?php echo htmlspecialchars($event['date']); ?></p>
                            <p>Time: <?php echo htmlspecialchars($event['time']); ?></p>
                            <p>Type: <?php echo htmlspecialchars($event['type']); ?></p>
                        </div>
                        <div class="event-actions">
                            <a href="expenses.php?event_id=<?php echo $event['ID']; ?>">Budget</a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="ID" value="<?php echo $event['ID']; ?>">
                                <button type="submit" name="delete_event" onclick="return confirm('Are you sure you want to delete this event? This will also delete all associated expenses.');">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-events">You haven't created any events yet.</p>
                <p class="no-events"><a href="create-event.php">Create your first event!</a></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="expense-summary-section">
        <h3>Your Expenses Summary</h3>
        <?php if (!empty($expenses_summary)): ?>
            <?php foreach ($expenses_summary as $event_id => $event_data): ?>
                <h4><?php echo $event_data['event_name']; ?></h4>
                <?php if (!empty($event_data['expenses'])): ?>
                    <table>
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
                                <a href="modify-expense.php?expense_id=<?php echo $expense['expense_id']; ?>" class="modify-expense-btn">Modify</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No expenses added for this event.</p>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No expenses recorded for any of your events yet.</p>
        <?php endif; ?>
    </section>
    <h2>Report</h2>
    <?php if (!empty($client_report_data)): ?>
        <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Event Budget</th>
                        <th>Expense Category</th>
                        <th>Expense Budget</th>
                        <th>Expense Actual Cost</th>
                        <th>Expense Variance</th>
                        <th>Expense Description</th>
                    </tr>
                </thead>

            <tbody>
                <?php
                    $current_event = null;
                    foreach ($client_report_data as $row):
                        if ($row['event_name'] !== $current_event):
                            if ($current_event !== null): ?>
                                <tr style="background-color: #f9f9f9;"><td colspan="9">&nbsp;</td></tr>
                            <?php endif; ?>
                                <tr style="background-color: #e9ecef;">
                                    <td><strong><?php echo htmlspecialchars($row['event_name']); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($row['date']); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($row['type']); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars(number_format($row['event_budget'], 2)); ?></strong></td>
                                    <td colspan="5"></td>
                                </tr>

                                  <?php
                               $current_event = $row['event_name'];
                            endif;
                                 ?>

                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><?php echo htmlspecialchars($row['expense_category'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(number_format($row['expense_budget_limit'], 2) ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(number_format($row['expense_actual_cost'], 2) ?? 'N/A'); ?></td>
                            <td class="variance <?php
                                $budget = $row['expense_budget_limit'] ?? 0;
                                $actual = $row['expense_actual_cost'] ?? 0;
                                $variance = $budget - $actual;
                                echo $variance >= 0 ? 'positive' : 'negative';
                            ?>">
                                <?php echo htmlspecialchars(number_format($variance, 2)); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['expense_description'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
            </tbody>

        </table>

        <?php else: ?>
            <p>No events or expenses recorded for your account yet.</p>
        <?php endif; ?>
</div>

<footer>

    <div class="footer-content">
        <div class="contact-form">
            <h4>Contact Us</h4>
            <form class="contact">
                <input type="text" placeholder="Your Name" id="name">
                <input type="email" placeholder="Your Email" id="email">
                <textarea placeholder="Your Message"></textarea>
                <button type="submit" class="submit">Send</button>
            </form>
        </div>

        <div class="sitemap">
            <h4>Sitemap</h4>
            <ul>
            <li><a href="event.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="#footer">Contact Us</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>

        <div class="company-info">
            <div class="logo">
                <img src="EMS.jpg" alt="Event Management System Logo">
            </div>
            <div class="address">
                <img src="phone-solid.svg" class="icons" alt="Phone Icon">
                <p class="info">672073759</p>
            </div>
            <div class="address">
                <img src="envelope-solid.svg" class="icons" alt="Email Icon">
                <p class="info">AJEvenemential@gmail.com</p>
            </div>
        </div>
        
    </div>
</footer>

<div class="copyright">
    <p>&copy; 2025 Event Management System. All rights reserved. AJ EVENEMENTIAL</p>
</div>
</body>
</html>