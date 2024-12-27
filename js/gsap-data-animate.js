gsap.registerPlugin(ScrollTrigger);

window.onload = function() {
  setTimeout(() => {
    const animateElements = document.querySelectorAll('[data-animate]');

    animateElements.forEach(element => {
      const options = element.getAttribute('data-animate').split(',').reduce((acc, option) => {
        const [key, value] = option.split(':').map(item => item.trim());
        
        if (key.startsWith('style_start-')) {
          const cssProp = key.replace('style_start-', '');
          acc.startStyles[cssProp] = value;
        } else if (key.startsWith('style_end-')) {
          const cssProp = key.replace('style_end-', '');
          acc.endStyles[cssProp] = value;
        } else {
          acc[key] = value;
        }

        return acc;
      }, { startStyles: {}, endStyles: {} });

      const defaultStart = 'top 80%';
      const defaultEnd = 'bottom 20%';

      const splitText = options.splittext === 'true';
      const staggerValue = parseFloat(options.stagger || 0.1);

      if (splitText) {
        const text = element.innerText;
        const chars = text.split('');
        element.innerHTML = chars.map(char => `<span style="opacity: 0;">${char}</span>`).join('');
      }

      const rotationAxis = options.axis || 'Z';
      const rotationProp = `rotate${rotationAxis.toUpperCase()}`;

      const isBodyTrigger = options.trigger === 'body';

      gsap.fromTo(
        splitText ? element.children : element,
        {
          x: options.x ? parseInt(options.x) : 0,
          y: options.y ? parseInt(options.y) : 0,
          opacity: 0, 
          scale: options.s ? parseFloat(options.s) : 1,
          ...options.startStyles
        },
        {
          x: 0,
          y: 0,
          opacity: 1, 
          scale: 1,
          ...options.endStyles,
          scrollTrigger: {
            trigger: isBodyTrigger ? document.body : element,
            start: options.start || (isBodyTrigger ? 'top top' : defaultStart),
            end: options.end || (isBodyTrigger ? 'bottom bottom' : defaultEnd),
            scrub: options.scrub === 'true' ? true : (parseFloat(options.scrub) || 1),
            pin: options.pin ? (options.pin === 'true' ? true : options.pin) : false,
            markers: options.markers === 'true',
            toggleClass: options.toggleClass || null,
            pinSpacing: options.pinSpacing || 'margin',
            invalidateOnRefresh: true,
            immediateRender: true, 
          },
          stagger: splitText ? staggerValue : 0,
        }
      );

      if (options.r) {
        gsap.to(element, {
          [rotationProp]: parseInt(options.r) || 360,
          scrollTrigger: {
            trigger: isBodyTrigger ? document.body : element,
            start: options.start || (isBodyTrigger ? 'top top' : defaultStart),
            end: options.end || (isBodyTrigger ? 'bottom bottom' : defaultEnd),
            scrub: true,
            markers: options.markers === 'true',
            pin: options.pin ? (options.pin === 'true' ? true : options.pin) : false,
            invalidateOnRefresh: true,
            immediateRender: false,
          }
        });
      }
    });
  }, 100);
};
