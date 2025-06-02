 function togglePassword() {
      const pwd = document.getElementById('password');
      const icon = document.getElementById('eye-icon');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
      } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
      }
    }
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