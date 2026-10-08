/**
 * VF Hero Slider - High Performance Vanilla JS Carousel Engine
 */
(function ($) {
    'use strict';

    /**
     * Slider Instance Class
     */
    function VFHeroSlider(element) {
        this.slider = element;
        this.$slider = $(element);

        // Guard against double initialization
        if (this.$slider.data('vf-slider-init')) {
            return;
        }
        this.$slider.data('vf-slider-init', true);

        // Parse config
        var rawConfig = this.slider.getAttribute('data-slider-config');
        try {
            this.config = JSON.parse(rawConfig || '{}');
        } catch (e) {
            this.config = {};
        }

        this.slides = Array.prototype.slice.call(this.slider.querySelectorAll('.vf-hero-slide'));
        this.total = this.slides.length;
        if (this.total <= 1) {
            return; // No need for carousel engine with single slide
        }

        this.currentIndex = 0;
        this.isAnimating = false;
        this.autoplayTimer = null;
        this.progressAnimFrame = null;
        this.isPaused = false;

        // UI Elements
        this.btnPrev = this.slider.querySelector('.vf-hero-arrow--prev');
        this.btnNext = this.slider.querySelector('.vf-hero-arrow--next');
        this.dots = Array.prototype.slice.call(this.slider.querySelectorAll('.vf-hero-dot'));
        this.progressBar = this.slider.querySelector('.vf-hero-progress-bar');
        this.counterCurrent = this.slider.querySelector('.vf-counter-current');

        this.init();
    }

    VFHeroSlider.prototype.init = function () {
        var self = this;

        // Set initial state
        this.updateState(0);

        // Bind Controls
        if (this.btnPrev) {
            this.btnPrev.addEventListener('click', function (e) {
                e.preventDefault();
                self.prev();
                if (self.config.pauseOnInteraction) self.stopAutoplay();
            });
        }

        if (this.btnNext) {
            this.btnNext.addEventListener('click', function (e) {
                e.preventDefault();
                self.next();
                if (self.config.pauseOnInteraction) self.stopAutoplay();
            });
        }

        // Dots
        this.dots.forEach(function (dot) {
            dot.addEventListener('click', function (e) {
                e.preventDefault();
                var targetIdx = parseInt(this.getAttribute('data-slide-target'), 10);
                if (!isNaN(targetIdx) && targetIdx !== self.currentIndex) {
                    self.goTo(targetIdx);
                    if (self.config.pauseOnInteraction) self.stopAutoplay();
                }
            });
        });

        // Touch & Swipe gestures
        if (this.config.swipe) {
            this.initSwipe();
        }

        // Keyboard navigation
        if (this.config.keyboard) {
            this.slider.setAttribute('tabindex', '0');
            this.slider.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowLeft') {
                    self.prev();
                } else if (e.key === 'ArrowRight') {
                    self.next();
                }
            });
        }

        // Pause on Hover
        if (this.config.pauseOnHover) {
            this.slider.addEventListener('mouseenter', function () {
                self.isPaused = true;
                self.pauseAutoplay();
            });
            this.slider.addEventListener('mouseleave', function () {
                self.isPaused = false;
                self.resumeAutoplay();
            });
        }

        // Intersection Observer (pause autoplay when out of view)
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        if (!self.isPaused) self.startAutoplay();
                    } else {
                        self.stopAutoplay();
                    }
                });
            }, { threshold: 0.25 });
            this.observer.observe(this.slider);
        } else {
            this.startAutoplay();
        }

        // Pause when tab hidden
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                self.stopAutoplay();
            } else if (!self.isPaused) {
                self.startAutoplay();
            }
        });

        // Start autoplay
        if (this.config.autoplay) {
            this.startAutoplay();
        }
    };

    /**
     * Touch & Pointer Swipe
     */
    VFHeroSlider.prototype.initSwipe = function () {
        var self = this;
        var startX = 0;
        var startY = 0;
        var distX = 0;
        var distY = 0;
        var threshold = 45; // min px to trigger slide
        var restraint = 100; // max allowed perpendicular deviation
        var isTouching = false;

        this.slider.addEventListener('touchstart', function (e) {
            var touchObj = e.changedTouches[0];
            startX = touchObj.pageX;
            startY = touchObj.pageY;
            isTouching = true;
        }, { passive: true });

        this.slider.addEventListener('touchend', function (e) {
            if (!isTouching) return;
            var touchObj = e.changedTouches[0];
            distX = touchObj.pageX - startX;
            distY = touchObj.pageY - startY;
            isTouching = false;

            if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint) {
                if (distX > 0) {
                    self.prev();
                } else {
                    self.next();
                }
                if (self.config.pauseOnInteraction) self.stopAutoplay();
            }
        }, { passive: true });
    };

    /**
     * Navigate to specific slide
     */
    VFHeroSlider.prototype.goTo = function (targetIndex, direction) {
        if (this.isAnimating || targetIndex === this.currentIndex) return;

        var prevIndex = this.currentIndex;
        var isForward = (typeof direction !== 'undefined') ? (direction === 'next') : (targetIndex > prevIndex);

        this.isAnimating = true;
        var prevSlide = this.slides[prevIndex];
        var nextSlide = this.slides[targetIndex];

        // Prepare next slide
        nextSlide.classList.remove('is-active', 'is-prev');
        nextSlide.classList.add('is-preparing');
        if (this.config.transition === 'slide') {
            nextSlide.style.transform = isForward ? 'translate3d(100%, 0, 0)' : 'translate3d(-100%, 0, 0)';
        }

        // Force browser reflow to reset CSS transitions and animations
        void nextSlide.offsetWidth;

        // Animate
        nextSlide.classList.remove('is-preparing');
        nextSlide.classList.add('is-active', 'is-loaded');
        nextSlide.setAttribute('aria-hidden', 'false');

        if (this.config.transition === 'slide') {
            nextSlide.style.transform = 'translate3d(0, 0, 0)';
            prevSlide.style.transform = isForward ? 'translate3d(-100%, 0, 0)' : 'translate3d(100%, 0, 0)';
        }

        prevSlide.classList.remove('is-active');
        prevSlide.setAttribute('aria-hidden', 'true');

        // Update Dots & Counter
        this.updateState(targetIndex);
        this.currentIndex = targetIndex;

        // Preload upcoming slide
        this.preloadNeighbor(targetIndex);

        // Reset animation lock after duration
        var duration = this.config.transitionSpeed || 700;
        var self = this;
        setTimeout(function () {
            self.isAnimating = false;
            if (self.config.transition === 'slide') {
                prevSlide.style.transform = '';
            }
        }, duration);

        // Reset autoplay cycle
        if (this.config.autoplay && !this.isPaused) {
            this.resetAutoplayProgress();
        }
    };

    VFHeroSlider.prototype.next = function () {
        var nextIndex = this.currentIndex + 1;
        if (nextIndex >= this.total) {
            if (this.config.infinite) {
                nextIndex = 0;
            } else {
                return;
            }
        }
        this.goTo(nextIndex, 'next');
    };

    VFHeroSlider.prototype.prev = function () {
        var prevIndex = this.currentIndex - 1;
        if (prevIndex < 0) {
            if (this.config.infinite) {
                prevIndex = this.total - 1;
            } else {
                return;
            }
        }
        this.goTo(prevIndex, 'prev');
    };

    /**
     * Preload neighbor slides for instant transition
     */
    VFHeroSlider.prototype.preloadNeighbor = function (currentIndex) {
        var nextIndex = (currentIndex + 1) % this.total;
        var nextSlide = this.slides[nextIndex];
        if (nextSlide && !nextSlide.classList.contains('is-loaded')) {
            var img = nextSlide.querySelector('img');
            if (img && img.getAttribute('loading') === 'lazy') {
                img.removeAttribute('loading');
            }
            nextSlide.classList.add('is-loaded');
        }
    };

    /**
     * Update active UI controls
     */
    VFHeroSlider.prototype.updateState = function (activeIndex) {
        // Update dots
        if (this.dots.length) {
            this.dots.forEach(function (dot, idx) {
                if (idx === activeIndex) {
                    dot.classList.add('is-active');
                    dot.setAttribute('aria-selected', 'true');
                } else {
                    dot.classList.remove('is-active');
                    dot.setAttribute('aria-selected', 'false');
                }
            });
        }

        // Update Counter
        if (this.counterCurrent) {
            var num = activeIndex + 1;
            this.counterCurrent.textContent = (num < 10 ? '0' : '') + num;
        }
    };

    /**
     * Autoplay Engine
     */
    VFHeroSlider.prototype.startAutoplay = function () {
        if (!this.config.autoplay) return;
        this.stopAutoplay();

        var self = this;
        var speed = this.config.autoplaySpeed || 5000;

        // Start progress bar animation
        this.animateProgressBar(speed);

        this.autoplayTimer = setTimeout(function () {
            self.next();
            self.startAutoplay();
        }, speed);
    };

    VFHeroSlider.prototype.pauseAutoplay = function () {
        if (this.autoplayTimer) {
            clearTimeout(this.autoplayTimer);
            this.autoplayTimer = null;
        }
        if (this.progressBar) {
            this.progressBar.style.transition = 'none';
        }
    };

    VFHeroSlider.prototype.resumeAutoplay = function () {
        if (this.config.autoplay) {
            this.startAutoplay();
        }
    };

    VFHeroSlider.prototype.stopAutoplay = function () {
        if (this.autoplayTimer) {
            clearTimeout(this.autoplayTimer);
            this.autoplayTimer = null;
        }
        if (this.progressBar) {
            this.progressBar.style.width = '0%';
            this.progressBar.style.transition = 'none';
        }
    };

    VFHeroSlider.prototype.resetAutoplayProgress = function () {
        this.stopAutoplay();
        this.startAutoplay();
    };

    VFHeroSlider.prototype.animateProgressBar = function (duration) {
        if (!this.progressBar) return;
        var bar = this.progressBar;
        bar.style.transition = 'none';
        bar.style.width = '0%';

        // Trigger reflow
        void bar.offsetWidth;

        bar.style.transition = 'width ' + duration + 'ms linear';
        bar.style.width = '100%';
    };

    /**
     * Elementor Widget Initialization Handler
     */
    function initVFHeroSlider($scope) {
        var $slider = $scope.find('.vf-hero-slider');
        if (!$slider.length) {
            $slider = $scope.hasClass('vf-hero-slider') ? $scope : null;
        }
        if ($slider && $slider.length) {
            $slider.each(function () {
                new VFHeroSlider(this);
            });
        }
    }

    // Register with Elementor Frontend Hook
    $(window).on('elementor/frontend/init', function () {
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            window.elementorFrontend.hooks.addAction(
                'frontend/element_ready/vf-hero-slider.default',
                initVFHeroSlider
            );
        }
    });

    // Fallback for standard DOM ready
    $(document).ready(function () {
        $('.vf-hero-slider').each(function () {
            new VFHeroSlider(this);
        });
    });

})(jQuery);
