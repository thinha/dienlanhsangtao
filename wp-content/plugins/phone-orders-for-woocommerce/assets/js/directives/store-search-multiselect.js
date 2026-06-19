var directive = {
    inserted: function (el, binding, vnode) {

	var deactivate = vnode.componentInstance.deactivate;

	vnode.componentInstance.deactivate = function(e) {
	    if ( ! this.search.length ) {
		deactivate();
	    }
	}

	vnode.componentInstance.$on('open', function(data) {
	    vnode.componentInstance.$root.bus.$emit('open-multiselect', data);
	});

	vnode.componentInstance.$root.bus.$on('open-multiselect', function (data) {
	    if (vnode.componentInstance.id !== data) {
		deactivate();
	    }
	});

	var closestClass = function (element, className) {
	    while(element) {
		if (element.className && element.className.split(' ').indexOf(className) > -1) {
		    return element;
		}
		element = element.parentNode;
	    }
	    return null;
	}

	var tab = closestClass(el, 'tab-pane');

	if ( ! tab ) {
	    return;
	}

	tab.addEventListener('click', function (e) {

	    if (vnode.componentInstance.id === e.target.id) {
		return true;
	    }

	    if (e.target.id === 'wpo-advanced-search-button' && el.nextSibling && el.nextSibling.nextSibling && el.nextSibling.nextSibling.id === 'wpo-advanced-search-button') {
		return true;
	    }

	    deactivate();
	});
    },
}

module.exports = directive