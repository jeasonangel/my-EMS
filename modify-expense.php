<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'eventmgtsyst';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['expense_id']) || !is_numeric($_GET['expense_id'])) {
    $_SESSION['error_message'] = "Invalid expense ID.";
    header("Location: client-dashboard.php");
    exit;
}

$expense_id = $_GET['expense_id'];
$client_id = $_SESSION['user_id'];

// Fetch the expense details
$sql_get_expense = "SELECT e.expense_id, e.category, e.budget_limit
                    FROM expenses e
                    JOIN events ev ON e.event_id = ev.ID
                    WHERE e.expense_id = '$expense_id' AND ev.client_id = '$client_id'";
$result_expense = $conn->query($sql_get_expense);

if (!$result_expense || $result_expense->num_rows !== 1) {
    $_SESSION['error_message'] = "Expense not found or you do not have permission to modify it.";
    header("Location: client-dashboard.php");
    exit;
}

$expense_data = $result_expense->fetch_assoc();

// Handle form submission
if (isset($_POST['modify-expense'])) {
    $new_category = $_POST['category'];
    $new_budget_limit = $_POST['budget_limit'];

    if (empty($new_category)) {
        $error = "Category cannot be empty.";
    } elseif (!is_numeric($new_budget_limit) || $new_budget_limit < 0) {
        $error = "Budget limit must be a non-negative number.";
    } else {
        $sql_update_expense = "UPDATE expenses SET category = '$new_category', budget_limit = '$new_budget_limit' WHERE expense_id = '$expense_id'";

        if ($conn->query($sql_update_expense)) {
            $_SESSION['success_message'] = "Expense updated successfully.";
            header("Location: client-dashboard.php");
            exit;
        } else {
            $error = "Error updating expense: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Expense</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 90%;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }

        .error-message {
            color: red;
            margin-top: 10px;
        }

        .button-group {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
            
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
        .update-btn{
            height: 45px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Modify Expense</h2>
        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?> 
        <form method="post" action="modify-expense">
            <input type="hidden" name="expense_id" value="<?php echo htmlspecialchars($expense_data['expense_id']); ?>">
            <div class="form-group">
               <label for="category">Category:</label>
               <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($expense_data['category']); ?>">
            </div>
            <div class="form-group">
               <label for="budget_limit">Budget Limit:</label>
               <input type="number" id="budget_limit" name="budget_limit" value="<?php echo htmlspecialchars($expense_data['budget_limit']); ?>" min="0">
            </div>
            <div class="button-group">
               <button type="submit" name="modify-expense" class="update-btn">Update Expense</button>
               <a href="client-dashboard.php" class="back-link" style="background-color: #6c757d; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none;">Cancel</a>
            </div>
        </form>
        <p class="back-link"><a href="client-dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>