/**
 * Custom Carousel JavaScript
 * OJS 3.5.0.5 Compatible
 */

document.addEventListener("DOMContentLoaded", function () {

    const carousel = document.querySelector(".carousel");
    const slides = document.querySelectorAll(".carousel-slide");

    if (!slides.length || !carousel) {
        return;
    }

    let current = 0;
    let autoplayInterval = null;
    const autoplayDelay = 5000;

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove("active"));
        
        if (index >= slides.length) {
            index = 0;
        } else if (index < 0) {
            index = slides.length - 1;
        }
        
        slides[index].classList.add("active");
        current = index;
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

    // Navigation buttons
    document.querySelector(".carousel-next")?.addEventListener("click", function(e) {
        e.preventDefault();
        nextSlide();
        startAutoplay();
    });

    document.querySelector(".carousel-prev")?.addEventListener("click", function(e) {
        e.preventDefault();
        prevSlide();
        startAutoplay();
    });

    // Pause on hover
    carousel.addEventListener("mouseenter", stopAutoplay);
    carousel.addEventListener("mouseleave", startAutoplay);

    // Touch swipe
    let touchStartX = 0;
    carousel.addEventListener("touchstart", function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    carousel.addEventListener("touchend", function(e) {
        const diff = touchStartX - e.changedTouches[0].screenX;
        if (Math.abs(diff) > 50) {
            diff > 0 ? nextSlide() : prevSlide();
            startAutoplay();
        }
    }, { passive: true });

    // Keyboard navigation
    document.addEventListener("keydown", function(e) {
        const rect = carousel.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            if (e.key === "ArrowLeft") { e.preventDefault(); prevSlide(); startAutoplay(); }
            if (e.key === "ArrowRight") { e.preventDefault(); nextSlide(); startAutoplay(); }
        }
    });

    startAutoplay();
});