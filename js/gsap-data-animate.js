gsap.registerPlugin(ScrollTrigger);

window.onload = function () {
  setTimeout(() => {
    const animateElements = document.querySelectorAll('[data-animate]');

    function observeIfScrollFalse(element, animationInstance) {
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animationInstance.play();
            obs.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.1
      });
      observer.observe(element);
    }

    animateElements.forEach((element) => {
      const animations = element
        .getAttribute('data-animate')
        .split(';')
        .map(anim => anim.trim())
        .filter(Boolean);

      if (animations.length > 1) {
        const firstOptions = parseAnimationOptions(animations[0]);
        
        gsap.set(splitText(element, firstOptions), firstOptions.startStyles);

        const timeline = gsap.timeline({
          paused: firstOptions.scroll === 'false',  // If scroll:false, start paused
          scrollTrigger: createScrollTriggerConfig(firstOptions, element)
        });

        animations.forEach((animation, index) => {
          const options = parseAnimationOptions(animation);

          timeline.to(
            splitText(element, options),
            {
              ...(options.x ? { x: parseInt(options.x) } : {}),
              ...(options.y ? { y: parseInt(options.y) } : {}),
              ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
              ...(options.s || options.scale ? { scale: parseFloat(options.s || options.scale) } : {}),
              ...(options.r || options.rotate ? { rotate: parseFloat(options.r || options.rotate) } : {}),
              ...options.endStyles,
              duration: options.duration || 1,
              delay: options.delay || 0,
              stagger: options.stagger ? parseFloat(options.stagger) : 0,
            },
            index > 0 ? `+=${options.delay || 0}` : 0
          );
        });

        if (firstOptions.scroll === 'false') {
          observeIfScrollFalse(element, timeline);
        }

      } else {
        const options = parseAnimationOptions(animations[0]);
        const scrollTriggerConfig = createScrollTriggerConfig(options, element);

        const tween = gsap.fromTo(
          splitText(element, options),
          {
            ...(options.x ? { x: parseInt(options.x) } : {}),
            ...(options.y ? { y: parseInt(options.y) } : {}),
            ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
            ...(options.s || options.scale ? { scale: parseFloat(options.s || options.scale) } : {}),
            ...(options.r || options.rotate ? { rotate: parseFloat(options.r || options.rotate) } : {}),
            ...options.startStyles,
          },
          {
            ...(options.x ? { x: 0 } : {}),
            ...(options.y ? { y: 0 } : {}),
            ...(options.o || options.opacity ? { opacity: 1 } : {}),
            ...(options.s || options.scale ? { scale: 1 } : {}),
            ...(options.r || options.rotate ? { rotate: 0 } : {}),
            ...options.endStyles,
            scrollTrigger: scrollTriggerConfig !== false ? scrollTriggerConfig : null,
            stagger: options.stagger ? parseFloat(options.stagger) : 0,
            duration: options.duration || 1,
            delay: options.delay || 0,
            paused: options.scroll === 'false', 
          }
        );

        if (options.scroll === 'false') {
          observeIfScrollFalse(element, tween);
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
      const defaultStart = 'top 60%';
      const defaultEnd = 'bottom 40%';
      const isBodyTrigger = options.trigger === 'body';

      if (options.scroll === 'false') {
        return false;
      }

      return {
        trigger: isBodyTrigger ? document.body : element,
        start: options.start || (isBodyTrigger ? 'top top' : defaultStart),
        end: options.end || (isBodyTrigger ? 'bottom bottom' : defaultEnd),
        scrub: options.scrub === 'true' ? true : parseFloat(options.scrub) || 1,
        pin: options.pin === 'true',
        markers: options.markers === 'true',
        toggleClass: options.toggleClass || null,
        pinSpacing: options.pinSpacing || 'margin',
        invalidateOnRefresh: true,
        immediateRender: false,
        animation: gsap.timeline({ paused: true })
      };
    }

    function splitText(element, options) {
      const splitText = options.splittext === 'true';
      if (splitText) {
        const text = element.innerText;
        const chars = text.split('');
        element.innerHTML = chars
          .map(char => `<span style="opacity: 0;">${char}</span>`)
          .join('');
        return element.children;
      }
      return element;
    }
  }, 10);
};