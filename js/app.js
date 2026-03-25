// Main Application JavaScript
console.log('App loaded');

document.addEventListener('DOMContentLoaded', function() {
    const brandSelector = document.getElementById('brand-selector');
    if (brandSelector) {
        brandSelector.addEventListener('change', function() {
            changeBrand(this.value);
        });
    }
});

function changeBrand(brand) {
    console.log('Brand changed to:', brand);
    
    const logo = document.querySelector('.logo h1');
    const tagline = document.querySelector('.tagline');
    
    console.log('Logo element:', logo);
    console.log('Tagline element:', tagline);
    
    if (logo && tagline) {
        if (brand === 'sbc') {
            logo.innerHTML = 'SBC Inventory';
            tagline.innerHTML = 'Security Building Controls';
        } else if (brand === 'other') {
            logo.innerHTML = 'Other Brand Inventory';
            tagline.innerHTML = 'Other Brand Name';
        }
        console.log('Updated:', logo.innerHTML, tagline.innerHTML);
    } else {
        console.log('Elements not found');
    }
}
