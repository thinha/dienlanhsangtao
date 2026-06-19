<template>
    <b-row>
        <b-col cols="12" class="google-autocomplete-component">
            <div class="autocomplete-block alert alert-primary">
                <b-form-input class="wpo_custom_autocomplete_input" :placeholder="inputPlaceholder" ref="custom_autocomplete_address" v-model="autocompleteInput" autocomplete="off"/>
                <fa-icon icon="search-location" class="autocomplete-icon"/>
            </div>
        </b-col>
    </b-row>
</template>

<style>

</style>

<script>

    import {library} from '@fortawesome/fontawesome-svg-core';
    import {faSearchLocation} from '@fortawesome/free-solid-svg-icons';
    import {FontAwesomeIcon as FaIcon} from '@fortawesome/vue-fontawesome';

    library.add(faSearchLocation)

    export default {
        props: {
            inputPlaceholder: {
                default: function () {
                    return 'Input your address';
                }
            },
            initAutocompleteFunction: {
                default: function () {
                    return '';
                }
            },
        },
        created() {
	    this.init();
        },
        data() {
            return {
                autocompleteInput: '',
            };
        },
        methods: {
            init() {

                this.$nextTick(() => {

		    if (typeof window[this.initAutocompleteFunction] === 'function') {
			window[this.initAutocompleteFunction](
			    this.$refs.custom_autocomplete_address.$el,
			    (fields) => {
				this.onChanged(fields);
			    }
			);
		    }
                });
            },
            onChanged(fields) {
                this.$emit('change', fields);
                this.clear();
            },
            clear() {
                this.autocompleteInput = '';
            },
        },
        components: {
            FaIcon,
        },
    }
</script>
