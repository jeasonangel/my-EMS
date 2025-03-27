<?php
session_start();
if (isset($_SESSION['success'])) {
    echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
    unset($_SESSION['success']);
  }
// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Sanitize input
    $email = $conn->real_escape_string($email);

    $loggedIn = false; 

   // Check admin table
   $sql = "SELECT admin_id, password FROM admins WHERE email = '$email'";
   $result = $conn->query($sql);

   if ($result->num_rows > 0) {
       $row = $result->fetch_assoc();
       if (password_verify($password, $row['password'])) {
           $_SESSION['logged_in'] = true;
           $_SESSION['user_id'] = $row['admin_id'];
           $_SESSION['role'] = 'admin';
           $loggedIn = true;
           header('Location: admin-dashboard.php');
           exit;
       } else {
           echo "Admin password incorrect.<br>"; // Debugging
       }
   } else {
       echo "Admin email not found.<br>"; // Debugging
   }

   // Check client table
   $sql = "SELECT client_id, password, name FROM clients WHERE email = '$email'";
   $result = $conn->query($sql);

   if ($result->num_rows > 0) {
       $row = $result->fetch_assoc();
       if (password_verify($password, $row['password'])) {
           $_SESSION['logged_in'] = true;
           $_SESSION['user_id'] = $row['client_id'];
           $_SESSION['username'] = $row['name'];
           $_SESSION['role'] = 'client';
           $loggedIn = true;
           header('Location: client-dashboard.php');
           exit;
       } else {
           echo "Client password incorrect.<br>"; 
       }
   } else {
       echo "Client email not found.<br>"; 
   }

   // Check vendor table
   $sql = "SELECT ID, company_name, password, status FROM vendors WHERE email = '$email'";
   $result = $conn->query($sql);
   if ($result->num_rows > 0) {
       $row = $result->fetch_assoc();
       if ($row['status'] === 'approved') {
           if ($password === '0000') {
               $_SESSION['logged_in'] = true;
               $_SESSION['role'] = 'vendor';
               $_SESSION['user_id'] = $row['ID'];
               $_SESSION['username'] = $row['company_name'];
               $loggedIn = true;
               header('Location: vendor-dashboard.php');
               exit;
           } elseif (password_verify($password, $row['password'])) {
               $_SESSION['logged_in'] = true;
               $_SESSION['role'] = 'vendor';
               $_SESSION['user_id'] = $row['ID'];
               $_SESSION['username'] = $row['company_name'];
               $loggedIn = true;
               header('Location: vendor-dashboard.php');
               exit;
           } else {
               echo "Vendor password incorrect.<br>";
           }
       } else {
           echo "Vendor status not approved.<br>"; 
       }
   } else {
       echo "Vendor email not found.<br>"; 
   }


    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login-form</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="aj - Copy-Photoroom.png" class="logo" alt="Logo">
        </div>
        <nav>
            <ul>
            <li><a href="event.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="service.php">Services</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h2>Login</h2>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        ?>
<form action="login.php" method="POST">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
</form>
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
                <li><a href="service.php">Services</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
                </ul>
            </div>
            <div class="company-info">
                <div class="logo">
                    <img src="aj - Copy-Photoroom.png" class="logo" alt="Logo">
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