function safeText(value) {
  return String(value ?? '').replace(/[&<>'"]/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#039;',
    '"': '&quot;',
  }[char]));
}

function profileEndpoint() {
  const role = document.body.dataset.role;
  return `/${role}/profil.php`;
}

function setProfileLoading(isLoading) {
  document.querySelectorAll('[data-profile-submit], [data-password-submit]').forEach((button) => {
    button.disabled = isLoading;
  });
}

function renderProfile(data) {
  const photo = document.querySelector('[data-profile-photo]');
  const roleLabel = document.querySelector('[data-profile-role]');
  const meta = document.querySelector('[data-profile-meta]');

  if (photo) {
    if (data.profile_photo_url) {
      photo.innerHTML = `<img src="${safeText(data.profile_photo_url)}" alt="Foto profil ${safeText(data.name)}">`;
    } else {
      const initials = (data.name || 'U').split(' ').map((item) => item[0]).join('').slice(0, 2).toUpperCase();
      photo.textContent = initials;
    }
  }

  if (roleLabel) roleLabel.textContent = data.role;
  if (meta) meta.innerHTML = `Status: <strong>${safeText(data.status)}</strong>`;

  const form = document.querySelector('[data-profile-form]');
  if (!form) return;

  form.elements.name.value = data.name || '';
  form.elements.email.value = data.email || '';
  form.elements.no_hp.value = data.profile?.no_hp || '';

  const nip = form.querySelector('[data-nip]');
  const nis = form.querySelector('[data-nis]');
  const kelas = form.querySelector('[data-kelas]');
  const mapel = form.querySelector('[name="mata_pelajaran_utama"]');

  if (nip) nip.value = data.profile?.nip || '-';
  if (nis) nis.value = data.profile?.nis || '-';
  if (kelas) kelas.value = data.profile?.nama_kelas ? `${data.profile.nama_kelas} - ${data.profile.tahun_ajaran || ''}` : '-';
  if (mapel) mapel.value = data.profile?.mata_pelajaran_utama || '';
}

async function loadProfile() {
  try {
    if (window.authReady) await window.authReady;
    const response = await apiRequest(profileEndpoint());
    renderProfile(response.data);
  } catch (error) {
    alertPage(error.message || 'Profil gagal dimuat.');
  }
}

function bindProfileForms() {
  const profileForm = document.querySelector('[data-profile-form]');
  const passwordForm = document.querySelector('[data-password-form]');
  const fileInput = document.querySelector('[name="profile_photo"]');

  if (fileInput) {
    fileInput.addEventListener('change', () => {
      const file = fileInput.files?.[0];
      const preview = document.querySelector('[data-file-preview]');
      if (!preview) return;
      preview.textContent = file ? `${file.name} (${Math.round(file.size / 1024)} KB)` : 'Belum ada file dipilih.';
    });
  }

  if (profileForm) {
    profileForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      setProfileLoading(true);
      const formData = new FormData(profileForm);
      formData.set('action', 'update');

      try {
        const response = await apiRequest(profileEndpoint(), {
          method: 'POST',
          body: formData,
        });
        alertPage(response.message, 'success');
        renderProfile(response.data);
        profileForm.reset();
        renderProfile(response.data);
        const preview = document.querySelector('[data-file-preview]');
        if (preview) preview.textContent = 'Belum ada file dipilih.';
      } catch (error) {
        alertPage(error.message || 'Profil gagal diperbarui.');
      } finally {
        setProfileLoading(false);
      }
    });
  }

  if (passwordForm) {
    passwordForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      setProfileLoading(true);
      const body = formDataObj(passwordForm);
      body.action = 'change_password';

      try {
        const response = await apiRequest(profileEndpoint(), {
          method: 'POST',
          body,
        });
        alertPage(response.message, 'success');
        passwordForm.reset();
      } catch (error) {
        alertPage(error.message || 'Password gagal diganti.');
      } finally {
        setProfileLoading(false);
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  bindProfileForms();
  loadProfile();
});
