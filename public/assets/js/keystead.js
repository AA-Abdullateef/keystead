(function () {
  var bar = document.querySelector('.topbar');
  var burger = document.querySelector('.burger');
  if (burger && bar) burger.addEventListener('click', function () { bar.classList.toggle('open'); });

  document.querySelectorAll('.dd > button').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.stopPropagation();
      var dd = b.parentElement, was = dd.classList.contains('open');
      document.querySelectorAll('.dd.open').forEach(function (d) { d.classList.remove('open'); });
      if (!was) dd.classList.add('open');
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('.dd.open').forEach(function (d) { d.classList.remove('open'); });
  });

  // Confirm destructive / money-moving submissions.
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); });
  });

  // Prevent double submits.
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      var btn = f.querySelector('button[type=submit]:not([data-keep])');
      if (btn) setTimeout(function () { btn.disabled = true; }, 0);
    });
  });

  // Country → state dependent select (admin user profile).
  var country = document.getElementById('country_id'), state = document.getElementById('state_id');
  if (country && state && country.dataset.url) {
    country.addEventListener('change', function () {
      state.innerHTML = '<option value="">—</option>';
      if (!country.value) return;
      fetch(country.dataset.url.replace('__ID__', country.value), { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (rows) {
          rows.forEach(function (s) { var o = document.createElement('option'); o.value = s.id; o.textContent = s.name; state.appendChild(o); });
        });
    });
  }
})();
