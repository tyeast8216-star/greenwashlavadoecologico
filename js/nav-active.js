// Manage navbar active state:
// - On single-page home: use IntersectionObserver to set 'Inicio' active when hero area visible
// - On other pages: set active link based on current pathname

(function(){
  function setActiveByHref(href){
    var link = document.querySelector('.navbar-nav .nav-link[href="'+href+'"]');
    if(!link) return null;
    var li = link.closest('.nav-item');
    document.querySelectorAll('.navbar-nav .nav-item').forEach(function(el){ el.classList.remove('active'); el.querySelectorAll('.nav-link').forEach(function(a){ a.classList.remove('active'); }); });
    li.classList.add('active');
    link.classList.add('active');
    return li;
  }

  // Try matching by full filename (e.g., /about.html or about.html)
  var path = window.location.pathname.split('/').pop() || 'index.html';
  // If on index.html (home), keep 'Inicio' active for the whole page
  if(path === '' || path === 'index.html'){
    setActiveByHref('index.html');
    return;
  }

  // For other pages, try to match exact file
  var matched = setActiveByHref(path);
  if(!matched){
    // Try matching without extension
    var name = path.replace(/\.html$/,'');
    var link = document.querySelector('.navbar-nav .nav-link[href*="'+name+'"]');
    if(link){
      var li = link.closest('.nav-item');
      li.classList.add('active');
      link.classList.add('active');
    }
  }
})();
