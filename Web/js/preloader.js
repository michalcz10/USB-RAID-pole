// Add to the end of your existing script section or in a separate file
document.addEventListener('DOMContentLoaded', function() {
    // Get the saved theme or use the system preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = savedTheme || (prefersDark ? 'dark' : 'light');
    
    // Apply theme to document immediately
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.body.classList.add('theme-' + theme);
    
    // Show content after a short delay
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        setTimeout(() => {
            contentWrapper.classList.add('visible');
            
            // Hide preloader
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.opacity = '0';
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 500);
            }
        }, 300);
    }
    
    // Add event listener to all internal links
    document.querySelectorAll('a').forEach(link => {
        // Only handle internal links that aren't file downloads
        if (link.hostname === window.location.hostname && 
            !link.href.endsWith('.zip') && 
            !link.href.endsWith('.pdf') && 
            !link.href.endsWith('.jpg') && 
            !link.href.endsWith('.png') &&
            !link.getAttribute('target') === '_blank') {
            
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // Skip for links with javascript: protocols or # anchors
                if (href.startsWith('javascript:') || href === '#') {
                    return;
                }
                
                e.preventDefault();
                
                // Create and show preloader
                const preloader = document.createElement('div');
                preloader.className = 'preloader';
                preloader.innerHTML = '<div class="spinner"></div>';
                document.body.appendChild(preloader);
                
                // Apply current theme to preloader
                const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                if (currentTheme) {
                    preloader.classList.add('theme-' + currentTheme);
                }
                
                // Navigate after a short delay to show the preloader
                setTimeout(() => {
                    window.location.href = href;
                }, 300);
            });
        }
    });
});

// Ensure page content doesn't show until loaded
window.addEventListener('beforeunload', function() {
    document.body.style.opacity = '0';
});