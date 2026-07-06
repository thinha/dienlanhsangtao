<template>
  <div>
    <b-modal id="productHistoryCustomer"
             ref="modal"
             :title="productHistoryTitle"
             size="lg"
             @show="show"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
             :hide-header-close="true"
    >
      <div class="wpo-search-wrapper">
        <b-form-input size="sm" :placeholder="searchPlaceholder" v-model="querySearch"></b-form-input>
      </div>
      <div class="wpo-loader" v-if="isLoading">
        <loader></loader>
      </div>
      <div v-else-if="filteredProductList.length > 0" class="wpo-product-history-list-wrapper">
        <div class="wpo-product-history-list" :class="{'wpo-grid': displayAsGrid}">
          <div v-for="product in filteredProductList" class="wpo-product-history-list__product"
               @click="selectedProductList[product.product_id]['checked'] = !selectedProductList[product.product_id]['checked']">
                        <span class="wpo-product-history-list__product__item"
                              @click="isAllowConfigureProduct && useConfigureProductActionAsDefault ? configureProduct(product) : null">
                            <span class="wpo-product-history-list__product__item__checkbox">
                                <input type="checkbox" v-model="selectedProductList[product.product_id]['checked']">
                            </span>
                            <img class="wpo-product-history-list__product__item__image" :src="product.img" alt=""
                                 v-show="!!product.img"
                                 width="100">
                            <span class="wpo-product-history-list__product__item__desc">
                                <span v-if="defaultActionClickOnTitle === 'edit_product'">
                                    <a :href="product.product_link" target="_blank" @mousedown.stop @click.stop>
                                        <span class="wpo-product-history-list__product__item__desc__title"
                                              v-html="product.title"></span>
                                    </a>
                                </span>
                                <span v-else-if="defaultActionClickOnTitle === 'view_product'">
                                    <a :href="product.permalink" target="_blank" @mousedown.stop @click.stop>
                                        <span class="wpo-product-history-list__product__item__desc__title"
                                              v-html="product.title"></span>
                                    </a>
                                </span>
                                <span v-else>
                                    <span class="wpo-product-history-list__product__item__desc__title"
                                          v-html="product.title"></span>
                                </span>
                            </span>
                            <span class="wpo-product-history-list__product__item__input-price" v-if="isShowPriceInput"
                                  @click.stop>
                                <b-form-input size="sm" autocomplete="off" placeholder="0"
                                              v-model="selectedProductList[product.product_id]['item_cost']"
                                              v-bind:disabled="!cartEnabled"
                                              @mousedown="selectedProductList[product.product_id]['checked'] = true"
                                              class="price"
                                              @input="selectedProductList[product.product_id]['cost_updated_manually'] = true"></b-form-input>
                            </span>
                            <span class="wpo-product-history-list__product__item__input-qty" v-if="isShowQtyInput"
                                  @click.stop>
                                <b-form-input
                                  size="sm"
                                  type="number"
                                  :step="product.qty_step"
                                  :min="product.min_qty"
                                  autocomplete="off"
                                  placeholder="0"
                                  v-model.number="selectedProductList[product.product_id]['qty']"
                                  class="qty"
                                  :disabled="!cartEnabled"
                                  :max="product.in_stock"
                                  @mousedown="selectedProductList[product.product_id]['checked'] = true"
                                >
                                </b-form-input>
                            </span>
                            <button class="btn btn-light wpo-btn-configure-product" @click="configureProduct(product)"
                                    v-show="isAllowConfigureProduct && !useConfigureProductActionAsDefault">
                                {{ buttonConfigureLabel }}
                            </button>
                        </span>
          </div>
        </div>
      </div>
      <div v-else>
        {{ noResultLabel }}
      </div>
      <template v-slot:footer>
        <div class="wpo-actions-wrapper">
          <div>
            <strong>{{ selectedProductsCountLabel }}:</strong>
            {{ selectedProductsCount }}
          </div>
          <div>
            <b-button @click="addToCart" variant="primary" size="sm">{{ addToCartLabel }}</b-button>
            <b-button @click="cancel" variant="light" size="sm">{{ cancelLabel }}</b-button>
          </div>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<style>
#productHistoryCustomer .modal-content {
  height: calc(100vh - 80px);
  font-size: 14px;
  color: #35495e;
}

#productHistoryCustomer .wpo-loader {
  position: absolute;
  text-align: center;
  top: 50%;
  width: 100%;
}

#productHistoryCustomer .wpo-search-wrapper {
  margin-bottom: 10px;
}

#productHistoryCustomer .wpo-actions-wrapper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

#productHistoryCustomer .wpo-actions-wrapper .btn {
  font-size: 13px;
}

#productHistoryCustomer .modal-footer {
  border-top: none;
}

#productHistoryCustomer .wpo-grid .wpo-product-history-list__product {
  display: inline-block;
  position: relative;
  width: 100%;
  margin-top: 10px;
  border: solid 3px #eee;
  margin-left: 5px;
  margin-right: 5px;
  vertical-align: top;
  text-align: center;
  border-radius: 8px;
  box-shadow: 0 0 10px #eee;
  max-width: 140px;
}

#productHistoryCustomer .wpo-product-history-list__product {
  cursor: pointer;
}

#productHistoryCustomer .wpo-product-history-list__product:hover {
  background: #5897fb;
  color: #fff;
}

#productHistoryCustomer .wpo-grid .wpo-product-history-list__product__item {
  padding: 18px;
  display: block;
  min-height: 250px;
}

#productHistoryCustomer .wpo-grid .wpo-product-history-list__product__item__checkbox {
  position: absolute;
  right: 14px;
  top: 16px;
}

#productHistoryCustomer .wpo-grid .wpo-product-history-list__product__item__image {
  width: 100%;
}

#productHistoryCustomer .wpo-grid .wpo-product-history-list__product__item__desc {
  display: block;
  margin-top: 15px;
}

#productHistoryCustomer .wpo-grid .wpo-product-history-list__product__item__input-price {
  display: block;
  margin-top: 15px;
  margin-right: 0;
}

#productHistoryCustomer .wpo-product-history-list__product__item__input-price .price {
  hegiht: 25px;
  width: 80px;
  text-align: center;
}

#productHistoryCustomer .wpo-grid .wpo-product-history-list__product__item__input-qty {
  display: block;
  margin-top: 15px;
}

#productHistoryCustomer .wpo-product-history-list__product__item__input-qty .qty {
  hegiht: 25px;
  width: 80px;
  text-align: center;
}

#productHistoryCustomer .wpo-product-history-list__product__item__desc {
  flex-grow: 1;
  padding-left: 10px;
}

#productHistoryCustomer .wpo-product-history-list__product__item__image {
  padding-left: 10px;
}

#productHistoryCustomer .wpo-product-history-list__product__item {
  padding: 7px;
  margin-bottom: 6px;
  display: flex;
  flex-flow: row;
  align-items: center;
}

#productHistoryCustomer .wpo-grid .wpo-product-history-list__product__item {
  display: flex;
  flex-flow: column;
}

#productHistoryCustomer .wpo-product-history-list__product__item__input-price {
  margin-right: 10px;
}

#productHistoryCustomer .modal-body {
  max-height: 100%;
  overflow: hidden;
}

#productHistoryCustomer .wpo-product-history-list-wrapper {
  max-height: 100%;
  overflow: scroll;
  height: 100%;
}

@media (max-width: 1700px) {
  #productHistoryCustomer .wpo-grid .wpo-product-history-list__product {
    margin-left: 6px;
    margin-right: 6px;
  }
}
</style>

<script>

var loader = require('vue-spinner/dist/vue-spinner.min').ClipLoader;

export default {
  props: {
    productHistoryTitle: {
      default: function () {
        return 'Products history';
      }
    },
    cancelLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    addToCartLabel: {
      default: function () {
        return 'Add to cart';
      }
    },
    tabName: {
      default: function () {
        return 'add-order';
      }
    },
    noResultLabel: {
      default: function () {
        return 'No products found';
      }
    },
    selectedProductsCountLabel: {
      default: function () {
        return 'Selected';
      }
    },
    searchPlaceholder: {
      default: function () {
        return 'Find products ...';
      }
    },
    useConfigureProductActionAsDefault: {
      default: function () {
        return false;
      }
    },
    buttonConfigureLabel: {
      default: function () {
        return 'Configure product';
      }
    },
    useDefaultQty: {
      default: function () {
        return 1;
      }
    },
  },
  data() {
    return {
      isLoading: false,
      showModal: false,
      productList: [],
      selectedProductList: [],
      querySearch: '',
    };
  },
  computed: {
    selectedProductsCount() {
      return this.selectedProductList.filter((item) => item.checked).length
    },
    isAllowConfigureProduct() {
      return this.isFrontend && this.getSettingsOption('allow_to_configure_product');
    },
    isFrontend() {
      return typeof window.wpo_frontend !== 'undefined';
    },
    defaultActionClickOnTitle() {
      return this.getSettingsOption('action_click_on_title_product_item_in_search_products', 'add_product_to_cart');
    },
    isShowPriceInput() {
      return this.getSettingsOption('show_price_input_advanced_search');
    },
    isShowQtyInput() {
      return this.getSettingsOption('show_qty_input_advanced_search');
    },
    filteredProductList() {
      return this.productList.filter((item) => item.title.match(new RegExp(this.querySearch, 'gi')))
    },
    displayAsGrid() {
      return this.getSettingsOption('display_search_result_as_grid');
    },
  },
  watch: {
    filteredProductList() {
      this.selectedProductList = [];
      this.filteredProductList.forEach((product) => {
        this.selectedProductList[product.product_id] = {
          product: product,
          checked: false,
          item_cost: product.item_cost,
          cost_updated_manually: false,
          qty: this.useDefaultQty,
        }
      })
    },
  },
  methods: {
    addToCart() {
      var items = [];
      for (var product_id in this.selectedProductList) {
        if (this.selectedProductList[product_id].checked) {
          var item = this.selectedProductList[product_id]
          items.push({
            product: {...item.product, ...{value: item.product.product_id}},
            qty: item.qty,
            item_cost: this.isShowPriceInput && item.cost_updated_manually ? item.item_cost : null
          })
        }
      }
      this.$root.bus.$emit('add-to-cart', items);
      this.cancel()
    },
    cancel() {
      this.showModal = false;
    },
    loadProductList() {
      this.isLoading = true;
      this.productList = [];
      this.axios.post(this.url, this.qs.stringify({
        action: 'phone-orders-for-woocommerce',
        method: 'get_product_history_customer_list',
        tab: this.tabName,
        customer_id: this.$store.state.add_order.cart.customer.id,
        cart: JSON.stringify(this.$store.state.add_order.cart),
        nonce: this.nonce,
      })).then((response) => {
        this.productList = response.data;
        this.isLoading = false;
      });
    },
    show() {
      this.querySearch = ''
      this.loadProductList()
      this.setModalWidth()
    },
    configureProduct(product) {
      this.$root.bus.$emit('configure-product-open', product)
    },
    setModalWidth() {
      this.$nextTick(() => {
        document.querySelector('#productHistoryCustomer .modal-dialog').style.maxWidth = document.getElementById('woo-phone-orders').offsetWidth + "px";
      })
    },
  },
  components: {
    loader,
  },
}
</script>
