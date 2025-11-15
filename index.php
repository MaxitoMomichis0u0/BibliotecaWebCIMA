<?php
session_start();
include 'conexion.php';
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == "login") {
  $correo = $_POST['usuario'] ?? '';
  $contraseña = $_POST['contraseña'] ?? '';

  $sql = "SELECT * FROM usuarios WHERE correo = ? AND contraseña = ?";
  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("ss", $correo, $contraseña);
  $stmt->execute();
  $resultado = $stmt->get_result();

  if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    $_SESSION['usuario'] = $usuario['nombre_usuario'];
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['rol'] = $usuario['rol'];

    switch (strtolower($usuario['rol'])) {
      case 'administrador':
        header("Location: administrador.php");
        exit;
      case 'usuario':
        header("Location: usuarios.php");
        exit;
      case 'bibliotecario':
        header("Location: bibliotecario.php");
        exit;
      default:
        $mensaje = "⚠️ Rol desconocido";
    }
  } else {
    $mensaje = "❌ Credenciales incorrectas";
  }

  $stmt->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == "registro") {
  $nombre = $_POST['nombre'] ?? '';
  $correo = $_POST['correo'] ?? '';
  $contraseña = $_POST['contraseña'] ?? '';
  $rol = $_POST['rol'] ?? '';

  if ($nombre && $correo && $contraseña && $rol) {
    $sql = "INSERT INTO usuarios (nombre_usuario, correo, contraseña, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $correo, $contraseña, $rol);

    if ($stmt->execute()) {
      $mensaje = "✅ Usuario registrado correctamente";
    } else {
      $mensaje = "❌ Error al registrar: " . $stmt->error;
    }

    $stmt->close();
  } else {
    $mensaje = "⚠️ Todos los campos son obligatorios";
  }
}

// Control de visibilidad
$mostrarLogin = true;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
  $mostrarLogin = $_POST['accion'] == "login";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="estilos.css">
  <script src="script.js" defer></script>
  <title>Login Biblioteca</title>
</head>
<body>

<!-- LOGIN -->
<div class="login-container" id="form-login" style="display: <?= $mostrarLogin ? 'block' : 'none' ?>; width: 500px; margin-left: auto; margin-right: 150px; margin-top: 118px;">
  <h2>
    <strong>Sistema Web de Biblioteca</strong><br>
    Centro de Investigación en Matemáticas Aplicadas<br><br>
    Iniciar Sesión
  </h2>

  <form method="POST" action="">
    <input type="hidden" name="accion" value="login">

    <label for="usuario">Usuario:</label>
    <input type="text" id="usuario" name="usuario" required>

    <label for="contraseña">Contraseña:</label>
    <input type="password" id="contraseña" name="contraseña" required>

    <button type="submit">Ingresar</button>

    <?php if (isset($_POST['accion']) && $_POST['accion'] == 'login' && !empty($mensaje)): ?>
      <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
  </form>

  <p class="alternar" style="text-align: center;" onclick="mostrarRegistro()">
    ¿Aún no tienes una cuenta? <br><strong>Regístrate aquí</strong>
  </p>
</div>

<!-- REGISTRO -->
<div class="login-container" id="form-registro" style="display: <?= !$mostrarLogin ? 'block' : 'none' ?>; width: 500px; margin-left: auto; margin-right: 150px; margin-top: 115px;">
  <h2>Registro de Usuario</h2>

  <form method="POST" action="">
    <input type="hidden" name="accion" value="registro">

    <label for="nombre">Nombre:</label>
    <input type="text" id="nombre" name="nombre" required>

    <label for="correo">Correo:</label>
    <input type="email" id="correo" name="correo" required>

    <label for="contraseña">Contraseña:</label>
    <input type="password" id="contraseña" name="contraseña" required>

    <label for="rol">Rol:</label>
    <select id="rol" name="rol" required>
      <option value="" disabled selected>Selecciona un rol</option>
      <option value="Usuario">Usuario</option>
    </select>

    <button type="submit">Registrar</button>

    <?php if (isset($_POST['accion']) && $_POST['accion'] == 'registro' && !empty($mensaje)): ?>
      <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
  </form>

  <p class="alternar" style="text-align: center;" onclick="mostrarLogin()">
    ¿Ya tienes una cuenta? <br><strong>Inicia sesión</strong>
  </p>
</div>

</body>
</html>