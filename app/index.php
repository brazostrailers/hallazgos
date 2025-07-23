<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login de Hallazgos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/login.css" rel="stylesheet">
</head>
<body>
  <div class="login-bg d-flex align-items-center justify-content-center">
    <div class="login-card shadow">
      <img src="assets/img/Logo.jpg" alt="Industria" class="logo-industrial mb-3">
      <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle"></i> Sesión cerrada exitosamente
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <center><button type="button" id="traducirLogin" class="btn btn-outline-secondary btn-sm mb-3">Traducir al Inglés</button></center>
      <h2 class="login-title mb-4">Acceso Hallazgos</h2>
      <form action="login.php" method="POST" autocomplete="off">
        <div class="mb-3">
          <input type="email" name="correo" class="form-control" placeholder="Correo electrónico" required autofocus>
        </div>
        <div class="mb-3 position-relative">
          <input type="password" name="contrasena" id="password" class="form-control" placeholder="Contraseña" required>
          <button type="button" class="btn btn-sm btn-outline-secondary show-pass-btn" tabindex="-1" onclick="togglePassword()">
            <span id="eye-icon" class="bi bi-eye"></span>
          </button>
        </div>
        <button class="btn btn-primary w-100" type="submit">Entrar</button>
      </form>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/login.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.js"></script>
</body>
<script>
  const traduccionesLoginES = {
    'Acceso Hallazgos': 'Findings Access',
    'Correo electrónico': 'Email address',
    'Contraseña': 'Password',
    'Entrar': 'Login',
    'Traducir al Inglés': 'Translate to English',
    'Translate to Spanish': 'Traducir al Español'
  };

  const traduccionesLoginEN = Object.fromEntries(
    Object.entries(traduccionesLoginES).map(([es, en]) => [en, es])
  );

  let idiomaLogin = 'es';

  document.getElementById('traducirLogin').addEventListener('click', () => {
    const diccionario = idiomaLogin === 'es' ? traduccionesLoginES : traduccionesLoginEN;

    // Cambiar título
    const titulo = document.querySelector('.login-title');
    if (diccionario[titulo.innerText]) {
      titulo.innerText = diccionario[titulo.innerText];
    }

    // Cambiar placeholders
    document.querySelectorAll('input').forEach(input => {
      if (diccionario[input.placeholder]) {
        input.placeholder = diccionario[input.placeholder];
      }
    });

    // Cambiar botón de submit
    const btnSubmit = document.querySelector('button[type="submit"]');
    if (btnSubmit && diccionario[btnSubmit.innerText]) {
      btnSubmit.innerText = diccionario[btnSubmit.innerText];
    }

    // Cambiar texto del botón toggle
    const btnToggle = document.getElementById('traducirLogin');
    btnToggle.innerText = idiomaLogin === 'es'
      ? traduccionesLoginES['Translate to Spanish']
      : traduccionesLoginES['Traducir al Inglés'];

    // Alternar idioma
    idiomaLogin = idiomaLogin === 'es' ? 'en' : 'es';
  });
</script>

</html>