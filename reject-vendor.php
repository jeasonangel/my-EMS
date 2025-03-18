<?php
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
    $sql = "UPDATE vendors SET status = 'rejected' WHERE ID = '$vendor_id'";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['success'] = "vendor rejected.";
} else{
    $_SESSION['error'] = "Rejection failed.";
}
}

header("Location: admin-dashboard.php");
exit;
?>

<form method="post">
    <input type="hidden" name="vendor_id" value="<?php echo isset($_GET['id']) ? intval($_GET['id']) : ''; ?>">
    <button type="submit" name="reject_vendor">Reject</button>
</form>