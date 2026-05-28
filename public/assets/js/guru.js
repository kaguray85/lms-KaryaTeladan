const guruState = {
  items: [],
  searchTimer: null,
};

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function setPageAlert(message, type = 'error') {
  const alertBox = document.querySelector('[data-alert]');
  if (!alertBox) return;

  alertBox.textContent = message;
  alertBox.className = `alert alert-${type}`;
  alertBox.hidden = false;
}

function setSaveLoading(isLoading) {
  const button = document.querySelector('[data-save-button]');
  if (!button) return;

  button.disabled = isLoading;
  button.textContent = isLoading ? 'Menyimpan...' : 'Simpan';
}

function statusBadge(status) {
  const label = status === 'active' ? 'Aktif' : 'Nonaktif';
  const type = status === 'active' ? 'success' : 'danger';
  return `<span class="badge badge-${type}">${label}</span>`;
}

function renderGuruTable(items) {
  const tbody = document.querySelector('[data-guru-table]');
  if (!tbody) return;

  if (!items.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="empty-state">Belum ada data guru.</td></tr>';
    return;
  }

  tbody.innerHTML = items.map((item, index) => `
    <tr>
      <td>${index + 1}</td>
      <td><strong>${escapeHtml(item.nama_guru)}</strong></td>
      <td>${escapeHtml(item.nip)}</td>
      <td>${escapeHtml(item.email)}</td>
      <td>${escapeHtml(item.no_hp || '-')}</td>
      <td>${escapeHtml(item.mata_pelajaran_utama || '-')}</td>
      <td>${statusBadge(item.status)}</td>
      <td>
        <div class="action-group">
          <button class="btn btn-small btn-secondary" type="button" data-edit-id="${item.id}">Edit</button>
          <button class="btn btn-small btn-danger" type="button" data-delete-id="${item.id}" ${item.status === 'inactive' ? 'disabled' : ''}>Nonaktifkan</button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function loadGuru() {
  const loading = document.querySelector('[data-loading]');
  const search = document.querySelector('[data-search]')?.value || '';
  const status = document.querySelector('[data-status-filter]')?.value || '';
  const params = new URLSearchParams();

  if (search) params.set('search', search);
  if (status) params.set('status', status);

  try {
    if (window.authReady) await window.authReady;
    if (loading) {
      loading.hidden = false;
      loading.textContent = 'Memuat data guru...';
    }

    const query = params.toString() ? `?${params.toString()}` : '';
    const response = await apiRequest(`/admin/guru.php${query}`);
    guruState.items = response.data || [];
    renderGuruTable(guruState.items);

    if (loading) loading.hidden = true;
  } catch (error) {
    if (loading) loading.textContent = error.message || 'Data guru gagal dimuat.';
    renderGuruTable([]);
  }
}

function openModal(mode = 'create', item = null) {
  const modal = document.querySelector('[data-modal]');
  const form = document.querySelector('[data-guru-form]');
  const title = document.querySelector('[data-modal-title]');
  const passwordField = document.querySelector('[data-password-field]');

  if (!modal || !form) return;

  form.reset();
  form.action.value = mode;
  form.id.value = item?.id || '';

  if (title) title.textContent = mode === 'create' ? 'Tambah Guru' : 'Edit Guru';
  if (passwordField) {
    passwordField.required = mode === 'create';
    passwordField.placeholder = mode === 'create' ? 'Minimal 6 karakter' : 'Kosongkan jika tidak diganti';
  }

  if (item) {
    form.nama_guru.value = item.nama_guru || '';
    form.nip.value = item.nip || '';
    form.email.value = item.email || '';
    form.no_hp.value = item.no_hp || '';
    form.mata_pelajaran_utama.value = item.mata_pelajaran_utama || '';
    form.status.value = item.status || 'active';
  }

  modal.hidden = false;
}

function closeModal() {
  const modal = document.querySelector('[data-modal]');
  if (modal) modal.hidden = true;
}

async function submitGuru(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const formData = new FormData(form);
  const payload = Object.fromEntries(formData.entries());

  if (payload.action === 'update' && !payload.password) {
    delete payload.password;
  }

  try {
    setSaveLoading(true);
    const response = await apiRequest('/admin/guru.php', {
      method: 'POST',
      body: payload,
    });

    setPageAlert(response.message, 'success');
    closeModal();
    await loadGuru();
  } catch (error) {
    const errors = error.response?.errors;
    const firstError = errors && Object.values(errors)[0];
    setPageAlert(firstError || error.message || 'Data guru gagal disimpan.');
  } finally {
    setSaveLoading(false);
  }
}

async function deleteGuru(id) {
  const item = guruState.items.find((guru) => Number(guru.id) === Number(id));
  const nama = item?.nama_guru || 'guru ini';

  if (!confirm(`Nonaktifkan data ${nama}? Akun login guru juga ikut dinonaktifkan.`)) {
    return;
  }

  try {
    const response = await apiRequest('/admin/guru.php', {
      method: 'POST',
      body: { action: 'delete', id },
    });

    setPageAlert(response.message, 'success');
    await loadGuru();
  } catch (error) {
    setPageAlert(error.message || 'Data guru gagal dinonaktifkan.');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.body.dataset.page !== 'admin-guru') return;

  loadGuru();

  document.querySelector('[data-open-create]')?.addEventListener('click', () => openModal('create'));
  document.querySelector('[data-refresh]')?.addEventListener('click', loadGuru);
  document.querySelector('[data-guru-form]')?.addEventListener('submit', submitGuru);
  document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModal));

  document.querySelector('[data-search]')?.addEventListener('input', () => {
    clearTimeout(guruState.searchTimer);
    guruState.searchTimer = setTimeout(loadGuru, 350);
  });

  document.querySelector('[data-status-filter]')?.addEventListener('change', loadGuru);

  document.querySelector('[data-guru-table]')?.addEventListener('click', (event) => {
    const editButton = event.target.closest('[data-edit-id]');
    const deleteButton = event.target.closest('[data-delete-id]');

    if (editButton) {
      const item = guruState.items.find((guru) => Number(guru.id) === Number(editButton.dataset.editId));
      if (item) openModal('update', item);
    }

    if (deleteButton) {
      deleteGuru(deleteButton.dataset.deleteId);
    }
  });
});
