<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Phone_Orders_Log_Page extends WC_Phone_Orders_Admin_Abstract_Page {
	public $title;
	public $priority = 40;
	protected $tab_name = 'log';

	protected $list_log_instance;
	private $hook_suffix = 'woocommerce_page_phone_orders';

	public function __construct() {
		parent::__construct();
		$this->title = __( 'Log', 'phone-orders-for-woocommerce' );
	}

	public function render() {
		$this->tab_data = array(
			'title'         => __( 'Log page', 'phone-orders-for-woocommerce' ),
			'tabName'       => 'log-page',
			'inputId'       => 'customer', // search by this field slug
			'sortBy'        => 'time_updated',
			'sortOrder'     => 'desc',
			'searchLabel'   => __( 'Search', 'phone-orders-for-woocommerce' ),
			'notFoundLabel' => __( 'No items found.' ),
		);
		add_thickbox();
		?>
                    <tab-log v-bind="<?php echo esc_attr( json_encode( $this->tab_data ) ) ?>"></tab-log>
		<?php
	}

	public function enqueue_scripts() {
		parent::enqueue_scripts();
	}

	public function print_log_table() {
		global $hook_suffix;
		$hook_suffix = $this->hook_suffix;
		$list_log_instance = new WC_Phone_Orders_List_Log();
		echo $list_log_instance->output_report();
	}

	protected function ajax_print_log( $data ) {
		$this->print_log_table();
	}

	protected function get_columns( $list_log_instance ) {
		$all_columns      = $list_log_instance->get_columns();
		$sortable_columns = array_keys( $list_log_instance->get_sortable_columns() );

		$columns = array();
		foreach ( $all_columns as $slug => $label ) {
			$columns[ $slug ]['label']    = $label;
			$columns[ $slug ]['sortable'] = in_array( $slug, $sortable_columns );
		}

		return $columns;
	}

	protected function get_rows( $list_log_instance ) {
		$list_log_instance->prepare_items();
		$rows = $list_log_instance->items;
		foreach ( $rows as $row ) {
		    if ( isset($row['id']) ) {
			    $row['ID'] = $row['id'];
		    }
		}

		return $rows;
	}

	protected function get_bulk_actions( $list_log_instance ) {
		return array();
	}

	protected function get_pagination_args( $list_log_instance ) {
		$list_log_instance->prepare_items();

		$pagination_args = array();
		foreach ( array( 'total_items', 'total_pages', 'per_page' ) as $arg ) {
			$pagination_args[ $arg ] = $list_log_instance->get_pagination_arg( $arg );
		}

		return $pagination_args;
	}

	protected function get_current_page( $list_log_instance ) {
		return $list_log_instance->get_pagenum();
	}

	protected function ajax_get_table( $data ) {
		global $hook_suffix;
		$hook_suffix = $this->hook_suffix;
		$list_log_instance = new WC_Phone_Orders_List_Log();

		ob_start();
		$list_log_instance->search_box( __( 'Search', 'phone-orders-for-woocommerce' ), 'search' );
		$search_box = ob_get_clean();

		$options = array(
			'columns'         => $this->get_columns( $list_log_instance ),
			'rows'            => $this->get_rows( $list_log_instance ),
			'pagination'      => $this->get_pagination_args( $list_log_instance ),
			'current_page'    => $this->get_current_page( $list_log_instance ),
			'bulk_actions'    => $this->get_bulk_actions( $list_log_instance ),
			'search_box_html' => $search_box,
			'sort_by'         => $list_log_instance->order_column,
			'sort_order'      => $list_log_instance->direction,
		);


		return $this->wpo_send_json_success( $options );
	}
}