<template>
    <tr v-show="shown">
        <td colspan=2>
            <table class="form-table">
                <tbody>
                <tr>
                    <td colspan=2>
                        <b>{{ title }}</b>
                    </td>
                </tr>

                <tr>
                    <td>
                        {{ logShowRecordsDaysLabel }}
                    </td>
                    <td>
                        <input type="number" class="option_number" v-model.number="logShowDays"
                               name="log_show_records_days" min=0>
                    </td>
                </tr>

                <tr>
                    <td>
                        {{ dontClosePopupClickOutsideLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpDontClosePopupClickOutside" name="dont_close_popup_click_outside">
                    </td>
                </tr>

                <tr>
                    <td>
                        {{ collapseWpMenuLabel }}
                    </td>
                    <td>
                        <input type="checkbox" class="option" v-model="tmpCollapseWpMenu" name="collapse_wp_menu">
                    </td>
                </tr>

                <slot name="pro-interface-settings"></slot>

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
                    return 'Interface';
                },
            },
            tabKey: {
                default: function() {
                    return 'interfaceSettings';
                },
            },
            logShowRecordsDaysLabel: {
                default: function () {
                    return 'Show records for last X days in log';
                },
            },
            logShowRecordsDays: {
                default: function () {
                    return 0;
                },
            },
            dontClosePopupClickOutsideLabel: {
                default: function () {
                    return "Don't close popup on click outside";
                },
            },
            dontClosePopupClickOutside: {
                default: function () {
                    return false;
                },
            },
            collapseWpMenuLabel: {
                default: function () {
                    return "Collapse WordPress menu";
                },
            },
            collapseWpMenu: {
                default: function () {
                    return false;
                },
            },
        },
        data() {
            return {
                logShowDays: +this.logShowRecordsDays,
                tmpDontClosePopupClickOutside: this.dontClosePopupClickOutside,
                tmpCollapseWpMenu: this.collapseWpMenu,

                shown: false,
            };
        },
        methods: {
            getSettings() {

                var settings = {
                    log_show_records_days: this.logShowDays,
                    dont_close_popup_click_outside: this.tmpDontClosePopupClickOutside,
                    collapse_wp_menu: this.tmpCollapseWpMenu,
                };

                var childsSettings = {};

                this.$children.forEach(function (child) {
                    if (typeof child.getSettings === 'function') {
                        childsSettings = Object.assign(childsSettings, child.getSettings());
                    }
                });

                return Object.assign(settings, childsSettings);
            },
            getTabsHeaders() {
                return {
                    key: this.tabKey,
                    title: this.title,
                };
            },
            showOption(key) {
                this.shown = this.tabKey === key;
            },
        },
    }
</script>