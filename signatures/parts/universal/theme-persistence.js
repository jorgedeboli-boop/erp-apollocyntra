/**
 * Persistencia de tema sin TemplateCustomizer (navbar Claro/Oscuro/Sistema).
 * Corrige getStoredTheme del vendor helpers.js cuando no hay customizer cargado.
 * Mantiene el primary #007BFF también en modo oscuro (evita el #666cff del tema).
 */
(function () {
  if (!window.Helpers || window.templateCustomizer) {
    return;
  }

  const PRIMARY_COLOR = '#007BFF';

  window.Helpers.getStoredTheme = function () {
    const templateName =
      document.documentElement.getAttribute('data-template') || 'vertical-menu-template-no-customizer';
    const stored = localStorage.getItem(`templateCustomizer-${templateName}--Theme`);

    if (stored) {
      return stored;
    }

    return document.documentElement.getAttribute('data-bs-theme') || 'light';
  };

  function applyPrimaryColor() {
    if (typeof window.config === 'undefined') {
      return;
    }

    window.Helpers.setColor(PRIMARY_COLOR, true);

    if (window.config.colors) {
      window.config.colors.primary = window.Helpers.getCssVar('primary', true);
    }
  }

  const originalSetTheme = window.Helpers.setTheme.bind(window.Helpers);

  window.Helpers.setTheme = function (theme) {
    originalSetTheme(theme);
    applyPrimaryColor();
  };

  function initPrimaryColorPersistence() {
    applyPrimaryColor();

    document.querySelectorAll('[data-bs-theme-value]').forEach(function (toggle) {
      toggle.addEventListener('click', function () {
        requestAnimationFrame(applyPrimaryColor);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPrimaryColorPersistence);
  } else {
    initPrimaryColorPersistence();
  }
})();
