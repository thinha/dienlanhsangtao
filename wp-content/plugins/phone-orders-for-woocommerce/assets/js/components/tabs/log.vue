<template>
    <span>
        <div v-show="isRunRequest" class="phone-orders-woocommerce_tab-license_loader">
            <loader></loader>
        </div>
        <p class="search-box">
            <label class="screen-reader-text" :for="inputId"></label>
            <input type="search" v-model="searchInput" :id="inputId" name="s" value="" />
            <input type="button" id="search-submit" class="button" :value="searchLabel" @click="search" />
        </p>
        <list-table
                :columns="columns"
                :loading="false"
                :rows="rows"
                :actions="[]"
                :show-cb="false"
                :total-items="totalItems"
                :bulk-actions="[]"
                :total-pages="totalPages"
                :per-page="rowsPerPage"
                :current-page="currentPage"
                :notFound="notFoundLabel"
                :sortBy="sortBy"
                :sortOrder="sortOrder"
                @pagination="goToPage"
                @sort="sort"
        >

            <!-- enable -->
            <template v-for="(column, index) in columns" :slot="index" slot-scope="data">
                <span v-html="data.row[index]"></span>
            </template>

            <template slot="time_updated" slot-scope="data">
                <span v-html="data.row['time_updated']"></span>
		<div class="row-details-block">
		    <div v-for="(column, index) in columns">
			<p v-if="['time_updated', 'order_number', 'total'].indexOf(index) === -1">
			    <strong>{{ columns[index].label }}:</strong>
			    <span v-html="data.row[index]"></span>
			</p>
		    </div>
		</div>
            </template>

        </list-table>
    </span>
</template>

<style>

    #phone-orders-app .wp-list-table .row-details-block {
	display: none;
    }

    @media (max-width:767px) {

	#phone-orders-app .wp-list-table .column.user_name,
	#phone-orders-app .wp-list-table .column.customer,
	#phone-orders-app .wp-list-table .column.items,
	#phone-orders-app .wp-list-table .column.discount,
	#phone-orders-app .wp-list-table .column.fees,
	#phone-orders-app .wp-list-table .column.shipping {
	    display: none !important;
	}

	#phone-orders-app .wp-list-table .row-details-block {
	    display: block;
	    margin-top: 10px;
	}

	#phone-orders-app p.search-box {
	    width: auto;
	}
    }
</style>

<script>

	var loader = require('vue-spinner/dist/vue-spinner.min').ClipLoader;

	import ListTable from 'vue-wp-list-table';

	export default {
		props: {
			title: {
				default: function() {
					return 'Log page';
				},
			},
			tabName: {
				default: function() {
					return 'log-page';
				},
			},
			defaultColumns: {
				default: function() {
					return {};
				},
			},
			defaultRows: {
				default: function() {
					return [];
				},
			},
			defaultTotalItems: {
				default: function() {
					return 0;
				},
			},
			defaultTotalPages: {
				default: function() {
					return 0;
				},
			},
			defaultRowsPerPage: {
				default: function() {
					return 0;
				},
			},
			defaultCurrentPage: {
				default: function() {
					return 1;
				},
			},
			searchLabel: {
				default: function() {
					return 'Search';
				},
			},
			defaultInputId: {
				default: function() {
					return 'customer';
				},
			},
			defaultSearchInput: {
				default: function() {
					return '';
				},
			},
			notFoundLabel: {
				default: function() {
					return 'No items found.';
				},
			},
			sortByDefault: {
				default: function() {
					return 'time_updated';
				},
			},
			sortOrderDefault: {
				default: function() {
					return 'desc';
				},
			},
			rowDetailsLabel: {
				default: function() {
					return 'details';
				},
			},
		},
		data: function () {
			return {
				columns: this.defaultColumns,
				rows: this.defaultRows,
				totalItems: this.defaultTotalItems,
				totalPages: this.defaultTotalPages,
				rowsPerPage: this.defaultRowsPerPage,
				currentPage: this.defaultCurrentPage,

				inputId: this.defaultInputId,
				searchInput: this.defaultSearchInput,
				sortBy: this.sortByDefault,
				sortOrder: this.sortOrderDefault,

				isRunRequest: false,
			};
		},
		methods: {
			getTable: function ( $additional_args ) {
				this.isRunRequest = true;
				let $args = {
					action: 'phone-orders-for-woocommerce',
					method: 'get_table',
					_wp_http_referer: this.referrer,
					_wpnonce: this.nonce,
					tab: this.tabName,
				};

				$args = Object.assign( $args, $additional_args );

				this.axios.post( this.url, this.qs.stringify( $args ) ).then( ( response ) => {
					let $data = response.data.data;

					this.columns = $data.columns;
					this.rows = $data.rows;
					this.totalItems = $data.pagination.total_items;
					this.totalPages = $data.pagination.total_pages;
					this.rowsPerPage = $data.pagination.per_page;
					this.currentPage = $data.current_page;

					this.sortBy = $data.sort_by;
					this.sortOrder = $data.sort_order;

					this.status = $data.status;
					this.error = $data.error;
					this.isRunRequest = false;
				}, () => {
					this.isRunRequest = false;
				} );
			},

			goToPage(page) {
				this.getTable( {paged: page} );
			},

			search() {
				this.getTable( {s: this.searchInput} );
			},

			update() {
				this.getTable();
			},

			sort( column, order ) {
				console.log(order);
				this.getTable( {
					orderby: column,
					order: order,
				} );
			},
		},
		components: {
			ListTable,
			loader,
		},
	}
</script>