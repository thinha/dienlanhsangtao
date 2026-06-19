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
                        {{ allowEditShippingCostLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="elAllowEditShippingCost" name="allow_edit_shipping_cost">
                    </td>
                </tr>
                <tr>
                    <td>
                        {{ allowEditShippingTitleLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="elAllowEditShippingTitle" name="allow_edit_shipping_title">
                    </td>
                </tr>

		<tr>
		    <td>
			{{ orderDefaultShippingMethodLabel }}
		    </td>
		    <td>
			<table style="width: 100%">
			    <tr v-for="zone in orderShippingZonesList">
				<td>
				    {{ zone.title }}
				</td>
				<td>
				    <multiselect
					:allow-empty="false"
					:searchable="false"
					style="width: 100%;max-width: 800px;"
					label="title"
					v-model="elOrderDefaultZonesShippingMethod[zone.id]"
					:options="zone.shipping_methods"
					track-by="value"
					:show-labels="false"
				    >
					<template slot="noOptions">
					    <span v-html="noOptionsTitle"></span>
					</template>
				    </multiselect>
				</td>
			    </tr>
			</table>
		    </td>
		</tr>

				<tr>
					<td>
						{{ allowToCreateOrdersWithoutShippingLabel }}
					</td>
					<td>
						<input type="checkbox" class="option" v-model="elAllowToCreateOrdersWithoutShipping" name="allow_to_create_orders_without_shipping">
					</td>
				</tr>

                <slot name="pro-shipping-settings"></slot>

                </tbody>
            </table>
        </td>
    </tr>
</template>

<style>


</style>

<script>

    import Multiselect from 'vue-multiselect';

    export default {
        props: {
            title: {
                default: function () {
                    return 'Shipping';
                },
            },
            tabKey: {
                default: function() {
                    return 'shippingSettings';
                },
            },
            allowEditShippingCostLabel: {
                default: function () {
                    return 'Allow to edit shipping cost';
                },
            },
            allowEditShippingCost: {
                default: function () {
                    return false;
                },
            },
            allowEditShippingTitleLabel: {
                default: function () {
                    return 'Allow to edit shipping title';
                },
            },
            allowEditShippingTitle: {
                default: function () {
                    return false;
                },
            },
	    orderDefaultShippingMethodLabel: {
                default: function() {
                    return 'Default shipping method';
                }
            },
            orderShippingZonesList: {
                default: function() {
                    return [];
                }
            },
            orderDefaultZonesShippingMethod: {
                default: function() {
                    return {};
                }
            },
			allowToCreateOrdersWithoutShippingLabel: {
				default: function () {
					return 'Allow to create orders without shipping';
				},
			},
			allowToCreateOrdersWithoutShipping: {
				default: function () {
					return false;
				},
			},
        },
        data() {

	    var orderDefaultZonesShippingMethod = {};

	    this.orderShippingZonesList.forEach((zone) => {
		orderDefaultZonesShippingMethod[zone.id] = this.getObjectByKeyValue(zone.shipping_methods, 'value', this.orderDefaultZonesShippingMethod[zone.id] || '');
	    });

	    return {
                elAllowEditShippingCost: this.allowEditShippingCost,
                elAllowEditShippingTitle: this.allowEditShippingTitle,
		elOrderDefaultZonesShippingMethod: orderDefaultZonesShippingMethod,
				elAllowToCreateOrdersWithoutShipping: this.allowToCreateOrdersWithoutShipping,
                shown: false,
            };
        },
        methods: {
            getSettings() {

		var orderDefaultZonesShippingMethod = {};

		for (var zoneID in this.elOrderDefaultZonesShippingMethod) {
		    orderDefaultZonesShippingMethod[zoneID] = this.getKeyValueOfObject(this.elOrderDefaultZonesShippingMethod[zoneID], 'value');
		}

                var settings = {
                    allow_edit_shipping_cost: this.elAllowEditShippingCost,
                    allow_edit_shipping_title: this.elAllowEditShippingTitle,
		    order_default_zones_shipping_method: orderDefaultZonesShippingMethod,
					allow_to_create_orders_without_shipping: this.elAllowToCreateOrdersWithoutShipping,
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
	components: {
            Multiselect,
        },
    }
</script>