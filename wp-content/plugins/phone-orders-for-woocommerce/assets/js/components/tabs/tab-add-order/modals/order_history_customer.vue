<template>
  <div>
    <b-modal id="orderHistoryCustomer"
             ref="modal"
             :title="orderHistoryCustomerLabelValue"
             :hide-footer="true"
             size="xl"
             @show="show"
             @hidden="hidden"
             :static="true"
             v-model="showModal"
    >
      <loader v-show="isLoading"></loader>
      <b-table
        striped
        hover
        bordered
        fixed
        show-empty
        :empty-text="orderHistoryCustomerTableEmptyText"
        :busy="isLoading"
        :items="data"
        :fields="orderHistoryCustomerTableHeaders"
        :current-page="currentPage"
        :per-page="perPage"
      >

        <template #cell(total)="data">
          <span v-html="data.value"></span>
        </template>
      </b-table>
      <b-row>
        <b-col md="6" class="my-1">
          <b-pagination v-show="totalRows > perPage" :total-rows="totalRows" :per-page="perPage" v-model="currentPage"
                        class="my-0"/>
        </b-col>
        <b-col md="1" class="my-1"></b-col>
        <b-col md="5" class="my-1">
          <div>
            <b-row v-show="!!Object.keys(summary).length && totalRows > 0">
              <b-col md="4" class="my-1">
                <strong>{{ orderHistoryCustomerTableSummaryLabels.no_transactions }}:</strong>
                {{ summary['no_transactions'] }}
              </b-col>
              <b-col md="4" class="my-1">
                <strong>{{ orderHistoryCustomerTableSummaryLabels.total_paid }}:</strong> <span
                v-html="summary['total_paid']"></span>
              </b-col>
              <b-col md="4" class="my-1">
                <strong>{{ orderHistoryCustomerTableSummaryLabels.total }}:</strong> <span
                v-html="summary['total']"></span>
              </b-col>
            </b-row>
          </div>
        </b-col>
      </b-row>
    </b-modal>
  </div>
</template>

<style>

#orderHistoryCustomer .v-spinner {
  position: absolute;
  top: 70px;
  left: 50%;
}

</style>

<script>

var loader = require('vue-spinner/dist/vue-spinner.min').ClipLoader;

export default {
  props: {
    orderHistoryCustomerLabel: {
      default: function () {
        return 'Order history of';
      }
    },
    orderHistoryCustomerTableHeaders: {
      default: function () {
        return [];
      }
    },
    tabName: {
      default: function () {
        return '';
      }
    },
    orderHistoryCustomerTableEmptyText: {
      default: function () {
        return '';
      }
    },
    orderHistoryCustomerTableSummaryLabels: {
      default: function () {
        return {};
      }
    },
  },
  created() {
    this.$root.bus.$on('order-history-customer-open', (customer) => {
      this.customer = customer;
      this.showModal = true;
    });
  },
  data: function () {
    return {
      customer: null,
      data: [],
      isLoading: false,
      currentPage: 1,
      perPage: 10,
      summary: {},
      showModal: false,
    };
  },
  computed: {
    totalRows() {
      return this.data.length;
    },
    orderHistoryCustomerLabelValue() {
      return this.orderHistoryCustomerLabel + (this.customer ? ' ' + [this.customer.billing_first_name, this.customer.billing_last_name].filter(function (v) {
        return !!v;
      }).join(' ') : '');
    },
  },
  methods: {
    show() {

      this.isLoading = true;

      this.axios.get(this.url, {
        params: {
          action: 'phone-orders-for-woocommerce',
          method: 'get_order_history_customer',
          tab: this.tabName,
          customer_id: this.customer.id,
          nonce: this.nonce,
        }
      }).then((response) => {
        this.data = response.data.data.items;
        this.summary = response.data.data.summary;
        this.isLoading = false;
      }, () => {
        this.isLoading = false;
      });
    },
    hidden() {
      this.customer = null;
      this.data = [];
      this.summary = {};
    },
  },
  components: {
    loader,
  },
}
</script>
