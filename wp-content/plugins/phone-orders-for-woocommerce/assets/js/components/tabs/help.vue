<template>
    <div class="phone-orders-woocommerce_tab-help">
        <p>
            <span v-html="note"></span>
        </p>
        <h4 class="custom-header">{{ title }}</h4>
        <ul>
            <li v-for="q in questions">
                <b>{{ q.question }}</b><br>
                <span v-html="q.answer"></span>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: {
            title: {
                default: function() {
                    return 'Short FAQ';
                }
            },
            note: {
                default: function() {
                    return '';
                },
            },
            questions: {
                default: function() {
                    return [];
                }
            }
        }
    }
</script>