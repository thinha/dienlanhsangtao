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
                <img :src="item.thumbnail" class="attachment-thumbnail size-thumbnail wp-post-image" height="80"
                     width="80">
              </div>
            </td>
            <td class="name">
              <template v-if="!itemRenaming">
                <a v-if="productLink" target="_blank" :href="productLink" class="wc-order-item-name">
                  {{ itemName }}
                </a>
                <div v-else class="wc-order-item-name">
                  {{ itemName }}
                </div>
              </template>
              <div v-else>
                <input type="text" ref="renameInput" style="width: 100%" @keydown.esc="toggleRenameCartItem"
                       @keyup.enter="renameCartItem">
              </div>
              <div v-if="isAllowedToRenameCartItems && !itemRenaming" style="display: inline"
                   @click.prevent="cartEnabled ? toggleRenameCartItem() : null">
                <fa-icon icon="edit"/>
              </div>
              <div class="wc-order-item-name" v-if="item.is_subscribed" v-html="item.product_price_html"></div>
              <div v-show="!hideItemMeta">
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
                  {{ variation_attribute }}
                </div>
                <div class="wc-order-item-readonly-custom-meta-fields" v-if="item.readonly_custom_meta_fields_html">
                  <div v-html="item.readonly_custom_meta_fields_html"></div>
                </div>
                <div class="wc-order-item-weight" v-if="showCartWeight && item.weight">
                  <strong>{{ weightLabel }}:</strong> {{ item.weight }} <span v-if="isShowWeightUnit">{{
                    weightUnit
                  }}</span>
                </div>
                <product-missing-attribute
                  v-bind="productMissingAttributeLabels"
                  v-for="(variation_attribute, index) in this.missingVariationAttributes"
                  :key="variation_attribute.key"
                  :index="index"
                  :attribute="Object.assign({}, variation_attribute)"
                  :itemKey="item.key"
                ></product-missing-attribute>
              </div>
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
            {{ formatPrice(cost, precision) }}
          </div>
          <div class="cost_with_tax" style="padding: 4px" v-if="showCostWithTax">
            {{ formatPrice(costWithTax, precision) }}
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
          <div class="total_with_tax" v-if="showTotalWithTax" style="padding: 4px;"
               v-html="wcPrice(totalWithTax, {decimals: this.precision})"></div>
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
        <div class="block-main">
          <template v-if="!itemRenaming">
            <a v-if="productLink" target="_blank" :href="productLink" class="wc-order-item-name">
              {{ itemName }}
            </a>
            <div v-else class="wc-order-item-name">
              {{ itemName }}
            </div>
          </template>
          <div v-else>
            <input type="text" ref="renameInput" style="width: 100%" @keydown.esc="toggleRenameCartItem"
                   @keyup.enter="renameCartItem">
          </div>
          <div v-if="isAllowedToRenameCartItems && !itemRenaming" style="display: inline"
               @click.prevent="cartEnabled ? toggleRenameCartItem() : null">
            <fa-icon icon="edit"/>
          </div>
          <div v-if="isShowProductDescription">
            <div v-if="this.stripTags(item.description).length <= showProductDescriptionPreviewSize">
              <span v-html="item.description"></span>
            </div>
            <div v-else>
              <span v-html="item.description.substring(0, this.showProductDescriptionPreviewSize)"></span>... <a href="#"
                                                                                                                 @click.prevent="cartEnabled ? showItemDescription() : null">{{
                readMoreLabel
              }}</a>
            </div>
          </div>
          <div class="wc-order-item-name" v-if="item.is_subscribed" v-html="item.product_price_html"></div>
          <div v-show="!hideItemMeta">
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
            <div v-if="this.item.variable_data" v-for="(values, key) in this.variableAttributes"
                 class="wc-order-item-variable-attribute">
              <strong>{{ this.variableAttributeLabels[key] }} </strong>
              <select @change="updateVariableSelectedAttributes(key, $event.target.value)" :data-attribute="key"
                      :disabled="!cartEnabled">
                <option value="" :selected="Object.keys(this.variableSelectedAttributes).length === 0">{{ anyLabel }}
                </option>
                <option v-for="value in values" :value="value"
                        :selected="this.variableSelectedAttributes.hasOwnProperty(key) && this.variableSelectedAttributes[key] === value">
                  {{ value }}
                </option>
              </select>
            </div>
            <div v-else class="wc-order-item-variation" v-for="(variation_attribute, key) in this.variationAttributes">
              <strong>
                {{ key }}:
              </strong>
              {{ variation_attribute }}
            </div>
            <div class="wc-order-item-readonly-custom-meta-fields" v-if="item.readonly_custom_meta_fields_html">
              <div v-html="item.readonly_custom_meta_fields_html"></div>
            </div>
            <div class="wc-order-item-weight" v-if="showCartWeight && item.weight">
              <strong>{{ weightLabel }}:</strong> {{ item.weight }} <span v-if="isShowWeightUnit">{{ weightUnit }}</span>
            </div>
            <product-missing-attribute
              v-bind="productMissingAttributeLabels"
              v-for="(variation_attribute, index) in this.missingVariationAttributes"
              :key="variation_attribute.key"
              :index="index"
              :attribute="Object.assign({}, variation_attribute)"
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
              :removed-fields:="removedCustomMetaFieldsKeys"
              @update="updateCustomMetaFields"
              @update-editable-fields="updateEditableCustomMetaFields"
            ></product-custom-meta-fields>
          </div>
          <div class="item-msg">
            {{
              item.in_stock === null || item.in_stock <= 0 || item.in_stock > tmpQty ?
                ''
                :
                productStockMessage.replace('%s', item.in_stock)
            }}
          </div>
        </div>
        <div class="block-rest">
          <div class="edit wpo-item-cost-value" style="margin-top: 10px;">
            {{ productsTableCostColumnTitle }}:
            <template v-if="originalPrice">
              <div class="sale_price">
                <del>
                  {{ originalPrice | formatPrice(precision) }}
                </del>
                <ins>
                  {{ formatPrice(cost, precision) }}
                </ins>
              </div>
            </template>
            <template v-else-if="isReadOnly">
              <div class="readonly_price">
                {{ itemCostReadonlyPrefix }} <span
                v-html="wcPrice(item.readonly_price ? item.readonly_price : cost, {decimals: this.precision})"></span>
              </div>
            </template>
            <template v-else>
              {{ itemCostInputPrefix }} <input type="text" autocomplete="off" placeholder="0" v-model.lazy="costModel"
                                               size="4" v-bind:disabled="!cartEnabled" name="wpo-item-cost-value" ref="cost"
                                               @keydown.tab.prevent="openProductQty">
            </template>
            <div class="cost_with_tax" style="padding: 4px" v-if="showCostWithTax">
              {{ formatPrice(costWithTax, precision) }}
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
                   v-model.number="tmpQtyModel"
                   size="6"
                   class="qty"
                   name="wpo-quantity-value"
                   :class="{'fractional-qty': this.allowToInputFractionalQty}"
                   :disabled="!cartEnabled"
                   :max="item.in_stock"
                   @keydown.tab.prevent="openProductSearchSelect"
                   @keyup.enter="openProductSearchSelect"
                   @blur="changeQty"
                   @mousedown="setFocus"
                   @change="onChangeQtyAndMaybeFormat"
                   style="max-width: 56px; height: 25px;"
            />
          </div>
        </div>
        <div class="block-discount" v-if="showColumnDiscount">
          <div class="item_discount">
            {{ columnDiscountTitle }}:
            <b-form-radio-group
              buttons
              button-variant="outline-primary"
              v-model="itemDiscountType"
              name="discount-type"
              :disabled="!cartEnabled"
              :options="[{html: this.currencySymbol, value: 'fixed'}, {text: '%', value: 'percent'}]"
            >
            </b-form-radio-group>
            <input type="text" autocomplete="off" placeholder="0" v-model.lazy="itemDiscountValue"
                   :disabled="!cartEnabled" class="form-control" style="max-width: 56px; height: 25px; min-width: 56px;">
          </div>
        </div>
      </td>
      <td class="item_extra_col" v-if="showProductsTableExtraColumn" v-html="item.extra_col_value"></td>
      <td class="item_discount" v-if="showColumnDiscount">
        <template v-if="!isGiftItem">
          <div>
            <b-form-radio-group
              buttons
              button-variant="outline-primary"
              v-model="itemDiscountType"
              name="discount-type"
              :disabled="!cartEnabled || !allowDiscount"
              :options="[{html: this.currencySymbol, value: 'fixed'}, {text: '%', value: 'percent'}]"
            >
            </b-form-radio-group>
            <input type="text" autocomplete="off" placeholder="0" v-model.lazy="itemDiscountValue"
                   :disabled="!cartEnabled || !allowDiscount" class="form-control">
          </div>
        </template>
      </td>
      <td class="item_cost">
        <div class="edit wpo-item-cost-value">
          <template v-if="originalPrice">
            <div class="sale_price">
              <del>
                {{ formatPrice(originalPrice, precision) }}
              </del>
              <ins>
                {{ formatPrice(cost, precision) }}
              </ins>
            </div>
          </template>
          <template v-else-if="isReadOnly || isGiftItem">
            <div class="readonly_price">
              {{ itemCostReadonlyPrefix }} <span
              v-html="wcPrice(item.readonly_price ? item.readonly_price : cost, {decimals: this.precision})"></span>
            </div>
          </template>
          <template v-else>
            {{ itemCostInputPrefix }} <input type="text" autocomplete="off" placeholder="0" v-model.lazy="costModel"
                                             size="4" v-bind:disabled="!cartEnabled" name="wpo-item-cost-value" ref="cost"
                                             @keydown.tab.prevent="openProductQty">
          </template>
          <div class="cost_with_tax" style="padding: 4px 0" v-if="showCostWithTax">
            <del v-if="costWithTaxOrig && costWithTax !== cost">{{ formatPrice(costWithTaxOrig, precision) }}</del>
            {{ itemCostIncTaxPrefix }} <span style="background-color:antiquewhite;" v-html="wcPrice(costWithTax, {decimals: this.precision})"></span>
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
                 name="wpo-quantity-value"
                 :step="item.qty_step"
                 :min="minQty"
                 autocomplete="off"
                 placeholder="0"
                 v-model.number="tmpQtyModel"
                 size="6"
                 class="qty"
                 :class="{'fractional-qty': this.allowToInputFractionalQty}"
                 :disabled="!cartEnabled"
                 :max="item.in_stock"
                 @keydown.tab.prevent="openProductSearchSelect"
                 @keyup.enter="openProductSearchSelect"
                 @blur="changeQty"
                 @mousedown="setFocus"
                 @change="onChangeQtyAndMaybeFormat"
          />
        </div>
      </td>
      <td class="line_total">
        <div class="wpo-line-total-value">
          <template v-if="isReadOnly || isGiftItem">
            <div class="total" style="padding: 4px;" v-if="this.item.readonly_line_subtotal_html"
                 v-html="this.item.readonly_line_subtotal_html"></div>
            <div class="total" style="padding: 4px;" v-else v-html="wcPrice(total, {decimals: this.precision})"></div>
          </template>
          <template v-else>
            <input type="text" style="text-align: right;" autocomplete="off" placeholder="0" :value="formatPrice(total, precision)"
                   size="4" name="wpo-item-cost-value" readonly disabled>
          </template>
          <div class="total_with_tax" v-if="showTotalWithTax" style="padding: 4px;"> <span style="background-color:antiquewhite;"
            v-html="wcPrice(totalWithTax, {decimals: this.precision})"></span></div>
        </div>
      </td>
      <td class="wc-order-edit-line-item">
        <div class="wc-order-edit-line-item-actions" v-if="isAllowDelete">
          <a @click.prevent.stop="cartEnabled ? removeItem(item) : null" style="margin-bottom: 0.6rem;" class="delete-order-item tips" href="#"
             :title="deleteProductItemButtonTooltipText"></a>
          <span v-if="totalWithTax && !hideTaxLineProductItem" style="white-space: nowrap; background-color:antiquewhite;">{{costLabel}}</span>
        </div>
      </td>
    </template>
  </tr>
</template>

<style>
.item_cost .readonly_price, .item_cost .sale_price {
  padding: 4px;
}
@media (max-width: 767px) {
  #woocommerce-order-items td.name {
    display: flex;
    flex-direction: column;
  }

  #woocommerce-order-items td.name .block-main {
    order: 1;
  }

  #woocommerce-order-items td.name .block-discount {
    order: 2;
  }

  #woocommerce-order-items td.name .block-rest {
    order: 3;
  }
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

@media (max-width: 767px) {
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

#woocommerce-order-items .woocommerce_order_items_wrapper .woocommerce_order_items td.item_discount > div {
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

#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item_discount .form-control {
  height: 38px;
}

</style>

<script>

import ProductMissingAttribute from './product_missing_attribute.vue';
import ProductCustomMetaFields from './product_custom_meta_fields.vue';
import ProductSubscriptionFields from './product_subscription_fields.vue';

import {library} from '@fortawesome/fontawesome-svg-core';
import {faAlignJustify, faEdit} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon as FaIcon} from '@fortawesome/vue-fontawesome';

library.add(faAlignJustify)
library.add(faEdit)


export default {
  props: {
    item: {
      default: function () {
        return {};
      }
    },
    calculated: {
      default: function () {
        return {};
      }
    },
    deleteProductItemButtonTooltipText: {
      default: function () {
        return 'Delete item';
      }
    },
    skuLabel: {
      default: function () {
        return 'SKU';
      }
    },
    anyLabel: {
      default: function () {
        return 'Choose an option';
      }
    },
    noProductsFoundLabel: {
      default: function () {
        return 'No products found';
      }
    },
    productStockMessage: {
      default: function () {
        return 'Only %s items can be purchased';
      }
    },
    variationIDLabel: {
      default: function () {
        return 'Variation ID';
      }
    },
    variationSKULabel: {
      default: function () {
        return 'Variation SKU';
      }
    },
    itemCostInputPrefix: {
      default: function () {
        return '';
      }
    },
    itemCostReadonlyPrefix: {
      default: function () {
        return '';
      }
    },
    itemCostIncTaxPrefix: {
      default: function () {
        return '';
      }
    },
    productMissingAttributeLabels: {
      default: function () {
        return {};
      }
    },
    productCustomMetaFieldsLabels: {
      default: function () {
        return {};
      }
    },
    showProductsTableExtraColumn: {
      default: function () {
        return false;
      }
    },
    showColumnDiscount: {
      default: function () {
        return false;
      }
    },
    editableCustomMetaFields: {
      default: function () {
        return false;
      }
    },
    productsTableCostColumnTitle: {
      default: function () {
        return 'Cost';
      }
    },
    productsTableQtyColumnTitle: {
      default: function () {
        return 'Qty';
      }
    },
    columnDiscountTitle: {
      default: function () {
        return 'Discount';
      }
    },
    productSubscriptionOptions: {
      default: function () {
        return {};
      }
    },
    weightLabel: {
      default: function () {
        return 'Weight';
      }
    },
    readMoreLabel: {
      default: function () {
        return 'Read more';
      }
    },
    costLabel:{
      default: function () {
        return '';
      }
    },
  },
  data: function () {

    var itemDiscount = typeof this.item.wpo_item_discount !== 'undefined' ? this.item.wpo_item_discount : {
      discount: 0,
      original_price: this.item.item_cost,
      discounted_price: null,
      discount_type: 'fixed'
    };

    let variableSelectedAttributes = {};

    if (this.item.variable_data) {
      if (Object.keys(this.item.variable_data.selected_attributes).length === 0) {
        Object.keys(this.item.variable_data.attributes).forEach(attr => variableSelectedAttributes[attr] = '');
      } else {
        variableSelectedAttributes = this.item.variable_data.selected_attributes
      }
    }

    return {
      cost: this.item.item_cost,
      baseCost: this.item.item_cost,
      baseSku: this.item.sku,
      qty: this.item.qty,
      customName: this.item.custom_name,
      tmpQty: this.item.qty,
      missingVariationAttributes: this.item.missing_variation_attributes,
      variationAttributes: this.item.formatted_variation_data,
      variableAttributes: this.item.variable_data ? this.item.variable_data.attributes : null,
      variableVariations: this.item.variable_data ? this.item.variable_data.variations : null,
      variableAttributeLabels: this.item.variable_data ? this.item.variable_data.attribute_labels : null,
      variableSelectedAttributes: variableSelectedAttributes,
      variableProductId: this.item.variable_data ? this.item.product_id : null,
      customMetaFields: this.item.custom_meta_fields,
      removedCustomMetaFieldsKeys: typeof this.item.removed_custom_meta_fields_keys !== 'undefined' ? this.item.removed_custom_meta_fields_keys : [],
      costUpdatedManually: this.item.cost_updated_manually,
      allowDiscount: this.item.allow_po_discount,

      itemDiscountValue: itemDiscount.discount,
      itemDiscountType: itemDiscount.discount_type,
      itemDiscount: itemDiscount,

      subscriptionFields: typeof this.item.wpo_subscription_fields !== 'undefined' ? this.item.wpo_subscription_fields : null,
      itemRenaming: false,
      originalOptions: {}
    };
  },
  created: function () {
    this.$root.bus.$on('change-missing-attribute', (data) => {
      if (data.itemKey !== this.item.key) {
        return false;
      }

      if (this.missingVariationAttributes && this.missingVariationAttributes.length) {
        var temp_items = [];
        this.missingVariationAttributes.forEach(function (current) {
          temp_items.push(Object.assign({}, current));
        });

        temp_items[data.attributeIndex].value = data.attributeValue;
        this.missingVariationAttributes = temp_items;

        this.updateItem();
      }
    });

    this.$root.bus.$on('changed::tab', this.updateDiscountColumnWidth);
  },
  mounted() {
    this.recalculateTableHeaders();
    this.setVariationOriginalOptions();
    this.updateVariableAttributeOptions();
  },
  beforeUnmount() {
    this.$root.bus.$off('changed::tab', this.updateDiscountColumnWidth);
  },
  watch: {
    cost(newVal) {
      this.updateItem();
    },
    qty(newVal, oldVal) {
      if (newVal !== oldVal
        &&
        // when autoRecalculate is disabled, qty is not updating in store, because calculatedQty is empty
        // add "not autoRecalculate" check
        ((this.calculatedQty && this.calculatedQty !== newVal) || !this.autoRecalculate)
      ) {
        this.updateItem();
      }
    },
    calculatedQty(newVal) {
      if (newVal) {
        this.tmpQty = newVal;
        this.qty = newVal;
      }
    },
    itemDiscountValue(newVal) {
      if (!this.allowDiscount) {
        return;
      }
      this.itemDiscount.discount = newVal;
      this.costUpdatedManually = true;
      this.updateItem();
    },
    itemDiscountType(newVal) {
      if (!this.allowDiscount) {
        return;
      }
      this.itemDiscount.discount_type = newVal;
      if (this.itemDiscountValue) {
        this.costUpdatedManually = true;
        this.updateItem();
      }
    },
    itemName() {
      this.$store.commit('add_order/updateCartItem', {
        key: this.item.key,
        item: Object.assign(this.item,
          {
            custom_name: this.customName,
          },
        ),
      });
    },
  },
  computed: {
    costModel: {
      get() {
        return this.formatPrice(this.cost, this.precision);
      },
      set(newVal) {
        newVal = this.parseNumber(newVal);
        this.costUpdatedManually = true;

        // disable discounts and purge values
        this.allowDiscount = false;
        this.itemDiscountValue = 0;
        this.itemDiscount.discount = 0;
        ////

        return this.cost = newVal;
      },
    },
    suitableVariation() {
      if (!Array.isArray(this.variableVariations)) {
        return null;
      }
      return this.variableVariations.find(variation => Object.keys(this.variableSelectedAttributes).every(attrKey => {
        if (variation.hasOwnProperty('attributes')) {
          return Object.keys(variation.attributes).some((variationAttrName) => {
            if (variationAttrName === attrKey) {
              return (variation.attributes[variationAttrName] === this.variableSelectedAttributes[attrKey] || variation.attributes[variationAttrName] === '')
                && this.variableSelectedAttributes[attrKey] !== '';
            }
          });
        }
      }));
    },
    itemName() {
      return this.customName ? this.customName : this.item.name;
    },
    costWithTaxOrig() {
      return typeof this.calculated.item_cost_with_tax_original !== 'undefined' ? this.calculated.item_cost_with_tax_original : '';
    },
    costWithTax() {
      return typeof this.calculated.item_cost_with_tax !== 'undefined' ? this.calculated.item_cost_with_tax : '';
    },
    calculatedQty() {
      return typeof this.calculated.qty !== 'undefined' ? this.calculated.qty : 0;
    },
    total() {
      return this.item.calc_line_subtotal ? (this.item.readonly_price ? this.item.readonly_price : this.cost) * this.qty : this.calculated.line_subtotal;
    },
    totalWithTax() {
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
    isReadOnlyQty() {
      return typeof this.calculated.is_readonly_qty !== 'undefined' ? this.calculated.is_readonly_qty : (
        typeof this.item.is_readonly_qty !== 'undefined' ? this.item.is_readonly_qty : false
      );
    },
    isReadOnly() {
      return typeof this.calculated.is_readonly_price !== 'undefined' ? this.calculated.is_readonly_price : (
        typeof this.item.is_readonly_price !== 'undefined' ? this.item.is_readonly_price : false
      );
    },
    // price before pricing plugin was applied
    originalPrice() {
      let originalPrice = false;
      if (typeof this.calculated.original_price !== 'undefined') {
        originalPrice = this.calculated.original_price;
        if (originalPrice !== false) {
          this.costUpdatedManually = false;
          this.cost = this.calculated.item_cost;
        }
      } else {
        originalPrice = typeof this.item.original_price !== 'undefined' ? this.item.original_price : false;
      }

      return originalPrice;
    },
    precision() {
      return this.getSettingsOption('item_price_precision');
    },
    autoRecalculate() {
      return this.getSettingsOption('auto_recalculate');
    },
    productKey() {
      return this.item.key ? this.item.key : (this.item.variation_id ? this.item.variation_id : this.item.product_id);
    },
    productLink() {
      return typeof window.wpo_frontend === 'undefined' && this.isDefaultActionProductItemLinkEditProduct ? this.item.product_link : this.item.permalink;
    },
    minQty() {
      let defMinQty = this.getSettingsOption('allow_to_input_fractional_qty') ? '0.01' : '1';
      return typeof this.item.min_qty !== 'undefined' ? this.item.min_qty : defMinQty;
    },
    isDefaultActionProductItemLinkEditProduct() {
      return this.getSettingsOption('action_click_on_title_product_item_in_cart', 'edit_product') === 'edit_product';
    },
    showCostWithTax() {
      return this.costWithTax && !this.hideTaxLineProductItem;
    },
    showTotalWithTax() {
      return this.totalWithTax && !this.hideTaxLineProductItem;
    },
    hideTaxLineProductItem() {
      return this.getSettingsOption('hide_tax_line_product_item');
    },
    currencySymbol() {
      return this.$store.state.add_order.cart.order_currency && this.getSettingsOption('show_order_currency_selector') ? this.$store.state.add_order.cart.order_currency.symbol : this.$store.state.add_order.cart.wc_price_settings.currency_symbol;
    },
    isAllowedToRenameCartItems() {
      return this.getSettingsOption('allow_to_rename_cart_items');
    },
    soldIndividually() {
      return this.item.sold_individually;
    },
    isGiftItem() {
      return this.item.adp && this.item.adp.attr && this.item.adp.attr.indexOf("free") !== -1;
    },
    hideItemMeta() {
      return this.getSettingsOption('hide_item_meta');
    },
    showCartWeight() {
      return this.getSettingsOption('show_cart_weight');
    },
    isShowWeightUnit() {
      return this.$store.state.add_order.cart.wc_measurements_settings.show_weight_unit;
    },
    weightUnit() {
      return this.$store.state.add_order.cart.wc_measurements_settings.weight_unit;
    },
    isAllowDelete() {
      return typeof this.calculated.is_allow_delete !== 'undefined' ? this.calculated.is_allow_delete : (
        typeof this.item.is_allow_delete !== 'undefined' ? this.item.is_allow_delete : true
      );
    },
    isShowProductDescription() {
      return this.getSettingsOption('show_product_description');
    },
    showProductDescriptionPreviewSize() {
      return +this.getSettingsOption('show_product_description_preview_size');
    },
    tmpQtyModel() {
      return this.tmpQty;
    },
    allowToInputFractionalQty() {
      return this.getSettingsOption('allow_to_input_fractional_qty');
    },
  },
  methods: {
    updateItem() {
      this.autoRecalculate && this.$store.commit('add_order/setIsLoadingWithoutBackground', true);
      this.$root.bus.$emit('clear-calculated-item', this.productKey);
      this.setToStoreUpdatedItem();
    },
    setToStoreUpdatedItem() {

      var discountData = {};

      if (this.showColumnDiscount && this.allowDiscount) {
        this.itemDiscount.discounted_price = this.calcDiscount(this.itemDiscount.original_price, this.itemDiscount.discount, this.itemDiscount.discount_type);

        discountData = {
          item_cost: this.itemDiscount.discounted_price.toString()
        };

        if (!!+this.itemDiscount.discount) {
          discountData['wpo_item_discount'] = this.itemDiscount;
        }
      }

      var subscriptionFields = {};

      if (this.subscriptionFields) {
        subscriptionFields = {
          wpo_subscription_fields: this.subscriptionFields,
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
            allow_po_discount: this.allowDiscount,
            key: this.productKey,
            custom_name: this.customName,
            removed_custom_meta_fields_keys: this.removedCustomMetaFieldsKeys,
          },
          discountData,
          subscriptionFields
        ),
      });
    },
    removeItem(item) {
      var delete_items = [item];

      if (typeof item.children !== 'undefined' && item.children) {
        this.$store.state.add_order.cart.items.forEach((_item) => {
          if (_item.wpo_cart_item_key && item.children.indexOf(_item.wpo_cart_item_key) > -1) {
            delete_items.push(_item);
          }
        })
      }

      var removeAdpGifts = JSON.parse(JSON.stringify(this.$store.state.add_order.cart.adp.remove_gifts_from_cart));

      delete_items.forEach((_item) => {
        this.$root.bus.$emit('clear-calculated-item', _item.key);
        this.$root.bus.$emit('clear-selected-item', this.variableProductId || _item.variation_id ? _item.variation_id : _item.product_id);
        this.$store.commit('add_order/removeCartItem', _item.key);

        if (_item.adp && _item.adp.attr && _item.adp.attr.indexOf("free") !== -1) {
          removeAdpGifts.push({
            gift_hash: _item.adp.gift_hash,
            cart_item_key: _item.wpo_cart_item_key,
            product_id: _item.product_id,
            variation_id: _item.variation_id,
            qty: _item.qty,
            variation: _item.variation_data,
            selected: _item.adp.selected_free_cart_item,
            free_cart_item_hash: _item.adp.free_cart_item_hash,
          });
        }
      });

      this.$store.commit('add_order/setAdpRemoveFromCartGifts', removeAdpGifts);
    },
    openProductSearchSelect() {
      this.$root.bus.$emit('open-search-product');
    },
    openProductQty() {
      if (!this.item.sold_individually) {
        this.$refs.qty.focus();
      } else {
        this.openProductSearchSelect()
      }
    },
    changeQty() {
      this.qty = this.tmpQty;
    },
    setFocus(e) {
      e.target.focus();
    },
    onChangeQtyAndMaybeFormat(e) {
      const qty = e.target.value;
      let digitsAfterDec = this.item.qty_step.toString().split('.').length > 1 ? this.item.qty_step.toString().split('.')[1].length : 0;
      this.tmpQty = this.allowToInputFractionalQty ? parseFloat(qty).toFixed(digitsAfterDec) : qty;
    },
    updateCustomMetaFields(data) {

      var tmp = [];

      data.custom_meta_fields.forEach((v) => {
        tmp.push(Object.assign({}, v));
      });

      this.customMetaFields = tmp;
      this.removedCustomMetaFieldsKeys = [...data.removed_custom_meta_fields_keys]
      this.setToStoreUpdatedItem();
    },
    recalculateTableHeaders() {

      var ths = this.$parent.$parent.$refs.woocommerceOrderItems.children[0].children[0].children;
      var tds = this.$el.children;

      for (var i = 2; i <= tds.length - 1; i++) {
        ths[i - 1].style = "width: " + tds[i].offsetWidth + "px;";
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
    toggleRenameCartItem() {
      this.itemRenaming = !this.itemRenaming;
      if (this.itemRenaming) {
        this.$nextTick(() => {
          this.$refs.renameInput.value = this.itemName;
          this.$refs.renameInput.focus();
          this.$refs.renameInput.addEventListener('blur', this.renameCartItem);
        });
      } else {
        this.$refs.renameInput.removeEventListener('blur', this.renameCartItem);
      }
    },
    renameCartItem(event) {
      this.customName = event.target.value;
      this.toggleRenameCartItem();
    },
    showItemDescription() {
      this.$root.bus.$emit('show-product-description', {title: this.itemName, description: this.item.description});
    },
    stripTags(html) {
      var tmp = document.createElement("div");
      tmp.innerHTML = html;
      return tmp.textContent || tmp.innerText || "";
    },
    setVariationOriginalOptions() {
      this.$el.querySelectorAll('.name .wc-order-item-variable-attribute select').forEach(function (select) {
        this.originalOptions[select.dataset.attribute] = select.getHTML();
      }.bind(this));
    },
    updateVariableAttributeOptions() {
      if (!Array.isArray(this.variableVariations)) {
        return;
      }
      this.$el.querySelectorAll('.name .wc-order-item-variable-attribute select').forEach(function (select) {
        var currentSelect = select;
        var currentAttributeName = currentSelect.dataset.attribute;
        var currentSelectedValue = currentSelect.value;

        // Restore the original options
        currentSelect.innerHTML = this.originalOptions[currentAttributeName];

        // Create a set to hold valid options for this attribute
        var validOptions = new Set();

        var availableVariations = this.variableVariations;

        let foundAnyVariation = false;

        // Loop through all variations to find valid options for this attribute
        availableVariations.forEach(function (variation) {
          var isValidOption = true;

          // Check if the variation matches the already selected attributes
          for (var attribute in this.variableSelectedAttributes) {
            if (attribute !== currentAttributeName &&
              this.variableSelectedAttributes[attribute] !== variation.attributes[attribute] &&
              this.variableSelectedAttributes[attribute] !== '' &&
              variation.attributes[attribute] !== ''
            ) {
              isValidOption = false;
            }
          }

          // If the option is valid, add it to the validOptions set
          if (isValidOption) {
            if (variation.attributes[currentAttributeName]) {
              validOptions.add(variation.attributes[currentAttributeName]);
            } else {
              foundAnyVariation = true;
              return;
            }
          }
        }.bind(this));

        if (foundAnyVariation) {
          if (currentSelect.querySelectorAll('option[value="' + currentSelectedValue + '"]').length === 0) {
            currentSelect.value = '';
          } else {
            currentSelect.value = currentSelectedValue;
          }
          return;
        }

        // Remove any invalid options from the select dropdown
        currentSelect.querySelectorAll('option').forEach(function (option) {
          var optionValue = option.value;

          if (optionValue !== '' && !validOptions.has(optionValue)) {
            option.remove();
          }
        });

        // Restore previously selected value if it's still valid, or reset to default
        if (currentSelect.querySelectorAll('option[value="' + currentSelectedValue + '"]').length === 0) {
          currentSelect.value = '';
        } else {
          currentSelect.value = currentSelectedValue;
        }
      }.bind(this));
    },
    updateVariableSelectedAttributes(key, value) {
      if (value) {
        this.variableSelectedAttributes[key] = value;
        this.item.variable_data.selected_attributes = {
          ...this.item.variable_data.selected_attributes,
          [key]: value
        };
      } else {
        delete this.variableSelectedAttributes[key];
        delete this.item.variable_data.selected_attributes[key];
      }
      this.updateVariableAttributeOptions();
      if (this.suitableVariation) {
        this.cost = String(this.suitableVariation.price);
        this.$store.state.add_order.cart.items.forEach((_item) => {
          if (_item.key === this.item.key) {
            _item.variation_id = this.suitableVariation.variation_id;
          }
        })
        this.item.variation_id = this.suitableVariation.variation_id;
        this.item.sku = this.suitableVariation.sku;
      } else {
        this.cost = this.baseCost;
        this.item.variation_id = null;
        this.item.sku = this.baseSku;
      }
    }
  },
  components: {
    ProductMissingAttribute,
    ProductCustomMetaFields,
    ProductSubscriptionFields,
    FaIcon,
  },
  emits: ['update-editable-custom-meta-fields']
}
</script>
