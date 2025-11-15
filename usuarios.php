<?php
session_start();

// Requiere rol usuario
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'usuario') {
    header("Location: index.php");
    exit;
}

include 'conexion.php';

// Validar que el id_usuario esté correctamente en sesión
if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_usuario']) <= 0) {
    header("Location: index.php?msg=need_login");
    exit;
}
$id_usuario = intval($_SESSION['id_usuario']);

$mensaje = '';
$section = $_GET['s'] ?? 'catalogo';
$qstr = '';

// Manejo de POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Buscar libros disponibles
    if (isset($_POST['accion']) && $_POST['accion'] === 'buscar') {
        $section = 'catalogo';
        $qstr = trim($_POST['q'] ?? '');
    }

    // Solicitar préstamo
    if (isset($_POST['accion']) && $_POST['accion'] === 'solicitar') {
        $section = 'catalogo';
        $codigo = trim($_POST['codigo_barras'] ?? '');

        if ($codigo !== '') {
            $stmt = $conexion->prepare("SELECT `titulo` FROM `libros` WHERE `codigo_barras` = ? LIMIT 1");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                $mensaje = "❌ Código de libro no encontrado.";
            } else {
                $titulo = $res->fetch_assoc()['titulo'];
                $stmt->close();

                $chkUserTitle = $conexion->prepare("
                    SELECT COUNT(*) AS c
                    FROM `prestamos` p
                    JOIN `libros` l ON p.codigo_barras = l.codigo_barras
                    WHERE p.id_usuario = ? AND p.estado = 'Activo' AND l.titulo = ?
                ");
                $chkUserTitle->bind_param("is", $id_usuario, $titulo);
                $chkUserTitle->execute();
                $user_has_active_title = $chkUserTitle->get_result()->fetch_assoc()['c'] > 0;
                $chkUserTitle->close();

                if ($user_has_active_title) {
                    $mensaje = "⚠️ Ya tienes un préstamo activo de este título.";
                } else {
                    $chkCopy = $conexion->prepare("SELECT COUNT(*) AS c FROM `prestamos` WHERE codigo_barras = ? AND estado = 'Activo'");
                    $chkCopy->bind_param("s", $codigo);
                    $chkCopy->execute();
                    $copy_active = $chkCopy->get_result()->fetch_assoc()['c'] > 0;
                    $chkCopy->close();

                    if ($copy_active) {
                        $mensaje = "⚠️ Este ejemplar ya fue prestado.";
                    } else {
                        $fecha_inicio = date('Y-m-d');
                        $fecha_fin = date('Y-m-d', strtotime('+14 days'));
                        $estado = 'Activo';

                        $ins = $conexion->prepare("INSERT INTO `prestamos` (`codigo_barras`, `id_usuario`, `fecha_inicio`, `fecha_fin`, `fecha_devolucion`, `estado`) VALUES (?, ?, ?, ?, NULL, ?)");
                        $ins->bind_param("sisss", $codigo, $id_usuario, $fecha_inicio, $fecha_fin, $estado);
                        $mensaje = $ins->execute()
                            ? "✅ Préstamo solicitado correctamente."
                            : "❌ Error al solicitar préstamo: " . $ins->error;
                        $ins->close();
                    }
                }
            }
        } else {
            $mensaje = "❌ Código de barras no válido.";
        }
    }

    // Repetir préstamo
    if (isset($_POST['accion']) && $_POST['accion'] === 'repetir') {
        $section = 'historial';
        $idp = intval($_POST['id_prestamo'] ?? 0);

        $q = $conexion->prepare("SELECT l.titulo FROM `prestamos` p JOIN `libros` l ON p.codigo_barras = l.codigo_barras WHERE p.id_prestamo = ? AND p.id_usuario = ? LIMIT 1");
        $q->bind_param("ii", $idp, $id_usuario);
        $q->execute();
        $resq = $q->get_result();
        if ($resq->num_rows > 0) {
            $titulo = $resq->fetch_assoc()['titulo'];
            $q->close();

            $chkUserTitle = $conexion->prepare("
                SELECT COUNT(*) AS c
                FROM `prestamos` p
                JOIN `libros` l ON p.codigo_barras = l.codigo_barras
                WHERE p.id_usuario = ? AND p.estado = 'Activo' AND l.titulo = ?
            ");
            $chkUserTitle->bind_param("is", $id_usuario, $titulo);
            $chkUserTitle->execute();
            $user_has_active_title = $chkUserTitle->get_result()->fetch_assoc()['c'] > 0;
            $chkUserTitle->close();

            if ($user_has_active_title) {
                $mensaje = "⚠️ Ya tienes un préstamo activo de este título.";
            } else {
                $find = $conexion->prepare("
                    SELECT l.codigo_barras
                    FROM `libros` l
                    WHERE l.titulo = ?
                      AND NOT EXISTS (
                          SELECT 1 FROM `prestamos` p
                          WHERE p.codigo_barras = l.codigo_barras AND p.estado = 'Activo'
                      )
                    LIMIT 1
                ");
                $find->bind_param("s", $titulo);
                $find->execute();
                $resFind = $find->get_result();
                if ($resFind->num_rows > 0) {
                    $codigo_disponible = $resFind->fetch_assoc()['codigo_barras'];
                    $find->close();

                    $fecha_inicio = date('Y-m-d');
                    $fecha_fin = date('Y-m-d', strtotime('+14 days'));
                    $estado = 'Activo';

                    $ins = $conexion->prepare("INSERT INTO `prestamos` (`codigo_barras`, `id_usuario`, `fecha_inicio`, `fecha_fin`, `fecha_devolucion`, `estado`) VALUES (?, ?, ?, ?, NULL, ?)");
                    $ins->bind_param("sisss", $codigo_disponible, $id_usuario, $fecha_inicio, $fecha_fin, $estado);
                    $mensaje = $ins->execute()
                        ? "✅ Préstamo solicitado correctamente (copia automática)."
                        : "❌ Error al solicitar préstamo: " . $ins->error;
                    $ins->close();
                } else {
                    $find->close();
                    $mensaje = "⚠️ No hay copias disponibles de ese título.";
                }
            }
        } else {
            $q->close();
            $mensaje = "❌ Préstamo histórico no encontrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Usuario - Biblioteca</title>
  <link rel="stylesheet" href="usuarios.css">
</head>
<body>
  <div class="navbar">
    <h1>Panel de Usuario - Biblioteca</h1>
    <div class="nav-links">
      <a href="#" onclick="mostrarSeccion('catalogo')" class="<?= $section === 'catalogo' ? 'active' : '' ?>">Catálogo</a>
      <a href="#" onclick="mostrarSeccion('activos')" class="<?= $section === 'activos' ? 'active' : '' ?>">Préstamos Activos</a>
      <a href="#" onclick="mostrarSeccion('historial')" class="<?= $section === 'historial' ? 'active' : '' ?>">Historial</a>
      <span class="logout" onclick="location.href='index.php'">Cerrar sesión</span>
    </div>
  </div>

  <div class="panel-content">
    <?php if ($mensaje): ?>
      <div class="mensaje"><?= $mensaje ?></div>
    <?php endif; ?>

    <div id="catalogo" class="section <?= $section === 'catalogo' ? 'active' : '' ?>">
      <h2>Catálogo de libros</h2>
      <form method="POST">
        <input type="hidden" name="accion" value="buscar">
        <input type="text" name="q" placeholder="Buscar por código, título, autor..." value="<?= htmlspecialchars($qstr) ?>">
        <button type="submit">Buscar</button>
      </form>
      <?php
      if ($qstr !== '') {
        $like = "%$qstr%";
        $sql = "SELECT l.* FROM libros l
                WHERE (l.codigo_barras LIKE ? OR l.titulo LIKE ? OR l.autor LIKE ? OR l.isbn LIKE ?)
                  AND NOT EXISTS (
                    SELECT 1 FROM prestamos p
                    WHERE p.codigo_barras = l.codigo_barras AND p.estado = 'Activo'
                  )
                LIMIT 200";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssss", $like, $like, $like, $like);
      } else {
        $sql = "SELECT l.* FROM libros l
                WHERE NOT EXISTS (
                  SELECT 1 FROM prestamos p
                  WHERE p.codigo_barras = l.codigo_barras AND p.estado = 'Activo'
                )
                LIMIT 200";
        $stmt = $conexion->prepare($sql);
      }

      $stmt->execute();
      $res = $stmt->get_result();
      if ($res->num_rows === 0) {
        echo "<p>No se encontraron libros disponibles.</p>";
      } else {
        echo "<div class='tabla-libros'><table><thead><tr>
          <th>Código</th><th>Título</th><th>Autor</th><th>Editorial</th><th>Material</th><th>Estatus</th><th>Acción</th>
          </tr></thead><tbody>";
        while ($row = $res->fetch_assoc()) {
          echo "<tr>
            <td class='num'>".htmlspecialchars($row['codigo_barras'])."</td>
            <td>".htmlspecialchars($row['titulo'])."</td>
            <td>".htmlspecialchars($row['autor'])."</td>
            <td>".htmlspecialchars($row['editorial'])."</td>
            <td>".htmlspecialchars($row['material'])."</td>
            <td>".htmlspecialchars($row['estatus'])."</td>
            <td>
              <form method='POST' style='display:inline'>
                <input type='hidden' name='accion' value='solicitar'>
                <input type='hidden' name='codigo_barras' value='".htmlspecialchars($row['codigo_barras'], ENT_QUOTES)."'>
                <button type='submit'>Solicitar préstamo</button>
              </form>
            </td>
          </tr>";
        }
        echo "</tbody></table></div>";
      }
      $stmt->close();
      ?>
    </div>

    <div id="activos" class="section <?= $section === 'activos' ? 'active' : '' ?>">
      <h2>Mis préstamos activos</h2>
      <?php
      $stmt = $conexion->prepare("SELECT p.*, l.titulo, l.autor FROM prestamos p LEFT JOIN libros l ON p.codigo_barras = l.codigo_barras WHERE p.id_usuario = ? AND p.estado = 'Activo' ORDER BY p.fecha_inicio DESC");
      $stmt->bind_param("i", $id_usuario);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res->num_rows === 0) {
        echo "<p>No tienes préstamos activos.</p>";
      } else {
        echo "<div class='tabla-libros'><table><thead><tr>
          <th>#</th><th>Código</th><th>Título</th><th>Autor</th><th>Inicio</th><th>Fin</th><th>Estado</th>
          </tr></thead><tbody>";
        while ($row = $res->fetch_assoc()) {
          echo "<tr>
            <td class='num'>".htmlspecialchars($row['id_prestamo'])."</td>
            <td class='num'>".htmlspecialchars($row['codigo_barras'])."</td>
            <td>".htmlspecialchars($row['titulo'])."</td>
            <td>".htmlspecialchars($row['autor'])."</td>
            <td>".htmlspecialchars($row['fecha_inicio'])."</td>
            <td>".htmlspecialchars($row['fecha_fin'])."</td>
            <td>".htmlspecialchars($row['estado'])."</td>
          </tr>";
        }
        echo "</tbody></table></div>";
      }
      $stmt->close();
      ?>
    </div>

    <div id="historial" class="section <?= $section === 'historial' ? 'active' : '' ?>">
      <h2>Historial de préstamos</h2>
      <?php
      $stmt = $conexion->prepare("SELECT p.*, l.titulo, l.autor FROM prestamos p LEFT JOIN libros l ON p.codigo_barras = l.codigo_barras WHERE p.id_usuario = ? ORDER BY p.fecha_inicio DESC LIMIT 500");
      $stmt->bind_param("i", $id_usuario);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res->num_rows === 0) {
        echo "<p>No tienes historial de préstamos.</p>";
      } else {
        echo "<div class='tabla-libros'><table><thead><tr>
          <th>#</th><th>Código</th><th>Título</th><th>Autor</th><th>Inicio</th><th>Devolución</th><th>Estado</th><th>Acción</th>
          </tr></thead><tbody>";
        while ($row = $res->fetch_assoc()) {
          echo "<tr>
            <td class='num'>".htmlspecialchars($row['id_prestamo'])."</td>
            <td class='num'>".htmlspecialchars($row['codigo_barras'])."</td>
            <td>".htmlspecialchars($row['titulo'])."</td>
            <td>".htmlspecialchars($row['autor'])."</td>
            <td>".htmlspecialchars($row['fecha_inicio'])."</td>
            <td>".htmlspecialchars($row['fecha_devolucion'])."</td>
            <td>".htmlspecialchars($row['estado'])."</td>
            <td>
              <form method='POST' style='display:inline'>
                <input type='hidden' name='accion' value='repetir'>
                <input type='hidden' name='id_prestamo' value='".htmlspecialchars($row['id_prestamo'], ENT_QUOTES)."'>
                <button type='submit'>Pedir otra vez</button>
              </form>
            </td>
          </tr>";
        }
        echo "</tbody></table></div>";
      }
      $stmt->close();
      ?>
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