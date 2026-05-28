function escapeHtml(value) {
  return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
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
function closeModal() { const modal = document.querySelector('[data-modal]'); if (modal) modal.hidden = true; }
function firstError(error) { const errors = error.response?.errors; return errors ? Object.values(errors)[0] : null; }
async function getJson(endpoint) { const response = await apiRequest(endpoint); return response.data || []; }

const kelasState = { items: [], guru: [], searchTimer: null };
async function loadGuruOptions() {
  kelasState.guru = await getJson('/admin/guru.php?status=active');
  const select = document.querySelector('[data-guru-options]');
  if (!select) return;
  select.innerHTML = '<option value="">Pilih wali kelas</option>' + kelasState.guru.map(g => `<option value="${g.id}">${escapeHtml(g.nama_guru)} - ${escapeHtml(g.nip)}</option>`).join('');
}
function renderKelasTable(items) {
  const tbody = document.querySelector('[data-kelas-table]');
  if (!tbody) return;
  if (!items.length) { tbody.innerHTML = '<tr><td colspan="8" class="empty-state">Belum ada data kelas.</td></tr>'; return; }
  tbody.innerHTML = items.map((item, i) => `<tr><td>${i+1}</td><td><strong>${escapeHtml(item.nama_kelas)}</strong></td><td>${escapeHtml(item.jurusan)}</td><td>${escapeHtml(item.wali_kelas || '-')}</td><td>${escapeHtml(item.jumlah_murid ?? 0)}</td><td>${escapeHtml(item.tahun_ajaran)}</td><td>${statusBadge(item.status)}</td><td><div class="action-group"><button class="btn btn-small btn-secondary" data-edit-id="${item.id}">Edit</button><button class="btn btn-small btn-danger" data-delete-id="${item.id}" ${item.status === 'inactive' ? 'disabled' : ''}>Nonaktifkan</button></div></td></tr>`).join('');
}
async function loadKelas() {
  const loading = document.querySelector('[data-loading]');
  const params = new URLSearchParams();
  const search = document.querySelector('[data-search]')?.value || '';
  const status = document.querySelector('[data-status-filter]')?.value || '';
  if (search) params.set('search', search); if (status) params.set('status', status);
  try { if (window.authReady) await window.authReady; if (loading) loading.hidden = false; const q = params.toString() ? `?${params}` : ''; const res = await apiRequest(`/admin/kelas.php${q}`); kelasState.items = res.data || []; renderKelasTable(kelasState.items); if (loading) loading.hidden = true; } catch (error) { if (loading) loading.textContent = error.message || 'Data kelas gagal dimuat.'; renderKelasTable([]); }
}
function openModal(mode='create', item=null) {
  const modal=document.querySelector('[data-modal]'); const form=document.querySelector('[data-kelas-form]'); const title=document.querySelector('[data-modal-title]'); if(!modal||!form)return;
  form.reset(); form.action.value=mode; form.id.value=item?.id||''; if(title) title.textContent = mode==='create'?'Tambah Kelas':'Edit Kelas';
  if(item){ form.nama_kelas.value=item.nama_kelas||''; form.jurusan.value=item.jurusan||''; form.wali_kelas_id.value=item.wali_kelas_id||''; form.tahun_ajaran.value=item.tahun_ajaran||''; form.status.value=item.status||'active'; }
  modal.hidden=false;
}
async function submitKelas(e){ e.preventDefault(); const payload=Object.fromEntries(new FormData(e.currentTarget).entries()); try{ setSaveLoading(true); const res=await apiRequest('/admin/kelas.php',{method:'POST',body:payload}); setPageAlert(res.message,'success'); closeModal(); await loadKelas(); }catch(error){ setPageAlert(firstError(error)||error.message||'Data kelas gagal disimpan.'); }finally{ setSaveLoading(false); }}
async function deleteKelas(id){ const item=kelasState.items.find(x=>Number(x.id)===Number(id)); if(!confirm(`Nonaktifkan kelas ${item?.nama_kelas || 'ini'}?`))return; try{ const res=await apiRequest('/admin/kelas.php',{method:'POST',body:{action:'delete',id}}); setPageAlert(res.message,'success'); await loadKelas(); }catch(error){ setPageAlert(error.message||'Data kelas gagal dinonaktifkan.'); }}
document.addEventListener('DOMContentLoaded', async()=>{ if(document.body.dataset.page!=='admin-kelas')return; await loadGuruOptions().catch(()=>setPageAlert('Pilihan wali kelas gagal dimuat.')); loadKelas(); document.querySelector('[data-open-create]')?.addEventListener('click',()=>openModal('create')); document.querySelector('[data-refresh]')?.addEventListener('click',loadKelas); document.querySelector('[data-kelas-form]')?.addEventListener('submit',submitKelas); document.querySelectorAll('[data-close-modal]').forEach(b=>b.addEventListener('click',closeModal)); document.querySelector('[data-search]')?.addEventListener('input',()=>{clearTimeout(kelasState.searchTimer); kelasState.searchTimer=setTimeout(loadKelas,350);}); document.querySelector('[data-status-filter]')?.addEventListener('change',loadKelas); document.querySelector('[data-kelas-table]')?.addEventListener('click',e=>{ const edit=e.target.closest('[data-edit-id]'); const del=e.target.closest('[data-delete-id]'); if(edit){ const item=kelasState.items.find(x=>Number(x.id)===Number(edit.dataset.editId)); if(item) openModal('update',item);} if(del) deleteKelas(del.dataset.deleteId); }); });
