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
                        {{ showOrderDateTimeLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpShowOrderDateTime"
                               name="show_order_date_time">
                    </td>
                </tr>

                <tr>
                    <td>
                        {{ showOrderStatusLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpShowOrderStatus" name="show_order_status">
                    </td>
                </tr>

                <tr>
                    <td>
                        {{ showPaymentMethodsLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpShowPaymentMethods" name="show_payment_methods">
                    </td>
                </tr>

                <tr>
                    <td>
                        {{ orderFieldsPositionLabel }}
                    </td>
                    <td>
                        <label>
                        <input type="radio" class="option" v-model="tmpOrderFieldsPosition" name="order_fields_position" value="above_customer_details">
                        {{ orderFieldsPositionAboveCustomerDetailsLabel }}
                        </label>
                        <label>
                        <input type="radio" class="option" v-model="tmpOrderFieldsPosition" name="order_fields_position" value="below_customer_details">
                        {{ orderFieldsPositionBelowCustomerDetailsLabel }}
                        </label>
                    </td>
                </tr>

                <slot name="pro-layout-settings"></slot>

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
                    return 'Layout';
                },
            },
            tabKey: {
                default: function() {
                    return 'layoutSettings';
                },
            },
            showOrderDateTimeLabel: {
                default: function () {
                    return 'Show order date/time';
                },
            },
            showOrderDateTime: {
                default: function () {
                    return false;
                },
            },
            showOrderStatusLabel: {
                default: function () {
                    return 'Show order status';
                },
            },
            showOrderStatus: {
                default: function () {
                    return false;
                },
            },
            showPaymentMethodsLabel: {
                default: function () {
                    return 'Show payment method';
                },
            },
            showPaymentMethods: {
                default: function () {
                    return false;
                },
            },
            orderFieldsPositionLabel: {
                default: function () {
                    return 'Order fields position';
                }
            },
            orderFieldsPositionAboveCustomerDetailsLabel: {
                default: function () {
                    return 'above customer details';
                }
            },
            orderFieldsPositionBelowCustomerDetailsLabel: {
                default: function () {
                    return 'below customer details';
                }
            },
            orderFieldsPosition: {
                default: function () {
                    return 'below_customer_details';
                }
            },
        },
        data() {
            return {
                tmpShowOrderDateTime: this.showOrderDateTime,
                tmpShowOrderStatus: this.showOrderStatus,
                tmpShowPaymentMethods: this.showPaymentMethods,
                tmpOrderFieldsPosition: this.orderFieldsPosition,

                shown: false,
            };
        },
        methods: {
            getSettings() {

                var settings = {
                    show_order_date_time: this.tmpShowOrderDateTime,
                    show_order_status: this.tmpShowOrderStatus,
                    show_payment_methods: this.tmpShowPaymentMethods,
                    order_fields_position: this.tmpOrderFieldsPosition,
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