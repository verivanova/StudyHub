'use strict'
const lenis = new Lenis({
    duration: 1.2,        
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), 
    smoothWheel: true,    
    wheelMultiplier: 1,   
    touchMultiplier: 1.5, 
  });

  function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
  }
  requestAnimationFrame(raf);
document.addEventListener('DOMContentLoaded', () => {
    const isAdmin = localStorage.getItem('isAdmin') === 'true';
    
    const loginBtn = document.querySelector('.second-btn, .btn-login');

    if (isAdmin && loginBtn) {
        loginBtn.textContent = 'Выйти';
        
        loginBtn.removeAttribute('onclick');
        
        loginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem('isAdmin');
            window.location.reload(); 
        });
    }
});

function toMaterials(){
    window.location.href="/html/materials.php"
}