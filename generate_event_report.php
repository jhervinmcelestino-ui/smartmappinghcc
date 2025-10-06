<?php
session_start();
require 'db.php';
require('libs/fpdf.php');

// Check if user is allowed
$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || ($role !== 'admin' && $role !== 'officer')) {
    die("Unauthorized access.");
}

// Custom PDF class para maging organized ang code
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Smart Mapping - Approved Event Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, "Status: Approved", 0, 1, 'C');
        $this->Ln(5);

        // Table Header (Adjusted widths for landscape A4: 280mm total width)
        $this->SetFont('Arial', 'B', 9); // Use smaller font for more columns
        $this->SetFillColor(230, 230, 230);
        
        // Use '1' as the border parameter here for the header
        $this->Cell(45, 8, 'Title', 1, 0, 'C', true);          // 45mm
        $this->Cell(45, 8, 'Organization', 1, 0, 'C', true);   // 45mm
        $this->Cell(25, 8, 'Attendees', 1, 0, 'C', true);    // 25mm
        $this->Cell(35, 8, 'Venue', 1, 0, 'C', true);        // 35mm
        $this->Cell(30, 8, 'Date', 1, 0, 'C', true);         // 30mm
        $this->Cell(25, 8, 'Start Time', 1, 0, 'C', true);   // 25mm
        $this->Cell(25, 8, 'End Time', 1, 0, 'C', true);     // 25mm
        $this->Cell(30, 8, 'Type', 1, 0, 'C', true);         // 30mm
        $this->Cell(20, 8, 'Status', 1, 1, 'C', true);       // 20mm
        
        $this->SetFont('Arial', '', 8); // Smaller font for data
        $this->SetFillColor(255, 255, 255);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Function to handle the table rows with MultiCell for long text
    function EventTable($header, $data) {
        // Data
        foreach ($data as $row) {
            // Using a fixed height for simplicity.
            $rowHeight = 8; 
            
            // Format Times
            $startTime = !empty($row['event_time']) ? date('h:i A', strtotime($row['event_time'])) : 'N/A';
            $endTime = !empty($row['end_time']) ? date('h:i A', strtotime($row['end_time'])) : 'N/A';
            
            $this->SetFont('Arial', '', 8); // Ensure data font is set
            
            // NOTE: Changed border parameter from 'LR' to '1' for full grid lines.
            // Title
            $this->Cell(45, $rowHeight, $this->truncateText($row['title'], 25), 1, 0, 'L');
            // Organization
            $this->Cell(45, $rowHeight, $this->truncateText($row['organization'], 25), 1, 0, 'L');
            // Attendees
            $this->Cell(25, $rowHeight, $row['estimated_attendees'] ?? 'N/A', 1, 0, 'C');
            // Venue
            $this->Cell(35, $rowHeight, $this->truncateText($row['venue'], 20), 1, 0, 'L');
            // Date
            $this->Cell(30, $rowHeight, date('M d, Y', strtotime($row['event_date'])), 1, 0, 'C');
            // Start Time
            $this->Cell(25, $rowHeight, $startTime, 1, 0, 'C');
            // End Time
            $this->Cell(25, $rowHeight, $endTime, 1, 0, 'C');
            // Type
            $this->Cell(30, $rowHeight, $this->truncateText($row['event_type'], 15), 1, 0, 'L');
            // Status
            $this->Cell(20, $rowHeight, $row['status'] ?? 'N/A', 1, 1, 'C'); // '1' moves to the next line
        }
        // Removed: $this->Cell(0, 0, '', 'T', 1); because '1' border handles the bottom line of the last row.
    }

    // A helper function to truncate long text
    function truncateText($text, $maxLength) {
        if (strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength - 3) . '...';
        }
        return $text;
    }
}

$pdf = new PDF('L', 'mm', 'A4'); // 'L' for Landscape orientation
$pdf->AliasNbPages();
$pdf->AddPage();

// Get events
$status = 'Approved';
$query = "SELECT * FROM events WHERE status = ? ORDER BY event_date";
$stmt = $pdo->prepare($query);
$stmt->execute([$status]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($events)) {
    // Generate the table
    $pdf->EventTable([], $events);
} else {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'No approved events found.', 0, 1, 'C');
}

// Signatories section (Previous error fix is maintained)
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Prepared by:', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, '_________________________', 0, 1);
$pdf->SetX(30); 
$pdf->Cell(0, 8, 'Event Officer', 0, 1);

$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Checked by:', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, '_________________________', 0, 1);
$pdf->SetX(30); 
$pdf->Cell(0, 8, 'Admin', 0, 1);

$pdf->Output('D', 'approved_event_report.pdf');
exit;
?>