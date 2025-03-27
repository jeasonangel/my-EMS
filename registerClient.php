<?php
session_start();
if (isset($_SESSION['error'])) {
    echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
    unset($_SESSION['error']);
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
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Retrieve form data
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Basic validation 
  if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['error'] = "All fields are required.";
    header('Location: register.php');
    exit;
  }
  // Database connection
  $conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
   //Check if email already exists
   $check_email = "SELECT client_id FROM clients WHERE email = '$email'";
  $result = $conn->query($check_email);
  if ($result->num_rows > 0) {
     $_SESSION['error'] = "Email exists already, Login.";
    header('Location: registerClient.php');
    exit;
  }

  // Hash the password (important for security)
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  // Insert user data into database
    $sql = "INSERT INTO clients (name, email, password) VALUES ('$name', '$email', '$hashed_password')";

  if ($conn->query($sql) === TRUE) {
     $_SESSION['success'] = "<script>alert('Registration successfull, you can now login')</script>";
    header('Location: login.php');
    exit;

  } else {
    $_SESSION['error'] = "Error: " . $sql . "<br>" . $conn->error;
    header('Location: registerClient.php');
    exit;
  }
  $conn->close();
}

?>
    <header>
        <div class="logo">
            <img src="aj - Copy-Photoroom.png" class="logo">
        </div>
        <nav>
            <ul>
                <li><a href="">Home</a></li>
                <li><a href="">Features</a></li>
                <li><a href="">services</li>
                <li><a href="">Find Venues</a></li>
                <li><a href="">Login</a></li>
                <li><a href="">Register</a></li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h2>Register</h2>

        <form id="RegForm" action="registerClient.php" method="POST">
            <input type="text" name="name" placeholder="Full Name" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Register</button>
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
                    <li><a href="#home">Home</a></li>
                    <li><a href="feature.html">Features</a></li>
                    <li><a href="service.php">Services</li>
                    <li><a href="#find-venues">Find Venues</a></li>
                    <li><a href="#login">Login</a></li>
                    <li><a href="#signup">SignUp</a></li>
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
