// Registers the ScrollTrigger plugin with GSAP and animates elements based on data attributes.
gsap.registerPlugin(ScrollTrigger);

window.onload = function() {
  setTimeout(() => {
    const animateElements = document.querySelectorAll('[data-animate]');

    animateElements.forEach(element => {
      // Extract options from data-animate attribute
      const options = element.getAttribute('data-animate').split(',').reduce((acc, option) => {
        const [key, value] = option.split(':').map(item => item.trim());
        
        // Convert "style_start-X" and "style_end-X" to proper format for gsap
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

      // Handle split text if needed
      const splitText = options.splittext === 'true';
      const staggerValue = parseFloat(options.stagger || 0.1);

      if (splitText) {
        const text = element.innerText;
        const chars = text.split('');
        element.innerHTML = chars.map(char => `<span>${char}</span>`).join('');
      }

      const rotationAxis = options.axis || 'Z';
      const rotationProp = `rotate${rotationAxis.toUpperCase()}`;

      // Check if the trigger is set to 'body' in data-animate
      const isBodyTrigger = options.trigger === 'body';

      // General GSAP animation for all elements with data-animate attribute
      gsap.fromTo(
        splitText ? element.children : element,
        {
          // Use the dynamic start styles extracted from data-animate
          x: options.x ? parseInt(options.x) : 0,
          y: options.y ? parseInt(options.y) : 0,
          opacity: options.o ? parseFloat(options.o) : 1,
          scale: options.s ? parseFloat(options.s) : 1,
          ...options.startStyles // Add any custom start styles
        },
        {
          x: 0,
          y: 0,
          opacity: 1,
          scale: 1,
          ...options.endStyles, // Add any custom end styles
          scrollTrigger: {
            trigger: isBodyTrigger ? document.body : element, // Use 'body' if specified
            start: options.start || (isBodyTrigger ? 'top top' : defaultStart),
            end: options.end || (isBodyTrigger ? 'bottom bottom' : defaultEnd),
            scrub: options.scrub === 'true' ? true : (parseFloat(options.scrub) || 1),
            pin: options.pin ? (options.pin === 'true' ? true : options.pin) : false,
            markers: options.markers === 'true',
            toggleClass: options.toggleClass || null,
            pinSpacing: options.pinSpacing || 'margin',
            invalidateOnRefresh: true,
            immediateRender: false,
          },
          stagger: splitText ? staggerValue : 0,
        }
      );

      // Apply rotation if specified
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
