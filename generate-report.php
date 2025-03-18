<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$report_data = [];
$report_title = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];

    if ($report_type === 'expense_report') {
        $event_filter = isset($_POST['event_id']) ? $conn->real_escape_string($_POST['event_id']) : null;
        $report_title = "Expense Report";
        $sql = "SELECT e.category, e.budget_limit, te.actual_cost, te.description, ev.event_name, cl.name AS client_name
                FROM expenses e
                JOIN events ev ON e.event_id = ev.ID
                JOIN clients cl ON ev.client_id = cl.client_id
                LEFT JOIN tracked_expenses te ON e.event_id = te.event_id AND e.category = te.category";
        if ($event_filter) {
            $sql .= " WHERE e.event_id = '$event_filter'";
            $report_title .= " for Event: " . htmlspecialchars(getEventName($conn, $event_filter));
        } else {
            $report_title .= " - All Events";
        }
        $sql .= " ORDER BY ev.event_name, e.category";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
        }
    } elseif ($report_type === 'vendor_report') {
        $report_title = "Vendor Report - All Approved Vendors";
        $sql = "SELECT company_name, contact, product_name, price, experience FROM vendors WHERE status = 'approved' ORDER BY company_name";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
        }
    } elseif ($report_type === 'venue_report') {
        $report_title = "Venue Report - All Venues";
        $sql = "SELECT name, location, capacity, price FROM venues ORDER BY name";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
        } elseif ($report_type === 'event_summary_report') {
            $report_title = "Event Summary Report - All Events";
            $sql = "SELECT events.event_name, events.date, events.type, events.time, events.budget, clients.name AS client_name
                    FROM events
                    JOIN clients ON events.client_id = clients.client_id
                    ORDER BY events.date";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $report_data = $result->fetch_all(MYSQLI_ASSOC);
            }
        } elseif ($report_type === 'financial_summary_report') {
            $report_title = "Financial Summary Report - All Events";
            $sql = "SELECT
                        ev.event_name,
                        cl.name AS client_name,
                        ev.budget AS total_budget,
                        SUM(te.actual_cost) AS total_actual_spending,
                        (ev.budget - SUM(te.actual_cost)) AS overall_variance
                    FROM events ev
                    JOIN clients cl ON ev.client_id = cl.client_id
                    LEFT JOIN tracked_expenses te ON ev.ID = te.event_id
                    GROUP BY ev.ID
                    ORDER BY ev.event_name";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $report_data = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
}

//function to get event name
function getEventName($conn, $event_id) {
    $sql = "SELECT event_name FROM events WHERE ID = '$event_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['event_name'];
    }
    return "Unknown Event";
}

// Fetch all events for the event filter dropdown
$events_sql = "SELECT ID, event_name FROM events ORDER BY event_name";
$events_result = $conn->query($events_sql);
$events = [];
if ($events_result && $events_result->num_rows > 0) {
    $events = $events_result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2, h3 {
            color: #007bff;
            margin-bottom: 15px;
        }
        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        form select, form button {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }
        form button {
            background-color: #28a745;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #1e7e34;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .variance {
            font-weight: bold;
        }
        .positive {
            color: green;
        }
        .negative {
            color: red;
        }
        .reportgen{
            background-color: blue;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Generate Report</h2>

        <form method="post">
            <label for="report_type">Report Type:</label>
            <select name="report_type" id="report_type">
                <option value="">-- Select Report --</option>
                <option value="expense_report">Expense Report</option>
                <option value="vendor_report">Vendor Report</option>
                <option value="venue_report">Venue Report</option>
            </select>

            <div id="expense_filters" style="display: none;">
                <label for="event_id">Filter by Event:</label>
                <select name="event_id" id="event_id">
                    <option value="">-- All Events --</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['ID']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="generate_report" class="reportgen">Generate Report</button>
        </form>

        <?php if (!empty($report_data)): ?>
            <h3><?php echo htmlspecialchars($report_title); ?></h3>
            <table>
                <thead>
                    <tr>
                        <?php if ($_POST['report_type'] === 'expense_report'): ?>
                            <th>Event Name</th>
                            <th>Client Name</th>
                            <th>Category</th>
                            <th>Budget Limit</th>
                            <th>Actual Cost</th>
                            <th>Variance</th>
                            <th>Description</th>
                        <?php elseif ($_POST['report_type'] === 'vendor_report'): ?>
                            <th>Company Name</th>
                            <th>Contact</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Year of Experience</th>
                        <?php elseif ($_POST['report_type'] === 'venue_report'): ?>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Price</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $row): ?>
                        <tr>
                            <?php if ($_POST['report_type'] === 'expense_report'): ?>
                                <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($row['budget_limit'], 2)); ?></td>
                                <td><?php echo htmlspecialchars(number_format($row['actual_cost'], 2) ?? 'N/A'); ?></td>
                                <td class="variance <?php
                                    $budget = $row['budget_limit'];
                                    $actual = $row['actual_cost'] ?? 0;
                                    $variance = $budget - $actual;
                                    echo $variance >= 0 ? 'positive' : 'negative';
                                ?>">
                                    <?php echo htmlspecialchars(number_format($variance, 2)); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['description'] ?? 'N/A'); ?></td>
                            <?php elseif ($_POST['report_type'] === 'vendor_report'): ?>
                                <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($row['price'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($row['experience']); ?></td>
                            <?php elseif ($_POST['report_type'] === 'venue_report'): ?>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['capacity']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($row['price'], 2)); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($_POST['generate_report'])): ?>
            <p>No data found for the selected report.</p>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportTypeSelect = document.getElementById('report_type');
            const expenseFiltersDiv = document.getElementById('expense_filters');

            function hideAllFilters() {
                expenseFiltersDiv.style.display = 'none';
            }

            reportTypeSelect.addEventListener('change', function() {
                hideAllFilters();
                if (this.value === 'expense_report') {
                    expenseFiltersDiv.style.display = 'block';
                }
            });

            // hide all filters
            hideAllFilters();
        });
    </script>
</body>
</html>