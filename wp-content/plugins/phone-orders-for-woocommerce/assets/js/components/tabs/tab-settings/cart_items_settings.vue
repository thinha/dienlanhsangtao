<template>
  <tr v-show="shown" :data-tab-key="tabKey">
    <td colspan=2>
      <table class="form-table">
        <tbody>
        <tr>
          <td colspan=2>
            <b>{{ title }}</b>
            <a style="display: inline-block; margin-left: 15px" :href="docLink" target="_blank" data-search-ignore>
              {{readDocsTitle}}
            </a>
          </td>
        </tr>

        <tr>
          <td>
            {{ allowToInputFractionalQtyLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpAllowToInputFractionalQty"
                   name="allow_to_input_fractional_qty">
          </td>
        </tr>

        <tr>
          <td>
            {{ scrollableCartContentsLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpScrollableCartContents" name="scrollable_cart_contents">
          </td>
        </tr>

        <tr>
          <td>
            {{ showCartLinkLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpShowCartLink" name="show_cart_link">
            <span class="show_cart_link_note">({{ showCartLinkNote }})</span>
          </td>
        </tr>

        <tr>
          <td>
            {{ showColumnDiscountLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpShowColumnDiscount" name="show_column_discount">
          </td>
        </tr>


        <slot name="pro-cart-items-settings"></slot>

        </tbody>
      </table>
    </td>
  </tr>
</template>

<style>


</style>

<script>

export default {
  props: {
    title: {
      default: function () {
        return 'Cart items';
      },
    },
    tabKey: {
      default: function () {
        return 'cartItemsSettings';
      },
    },
    showCartLinkLabel: {
      default: function () {
        return 'Show button "Copy url to populate cart"';
      },
    },
    showCartLink: {
      default: function () {
        return false;
      },
    },
    readDocsTitle:{
      default: function () {
        return 'Read docs';
      },
    },
    docLink:{
      default: function () {
        return '';
      },
    },
    showCartLinkNote: {
      default: function () {
        return 'warning : this feature is not compatible with discounts';
      },
    },
    scrollableCartContentsLabel: {
      default: function () {
        return 'Scrollable cart contents';
      }
    },
    scrollableCartContents: {
      default: function () {
        return false;
      }
    },
    allowToInputFractionalQtyLabel: {
      default: function () {
        return 'Allow to input fractional qty';
      },
    },
    allowToInputFractionalQty: {
      default: function () {
        return false;
      },
    },
    showColumnDiscountLabel: {
      default: function () {
        return 'Show column "Discount"';
      }
    },
    showColumnDiscount: {
      default: function () {
        return false;
      }
    },
  },
  mounted() {
    this.addSettingsTab(this.getTabsHeaders())
    this.setComponentsSettings(this.componentsSettings)
  },
  data() {
    return {
      tmpShowCartLink: this.showCartLink,
      tmpScrollableCartContents: this.scrollableCartContents,
      tmpAllowToInputFractionalQty: this.allowToInputFractionalQty,
      tmpShowColumnDiscount: this.showColumnDiscount,
    };
  },
  watch: {
    componentsSettings() {
      this.setComponentsSettings(this.componentsSettings)
    },
  },
  computed: {
    shown() {

      if (this.getSearchMode()) {
        return this.getMatchedTabs().includes(this.tabKey);
      }

      return this.getSettingsCurrentTab() === this.tabKey;
    },
    componentsSettings() {
      return this.getSettings();
    },
  },
  methods: {
    getSettings() {

      var settings = {
        show_cart_link: this.tmpShowCartLink,
        scrollable_cart_contents: this.tmpScrollableCartContents,
        allow_to_input_fractional_qty: this.tmpAllowToInputFractionalQty,
        show_column_discount: this.tmpShowColumnDiscount,
      };

      return settings;
    },
    getTabsHeaders() {
      return {
        key: this.tabKey,
        title: this.title,
        menu_order: 60,
      };
    },
    showOption(key) {
      this.shown = this.tabKey === key;
    },
  },
}
</script>
