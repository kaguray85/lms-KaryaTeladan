function showPasswordResetAlert(message, type = 'error') {
  const alertBox = document.querySelector('[data-alert]');
  if (!alertBox) return;

  alertBox.textContent = message;
  alertBox.className = `alert alert-${type}`;
  alertBox.hidden = false;
}

function setPasswordResetLoading(isLoading, loadingText, defaultText) {
  const button = document.querySelector('[data-submit-button]');
  if (!button) return;

  button.disabled = isLoading;
  button.textContent = isLoading ? loadingText : defaultText;
}

function setupResetPasswordToggles() {
  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const targetId = button.dataset.target;
    const input = targetId ? document.getElementById(targetId) : null;
    if (!input) return;

    button.addEventListener('click', () => {
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      button.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
      button.setAttribute('aria-pressed', String(isHidden));
    });
  });
}

function setupForgotPasswordForm() {
  const form = document.querySelector('[data-forgot-password-form]');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setPasswordResetLoading(true, 'Mengirim...', 'Kirim Kode OTP');

    try {
      const formData = new FormData(form);
      const response = await apiRequest('/auth/send-reset-otp.php', {
        method: 'POST',
        body: { email: formData.get('email') },
      });

      showPasswordResetAlert(response.message, 'success');
      window.setTimeout(() => {
        window.location.href = 'verify-otp.html';
      }, 900);
    } catch (error) {
      showPasswordResetAlert(error.message || 'Permintaan OTP gagal.');
    } finally {
      setPasswordResetLoading(false, 'Mengirim...', 'Kirim Kode OTP');
    }
  });
}

function setupVerifyOtpForm() {
  const form = document.querySelector('[data-verify-otp-form]');
  if (!form) return;

  const otpInput = form.querySelector('[name="otp"]');
  otpInput?.addEventListener('input', () => {
    otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setPasswordResetLoading(true, 'Memverifikasi...', 'Verifikasi OTP');

    try {
      const formData = new FormData(form);
      const response = await apiRequest('/auth/process-verify-otp.php', {
        method: 'POST',
        body: { otp: formData.get('otp') },
      });

      showPasswordResetAlert(response.message, 'success');
      window.setTimeout(() => {
        window.location.href = 'reset-password.html';
      }, 700);
    } catch (error) {
      showPasswordResetAlert(error.message || 'Verifikasi OTP gagal.');
    } finally {
      setPasswordResetLoading(false, 'Memverifikasi...', 'Verifikasi OTP');
    }
  });
}

function setupResetPasswordForm() {
  const form = document.querySelector('[data-reset-password-form]');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setPasswordResetLoading(true, 'Menyimpan...', 'Simpan Password Baru');

    const formData = new FormData(form);
    const password = formData.get('password');
    const passwordConfirmation = formData.get('password_confirmation');

    if (password.length < 8) {
      showPasswordResetAlert('Password minimal 8 karakter.');
      setPasswordResetLoading(false, 'Menyimpan...', 'Simpan Password Baru');
      return;
    }

    if (password !== passwordConfirmation) {
      showPasswordResetAlert('Konfirmasi password tidak sama.');
      setPasswordResetLoading(false, 'Menyimpan...', 'Simpan Password Baru');
      return;
    }

    try {
      const response = await apiRequest('/auth/process-reset-password.php', {
        method: 'POST',
        body: {
          password,
          password_confirmation: passwordConfirmation,
        },
      });

      showPasswordResetAlert(response.message, 'success');
      form.hidden = true;
      window.setTimeout(() => {
        window.location.href = 'login.html';
      }, 1200);
    } catch (error) {
      showPasswordResetAlert(error.message || 'Reset password gagal.');
    } finally {
      setPasswordResetLoading(false, 'Menyimpan...', 'Simpan Password Baru');
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  setupResetPasswordToggles();
  setupForgotPasswordForm();
  setupVerifyOtpForm();
  setupResetPasswordForm();
});
