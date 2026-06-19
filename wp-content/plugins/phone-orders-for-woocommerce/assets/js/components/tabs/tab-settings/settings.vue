<template>
    <tr>
        <td colspan=2>
            <table class="form-table">
                <slot name="common-settings"></slot>
                <slot name="layout-settings"></slot>
                <slot name="interface-settings"></slot>
                <slot name="woocommerce-settings"></slot>
                <slot name="cart-items-settings"></slot>
                <slot name="tax-settings"></slot>
                <slot name="coupons-settings"></slot>
                <slot name="shipping-settings"></slot>
                <slot name="references-settings"></slot>
            </table>
        </td>
    </tr>
</template>

<script>

    export default {

        data: function () {
            return {
		activatedTabKey: '',
            };
        },
        methods: {
            getSettings: function () {

                var settings = {};

                this.$children.forEach(function (child) {
                    settings = Object.assign(settings, child.getSettings());
                });

                return settings;
            },
	    getTabsHeaders: function () {

		let headers = [];

		this.$children.forEach((child) => {
		    if (typeof child.getTabsHeaders === 'function') {
			headers.push(child.getTabsHeaders());
		    }
		} );

		return headers;
	    },
	    showOption: function (key) {
		return this.$children.forEach((child) => {
		    if (typeof child.showOption === 'function') {
			child.showOption(key);
		    }
		} );
	    },
        },
    }
</script>