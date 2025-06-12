window.tskp ||= {}

window.tskp.custom_fields = {
  renderBudgetRequestCustomFields: function (custom_fields) {
    if (!Object.keys(custom_fields).length) {
      return '';
    }

    var html = '';
    for (var field in custom_fields) {
      html += `
        <tr>
        <td width="20%">${field}:</td>
        <td>
          ${custom_fields[field]}
        </td>
        </tr>
      `;
    }

    return html;
  }
}
