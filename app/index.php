<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login de Hallazgos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="assets/css/login.css" rel="stylesheet">
</head>
<body>
  <div class="login-bg d-flex align-items-center justify-content-center">
    <link rel="manifest" href="manifest.json" crossorigin="use-credentials">
    <link rel="icon" sizes="192x192" href="assets/img/Logo.jpg" type="image/jpeg">
    <link rel="icon" sizes="512x512" href="assets/img/Logo.jpg" type="image/jpeg">

    <div class="login-card shadow p-0" style="max-width: 420px; width: 100%">
      <div class="p-4 pb-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <img src="assets/img/Logo.jpg" alt="Industria" class="rounded" style="width: 42px; height: 42px; object-fit: cover;">
          <div>
            <h2 class="login-title h5 mb-0">Acceso Hallazgos</h2>
            <small class="text-muted">Ingresa con tus credenciales</small>
          </div>
        </div>
        <button type="button" id="traducirLogin" class="btn btn-outline-secondary btn-sm">English</button>
      </div>

      <div class="p-4">
        <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> Sesión cerrada exitosamente
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label small text-muted">Correo</label>
            <div class="input-group input-group-lg">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" name="correo" class="form-control" placeholder="Correo electrónico" required autofocus>
              <div class="invalid-feedback">Ingresa tu correo</div>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label small text-muted">Contraseña</label>
            <div class="input-group input-group-lg position-relative">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" name="contrasena" id="password" class="form-control" placeholder="Contraseña" required>
              <button type="button" class="btn btn-outline-secondary" tabindex="-1" onclick="togglePassword()" title="Mostrar/Ocultar">
                <i id="eye-icon" class="bi bi-eye"></i>
              </button>
              <div class="invalid-feedback">Ingresa tu contraseña</div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <button type="button" class="btn btn-link p-0" id="btnOpenChangePassword">
              <i class="bi bi-key me-1"></i> Cambiar contraseña
            </button>
          </div>

          <button class="btn btn-primary w-100 btn-lg" type="submit">
            <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Cambiar contraseña -->
  <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="changePasswordLabel"><i class="bi bi-key me-2"></i>Cambiar contraseña</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="changePasswordForm" class="needs-validation" novalidate>
            <div class="mb-3">
              <label class="form-label">Correo</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="cp_email" placeholder="correo@dominio.com" required>
                <div class="invalid-feedback">Ingresa un correo válido</div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña actual</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="cp_current" placeholder="Tu contraseña actual" required>
                <button type="button" class="btn btn-outline-secondary" onclick="toggleField('cp_current','cp_eye1')"><i id="cp_eye1" class="bi bi-eye"></i></button>
                <div class="invalid-feedback">Ingresa tu contraseña actual</div>
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label">Nueva contraseña</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                <input type="password" class="form-control" id="cp_new" placeholder="Nueva contraseña" minlength="6" required>
                <button type="button" class="btn btn-outline-secondary" onclick="toggleField('cp_new','cp_eye2')"><i id="cp_eye2" class="bi bi-eye"></i></button>
                <div class="invalid-feedback">Mínimo 6 caracteres</div>
              </div>
              <small class="text-muted">Usa una contraseña segura (mín. 6 caracteres)</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirmar nueva contraseña</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                <input type="password" class="form-control" id="cp_confirm" placeholder="Repite la nueva contraseña" required>
                <button type="button" class="btn btn-outline-secondary" onclick="toggleField('cp_confirm','cp_eye3')"><i id="cp_eye3" class="bi bi-eye"></i></button>
                <div class="invalid-feedback">Las contraseñas deben coincidir</div>
              </div>
            </div>
            <div id="cp_alert" class="alert d-none" role="alert"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary" id="cp_submit">
            <i class="bi bi-arrow-repeat me-1"></i> Actualizar contraseña
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/login.js"></script>

  <script>
    // Mostrar/ocultar password del login
    function togglePassword() {
      const pwd = document.getElementById('password');
      const icon = document.getElementById('eye-icon');
      if (!pwd) return;
      const isText = pwd.type === 'text';
      pwd.type = isText ? 'password' : 'text';
      if (icon) icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
    function toggleField(id, iconId) {
      const field = document.getElementById(id);
      const icon = document.getElementById(iconId);
      if (!field) return;
      const isText = field.type === 'text';
      field.type = isText ? 'password' : 'text';
      if (icon) icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    }

    // Modal: abrir
    document.getElementById('btnOpenChangePassword').addEventListener('click', () => {
      const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
      modal.show();
      // Prefill correo si ya escribiste en el login
      const loginEmail = document.querySelector('input[name="correo"]').value;
      if (loginEmail) document.getElementById('cp_email').value = loginEmail;
    });

    // Cambiar contraseña (AJAX)
    document.getElementById('cp_submit').addEventListener('click', async () => {
      const email = document.getElementById('cp_email').value.trim();
      const current = document.getElementById('cp_current').value;
      const nw = document.getElementById('cp_new').value;
      const confirm = document.getElementById('cp_confirm').value;
      const alertBox = document.getElementById('cp_alert');

      // Validaciones básicas
      if (!email || !current || !nw || !confirm) {
        showCpAlert('Completa todos los campos', 'danger');
        return;
      }
      if (nw.length < 6) {
        showCpAlert('La nueva contraseña debe tener al menos 6 caracteres', 'warning');
        return;
      }
      if (nw !== confirm) {
        showCpAlert('Las contraseñas no coinciden', 'warning');
        return;
      }

      try {
        const form = new FormData();
        form.append('correo', email);
        form.append('contrasena_actual', current);
        form.append('contrasena_nueva', nw);

        const res = await fetch('includes/change_password.php', { method: 'POST', body: form });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'No se pudo cambiar la contraseña');

        showCpAlert('Contraseña actualizada correctamente', 'success');
        // Si éxito, cerrar modal tras 1.2s y limpiar
        setTimeout(() => {
          const modalEl = document.getElementById('changePasswordModal');
          const modal = bootstrap.Modal.getInstance(modalEl);
          modal?.hide();
          document.getElementById('changePasswordForm').reset();
          alertBox.classList.add('d-none');
        }, 1200);
      } catch (e) {
        showCpAlert(e.message, 'danger');
      }
    });

    function showCpAlert(msg, type) {
      const alertBox = document.getElementById('cp_alert');
      alertBox.className = `alert alert-${type}`;
      alertBox.textContent = msg;
      alertBox.classList.remove('d-none');
    }

    // Traducciones simples del login (botón alterna texto y placeholders principales)
    const traduccionesLoginES = {
      'Acceso Hallazgos': 'Findings Access',
      'Ingresa con tus credenciales': 'Sign in with your credentials',
      'Correo electrónico': 'Email address',
      'Contraseña': 'Password',
      'Entrar': 'Login',
      'Cambiar contraseña': 'Change password',
      'English': 'Español',
      'Español': 'English'
    };
    const traduccionesLoginEN = {
      'Findings Access': 'Acceso Hallazgos',
      'Sign in with your credentials': 'Ingresa con tus credenciales',
      'Email address': 'Correo electrónico',
      'Password': 'Contraseña',
      'Login': 'Entrar',
      'Change password': 'Cambiar contraseña',
      'English': 'Español',
      'Español': 'English'
    };
    let idiomaLogin = 'es';

    document.getElementById('traducirLogin').addEventListener('click', () => {
      const diccionario = idiomaLogin === 'es' ? traduccionesLoginES : traduccionesLoginEN;

      const titulo = document.querySelector('.login-title');
      if (diccionario[titulo.innerText]) titulo.innerText = diccionario[titulo.innerText];
      const subt = document.querySelector('.login-title')?.nextElementSibling;
      if (subt && diccionario[subt.innerText]) subt.innerText = diccionario[subt.innerText];

      // Placeholders de correo/contraseña
      const emailInput = document.querySelector('input[name="correo"]');
      const passInput = document.querySelector('#password');
      if (emailInput && diccionario[emailInput.placeholder]) emailInput.placeholder = diccionario[emailInput.placeholder];
      if (passInput && diccionario[passInput.placeholder]) passInput.placeholder = diccionario[passInput.placeholder];

      // Botones
      const submitBtn = document.querySelector('button[type="submit"]');
      if (submitBtn && diccionario[submitBtn.innerText.trim()]) submitBtn.innerText = diccionario[submitBtn.innerText.trim()];
      const changeBtn = document.getElementById('btnOpenChangePassword');
      if (changeBtn) {
        const txt = changeBtn.innerText.trim();
        if (diccionario[txt]) changeBtn.innerHTML = `<i class="bi bi-key me-1"></i> ${diccionario[txt]}`;
      }

      // Alternar idioma y actualizar botón toggle
      idiomaLogin = idiomaLogin === 'es' ? 'en' : 'es';
      const btnToggle = document.getElementById('traducirLogin');
      btnToggle.innerText = idiomaLogin === 'es' ? 'English' : 'Español';
    });
  </script>

</body>
</html>