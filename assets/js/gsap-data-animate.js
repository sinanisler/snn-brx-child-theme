gsap.registerPlugin(ScrollTrigger);

window.onload = function () {
  setTimeout(() => {
    const animateElements = document.querySelectorAll('[data-animate]');

    // -- DEVICE UTILS --
    function getDevice() {
      const width = window.innerWidth;
      if (width >= 991) return 'desktop';
      if (width < 991 && width > 767) return 'tablet';
      return 'mobile';
    }
    function shouldDisableForDevice(options) {
      const device = getDevice();
      if (device === 'desktop' && options.desktop === 'false') return true;
      if (device === 'tablet' && options.tablet === 'false') return true;
      if (device === 'mobile' && options.mobile === 'false') return true;
      return false;
    }

    function addVisibilityCallback(props) {
      const originalOnStart = props.onStart;
      props.onStart = function () {
        this.targets().forEach(el => {
          el.style.visibility = "visible";
        });
        if (originalOnStart) {
          originalOnStart.call(this);
        }
      };
      return props;
    }

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

      // --- DEVICE DISABLING: SKIP IF DISABLED FOR CURRENT DEVICE ---
      if (shouldDisableForDevice(firstOptions)) return;

      if (firstOptions.trigger === 'true') {
        const timeline = gsap.timeline({ paused: true });
        animations.forEach(animation => {
          const options = parseAnimationOptions(animation);
          if (shouldDisableForDevice(options)) return; // skip this part if disabled
          const hasRotate = (options.startStyles.rotate !== undefined || options.endStyles.rotate !== undefined);
          const rotateProp = options.endStyles.rotate !== undefined
            ? { rotate: parseFloat(options.endStyles.rotate) }
            : (options.startStyles.rotate !== undefined
                ? { rotate: 0 }
                : (options.r || options.rotate
                    ? { rotate: randomizeValue(options.r || options.rotate, options.rand === 'true') }
                    : {}));
          let cleanEndStyles = { ...options.endStyles };
          delete cleanEndStyles.rotate;
          const animationProps = {
            ...(options.x ? { x: randomizeValue(options.x, options.rand === 'true') } : {}),
            ...(options.y ? { y: randomizeValue(options.y, options.rand === 'true') } : {}),
            ...(options.s || options.scale ? { scale: parseFloat(options.s || options.scale) } : {}),
            ...rotateProp,
            ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
            ...cleanEndStyles,
            duration: options.duration || 1,
            delay: options.delay || 0,
            stagger: options.stagger ? getStaggerValue(options) : 0,
            ...(options.ease ? { ease: options.ease } : {}),
            ...(hasRotate ? { force3D: false } : {})
          };
          timeline.to(
            splitText(element, options),
            addVisibilityCallback(animationProps)
          );
        });
        element._gsapAnimationInstance = timeline;
      } else if (animations.length > 1) {
        gsap.set(splitText(element, firstOptions), firstOptions.startStyles);
        const timeline = gsap.timeline({
          paused: firstOptions.scroll === 'false',
          scrollTrigger: createScrollTriggerConfig(firstOptions, element)
        });
        animations.forEach((animation, index) => {
          const options = parseAnimationOptions(animation);
          if (shouldDisableForDevice(options)) return; // skip this part if disabled
          const hasRotate = (options.startStyles.rotate !== undefined || options.endStyles.rotate !== undefined);
          const rotateProp = options.endStyles.rotate !== undefined
            ? { rotate: parseFloat(options.endStyles.rotate) }
            : (options.startStyles.rotate !== undefined
                ? { rotate: 0 }
                : (options.r || options.rotate
                    ? { rotate: randomizeValue(options.r || options.rotate, options.rand === 'true') }
                    : {}));
          let cleanEndStyles = { ...options.endStyles };
          delete cleanEndStyles.rotate;
          const animationProps = {
            ...(options.x ? { x: randomizeValue(options.x, options.rand === 'true') } : {}),
            ...(options.y ? { y: randomizeValue(options.y, options.rand === 'true') } : {}),
            ...(options.s || options.scale ? { scale: parseFloat(options.s || options.scale) } : {}),
            ...rotateProp,
            ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
            ...cleanEndStyles,
            duration: options.duration || 1,
            delay: options.delay || 0,
            stagger: options.stagger ? getStaggerValue(options) : 0,
            ...(options.ease ? { ease: options.ease } : {}),
            ...(hasRotate ? { force3D: false } : {})
          };
          if (options.stagger) {
            animationProps.immediateRender = false;
          }
          timeline.to(
            splitText(element, options),
            addVisibilityCallback(animationProps),
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
        if (shouldDisableForDevice(options)) return;
        const scrollTriggerConfig = createScrollTriggerConfig(options, element);
        const hasRotate = (options.startStyles.rotate !== undefined || options.endStyles.rotate !== undefined);
        let cleanStartStyles = { ...options.startStyles };
        delete cleanStartStyles.rotate;
        let cleanEndStyles = { ...options.endStyles };
        delete cleanEndStyles.rotate;
        const fromProps = {
          ...(options.x ? { x: randomizeValue(options.x, options.rand === 'true') } : {}),
          ...(options.y ? { y: randomizeValue(options.y, options.rand === 'true') } : {}),
          ...(options.s || options.scale ? { scale: parseFloat(options.s || options.scale) } : {}),
          ...(options.startStyles.rotate !== undefined
            ? { rotate: parseFloat(options.startStyles.rotate) }
            : (options.r || options.rotate
                ? { rotate: randomizeValue(options.r || options.rotate, options.rand === 'true') }
                : {})),
          ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
          ...cleanStartStyles,
          ...(hasRotate ? { force3D: false } : {})
        };
        const toProps = {
          ...(options.x ? { x: 0 } : {}),
          ...(options.y ? { y: 0 } : {}),
          ...(options.s || options.scale ? { scale: 1 } : {}),
          ...(options.endStyles.rotate !== undefined
            ? { rotate: parseFloat(options.endStyles.rotate) }
            : (options.startStyles.rotate !== undefined
                ? { rotate: 0 }
                : (options.r || options.rotate
                    ? { rotate: 0 }
                    : {}))),
          ...(options.o || options.opacity ? { opacity: parseFloat(options.o || options.opacity) } : {}),
          ...cleanEndStyles,
          scrollTrigger: scrollTriggerConfig !== false ? scrollTriggerConfig : null,
          stagger: options.stagger ? getStaggerValue(options) : 0,
          duration: options.duration || 1,
          delay: options.delay || 0,
          paused: options.scroll === 'false',
          ...(options.ease ? { ease: options.ease } : {}),
          ...(hasRotate ? { force3D: false } : {})
        };
        if (options.stagger) {
          toProps.immediateRender = false;
        }
        const targets = splitText(element, options);
        if (options.stagger) {
          gsap.set(targets, fromProps);
        }
        const tween = gsap.fromTo(
          targets,
          fromProps,
          addVisibilityCallback(toProps)
        );
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
        option = option.trim();
        let regex = /^(style_(start|end))-(\w+)\(([^)]+)\)$/;
        let match = option.match(regex);
        if (match) {
          let type = match[1];
          let prop = match[3];
          let value = match[4];
          if (type === "style_start") {
            acc.startStyles[prop] = value;
          } else {
            acc.endStyles[prop] = value;
          }
          return acc;
        }
        let index = option.indexOf(':');
        if (index === -1) {
          return acc;
        }
        let key = option.substring(0, index).trim();
        let value = option.substring(index + 1).trim();
        // ADD: device disable options support
        if (
          (key === 'desktop' || key === 'tablet' || key === 'mobile')
          && (value === 'false' || value === 'true')
        ) {
          acc[key] = value;
          return acc;
        }
        if (key.startsWith('style_start-')) {
          const cssProp = key.replace('style_start-', '').trim();
          if (cssProp === 'transform' && value.includes('rotate(')) {
            const match = value.match(/rotate\((-?\d+(?:\.\d+)?)deg\)/);
            if (match) {
              acc.startStyles.rotate = match[1] + 'deg';
            }
          } else {
            acc.startStyles[cssProp] = value;
          }
        } else if (key.startsWith('style_end-')) {
          const cssProp = key.replace('style_end-', '').trim();
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
      const finalEnd = parseStartEndValue(options.end, isBodyTrigger ? 'bottom bottom' : defaultEnd);
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
        immediateRender: options.stagger ? false : true,
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
      if (!options.splittext) {
        const childElements = element.children;
        if (options.stagger && childElements.length > 1) {
          return childElements;
        }
        return element;
      }
      const type = options.splittext.toLowerCase();
      const startStylesString = convertStylesToString(options.startStyles);

      if (type === 'line') {
        let children = Array.from(element.children).filter(child => {
          return child.nodeType === 1;
        });
        if (children.length > 0) {
          return children;
        }
        if (element.childNodes.length === 1 && element.childNodes[0].nodeType === 3) {
          const span = document.createElement('span');
          span.style.display = 'block';
          span.style.position = 'relative';
          if (startStylesString) span.setAttribute('style', span.getAttribute('style') + startStylesString);
          span.textContent = element.textContent;
          element.innerHTML = '';
          element.appendChild(span);
          return [span];
        }
        return [];
      }

      if (type === 'true' || type === 'word') {
        let html = '';
        let childNodes = Array.from(element.childNodes);
        childNodes.forEach(node => {
          if (node.nodeType === 3) {
            let txt = node.textContent;
            let parts = (type === 'true')
              ? txt.split('')
              : txt.split(/(\s+)/);
            parts.forEach(part => {
              if (part === '') return;
              if (part.match(/^\s+$/)) {
                html += `<span style="display:inline-block; position:relative; white-space:pre;">${part}</span>`;
              } else {
                html += `<span style="display:inline-block; position:relative; ${startStylesString}">${part}</span>`;
              }
            });
          } else if (node.nodeType === 1 && node.tagName === "P") {
            html += `<p>`;
            let txt = node.textContent;
            let parts = (type === 'true')
              ? txt.split('')
              : txt.split(/(\s+)/);
            parts.forEach(part => {
              if (part === '') return;
              if (part.match(/^\s+$/)) {
                html += `<span style="display:inline-block; position:relative; white-space:pre;">${part}</span>`;
              } else {
                html += `<span style="display:inline-block; position:relative; ${startStylesString}">${part}</span>`;
              }
            });
            html += `</p>`;
          } else if (node.nodeType === 1) {
            // other tags: just keep as is
            html += node.outerHTML;
          }
        });
        element.innerHTML = html;
        return element.querySelectorAll('span');
      } else {
        return element;
      }
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
