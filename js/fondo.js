(() => {
  const canvas = document.getElementById('canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const mqReduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  const CFG = { density: 20000, max: 60, linkDist: 120, lineOp: 0.10, speed: 0.35, blue: '100, 180, 255', gold: '245, 184, 91' };
  let particles = [], raf = null;
  const reduced = () => mqReduce.matches;
  function resize() {
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    canvas.width = Math.floor(innerWidth * dpr);
    canvas.height = Math.floor(innerHeight * dpr);
    canvas.style.width = innerWidth + 'px';
    canvas.style.height = innerHeight + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    init();
  }
  function init() {
    const n = Math.min(CFG.max, Math.floor((innerWidth * innerHeight) / CFG.density));
    particles = Array.from({ length: n }, () => {
      const isGold = Math.random() < 0.12;
      return { x: Math.random()*innerWidth, y: Math.random()*innerHeight, r: Math.random()*1.6+0.4, vx:(Math.random()-.5)*CFG.speed, vy:(Math.random()-.5)*CFG.speed, o: isGold? Math.random()*0.14+0.06 : Math.random()*0.22+0.08, c: isGold? CFG.gold:CFG.blue };
    });
  }
  function step() {
    ctx.clearRect(0, 0, innerWidth, innerHeight);
    for (const p of particles) {
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0) p.x = innerWidth; else if (p.x > innerWidth) p.x = 0;
      if (p.y < 0) p.y = innerHeight; else if (p.y > innerHeight) p.y = 0;
      ctx.fillStyle = 'rgba(' + p.c + ',' + p.o + ')';
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2); ctx.fill();
    }
    for (let a=0;a<particles.length;a++) for(let b=a+1;b<particles.length;b++){
      const dx=particles[a].x-particles[b].x, dy=particles[a].y-particles[b].y;
      const d2=dx*dx+dy*dy;
      if(d2 < CFG.linkDist*CFG.linkDist){
        const t=1-Math.sqrt(d2)/CFG.linkDist;
        ctx.strokeStyle='rgba('+CFG.blue+','+(t*CFG.lineOp)+')';
        ctx.lineWidth=0.5;
        ctx.beginPath(); ctx.moveTo(particles[a].x,particles[a].y); ctx.lineTo(particles[b].x,particles[b].y); ctx.stroke();
      }
    }
    raf=requestAnimationFrame(step);
  }
  const start=()=>{ if(raf===null && !reduced()) raf=requestAnimationFrame(step); };
  const stop=()=>{ cancelAnimationFrame(raf); raf=null; };
  document.addEventListener('visibilitychange',()=>document.hidden?stop():start());
  if(mqReduce.addEventListener) mqReduce.addEventListener('change',()=>reduced()?stop():start());
  let t; addEventListener('resize',()=>{ clearTimeout(t); t=setTimeout(resize,150); });
  if(!reduced()){ resize(); start(); }
})();
