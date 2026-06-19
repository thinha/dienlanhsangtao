<template>
    <button class="btn btn-secondary copy-cart-link-btn" v-show="isShowButton" @click="copyCart" :disabled="!cartEnabled">
        {{ buttonLabel }}
    </button>
</template>

<style>
    .copy-cart-link-btn {
        text-align: center;
    }
</style>

<script>
    export default {
        props: {
            defaultButtonLabel: {
                default: function() {
                    return 'Copy url to populate cart';
                }
            },
            copiedButtonLabel: {
                default: function() {
                    return 'Url has been copied to clipboard';
                }
            },
        },
        data () {
            return {
                buttonLabel: this.defaultButtonLabel,
            };
        },
        computed: {
            isShowButton: function () {
                return !!this.$store.state.add_order.cart.items.length && this.getSettingsOption('show_cart_link');
            },
            url() {

                var items = this.$store.state.add_order.cart.items;

                return this.base_cart_url + '/?wpo_fill_cart=' +
                            items.map((i) => {
                                return (+i.qty > 1 ? +i.qty + 'x' : '') + (i.variation_id || i.product_id);
                            }).join(',');
            }
        },
        methods: {
            copyCart () {
                this.$copyText(this.url).then((e) => {
                    this.buttonLabel = this.copiedButtonLabel;
                    setTimeout(() => {
                        this.buttonLabel = this.defaultButtonLabel;
                    }, 2000);
                }, (e) => {});
            },
        },
    }
</script>