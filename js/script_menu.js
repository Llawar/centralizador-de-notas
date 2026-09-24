document.addEventListener('DOMContentLoaded',()=>{
  const btnSide=document.getElementById('btnSide');
  const backdrop=document.getElementById('backdrop');
  const sidebar=document.getElementById('sidebar');
  if(btnSide){
    btnSide.addEventListener('click',()=>{
      if(innerWidth<=960) document.body.classList.toggle('nav-open');
      else document.body.classList.toggle('nav-collapsed');
    });
  }
  if(backdrop) backdrop.addEventListener('click',()=>document.body.classList.remove('nav-open'));
  addEventListener('resize',()=>{ if(innerWidth>960) document.body.classList.remove('nav-open'); });
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape') document.body.classList.remove('nav-open');
    const tag=document.activeElement.tagName;
    if(e.key==='/' && tag!=='INPUT' && tag!=='TEXTAREA' && tag!=='SELECT'){
      const q=document.getElementById('q');
      if(q){ e.preventDefault(); q.focus(); }
    }
  });
  const qInput=document.getElementById('q');
  if(qInput){
    qInput.addEventListener('keydown',e=>{
      if(e.key==='Enter' && qInput.value.trim()){
        // placeholder search toast if function exists
        if(typeof toast==='function') toast('Buscando "'+qInput.value.trim()+'"…','search','info');
      }
    });
  }
  // notif toggle
  const btnBell=document.getElementById('btnBell');
  const notifPanel=document.getElementById('notifPanel');
  if(btnBell && notifPanel){
    btnBell.addEventListener('click',e=>{ e.stopPropagation(); notifPanel.classList.toggle('open'); });
    document.addEventListener('click',e=>{ if(!e.target.closest('.bell-wrap')) notifPanel.classList.remove('open'); });
    const btnRead=document.getElementById('btnRead');
    if(btnRead) btnRead.addEventListener('click',()=>{
      const b=document.getElementById('bellBadge'); if(b) b.style.display='none';
      btnRead.textContent='Sin nuevas'; btnRead.disabled=true;
    });
  }
});
