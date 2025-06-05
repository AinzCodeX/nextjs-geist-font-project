<?php
require_once 'db.php';
require_once __DIR__ . '/fpdf/fpdf.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('ID transaksi tidak valid.');
}

// Fetch transaction with customer and playstation info
$stmt = $pdo->prepare('
    SELECT t.*, c.name AS customer_name, c.contact_info, p.name AS playstation_name, p.price_per_hour
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    JOIN playstations p ON t.playstation_id = p.id
    WHERE t.id = ? AND t.status = "paid"
');
$stmt->execute([$id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    die('Transaksi tidak ditemukan atau belum dibayar.');
}

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Times','B',16);
        $this->Cell(0,10,'Bukti Pembayaran Rental PlayStation',0,1,'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Times','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Times','',12);
$pdf->Cell(50,10,'ID Bukti:',0,0);
$pdf->Cell(0,10,$transaction['id'],0,1);

$pdf->Cell(50,10,'Nama Pelanggan:',0,0);
$pdf->Cell(0,10,$transaction['customer_name'],0,1);

$pdf->Cell(50,10,'Informasi Kontak:',0,0);
$pdf->Cell(0,10,$transaction['contact_info'],0,1);

$pdf->Cell(50,10,'PlayStation:',0,0);
$pdf->Cell(0,10,$transaction['playstation_name'],0,1);

$pdf->Cell(50,10,'Harga Per Jam:',0,0);
$pdf->Cell(0,10,'Rp '.number_format($transaction['price_per_hour'], 0, ',', '.'),0,1);

$pdf->Cell(50,10,'Waktu Mulai:',0,0);
$pdf->Cell(0,10,$transaction['start_time'],0,1);

$pdf->Cell(50,10,'Waktu Selesai:',0,0);
$pdf->Cell(0,10,$transaction['end_time'],0,1);

$pdf->Cell(50,10,'Total Harga:',0,0);
$pdf->Cell(0,10,'Rp '.number_format($transaction['total_price'], 0, ',', '.'),0,1);

$pdf->Cell(50,10,'Status:',0,0);
$pdf->Cell(0,10,ucfirst($transaction['status']),0,1);

$pdf->Cell(50,10,'Tanggal:',0,0);
$pdf->Cell(0,10,$transaction['created_at'],0,1);

$pdf->Ln(10);
$pdf->Cell(0,10,'Terima kasih atas pembayaran Anda!',0,1,'C');

$pdf->Output('I', 'bukti_'.$transaction['id'].'.pdf');
?>
