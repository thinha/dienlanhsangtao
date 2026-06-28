<template>
  <div>
    <b-modal id="loadSessions"
             ref="modal"
             :title="loadSessionsModalLabel"
             @shown="shown"
             size="lg"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >

      <b-table
        striped
        hover
        bordered
        fixed
        show-empty
        :items="sessions"
        :fields="loadSessionsTableHeaders"
        :empty-text="loadSessionsTableEmpty"
      >

        <template #cell(customer)="data">
          <span>{{ data.item.customer.name }}</span>
        </template>

        <template #cell(cart_items)="data">
          <div>
            <div v-for="(line, index) in data.item.cart_items" :key="index">
              {{ line }}
            </div>
          </div>
        </template>


        <template #cell(address)="data">
          <span>{{ data.item.address }}</span>
        </template>

        <template #cell(date)="data">
          <span>{{ data.item.date }}</span>
        </template>

        <template #cell(actions)="data">
          <b-button
            size="sm"
            variant="primary"
            @click="loadCart(data.item.key, data.item.customer.id, data.item.customer.type)"
          >
            {{loadSessionsBtnLoadCart}}
          </b-button>
        </template>
      </b-table>


      <template v-slot:footer>
        <div>
          <b-button @click="close">{{ loadSessionsBtnCancelLabel }}</b-button>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<script>

import ProFeatures from '../pro_features.vue';

export default {
  props: {
    loadSessionsTableHeaders: {
      default: function () {
        return [];
      }
    },
    loadSessionsModalLabel: {
      default: function () {
        return 'Active customer sessions at website';
      }
    },
    loadSessionsTableEmpty: {
      default: function () {
        return 'No sessions found';
      }
    },
    loadSessionsBtnLoadCart: {
      default: function () {
        return 'Load cart';
      }
    },
    loadSessionsBtnCancelLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    tabName: {
      default: function () {
        return 'add-order';
      }
    },
  },
  data() {
    return {
      showModal: false,
      sessions: [],
    };
  },

  components: {
    ProFeatures,
  },
  methods: {
    shown(e) {
      this.loadSessions();
    },
    loadSessions() {

      this.axios.get(this.url, {
        params: {
          action: 'phone-orders-for-woocommerce',
          method: 'get_sessions',
          tab: this.tabName,
          nonce: this.nonce,
        }
      }).then((response) => {

        if (!response.data.success) {
          this.sessions = [];
          return;
        }

        this.sessions = response.data.data.sessions;
      }).catch(() => {
      });
    },
    close() {
      this.showModal = false;
    },
    loadCart(sessionKey, id, type) {

      const payload = this.qs.stringify({
        action: 'phone-orders-for-woocommerce',
        method: 'set_customer',
        tab: this.tabName,
        id: id,
        type: type,
        session_key: sessionKey,
        cart: JSON.stringify(this.clearCartParam(this.$store.state.add_order.cart)),
        is_frontend: this.isFrontend ? 1 : 0,
        nonce: this.nonce,
      });

      this.axios.post(this.url, payload)
        .then((response) => {

          if (!response.data.success) {
            console.error("Failed:", response.data.data);
            return;
          }

          this.updateCustomer(response.data.data.customer);

          this.$root.bus.$emit('customer-updated', response.data.data.customer);
          this.$root.bus.$emit('apply-recalculated-cart', response.data.data.cart);

          this.showModal = false;
          this.$store.commit('add_order/setIsLoading', false);
        })
        .catch(() => {
        });
    }

  },
}
</script>
