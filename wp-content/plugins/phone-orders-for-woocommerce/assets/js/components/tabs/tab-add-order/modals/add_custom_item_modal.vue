<template>
  <div>
    <b-modal id="addCustomItemModal"
             ref="modal"
             :title="addCustomItemLabel"
             size="lg"
             @shown="shown"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <b-form @submit.stop.prevent="submit">
        <div>
          <label class="col-12 col-md-6">{{ lineItemNameLabel }}
            <b-form-input type="text" name="name" v-model.trim="name" ref="name" required
                          v-if="typeFieldName.type === 'input'" autocomplete="off"></b-form-input>
            <b-form-textarea name="name" v-model.trim="name" ref="name" required
                             v-if="typeFieldName.type === 'textarea'"
                             :rows="typeFieldName.meta ? typeFieldName.meta.rows : ''"
                             :cols="typeFieldName.meta ? typeFieldName.meta.cols : ''"></b-form-textarea>
          </label>
          <label class="col-12 col-md-3">{{ pricePerItemLabel }}
            <b-form-input type="text" name="price" v-model.trim="price" ref="price" required></b-form-input>
          </label>
          <label class="col-12 col-md-2">{{ quantityLabel }}
            <b-form-input type="text" name="quantity" v-model.trim="quantity" ref="quantity" required></b-form-input>
          </label>
          <b-button type="submit" v-show="false"></b-button>
        </div>

        <div>
          <label class="col-12 col-md-3" v-if="isShowSKU">{{ skuNameLabel }}
            <b-form-input type="text" name="sku" v-model.trim="sku" ref="sku"></b-form-input>
          </label>
          <label class="col-12 col-md-4" v-if="isShowCategory">{{ categoryLabel }}
            <multiselect
              :allow-empty="false"
              :hide-selected="true"
              :searchable="false"
              :show-labels="false"
              style="width: 100%;max-width: 800px;"
              label="title"
              track-by="id"
              v-model="category"
              :options="categoriesList"
              ref="category"
              :placeholder="selectOptionPlaceholder"
            >
              <template v-slot:singleLabel="props">
                            <span>
                                <span v-html="props.option.title"></span>
                            </span>
              </template>
              <template v-slot:option="props">
                            <span>
                                <span v-html="props.option.title"></span>
                            </span>
              </template>
              <template v-slot:noOptions>
                                <span>
                                    <span v-html="noOptionsTitle"></span>
                                </span>
              </template>
            </multiselect>
          </label>
          <label class="col-12 col-md-4" v-if="isShowTaxClass">{{ taxClassLabel }}
            <multiselect
              :allow-empty="false"
              :hide-selected="true"
              :searchable="false"
              style="width: 100%;max-width: 800px;"
              label="title"
              v-model="taxClass"
              :options="taxClasses"
              track-by="slug"
              :show-labels="false"
              ref="taxClass"
              :placeholder="selectOptionPlaceholder"
            >
              <template v-slot:noOptions>
                                <span>
                                    <span v-html="noOptionsTitle"></span>
                                </span>
              </template>
            </multiselect>
          </label>
        </div>
        <div>
          <label class="col-12 col-md-3 col-lg-2" v-if="isShowWeight">
            {{ weightLabel }}
            <span v-if="isShowWeightUnit">({{ weightUnit }})</span>
            <b-form-input type="text" name="weight" v-model.trim="weight" ref="weight"></b-form-input>
          </label>
          <label class="col-12 col-md-3 col-lg-2" v-if="isShowLength">
            {{ lengthLabel }}
            <span v-if="isShowDimensionsUnit">({{ dimensionsUnit }})</span>
            <b-form-input type="text" name="length" v-model.trim="length" ref="length"></b-form-input>
          </label>
          <label class="col-12 col-md-3 col-lg-2" v-if="isShowWidth">
            {{ widthLabel }}
            <span v-if="isShowDimensionsUnit">({{ dimensionsUnit }})</span>
            <b-form-input type="text" name="width" v-model.trim="width" ref="width"></b-form-input>
          </label>
          <label class="col-12 col-md-3 col-lg-2" v-if="isShowHeight">
            {{ heightLabel }}
            <span v-if="isShowDimensionsUnit">({{ dimensionsUnit }})</span>
            <b-form-input type="text" name="height" v-model.trim="height" ref="height"></b-form-input>
          </label>
        </div>
        <div class="col-12 wpo-add-product-create-woocommerce-product" v-if="isShowCreateWoocommerceProduct">
          <label>
            <input type="checkbox" name="create_woocommerce_product" v-model="elCreateWoocommerceProduct"
                   ref="create_woocommerce_product">
            {{ createWoocommerceProductLabel }}
          </label>
        </div>

      </b-form>

      <template v-slot:footer>
        <div>
          <b-button @click="close">{{ cancelLabel }}</b-button>
          <b-button @click="save" variant="primary" :disabled="!isAllowedSubmit">
            {{ saveLabel }}
          </b-button>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<script>
import Multiselect from 'vue-multiselect';

export default {
  props: {
    cancelLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    saveLabel: {
      default: function () {
        return 'Save';
      }
    },
    addCustomItemLabel: {
      default: function () {
        return 'Add custom product';
      }
    },
    skuNameLabel: {
      default: function () {
        return 'SKU';
      }
    },
    taxClassLabel: {
      default: function () {
        return 'Tax class';
      }
    },
    lineItemNameLabel: {
      default: function () {
        return 'Line item name';
      }
    },
    pricePerItemLabel: {
      default: function () {
        return 'Price per item';
      }
    },
    quantityLabel: {
      default: function () {
        return 'Quantity';
      }
    },
    skuName: {
      default: function () {
        return '';
      }
    },
    lineItemName: {
      default: function () {
        return '';
      }
    },
    pricePerItem: {
      default: function () {
        return '0';
      }
    },
    quantityItems: {
      default: function () {
        return '1';
      }
    },
    itemTaxClasses: {
      default: function () {
        return [];
      }
    },
    categoryLabel: {
      default: function () {
        return 'Category';
      }
    },
    tabName: {
      default: function () {
        return 'add-order';
      }
    },
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
      }
    },
    selectOptionPlaceholder: {
      default: function () {
        return 'Select option';
      }
    },
    weightLabel: {
      default: function () {
        return 'Weight';
      }
    },
    lengthLabel: {
      default: function () {
        return 'Length';
      }
    },
    widthLabel: {
      default: function () {
        return 'Width';
      }
    },
    heightLabel: {
      default: function () {
        return 'Height';
      }
    },
    createWoocommerceProductLabel: {
      default: function () {
        return 'Create product';
      }
    },
    typeFieldName: {
      default: function () {
        return {};
      }
    },
  },
  data: function () {
    return {
      sku: this.sku,
      taxClass: this.getObjectByKeyValue(this.itemTaxClasses, 'slug', this.itemTaxClass),
      taxClasses: this.itemTaxClasses,
      category: null,
      name: this.lineItemName,
      price: this.pricePerItem,
      quantity: this.quantityItems,

      weight: '',
      length: '',
      width: '',
      height: '',

      elCreateWoocommerceProduct: false,

      tab: this.tabName,
      showModal: false,
    };
  },
  computed: {
    isValidName() {
      return !!this.name.length;
    },
    isValidPrice() {
      return parseFloat(this.price) >= 0.0;
    },
    isValidQuantity() {
      return parseInt(this.quantity) > 0;
    },
    isAllowedSubmit() {
      return this.isValidName && this.isValidPrice && this.isValidQuantity;
    },
    isShowSKU() {
      return this.getSettingsOption('new_product_ask_sku');
    },
    isShowTaxClass() {
      return this.getSettingsOption('new_product_ask_tax_class');
    },
    itemTaxClass() {
      return this.getSettingsOption('item_tax_class');
    },
    editProductInNewTabOption() {
      return this.getSettingsOption('edit_created_product_in_new_tab');
    },
    isShowCategory() {
      return this.getSettingsOption('show_product_category');
    },
    categoriesList() {
      return this.$store.state.product_category_tags_filter.categories || [];
    },
    isShowWeight() {
      return this.getSettingsOption('new_product_show_weight');
    },
    isShowLength() {
      return this.getSettingsOption('new_product_show_length');
    },
    isShowWidth() {
      return this.getSettingsOption('new_product_show_width');
    },
    isShowHeight() {
      return this.getSettingsOption('new_product_show_height');
    },
    isShowWeightUnit() {
      return this.$store.state.add_order.cart.wc_measurements_settings.show_weight_unit;
    },
    weightUnit() {
      return this.$store.state.add_order.cart.wc_measurements_settings.weight_unit;
    },
    isShowDimensionsUnit() {
      return this.$store.state.add_order.cart.wc_measurements_settings.show_dimension_unit;
    },
    dimensionsUnit() {
      return this.$store.state.add_order.cart.wc_measurements_settings.dimension_unit;
    },
    isShowCreateWoocommerceProduct() {
      return ['show_checkbox__marked', 'show_checkbox__unmarked'].indexOf(this.getSettingsOption('new_product_create_woocommerce_product', 'dont_show_checkbox__create_product')) > -1;
    },
    createWoocommerceProduct() {
      return ['dont_show_checkbox__create_product', 'show_checkbox__marked'].indexOf(this.getSettingsOption('new_product_create_woocommerce_product', 'dont_show_checkbox__create_product')) > -1;
    },
  },
  methods: {
    close() {
      this.showModal = false;
    },
    shown() {

      this.sku = this.skuName;
      this.taxClass = this.getObjectByKeyValue(this.itemTaxClasses, 'slug', this.itemTaxClass);
      this.category = null;
      this.name = this.lineItemName;
      this.price = this.pricePerItem;
      this.quantity = this.quantityItems;

      this.weight = '';
      this.length = '';
      this.width = '';
      this.height = '';

      this.elCreateWoocommerceProduct = this.createWoocommerceProduct;

      this.$refs.name.focus();
    },
    submit() {

      if (this.isAllowedSubmit) {
        this.save();
        return;
      }

      if (!this.isValidName) {
        this.$refs.name.focus();
        return;
      }

      if (!this.isValidPrice) {
        this.$refs.price.focus();
        return;
      }

      if (!this.isValidQuantity) {
        this.$refs.quantity.focus();
        return;
      }

    },
    save() {
      this.createItem();
      this.close();
    },
    createItem: function () {

      let $args = {
        action: 'phone-orders-for-woocommerce',
        method: 'create_item',
        _wp_http_referer: this.referrer,
        _wpnonce: this.nonce,
        tab: this.tabName,
        data: {
          sku: this.sku,
          tax_class: this.taxClass,
          category: this.category,
          name: this.name,
          price: this.price,
          quantity: this.quantity,

          weight: this.weight,
          length: this.length,
          width: this.width,
          height: this.height,

          create_product: this.elCreateWoocommerceProduct ? 1 : 0,
        },
        nonce: this.nonce,
      };

      this.axios.post(this.url, this.qs.stringify($args)).then((response) => {
        this.addProductItemToStore(response.data.data.item);
        if (this.editProductInNewTabOption) {
          window.open(response.data.data.url);
        }
      });
    },
  },
  components: {
    Multiselect,
  },
}
</script>
