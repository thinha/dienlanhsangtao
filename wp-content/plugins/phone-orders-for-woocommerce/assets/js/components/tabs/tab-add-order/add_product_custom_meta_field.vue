<template>
    <div class="add-product-item-meta-field-list-item">
        <div>
            <input
                ref="input_meta_key"
                type="text"
                v-model.trim="input_meta_key"
                :disabled="!cartEnabled"
                :placeholder="customMetaKeyPlaceholder"
                @keyup.enter="$refs.input_meta_value.focus()"
            >
            <span class="add-product-item-meta-field-list-item__equal">=</span>
            <input
                ref="input_meta_value"
                type="text"
                v-model.trim="input_meta_value"
                :disabled="!cartEnabled"
                :placeholder="customMetaValuePlaceholder"
                @keyup.enter="cartEnabled ? addMeta() : null"
            >
            <a href="#" @click.prevent="cartEnabled ? addMeta() : null" :class="{disabled: !cartEnabled}" class="meta-add-btn">
                &#43;
            </a>
            <div class="add-product-item-meta-field-list-item__meta-key__tags">
                <a href="#" @click.prevent="cartEnabled ? clickTag(key) : null" v-for="key in defaultMetaKeys" :title="key" :disabled="!cartEnabled">
                    {{ key.length > 10 ? key.slice(0, 10) + '...' : key }}
                </a>
            </div>
        </div>
        <div class="add-product-item-meta-field-list-item__buttons">
            <button class="btn btn-secondary cancel-edit-btn" @click="cartEnabled ? cancelEditMeta() : null" :disabled="!cartEnabled">
                {{ cancelEditMetaLabel }}
            </button>
        </div>
    </div>
</template>

<style>

    .add-product-item-meta-field-list-item {
        margin-bottom: 3px;
    }

    #phone-orders-app #woocommerce-order-items .add-product-item-meta-field-list-item input {
        font-size: 13px;
        width: 48%;
        max-width: 180px;
    }

    .add-product-item-meta-field-list-item__meta-key__tags {
        margin-top: 4px;
        width: 100%;
        max-width: 375px;
    }

    .add-product-item-meta-field-list-item__buttons {
        margin-top: 10px;
    }

    #phone-orders-app .add-product-item-meta-field-list-item .meta-add-btn {
        color: #ccc;
        font-size: 25px;
        text-decoration: none;
        line-height: 10px;
        vertical-align: middle;
        margin-left: 7px;
    }

    #phone-orders-app .add-product-item-meta-field-list-item .meta-add-btn:hover {
        color: #007bff;
    }

    #phone-orders-app .add-product-item-meta-field-list-item .meta-add-btn.disabled:hover,
    #phone-orders-app .add-product-item-meta-field-list-item .meta-add-btn.disabled {
        color: #ccc;
    }

    @media (max-width:768px){

        .add-product-item-meta-field-list-item__equal {
            display: none;
        }

        #phone-orders-app #woocommerce-order-items .add-product-item-meta-field-list-item input {
            width: 90%;
            max-width: 100%;
        }
    }

    @media (min-width:769px) and (max-width:1024px){

        .add-product-item-meta-field-list-item__equal {
            display: none;
        }

        #phone-orders-app #woocommerce-order-items .add-product-item-meta-field-list-item input {
            width: 80%;
            max-width: 100%;
        }
    }
</style>

<script>

    import _ from 'lodash';

    export default {
        props: {
            chooseOptionLabel: {
                default: function() {
                    return 'Choose an option';
                }
            },
            customMetaKeyPlaceholder: {
                default: function() {
                    return 'Custom meta field key';
                }
            },
            customMetaValuePlaceholder: {
                default: function() {
                    return 'Custom meta field value';
                }
            },
            addMetaLabel: {
                default: function() {
                    return 'Add meta';
                }
            },
            cancelEditMetaLabel: {
                default: function() {
                    return 'Collapse edit meta';
                }
            },
            enabled: {
                default: function() {
                    return false;
                }
            },
        },
        data: function () {
            return {
                input_meta_key: '',
                input_meta_value: '',
            };
        },
        computed: {
            defaultMetaKeys () {
                return _.uniq([
                    ...this.getItemCustomMetaFieldsList(this.getSettingsOption('item_custom_meta_fields')),
                    ...this.getDefaultListItemCustomMetaFieldsList(this.getSettingsOption('default_list_item_custom_meta_fields')),
                ]);
            },
        },
        watch: {
            enabled() {
                this.enabled && this.init();
            },
        },
        methods: {
            init() {
                this.input_meta_key   = '';
                this.input_meta_value = '';
                this.$refs.input_meta_key.focus();
            },
            addMeta () {
                this.$emit('add', {
                    meta_key: this.input_meta_key,
                    meta_value: this.input_meta_value,
                });
                this.init();
            },
            cancelEditMeta() {
                this.$emit('cancel');
            },
            clickTag(val) {
                this.input_meta_key = val;
                this.$refs.input_meta_value.focus();
            },
        },
    }
</script>