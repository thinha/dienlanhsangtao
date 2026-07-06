import {createStore} from 'vuex'

import {store as add_order, mixin as add_order_mixin} from './modules/order'
import {store as settings, mixin as settings_mixin} from './modules/settings'

var modules = {
    add_order: add_order,
    settings: settings,
};

try {
    modules = Object.assign(modules, require('./../../../pro_version/assets/js/store').default);
} catch (e) {
}

const store = createStore({
    modules,
})

store.init = function (app) {
    this._modules.root.forEachChild((module) => {
        if (typeof module._rawModule.init === 'function') {
            module._rawModule.init.apply(module.context, [app]);
        }
    })
}

var mixins = [add_order_mixin, settings_mixin];

export {store, mixins}
