var numeral = require("numeral");

module.exports = function (value, settings) {

    if (typeof value === 'undefined') {
	return value;
    }

    var price = numeral(value < 0 ? value * -1 : value)
		    .format("0,0." + "0".repeat(settings.decimals));

    var tmp = price.split('.');

    price = tmp[0].replace(/,/g, settings.thousand_separator);

    if (tmp.length > 1) {
	price = price + settings.decimal_separator + tmp[1];
    }

    var formatted_price = ( value < 0 ? '-' : '' ) +
			    settings.price_format
				.replace( '%1$s', '<span class="woocommerce-Price-currencySymbol">' + settings.currency_symbol + '</span>')
				.replace( '%2$s', price);

    return '<span class="woocommerce-Price-amount amount">'+ formatted_price +'</span>';
}