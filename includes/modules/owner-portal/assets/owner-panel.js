/**
 * ALQUIPRESS — Panel propietario: tabs
 */
(function () {
    function init() {
        var tabBtns = document.querySelectorAll('.alquipress-owner-panel .tab-btn');
        var tabContents = document.querySelectorAll('.alquipress-owner-panel .tab-content');
        if (!tabBtns.length || !tabContents.length) return;

        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetTab = this.getAttribute('data-tab');
                if (!targetTab) return;

                tabBtns.forEach(function (b) { b.classList.remove('active'); });
                tabContents.forEach(function (c) { c.classList.remove('active'); });

                this.classList.add('active');
                var content = document.getElementById('tab-' + targetTab);
                if (content) content.classList.add('active');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
