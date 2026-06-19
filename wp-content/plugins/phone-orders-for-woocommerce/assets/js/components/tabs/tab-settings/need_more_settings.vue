<template>
    <tr>
        <td colspan=2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <td></td>
                        <td v-html="content"></td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</template>

<script>
    export default {
        props: {
            content: {
                default: function() {
                    return '';
                }
            },
        }
    }
</script>