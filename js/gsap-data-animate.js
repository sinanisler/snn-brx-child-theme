
gsap.registerPlugin(ScrollTrigger);
const animateElements = document.querySelectorAll('[data-animate]');
animateElements.forEach(element => {
  const options = element.getAttribute('data-animate').split(',').reduce((acc, option) => {
    const [key, value] = option.split(':').map(item => item.trim());
    acc[key] = value;
    return acc;
  }, {});
  const defaultStart = 'top 50%';
  const defaultEnd = 'bottom 50%';
  gsap.fromTo(
    element,
    {
      x: options.x ? parseInt(options.x) : 0,
      y: options.y ? parseInt(options.y) : 0,
      opacity: options.o ? parseFloat(options.o) : 1,
      rotation: options.r ? 0 : 0,
      scale: options.s ? parseFloat(options.s) : 1
    },
    {
      x: 0,
      y: 0,
      opacity: 1,
      rotation: options.r ? parseInt(options.r) : 0,
      scale: 1,
      scrollTrigger: {
        trigger: element,
        start: options.start || defaultStart,
        end: options.end || defaultEnd,
        scrub: options.scrub ? (options.scrub === 'true' ? true : parseFloat(options.scrub)) : 1,
        pin: options.pin ? (options.pin === 'true' ? true : options.pin) : false,
        markers: options.markers === 'true',
        toggleClass: options.toggleClass || null,
        pinSpacing: options.pinSpacing || 'margin'
      }
    }
  );
});
