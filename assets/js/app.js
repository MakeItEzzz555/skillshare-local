document.addEventListener('DOMContentLoaded', () => {
  document.body.classList.add('js-ready');

  // Soft fade-in for hero + cards
  const fadeTargets = document.querySelectorAll('.card-neo, .page-title, .fade-up');
  fadeTargets.forEach(el => {
    el.classList.add('fade-target');
    requestAnimationFrame(() => el.classList.add('fade-visible'));
  });

  // Confirm dialog for links that need an extra click
  document.querySelectorAll('.js-confirm').forEach(link => {
    link.addEventListener('click', (e) => {
      if (!confirm('Are you sure?')) {
        e.preventDefault();
      }
    });
  });

  // Handle location filter (homepage) with two dropdowns
  const cityBtn = document.querySelector('.js-city-btn');
  const onlineBtn = document.querySelector('.js-online-btn');
  const locationValue = document.getElementById('locationValue');

  if (cityBtn && onlineBtn && locationValue) {
    const cityOptions = document.querySelectorAll('.js-city-option');
    const onlineOptions = document.querySelectorAll('.js-online-option');

    const resetOnline = () => {
      onlineBtn.textContent = onlineBtn.dataset.default || 'Online platform';
    };
    const resetCity = () => {
      cityBtn.textContent = cityBtn.dataset.default || 'City (any)';
    };

    cityOptions.forEach(opt => {
      opt.addEventListener('click', (e) => {
        e.preventDefault();
        const val = opt.dataset.value || '';
        cityBtn.textContent = val ? `City: ${val}` : (cityBtn.dataset.default || 'City (any)');
        locationValue.value = val;
        resetOnline();
      });
    });

    onlineOptions.forEach(opt => {
      opt.addEventListener('click', (e) => {
        e.preventDefault();
        const val = opt.dataset.value || '';
        onlineBtn.textContent = val ? `Online: ${val}` : (onlineBtn.dataset.default || 'Online platform');
        locationValue.value = val;
        resetCity();
      });
    });
  }

});
