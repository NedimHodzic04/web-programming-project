let Utils = {
  /**
   * Initializes or re-initializes a DataTable.
   * @param {string} table_id The ID of the table element (without the #).
   * @param {Array} columns The column definitions for the DataTable.
   * @param {Array} data The data to populate the table with.
   * @param {number} [pageLength=15] The default number of rows per page.
   */
  datatable: function (table_id, columns, data, pageLength = 15) {
    if ($.fn.dataTable.isDataTable("#" + table_id)) {
      $("#" + table_id)
        .DataTable()
        .destroy();
    }
    $("#" + table_id).DataTable({
      data: data,
      columns: columns,
      pageLength: pageLength,
      lengthMenu: [2, 5, 10, 15, 25, 50, 100, "All"],
    });
  },

  /**
   * Parses a JWT token to extract its payload.
   * @param {string} token The JWT token string.
   * @returns {object|null} The decoded payload object or null if token is invalid.
   */
  parseJwt: function(token) {
    if (!token) return null;
    try {
      const payload = token.split('.')[1];
      const decoded = atob(payload);
      return JSON.parse(decoded);
    } catch (e) {
      console.error("Invalid JWT token", e);
      return null;
    }
  },

  /**
   * Escapes special characters in a string for use in HTML.
   * This prevents Cross-Site Scripting (XSS) attacks.
   * @param {string} str The string to escape.
   * @returns {string} The escaped string.
   */
  escapeHtml: function(str) {
    if (typeof str !== 'string') {
      return '';
    }
    return str.replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
  }
}