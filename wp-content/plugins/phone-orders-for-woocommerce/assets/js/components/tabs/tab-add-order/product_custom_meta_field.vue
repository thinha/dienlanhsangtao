<template>
  <div class="product-item-meta-field-list-item">
    <div v-show="!editable" class="product-item-meta-field-list-item-static">
      <div>
        <strong>
          {{ meta_key }}:
        </strong>
        {{ meta_value }}
      </div>
      <div class="product-item-meta-field-list-item-static-delete" v-show="enabled">
        <a href="#" @click.prevent="cartEnabled ? deleteMeta(index) : null" :class="{disabled: !cartEnabled}"
           class="meta-delete-btn">
          &times;
        </a>
      </div>
    </div>
    <div v-show="editable" class="product-item-meta-field-list-item-edit">
      <input type="text" v-model.trim.lazy="input_meta_key" :disabled="!cartEnabled"
             :placeholder="customMetaKeyPlaceholder">
      <span class="product-item-meta-field-list-item-edit__equal">=</span>
      <input type="text" v-model.trim.lazy="input_meta_value" :disabled="!cartEnabled"
             :placeholder="customMetaValuePlaceholder">
      <a href="#" @click.prevent="cartEnabled ? deleteMeta(index) : null" :class="{disabled: !cartEnabled}"
         class="meta-delete-btn">
        &times;
      </a>
    </div>
  </div>
</template>

<style>

.product-item-meta-field-list-item {
  margin-bottom: 3px;
}

.product-item-meta-field-list-item-edit {
  margin: 10px 0 15px;
}

#phone-orders-app #woocommerce-order-items .product-item-meta-field-list-item-edit input {
  max-width: 180px;
  width: 48%;
}

.product-item-meta-field-list-item-edit-meta-value {
  margin-top: 5px;
}

#phone-orders-app .product-item-meta-field-list-item .meta-delete-btn {
  color: #ccc;
  font-size: 25px;
  text-decoration: none;
  line-height: 10px;
  vertical-align: middle;
  margin-left: 7px;
}

#phone-orders-app .product-item-meta-field-list-item .meta-delete-btn:hover {
  color: red;
}

#phone-orders-app .product-item-meta-field-list-item .meta-delete-btn.disabled:hover,
#phone-orders-app .product-item-meta-field-list-item .meta-delete-btn.disabled {
  color: #ccc;
}

#phone-orders-app #woocommerce-order-items .product-item-meta-field-list-item input {
  font-size: 13px;
}

@media (max-width: 768px) {

  .product-item-meta-field-list-item-edit__equal {
    display: none;
  }

  #phone-orders-app #woocommerce-order-items .product-item-meta-field-list-item-edit input {
    width: 90%;
    max-width: 100%;
  }
}

@media (min-width: 769px) and (max-width: 1025px) {

  .product-item-meta-field-list-item-edit__equal {
    display: none;
  }

  #phone-orders-app #woocommerce-order-items .product-item-meta-field-list-item-edit input {
    width: 80%;
    max-width: 100%;
  }
}

#phone-orders-app .product-item-meta-field-list-item .product-item-meta-field-list-item-static {
  display: flex;
}

#phone-orders-app .product-item-meta-field-list-item .product-item-meta-field-list-item-static-delete {
  align-self: center;
}

</style>

<script>

export default {
  props: {
    customMetaKeyPlaceholder: {
      default: function () {
        return 'Custom meta field key';
      }
    },
    customMetaValuePlaceholder: {
      default: function () {
        return 'Custom meta field value';
      }
    },
    editable: {
      default: function () {
        return false;
      }
    },
    index: {
      default: function () {
        return -1;
      }
    },
    id: {
      default: function () {
        return '';
      }
    },
    meta_key: {
      default: function () {
        return '';
      }
    },
    meta_value: {
      default: function () {
        return '';
      }
    },
  },
  data: function () {
    return {
      input_meta_key: this.meta_key,
      input_meta_value: this.meta_value,
    };
  },
  watch: {
    input_meta_key() {
      this.updateMeta();
    },
    input_meta_value() {
      this.updateMeta();
    },
  },
  computed: {
    enabled: function () {
      return !this.getSettingsOption('disable_edit_meta');
    },
  },
  methods: {
    updateMeta() {
      this.$emit('update', {
        index: this.index,
        id: this.id,
        meta_key: this.input_meta_key,
        meta_value: this.input_meta_value,
      });
    },
    deleteMeta() {
      this.$emit('delete', {
        index: this.index,
        meta_key: this.input_meta_key,
      });
    },
  },
  emits: ['update', 'delete']
}
</script>
