<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $sql = "UPDATE prestamos SET estado = 'Finalizado', fecha_devolucion = CURDATE() WHERE id_prestamo = ?";
  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

header("Location: bibliotecario.php?s=prestamos");
exit;
?>