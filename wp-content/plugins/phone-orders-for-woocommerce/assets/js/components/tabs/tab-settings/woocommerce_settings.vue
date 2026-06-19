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
                        {{ showIconInOrdersListLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpShowIconInOrdersList" name="show_icon_in_orders_list">
                    </td>
                </tr>

                <slot name="pro-woocommerce-settings"></slot>

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
                    return 'WooCommerce';
                },
            },
            tabKey: {
                default: function() {
                    return 'woocommerceSettings';
                },
            },
            showIconInOrdersListLabel: {
                default: function () {
                    return 'Show icon for phone orders in orders list';
                },
            },
	    showIconInOrdersList: {
                default: function () {
                    return false;
                },
            },
        },
        data() {
            return {
                tmpShowIconInOrdersList: this.showIconInOrdersList,

                shown: false,
            };
        },
        methods: {
            getSettings() {

                var settings = {
                    show_icon_in_orders_list: this.tmpShowIconInOrdersList,
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