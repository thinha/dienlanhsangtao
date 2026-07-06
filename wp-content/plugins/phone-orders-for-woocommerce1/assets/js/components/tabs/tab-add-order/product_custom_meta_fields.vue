<template>
  <div class="product-item-meta-field-list">

    <product-custom-meta-field v-for="(field, i) in this.fieldList"
                               v-bind="productCustomMetaFieldLabels"
                               :editable="editable"
                               :key="field.meta_key + '_' + field.meta_value + '_' + i"
                               :id="field.id"
                               :meta_key="field.meta_key"
                               :meta_value="field.meta_value"
                               :index="i"
                               @update="updateItemFieldList"
                               @delete="deleteItemFieldList"
    ></product-custom-meta-field>

    <template v-if="enabled">
      <div v-show="editable">
        <add-product-custom-meta-field
          v-bind="productCustomMetaFieldLabels"
          :enabled="editable"
          @add="addItemFieldList"
          @cancel="cancelEditMeta"
        ></add-product-custom-meta-field>
      </div>
      <a href="#" @click.prevent="cartEnabled ? editMeta() : null" :class="{disabled: !cartEnabled}" v-show="!editable">
        {{ editMetaLabel }}
      </a>
    </template>
  </div>
</template>

<style>
.product-item-meta-field-list {
  margin-top: 10px;
}
</style>
<script>

import ProductCustomMetaField from './product_custom_meta_field.vue';
import AddProductCustomMetaField from './add_product_custom_meta_field.vue';

export default {
  created() {

    var func = (data) => {
      this.cancelEditMeta();
    }

    this.$root.bus.$on('create-order', func);
    this.$root.bus.$on('edit-order', func);
    this.$root.bus.$on('update-order', func);
    this.$root.bus.$on('cancel-update-order', func);
  },
  props: {
    productCustomMetaFieldLabels: {
      default: function () {
        return {};
      }
    },
    fields: {
      default: function () {
        return [];
      }
    },
    editMetaLabel: {
      default: function () {
        return 'Edit meta';
      }
    },
    editableFields: {
      default: function () {
        return false;
      }
    },
    removedFields: {
      default: function () {
        return [];
      }
    },
  },
  data: function () {
    return {
      fieldList: [...this.fields],
      editable: this.editableFields,
      removedFieldListKeys: [...this.removedFields],
    };
  },
  watch: {
    fieldList(oldVal, newVal) {
      this.onUpdate();
    },
    editable(newVal) {
      this.$emit('update-editable-fields', {editable: newVal});
    },
  },
  computed: {
    enabled: function () {
      return !this.getSettingsOption('disable_edit_meta');
    },
  },
  methods: {
    onUpdate() {
      this.$emit('update', {
        custom_meta_fields: this.fieldList,
        removed_custom_meta_fields_keys: this.removedFieldListKeys,
      });
    },
    addItemFieldList(data) {
      this.fieldList.push({
        id: '',
        meta_key: data.meta_key,
        meta_value: data.meta_value,
      });
      this.fieldList = [...this.fieldList];
    },
    updateItemFieldList(data) {
      this.fieldList[data.index] = {
        id: data.id,
        meta_key: data.meta_key,
        meta_value: data.meta_value,
      };
      this.fieldList = [...this.fieldList];
    },
    deleteItemFieldList(data) {
      this.fieldList.splice(data.index, 1);
      this.fieldList = [...this.fieldList];
      this.removedFieldListKeys.push(data.meta_key)
    },
    editMeta() {
      this.editable = true;
    },
    cancelEditMeta() {
      this.editable = false;
    },
  },
  components: {
    ProductCustomMetaField,
    AddProductCustomMetaField,
  },
  emits: ['update-editable-fields', 'update']
}
</script>
