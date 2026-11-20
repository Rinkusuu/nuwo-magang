(function () {
  const akar = document.documentElement;
  const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const aktif = !kurangiGerak && 'IntersectionObserver' in window;
  if (aktif) akar.classList.add('gerak');

  let pengamat = null;

  function pecahKata(el) {
    if (el.dataset.kataSiap) return;
    const teks = el.textContent.trim().split(/\s+/);
    el.setAttribute('aria-label', teks.join(' '));
    el.innerHTML = teks.map((k, i) =>
      `<span class="kata" aria-hidden="true"><span style="--k:${i}">${k.replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]))}</span></span>`
    ).join(' ');
    el.dataset.kataSiap = '1';
  }

  function ukurRute(root) {
    (root || document).querySelectorAll('svg.rute').forEach(svg => {
      const garis = svg.querySelector('path.garis');
      if (!garis || typeof garis.getTotalLength !== 'function') return;
      const panjang = garis.getTotalLength();
      svg.querySelectorAll('path').forEach(p => p.style.setProperty('--panjang', Math.ceil(p.getTotalLength())));
      if (!svg.dataset.halte || svg.dataset.halteSiap) return;
      const ns = 'http://www.w3.org/2000/svg';
      svg.dataset.halte.split(',').map(Number).forEach((posisi, i) => {
        const t = garis.getPointAtLength(panjang * posisi);
        if (i === 0) {
          const cincin = document.createElementNS(ns, 'circle');
          cincin.setAttribute('class', 'cincin');
          cincin.setAttribute('cx', t.x); cincin.setAttribute('cy', t.y); cincin.setAttribute('r', 7);
          svg.appendChild(cincin);
        }
        const halte = document.createElementNS(ns, 'circle');
        halte.setAttribute('class', i === 0 ? 'halte halte-awal' : 'halte');
        halte.setAttribute('cx', t.x); halte.setAttribute('cy', t.y); halte.setAttribute('r', i === 0 ? 6 : 5);
        halte.style.setProperty('--h', i);
        svg.appendChild(halte);
      });
      svg.dataset.halteSiap = '1';
    });
  }

  function amati(root) {
    if (!aktif) return;
    const scope = root || document;
    scope.querySelectorAll('[data-muncul]:not([data-diamati])').forEach(el => {
      el.dataset.diamati = '1';
      if (!el.style.getPropertyValue('--urut')) {
        const saudara = el.parentElement ? [...el.parentElement.children].filter(c => c.hasAttribute('data-muncul')) : [el];
        el.style.setProperty('--urut', Math.max(0, saudara.indexOf(el)));
      }
      pengamat.observe(el);
    });
    scope.querySelectorAll('.flow:not([data-diamati])').forEach(el => { el.dataset.diamati = '1'; pengamat.observe(el); });
  }
  window.gerakAmati = amati;

  function faqHalus() {
    document.querySelectorAll('.faq details').forEach(d => {
      const ringkasan = d.querySelector('summary');
      const isi = d.querySelector('summary ~ *');
      if (!ringkasan || !isi) return;
      let anim = null;
      ringkasan.addEventListener('click', e => {
        if (!aktif) return;
        e.preventDefault();
        if (anim) anim.cancel();
        const tinggiTutup = ringkasan.offsetHeight;
        const gayaDetail = getComputedStyle(d);
        const padding = parseFloat(gayaDetail.paddingTop) + parseFloat(gayaDetail.paddingBottom);
        const border = parseFloat(gayaDetail.borderTopWidth) + parseFloat(gayaDetail.borderBottomWidth);
        const awal = d.offsetHeight;
        d.style.overflow = 'hidden';
        if (!d.open) {
          d.open = true;
          const akhir = d.offsetHeight;
          anim = d.animate({ height: [awal + 'px', akhir + 'px'] }, { duration: 380, easing: 'cubic-bezier(.22,.8,.26,1)' });
          isi.animate({ opacity: [0, 1], transform: ['translateY(-4px)', 'none'] }, { duration: 380, easing: 'ease-out' });
        } else {
          const akhir = tinggiTutup + padding + border;
          anim = d.animate({ height: [awal + 'px', akhir + 'px'] }, { duration: 300, easing: 'cubic-bezier(.65,0,.35,1)' });
          anim.onfinish = () => { d.open = false; };
        }
        anim.addEventListener('finish', () => { d.style.overflow = ''; anim = null; });
      });
    });
  }

  function bayanganHeader() {
    const kepala = document.querySelector('header.nav, .topbar');
    if (!kepala) return;
    let menunggu = false;
    const perbarui = () => { menunggu = false; kepala.classList.toggle('bergulir', window.scrollY > 8); };
    window.addEventListener('scroll', () => { if (!menunggu) { menunggu = true; requestAnimationFrame(perbarui); } }, { passive: true });
    perbarui();
  }

  function geserPola() {
    if (!aktif) return;
    const pola = document.querySelector('.hero .pola-titik');
    if (!pola) return;
    let menunggu = false;
    const perbarui = () => {
      menunggu = false;
      pola.style.transform = `translate3d(0, ${Math.min(window.scrollY * 0.12, 90).toFixed(1)}px, 0)`;
    };
    window.addEventListener('scroll', () => { if (!menunggu) { menunggu = true; requestAnimationFrame(perbarui); } }, { passive: true });
  }

  document.addEventListener('DOMContentLoaded', () => {
    ukurRute();
    if (aktif) {
      document.querySelectorAll('[data-kata]').forEach(pecahKata);
      pengamat = new IntersectionObserver(entri => {
        entri.forEach(e => {
          if (!e.isIntersecting) return;
          e.target.classList.add('tampil');
          pengamat.unobserve(e.target);
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
      amati();
    }
    faqHalus();
    bayanganHeader();
    geserPola();
  });
})();
