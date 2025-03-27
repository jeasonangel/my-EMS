<?php
session_start();

// Check if the user is logged in as a client
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = $_SESSION['error'] ?? ""; // Get existing error, if any
$success = $_SESSION['success'] ?? ""; // Get existing success message, if any

unset($_SESSION['error']); // Clear error message
unset($_SESSION['success']); // Clear success message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $event_name = isset($_POST['event_name']) ? trim($_POST['event_name']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $time = isset($_POST['time']) ? trim($_POST['time']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $budget = isset($_POST['budget']) ? trim($_POST['budget']) : '';
    $client_id = $_SESSION['user_id'];

    // Basic validation
    if (empty($event_name) || empty($date) || empty($time) || empty($type) || empty($budget)) {
        $error = "All fields are required.";
    } elseif (!is_numeric($budget) || $budget <= 0) {
        $error = "Budget must be a positive number.";
    } else {
        // Data sanitization
        $event_name = $conn->real_escape_string($event_name);
        $date = $conn->real_escape_string($date);
        $time = $conn->real_escape_string($time);
        $type = $conn->real_escape_string($type);
        $budget = floatval($budget);

        // Insert event data into database
        $sql = "INSERT INTO events (event_name, date, time, type, budget, client_id)
                    VALUES ('$event_name', '$date', '$time', '$type', '$budget', '$client_id')";

        if ($conn->query($sql)) {
            $event_id = $conn->insert_id; // Get the ID of the newly created event
            $_SESSION['success'] = "Event created successfully.";
            header("Location: expenses.php?event_id=$event_id"); // Redirect to expenses page
            exit;
        } else {
            $error = "Error creating event: " . $conn->error;
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
    <title>Create Event</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        h2 {
            color: #007bff;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="aj - Copy-Photoroom.png" class="logo" alt="Event Management System Logo">
        </div>
        <nav>
            <ul>
                <li><a href="event.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="#footer">Contact Us</a></li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2><i class="fas fa-calendar-plus"></i> Create Your Event</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form id="eventForm" action="create-event.php" method="POST">
            <div class="form-group">
                <label for="event_name"><i class="fas fa-signature"></i> Event Name:</label>
                <input type="text" name="event_name" placeholder="Event Name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="date"><i class="fas fa-calendar-day"></i> Date:</label>
                <input type="date" name="date" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="time"><i class="fas fa-clock"></i> Time:</label>
                <input type="time" name="time" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="type"><i class="fas fa-list"></i> Event Type:</label>
                <select name="type" class="form-control" required>
                    <option value="">Select Event Type</option>
                    <option value="wedding">Wedding</option>
                    <option value="party">Party</option>
                    <option value="conference">Conference</option>
                    <option value="birthday">Birthday</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="budget"><i class="fas fa-dollar-sign"></i> Budget:</label>
                <input type="number" name="budget" placeholder="Budget" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Create Event</button>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            <div class="contact-form">
                <h4><i class="fas fa-envelope"></i> Contact Us</h4>
                <form action="https://formsubmit.co/jeasonangel0@gmail.com" method="POST">
                    <input type="text" placeholder="Your Name" id="name" class="form-control">
                    <input type="email" placeholder="Your Email" id="email" class="form-control">
                    <textarea placeholder="Your Message" class="form-control"></textarea>
                    <button type="submit" class="submit btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                </form>
            </div>

            <div class="sitemap">
                <h4><i class="fas fa-sitemap"></i> Sitemap</h4>
                <ul>
                    <li><a href="#home"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="feature.html"><i class="fas fa-star"></i> Features</a></li>
                    <li><a href="service.php"><i class="fas fa-concierge-bell"></i> Services</a></li>
                    <li><a href="#find-venues"><i class="fas fa-map-marker-alt"></i> Find Venues</a></li>
                    <li><a href="#login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="#signup"><i class="fas fa-user-plus"></i> SignUp</a></li>
                </ul>
            </div>

            <div class="company-info">
                <div class="logo">
                    <img src="aj - Copy-Photoroom.png" class="logo" alt="Event Management System Logo">
                </div>
                <div class="address">
                    <i class="fas fa-phone"></i>
                    <p class="info">672073759</p>
                </div>
                <div class="address">
                    <i class="fas fa-envelope"></i>
                    <p class="info">AJEvenemential@gmail.com</p>
                </div>
            </div>
        </div>
    </footer>

    <div class="copyright">
        <p>&copy; 2025 Event Management System. All rights reserved. AJ EVENEMENTIAL</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>