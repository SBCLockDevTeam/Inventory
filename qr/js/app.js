// Main Application JavaScript
console.log('App loaded');

const BRAND_STORAGE_KEY = 'sbcqr_selected_brand';
const DEFAULT_BRAND = 'sbc';

// Initialize app on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeBrandSelector();
});

/**
 * Initialize brand selector with saved brand or default
 * Loads previously selected brand from localStorage
 * Sets dropdown to saved value
 * Attaches change listener
 */
function initializeBrandSelector() {
    const brandSelector = document.getElementById('brand-selector');
    if (!brandSelector) {
        console.warn('Brand selector not found');
        return;
    }
    
    // Load saved brand from localStorage or use default
    const savedBrand = localStorage.getItem(BRAND_STORAGE_KEY) || DEFAULT_BRAND;
    
    // Set dropdown to saved value
    brandSelector.value = savedBrand;
    
    // Apply brand display
    applyBrand(savedBrand);
    
    // Attach change listener
    brandSelector.addEventListener('change', function() {
        changeBrand(this.value);
    });
}

/**
 * Change brand and persist selection
 * @param {string} brand - Brand identifier to switch to
 */
function changeBrand(brand) {
    console.log('Brand changed to:', brand);
    
    // Save to localStorage for persistence
    localStorage.setItem(BRAND_STORAGE_KEY, brand);
    
    // Apply brand changes
    applyBrand(brand);
}

/**
 * Apply brand-specific display changes
 * Updates header content based on selected brand
 * @param {string} brand - Brand identifier
 */
function applyBrand(brand) {
    const logo = document.querySelector('.logo h1');
    const tagline = document.querySelector('.tagline');
    
    if (!logo || !tagline) {
        console.warn('Logo or tagline elements not found');
        return;
    }
    
    const brandData = {
        sbc: {
            name: 'SBC Inventory',
            tagline: 'Security Building Controls'
        },
        other: {
            name: 'Other Brand Inventory',
            tagline: 'Other Brand Name'
        }
    };
    
    const data = brandData[brand] || brandData[DEFAULT_BRAND];
    
    logo.textContent = data.name;
    tagline.textContent = data.tagline;
    
    console.log('Brand applied:', data);
}