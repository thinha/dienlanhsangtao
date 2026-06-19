<template>
    <tr v-show="shown">
        <td colspan=2>
            <table class="form-table">
                <tbody>
                <tr>
                    <td colspan=2>
                        <b>{{ title }}</b>
                    </td>
                </tr>

		<tr>
                    <td>
                        {{ allowToInputFractionalQtyLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpAllowToInputFractionalQty" name="allow_to_input_fractional_qty">
                    </td>
                </tr>

                <tr>
                    <td>
                        {{ scrollableCartContentsLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpScrollableCartContents" name="scrollable_cart_contents">
                    </td>
                </tr>

                <tr>
                    <td>
                        {{ showCartLinkLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpShowCartLink" name="show_cart_link">
			<span class="show_cart_link_note">({{ showCartLinkNote }})</span>
                    </td>
                </tr>

		<tr>
                    <td>
                        {{ showColumnDiscountLabel }}
                    </td>
                    <td>
			<input type="checkbox" class="option" v-model="tmpShowColumnDiscount" name="show_column_discount">
                    </td>
                </tr>


                <slot name="pro-cart-items-settings"></slot>

                </tbody>
            </table>
        </td>
    </tr>
</template>

<style>


</style>

<script>

    export default {
        props: {
            title: {
                default: function () {
                    return 'Cart Items';
                },
            },
            tabKey: {
                default: function() {
                    return 'cartItemsSettings';
                },
            },
            showCartLinkLabel: {
                default: function () {
                    return 'Show button "Copy url to populate cart"';
                },
            },
            showCartLink: {
                default: function () {
                    return false;
                },
            },
            showCartLinkNote: {
                default: function () {
                    return 'warning : this feature is not compatible with discounts';
                },
            },
            scrollableCartContentsLabel: {
                default: function () {
                    return 'Scrollable cart contents';
                }
            },
            scrollableCartContents: {
                default: function () {
                    return false;
                }
            },
	    allowToInputFractionalQtyLabel: {
                default: function () {
                    return 'Allow to input fractional qty';
                },
            },
            allowToInputFractionalQty: {
                default: function () {
                    return false;
                },
            },
	    showColumnDiscountLabel: {
                default: function () {
                    return 'Show column "Discount"';
                }
            },
            showColumnDiscount: {
                default: function () {
                    return false;
                }
            },
        },
        data() {
            return {
                tmpShowCartLink: this.showCartLink,
                tmpScrollableCartContents: this.scrollableCartContents,
		tmpAllowToInputFractionalQty: this.allowToInputFractionalQty,
		tmpShowColumnDiscount: this.showColumnDiscount,

                shown: false,
            };
        },
        methods: {
            getSettings() {

                var settings = {
                    show_cart_link: this.tmpShowCartLink,
                    scrollable_cart_contents: this.tmpScrollableCartContents,
		    allow_to_input_fractional_qty: this.tmpAllowToInputFractionalQty,
		    show_column_discount: this.tmpShowColumnDiscount,
		};

                var childsSettings = {};

                this.$children.forEach(function (child) {
                    if (typeof child.getSettings === 'function') {
                        childsSettings = Object.assign(childsSettings, child.getSettings());
                    }
                });

                return Object.assign(settings, childsSettings);
            },
            getTabsHeaders() {
                return {
                    key: this.tabKey,
                    title: this.title,
                };
            },
            showOption(key) {
                this.shown = this.tabKey === key;
            },
        },
    }
</script>