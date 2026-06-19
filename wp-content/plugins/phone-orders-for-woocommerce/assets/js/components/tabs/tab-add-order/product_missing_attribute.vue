<template>
    <div class="wc-order-item-missing-variation-attribute">
        <strong>
            {{ attribute.label }}:
        </strong>
        <select v-model="attributeValue" :disabled="!cartEnabled">
            <option value="" disabled selected>{{chooseOptionLabel}}</option>
            <option v-for="valueLabel in attribute.values" :value="valueLabel.value">{{valueLabel.label}}</option>
        </select>
    </div>
</template>

<style>
</style>

<script>

    export default {
        props: {
	        attribute: {
                default: function() {
                    return {};
                }
            },
	        index: {
		        default: function() {
			        return 0;
		        }
	        },
            chooseOptionLabel: {
	        	default: function() {
			        return 'Choose an option';
                }
            },
	        itemKey: {
		        default: function() {
			        return "";
		        }
	        },
        },
        data: function () {
            return {
	            attributeValue: this.attribute.value,
            };
        },
        watch: {
	        attributeValue: function(newVal, oldVal) {
		        this.$root.bus.$emit('change-missing-attribute', {
			        attributeIndex: this.index,
			        attributeValue: newVal,
			        itemKey: this.itemKey,
		        });
	        }
        },
    }
</script>