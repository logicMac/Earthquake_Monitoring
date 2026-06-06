/**
 * Theme Toggle System
 * Handles dark/light mode switching with localStorage persistence
 */

// Initialize theme on page load
(function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Add theme class to body immediately to prevent flash
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark-mode');
    }
})();

// Toggle theme function
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    // Update theme
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Toggle dark mode class
    if (newTheme === 'dark') {
        html.classList.add('dark-mode');
    } else {
        html.classList.remove('dark-mode');
    }
    
    // Update toggle button icon
    updateToggleIcon();
}

// Update toggle button icon based on current theme
function updateToggleIcon() {
    const theme = document.documentElement.getAttribute('data-theme');
    const sunIcon = document.getElementById('sunIcon');
    const moonIcon = document.getElementById('moonIcon');
    
    if (sunIcon && moonIcon) {
        if (theme === 'dark') {
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
        } else {
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
        }
    }
}

// Initialize toggle icon on page load
document.addEventListener('DOMContentLoaded', function() {
    updateToggleIcon();
});
