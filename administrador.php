<?php
session_start();
if ($_SESSION['rol'] !== 'Administrador') {
  header("Location: index.php");
  exit;
}

include 'conexion.php';
$mensaje = "";
$seccionActiva = $_GET['s'] ?? 'bibliotecarios';$rows = [];
  $mensaje_catalogo = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["archivo_csv"])) {
  $seccionActiva = 'excel';
  $archivo = $_FILES["archivo_csv"]["tmp_name"];
  $insertados = 0;
  $errores = 0;

  if (($gestor = fopen($archivo, "r")) !== FALSE) {
    $fila = 0;
    while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
      $rows[] = $datos;

      if ($fila === 0) {
        $fila++;
        continue;
      }

      $codigo_barras  = $datos[0] ?? '';
      $sistema        = $datos[1] ?? '';
      $titulo         = $datos[2] ?? '';
      $autor          = $datos[3] ?? '';
      $editorial      = $datos[4] ?? '';
      $biblioteca     = $datos[5] ?? '';
      $fecha_ingreso  = $datos[6] ?? '';
      $acervo         = $datos[7] ?? '';
      $clasificacion  = $datos[8] ?? '';
      $material       = $datos[9] ?? '';
      $estatus        = $datos[10] ?? '';
      $año            = $datos[11] ?? '';
      $isbn           = $datos[12] ?? '';

      if ($codigo_barras && $titulo) {
        $sql = "INSERT INTO libros (
          codigo_barras, sistema, titulo, autor, editorial, biblioteca,
          fecha_ingreso, acervo, clasificacion, material, estatus, año, isbn
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
          "sssssssssssis",
          $codigo_barras, $sistema, $titulo, $autor, $editorial, $biblioteca,
          $fecha_ingreso, $acervo, $clasificacion, $material, $estatus, $año, $isbn
        );

        if ($stmt->execute()) {
          $insertados++;
        } else {
          $errores++;
        }

        $stmt->close();
      } else {
        $errores++;
      }

      $fila++;
    }

    fclose($gestor);

    if ($insertados > 0) {
      $mensaje .= "Se guardaron <strong>$insertados</strong> libros en la base de datos.<br>";
    }

    if ($errores > 0) {
      $mensaje .= "Se omitieron <strong>$errores</strong> registros por errores o datos incompletos.";
    }
  } else {
    $mensaje = "No se pudo abrir el archivo.";
  }
}

$mensaje_bibliotecario = "";
$busqueda = $_GET['buscar_bibliotecario'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion_bibliotecario'])) {
  $accion = $_POST['accion_bibliotecario'];
  $id = $_POST['id_usuario'] ?? null;
  $nombre = $_POST['nombre_usuario'] ?? '';
  $correo = $_POST['correo'] ?? '';
  $contraseña = $_POST['contraseña'] ?? '';

  if ($accion === 'agregar' && $nombre && $correo && $contraseña) {
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre_usuario, correo, contraseña, rol) VALUES (?, ?, ?, 'Bibliotecario')");
    $stmt->bind_param("sss", $nombre, $correo, $contraseña);
    $stmt->execute();
    $mensaje_bibliotecario = "Bibliotecario agregado correctamente.";
    $stmt->close();
  }

  if ($accion === 'actualizar' && $id) {
    $stmt = $conexion->prepare("UPDATE usuarios SET nombre_usuario=?, correo=?, contraseña=? WHERE id_usuario=? AND rol='Bibliotecario'");
    $stmt->bind_param("sssi", $nombre, $correo, $contraseña, $id);
    $stmt->execute();
    $mensaje_bibliotecario = "Bibliotecario actualizado.";
    $stmt->close();
  }

  if ($accion === 'eliminar' && $id) {
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario=? AND rol='Bibliotecario'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $mensaje_bibliotecario = "Bibliotecario eliminado.";
    $stmt->close();
  }
  
}



$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE rol='Bibliotecario' AND (nombre_usuario LIKE ? OR correo LIKE ?) ORDER BY nombre_usuario");
$like = "%$busqueda%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$resultado_bibliotecarios = $stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion_libro'])) {
  $accion = $_POST['accion_libro'];
  $codigo_barras = $_POST['codigo_barras'] ?? '';
  $titulo = $_POST['titulo'] ?? '';
  $autor = $_POST['autor'] ?? '';
  $editorial = $_POST['editorial'] ?? '';
  $material = $_POST['material'] ?? '';
  $estatus = $_POST['estatus'] ?? '';

  if ($accion === 'agregar' && $codigo_barras && $titulo) {
    $stmt = $conexion->prepare("INSERT INTO libros (codigo_barras, titulo, autor, editorial, material, estatus) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $codigo_barras, $titulo, $autor, $editorial, $material, $estatus);
    $stmt->execute();
    $mensaje_catalogo = "📘 Libro agregado correctamente.";
    $stmt->close();
    $section = 'catalogo';
  }

  if ($accion === 'eliminar' && $codigo_barras) {
    $stmt = $conexion->prepare("DELETE FROM libros WHERE codigo_barras=?");
    $stmt->bind_param("s", $codigo_barras);
    $stmt->execute();
    $mensaje_catalogo = "🗑️ Libro eliminado correctamente.";
    $stmt->close();
    $section = 'catalogo';
  }
}

?>



<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Administración</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>

  <div class="navbar">
    <h1>Biblioteca Web - Centro de Investigación en Matemáticas Aplicadas</h1>
    <div class="nav-links">
      <a href="#" onclick="mostrarSeccion('bibliotecarios')">📚 Bibliotecarios</a>
      <a href="#" onclick="mostrarSeccion('excel')">📥 Cargar Excel</a>
      <a href="#" onclick="mostrarSeccion('usuarios')">👥 Préstamos</a>
      <a href="#" onclick="mostrarSeccion('catalogo')" class="<?= $section === 'catalogo' ? 'active' : '' ?>">📖 Catálogo</a>
      <span class="logout" onclick="location.href='index.php'">Cerrar sesión</span>
    </div>
  </div>

  <div class="panel-content">
    <div id="bibliotecarios" class="section <?= $seccionActiva === 'bibliotecarios' ? 'active' : '' ?>">
      <h2>Agregar o Eliminar Bibliotecarios</h2>
      
      <?php if ($mensaje_bibliotecario): ?>
    <div class="mensaje"><?= $mensaje_bibliotecario ?></div>
  <?php endif; ?>

  <form method="GET" style="margin-bottom: 1em;">
    <input type="text" name="buscar_bibliotecario" placeholder="Buscar bibliotecario..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit"> Buscar</button>
  </form>

  <form method="POST" class="form-inline">
    <input type="hidden" name="accion_bibliotecario" value="agregar">
    <input type="text" name="nombre_usuario" placeholder="Nombre completo" required>
    <input type="email" name="correo" placeholder="Correo electrónico" required>
    <input type="text" name="contraseña" placeholder="Contraseña" required>
    <button type="submit"> Agregar</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>ID</th><th>Nombre</th><th>Correo</th><th>Contraseña</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($b = $resultado_bibliotecarios->fetch_assoc()): ?>
        <tr>
          <form method="POST">
            <td class="num"><?= $b['id_usuario'] ?></td>
            <td><input type="text" name="nombre_usuario" value="<?= htmlspecialchars($b['nombre_usuario']) ?>"></td>
            <td><input type="email" name="correo" value="<?= htmlspecialchars($b['correo']) ?>"></td>
            <td><input type="text" name="contraseña" value="<?= htmlspecialchars($b['contraseña']) ?>"></td>
            <td>
              <input type="hidden" name="id_usuario" value="<?= $b['id_usuario'] ?>">
              <button type="submit" name="accion_bibliotecario" value="actualizar"> Guardar Modificación </button>
              <button type="submit" name="accion_bibliotecario" value="eliminar" onclick="return confirm('¿Eliminar bibliotecario?')"> Eliminar </button>
            </td>
          </form>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>


    <div id="excel" class="section <?= $seccionActiva === 'excel' ? 'active' : '' ?>">
      <h2>Cargar Archivo Excel de Libros</h2>

      <?php if (!empty($mensaje)): ?>
        <div class="mensaje"><?= $mensaje ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <label for="archivo_csv">Selecciona archivo CSV:</label>
        <input type="file" name="archivo_csv" accept=".csv" required>
        <button type="submit">Subir e insertar</button>
      </form>

      <?php if ($rows): ?>
        <div class="tabla-libros">
          <h3>📘 Vista previa del archivo</h3>
          <table>
            <thead>
              <tr>
                <?php foreach ($rows[0] as $col): ?>
                  <th><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php for ($i = 1; $i < count($rows); $i++): ?>
                <tr>
                  <?php foreach ($rows[$i] as $cell): ?>
                    <?php $cellOut = htmlspecialchars($cell); ?>
                    <?php if (is_numeric(str_replace([' ', ','], ['',''], $cell))): ?>
                      <td class="num"><?= $cellOut ?></td>
                    <?php else: ?>
                      <td><?= $cellOut ?></td>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div id="usuarios" class="section <?= $seccionActiva === 'usuarios' ? 'active' : '' ?>">
      <h2>Lista de Préstamos Activos</h2>
      

<form method="POST" action="generar_reporte.php" style="margin-bottom: 1em;">
  <button type="submit">Descargar reporte PDF</button>
</form>

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
  </div>

<div id="catalogo" class="section <?= $seccionActiva === 'catalogo' ? 'active' : '' ?>">  <h2>📚 Catálogo de Libros</h2>

  <?php if (!empty($mensaje_catalogo)): ?>
    <div class="mensaje"><?= $mensaje_catalogo ?></div>
  <?php endif; ?>

  <form method="GET" action="administrador.php" style="margin-bottom: 1em;">
  <input type="hidden" name="s" value="catalogo">
  <input type="text" name="q" placeholder="Buscar por código, título o autor..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
  <button type="submit">Buscar</button>
</form>

  <form method="POST" action="?s=catalogo" class="form-inline" style="margin-bottom: 1em;">
    <input type="hidden" name="accion_libro" value="agregar">
    <input type="text" name="codigo_barras" placeholder="Código de barras" required>
    <input type="text" name="titulo" placeholder="Título" required>
    <input type="text" name="autor" placeholder="Autor">
    <input type="text" name="editorial" placeholder="Editorial">
    <input type="text" name="material" placeholder="Material">
    <input type="text" name="estatus" placeholder="Estatus">
    <button type="submit"> Agregar libro</button>
  </form>

  <div class="tabla-libros">
    <table>
      <thead>
        <tr>
          <th>Código</th><th>Título</th><th>Autor</th><th>Editorial</th><th>Material</th><th>Estatus</th><th>Acción</th>
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
          $res = $conexion->query("SELECT * FROM libros LIMIT 300");
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
              <td>
                <form method='POST' action='?s=catalogo' style='display:inline'>
                  <input type='hidden' name='accion_libro' value='eliminar'>
                  <input type='hidden' name='codigo_barras' value='{$l['codigo_barras']}'>
                  <button type='submit' onclick=\"return confirm('¿Eliminar este libro?')\">Eliminar Libro</button>
                </form>
              </td>
            </tr>";
          }
        } else {
          echo "<tr><td colspan='7'>No se encontraron libros.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

  <script>
    function mostrarSeccion(id) {
      document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
      document.getElementById(id).classList.add('active');
    }
  </script>

</body>
</html>