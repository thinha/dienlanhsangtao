var Vue = require('vue');

Vue.mixin({
    methods: {
        getAllSettings() {
            return this.$store.getters['settings/getAll'];
        },
        setAllSettings(settings) {
            this.$store.commit('settings/setState', settings);
        },
        getSettingsOption (optionName, defaultValue) {
            var val = this.$store.getters['settings/get'](optionName);
            return typeof val !== 'undefined' ? val : defaultValue;
        },
        setSettingsOption (optionName, optionValue) {
            this.$store.commit('settings/set', {name: optionName, value: optionValue});
        },
    },
});

const state = false;

const getters =  {
    get: function (state) {
        return function (option_name) {
            return state[option_name];
        };
    },
    getAll: function (state) {
        return state;
    },
};

const mutations = {
    set: function (state, option) {
        state[option.name] = option.value;
    },
    setState: function (state, newState) {
        for(var option in newState) {
            this._vm.$set(state, option, newState[option]);
        }
    },
};

module.exports = {
    namespaced: true,
    state,
    getters,
    mutations,
};