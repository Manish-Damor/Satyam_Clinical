(function () {
  "use strict";

  function isInsideResponsiveWrapper(table) {
    return Boolean(table.closest(".table-responsive, .auto-table-responsive"));
  }

  function shouldSkipTable(table) {
    if (!table) {
      return true;
    }

    if (table.classList.contains("no-auto-responsive")) {
      return true;
    }

    if (isInsideResponsiveWrapper(table)) {
      return true;
    }

    if (table.closest(".invoice-box, .print-container")) {
      return true;
    }

    return false;
  }

  function getColumnCount(table) {
    var row = table.querySelector("thead tr") || table.querySelector("tr");
    if (!row) {
      return 0;
    }

    return row.querySelectorAll("th, td").length;
  }

  function wrapTable(table) {
    var wrapper = document.createElement("div");
    wrapper.className = "table-responsive auto-table-responsive";

    table.parentNode.insertBefore(wrapper, table);
    wrapper.appendChild(table);

    var columns = getColumnCount(table);
    if (columns >= 5) {
      table.classList.add("mobile-wide-table");
    } else {
      table.classList.add("mobile-compact-table");
    }
  }

  function initAutoResponsiveTables() {
    var tables = document.querySelectorAll(
      ".page-wrapper table.table, .page-wrapper table.dataTable, .page-wrapper .card table",
    );

    tables.forEach(function (table) {
      if (shouldSkipTable(table)) {
        return;
      }

      wrapTable(table);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAutoResponsiveTables);
  } else {
    initAutoResponsiveTables();
  }
})();
