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

$error = "";
$success = "";

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
    <style>
        .container {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #007bff;
        }

        input[type="text"],
        input[type="date"],
        input[type="time"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
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
                <li><a href="#footer">Contact Us</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Create Your Event</h2>

        <form id="eventForm" action="create-event.php" method="POST">
            <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
            <?php endif; ?>

            <label for="event_name">Event Name:</label>
            <input type="text" name="event_name" placeholder="Event Name" required><br>

            <label for="date">Date:</label>
            <input type="date" name="date" required><br>

            <label for="time">Time:</label>
            <input type="time" name="time" required><br>

            <label for="type">Event Type:</label>
            <select name="type" required>
                <option value="">Select Event Type</option>
                <option value="wedding">Wedding</option>
                <option value="party">Party</option>
                <option value="conference">Conference</option>
                <option value="birthday">Birthday</option>
                <option value="other">Other</option>
            </select><br>

            <label for="budget">Budget:</label>
            <input type="number" name="budget" placeholder="Budget" required><br>

            <button type="submit">Create Event</button>
        </form>
    </div>

    <footer>
        <div class="footer-content">

            <div class="contact-form">
                <h4>Contact Us</h4>
                <form action = "https://formsubmit.co/jeasonangel0@gmail.com" method = 'POST'>
                    <input type="text" placeholder="Your Name" id="name">
                    <input type="email" placeholder="Your Email" id="email">
                    <textarea placeholder="Your Message"></textarea>
                    <button type="submit" class="submit">Send</button>
                </form>
            </div>

            <div class="sitemap">
                <h4>Sitemap</h4>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="feature.html">Features</a></li>
                    <li><a href="">Contact Us</a></li>
                    <li><a href="#find-venues">Find Venues</a></li>
                    <li><a href="#login">Login</a></li>
                    <li><a href="#signup">SignUp</a></li>
                </ul>
            </div>

            <div class="company-info">
                <div class="logo">
                    <img src="aj - Copy-Photoroom.png" class="logo" alt="Event Management System Logo">
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