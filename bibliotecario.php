<?php
session_start();
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'bibliotecario') {
  header("Location: index.php");
  exit;
}

include 'conexion.php';
$section = $_GET['s'] ?? 'prestamos';
$mensaje = '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel del Bibliotecario</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>

  <div class="navbar">
    <h1>Panel del Bibliotecario - Biblioteca CIMA</h1>
    <div class="nav-links">
      <a href="#" onclick="mostrarSeccion('prestamos')" class="<?= $section === 'prestamos' ? 'active' : '' ?>">Préstamos activos</a>
      <a href="#" onclick="mostrarSeccion('historial')" class="<?= $section === 'historial' ? 'active' : '' ?>">Historial</a>
      <a href="#" onclick="mostrarSeccion('usuarios')" class="<?= $section === 'usuarios' ? 'active' : '' ?>">Usuarios</a>
      <a href="#" onclick="mostrarSeccion('catalogo')" class="<?= $section === 'catalogo' ? 'active' : '' ?>">Catálogo</a>
      <span class="logout" onclick="location.href='index.php'">Cerrar sesión</span>
    </div>
  </div>

  <div class="panel-content">

    <?php if ($mensaje): ?>
      <div class="mensaje"><?= $mensaje ?></div>
    <?php endif; ?>

    <div id="prestamos" class="section <?= $section === 'prestamos' ? 'active' : '' ?>">
      <h2>Préstamos activos</h2>
      <div class="tabla-libros">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Libro</th><th>Usuario</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $sql = "SELECT p.*, l.titulo, u.nombre_usuario 
                  FROM prestamos p
                  JOIN libros l ON p.codigo_barras = l.codigo_barras
                  JOIN usuarios u ON p.id_usuario = u.id_usuario
                  WHERE p.estado = 'Activo'
                  ORDER BY p.fecha_inicio DESC";
          $res = $conexion->query($sql);
          if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
              echo "<tr>
                <td class='num'>{$row['id_prestamo']}</td>
                <td>".htmlspecialchars($row['titulo'])."</td>
                <td>".htmlspecialchars($row['nombre_usuario'])."</td>
                <td>{$row['fecha_inicio']}</td>
                <td>{$row['fecha_fin']}</td>
                <td>{$row['estado']}</td>
                <td>
                  <form method='POST' action='finalizar_prestamo.php' style='display:inline'>
                    <input type='hidden' name='id' value='{$row['id_prestamo']}'>
                    <button type='submit'>Finalizar</button>
                  </form>
                </td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='7'>No hay préstamos activos.</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="historial" class="section <?= $section === 'historial' ? 'active' : '' ?>">
      <h2>Historial de préstamos</h2>
      <div class="tabla-libros">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Libro</th><th>Usuario</th><th>Inicio</th><th>Fin</th><th>Devolución</th><th>Estado</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $sql = "SELECT p.*, l.titulo, u.nombre_usuario 
                  FROM prestamos p
                  JOIN libros l ON p.codigo_barras = l.codigo_barras
                  JOIN usuarios u ON p.id_usuario = u.id_usuario
                  WHERE p.estado = 'Finalizado'
                  ORDER BY p.fecha_inicio DESC";
          $res = $conexion->query($sql);
          if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
              echo "<tr>
                <td class='num'>{$row['id_prestamo']}</td>
                <td>".htmlspecialchars($row['titulo'])."</td>
                <td>".htmlspecialchars($row['nombre_usuario'])."</td>
                <td>{$row['fecha_inicio']}</td>
                <td>{$row['fecha_fin']}</td>
                <td>{$row['fecha_devolucion']}</td>
                <td>{$row['estado']}</td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='7'>No hay préstamos finalizados aún.</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="usuarios" class="section <?= $section === 'usuarios' ? 'active' : '' ?>">
      <h2>Usuarios registrados</h2>
      <div class="tabla-libros">
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Fecha registro</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $res = $conexion->query("SELECT * FROM usuarios WHERE rol = 'Usuario' ORDER BY id_usuario ASC");
          if ($res && $res->num_rows > 0) {
            while ($u = $res->fetch_assoc()) {
              echo "<tr>
                <td class='num'>{$u['id_usuario']}</td>
                <td>".htmlspecialchars($u['nombre_usuario'])."</td>
                <td>".htmlspecialchars($u['correo'])."</td>
                <td>{$u['rol']}</td>
                <td>{$u['fecha_registro']}</td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='5'>No hay usuarios registrados.</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="catalogo" class="section <?= $section === 'catalogo' ? 'active' : '' ?>">
      <h2>Catálogo de libros</h2>
      <form method="GET">
        <input type="hidden" name="s" value="catalogo">
        <input type="text" name="q" placeholder="Buscar por código, título o autor..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        <button type="submit">Buscar</button>
      </form>
      <div class="tabla-libros">
        <table>
          <thead>
            <tr>
              <th>Código</th><th>Título</th><th>Autor</th><th>Editorial</th><th>Material</th><th>Estatus</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $q = trim($_GET['q'] ?? '');
          if ($q !== '') {
            $like = "%$q%";
            $stmt = $conexion->prepare("SELECT * FROM libros WHERE codigo_barras LIKE ? OR titulo LIKE ? OR autor LIKE ? LIMIT 200");
            $stmt->bind_param("sss", $like, $like, $like);
            $stmt->execute();
            $res = $stmt->get_result();
          } else {
            $res = $conexion->query("SELECT * FROM libros LIMIT 200");
          }

          if ($res && $res->num_rows > 0) {
            while ($l = $res->fetch_assoc()) {
              echo "<tr>
                <td class='num'>{$l['codigo_barras']}</td>
                <td>".htmlspecialchars($l['titulo'])."</td>
                <td>".htmlspecialchars($l['autor'])."</td>
                <td>".htmlspecialchars($l['editorial'])."</td>
                <td>".htmlspecialchars($l['material'])."</td>
                <td>".htmlspecialchars($l['estatus'])."</td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='6'>No se encontraron libros.</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script>
    function mostrarSeccion(id) {
      document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
      document.getElementById(id).classList.add('active');

      document.querySelectorAll('.nav-links a').forEach(link => link.classList.remove('active'));
      document.querySelector(`.nav-links a[onclick="mostrarSeccion('${id}')"]`)?.classList.add('active');
    }
    window.addEventListener('DOMContentLoaded', () => {
      mostrarSeccion('<?= $section ?>');
    });
  </script>

</body>
</html>
