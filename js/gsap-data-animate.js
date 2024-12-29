gsap.registerPlugin(ScrollTrigger);

window.onload = function () {
  setTimeout(() => {
    const animateElements = document.querySelectorAll('[data-animate]');

    animateElements.forEach((element) => {
      // Parse multiple `data-animate` attributes, separated by semicolons
      const animations = element.getAttribute('data-animate').split(';').map(anim => anim.trim()).filter(Boolean);

      if (animations.length > 1) {
        // Create a GSAP timeline for multiple animations
        const timeline = gsap.timeline();

        animations.forEach((animation) => {
          const options = parseAnimationOptions(animation);
          const scrollTriggerConfig = createScrollTriggerConfig(options, element);

          timeline.to(
            splitText(element, options),
            {
              x: options.x ? parseInt(options.x) : 0,
              y: options.y ? parseInt(options.y) : 0,
              opacity: options.o || options.opacity || 1,
              scale: options.s || options.scale || 1,
              rotate: options.r || options.rotate || 0,
              duration: options.duration || 1,
              delay: options.delay || 0,
              ...options.endStyles,
              stagger: options.stagger ? parseFloat(options.stagger) : 0,
              scrollTrigger: scrollTriggerConfig,
            },
            options.startTime || 0 // Start time for timeline animations
          );
        });
      } else {
        // Single animation: process as in the original code
        const options = parseAnimationOptions(animations[0]);
        const scrollTriggerConfig = createScrollTriggerConfig(options, element);

        const animation = gsap.fromTo(
          splitText(element, options),
          {
            x: options.x ? parseInt(options.x) : 0,
            y: options.y ? parseInt(options.y) : 0,
            opacity: options.o || options.opacity || 0,
            scale: options.s || options.scale || 1,
            ...options.startStyles,
          },
          {
            x: 0,
            y: 0,
            opacity: 1,
            scale: 1,
            ...options.endStyles,
            scrollTrigger: scrollTriggerConfig,
            stagger: options.stagger ? parseFloat(options.stagger) : 0,
            duration: options.duration || 1,
            delay: options.delay || 0,
          }
        );

        if (!options.scroll) {
          ScrollTrigger.create({
            trigger: element,
            start: options.start || 'top 90%',
            onEnter: () => animation.play(),
            markers: options.markers === 'true',
          });
        }
      }
    });

    function parseAnimationOptions(data) {
      return data.split(',').reduce((acc, option) => {
        const [key, value] = option.split(':').map(item => item.trim());
        if (key.startsWith('style_start-')) {
          const cssProp = key.replace('style_start-', '');
          acc.startStyles[cssProp] = value;
        } else if (key.startsWith('style_end-')) {
          const cssProp = key.replace('style_end-', '');
          acc.endStyles[cssProp] = value;
        } else if (key === 'duration' || key === 'delay') {
          acc[key] = parseFloat(value.replace('s', ''));
        } else {
          acc[key] = value;
        }
        return acc;
      }, { startStyles: {}, endStyles: {} });
    }

    function createScrollTriggerConfig(options, element) {
      const defaultStart = 'top 90%';
      const defaultEnd = 'bottom 50%';
      const isBodyTrigger = options.trigger === 'body';

      return options.scroll !== 'false'
        ? {
            trigger: isBodyTrigger ? document.body : element,
            start: options.start || (isBodyTrigger ? 'top top' : defaultStart),
            end: options.end || (isBodyTrigger ? 'bottom bottom' : defaultEnd),
            scrub: options.scrub === 'true' ? true : parseFloat(options.scrub) || 1,
            pin: options.pin === 'true',
            markers: options.markers === 'true',
            toggleClass: options.toggleClass || null,
            pinSpacing: options.pinSpacing || 'margin',
            invalidateOnRefresh: true,
            immediateRender: true,
          }
        : false;
    }

    function splitText(element, options) {
      const splitText = options.splittext === 'true';
      if (splitText) {
        const text = element.innerText;
        const chars = text.split('');
        element.innerHTML = chars.map(char => `<span style="opacity: 0;">${char}</span>`).join('');
        return element.children;
      }
      return element;
    }
  }, 10);
};
