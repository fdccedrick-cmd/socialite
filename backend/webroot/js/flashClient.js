try { console.log('flashClient.js loaded'); } catch(e){}

(function(){
  // showFlash(message, type='success', durationMs=4000)
  window.showFlash = function(message, type='success', duration=4000){
    try { console.log('showFlash called', {message, type, duration}); } catch(e){}

    const container = document.getElementById('flashContainer');
    if (!container) {
      console.warn('flashContainer not found');
      return;
    }

    const el = document.createElement('div');
    el.className = 'flash-message';

    // Use Tailwind-like classes for quick visual styling based on type
    const colorClass = (type === 'success') ? 'bg-green-50 text-green-800' : (type === 'error' ? 'bg-red-50 text-red-800' : 'bg-blue-50 text-blue-800');

    el.innerHTML = `<div class="px-4 py-2 rounded ${colorClass}">${message}</div>`;

    container.appendChild(el);

    // trigger enter animation
    requestAnimationFrame(()=>{
      try { el.classList.add('flash-in'); } catch(e){}
    });

    const VISIBLE_FOR = duration || 4000;
    const removeFn = ()=>{
      try { el.classList.remove('flash-in'); el.classList.add('flash-out'); } catch(e){}
      setTimeout(()=>{ try{ el.remove(); } catch(e){} }, 400);
    };

    const tid = setTimeout(removeFn, VISIBLE_FOR);

    el.addEventListener('click', ()=>{
      clearTimeout(tid);
      removeFn();
    });

    return {
      close() { clearTimeout(tid); removeFn(); }
    };
  };

})();
