<template>
    <div class="postbox disable-on-order">
        <h2>
            <span>{{ title }}</span>
        </h2>
        <div class="order-status-select">
            <multiselect
                    :allow-empty="false"
                    :hide-selected="true"
                    :searchable="false"
                    style="width: 100%;max-width: 800px;"
                    label="title"
                    v-model="statusOrder"
                    :options="orderStatusesList"
                    track-by="value"
                    :show-labels="false"
                    @input="onChange"
                    :disabled="!cartEnabled"
            >
                <template slot="noOptions">
                    <span v-html="noOptionsTitle"></span>
                </template>
            </multiselect>
        </div>
    </div>
</template>

<style>
    .postbox.disable-on-order .order-status-select {
        padding: 5px;
    }
</style>


<script>

    import Multiselect from 'vue-multiselect';

	export default {
        created() {
            this.$root.bus.$on('marked-as-paid', (data) => {
                this.statusOrder = this.getObjectByKeyValue(this.orderStatusesList, 'value', data.order_status);
            });
        },
		props: {
			title: {
				default: function () {
					return 'Order status';
				}
			},
            orderStatusesList: {
                default: function() {
                    return [];
                },
            },
            noOptionsTitle: {
                default: function() {
                    return 'List is empty.';
                }
            },
		},
		data: function () {
			return {
                statusOrder: {},
			};
		},
		watch: {
            storedOrderStatus( newVal, oldVal ) {
              this.statusOrder = this.getObjectByKeyValue(this.orderStatusesList, 'value', newVal);
            },
            orderStatusOption(newVal, oldVal) {
                this.statusOrder = this.getObjectByKeyValue(this.orderStatusesList, 'value', newVal);
                this.onChange();
            },
		},
		computed: {
			storedOrderStatus: {
				get: function () {
					return this.$store.state.add_order.order_status;
				},
				set: function ( newVal ) {
					this.$store.commit( 'add_order/updateOrderStatus', newVal );
				},
			},
            showOrderStatus () {
                return this.getSettingsOption('show_order_status');
            },
            orderStatusOption () {
                return this.getSettingsOption('order_status');
            },
		},
		methods: {
            onChange: function () {
                this.storedOrderStatus = this.getKeyValueOfObject(this.statusOrder, 'value');
            },
		},
		components: {
            Multiselect,
		},
	}
</script>