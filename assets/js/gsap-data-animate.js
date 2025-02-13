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
      }, { threshold: 0.1 });
      observer.observe(element);
    }

    function setupTriggers() {
      const triggers = document.querySelectorAll('[data-trigger]');
      triggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
          const targetSelector = trigger.getAttribute('data-trigger');
          const targetElement = document.querySelector(targetSelector);
          if (targetElement) {
            const animation = targetElement._gsapAnimationInstance;
            if (animation) {
              animation.play(0);
            }
          }
        });
      });
    }

    function randomizeValue(val, isRandom) {
      const num = parseFloat(val);
      return isRandom ? gsap.utils.random(-Math.abs(num), Math.abs(num)) : num;
    }

    function getStaggerValue(options) {
      if (options.stagger) {
        const s = parseFloat(options.stagger);
        return (options.rand === 'true') ? { each: s, from: "random" } : s;
      }
      return 0;
    }

    animateElements.forEach(element => {
      const animations = element
        .getAttribute('data-animate')
        .split(';')
        .map(anim => anim.trim())
        .filter(Boolean);

      const firstOptions = parseAnimationOptions(animations[0]);

      // Branch 1: When trigger is explicitly set to "true"
      if (firstOptions.trigger === 'true') {
        const timeline = gsap.timeline({ paused: true });

        animations.forEach(animation => {
          const options = parseAnimationOptions(animation);
          // Check if a rotation was provided in the start/end styles.
          const hasRotate = options.startStyles.rotate || options.endStyles.rotate;

          const animationProps = {
            ...(options.x ? { x: randomizeValue(options.x, options.rand === 'true') } : {}),
            ...(options.y ? { y: randomizeValue(options.y, options.rand === 'true') } : {}),
            ...(options.s || options.scale ? { scale: parseFloat(options.s || options.scale) } : {}),
            // If a rotation is provided via the transform string, use it;
            // otherwise, check for legacy options "r" or "rotate".
            ...(options.endStyles.rotate
              ? { rotate: parseFloat(options.endStyles.rotate) }
              : (!hasRotate && (options.r || options.rotate)
                  ? { rotate: randomizeValue(options.r || options.rotate, options.rand === 'true') }
                  : {})),
            ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
            ...options.endStyles,
            duration: options.duration || 1,
            delay: options.delay || 0,
            stagger: options.stagger ? getStaggerValue(options) : 0,
            ...(hasRotate ? { force3D: false } : {})
          };

          timeline.to(splitText(element, options), animationProps);
        });

        element._gsapAnimationInstance = timeline;

      // Branch 2: When there are multiple animations
      } else if (animations.length > 1) {
        gsap.set(splitText(element, firstOptions), firstOptions.startStyles);

        const timeline = gsap.timeline({
          paused: firstOptions.scroll === 'false',
          scrollTrigger: createScrollTriggerConfig(firstOptions, element)
        });

        animations.forEach((animation, index) => {
          const options = parseAnimationOptions(animation);
          const hasRotate = options.startStyles.rotate || options.endStyles.rotate;

          const animationProps = {
            ...(options.x ? { x: randomizeValue(options.x, options.rand === 'true') } : {}),
            ...(options.y ? { y: randomizeValue(options.y, options.rand === 'true') } : {}),
            ...(options.s || options.scale ? { scale: parseFloat(options.s || options.scale) } : {}),
            ...(options.endStyles.rotate
              ? { rotate: parseFloat(options.endStyles.rotate) }
              : (!hasRotate && (options.r || options.rotate)
                  ? { rotate: randomizeValue(options.r || options.rotate, options.rand === 'true') }
                  : {})),
            ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
            ...options.endStyles,
            duration: options.duration || 1,
            delay: options.delay || 0,
            stagger: options.stagger ? getStaggerValue(options) : 0,
            ...(hasRotate ? { force3D: false } : {})
          };

          timeline.to(
            splitText(element, options),
            animationProps,
            index > 0 ? `+=${options.delay || 0}` : 0
          );
        });

        element._gsapAnimationInstance = timeline;

        if (firstOptions.scroll === 'false' && firstOptions.loop === 'true') {
          timeline.repeat(-1).yoyo(true);
        }
        if (firstOptions.scroll === 'false') {
          observeIfScrollFalse(element, timeline);
        }

      } else {
        const options = parseAnimationOptions(animations[0]);
        const scrollTriggerConfig = createScrollTriggerConfig(options, element);
        const hasRotate = options.startStyles.rotate || options.endStyles.rotate;

        const fromProps = {
          ...(options.x ? { x: randomizeValue(options.x, options.rand === 'true') } : {}),
          ...(options.y ? { y: randomizeValue(options.y, options.rand === 'true') } : {}),
          ...(options.s || options.scale ? { scale: parseFloat(options.s || options.scale) } : {}),
          ...(options.startStyles.rotate
            ? { rotate: parseFloat(options.startStyles.rotate) }
            : (!hasRotate && (options.r || options.rotate)
                ? { rotate: randomizeValue(options.r || options.rotate, options.rand === 'true') }
                : {})),
          ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
          ...options.startStyles,
          ...(hasRotate ? { force3D: false } : {})
        };

        const toProps = {
          ...(options.x ? { x: 0 } : {}),
          ...(options.y ? { y: 0 } : {}),
          ...(options.s || options.scale ? { scale: 1 } : {}),
          ...(options.endStyles.rotate
            ? { rotate: parseFloat(options.endStyles.rotate) }
            : (!hasRotate && (options.r || options.rotate)
                ? { rotate: 0 }
                : {})),
          ...(options.o || options.opacity ? { opacity: 1 } : {}),
          ...options.endStyles,
          scrollTrigger: scrollTriggerConfig !== false ? scrollTriggerConfig : null,
          stagger: options.stagger ? getStaggerValue(options) : 0,
          duration: options.duration || 1,
          delay: options.delay || 0,
          paused: options.scroll === 'false',
          ...(hasRotate ? { force3D: false } : {})
        };

        const tween = gsap.fromTo(splitText(element, options), fromProps, toProps);

        element._gsapAnimationInstance = tween;

        if (options.scroll === 'false' && options.loop === 'true') {
          tween.repeat(-1).yoyo(true);
        }
        if (options.scroll === 'false') {
          observeIfScrollFalse(element, tween);
        }
      }
    });

    setupTriggers();

    function parseAnimationOptions(data) {
      if (!data) {
        return { startStyles: {}, endStyles: {} };
      }
      return data.split(',').reduce((acc, option) => {
        const [key, value] = option.split(':').map(item => item.trim());
        if (key.startsWith('style_start-')) {
          const cssProp = key.replace('style_start-', '');
          // If a transform is specified and contains "rotate", extract the rotate value.
          if (cssProp === 'transform' && value.includes('rotate(')) {
            const match = value.match(/rotate\((-?\d+(?:\.\d+)?)deg\)/);
            if (match) {
              acc.startStyles.rotate = match[1] + 'deg';
            }
          } else {
            acc.startStyles[cssProp] = value;
          }
        } else if (key.startsWith('style_end-')) {
          const cssProp = key.replace('style_end-', '');
          if (cssProp === 'transform' && value.includes('rotate(')) {
            const match = value.match(/rotate\((-?\d+(?:\.\d+)?)deg\)/);
            if (match) {
              acc.endStyles.rotate = match[1] + 'deg';
            }
          } else {
            acc.endStyles[cssProp] = value;
          }
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

      if (options.scroll === 'false' || options.trigger === 'true') {
        return false;
      }

      const finalStart = parseStartEndValue(options.start, isBodyTrigger ? 'top top' : defaultStart);
      const finalEnd   = parseStartEndValue(options.end,   isBodyTrigger ? 'bottom bottom' : defaultEnd);

      return {
        trigger: isBodyTrigger ? document.body : element,
        start: finalStart,
        end: finalEnd,
        scrub: options.scrub === 'true' ? true : parseFloat(options.scrub) || 1,
        pin: options.pin === 'true',
        
        markers: (options.markers === 'true' && options.scroll !== 'false') ? true : false,
        toggleClass: options.toggleClass || null,
        pinSpacing: options.pinSpacing || 'margin',
        invalidateOnRefresh: true,
        immediateRender: true,
        animation: gsap.timeline({ paused: true })
      };
    }

    function parseStartEndValue(value, defaultValue) {
      if (!value) {
        return defaultValue;
      }

      if (/\s/.test(value)) {
        return value; 
      }

      if (/^\d+(\.\d+)?(px)?$/i.test(value)) {
        return 'top+=' + value;
      }

      if (/^\d+(\.\d+)?%$/.test(value)) {
        return 'top ' + value;
      }

      return value;
    }

    function splitText(element, options) {
      const shouldSplitText = options.splittext === 'true';
      if (shouldSplitText) {
        const text = element.innerText;
        const chars = text.split('');
        const startStylesString = convertStylesToString(options.startStyles);
        element.innerHTML = chars
          .map(char => `<span style="display:inline-block; position:relative; ${startStylesString}">${char}</span>`)
          .join('');
        return element.children;
      }
      return element;
    }

    function convertStylesToString(styles) {
      let styleString = '';
      Object.entries(styles).forEach(([key, value]) => {
        if (key === 'rotate') {
          styleString += `transform: rotate(${value}); `;
        } else {
          const kebabKey = key.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
          styleString += `${kebabKey}: ${value}; `;
        }
      });
      return styleString.trim();
    }
  }, 10);
};
