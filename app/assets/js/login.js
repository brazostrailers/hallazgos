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
    