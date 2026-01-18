/**
 * Before/After Component JavaScript
 * Gère l'interaction du slider de comparaison d'images
 */

(function($) {
    'use strict';

    /**
     * Classe BeforeAfter
     */
    class BeforeAfter {
        constructor(element) {
            this.container = element;
            this.$container = $(element);
            this.slider = this.$container.find('.ba-slider')[0];
            this.afterImage = this.$container.find('.ba-after')[0];
            this.handle = this.$container.find('.ba-handle')[0];

            // Configuration
            this.orientation = this.$container.data('orientation') || 'horizontal';
            this.position = parseFloat(this.$container.data('position')) || 50;

            // État
            this.isDragging = false;
            this.bounds = null;

            this.init();
        }

        init() {
            // Définir la position initiale
            this.setPosition(this.position);

            // Événements
            this.bindEvents();

            // Animation d'intro (pulse)
            this.introAnimation();

            // Mise à jour lors du redimensionnement
            $(window).on('resize', this.debounce(() => {
                this.updateBounds();
            }, 250));
        }

        bindEvents() {
            // Mouse events
            this.$container.on('mousedown', this.handleStart.bind(this));
            $(document).on('mousemove', this.handleMove.bind(this));
            $(document).on('mouseup', this.handleEnd.bind(this));

            // Touch events
            this.$container.on('touchstart', this.handleStart.bind(this));
            $(document).on('touchmove', this.handleMove.bind(this));
            $(document).on('touchend', this.handleEnd.bind(this));

            // Keyboard navigation
            this.$container.attr('tabindex', '0');
            this.$container.on('keydown', this.handleKeyboard.bind(this));

            // Prevent text selection
            this.$container.on('selectstart', (e) => e.preventDefault());
        }

        handleStart(e) {
            e.preventDefault();
            this.isDragging = true;
            this.$container.addClass('ba-dragging');
            $(this.handle).removeClass('ba-pulse');
            this.updateBounds();
        }

        handleMove(e) {
            if (!this.isDragging) return;

            e.preventDefault();

            const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

            this.updatePosition(clientX, clientY);
        }

        handleEnd(e) {
            if (!this.isDragging) return;

            this.isDragging = false;
            this.$container.removeClass('ba-dragging');
        }

        handleKeyboard(e) {
            let newPosition = this.position;
            const step = 2; // Pourcentage de déplacement par touche

            if (this.orientation === 'horizontal') {
                if (e.keyCode === 37) { // Flèche gauche
                    newPosition = Math.max(0, this.position - step);
                } else if (e.keyCode === 39) { // Flèche droite
                    newPosition = Math.min(100, this.position + step);
                }
            } else {
                if (e.keyCode === 38) { // Flèche haut
                    newPosition = Math.max(0, this.position - step);
                } else if (e.keyCode === 40) { // Flèche bas
                    newPosition = Math.min(100, this.position + step);
                }
            }

            if (newPosition !== this.position) {
                e.preventDefault();
                this.setPosition(newPosition);
            }
        }

        updatePosition(clientX, clientY) {
            if (!this.bounds) {
                this.updateBounds();
            }

            let position;

            if (this.orientation === 'horizontal') {
                const x = clientX - this.bounds.left;
                position = (x / this.bounds.width) * 100;
            } else {
                const y = clientY - this.bounds.top;
                position = (y / this.bounds.height) * 100;
            }

            // Limiter entre 0 et 100
            position = Math.max(0, Math.min(100, position));

            this.setPosition(position);
        }

        setPosition(position) {
            this.position = position;

            if (this.orientation === 'horizontal') {
                // Position horizontale
                this.slider.style.left = position + '%';
                this.afterImage.style.clipPath = `polygon(0 0, ${position}% 0, ${position}% 100%, 0 100%)`;
            } else {
                // Position verticale
                this.slider.style.top = position + '%';
                this.afterImage.style.clipPath = `polygon(0 0, 100% 0, 100% ${position}%, 0 ${position}%)`;
            }
        }

        updateBounds() {
            this.bounds = this.container.getBoundingClientRect();
        }

        introAnimation() {
            // Animation de pulsation au chargement pour indiquer l'interactivité
            $(this.handle).addClass('ba-pulse');

            setTimeout(() => {
                $(this.handle).removeClass('ba-pulse');
            }, 3000);
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        destroy() {
            this.$container.off('mousedown touchstart keydown selectstart');
            $(document).off('mousemove touchmove mouseup touchend');
            $(window).off('resize');
        }
    }

    /**
     * jQuery Plugin
     */
    $.fn.beforeAfter = function(options) {
        return this.each(function() {
            const $this = $(this);
            let instance = $this.data('beforeAfter');

            if (!instance) {
                instance = new BeforeAfter(this, options);
                $this.data('beforeAfter', instance);
            }
        });
    };

    /**
     * Auto-initialisation
     */
    $(document).ready(function() {
        $('.ba-container').beforeAfter();
    });

    /**
     * UIkit Integration
     * Réinitialiser après les modifications DOM de UIkit
     */
    if (typeof UIkit !== 'undefined') {
        UIkit.util.on(document, 'afterready', function() {
            $('.ba-container').each(function() {
                if (!$(this).data('beforeAfter')) {
                    $(this).beforeAfter();
                }
            });
        });
    }

    /**
     * Exposition globale
     */
    window.BeforeAfter = BeforeAfter;

})(jQuery);
