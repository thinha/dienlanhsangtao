<template>
  <div class="wpo-config-product-modal">
    <b-modal id="configureProduct"
             ref="modal"
             :title="product ? configureProductLabel : addProductsFromShopLabel"
             :hide-footer="true"
             size="xl"
             @hidden="hidden"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <b-alert show variant="success">
        {{ product ? configureProductNote : addProductsFromShopNote }}
      </b-alert>
      <iframe :src="product ? product.configure_product_page_link : shopPermalinkValue" width="100%"
              height="700"></iframe>
    </b-modal>
  </div>
</template>

<script>

export default {
  props: {
    configureProductLabel: {
      default: function () {
        return 'Configure product';
      }
    },
    addProductsFromShopLabel: {
      default: function () {
        return 'Add products to the cart';
      }
    },
    configureProductNote: {
      default: function () {
        return 'Select the options, add the item to the cart and close this window. Please note that any special prices for the customer will be applied after adding the product to the order and closing this window.';
      }
    },
    addProductsFromShopNote: {
      default: function () {
        return 'Select products and add them to the cart and close this window. Please note that any special prices for the customer will be applied after adding the product to the order and closing this window.'
      }
    },
    shopPermalink: {
      default: function () {
        return '';
      }
    },
  },
  created() {
    this.$root.bus.$on('configure-product-open', (product) => {
      this.product = product;
      this.shopPermalinkValue = this.shopPermalink;
      this.showModal = true;
    });
    window.wpo_close_configure_modal = () => {
      this.close();
    }
  },
  data: function () {
    return {
      product: null,
      shopPermalinkValue: '',
      showModal: false,
    };
  },
  methods: {
    hidden() {
      this.$root.bus.$emit('configure-product-close', this.product);
      this.product = null;
      this.shopPermalinkValue = '';
    },
    close() {
      this.showModal = false;
    }
  },
}
</script>
