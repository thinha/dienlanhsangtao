<template>
  <div>
    <b-modal id="addCoupon"
             ref="modal"
             :title="addCouponLabel"
             size="sm"
             @shown="shown"
             :no-close-on-backdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <b-form inline>
        <multiselect
          v-model="coupon"
          :options="showAllCouponsInAutocomplete ? selectCouponsList : couponsList"
          id="searchCouponsMultiselect"
          :placeholder="typeToSearchLabel"
          :loading="isLoading"
          :internal-search="showAllCouponsInAutocomplete ? true : false"
          :show-no-results="true"
          @search-change="asyncFind"
          :hide-selected="true"
          :searchable="true"
          open-direction="bottom"
          @select="select"
          ref="selectCoupon"
          :show-labels="false"
        >
          <template v-slot:noResult>
            <span>{{ noResultLabel }}</span>
          </template>
          <template v-slot:singleLabel="props">
                        <span>
                            <span v-html="props.option"></span>
                        </span>
          </template>
          <template v-slot:option="props">
                        <span>
                            <span v-html="props.option"></span>
                        </span>
          </template>
          <template v-slot:noOptions>
                        <span>
                            <span v-html="noOptionsTitle"></span>
                        </span>
          </template>
        </multiselect>
      </b-form>
      <template v-slot:footer>
        <div>
          <b-button @click="close">{{ cancelLabel }}</b-button>
          <b-button @click="apply" variant="primary" :disabled="!coupon">{{ applyLabel }}</b-button>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<script>

import Multiselect from 'vue-multiselect';

export default {
  created: function () {
    this.$root.bus.$on('app-loaded', () => {
      this.showAllCouponsInAutocomplete && this.loadCouponsList();
    });
  },
  props: {
    cancelLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    applyLabel: {
      default: function () {
        return 'Apply';
      }
    },
    addCouponLabel: {
      default: function () {
        return 'Add Coupon';
      }
    },
    typeToSearchLabel: {
      default: function () {
        return 'Type to search';
      }
    },
    tabName: {
      default: function () {
        return 'add-order';
      }
    },
    noResultLabel: {
      default: function () {
        return 'Oops! No elements found. Consider changing the search query.';
      }
    },
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
      }
    },
    multiSelectSearchDelay: {
      default: function () {
        return 1000;
      }
    },
  },
  data: function () {
    return {
      isLoading: false,
      coupon: null,
      couponsList: [],
      lastRequestTimeoutID: null,
      showModal: false,
    };
  },
  computed: {
    excludedCouponList: function () {
      return this.$store.state.add_order.cart.coupons;
    },
    excludedCouponListTitles: function () {

      var coupons = this.excludedCouponList;

      if (!coupons) {
        return [];
      }

      return coupons.map((coupon) => {
        return coupon.title;
      });
    },
    cacheCouponsSessionKey: function () {
      return this.getSettingsOption('cache_coupons_session_key');
    },
    selectCouponsList: function () {
      return this.couponsList.filter((coupon) => {
        return this.excludedCouponListTitles.indexOf(coupon) === -1;
      });
    },
    showAllCouponsInAutocomplete: function () {
      return this.getSettingsOption('show_all_coupons_in_autocomplete');
    },
    customerID: function () {
      return this.$store.state.add_order.cart.customer ? this.$store.state.add_order.cart.customer.id : '';
    },
  },
  watch: {
    showAllCouponsInAutocomplete(newVal) {
      newVal && this.loadCouponsList();
    },
  },
  methods: {
    select(coupon) {
      this.saveToStore(coupon);
      this.close();
    },
    apply() {
      this.saveToStore(this.coupon);
      this.close();
    },
    saveToStore(coupon) {

      if (!coupon) {
        return;
      }

      coupon = coupon.lastIndexOf(" - ") !== -1 ? coupon.substring(0, coupon.lastIndexOf(" - ")) : coupon;

      this.$store.commit('add_order/addCouponItem', {title: coupon});
      this.$store.commit('add_order/addAction', {action: 'add_coupon', coupon: coupon});
    },
    close() {
      this.showModal = false;
    },
    asyncFind(query) {

      if (this.showAllCouponsInAutocomplete) {
        return;
      }

      this.lastRequestTimeoutID && clearTimeout(this.lastRequestTimeoutID);

      if (!query && query !== null) {
        this.isLoading = false;
        this.lastRequestTimeoutID = null;
        return;
      }

      this.isLoading = true;

      this.lastRequestTimeoutID = setTimeout(() => {
        this.axios.get(this.url, {
          params: {
            action: 'phone-orders-for-woocommerce',
            method: 'get_coupons_list',
            term: query,
            customer_id: this.customerID,
            exclude: this.excludedCouponList,
            tab: this.tabName,
            wpo_cache_coupons_key: this.cacheCouponsSessionKey,
            nonce: this.nonce,
          }
        }).then((response) => {

          var couponsList = [];

          for (var id in response.data) {
            if (response.data.hasOwnProperty(id)) {
              couponsList.push(response.data[id].title);
            }
          }

          this.couponsList = couponsList;

          this.isLoading = false;
        });
      }, this.multiSelectSearchDelay);
    },
    loadCouponsList() {

      this.isLoading = true;

      this.axios.get(this.url, {
        params: {
          action: 'phone-orders-for-woocommerce',
          method: 'get_coupons_list',
          tab: this.tabName,
          wpo_cache_coupons_key: this.cacheCouponsSessionKey,
          nonce: this.nonce,
        }
      }).then((response) => {

        var couponsList = [];

        for (var id in response.data) {
          if (response.data.hasOwnProperty(id)) {
            couponsList.push(response.data[id].title);
          }
        }

        this.couponsList = couponsList;

        this.isLoading = false;
      });
    },
    shown() {
      this.coupon = null;

      if (this.showAllCouponsInAutocomplete) {
        return;
      }

      this.couponsList = [];
      this.$refs.selectCoupon.activate();
    },
  },
  components: {
    Multiselect,
  },
}
</script>
