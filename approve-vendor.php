<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include PHPMailer via Composer

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
  header('Location: login.php');
  exit;
}

if (isset($_GET['id'])) {
  $vendor_id = $_GET['id'];

  // Database connection
  $conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Update vendor status
  $sql = "UPDATE vendors SET status = 'approved' WHERE ID = '$vendor_id'";
  if ($conn->query($sql) === TRUE) {
    // Fetch vendor email 
    $vendor_email_sql = "SELECT company_name, email FROM vendors WHERE ID = '$vendor_id'";
    $vendor_email_result = $conn->query($vendor_email_sql);
    if ($vendor_email_result->num_rows > 0) {
      $vendor = $vendor_email_result->fetch_assoc();
      $vendor_name = $vendor['company_name'];
      $vendor_email = $vendor['email'];
      $temporary_password = "0000";
      
      // Send email notification using PHPMailer
      $mail = new PHPMailer(true);

      try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable verbose debug output
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jeasonangel0@gmail.com'; // SMTP username
        $mail->Password   = 'jbzy mrza bpak caap';         // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = 587;                // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        // Recipients
        $mail->setFrom('jeasonangel0@gmail.com', 'jeason angel'); 
        $mail->addAddress($vendor_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Vendor Registration Approved';
        $mail->Body    = "Dear " . $vendor_name . ",\n\n";
        $mail->Body   .= "Your vendor registration has been approved by our team.\n";
        $mail->Body   .= "Your temporary password for login is: " . $temporary_password . "\n";
        $mail->Body   .= "Please use this password to log in for the first time '\n'";
        $mail->Body   .= "Sincerely,'\n' The Event Management Team";

        $mail->send();
        $_SESSION['success'] = "Vendor approved and notification sent.";
      } catch (Exception $e) {
        $_SESSION['error'] = "Vendor approved, but failed to send notification: {$mail->ErrorInfo}";
      }
    } else {
      $_SESSION['error'] = "Vendor approved, but email not found.";
    }
  } else {
    $_SESSION['error'] = "Error approving vendor: " . $conn->error;
  }

  $conn->close();
} else {
  $_SESSION['error'] = "Invalid vendor ID.";
}

header('Location: admin-dashboard.php');
exit;
?>