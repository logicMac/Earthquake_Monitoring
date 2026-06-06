# Dark/Light Mode Theme Toggle Guide

## Overview
Added a complete dark/light mode theme toggle system to the earthquake monitoring application. The theme preference is saved in localStorage and persists across sessions and pages.

## Files Created

### 1. `assets/theme-toggle.js`
JavaScript file that handles:
- Theme initialization on page load
- Theme switching functionality
- localStorage persistence
- Icon updates (sun/moon)
- Prevents flash of unstyled content

### 2. `assets/theme.css`
CSS file with:
- CSS custom properties (variables) for both themes
- Light mode colors (default)
- Dark mode colors
- Smooth transitions between themes
- Theme-aware component styles

## Theme Colors

### Light Mode (Default)
- Background: `#f9fafb` (gray-50)
- Cards: `#ffffff` (white)
- Text: `#111827` (gray-900)
- Borders: `#e5e7eb` (gray-200)
- Buttons: Black with white text
- Chart: Black line with gray gradient

### Dark Mode
- Background: `#0f172a` (slate-900)
- Cards: `#1e293b` (slate-800)
- Text: `#f1f5f9` (slate-100)
- Borders: `#334155` (slate-700)
- Buttons: White with dark text
- Chart: Blue line with blue gradient

## Implementation Details

### Theme Toggle Button
Located in navigation bar on all pages:
- Desktop: Visible on larger screens
- Mobile: Included in hamburger menu
- Icons: Sun (light mode) / Moon (dark mode)
- Smooth rotation animation on hover

### CSS Variables
All theme-dependent styles use CSS custom properties:
```css
var(--bg-primary)
var(--text-primary)
var(--card-bg)
var(--button-primary-bg)
```

### Theme Classes
Applied to elements for theme support:
- `.theme-card` - Cards and containers
- `.theme-text-primary` - Primary text
- `.theme-text-secondary` - Secondary text
- `.theme-text-tertiary` - Tertiary text
- `.theme-btn-primary` - Primary buttons
- `.theme-btn-secondary` - Secondary buttons
- `.theme-input` - Form inputs
- `.theme-table-header` - Table headers
- `.theme-table-row` - Table rows

### Chart Theme Support
The Chart.js instance in index.php is theme-aware:
- Automatically updates colors when theme changes
- Uses different gradients for light/dark modes
- Grid lines and text adapt to theme

## Pages Updated

### 1. index.php (Dashboard)
- Theme toggle in navigation
- All cards use theme classes
- Chart updates with theme
- Table rows theme-aware
- Mobile menu includes theme toggle

### 2. reports.php
- Theme toggle in navigation
- Statistics cards themed
- Tables themed
- Filters section themed
- Mobile menu includes theme toggle

### 3. manage_recipients.php
- Theme toggle in navigation
- Stats cards themed
- Forms themed
- Tables themed
- Mobile menu includes theme toggle

### 4. login.php
- Theme toggle in header
- Login form themed
- Inputs themed
- Buttons themed
- Background adapts to theme

## How It Works

### 1. Page Load
```javascript
// theme-toggle.js initializes immediately
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);
```

### 2. Theme Toggle
```javascript
function toggleTheme() {
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}
```

### 3. CSS Application
```css
[data-theme="dark"] {
    --bg-primary: #0f172a;
    /* ... other dark mode variables */
}
```

## User Experience

### Features
- Instant theme switching (no page reload)
- Smooth transitions (0.3s ease)
- Persistent across sessions
- Consistent across all pages
- Mobile-friendly toggle
- Accessible with keyboard navigation

### Animations
- Button hover: Scale and rotate
- Theme change: Smooth color transitions
- Icon swap: Fade in/out effect

## Status Colors Preserved
The following colors remain consistent in both themes:
- Green: Success, active status
- Red: Alerts, danger, high intensity
- Yellow/Orange: Warnings, moderate alerts
- These colors are critical for earthquake monitoring

## Browser Support
- Modern browsers (Chrome, Firefox, Safari, Edge)
- localStorage API required
- CSS custom properties required
- Graceful degradation for older browsers

## Testing Checklist
- [x] Theme toggle works on all pages
- [x] Theme persists after page reload
- [x] Theme persists across different pages
- [x] Mobile menu toggle works
- [x] Chart updates with theme
- [x] All text is readable in both themes
- [x] Status colors remain visible
- [x] Buttons are accessible in both themes
- [x] Forms work in both themes
- [x] Tables are readable in both themes

## Customization

### Adding New Theme Colors
Edit `assets/theme.css`:
```css
:root {
    --custom-color: #value;
}

[data-theme="dark"] {
    --custom-color: #dark-value;
}
```

### Using Theme Colors
In HTML/PHP:
```html
<div class="theme-card">
    <p class="theme-text-primary">Text</p>
</div>
```

In CSS:
```css
.custom-element {
    background-color: var(--bg-primary);
    color: var(--text-primary);
}
```

## Performance
- Minimal JavaScript overhead
- CSS transitions are GPU-accelerated
- localStorage is fast and synchronous
- No external dependencies
- Total added file size: ~8KB

## Accessibility
- High contrast in both themes
- WCAG AA compliant color ratios
- Keyboard accessible toggle button
- Screen reader friendly
- Focus states visible in both themes

## Future Enhancements (Optional)
- Auto-detect system theme preference
- Schedule-based theme switching
- Custom theme colors
- Theme preview before applying
- Transition animations between themes
