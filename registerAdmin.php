<?php
ob_start(); 
session_start();

if (isset($_SESSION['error'])) {
    echo '<p style="color: green;">' . $_SESSION['error'] . '</p>';
    unset($_SESSION['error']);
}

// Secret key for admin registration
$secret_key = "0000";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $name = $_POST['name']; 
    $password = $_POST['password'];
    $email = $_POST['email'];
    $input_secret_key = $_POST['secret_key'];

    // Verify secret key
    if ($input_secret_key !== $secret_key) {
        $_SESSION['error'] = "Invalid secret key.";
        header('Location: registerAdmin.php');
        exit;
    }

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $hashed_secret_key = password_hash($input_secret_key, PASSWORD_DEFAULT);

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($input_secret_key)) {
        $_SESSION['error'] = "All fields are required.";
        header('Location: registerAdmin.php'); 
        exit;
    }

    // Check if email already exists
    $check_email = "SELECT admin_id FROM admins WHERE email = '$email'";
    $result = $conn->query($check_email);
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email exists already, Login.";
        header('Location: registerAdmin.php'); 
        exit;
    }

    // Insert user data into database
    $sql = "INSERT INTO admins (name, password, email, secret_key) VALUES ('$name', '$hashed_password', '$email', '$hashed_secret_key')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['success'] = "Admin registration successful. You can now log in.";
        header('Location: login.php'); 
        exit;
    } else {
        $_SESSION['error'] = "Registration failed: " . $conn->error;
        header('Location: registerAdmin.php'); 
        exit;
    }
    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
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
                <li><a href="#footer">Contact Us</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h2>Admin Registration</h2>
        <form action="registerAdmin.php" method="POST">
            <input type="text" name="name" placeholder="Name" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="secret_key" placeholder="Secret Key" required><br>
            <button type="submit" name="registerAdmin">Register</button>

            <div class="login-link">
                <p>Already a member? <a href="login.php">Login</a></p>
            </div>
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
                    <img src="aj - Copy-Photoroom.png" class="logo">
                </div>
                <div class="address">
                    <img src="phone-solid.svg" class="icons">
                    <p class="info">672073759</p>
                </div>
                <div class="address">
                    <img src="envelope-solid.svg" class="icons">
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

<?php
ob_end_flush(); // End output buffering at the very end
?>