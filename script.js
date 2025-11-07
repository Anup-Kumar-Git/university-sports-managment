// ===== Modal Logic =====
const modal = document.getElementById("modal");
const modalText = document.getElementById("modalText");
const closeBtn = document.querySelector(".close");

document.querySelectorAll(".sport_card button").forEach(btn => {
    btn.addEventListener("click", () => {
        modal.classList.add("show");
        modalText.textContent = btn.getAttribute("data-info");
        showToast("Information loaded successfully");
    });
});

closeBtn.onclick = () => { modal.classList.remove("show"); };
window.onclick = (event) => { if (event.target === modal) modal.classList.remove("show"); };

// ===== Search Filter =====
const searchBar = document.getElementById("searchBar");
const cards = document.querySelectorAll(".sport_card");

searchBar.addEventListener("keyup", () => {
    let query = searchBar.value.toLowerCase();
    let visibleCount = 0;
    
    cards.forEach(card => {
        let sportName = card.querySelector("h3").textContent.toLowerCase();
        if (sportName.includes(query)) {
            card.style.display = "block";
            visibleCount++;
            // Add a slight delay for staggered appearance
            setTimeout(() => {
                card.style.opacity = "1";
                card.style.transform = "translateY(0)";
            }, 100 * visibleCount);
        } else {
            card.style.opacity = "0";
            card.style.transform = "translateY(20px)";
            setTimeout(() => {
                card.style.display = "none";
            }, 300);
        }
    });
    
    if (visibleCount === 0 && query !== "") {
        showToast("No sports found matching your search");
    }
});

// ===== Carousel =====
const slides = document.querySelector(".slides");
const images = slides.querySelectorAll("img");
const prev = document.querySelector(".prev");
const next = document.querySelector(".next");
const dotsContainer = document.querySelector(".dots");

let index = 0;
let autoSlideInterval;

// Create dots dynamically
images.forEach((_, i) => {
    const dot = document.createElement("span");
    dot.addEventListener("click", () => moveToSlide(i));
    dotsContainer.appendChild(dot);
});
const dots = dotsContainer.querySelectorAll("span");

function updateCarousel() {
    slides.style.transform = `translateX(-${index * 100}%)`;
    dots.forEach((dot, i) => dot.classList.toggle("active", i === index));
}

function moveToSlide(i) {
    index = (i + images.length) % images.length;
    updateCarousel();
    resetAutoSlide();
}

function startAutoSlide() {
    autoSlideInterval = setInterval(() => moveToSlide(index + 1), 4000);
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    startAutoSlide();
}

prev.addEventListener("click", () => moveToSlide(index - 1));
next.addEventListener("click", () => moveToSlide(index + 1));

// Initialize carousel
updateCarousel();
startAutoSlide();

// Pause auto slide on hover
const carousel = document.querySelector(".carousel");
carousel.addEventListener("mouseenter", () => {
    clearInterval(autoSlideInterval);
});
carousel.addEventListener("mouseleave", () => {
    startAutoSlide();
});

// ===== Scroll to Top Button =====
const scrollTopBtn = document.getElementById("scrollTop");

window.addEventListener("scroll", () => {
    if (window.pageYOffset > 300) {
        scrollTopBtn.classList.add("visible");
    } else {
        scrollTopBtn.classList.remove("visible");
    }
});

scrollTopBtn.addEventListener("click", () => {
    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
});

// ===== Toast Notification =====
const toast = document.getElementById("toast");
const toastMessage = document.getElementById("toastMessage");

function showToast(message) {
    toastMessage.textContent = message;
    toast.classList.add("show");
    
    setTimeout(() => {
        toast.classList.remove("show");
    }, 3000);
}

// ===== Animate elements on scroll =====
function animateOnScroll() {
    const elements = document.querySelectorAll('.sport_card, .carousel_section h2, .sports_section h2');
    
    elements.forEach(element => {
        const position = element.getBoundingClientRect();
        
        // If element is in viewport
        if(position.top < window.innerHeight - 50) {
            element.style.opacity = 1;
            element.style.transform = 'translateY(0)';
        }
    });
}

// Initialize elements for animation
document.querySelectorAll('.sport_card, .carousel_section h2, .sports_section h2').forEach(element => {
    element.style.opacity = 0;
    element.style.transform = 'translateY(20px)';
    element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
});

window.addEventListener('scroll', animateOnScroll);
// Initial check
animateOnScroll();

// ===== Button Ripple Effect =====
document.querySelectorAll('button').forEach(button => {
    button.addEventListener('click', function(e) {
        const x = e.clientX - e.target.offsetLeft;
        const y = e.clientY - e.target.offsetTop;
        
        const ripple = document.createElement('span');
        ripple.classList.add('ripple-effect');
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});

// ===== CTA Button Smooth Scroll =====
document.querySelector('.cta-button').addEventListener('click', () => {
    document.querySelector('.sports_section').scrollIntoView({ 
        behavior: 'smooth' 
    });
});

// Initialize animations when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code needed when the page loads
});