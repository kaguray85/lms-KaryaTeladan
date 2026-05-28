document.addEventListener('DOMContentLoaded', async()=>{
 if(!['admin-pengumuman','guru-pengumuman','murid-pengumuman'].includes(document.body.dataset.page)) return; if(window.authReady) await window.authReady;
 const role=document.body.dataset.role; const tbody=document.querySelector('[data-table]');
 function renderRow(x,i){
  if(role!=='admin') return `<tr><td>${i+1}</td><td><strong>${esc(x.judul)}</strong><small>${esc(x.isi)}</small></td><td>${esc(x.tanggal)}</td></tr>`;
  return `<tr><td>${i+1}</td><td><strong>${esc(x.judul)}</strong><small>${esc(x.isi)}</small></td><td>${badge(x.target_role,'secondary')}</td><td>${esc(x.pembuat)}</td><td>${esc(x.tanggal)}</td><td>${role==='admin'?`<button class="btn btn-small btn-danger" data-delete="${x.id}">Hapus</button>`:'-'}</td></tr>`;
 }
 async function load(){try{const endpoint=role==='admin'?'/admin/pengumuman.php':`/${role}/pengumuman.php`; const data=await loadData(endpoint); if(!tbody)return; if(!data.length)return renderEmpty(tbody,role==='admin'?6:3,'Belum ada pengumuman.'); tbody.innerHTML=data.map(renderRow).join('');}catch(e){alertPage(e.message||'Pengumuman gagal dimuat.');}}
 if(role==='admin'){document.querySelector('[data-pengumuman-form]')?.addEventListener('submit',async(e)=>{e.preventDefault(); try{const res=await apiRequest('/admin/pengumuman.php',{method:'POST',body:formDataObj(e.currentTarget)}); alertPage(res.message,'success'); e.currentTarget.reset(); load();}catch(err){alertPage(err.message||'Pengumuman gagal disimpan.');}}); tbody?.addEventListener('click',async e=>{const btn=e.target.closest('[data-delete]'); if(!btn||!confirm('Hapus pengumuman ini?'))return; try{const res=await apiRequest('/admin/pengumuman.php',{method:'POST',body:{action:'delete',id:btn.dataset.delete}}); alertPage(res.message,'success'); load();}catch(err){alertPage(err.message||'Pengumuman gagal dihapus.');}});}
 load(); document.querySelector('[data-refresh]')?.addEventListener('click',load);
});
