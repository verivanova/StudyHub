'use strict'
const lenis = new Lenis({
    duration: 1.2,        // длительность "догона" в секундах
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // мягкая кривая
    smoothWheel: true,    // плавное колёсико
    wheelMultiplier: 1,   // множитель скорости колеса
    touchMultiplier: 1.5, // на тач-устройствах
  });

  function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
  }
  requestAnimationFrame(raf);