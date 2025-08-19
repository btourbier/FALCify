(function(){
    function ready(fn){if(document.readyState!=='loading'){fn()}else{document.addEventListener('DOMContentLoaded',fn)}}
    ready(function(){
        document.querySelectorAll('.falcify-toggle').forEach(function(box){
            var btn = box.querySelector('.falcify-btn');
            var area = box.querySelector('.falcify-content');
            if(!btn || !area){return;}

            var original = area.getAttribute('data-original') || '';
            var falc = area.getAttribute('data-falc') || '';
            var mode = 'original';

            function setLabel(){
                if(mode === 'original'){
                    btn.textContent = (window.FALCIFY_FREE && FALCIFY_FREE.i18n && FALCIFY_FREE.i18n.read_easy) || 'Lire en version facile';
                    btn.setAttribute('aria-pressed','false');
                } else {
                    btn.textContent = (window.FALCIFY_FREE && FALCIFY_FREE.i18n && FALCIFY_FREE.i18n.read_original) || 'Lire la version originale';
                    btn.setAttribute('aria-pressed','true');
                }
            }
            setLabel();

            btn.addEventListener('click', function(){
                mode = (mode === 'original') ? 'falc' : 'original';
                area.innerHTML = (mode === 'original') ? original : falc;
                setLabel();
            });
        });
    });
})();