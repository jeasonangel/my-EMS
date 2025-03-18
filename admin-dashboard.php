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

// Handle form submission 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_venue'])) {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price'];

    // Image upload handling
    $target_dir = "uploads/venues/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        $_SESSION['error'] = "File is not an image.";
        $uploadOk = 0;
    }
    if ($_FILES["image"]["size"] > 5000000) {
        $_SESSION['error'] = "Sorry, your file is too large.";
        $uploadOk = 0;
    }
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO venues (name, location, capacity, price, image) VALUES ('$name', '$location', '$capacity', '$price', '$target_file')";
            if ($conn->query($sql) === TRUE) {
                $_SESSION['success'] = "Venue added successfully.";
            } else {
                $_SESSION['error'] = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Sorry, there was an error uploading your file.";
        }
    }
    header("Location: admin-dashboard.php");
    exit();
}

// Fetch pending vendor registrations
$pending_vendors_sql = "SELECT * FROM vendors WHERE status = 'pending'";
$pending_vendors_result = $conn->query($pending_vendors_sql);

// Fetch all events
$events_sql = "SELECT events.*, clients.name AS client_name
               FROM events
               JOIN clients ON events.client_id = clients.client_id";
$events_result = $conn->query($events_sql);

// Fetch all expenses with event and client details
$expenses_sql = "SELECT expenses.category, expenses.budget_limit, events.event_name, clients.name AS client_name, clients.email AS client_email
                 FROM expenses
                 JOIN events ON expenses.event_id = events.ID
                 JOIN clients ON events.client_id = clients.client_id";
$expenses_result = $conn->query($expenses_sql);
if ($expenses_result === false) {
    echo "SQL Error: " . $conn->error;
    die();
}
$expenses = $expenses_result->fetch_all(MYSQLI_ASSOC);
// Fetch all expenses grouped by event to show a summary on the dashboard
$expenses_summary_sql = "SELECT events.ID AS event_id, events.event_name, clients.name AS client_name, COUNT(expenses.expense_id) AS total_expenses
                         FROM expenses
                         JOIN events ON expenses.event_id = events.ID
                         JOIN clients ON events.client_id = clients.client_id
                         GROUP BY events.ID
                         ORDER BY events.event_name";
$expenses_summary_result = $conn->query($expenses_summary_sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        th {
            background-color: #f8f8f8;
            font-weight: bold;
            color: #555;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .action-btn-container {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            color: white;
            transition: background-color 0.3s ease;
            display: inline-block;
            text-align: center;
        }

        .approve-btn {
            background-color: #28a745; /* Green */
        }

        .approve-btn:hover {
            background-color: #1e7e34;
        }

        .reject-btn {
            background-color: #dc3545; /* Red */
        }

        .reject-btn:hover {
            background-color: #c82333;
        }

        h3 {
            color: #28a745;
            margin-top: 25px;
            margin-bottom: 15px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 5px;
        }

        form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
            border: 1px solid #eee;
        }

        form input[type="text"],
        form input[type="number"],
        form input[type="file"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }

        form button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        form button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: #dc3545;
            margin-top: 10px;
        }

        .success-message {
            color: #28a745;
            margin-top: 10px;
        }
    </style>
</head>

<body>

<div class="container">
    <h2>Admin Dashboard</h2>

    <h3>Pending Vendor Approvals</h3>
    <?php if ($pending_vendors_result->num_rows > 0) : ?>
        <table>
            <thead>
            <tr>
                <th>Company Name</th>
                <th>Contact</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Year of Experience</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $pending_vendors_result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact']); ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['price']); ?></td>
                    <td><?php echo htmlspecialchars($row['experience']); ?></td>
                    <td>
                        <div class="action-btn-container">
                            <a href="approve-vendor.php?id=<?php echo $row['ID']; ?>" class="action-btn approve-btn">Approve</a>
                            <a href="reject-vendor.php?id=<?php echo $row['ID']; ?>" class="action-btn reject-btn">Reject</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>No pending vendor approvals.</p>
    <?php endif; ?>

    <h3>All Events</h3>
    <?php if ($events_result->num_rows > 0) : ?>
        <table>
            <thead>
            <tr>
                <th>Event Name</th>
                <th>Date</th>
                <th>Type</th>
                <th>Time</th>
                <th>Budget</th>
                <th>Client Name</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $events_result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo htmlspecialchars($row['time']); ?></td>
                    <td><?php echo htmlspecialchars($row['budget']); ?></td>
                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>No events created yet.</p>
    <?php endif; ?>

    <h3>Manage Event Expenses</h3>
    <?php if ($expenses_summary_result->num_rows > 0) : ?>
        <table>
            <thead>
            <tr>
                <th>Event Name</th>
                <th>Client Name</th>
                <th>Total Expense Categories</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $expenses_summary_result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['total_expenses']); ?></td>
                    <td>
                        <a href="manage-expenses.php?event_id=<?php echo $row['event_id']; ?>" class="manage-btn">Manage</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>No expenses recorded yet.</p>
    <?php endif; ?>
    <h3>All Expenses</h3>
    <table>
        <thead>
        <tr>
            <th>Category</th>
            <th>Budget Limit</th>
            <th>Event Name</th>
            <th>Client Name</th>
            <th>Client Email</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($expenses as $expense) : ?>
            <tr>
                <td><?php echo htmlspecialchars($expense['category']); ?></td>
                <td><?php echo htmlspecialchars($expense['budget_limit']); ?></td>
                <td><?php echo htmlspecialchars($expense['event_name']); ?></td>
                <td><?php echo htmlspecialchars($expense['client_name']); ?></td>
                <td><?php echo htmlspecialchars($expense['client_email']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Add New Venue</h3>
    <form action="admin-dashboard.php" method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Venue Name" required><br>
        <input type="text" name="location" placeholder="Location" required><br>
        <input type="number" name="capacity" placeholder="Capacity" required><br>
        <input type="number" name="price" placeholder="Price" required><br>
        <label for="image">Venue Image:</label>
        <input type="file" name="image" id="image" accept="image/*" required><br>
        <button type="submit" name="add_venue">Add Venue</button>
    </form>

    <?php
    if (isset($_SESSION['error'])) {
        echo '<p class="error-message">' . $_SESSION['error'] . '</p>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<p class="success-message">' . $_SESSION['success'] . '</p>';
        unset($_SESSION['success']);
    }
    ?>
    
    <h3>Reports</h3>
<p><a href="generate-report.php">Generate Reports</a></p>
</div>

</body>
</html>