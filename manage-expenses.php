<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    header('Location: admin-dashboard.php');
    exit;
}

$event_id = $_GET['event_id'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch event details
$event_details_sql = "SELECT event_name FROM events WHERE ID = '$event_id'";
$event_result = $conn->query($event_details_sql);
if ($event_result && $event_result->num_rows === 0) {
    $_SESSION['error'] = "Invalid Event ID.";
    header('Location: admin-dashboard.php');
    exit;
}
$event = $event_result->fetch_assoc();

// Handle form submission for updating tracked expenses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['expense_id'] as $key => $expense_id_to_update) {
        $category = $conn->real_escape_string($_POST['category'][$key]);
        $budget_limit = $conn->real_escape_string($_POST['budget_limit'][$key]);
        $actual_cost = isset($_POST['actual_cost'][$key]) ? $conn->real_escape_string($_POST['actual_cost'][$key]) : null;
        $description = isset($_POST['description'][$key]) ? $conn->real_escape_string($_POST['description'][$key]) : '';

        // Check if a record already exists in tracked_expenses for this event and category
        $check_sql = "SELECT id FROM tracked_expenses WHERE event_id = '$event_id' AND category = '$category'";
        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            // Update existing record
            $update_sql = "UPDATE tracked_expenses SET actual_cost = '$actual_cost', description = '$description' WHERE event_id = '$event_id' AND category = '$category'";
            $conn->query($update_sql);
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO tracked_expenses (event_id, category, budget_limit, actual_cost, description) VALUES ('$event_id', '$category', '$budget_limit', '$actual_cost', '$description')";
            $conn->query($insert_sql);
        }
    }
    $_SESSION['success'] = "Expense tracking updated for " . htmlspecialchars($event['event_name']);
    header("Location: manage-expenses.php?event_id=" . $event_id);
    exit();
}

// Fetch expenses for the specific event
$expenses_sql = "SELECT category, budget_limit FROM expenses WHERE event_id = '$event_id'";
$expenses_result = $conn->query($expenses_sql);
$expenses = [];
if ($expenses_result && $expenses_result->num_rows > 0) {
    while ($row = $expenses_result->fetch_assoc()) {
        $expenses[] = $row;
    }
}

// Fetch any previously tracked expenses for this event
$tracked_expenses_sql = "SELECT category, actual_cost, description FROM tracked_expenses WHERE event_id = '$event_id'";
$tracked_expenses_result = $conn->query($tracked_expenses_sql);
$tracked_expenses_data = [];
if ($tracked_expenses_result && $tracked_expenses_result->num_rows > 0) {
    while ($row = $tracked_expenses_result->fetch_assoc()) {
        $tracked_expenses_data[$row['category']] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Expenses - <?php echo htmlspecialchars($event['event_name']); ?></title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .container {
            width: 90%;
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #007bff;
            margin-bottom: 20px;
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
        }
        input[type="number"], textarea {
            width: calc(100% - 12px);
            padding: 8px;
            box-sizing: border-box;
            margin-bottom: 5px;
        }
        .variance {
            font-weight: bold;
        }
        .positive {
            color: #007bff;
        }
        .negative {
            color: red;
        }
        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover {
            background-color: #1e7e34;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #dc3545;
            margin-top: 10px;
        }
        .success-message {
            color: #28a745;
            margin-top: 10px;
        }
        .save{
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Manage Expenses - <?php echo htmlspecialchars($event['event_name']); ?></h2>

    <?php if (isset($_SESSION['error'])): ?>
        <p class="error-message"><?php echo $_SESSION['error']; ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <p class="success-message"><?php echo $_SESSION['success']; ?></p>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="post">
        <table>

            <thead>
            <tr>
                <th>Category</th>
                <th>Budget Limit</th>
                <th>Actual Cost</th>
                <th>Variance</th>
                <th>Description</th>
            </tr>
            </thead>

            <tbody>
            <?php if (!empty($expenses)): ?>
                <?php foreach ($expenses as $key => $expense): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($expense['category']); ?>
                            <input type="hidden" name="category[]" value="<?php echo htmlspecialchars($expense['category']); ?>">
                            <input type="hidden" name="budget_limit[]" value="<?php echo htmlspecialchars($expense['budget_limit']); ?>">
                            <input type="hidden" name="expense_id[]" value="<?php  ?>">
                        </td>
                        <td><?php echo htmlspecialchars(number_format($expense['budget_limit'], 2)); ?></td>
                        <td>
                            <input type="number" step="0.01" name="actual_cost[]" value="<?php echo isset($tracked_expenses_data[$expense['category']]['actual_cost']) ? htmlspecialchars($tracked_expenses_data[$expense['category']]['actual_cost']) : ''; ?>">
                        </td>
                        <td class="variance <?php
                            $budget = $expense['budget_limit'];
                            $actual = isset($_POST['actual_cost'][$key]) ? $_POST['actual_cost'][$key] : (isset($tracked_expenses_data[$expense['category']]['actual_cost']) ? $tracked_expenses_data[$expense['category']]['actual_cost'] : 0);
                            $variance = $budget - $actual;
                            echo $variance >= 0 ? 'positive' : 'negative';
                        ?>">
                            <?php echo htmlspecialchars(number_format($variance, 2)); ?>
                        </td>
                        <td>
                            <textarea name="description[]"><?php echo isset($tracked_expenses_data[$expense['category']]['description']) ? htmlspecialchars($tracked_expenses_data[$expense['category']]['description']) : ''; ?></textarea>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No expense categories found for this event.</td></tr>
            <?php endif; ?>
            </tbody>
            
        </table><br>
        <?php if (!empty($expenses)): ?>
            <button type="submit" class="save">Save Expense Tracking</button>
        <?php endif; ?>
    </form><br><br>

    <a href="admin-dashboard.php" class="admin-dashboard">Back to Dashboard</a>
</div>

</body>
</html>