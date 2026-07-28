const ROLE_ROUTES = {
  admin: `${APP_BASE}/public/admin/dashboard.html`,
  guru: `${APP_BASE}/public/guru/dashboard.html`,
  murid: `${APP_BASE}/public/murid/dashboard.html`,
};

function showAlert(message, type = 'error') {
  const alertBox = document.querySelector('[data-alert]');
  if (!alertBox) return;

  alertBox.textContent = message;
  alertBox.className = `alert alert-${type}`;
  alertBox.hidden = false;
}

function setLoading(isLoading) {
  const button = document.querySelector('[data-login-button]');
  if (!button) return;

  button.disabled = isLoading;
  button.textContent = isLoading ? 'Memproses...' : 'Masuk';
}

function setupPasswordToggle() {
  const passwordInput = document.querySelector('#password');
  const toggleButton = document.querySelector('[data-password-toggle]');
  if (!passwordInput || !toggleButton) return;

  toggleButton.addEventListener('click', () => {
    const isHidden = passwordInput.type === 'password';
    passwordInput.type = isHidden ? 'text' : 'password';
    toggleButton.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
    toggleButton.setAttribute('aria-pressed', String(isHidden));
  });
}

function getLoginRecaptchaToken() {
  const token = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : '';

  if (!token) {
    throw new Error('Silakan centang reCAPTCHA sebelum login.');
  }

  return token;
}

function resetLoginRecaptcha() {
  if (typeof grecaptcha !== 'undefined') {
    grecaptcha.reset();
  }
}

async function getCurrentUser() {
  const response = await apiRequest('/auth/me.php');
  return response.data;
}

async function redirectIfLoggedIn() {
  try {
    const user = await getCurrentUser();
    if (ROLE_ROUTES[user.role]) {
      window.location.href = ROLE_ROUTES[user.role];
    }
  } catch (error) {
    // User belum login, tetap di halaman login.
  }
}

async function protectPage(requiredRole) {
  try {
    const user = await getCurrentUser();

    if (requiredRole && user.role !== requiredRole) {
      window.location.href = ROLE_ROUTES[user.role] || `${APP_BASE}/public/login.html`;
      return null;
    }

    const userNameTarget = document.querySelector('[data-user-name]');
    if (userNameTarget) userNameTarget.textContent = user.name;

    return user;
  } catch (error) {
    window.location.href = `${APP_BASE}/public/login.html`;
    return null;
  }
}

async function logout() {
  try {
    await apiRequest('/auth/logout.php', { method: 'POST', body: {} });
  } finally {
    window.location.href = `${APP_BASE}/public/login.html`;
  }
}


function setupResponsiveShell() {
  const sidebar = document.querySelector('.sidebar');
  const topbar = document.querySelector('.topbar');
  if (!sidebar || !topbar) return;

  if (!document.querySelector('[data-mobile-menu]')) {
    const menuButton = document.createElement('button');
    menuButton.type = 'button';
    menuButton.className = 'mobile-menu-button';
    menuButton.dataset.mobileMenu = 'true';
    menuButton.setAttribute('aria-label', 'Buka atau tutup menu');
    menuButton.textContent = 'Menu';
    topbar.prepend(menuButton);

    menuButton.addEventListener('click', () => {
      document.body.classList.toggle('sidebar-open');
    });
  }

  document.querySelectorAll('.sidebar .nav a, .sidebar .nav button').forEach((item) => {
    item.addEventListener('click', () => {
      document.body.classList.remove('sidebar-open');
    });
  });

  if (!document.body.dataset.responsiveShellReady) {
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
      }
    });
    document.body.dataset.responsiveShellReady = 'true';
  }

  document.querySelectorAll('.nav a').forEach((link) => {
    const current = window.location.pathname.split('/').pop();
    const target = link.getAttribute('href');
    if (target === current) {
      link.classList.add('active');
    }
  });
}

function setupThemePreference() {
  const savedTheme = localStorage.getItem('lms-theme') || 'light';
  document.documentElement.dataset.theme = savedTheme;

  const topbar = document.querySelector('.topbar');
  if (!topbar || document.querySelector('[data-theme-toggle]')) return;

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'btn btn-secondary btn-small btn-auto';
  button.dataset.themeToggle = 'true';
  button.textContent = savedTheme === 'dark' ? 'Light Mode' : 'Dark Mode';

  button.addEventListener('click', () => {
    const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = nextTheme;
    localStorage.setItem('lms-theme', nextTheme);
    button.textContent = nextTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
  });

  topbar.append(button);
}


document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.querySelector('[data-login-form]');
  const pageRole = document.body.dataset.role;

  setupResponsiveShell();
  setupThemePreference();
  setupPasswordToggle();

  if (document.body.dataset.page === 'login') {
    redirectIfLoggedIn();
  }

  if (pageRole) {
    window.authReady = protectPage(pageRole);
  }

  document.querySelectorAll('[data-logout]').forEach((button) => {
    button.addEventListener('click', logout);
  });

  if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      setLoading(true);

      const formData = new FormData(loginForm);
      const email = formData.get('email');
      const password = formData.get('password');

      try {
        const recaptchaToken = getLoginRecaptchaToken();
        const response = await apiRequest('/auth/login.php', {
          method: 'POST',
          body: { email, password, recaptcha_token: recaptchaToken },
        });

        showAlert(response.message, 'success');
        const user = response.data;
        window.location.href = ROLE_ROUTES[user.role] || `${APP_BASE}/public/login.html`;
      } catch (error) {
        showAlert(error.message || 'Login gagal.');
        resetLoginRecaptcha();
      } finally {
        setLoading(false);
      }
    });
  }
});
