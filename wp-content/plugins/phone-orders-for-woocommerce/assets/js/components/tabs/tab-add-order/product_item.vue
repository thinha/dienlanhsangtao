<template>
    <tr class="item new_row">
	<template v-if="isChild">
	    <td class="thumb text-center"></td>
	    <td class="name item__wpo-readonly-child-item">
		<table>
		    <tbody>
			<tr>
			    <td class="thumb">
				<div class="wc-order-item-thumbnail">
				    <img :src="item.thumbnail" class="attachment-thumbnail size-thumbnail wp-post-image" height="80" width="80">
				</div>
			    </td>
			    <td class="name">
				<a v-if="productLink" target="_blank" :href="productLink" class="wc-order-item-name">
				    {{ item.name }}
				</a>
				<div v-else class="wc-order-item-name">
				    {{ item.name }}
				</div>
				<div class="wc-order-item-name" v-if="item.is_subscribed" v-html="item.product_price_html"></div>
				<div class="wc-order-item-sku" v-if="item.sku">
				    <strong>
					{{ skuLabel }}:
				    </strong>
				    {{ item.sku }}
				</div>
				<div class="wc-order-item-variation" v-if="item.variation_id">
				    <strong>
					{{ variationIDLabel }}:
				    </strong>
				    {{ item.variation_id }}
				</div>
                                <div class="wc-order-item-variation" v-for="(variation_attribute, key) in this.variationAttributes">
                                    <strong>
                                    {{ key }}:
                                    </strong>
                                    {{variation_attribute}}
                                </div>
				<div class="wc-order-item-readonly-custom-meta-fields" v-if="item.readonly_custom_meta_fields_html">
				    <div v-html="item.readonly_custom_meta_fields_html"></div>
				</div>
                                <product-missing-attribute
                                        v-bind="productMissingAttributeLabels"
                                        v-for="(variation_attribute, index) in this.missingVariationAttributes"
                                        :key="variation_attribute.key"
                                        :index="index"
                                        :attribute = "Object.assign({}, variation_attribute)"
                                        :itemKey="item.key"
                                ></product-missing-attribute>
				 <div class="item-msg">
				     {{
					 item.in_stock === null || item.in_stock <= 0 || item.in_stock > tmpQty ?
					     ''
					 :
					     productStockMessage.replace('%s', item.in_stock)
				     }}
				 </div>
			    </td>
			</tr>
		    </tbody>
		</table>
	    </td>
	    <td class="item_cost">
		<div class="edit wpo-item-cost-value" :class="{'hide': item.wpo_hide_item_price}">
		    <div class="readonly_price">
			{{ cost | formatPrice(precision) }}
		    </div>
                    <div class="cost_with_tax" style="padding: 4px" v-if="showCostWithTax">
			{{ costWithTax | formatPrice(precision) }}
		    </div>
		</div>
	    </td>
	    <td class="quantity">
		<div class="edit wpo-quantity-value">
		    <div style="padding: 4px">
			{{ qty }}
		    </div>
		</div>
	     </td>
	     <td class="line_total">
		<div class="wpo-line-total-value" :class="{'hide': item.wpo_hide_item_price}">
		    <div class="total" style="padding: 4px;" v-html="wcPrice(total, {decimals: this.precision})"></div>
		    <div class="total_with_tax" v-if="showTotalWithTax" style="padding: 4px;" v-html="wcPrice(totalWithTax, {decimals: this.precision})"></div>
		</div>
	    </td>
	    <td></td>
	</template>
	<template v-else>
		<td class="wc-order-move-line-item">
		 <div class="wc-order-move-line-item-actions">
			 <fa-icon icon="align-justify" class="handle"/>
		 </div>
	     </td>
	    <td class="thumb">
		<div class="wc-order-item-thumbnail">
		    <div class="wc-order-item-thumbnail">
			<img :src="item.thumbnail" class="attachment-thumbnail size-thumbnail wp-post-image" height="80" width="80">
		    </div>
		</div>
	    </td>
	    <td class="name">
		<a v-if="productLink" target="_blank" :href="productLink" class="wc-order-item-name">
		    {{ item.name }}
		</a>
		<div v-else class="wc-order-item-name">
		    {{ item.name }}
		</div>
		<div class="wc-order-item-name" v-if="item.is_subscribed" v-html="item.product_price_html"></div>
		<div class="wc-order-item-sku" v-if="item.sku">
		    <strong>
			{{ skuLabel }}:
		    </strong>
		    {{ item.sku }}
		</div>

		<div class="wc-order-item-variation" v-if="item.variation_id">
		    <strong>
			{{ variationIDLabel }}:
		    </strong>
		    {{ item.variation_id }}
		</div>
		<div class="wc-order-item-variation" v-for="(variation_attribute, key) in this.variationAttributes">
			<strong>
			{{ key }}:
			</strong>
			{{variation_attribute}}
		</div>
		<div class="wc-order-item-readonly-custom-meta-fields" v-if="item.readonly_custom_meta_fields_html">
		    <div v-html="item.readonly_custom_meta_fields_html"></div>
		</div>
		<product-missing-attribute
			v-bind="productMissingAttributeLabels"
			v-for="(variation_attribute, index) in this.missingVariationAttributes"
			:key="variation_attribute.key"
			:index="index"
			:attribute = "Object.assign({}, variation_attribute)"
			:itemKey="item.key"
		></product-missing-attribute>

		<product-subscription-fields
			v-if="item.is_subscribed && subscriptionFields"
			v-bind="productSubscriptionOptions"
			:fields="subscriptionFields"
			@update="updateSubscriptionFields"
		></product-subscription-fields>

		<product-custom-meta-fields
		    v-bind="productCustomMetaFieldsLabels"
		    :fields="customMetaFields"
		    :editable-fields="editableCustomMetaFields"
		    @update="updateCustomMetaFields"
		    @update-editable-fields="updateEditableCustomMetaFields"
		></product-custom-meta-fields>

		 <div class="item-msg">
		     {{
			 item.in_stock === null || item.in_stock <= 0 || item.in_stock > tmpQty ?
			     ''
			 :
			     productStockMessage.replace('%s', item.in_stock)
		     }}
		 </div>
		 <div class="edit wpo-item-cost-value" style="margin-top: 10px;">
		    {{ productsTableCostColumnTitle }}:
		    <template v-if="originalPrice">
			<div class="sale_price">
			    <del>
				{{ originalPrice | formatPrice(precision) }}
			    </del>
			    <ins>
				{{ cost | formatPrice(precision) }}
			    </ins>
			</div>
		    </template>
		    <template v-else-if="isReadOnly">
			<div class="readonly_price">
			    {{ (item.readonly_price ? item.readonly_price : cost) | formatPrice(precision) }}
			</div>
		    </template>
		    <template v-else>
			<input type="text" autocomplete="off" placeholder="0" v-model.lazy="costModel" size="4" v-bind:disabled="!cartEnabled">
		    </template>
		    <div class="cost_with_tax" style="padding: 4px" v-if="showCostWithTax">
			{{ costWithTax | formatPrice(precision) }}
		    </div>
		</div>
		<div class="edit wpo-quantity-value" style="margin-top: 10px;">
		    {{ productsTableQtyColumnTitle }}:
		    <div v-if="soldIndividually || isReadOnlyQty" style="padding: 4px">
			{{ qty }}
		    </div>
		    <input v-else
			ref="qty"
			type="number"
			:step="item.qty_step"
			:min="minQty"
			autocomplete="off"
			placeholder="0"
			v-model.number="tmpQty"
			size="4"
			class="qty"
			:disabled="!cartEnabled"
			:max="item.in_stock"
			@keyup.enter="openProductSearchSelect"
			@blur="changeQty"
			@mousedown="setFocus"
			style="max-width: 40px; height: 25px;"
		    />
		</div>
		<div class="item_discount" v-if="showColumnDiscount">
		    {{ columnDiscountTitle }}:
		    <b-form-radio-group
			buttons
			button-variant="outline-primary"
			v-model="itemDiscountType"
			name="discount-type"
			:disabled="!cartEnabled"
		    >
			<b-form-radio value="fixed">
			    <span v-html="currencySymbol"></span>
			</b-form-radio>
			<b-form-radio value="percent">%</b-form-radio>
		    </b-form-radio-group>
		    <input type="text" autocomplete="off" placeholder="0" v-model.lazy="itemDiscountValue" :disabled="!cartEnabled" class="form-control">
		</div>
	    </td>
	    <td class="item_extra_col" v-if="showProductsTableExtraColumn" v-html="item.extra_col_value"></td>
	    <td class="item_discount" v-if="showColumnDiscount">
		<b-form-radio-group
		    buttons
		    button-variant="outline-primary"
		    v-model="itemDiscountType"
		    name="discount-type"
		    :disabled="!cartEnabled"
		>
		    <b-form-radio value="fixed">
			<span v-html="currencySymbol"></span>
		    </b-form-radio>
		    <b-form-radio value="percent">%</b-form-radio>
		</b-form-radio-group>
		<input type="text" autocomplete="off" placeholder="0" v-model.lazy="itemDiscountValue" :disabled="!cartEnabled" class="form-control">
	    </td>
	    <td class="item_cost">
		<div class="edit wpo-item-cost-value">
		    <template v-if="originalPrice">
			<div class="sale_price">
			    <del>
				{{ originalPrice | formatPrice(precision) }}
			    </del>
			    <ins>
				{{ cost | formatPrice(precision) }}
			    </ins>
			</div>
		    </template>
		    <template v-else-if="isReadOnly">
			<div class="readonly_price">
			    {{ (item.readonly_price ? item.readonly_price : cost) | formatPrice(precision) }}
			</div>
		    </template>
		    <template v-else>
			<input type="text" autocomplete="off" placeholder="0" v-model.lazy="costModel" size="4" v-bind:disabled="!cartEnabled">
		    </template>
		    <div class="cost_with_tax" style="padding: 4px" v-if="showCostWithTax">
			{{ costWithTax | formatPrice(precision) }}
		    </div>
		</div>
	    </td>
	    <td class="quantity">
		<div class="edit wpo-quantity-value">
		    <div v-if="soldIndividually || isReadOnlyQty" style="padding: 4px">
			{{ qty }}
		    </div>
		    <input v-else
			ref="qty"
			type="number"
			:step="item.qty_step"
			:min="minQty"
			autocomplete="off"
			placeholder="0"
			v-model.number="tmpQty"
			size="4"
			class="qty"
			:disabled="!cartEnabled"
			:max="item.in_stock"
			@keyup.enter="openProductSearchSelect"
			@blur="changeQty"
			@mousedown="setFocus"
		    />
		</div>
	     </td>
	     <td class="line_total">
		<div class="wpo-line-total-value">
		    <div class="total" style="padding: 4px;" v-html="wcPrice(total, {decimals: this.precision})"></div>
		    <div class="total_with_tax" v-if="showTotalWithTax" style="padding: 4px;" v-html="wcPrice(totalWithTax, {decimals: this.precision})"></div>
		</div>
	     </td>
	     <td class="wc-order-edit-line-item">
		 <div class="wc-order-edit-line-item-actions">
		     <a @click.prevent.stop="cartEnabled ? removeItem(item) : null" class="delete-order-item tips" href="#" :title="deleteProductItemButtonTooltipText"></a>
		 </div>
	     </td>
	 </template>
     </tr>
</template>

<style>
    .item_cost .readonly_price, .item_cost .sale_price {
        padding: 4px;
    }

    .item__wpo-readonly-child-item table {
	width: 100%;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items tbody tr td.item__wpo-readonly-child-item table tr td {
	border-width: 0;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item__wpo-readonly-child-item {
	padding-top: 0;
	padding-bottom: 0;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item__wpo-readonly-child-item td.thumb {
	padding-left: 0;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item__wpo-readonly-child-item td.name {
	padding-right: 0;
	width: 100%;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items .text-center {
	text-align: center;
    }

    #woocommerce-order-items .wc-order-item-readonly-custom-meta-fields {
	margin-top: 10px;
    }

    #woocommerce-order-items .wc-order-item-readonly-custom-meta-fields p {
	margin-bottom: 5px;
    }

    #woocommerce-order-items .wc-order-item-readonly-custom-meta-fields dl.variation dt {
	font-weight: bold;
	display: inline;
	margin: 0 4px 0 0;
	padding: 0;
	float: left;
    }

    #woocommerce-order-items .wc-order-item-readonly-custom-meta-fields dl.variation dd {
	display: inline;
	padding: 0;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.name .wpo-item-cost-value,
    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.name .wpo-quantity-value,
    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.name .item_discount {
	display: none;
    }

    @media (max-width:767px){
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.name .wpo-item-cost-value,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.name .wpo-quantity-value,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.name .item_discount {
	    display: block;
	}
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item_discount input {
       width: 70px;
       vertical-align: middle;
       text-align: right;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper .woocommerce_order_items td.item_discount {
       display: flex;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item_discount .btn-group {
	margin-right: 5px;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item_discount .btn-group label > span {
	vertical-align: middle;
	line-height: 28px;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.name .item_discount .btn-group {
	margin: 10px 0;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.name .item_discount .form-control {
	max-width: 70px;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper .wpo-item-cost-value.hide,
    #woocommerce-order-items .woocommerce_order_items_wrapper .wpo-line-total-value.hide {
	display: none;
    }

</style>

<script>

    import ProductMissingAttribute from './product_missing_attribute.vue';
    import ProductCustomMetaFields from './product_custom_meta_fields.vue';
    import ProductSubscriptionFields from './product_subscription_fields.vue';

    import {library} from '@fortawesome/fontawesome-svg-core';
    import {faAlignJustify} from '@fortawesome/free-solid-svg-icons';
    import {FontAwesomeIcon as FaIcon} from '@fortawesome/vue-fontawesome';
    library.add(faAlignJustify)

    export default {
        props: {
            item: {
                default: function() {
                    return {};
                }
            },
            calculated: {
                default: function() {
                    return {};
                }
            },
            deleteProductItemButtonTooltipText: {
                default: function() {
                    return 'Delete item';
                }
            },
            skuLabel: {
                default: function() {
                    return 'SKU';
                }
            },
            productStockMessage: {
                default: function() {
                    return 'Only %s items can be purchased';
                }
            },
            variationIDLabel: {
                default: function() {
                    return 'Variation ID';
                }
            },
            productMissingAttributeLabels: {
                default: function() {
                    return {};
                }
            },
            productCustomMetaFieldsLabels: {
                default: function() {
                    return {};
                }
            },
            showProductsTableExtraColumn: {
                default: function() {
                    return false;
                }
            },
            showColumnDiscount: {
                default: function() {
                    return false;
                }
            },
            editableCustomMetaFields: {
                default: function() {
                    return false;
                }
            },
	    productsTableCostColumnTitle: {
                default: function() {
                    return 'Cost';
                }
            },
            productsTableQtyColumnTitle: {
                default: function() {
                    return 'Qty';
                }
            },
            columnDiscountTitle: {
                default: function() {
                    return 'Discount';
                }
            },
            productSubscriptionOptions: {
                default: function() {
                    return {};
                }
            },
        },
        data: function () {

	    var itemDiscount = typeof this.item.wpo_item_discount !== 'undefined' ? this.item.wpo_item_discount : {discount: 0, original_price: this.item.item_cost, discounted_price: this.item.item_cost, discount_type: 'fixed'};

            return {
                cost: this.item.item_cost,
                qty: this.item.qty,
                tmpQty: this.item.qty,
                missingVariationAttributes: this.item.missing_variation_attributes,
		variationAttributes: this.item.formatted_variation_data,
                customMetaFields: this.item.custom_meta_fields,
                costUpdatedManually: this.item.cost_updated_manually,

		itemDiscountValue: itemDiscount.discount,
		itemDiscountType: itemDiscount.discount_type,
		itemDiscount: itemDiscount,

		subscriptionFields: typeof this.item.wpo_subscription_fields !== 'undefined' ? this.item.wpo_subscription_fields : null,
            };
        },
        created: function () {
            this.$root.bus.$on( 'change-missing-attribute', ( data ) => {
            	if ( data.itemKey !== this.item.key ) {
            		return false;
                }

                if ( this.missingVariationAttributes && this.missingVariationAttributes.length ) {
                    var temp_items = [];
                    this.missingVariationAttributes.forEach( function ( current ) {
                        temp_items.push( Object.assign( {}, current ) );
                    } );

                    temp_items[data.attributeIndex].value = data.attributeValue;
                    this.missingVariationAttributes = temp_items;

                    this.updateItem();
                }
            });

            this.$root.$on('changed::tab', this.updateDiscountColumnWidth);
        },
	mounted() {
	    this.recalculateTableHeaders();
	},
	beforeDestroy() {
            this.$root.$off('changed::tab', this.updateDiscountColumnWidth);
	},
        watch: {
            cost (newVal) {
                this.updateItem();
            },
            qty (newVal, oldVal) {
            	if ( newVal !== oldVal
                     &&
                     // when autoRecalculate is disabled, qty is not updating in store, because calculatedQty is empty
                     // add "not autoRecalculate" check
                     ( ( this.calculatedQty && this.calculatedQty !== newVal ) || ! this.autoRecalculate )
                ) {
                    this.updateItem();
                }
            },
	    calculatedQty (newVal) {
            	if ( newVal ) {
		            this.tmpQty = newVal;
		            this.qty = newVal;
                }
            },
	    itemDiscountValue (newVal) {
		this.itemDiscount.discount = newVal;
		this.costUpdatedManually = true;
		this.updateItem();
            },
	    itemDiscountType (newVal) {
		this.itemDiscount.discount_type = newVal;
		this.costUpdatedManually = true;
		this.updateItem();
            },
        },
        computed: {
            costModel: {
                get () {
                    return this.$options.filters.formatPrice(this.cost, this.precision);
                },
                set (newVal) {
		    newVal = this.parseNumber(newVal);
		    this.costUpdatedManually = true;
		    this.itemDiscount.original_price = newVal;
                    return this.cost = newVal;
                },
            },
            costWithTax () {
                return typeof this.calculated.item_cost_with_tax !== 'undefined' ? this.calculated.item_cost_with_tax : '';
            },
            calculatedQty () {
	            return typeof this.calculated.qty !== 'undefined' ? this.calculated.qty : 0;
            },
            total () {
                return this.item.calc_line_subtotal ? (this.item.readonly_price ? this.item.readonly_price : this.cost) * this.qty : this.calculated.line_subtotal;
            },
            totalWithTax () {
                return typeof this.calculated.line_total_with_tax !== 'undefined' ? this.calculated.line_total_with_tax : '';
            },
	    isChild() {

		if (typeof this.calculated.wpo_child_item !== 'undefined' && this.calculated.wpo_child_item) {
		    return true;
		}

		if (typeof this.item.wpo_child_item !== 'undefined' && this.item.wpo_child_item) {
		    return true;
		}

		return false;
	    },
	    isReadOnlyQty () {
		return typeof this.calculated.is_readonly_qty !== 'undefined' ? this.calculated.is_readonly_qty : (
			typeof this.item.is_readonly_qty !== 'undefined' ? this.item.is_readonly_qty : false
		);
	    },
	    isReadOnly () {
		    return typeof this.calculated.is_readonly_price !== 'undefined' ? this.calculated.is_readonly_price : (
			    typeof this.item.is_readonly_price !== 'undefined' ? this.item.is_readonly_price : false
		    );
	        },
            // price before pricing plugin was applied
	        originalPrice () {
		        let originalPrice = false;
		        if ( typeof this.calculated.original_price !== 'undefined' ) {
			        originalPrice = this.calculated.original_price;
			        if ( originalPrice !== false ) {
						this.costUpdatedManually = false;
				        this.cost = this.calculated.item_cost;
                    }
		        } else {
			        originalPrice = typeof this.item.original_price !== 'undefined' ? this.item.original_price : false;
		        }

		        return originalPrice;
	        },
            precision () {
                return this.getSettingsOption('item_price_precision');
            },
            autoRecalculate () {
                return this.getSettingsOption('auto_recalculate');
            },
            productKey () {
                return this.item.key ? this.item.key : (this.item.variation_id ? this.item.variation_id : this.item.product_id);
            },
            productLink () {
                return typeof window.wpo_frontend === 'undefined' && this.isDefaultActionProductItemLinkEditProduct ? this.item.product_link : this.item.permalink;
            },
            minQty () {
                return this.getSettingsOption('allow_to_input_fractional_qty') ? '0.01' : '1';
            },
            isDefaultActionProductItemLinkEditProduct () {
                return this.getSettingsOption('action_click_on_title_product_item_in_cart', 'edit_product') === 'edit_product';
            },
	    showCostWithTax () {
                return this.costWithTax && !this.hideTaxLineProductItem;
            },
            showTotalWithTax () {
                return this.totalWithTax && !this.hideTaxLineProductItem;
            },
            hideTaxLineProductItem () {
                return this.getSettingsOption('hide_tax_line_product_item');
            },
	    currencySymbol() {
		return this.$store.state.add_order.cart.wc_price_settings.currency_symbol;
	    },
        },
        methods: {
            updateItem () {
		this.autoRecalculate && this.$store.commit('add_order/setIsLoadingWithoutBackground', true);
                this.$root.bus.$emit('clear-calculated-item', this.productKey);
                this.setToStoreUpdatedItem();
            },
            setToStoreUpdatedItem() {

		this.itemDiscount.discounted_price = this.calcDiscount(this.itemDiscount.original_price, this.itemDiscount.discount, this.itemDiscount.discount_type);

		var discountData = {};

		if (this.showColumnDiscount) {
		    discountData = {
			item_cost: this.itemDiscount.discounted_price,
			wpo_item_discount: this.itemDiscount,
		    };
		}

		this.$store.commit('add_order/updateCartItem', {
                    key: this.item.key,
                    item: Object.assign(this.item,
                        {
                            item_cost: this.cost,
                            qty: this.qty,
                            missing_variation_attributes: this.missingVariationAttributes,
			    custom_meta_fields: this.customMetaFields,
                            sold_individually: this.soldIndividually,
			    is_readonly_price: this.isReadOnly,
			    original_price: this.originalPrice,
			    cost_updated_manually: this.costUpdatedManually,
			    key: this.productKey + +(new Date()),
			    wpo_subscription_fields: this.subscriptionFields,
                        },
			discountData
                    ),
                });
            },
            removeItem (item) {

		var delete_items = [item];

		if (typeof item.children !== 'undefined' && item.children) {
		    this.$store.state.add_order.cart.items.forEach((_item) => {
			if (_item.wpo_cart_item_key && item.children.indexOf(_item.wpo_cart_item_key) > -1) {
			    delete_items.push(_item);
			}
		    })
		}

		delete_items.forEach((_item) => {
		    this.$root.bus.$emit('clear-calculated-item', _item.key);
		    this.$root.bus.$emit('clear-selected-item', _item.variation_id ? _item.variation_id : _item.product_id);
		    this.$store.commit('add_order/removeCartItem', _item.key);
		});
            },
            openProductSearchSelect () {
                this.$root.bus.$emit('open-search-product');
            },
            changeQty () {
                this.qty = this.tmpQty;
            },
            setFocus (e) {
                e.target.focus();
            },
            updateCustomMetaFields(data) {

                var tmp = [];

                data.custom_meta_fields.forEach((v) => {
                    tmp.push(Object.assign({}, v));
                });

                this.customMetaFields = tmp;
                this.setToStoreUpdatedItem();
            },
	    recalculateTableHeaders() {
		var ths = this.$parent.$parent.$refs.woocommerceOrderItems.children[0].children[0].children;
		var tds = this.$el.children;

		for(var i = 2; i <= tds.length - 1; i++) {
		    ths[i - 1].style= "width: " + tds[i].offsetWidth + "px;";
		}
	    },
	    updateEditableCustomMetaFields(data) {
		this.$emit('update-editable-custom-meta-fields', {editable: data.editable, product_key: this.item.key});
	    },
	    calcDiscount(cost, discount, discount_type) {

		var discounted_cost = cost;

		if (discount_type === 'fixed') {
		    discounted_cost = cost - +discount;
		} else if (discount_type === 'percent') {
		    discounted_cost = cost - cost * +discount / 100;
		}

		return discounted_cost > 0 ? discounted_cost : 0;
	    },
	    updateDiscountColumnWidth(tabs, index) {

		if (index !== 0) {
		    return;
		}

		this.$nextTick(() => {
		    this.recalculateTableHeaders();
		})
	    },
	    parseNumber(str) {
		var number = parseFloat(str.replace(new RegExp('\,', 'g'), '.'));
		return isNaN(number) ? 0 : number;
	    },
	    updateSubscriptionFields(fields) {
		this.subscriptionFields = fields;
		this.updateItem();
	    },
        },
	components: {
	    ProductMissingAttribute,
	    ProductCustomMetaFields,
	    ProductSubscriptionFields,
	    FaIcon,
	},
    }
</script>