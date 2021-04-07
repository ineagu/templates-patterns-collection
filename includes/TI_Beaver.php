<?php
/**
 * Handles code for Beaver.
 * It is not named Beaver.php on purpose as Beaver takes the module name from the class, hence naming it to Beaver.php would give
 * the module the name of Beaver which can cause conflicts.
 *
 * @package    templates-patterns-collection
 */

namespace TIOB;

use FLBuilder;
use FLBuilderAJAX;
use FLBuilderModel;
use FLBuilderModule;
use FLBuilderSettingsCompat;
use FLBuilderAJAXLayout;
use FLBuilderUISettingsForms;

/**
 * Class TI_Beaver
 *
 * @package templates-patterns-collection
 */
class TI_Beaver extends FLBuilderModule {

	/**
	 * The module construct, we need to pass some basic info here.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'name'        => __( 'Templates Cloud', 'templates-patterns-collection' ),
				'description' => __( 'Templates Cloud by Neve.', 'templates-patterns-collection' ),
				'category'    => __( 'Templates Cloud', 'templates-patterns-collection' ),
				'dir'         => TIOB_PATH . 'beaver/',
				'url'         => TIOB_URL . 'beaver/',
				'icon'        => TIOB_URL . 'beaver/icon.svg',
			)
		);

		$deps = require( TIOB_PATH . 'beaver/build/index.asset.php' );

		$this->add_js( 'ti-tpc-beaver', $this->url . 'build/index.js', array_merge( $deps['dependencies'], array() ), $deps['version'], true );
		$this->add_css( 'ti-tpc-beaver', $this->url . 'build/index.css', array( 'wp-components' ), $deps['version'] );
	}

	/**
	 * Initialize the Editor.
	 */
	public function init() {
		FLBuilderAJAX::add_action( 'ti_get_position', __CLASS__ . '::get_position', array( 'node' ) );
		FLBuilderAJAX::add_action( 'ti_apply_template', __CLASS__ . '::apply_template', array( 'template', 'position' ) );
		FLBuilderAJAX::add_action( 'ti_export_template', __CLASS__ . '::export_template', array( 'node', 'title' ) );
		FLBuilderAJAX::add_action( 'ti_export_page_template', __CLASS__ . '::export_page_template', array( 'is_sync' ) );
		FLBuilderAJAX::add_action( 'ti_publish_template', __CLASS__ . '::publish_template', array( 'slug', 'screenshot', 'premade' ) );

		add_action( 'wp_head', array( $this, 'inline_script' ), 9 );
		add_action( 'fl_builder_before_save_layout', array( $this, 'update_published_template' ), 10, 4 );

		$is_pro = apply_filters( 'product_neve_license_key', 'free' );

		if ( $is_pro === 'free' ) {
			return;
		}

		add_filter( 'fl_builder_main_menu', array( $this, 'add_export_menu' ), 10, 1 );
	}

	/**
	 * Template Meta.
	 */
	static public function get_template_meta() {
		return apply_filters( 'ti_tpc_template_meta', array(), $type = 'beaver' );
	}

	/**
	 * Add strings as global variable.
	 */
	public function inline_script() {
		$ti_tpc = apply_filters(
			'ti_tpc_editor_data',
			array(
				'endpoint'     => TPC_TEMPLATES_CLOUD_ENDPOINT,
				'params'       => array(
					'site_url'   => get_site_url(),
					'license_id' => apply_filters( 'product_neve_license_key', 'free' ),
					'type'       => 'beaver',
				),
				'canPredefine' => apply_filters( 'ti_tpc_can_predefine', false ),
				'pageTitle'    => get_the_title(),
				'postType'     => get_post_type(),
				'postTypes'    => FLBuilderModel::get_post_types(),
				'postMeta'     => array(
					'_ti_tpc_template_sync'  => get_post_meta( get_the_ID(), '_ti_tpc_template_sync', true ),
					'_ti_tpc_screenshot_url' => get_post_meta( get_the_ID(), '_ti_tpc_screenshot_url', true ),
					'_ti_tpc_site_slug'      => get_post_meta( get_the_ID(), '_ti_tpc_site_slug', true ),
					'_ti_tpc_published'      => get_post_meta( get_the_ID(), '_ti_tpc_published', true ),
				),
				'exporter'     => array(
					'modalLabel'      => __( 'Save Templates' ),
					'textLabel'       => __( 'Template Name' ),
					'textPlaceholder' => __( 'Template' ),
					'buttonLabel'     => __( 'Save' ),
					'toggleLabel'     => __( 'Automatically sync to the cloud' ),
					'cancelLabel'     => __( 'Cancel' ),
					'importFailed'    => __( 'Import Failed' ),
					'exportFailed'    => __( 'Export Failed' ),
				),
				'library'      => array(
					'templatesCloud' => __( 'Templates Cloud' ),
					'404'            => __( 'No templates available. Add a new one?' ),
					'deleteItem'     => __( 'Are you sure you want to delete this template?' ),
					'synced'         => __( 'This template is synced to a page.' ),
					'tabs'           => array(
						'templates' => __( 'Page Templates' ),
						'library'   => __( 'My Library' ),
					),
					'actions'        => array(
						'sync'     => __( 'Sync Library' ),
						'save'     => __( 'Save to Neve Cloud' ),
						'update'   => __( 'Update' ),
						'close'    => __( 'Close' ),
						'edit'     => __( 'Edit' ),
						'delete'   => __( 'Delete' ),
						'deleting' => __( 'Deleting' ),
						'preview'  => __( 'Preview' ),
						'import'   => __( 'Import' ),
					),
					'filters'        => array(
						'sortLabel'   => __( 'Sort by' ),
						'sortLabels'  => array(
							'name'     => __( 'Name' ),
							'date'     => __( 'Date' ),
							'modified' => __( 'Last Modified' ),
							'actions'  => __( 'Actions' ),
							'list'     => __( 'List View' ),
							'grid'     => __( 'Grid View' ),
						),
						'search'      => __( 'Search' ),
						'searchLabel' => __( 'Search Templates' ),
						'clearSearch' => __( 'Clear search query' ),
					),
					'export'         => array(
						'save'            => __( 'Save' ),
						'title'           => __( 'Save your page to Templates Cloud' ),
						'labelScreenshot' => __( 'Screenshot URL' ),
						'labelSlug'       => __( 'Site Slug' ),
						'publish'         => __( 'Publish' ),
						'unpublish'       => __( 'Unpublish' ),
					),
				),
			)
		);
		?>
		<script type="text/javascript">
			window.tiTpc = <?php echo json_encode( $ti_tpc ); ?>;
		</script> 
		<?php
	}

	/**
	 * Properly serialize JSON output.
	 */
	static public function serialize_corrector( $serialized_string ) {
		// at first, check if "fixing" is really needed at all. After that, security checkup.
		if ( @unserialize( $serialized_string ) !== true && preg_match( '/^[aOs]:/', $serialized_string ) ) {
			$serialized_string = preg_replace_callback(
				'/s\:(\d+)\:\"(.*?)\";/s',
				function ( $matches ) {
					return 's:' . strlen( $matches[2] ) . ':"' . $matches[2] . '";';
				},
				$serialized_string
			);
		}
		return $serialized_string;
	}

	/**
	 * Get position of node.
	 */
	static public function get_position( $node ) {
		if ( empty( $node ) ) {
			$row_position = FLBuilderModel::next_node_position( 'row' );
			return $row_position;
		}

		$row = FLBuilderModel::get_node_parent_by_type( $node, 'row' );
		return $row->position;
	}

	/**
	 * Import templates to Beaver.
	 */
	static public function apply_template( $template, $position = 0 ) {
		$response = self::get_template_content( $template );

		$row_position    = $position;
		$new_items_count = 0;

		if ( isset( $response->nodes ) ) {
			// Get new ids for the template nodes.
			$response->nodes = FLBuilderModel::generate_new_node_ids( $response->nodes );

			// Filter the nodes for backwards compatibility with old settings.
			$response->nodes = FLBuilderSettingsCompat::filter_layout_data( $response->nodes );

			// Get the existing layout data and settings.
			$layout_data     = FLBuilderModel::get_layout_data();
			$layout_settings = FLBuilderModel::get_layout_settings();

			// Reposition rows?
			foreach ( $response->nodes as $node_id => $node ) {
				if ( 'row' === $node->type ) {
					$response->nodes[ $node_id ]->position += $row_position;
					$new_items_count++;
				}
			}

			if ( count( $response->nodes ) > 1 && count( $layout_data ) > 0 ) {
				foreach ( $layout_data as $node_id => $node ) {
					if ( 'row' === $node->type && (int) $position <= (int) $layout_data[ $node_id ]->position ) {
						$layout_data[ $node_id ]->position += $new_items_count;
					}
				}
			}

			// Merge and update the layout data.
			$data = array_merge( $layout_data, $response->nodes );
			FLBuilderModel::update_layout_data( $data );

			// Merge and update the layout settings.
			if ( isset( $response->settings ) ) {
				$settings = FLBuilderModel::merge_layout_settings( $layout_settings, $response->settings );
				FLBuilderModel::update_layout_settings( $settings );
			}
		}

		// Delete old asset cache.
		FLBuilderModel::delete_asset_cache();

		// Return the layout.
		return array(
			'layout_css' => isset( $settings ) ? $settings->css : null,
			'layout'     => FLBuilderAJAXLayout::render(),
			'config'     => FLBuilderUISettingsForms::get_node_js_config(),
		);
	}

	/**
	 * Add export menu to Beaver Row's context.
	 */
	public function add_export_menu( $views ) {
		if ( in_array( get_post_type(), FLBuilderModel::get_post_types() ) ) {
			$views['main']['items'][15] = array(
				'label'     => __( 'Save to Neve Cloud', 'templates-patterns-collection' ),
				'type'      => 'event',
				'eventName' => 'tiTpcExport',
			);
		}

		return $views;
	}

	/**
	 * Export row template.
	 */
	static public function export_template( $node, $title ) {
		$row                 = FLBuilderModel::get_node( $node );
		$nodes               = FLBuilderModel::get_nested_nodes( $node );
		$post_id             = FLBuilderModel::get_post_id();
		$nodes[ $row->node ] = $row;
		$obj                 = new \stdClass();
		$obj->nodes          = $nodes;
		$body                = serialize( $obj );
		return self::save_template( $title, $body );
	}

	/**
	 * Export page template.
	 */
	static public function export_page_template( $is_sync ) {
		$title         = get_the_title();
		$title         = empty( $title ) ? __( 'Template', 'templates-patterns-collection' ) : $title;
		$nodes         = FLBuilderModel::get_layout_data();
		$settings      = FLBuilderModel::get_layout_settings();
		$obj           = new \stdClass();
		$obj->nodes    = $nodes;
		$obj->settings = $settings;
		$body          = serialize( $obj );
		$post_id       = FLBuilderModel::get_post_id();
		$template_id   = get_post_meta( $post_id, '_ti_tpc_template_id', true );

		if ( ! empty( $template_id ) && self::get_template( $template_id ) ) {
			return self::update_template( $template_id, $title, $body, $is_sync );
		} else {
			return self::save_template( $title, $body, true, $is_sync );
		}
	}

	/**
	 * Update published templates.
	 */
	public function update_published_template( $post_id, $publish, $data, $settings ) {
		if ( ! $publish ) {
			return false;
		}

		$is_sync = get_post_meta( intval( $post_id ), '_ti_tpc_template_sync', true );

		if ( $is_sync ) {
			return self::export_page_template( true );
		}
	}

	/**
	 * Get template by ID.
	 */
	static public function get_template( $template_id ) {
		$url = add_query_arg(
			array(
				'site_url'   => get_site_url(),
				'license_id' => apply_filters( 'product_neve_license_key', 'free' ),
				'cache'      => uniqid(),
			),
			TPC_TEMPLATES_CLOUD_ENDPOINT . 'templates/' . esc_attr( $template_id )
		);

		$response = wp_remote_get( esc_url_raw( $url ) );
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		if ( isset( $response['message'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get template content by ID.
	 */
	static public function get_template_content( $template_id ) {
		$url = add_query_arg(
			array(
				'site_url'   => get_site_url(),
				'license_id' => apply_filters( 'product_neve_license_key', 'free' ),
				'cache'      => uniqid(),
			),
			TPC_TEMPLATES_CLOUD_ENDPOINT . 'templates/' . $template_id . '/import'
		);

		$response = wp_remote_get( esc_url_raw( $url ) );
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		if ( isset( $response['message'] ) ) {
			return wp_send_json_error( $response['message'] );
		}

		$response = self::serialize_corrector( $response );
		$response = unserialize( $response );
		return $response;
	}

	/**
	 * Save Beaver template to Templates Cloud.
	 */
	static public function save_template( $title, $body, $is_page = false, $is_sync = false ) {
		$post_id = FLBuilderModel::get_post_id();

		$url = add_query_arg(
			array(
				'site_url'      => get_site_url(),
				'license_id'    => apply_filters( 'product_neve_license_key', 'free' ),
				'template_name' => esc_attr( $title ),
				'template_type' => 'beaver',
				'link'          => $is_page ? get_permalink( $post_id ) : '',
				'cache'         => uniqid(),
			),
			TPC_TEMPLATES_CLOUD_ENDPOINT . 'templates'
		);

		$response = wp_safe_remote_post(
			$url,
			array(
				'body' => json_encode( $body ),
			)
		);

		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		if ( isset( $response['message'] ) ) {
			return wp_send_json_error( $response['message'] );
		}

		if ( $is_page ) {
			update_post_meta( $post_id, '_ti_tpc_template_sync', $is_sync );

			if ( isset( $response['template_id'] ) ) {
				update_post_meta( $post_id, '_ti_tpc_template_id', $response['template_id'] );
			}
		}

		return $response;
	}

	/**
	 * Update Beaver template to Templates Cloud.
	 */
	static public function update_template( $template_id, $title, $body, $is_sync = false ) {
		$post_id = FLBuilderModel::get_post_id();

		$premade = get_post_meta( get_the_ID(), '_ti_tpc_published', true );

		$url = add_query_arg(
			array(
				'site_url'      => get_site_url(),
				'license_id'    => apply_filters( 'product_neve_license_key', 'free' ),
				'template_name' => esc_attr( $title ),
				'template_type' => 'beaver',
				'link'          => get_permalink( $post_id ),
				'cache'         => uniqid(),
				'meta'          => 'yes' === $premade ? json_encode( self::get_template_meta() ) : '',
			),
			TPC_TEMPLATES_CLOUD_ENDPOINT . 'templates/' . esc_attr( $template_id )
		);

		$response = wp_safe_remote_post(
			$url,
			array(
				'body' => json_encode( $body ),
			)
		);

		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		if ( isset( $response['message'] ) ) {
			return wp_send_json_error( $response['message'] );
		}

		$post_id = FLBuilderModel::get_post_id();

		update_post_meta( $post_id, '_ti_tpc_template_sync', $is_sync );
		update_post_meta( $post_id, '_ti_tpc_template_id', $template_id );

		return $response;
	}

	/**
	 * Publish Beaver template on Templates Cloud.
	 */
	static public function publish_template( $slug, $screenshot, $premade ) {
		$post_id     = FLBuilderModel::get_post_id();
		$template_id = get_post_meta( $post_id, '_ti_tpc_template_id', true );

		$url = add_query_arg(
			array(
				'site_url'           => get_site_url(),
				'license_id'         => apply_filters( 'product_neve_license_key', 'free' ),
				'template_site_slug' => $slug,
				'template_thumbnail' => $screenshot,
				'premade'            => $premade,
				'link'               => get_permalink( $post_id ),
				'cache'              => uniqid(),
				'meta'               => 'yes' === $premade ? json_encode( self::get_template_meta() ) : '',
			),
			TPC_TEMPLATES_CLOUD_ENDPOINT . 'templates/' . esc_attr( $template_id ) . '/publish'
		);

		$bearer = apply_filters( 'ti_tpc_editor_data', array() );

		$response = wp_safe_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . isset( $bearer['bearer'] ) ? $bearer['bearer'] : '',
				),
			)
		);

		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		if ( isset( $response['message'] ) ) {
			return wp_send_json_error( $response['message'] );
		}

		update_post_meta( $post_id, '_ti_tpc_site_slug', $slug );
		update_post_meta( $post_id, '_ti_tpc_screenshot_url', $screenshot );
		update_post_meta( $post_id, '_ti_tpc_published', $premade );

		return $response;
	}
}

FLBuilder::register_module( 'TIOB\TI_Beaver', array() );
