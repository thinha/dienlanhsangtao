var Vue  = require('vue');
var Vuex = require('vuex');

var modules = {
    add_order: require('./modules/order'),
    settings: require('./modules/settings'),
};

Vue.use(Vuex);

try {
    modules = Object.assign(modules, require( './../../../pro_version/assets/js/store' ));
} catch (e) {}

var store = new Vuex.Store({
    modules,
});

store.init = function (app) {
    this._modules.root.forEachChild((module) => {
	if (typeof module._rawModule.init === 'function') {
	    module._rawModule.init.apply(module.context, [app]);
	}
    })
}

module.exports = store