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

const mapelState = { items: [], guru: [], kelas: [], searchTimer: null };
async function loadOptions(){
  [mapelState.guru, mapelState.kelas] = await Promise.all([getJson('/admin/guru.php?status=active'), getJson('/admin/kelas.php?mode=options')]);
  const guruSelect=document.querySelector('[data-guru-options]'); if(guruSelect) guruSelect.innerHTML='<option value="">Pilih guru</option>'+mapelState.guru.map(g=>`<option value="${g.id}">${escapeHtml(g.nama_guru)} - ${escapeHtml(g.nip)}</option>`).join('');
  document.querySelectorAll('[data-kelas-options], [data-kelas-filter]').forEach((select)=>{ const first=select.hasAttribute('data-kelas-filter')?'<option value="">Semua kelas</option>':'<option value="">Pilih kelas</option>'; select.innerHTML=first+mapelState.kelas.map(k=>`<option value="${k.id}">${escapeHtml(k.nama_kelas)} - ${escapeHtml(k.jurusan)}</option>`).join(''); });
}
function renderMapelTable(items){ const tbody=document.querySelector('[data-mapel-table]'); if(!tbody)return; if(!items.length){tbody.innerHTML='<tr><td colspan="8" class="empty-state">Belum ada data mata pelajaran.</td></tr>';return;} tbody.innerHTML=items.map((item,i)=>`<tr><td>${i+1}</td><td><strong>${escapeHtml(item.kode_mapel)}</strong></td><td>${escapeHtml(item.nama_mapel)}</td><td>${escapeHtml(item.nama_guru||'-')}</td><td>${escapeHtml(item.nama_kelas||'-')}</td><td>${escapeHtml(item.semester)}</td><td>${statusBadge(item.status)}</td><td><div class="action-group"><button class="btn btn-small btn-secondary" data-edit-id="${item.id}">Edit</button><button class="btn btn-small btn-danger" data-delete-id="${item.id}" ${item.status==='inactive'?'disabled':''}>Nonaktifkan</button></div></td></tr>`).join(''); }
async function loadMapel(){ const loading=document.querySelector('[data-loading]'); const params=new URLSearchParams(); const search=document.querySelector('[data-search]')?.value||''; const status=document.querySelector('[data-status-filter]')?.value||''; const kelas=document.querySelector('[data-kelas-filter]')?.value||''; if(search)params.set('search',search); if(status)params.set('status',status); if(kelas)params.set('kelas_id',kelas); try{ if(window.authReady) await window.authReady; if(loading)loading.hidden=false; const q=params.toString()?`?${params}`:''; const res=await apiRequest(`/admin/mapel.php${q}`); mapelState.items=res.data||[]; renderMapelTable(mapelState.items); if(loading)loading.hidden=true; }catch(error){ if(loading) loading.textContent=error.message||'Data mapel gagal dimuat.'; renderMapelTable([]); }}
function openModal(mode='create', item=null){ const modal=document.querySelector('[data-modal]'); const form=document.querySelector('[data-mapel-form]'); const title=document.querySelector('[data-modal-title]'); if(!modal||!form)return; form.reset(); form.action.value=mode; form.id.value=item?.id||''; if(title) title.textContent=mode==='create'?'Tambah Mata Pelajaran':'Edit Mata Pelajaran'; if(item){ form.kode_mapel.value=item.kode_mapel||''; form.nama_mapel.value=item.nama_mapel||''; form.guru_id.value=item.guru_id||''; form.kelas_id.value=item.kelas_id||''; form.semester.value=item.semester||'Ganjil'; form.status.value=item.status||'active'; } modal.hidden=false; }
async function submitMapel(e){ e.preventDefault(); const payload=Object.fromEntries(new FormData(e.currentTarget).entries()); try{ setSaveLoading(true); const res=await apiRequest('/admin/mapel.php',{method:'POST',body:payload}); setPageAlert(res.message,'success'); closeModal(); await loadMapel(); }catch(error){ setPageAlert(firstError(error)||error.message||'Data mapel gagal disimpan.'); }finally{ setSaveLoading(false); }}
async function deleteMapel(id){ const item=mapelState.items.find(x=>Number(x.id)===Number(id)); if(!confirm(`Nonaktifkan mapel ${item?.nama_mapel || 'ini'}?`))return; try{ const res=await apiRequest('/admin/mapel.php',{method:'POST',body:{action:'delete',id}}); setPageAlert(res.message,'success'); await loadMapel(); }catch(error){ setPageAlert(error.message||'Data mapel gagal dinonaktifkan.'); }}
document.addEventListener('DOMContentLoaded', async()=>{ if(document.body.dataset.page!=='admin-mapel')return; await loadOptions().catch(()=>setPageAlert('Pilihan guru/kelas gagal dimuat.')); loadMapel(); document.querySelector('[data-open-create]')?.addEventListener('click',()=>openModal('create')); document.querySelector('[data-refresh]')?.addEventListener('click',loadMapel); document.querySelector('[data-mapel-form]')?.addEventListener('submit',submitMapel); document.querySelectorAll('[data-close-modal]').forEach(b=>b.addEventListener('click',closeModal)); document.querySelector('[data-search]')?.addEventListener('input',()=>{clearTimeout(mapelState.searchTimer); mapelState.searchTimer=setTimeout(loadMapel,350);}); document.querySelector('[data-status-filter]')?.addEventListener('change',loadMapel); document.querySelector('[data-kelas-filter]')?.addEventListener('change',loadMapel); document.querySelector('[data-mapel-table]')?.addEventListener('click',e=>{ const edit=e.target.closest('[data-edit-id]'); const del=e.target.closest('[data-delete-id]'); if(edit){ const item=mapelState.items.find(x=>Number(x.id)===Number(edit.dataset.editId)); if(item) openModal('update',item);} if(del) deleteMapel(del.dataset.deleteId); }); });
