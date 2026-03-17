<script>
  (function () {
    function getTheme() {
      return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    }

    function applyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('ticketly-theme', theme);
      document.querySelectorAll('[data-theme-label]').forEach(function (el) {
        el.textContent = theme === 'light' ? 'Light' : 'Dark';
      });
      window.dispatchEvent(new CustomEvent('ticketly:theme-changed', { detail: { theme: theme } }));
    }

    document.addEventListener('DOMContentLoaded', function () {
      applyTheme(getTheme());
      document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          applyTheme(getTheme() === 'light' ? 'dark' : 'light');
        });
      });
    });
  })();
</script>
