<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('table').forEach((table) => {
    const body = table.tBodies[0];
    // Dashboard is a concise overview: it must never receive the automatic
    // search, page-size selector, or client-side pagination toolbar.
    if (!body || table.closest('[data-no-auto-tools], .finance-dashboard, .spmb-dashboard, .panitia-spmb-dashboard, .participant-dashboard')) return;
    let ancestor = table.parentElement, hasServerTools = false;
    for (let i=0; ancestor && i<5; i+=1, ancestor=ancestor.parentElement) if (ancestor.querySelector?.('.spmb-pagination')) { hasServerTools=true; break; }
    if (hasServerTools) return;
    const rows = [...body.rows].filter(row => !row.querySelector('[colspan]'));
    if (!rows.length) return;
    const host = document.createElement('div'); host.className='auto-list-tools';
    host.innerHTML='<label><input type="text" autocomplete="off" placeholder="Cari data"></label><div class="auto-list-count">Tampilkan <button type="button">10⌄</button></div><div class="auto-list-pages"></div>';
    const searchInput = host.querySelector('input');
    searchInput.style.setProperty('padding-left', '48px', 'important');
    searchInput.style.setProperty('background-image', 'none', 'important');
    table.parentElement.insertBefore(host, table);
    let size=10,page=1,filtered=rows;
    const render=()=>{ filtered=rows.filter(r=>r.textContent.toLowerCase().includes(searchInput.value.toLowerCase())); const total=Math.max(1,Math.ceil(filtered.length/size)); page=Math.min(page,total); rows.forEach(r=>r.style.display='none'); filtered.slice((page-1)*size,page*size).forEach(r=>r.style.display=''); const pages=host.querySelector('.auto-list-pages'); pages.innerHTML=''; if(total>1){ for(let n=1;n<=total;n++){const b=document.createElement('button');b.type='button';b.textContent=n;b.className=n===page?'is-active':'';b.onclick=()=>{page=n;render()};pages.append(b)} }};
    searchInput.addEventListener('input',()=>{page=1;render()});
    const trigger=host.querySelector('.auto-list-count button');
    trigger.onclick=()=>{ document.querySelector('.auto-list-menu')?.remove(); const menu=document.createElement('div');menu.className='auto-list-menu'; [10,20,50,100].forEach(n=>{const b=document.createElement('button');b.textContent=n;b.onclick=()=>{size=n;page=1;trigger.textContent=n+'⌄';menu.remove();render()};menu.append(b)}); document.body.append(menu); const place=()=>{const rect=trigger.getBoundingClientRect(), below=innerHeight-rect.bottom-12, up=rect.top-12, height=menu.offsetHeight; const openUp=height>below && up>below; menu.style.left=Math.max(12,rect.left)+'px'; menu.style.width=rect.width+'px'; menu.style.top=(openUp?Math.max(12,rect.top-height-6):Math.min(innerHeight-height-12,rect.bottom+6))+'px';}; place(); window.addEventListener('scroll', place, true); window.addEventListener('resize', place, {once:true}); setTimeout(()=>document.addEventListener('click',e=>{if(!menu.contains(e.target)&&e.target!==trigger)menu.remove()},{once:true}),0);};
    render();
  });
});
</script>
<style>
.auto-list-tools{display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:1px solid #e7edf5;background:#fff}.auto-list-tools label{position:relative;flex:1;max-width:360px}.auto-list-tools label:before{content:'';position:absolute;z-index:1;left:16px;top:50%;width:16px;height:16px;transform:translateY(-50%);pointer-events:none;background:center/16px 16px no-repeat url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='6'/%3E%3Cpath stroke-linecap='round' d='m16 16 4 4'/%3E%3C/svg%3E")}.auto-list-tools input{width:100%;box-sizing:border-box;border:1px solid #d8e1ed;border-radius:10px;padding:9px 12px 9px 48px !important;background-image:none !important;font:600 13px inherit}.auto-list-count{display:flex;align-items:center;gap:8px;color:#64748b;font-size:12px;font-weight:700}.auto-list-count button,.auto-list-pages button,.auto-list-menu button{border:1px solid #d8e1ed;border-radius:9px;background:#fff;padding:8px 11px;font-weight:800;color:#334155}.auto-list-pages{margin-left:auto;display:flex;gap:4px}.auto-list-pages .is-active{background:#0f766e;color:#fff;border-color:#0f766e}.auto-list-menu{position:fixed;z-index:500;background:#fff;border:1px solid #d8e1ed;border-radius:10px;padding:5px;box-shadow:0 12px 28px #0f172a24}.auto-list-menu button{display:block;width:100%;border:0;text-align:left}.auto-list-menu button:hover{background:#f1f5f9}@media(max-width:640px){.auto-list-tools{flex-wrap:wrap}.auto-list-tools label{max-width:none;flex-basis:100%}.auto-list-pages{margin-left:0}}
</style>

