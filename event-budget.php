<?php
session_start();

// Database connection 
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if event_id is provided in the URL
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    $_SESSION['error_message'] = "Invalid event ID.";
    header("Location: client-dashboard.php");
    exit;
}

$event_id = $_GET['event_id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Clients should only see their own event budgets
$event_check_sql = "SELECT client_id FROM events WHERE ID = '$event_id'";
$event_check_result = $conn->query($event_check_sql);

if ($event_check_result->num_rows === 1) {
    $event_data = $event_check_result->fetch_assoc();
    if ($user_role === 'client' && $event_data['client_id'] != $user_id) {
        $_SESSION['error_message'] = "You do not have permission to view this budget.";
        header("Location: client-dashboard.php");
        exit;
    }

} else {
    $_SESSION['error_message'] = "Event not found.";
    header("Location: client-dashboard.php");
    exit;
}

// Fetch budget items for the event
$sql = "SELECT * FROM expenses WHERE event_id = '$event_id'";
$budget_result = $conn->query($sql);
$budget_items = $budget_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Budget</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .budget-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .budget-table th, .budget-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .budget-table th {
            background-color: #f0f0f0;
        }

        .over-budget {
            background-color: #ffe0e0; 
        }

        .variance-positive {
            color: green;
        }

        .variance-negative {
            color: red;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Event Budget</h2>
        <table class="budget-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Budgeted Amount</th>
                    <th>Actual Cost</th>
                    <th>Variance</th>
                    </tr>
            </thead>
            
            <tbody>
                <?php if (!empty($budget_items)): ?>
                    <?php foreach ($budget_items as $item): ?>
                        <tr <?php if (isset($item['actual_cost']) && $item['actual_cost'] > $item['budget_limit']): ?>class="over-budget"<?php endif; ?>>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($item['budget_limit'], 2)); ?></td>
                            <td><?php echo isset($item['actual_cost']) ? htmlspecialchars(number_format($item['actual_cost'], 2)) : '-'; ?></td>
                            <td>
                                <?php
                                    $variance = $item['budget_limit'] - (isset($item['actual_cost']) ? $item['actual_cost'] : 0);
                                    $variance_class = ($variance >= 0) ? 'variance-positive' : 'variance-negative';
                                    echo '<span class="' . $variance_class . '">' . htmlspecialchars(number_format($variance, 2)) . '</span>';
                                ?>
                            </td>
                            </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No budget items added yet for this event.</td></tr>
                <?php endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <th colspan="2">Totals</th>
                    <th>
                        <?php
                            $total_budgeted = array_sum(array_column($budget_items, 'budget_limit'));
                            echo htmlspecialchars(number_format($total_budgeted, 2));
                        ?>
                    </th>
                    <th>
                        <?php
                            $total_actual = array_sum(array_column($budget_items, 'actual_cost'));
                            echo isset($total_actual) ? htmlspecialchars(number_format($total_actual, 2)) : '-';
                        ?>
                    </th>
                    <th>
                        <?php
                            $overall_variance = $total_budgeted - (isset($total_actual) ? $total_actual : 0);
                            $overall_variance_class = ($overall_variance >= 0) ? 'variance-positive' : 'variance-negative';
                            echo '<span class="' . $overall_variance_class . '">' . htmlspecialchars(number_format($overall_variance, 2)) . '</span>';
                        ?>
                    </th>
                </tr>
            </tfoot>
        </table>

        <p><a href="client-dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>