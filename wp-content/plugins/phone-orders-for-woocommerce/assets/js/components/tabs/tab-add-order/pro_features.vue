<template>
    <div class="pro-features">
        <b>{{ needExtraFeaturesTitle }}</b>
        <a href="https://algolplus.com/plugins/downloads/phone-orders-woocommerce-pro/" target=_blank>
            {{ buyProVersionTitle }}
        </a>
    </div>
</template>

<script>
    export default {
        props: {
            needExtraFeaturesTitle: {
                default: function() {
                    return 'Need extra features?';
                }
            },
            buyProVersionTitle: {
                default: function() {
                    return 'Buy Pro version';
                }
            },
        },
    }
</script>