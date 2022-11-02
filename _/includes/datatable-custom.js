/**
 * Dependent to Datatable.js
 *
 */

 /**
  * Custom type for sorting date with unstandard format
  */
jQuery.extend(jQuery.fn.dataTableExt.oSort, {
    'non-empty-date-asc': function (a, b) {
        if(a == "")
            return 1;
        if(b == "")
            return -1;
        var x = Date.parse(a);
        var y = Date.parse(b);
        if (x == y) { return 0; }
        if (isNaN(x) || x < y) { return 1; }
        if (isNaN(y) || x > y) { return -1; }
    },
    'non-empty-date-desc': function (a, b) {
        if(a == "")
            return 1;
        if(b == "")
            return -1;
        var x = Date.parse(a);
        var y = Date.parse(b);
        if (x == y) { return 0; }
        if (isNaN(y) || x < y) { return -1; }
        if (isNaN(x) || x > y) { return 1; }
    }
});