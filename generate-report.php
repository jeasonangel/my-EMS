<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/vendor/autoload.php'; // Ensure Composer autoloader is included

//use TCPDF;

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$report_data = [];
$report_title_text = "";
$report_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate-report'])) {
    $report_type = $_POST['report_type'];

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Event Management System');
    $pdf->SetTitle('Admin Report');
    $pdf->SetSubject('Admin Report');
    $pdf->SetKeywords('event, report, admin');

    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    // Set default font subsetting mode
    $pdf->setFontSubsetting(true);

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Add a page
    $pdf->AddPage();

    $html = '<h1>Admin Report</h1>';
    $generation_time = date('Y-m-d H:i:s');
    $html .= '<p>Generated on: ' . $generation_time . '</p>';

    if ($report_type === 'expense_report') {
        $event_filter = isset($_POST['event_id']) ? $conn->real_escape_string($_POST['event_id']) : null;
        $report_title_text = "Expense Report";
        if ($event_filter) {
            $report_title_text .= " for Event: " . htmlspecialchars(getEventName($conn, $event_filter));
            $sql = "SELECT e.category, e.budget_limit, te.actual_cost, te.description, ev.event_name, cl.name AS client_name
                    FROM expenses e
                    JOIN events ev ON e.event_id = ev.ID
                    JOIN clients cl ON ev.client_id = cl.client_id
                    LEFT JOIN tracked_expenses te ON e.event_id = te.event_id AND e.category = te.category
                    WHERE e.event_id = '$event_filter'
                    ORDER BY ev.event_name, e.category";
        } else {
            $report_title_text .= " - All Events";
            $sql = "SELECT e.category, e.budget_limit, te.actual_cost, te.description, ev.event_name, cl.name AS client_name
                    FROM expenses e
                    JOIN events ev ON e.event_id = ev.ID
                    JOIN clients cl ON ev.client_id = cl.client_id
                    LEFT JOIN tracked_expenses te ON e.event_id = te.event_id AND e.category = te.category
                    ORDER BY ev.event_name, e.category";
        }
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $html .= '<h3>' . htmlspecialchars($report_title_text) . '</h3>';
            $html .= '<table border="1" cellpadding="5">';
            $html .= '<thead><tr><th>Event Name</th><th>Client Name</th><th>Category</th><th>Budget</th><th>Actual Cost</th><th>Variance</th><th>Description</th></tr></thead><tbody>';
            while ($row = $result->fetch_assoc()) {
                $variance = ($row['budget_limit'] ?? 0) - ($row['actual_cost'] ?? 0);
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($row['event_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['client_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['category']) . '</td>';
                $html .= '<td align="right">' . htmlspecialchars(number_format($row['budget_limit'], 2)) . '</td>';
                $html .= '<td align="right">' . htmlspecialchars(number_format($row['actual_cost'], 2) ?? 'N/A') . '</td>';
                $html .= '<td align="right" style="color:' . ($variance >= 0 ? 'green' : 'red') . '">' . htmlspecialchars(number_format($variance, 2)) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['description'] ?? 'N/A') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No expense data found for the selected criteria.</p>';
        }
    } elseif ($report_type === 'vendor_report') {
        $report_title_text = "Vendor Report - All Approved Vendors";
        $sql = "SELECT company_name, contact, product_name, price, experience FROM vendors WHERE status = 'approved' ORDER BY company_name";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $html .= '<h3>' . htmlspecialchars($report_title_text) . '</h3>';
            $html .= '<table border="1" cellpadding="5">';
            $html .= '<thead><tr><th>Company Name</th><th>Contact</th><th>Product Name</th><th>Price</th><th>Experience</th></tr></thead><tbody>';
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($row['company_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['contact']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['product_name']) . '</td>';
                $html .= '<td align="right">' . htmlspecialchars(number_format($row['price'], 2)) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['experience']) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No approved vendor data found.</p>';
        }
    } elseif ($report_type === 'venue_report') {
        $report_title_text = "Venue Report - All Venues";
        $sql = "SELECT name, location, capacity, price, image FROM venues ORDER BY name";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $html .= '<h3>' . htmlspecialchars($report_title_text) . '</h3>';
            $html .= '<table border="1" cellpadding="5">';
            $html .= '<thead><tr><th>Name</th><th>Location</th><th>Capacity</th><th>Price</th><th>Image</th></tr></thead><tbody>';
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['location']) . '</td>';
                $html .= '<td align="center">' . htmlspecialchars($row['capacity']) . '</td>';
                $html .= '<td align="right">' . htmlspecialchars(number_format($row['price'], 2)) . '</td>';
                $html .= '<td align="center">';
                if ($row['image']) {
                    $img_path = __DIR__ . '/' . $row['image']; // Adjust path if needed
                    if (file_exists($img_path)) {
                        $html .= '<img src="' . $img_path . '" height="50">';
                    } else {
                        $html .= 'Image not found';
                    }
                } else {
                    $html .= 'No Image';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No venue data found.</p>';
        }
    }

    // Output the PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('admin_report_' . str_replace(' ', '_', strtolower($report_title_text)) . '_' . date('YmdHis') . '.pdf', 'D');
    exit();
}

//function to get event name
function getEventName($conn, $event_id) {
    $sql = "SELECT event_name FROM events WHERE ID = '$event_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['event_name'];
    }
    return "Unknown Event";
}

// Fetch all events for the event filter dropdown
$events_sql = "SELECT ID, event_name FROM events ORDER BY event_name";
$events_result = $conn->query($events_sql);
$events = [];
if ($events_result && $events_result->num_rows > 0) {
    $events = $events_result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #495057;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #007bff;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        h3 {
            color: #28a745;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        form {
            background-color: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #343a40;
        }
        form select, form button {
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            width: 100%;
        }
        form button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #0056b3;
        }
        #expense_filters {
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
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
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .variance {
            font-weight: bold;
        }
        .positive {
            color: green;
        }
        .negative {
            color: red;
        }
        .no-data {
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .back-link:hover {
            background-color: #5a6268;
        }
        .venue-image {
            max-width: 100px;
            height: auto;
            display: block;
            margin: 5px auto;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-chart-bar"></i> Generate Report</h2>

        <form method="post">
            <div class="mb-3">
                <label for="report_type" class="form-label"><i class="fas fa-list"></i> Report Type:</label>
                <select class="form-select" name="report_type" id="report_type">
                    <option value="">-- Select Report --</option>
                    <option value="expense_report">Expense Report</option>
                    <option value="vendor_report">Vendor Report</option>
                    <option value="venue_report">Venue Report</option>
                </select>
            </div>

            <div id="expense_filters" style="display: none;">
                <div class="mb-3">
                    <label for="event_id" class="form-label"><i class="fas fa-filter"></i> Filter by Event:</label>
                    <select class="form-select" name="event_id" id="event_id">
                        <option value="">-- All Events --</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo $event['ID']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" name="generate-report" class="btn btn-primary"><i class="fas fa-file-alt"></i> Generate Report</button>
        </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportTypeSelect = document.getElementById('report_type');
            const expenseFiltersDiv = document.getElementById('expense_filters');

            function hideAllFilters() {
                expenseFiltersDiv.style.display = 'none';
            }

            reportTypeSelect.addEventListener('change', function() {
                hideAllFilters();
                if (this.value === 'expense_report') {
                    expenseFiltersDiv.style.display = 'block';
                }
            });

            // Initially hide all filters
            hideAllFilters();
        });
    </script>
</body>
</html>