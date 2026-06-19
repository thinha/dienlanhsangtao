var numeral = require("numeral");

module.exports = function (value, precision) {

    if (typeof value === 'undefined') {
	return value;
    }

    var _precision = typeof precision !== 'undefined' ? +precision : 2;
    return numeral(value).format("0." + "0".repeat(_precision));
}