<?php

session_start();

// Check if the user is a logged-in client
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";
$event_id = null;
$event_name = 'Unknown Event';
$existing_categories = [];

if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    $client_id = $_SESSION['user_id'];

    // Verify if the client owns this event
    $sql_check_ownership = "SELECT ID, event_name FROM events WHERE ID = $event_id AND client_id = $client_id";
    $result_ownership = $conn->query($sql_check_ownership);

    if ($result_ownership->num_rows === 0) {
        $_SESSION['error_message'] = "You are not authorized to manage expenses for this event.";
        header("Location: client-dashboard.php");
        exit();
    } else {
        $event_data = $result_ownership->fetch_assoc();
        $event_name = htmlspecialchars($event_data['event_name']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            foreach ($_POST['categories'] as $category) {
                $name = $conn->real_escape_string($category['name']);
                $budget_limit = floatval($category['budget_limit']);

                if (!empty($name)) {
                    $sql = "INSERT INTO expenses (event_id, category, budget_limit) VALUES ($event_id, '$name', $budget_limit)";

                    if ($conn->query($sql) === TRUE) {
                        $success = "Expense categories added successfully!";
                    } else {
                        $error = "Error adding expense category: " . $conn->error;
                    }
                }
            }
            header("Location: expenses.php?event_id=$event_id"); // Redirect to refresh
            exit();
        } else {
            $error = "No expense categories submitted.";
        }
    }

    // Fetch existing expense categories
    $sql_existing_categories = "SELECT expense_id, category, budget_limit FROM expenses WHERE event_id = $event_id";
    $result_existing_categories = $conn->query($sql_existing_categories);

    if ($result_existing_categories->num_rows > 0) {
        while ($row = $result_existing_categories->fetch_assoc()) {
            $existing_categories[] = $row;
        }
    }

} else {
    $_SESSION['error_message'] = "Invalid event ID.";
    header("Location: client-dashboard.php");
    exit();
}

// Display error and success messages
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Budget - <?php echo $event_name; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .category-row {
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .category-row label {
            flex-basis: 120px;
            font-weight: bold;
        }

        .category-row input[type="text"],
        .category-row input[type="number"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            flex-grow: 1;
        }

        #add-category-btn {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        #add-category-btn:hover {
            background-color: #1e7e34;
        }

        button[type="submit"] {
            padding: 12px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 1em;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .existing-categories {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .existing-categories h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .existing-categories ul {
            list-style: none;
            padding: 0;
        }

        .existing-categories li {
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .existing-categories li:last-child {
            border-bottom: none;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
        }

        .success-message {
            color: green;
            margin-bottom: 15px;
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
    </style>
</head>

<body>

    <div class="container">
        <h2>Manage Budget for Event: <?php echo $event_name; ?></h2>

        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="success-message"><?php echo $success; ?></p>
        <?php endif; ?>

        <div class="form-section">
            <h3>Add New Budget Categories</h3>
            
            <form method="POST" action="expenses.php?event_id=<?php echo $event_id; ?>">
                <div id="expense-categories-container">
                    <div class="category-row">
                        <label for="category_name_0">Category Name:</label>
                        <input type="text" id="category_name_0" name="categories[0][name]" placeholder="e.g., Catering, venues" required>
                        <label for="budget_limit_0">Budget Limit:</label>
                        <input type="number" id="budget_limit_0" name="categories[0][budget_limit]" placeholder="e.g., 5000.00" step="0.01">
                    </div>
                </div>
                <button type="button" id="add-category-btn">Add Another Category</button>
                <button type="submit">Save Budget Categories</button>
            </form>
        </div>

        <div class="existing-categories">
            <h3>Current Budget Categories:</h3>
            <?php if (!empty($existing_categories)): ?>
                <ul>
                    <?php foreach ($existing_categories as $category): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($category['category']); ?>:</strong>
                            Budget Limit: <?php echo number_format($category['budget_limit'], 2); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No budget categories added yet for this event.</p>
            <?php endif; ?>
        </div>

        <a href="client-dashboard.php" class="back-link">Back to Client Dashboard</a>
    </div>

    <script>
        const container = document.getElementById('expense-categories-container');
        const addCategoryBtn = document.getElementById('add-category-btn');
        let categoryCount = 1;

        addCategoryBtn.addEventListener('click', function() {
            const newRow = document.createElement('div');
            newRow.classList.add('category-row');
            newRow.innerHTML = `
                <label for="category_name_${categoryCount}">Category Name:</label>
                <input type="text" id="category_name_${categoryCount}" name="categories[${categoryCount}][name]" placeholder="e.g., Decorations, Entertainement" required>
                <label for="budget_limit_${categoryCount}">Budget Limit:</label>
                <input type="number" id="budget_limit_${categoryCount}" name="categories[${categoryCount}][budget_limit]" placeholder="e.g., 1000.00" step="0.01">
            `;
            container.appendChild(newRow);
            categoryCount++;
        });
    </script>
</body>
</html>