/**
 * @file plugins/themes/default/js/main.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Handle JavaScript functionality unique to this theme.
 */
(function($) {

	// Initialize dropdown navigation menus on large screens
	if (typeof $.fn.dropdown !== 'undefined') {
		var $nav = $('#navigationPrimary, #navigationUser'),
		$submenus = $('ul', $nav);
		function toggleDropdowns() {
			if (window.innerWidth > 992) {
				$submenus.each(function(i) {
					var id = 'pkpDropdown' + i;
					$(this)
						.addClass('dropdown-menu')
						.attr('aria-labelledby', id);
					$(this).siblings('a')
						.attr('data-toggle', 'dropdown')
						.attr('aria-haspopup', true)
						.attr('aria-expanded', false)
						.attr('id', id)
						.attr('href', '#');
				});
				$('[data-toggle="dropdown"]').dropdown();

			} else {
				$('[data-toggle="dropdown"]').dropdown('dispose');
				$submenus.each(function(i) {
					$(this)
						.removeClass('dropdown-menu')
						.removeAttr('aria-labelledby');
					$(this).siblings('a')
						.removeAttr('data-toggle')
						.removeAttr('aria-haspopup')
						.removeAttr('aria-expanded')
						.removeAttr('id')
						.attr('href', '#');
				});
			}
		}
		window.onresize = toggleDropdowns;
		$().ready(function() {
			toggleDropdowns();
		});
	}

	// Toggle nav menu on small screens
	$('.pkp_site_nav_toggle').click(function(e) {
		$('.pkp_site_nav_menu').toggleClass('pkp_site_nav_menu--isOpen');
		$('.pkp_site_nav_toggle').toggleClass('pkp_site_nav_toggle--transform');
	});

	// Modify the Chart.js display options used by UsageStats plugin
	document.addEventListener('usageStatsChartOptions.pkp', function(e) {
		e.chartOptions.elements.line.backgroundColor = 'rgba(0, 122, 178, 0.6)';
		e.chartOptions.elements.bar.backgroundColor = 'rgba(0, 122, 178, 0.6)';
	});

	// Toggle display of consent checkboxes in site-wide registration
	var $contextOptinGroup = $('#contextOptinGroup');
	if ($contextOptinGroup.length) {
		var $roles = $contextOptinGroup.find('.roles :checkbox');
		$roles.change(function() {
			var $thisRoles = $(this).closest('.roles');
			if ($thisRoles.find(':checked').length) {
				$thisRoles.siblings('.context_privacy').addClass('context_privacy_visible');
			} else {
				$thisRoles.siblings('.context_privacy').removeClass('context_privacy_visible');
			}
		});
	}

	// Show or hide the reviewer interests field on the registration form
	function reviewerInterestsToggle() {
		var is_checked = false;
		$('#reviewerOptinGroup').find('input').each(function() {
			if ($(this).is(':checked')) {
				is_checked = true;
				return false;
			}
		});
		if (is_checked) {
			$('#reviewerInterests').addClass('is_visible');
		} else {
			$('#reviewerInterests').removeClass('is_visible');
		}
	}

	reviewerInterestsToggle();
	$('#reviewerOptinGroup input').on('click', reviewerInterestsToggle);

	// Initialize Swiper for highlights
	var swiper = new Swiper('.swiper', {
		a11y: {
			prevSlideMessage: pkpDefaultThemeI18N.prevSlide,
			nextSlideMessage: pkpDefaultThemeI18N.nextSlide,
		},
		autoHeight: true,
		navigation: {
			nextEl: '.swiper-button-next',
			prevEl: '.swiper-button-prev',
		},
		pagination: {
			el: '.swiper-pagination',
			type: 'bullets',
		}
	});

	// ========================================
	// CUSTOM: Homepage Carousel
	// ========================================
	$(document).ready(function() {
		var carousel = document.querySelector('.carousel');
		var slides = document.querySelectorAll('.carousel-slide');

		if (!slides.length || !carousel) {
			return;
		}

		// PRELOAD ALL IMAGES - prevents blink when switching slides
		slides.forEach(function(slide) {
			var img = slide.querySelector('img');
			if (img && img.src) {
				var preload = new Image();
				preload.src = img.src;
			}
		});

		var current = 0;
		var autoplayInterval = null;
		var autoplayDelay = 5000;
		var isAnimating = false;

		function showSlide(index) {
			// Prevent double-triggering during transition
			if (isAnimating) return;
			isAnimating = true;

			if (index >= slides.length) {
				index = 0;
			} else if (index < 0) {
				index = slides.length - 1;
			}

			slides.forEach(function(slide) {
				slide.classList.remove('active');
			});

			slides[index].classList.add('active');
			current = index;

			// Release lock after transition completes (0.6s + buffer)
			setTimeout(function() {
				isAnimating = false;
			}, 650);
		}

		function nextSlide() {
			showSlide(current + 1);
		}

		function prevSlide() {
			showSlide(current - 1);
		}

		function startAutoplay() {
			stopAutoplay();
			autoplayInterval = setInterval(nextSlide, autoplayDelay);
		}

		function stopAutoplay() {
			if (autoplayInterval) {
				clearInterval(autoplayInterval);
				autoplayInterval = null;
			}
		}

		// Next button
		var nextBtn = carousel.querySelector('.carousel-next');
		if (nextBtn) {
			nextBtn.addEventListener('click', function(e) {
				e.preventDefault();
				nextSlide();
				startAutoplay();
			});
		}

		// Prev button
		var prevBtn = carousel.querySelector('.carousel-prev');
		if (prevBtn) {
			prevBtn.addEventListener('click', function(e) {
				e.preventDefault();
				prevSlide();
				startAutoplay();
			});
		}

		// Pause on hover
		carousel.addEventListener('mouseenter', stopAutoplay);
		carousel.addEventListener('mouseleave', startAutoplay);

		// Start
		startAutoplay();
	});

})(jQuery);