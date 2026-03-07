(function () {
  "use strict";

  var desktopQuery = window.matchMedia("(min-width: 1024px)");
  var storageKey = "sc_sidebar_collapsed";

  function getSidebar() {
    return (
      document.querySelector(".left-sidebar.sc-sidebar") ||
      document.querySelector(".left-sidebar")
    );
  }

  function getCollapseToggle() {
    return document.querySelector(".sidebar-collapse-toggle");
  }

  function getNavTogglerIcon() {
    return document.querySelector(".nav-toggler i");
  }

  function syncMobileTogglerIcon() {
    var icon = getNavTogglerIcon();
    if (!icon) {
      return;
    }

    icon.classList.remove("mdi-menu", "mdi-close");
    icon.classList.add("mdi");
    icon.classList.add(
      document.body.classList.contains("show-sidebar")
        ? "mdi-close"
        : "mdi-menu",
    );
  }

  function setCollapseIcon(collapsed) {
    var toggle = getCollapseToggle();
    if (!toggle) {
      return;
    }

    var icon = toggle.querySelector("i");
    if (!icon) {
      return;
    }

    icon.classList.remove("fa-chevron-left", "fa-chevron-right");
    icon.classList.add(collapsed ? "fa-chevron-right" : "fa-chevron-left");
  }

  function saveCollapsedPreference(collapsed) {
    try {
      localStorage.setItem(storageKey, collapsed ? "1" : "0");
    } catch (error) {
      // Ignore storage failures in private browsing or restricted contexts.
    }
  }

  function readCollapsedPreference() {
    try {
      return localStorage.getItem(storageKey) === "1";
    } catch (error) {
      return false;
    }
  }

  function setDesktopCollapsed(collapsed, persist) {
    var sidebar = getSidebar();
    if (!sidebar || !desktopQuery.matches) {
      return;
    }

    if (document.body.classList.contains("mini-sidebar")) {
      sidebar.classList.remove("collapsed");
      document.body.classList.remove("body-with-collapsed-sidebar");
      setCollapseIcon(false);
      return;
    }

    sidebar.classList.toggle("collapsed", collapsed);
    document.body.classList.toggle("body-with-collapsed-sidebar", collapsed);
    setCollapseIcon(collapsed);

    if (persist) {
      saveCollapsedPreference(collapsed);
    }
  }

  function closeMobileSidebar() {
    document.body.classList.remove("show-sidebar");
    syncMobileTogglerIcon();
  }

  function syncLayoutForViewport() {
    var sidebar = getSidebar();
    if (!sidebar) {
      return;
    }

    if (desktopQuery.matches) {
      closeMobileSidebar();

      if (document.body.classList.contains("mini-sidebar")) {
        sidebar.classList.remove("collapsed");
        document.body.classList.remove("body-with-collapsed-sidebar");
        setCollapseIcon(false);
        return;
      }

      setDesktopCollapsed(readCollapsedPreference(), false);
      syncMobileTogglerIcon();
      return;
    }

    sidebar.classList.remove("collapsed");
    document.body.classList.remove("body-with-collapsed-sidebar");
    setCollapseIcon(false);
    syncMobileTogglerIcon();
  }

  function handleDocumentClick(event) {
    if (event.target.closest(".nav-toggler")) {
      // custom.min.js toggles the body class; sync icon right after it runs.
      window.setTimeout(syncMobileTogglerIcon, 0);
      return;
    }

    var collapseToggle = event.target.closest(".sidebar-collapse-toggle");
    if (collapseToggle) {
      event.preventDefault();
      if (!desktopQuery.matches) {
        return;
      }

      var sidebar = getSidebar();
      if (!sidebar) {
        return;
      }

      var nextCollapsedState = !sidebar.classList.contains("collapsed");
      setDesktopCollapsed(nextCollapsedState, true);
      return;
    }

    if (event.target.closest(".sidebar-mobile-overlay")) {
      closeMobileSidebar();
      return;
    }

    if (
      !desktopQuery.matches &&
      document.body.classList.contains("show-sidebar")
    ) {
      var inSidebar = event.target.closest(".left-sidebar.sc-sidebar");
      if (!inSidebar) {
        closeMobileSidebar();
        return;
      }
    }

    if (!desktopQuery.matches) {
      var sidebarLink = event.target.closest(
        ".left-sidebar.sc-sidebar .sidebar-nav a",
      );
      if (sidebarLink && !sidebarLink.classList.contains("has-arrow")) {
        closeMobileSidebar();
      }
    }
  }

  function handleKeydown(event) {
    if (event.key === "Escape") {
      closeMobileSidebar();
    }
  }

  function initSidebarResponsive() {
    if (!getSidebar()) {
      return;
    }

    syncLayoutForViewport();
    syncMobileTogglerIcon();
    document.addEventListener("click", handleDocumentClick);
    document.addEventListener("keydown", handleKeydown);
    window.addEventListener("resize", syncLayoutForViewport);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSidebarResponsive);
  } else {
    initSidebarResponsive();
  }
})();
