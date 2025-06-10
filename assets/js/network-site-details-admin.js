// Table sorting for Network Site Details admin table
function netwside_sortTable(columnIndex, order) {
    var table = document.getElementById("network-site-details-table");
    var rows = Array.from(table.rows).slice(1);
    rows.sort(function(a, b) {
        var cellA = a.cells[columnIndex].innerText;
        var cellB = b.cells[columnIndex].innerText;
        var valueA = isNaN(cellA) ? cellA : parseInt(cellA);
        var valueB = isNaN(cellB) ? cellB : parseInt(cellB);
        if (order === 'asc') {
            return valueA > valueB ? 1 : -1;
        } else {
            return valueA < valueB ? 1 : -1;
        }
    });
    rows.forEach(function(row) {
        table.tBodies[0].appendChild(row);
    });
}
window.netwside_sortTable = netwside_sortTable;
