/**
 * JavaScript frontend para filtros AJAX
 */

(function() {
    'use strict';
    
    const filterBlocks = document.querySelectorAll('.alq-property-filters[data-ajax="true"]');
    
    filterBlocks.forEach(function(block) {
        initFilters(block);
    });
    
    function initFilters(block) {
        const form = block.querySelector('.alq-property-filters-form');
        const rangeInputs = block.querySelectorAll('.alq-property-filters-range-input');
        const gridBlock = document.querySelector('.alq-property-grid');
        
        if (!form || !gridBlock) {
            return;
        }
        
        // Actualizar valores del range slider
        rangeInputs.forEach(function(input) {
            const label = input.dataset.label;
            const display = block.querySelector('.alq-property-filters-range-' + label);
            
            input.addEventListener('input', function() {
                if (display) {
                    display.textContent = this.value + '€';
                }
            });
        });
        
        // Interceptar submit del formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (block.dataset.ajax === 'true') {
                applyFiltersAjax(form, gridBlock);
            } else {
                form.submit();
            }
        });
    }
    
    function applyFiltersAjax(form, gridBlock) {
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Convertir FormData a URLSearchParams
        for (const [key, value] of formData.entries()) {
            if (key.includes('[]')) {
                const baseKey = key.replace('[]', '');
                if (!params.has(baseKey)) {
                    params.append(baseKey, value);
                } else {
                    params.append(baseKey, value);
                }
            } else {
                params.append(key, value);
            }
        }
        
        // Mostrar loading
        gridBlock.classList.add('loading');
        
        // Llamada AJAX
        fetch('/wp-json/alquipress/v1/properties-filtered?' + params.toString())
            .then(response => response.json())
            .then(data => {
                updateGrid(data.properties, gridBlock);
                updateURL(params);
                gridBlock.classList.remove('loading');
            })
            .catch(error => {
                console.error('Error:', error);
                gridBlock.classList.remove('loading');
            });
    }
    
    function updateGrid(properties, gridBlock) {
        const itemsContainer = gridBlock.querySelector('.alq-property-grid-items');
        if (!itemsContainer) {
            return;
        }
        
        itemsContainer.innerHTML = '';
        
        if (properties.length === 0) {
            itemsContainer.innerHTML = '<div class="alq-property-grid-empty"><p>No se encontraron propiedades.</p></div>';
            return;
        }
        
        // Renderizar propiedades (necesitarías un template JS o hacerlo via PHP)
        properties.forEach(function(property) {
            // Por ahora, recargar la página con los nuevos parámetros
            // En el futuro se puede hacer renderizado JS completo
        });
        
        // Por ahora, recargar la página
        window.location.href = window.location.pathname + '?' + new URLSearchParams(window.location.search).toString();
    }
    
    function updateURL(params) {
        const newUrl = window.location.pathname + '?' + params.toString();
        window.history.pushState({}, '', newUrl);
    }
})();
