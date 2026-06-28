<template>
  <div class="wpo-choose-gifts-modal">
    <b-modal id="chooseGifts"
             ref="modal"
             :title="chooseGiftsLabel"
             size="xl"
             @hidden="hidden"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <div class="wpo-gifts-list">
        <div v-if="isLoading">
          <loader></loader>
        </div>
        <div v-else>
          <div v-for="product in products" class="wpo-gifts-list__product">
            <label>
			    <span class="wpo-gifts-list__product__item">
				<span class="wpo-gifts-list__product__item__checkbox">
				    <input type="checkbox" v-model="selectedProducts" :value="product.id"
                   :disabled="countGiftsLeft <= 0 && selectedProducts.indexOf(product.id) === -1">
				</span>
				<img class="wpo-gifts-list__product__item__image" :src="product.img" alt="" v-show="!!product.img" width="100">
				<span class="wpo-gifts-list__product__item__desc">
				    <span class="wpo-gifts-list__product__item__title" v-html="product.title"></span>
				</span>
				<div class="wc-order-item-missing-variation-attribute"
             v-for="(variation_attribute, index) in product.missing_variation_attributes"
             :key="variation_attribute.key">
				    <strong>
					{{ variation_attribute.label }}:
				    </strong>
				    <select v-model="variation_attribute.value" :disabled="!cartEnabled">
					<option value="" disabled selected>{{ productMissingAttributeLabels.chooseOptionLabel }}</option>
					<option v-for="valueLabel in variation_attribute.values"
                  :value="valueLabel.value">{{ valueLabel.label }}</option>
				    </select>
				</div>
			    </span>
            </label>
          </div>
        </div>
      </div>
      <template v-slot:footer>
        <div style="width: 100%">
          <b-row>
            <b-col cols="6" md="3">
              {{ countGiftsLeft }} {{ giftsLeftLabel }}
            </b-col>
            <b-col cols="6" md="9" style="text-align: right">
              <b-button @click="addToCart" variant="primary" :disabled="!!!selectedProducts.length">{{
                  addToCartLabel
                }}
              </b-button>
              <b-button @click="close">{{ closeLabel }}</b-button>
            </b-col>
          </b-row>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<style>
#chooseGifts .modal-content {
  min-height: 700px;
}

#chooseGifts .modal-body {
  overflow: auto;
  max-height: 570px;
}

#chooseGifts .wpo-gifts-list__product {
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

#chooseGifts .wpo-gifts-list__product:hover {
  background: #5897fb;
  color: #fff;
}

#chooseGifts .wpo-gifts-list__product__item {
  padding: 18px;
  display: block;
  min-height: 250px;
}

#chooseGifts .wpo-gifts-list__product__item__checkbox {
  position: absolute;
  right: 14px;
  top: 16px;
}

#chooseGifts .wpo-gifts-list__product__item__image {
  width: 100%;
}

#chooseGifts .wpo-gifts-list__product__item__desc {
  display: block;
  margin-top: 15px;
}

#chooseGifts .wc-order-item-missing-variation-attribute {
  margin-top: 5px;
}

#chooseGifts .wc-order-item-missing-variation-attribute select {
  max-width: 100px;
}

@media (max-width: 1700px) {
  #chooseGifts .wpo-gifts-list__product {
    margin-left: 6px;
    margin-right: 6px;
  }
}
</style>

<script>

var loader = require('vue-spinner/dist/vue-spinner.min').ClipLoader;

export default {
  props: {
    chooseGiftsLabel: {
      default: function () {
        return 'Choose gifts';
      }
    },
    addToCartLabel: {
      default: function () {
        return 'Add to cart';
      }
    },
    closeLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    giftsLeftLabel: {
      default: function () {
        return 'gifts left';
      }
    },
    tabName: {
      default: function () {
        return 'add-order';
      },
    },
    productMissingAttributeLabels: {
      default: function () {
        return {};
      },
    },
  },
  created() {
    this.$root.bus.$on('choose-gifts-open', (data) => {
      this.giftHash = data.hash;
      this.requiredQty = data.qty;
      this.loadProducts();
      this.showModal = true;
    });
  },
  data: function () {
    return {
      isLoading: true,
      giftHash: null,
      requiredQty: null,
      products: [],
      selectedProducts: [],
      showModal: false,
    };
  },
  computed: {
    countSelected() {
      return this.selectedProducts.length;
    },
    countGiftsLeft() {
      return +this.requiredQty - this.countSelected;
    },
  },
  methods: {
    loadProducts() {

      this.isLoading = true;

      this.axios.post(this.url, this.qs.stringify({
        action: 'phone-orders-for-woocommerce',
        method: 'load_gifts_products',
        tab: this.tabName,
        gift_hash: this.giftHash,
        customer_id: this.$store.state.add_order.cart.customer.id,
        cart: JSON.stringify(this.$store.state.add_order.cart),
        nonce: this.nonce,
      })).then((response) => {

        this.products = [];

        var data = response.data;

        if (data && Array.isArray(data)) {
          data.forEach((product) => {
            this.products.push({
              id: product.product_id,
              title: product.title,
              img: product.img,
              variation_id: product.variation_id,
              variation_data: product.variation_data,
              missing_variation_attributes: product.missing_variation_attributes,
            });
          });
        }

        this.isLoading = false;

      }, () => {
      });
    },
    addToCart() {

      var selectedProducts = [];

      this.selectedProducts.forEach((i) => {

        var product = this.getObjectByKeyValue(this.products, 'id', i);

        var variation_data = Object.assign({}, product.variation_data);

        if (product.missing_variation_attributes) {
          product.missing_variation_attributes.forEach((attribute) => {
            variation_data['attribute_' + attribute.key] = attribute.value;
          });
        }

        selectedProducts.push({
          id: i,
          qty: 1,
          gift_hash: this.giftHash,
          data: {
            variation_id: product.variation_id,
            variation_data: variation_data,
          }
        });
      });

      this.$root.bus.$emit('choose-gifts-close', selectedProducts);
      this.close();
    },
    close() {
      this.showModal = false;
    },
    hidden() {
      this.giftHash = null;
      this.products = [];
      this.selectedProducts = [];
      this.isLoading = true;
    },
  },
  components: {
    loader,
  },
}
</script>
