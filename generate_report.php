<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';


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

$client_id = $_SESSION['user_id'];

// Fetch the client's events and their expenses for the report 
$sql = "SELECT
            ev.event_name,
            ev.date,
            ev.type,
            ev.budget AS event_budget,
            e.category AS expense_category,
            e.budget_limit AS expense_budget_limit,
            te.actual_cost AS expense_actual_cost,
            te.description AS expense_description
        FROM events ev
        LEFT JOIN expenses e ON ev.ID = e.event_id
        LEFT JOIN tracked_expenses te ON e.event_id = te.event_id AND e.category = te.category
        WHERE ev.client_id = '$client_id'
        ORDER BY ev.event_name, e.category";

$result = $conn->query($sql);
$client_report_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $client_report_data[] = $row;
    }
}


// Create a new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Company Name');
$pdf->SetTitle('Client Event Report');
$pdf->SetSubject('Event Report');
$pdf->SetKeywords('event, report, client');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Client Event Report', $_SESSION['username'] ?? '', array(0,64,0), array(0,0,0));
$pdf->setFooterData(array(0,64,0), array(0,0,0));

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
$pdf->SetFont('helvetica', '', 10, '', true);

// Add a page
$pdf->AddPage();

// Content for the PDF
$html = '<h1>Client Event Report for ' . htmlspecialchars($_SESSION['username'] ?? 'Guest') . '</h1>';
$html .= '<p>Generated on: ' . date('Y-m-d') . '</p>';

if (!empty($client_report_data)) {
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Event Name</th>';
    $html .= '<th>Date</th>';
    $html .= '<th>Type</th>';
    $html .= '<th>Event Budget</th>';
    $html .= '<th>Expense Category</th>';
    $html .= '<th>Expense Budget</th>';
    $html .= '<th>Expense Actual Cost</th>';
    $html .= '<th>Expense Variance</th>';
    $html .= '<th>Expense Description</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    $current_event = null;
    foreach ($client_report_data as $row) {
        if ($row['event_name'] !== $current_event) {
            if ($current_event !== null) {
                $html .= '<tr><td colspan="9">&nbsp;</td></tr>';
            }
            $html .= '<tr>';
            $html .= '<td style="font-weight: bold;">' . htmlspecialchars($row['event_name']) . '</td>';
            $html .= '<td style="font-weight: bold;">' . htmlspecialchars($row['date']) . '</td>';
            $html .= '<td style="font-weight: bold;">' . htmlspecialchars($row['type']) . '</td>';
            $html .= '<td style="font-weight: bold;">' . htmlspecialchars(number_format($row['event_budget'], 2)) . '</td>';
            $html .= '<td colspan="5"></td>';
            $html .= '</tr>';
            $current_event = $row['event_name'];
        }
        $budget_limit = $row['expense_budget_limit'];
        $actual_cost = $row['expense_actual_cost'];
        $variance = ($budget_limit ?? 0) - ($actual_cost ?? 0);

        $html .= '<tr>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '<td>' . htmlspecialchars($row['expense_category'] ?? 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars(($budget_limit !== null) ? number_format($budget_limit, 2) : 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars(($actual_cost !== null) ? number_format($actual_cost, 2) : 'N/A') . '</td>';
        $variance_class = $variance >= 0 ? 'positive' : 'negative';
        $html .= '<td class="' . $variance_class . '">' . htmlspecialchars(number_format($variance, 2)) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['expense_description'] ?? 'N/A') . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table>';
} else {
    $html .= '<p>No event data available for the report.</p>';
}

// Output the PDF to the browser (force download)
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('client_event_report.pdf', 'D'); // 'D' forces download

// Close the database connection
$conn->close();
?>