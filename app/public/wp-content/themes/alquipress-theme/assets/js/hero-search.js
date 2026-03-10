/**
 * JavaScript frontend para bloque Hero con Buscador
 * Autocompletado de ubicaciones y validación de fechas
 */

(function() {
    'use strict';
    
    const heroSearchBlocks = document.querySelectorAll('.alq-hero-search');
    
    heroSearchBlocks.forEach(function(block) {
        initHeroSearch(block);
    });
    
    function initHeroSearch(block) {
        const locationInput = block.querySelector('input[name="poblacion"]');
        const checkinInput = block.querySelector('input[name="checkin"]');
        const checkoutInput = block.querySelector('input[name="checkout"]');
        const autocompleteContainer = block.querySelector('.alq-hero-search-autocomplete');
        const resultsPage = block.dataset.resultsPage || '';
        
        let autocompleteTimeout;
        let selectedLocation = null;
        
        // Autocompletado de ubicaciones
        if (locationInput && autocompleteContainer) {
            locationInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                
                clearTimeout(autocompleteTimeout);
                
                if (searchTerm.length < 2) {
                    autocompleteContainer.classList.remove('active');
                    return;
                }
                
                autocompleteTimeout = setTimeout(function() {
                    fetchLocations(searchTerm, autocompleteContainer, locationInput);
                }, 300);
            });
            
            // Cerrar autocompletado al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!block.contains(e.target)) {
                    autocompleteContainer.classList.remove('active');
                }
            });
            
            // Seleccionar ubicación del autocompletado
            autocompleteContainer.addEventListener('click', function(e) {
                const item = e.target.closest('.alq-hero-search-autocomplete-item');
                if (item) {
                    const locationName = item.dataset.name;
                    const locationSlug = item.dataset.slug;
                    locationInput.value = locationName;
                    selectedLocation = locationSlug;
                    autocompleteContainer.classList.remove('active');
                }
            });
        }
        
        // Validación de fechas
        if (checkinInput && checkoutInput) {
            checkinInput.addEventListener('change', function() {
                const checkinDate = new Date(this.value);
                const minCheckout = new Date(checkinDate);
                minCheckout.setDate(minCheckout.getDate() + 1);
                checkoutInput.min = minCheckout.toISOString().split('T')[0];
                
                // Si checkout es anterior a checkin, actualizar
                if (checkoutInput.value && new Date(checkoutInput.value) <= checkinDate) {
                    checkoutInput.value = '';
                }
            });
            
            checkoutInput.addEventListener('change', function() {
                if (checkinInput.value && this.value) {
                    validateDates(checkinInput.value, this.value);
                }
            });
        }
        
        // Actualizar campo oculto con slug si existe
        const form = block.querySelector('.alq-hero-search-form');
        if (form && selectedLocation) {
            form.addEventListener('submit', function(e) {
                // Si hay ubicación seleccionada, usar slug en lugar del nombre
                if (selectedLocation) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'poblacion';
                    hiddenInput.value = selectedLocation;
                    form.appendChild(hiddenInput);
                    locationInput.name = 'poblacion_display'; // Cambiar nombre para no enviar
                }
            });
        }
    }
    
    function fetchLocations(searchTerm, container, input) {
        const apiUrl = '/wp-json/alquipress/v1/locations?search=' + encodeURIComponent(searchTerm);
        
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    renderAutocomplete(data, container);
                    container.classList.add('active');
                } else {
                    container.classList.remove('active');
                }
            })
            .catch(error => {
                console.error('Error fetching locations:', error);
                container.classList.remove('active');
            });
    }
    
    function renderAutocomplete(locations, container) {
        container.innerHTML = '';
        
        locations.forEach(function(location) {
            const item = document.createElement('div');
            item.className = 'alq-hero-search-autocomplete-item';
            item.dataset.name = location.name;
            item.dataset.slug = location.slug;
            
            item.innerHTML = `
                <div class="alq-hero-search-autocomplete-item-name">${location.name}</div>
                <div class="alq-hero-search-autocomplete-item-count">${location.count} propiedades</div>
            `;
            
            container.appendChild(item);
        });
    }
    
    function validateDates(checkin, checkout) {
        // Validación básica - se puede extender con llamada a API
        const checkinDate = new Date(checkin);
        const checkoutDate = new Date(checkout);
        
        if (checkoutDate <= checkinDate) {
            alert('La fecha de salida debe ser posterior a la de entrada');
            return false;
        }
        
        return true;
    }
})();
