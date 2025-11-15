<?php
require('fpdf186/fpdf.php');
 
include 'conexion.php';

 
$sql = "SELECT id_prestamo, codigo_barras, id_usuario, fecha_inicio, fecha_fin, fecha_devolucion, estado 
        FROM prestamos";
$result = $conexion->query($sql);
 
$pdf = new FPDF();
$pdf->AddPage();
 
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Reporte de Prestamos',0,1,'C');
$pdf->Ln(10);
 
$pdf->SetFont('Arial','B',12);
$pdf->Cell(20,10,'ID',1,0,'C');
$pdf->Cell(40,10,'Cod. Barras',1,0,'C');
$pdf->Cell(30,10,'Usuario',1,0,'C');
$pdf->Cell(30,10,'Inicio',1,0,'C');
$pdf->Cell(30,10,'Fin',1,0,'C');
$pdf->Cell(30,10,'Devolucion',1,0,'C');
$pdf->Cell(20,10,'Estado',1,1,'C');
 
$pdf->SetFont('Arial','',10);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pdf->Cell(20,10,$row['id_prestamo'],1,0,'C');
        $pdf->Cell(40,10,$row['codigo_barras'],1,0,'C');
        $pdf->Cell(30,10,$row['id_usuario'],1,0,'C');
        $pdf->Cell(30,10,$row['fecha_inicio'],1,0,'C');
        $pdf->Cell(30,10,$row['fecha_fin'],1,0,'C');
        $pdf->Cell(30,10,($row['fecha_devolucion'] ? $row['fecha_devolucion'] : 'Pendiente'),1,0,'C');
        $pdf->Cell(20,10,$row['estado'],1,1,'C');
    }
} else {
    $pdf->Cell(0,10,'No hay registros',1,1,'C');
}
 
$conexion->close();
 
$pdf->Output('D', 'ReporteDePrestamos.pdf');
?>