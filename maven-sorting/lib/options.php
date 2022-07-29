<?php
/**
 * YIKES Simple Taxonomy Ordering Options Class.
 *
 * @package YIKES_Simple_Taxonomy_Ordering
 */
class Simple_Taxonomy_Options {

	/**
	 * Holds the values to be used in the fields callbacks.
	 *
	 * @var array $options.
	 */
	private $options;

	/**
	 * Start up.
	 */
	public function __construct() {
        
		add_action( 'admin_menu', array( $this, 'define_options_page' ) );
		add_action( 'admin_init', array( $this, 'init_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Determine whether this is our settings page.
	 *
	 * @return bool True if we're on our settings page.
	 */
	private function is_settings_page() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : '';
		return ! empty( $screen ) && ! empty( $screen->base ) && $screen->base === 'settings_page_simple-taxonomy-ordering';
	}

	/**
	 * Add additiona scripts and styles as needed.
	 */
	public function enqueue_assets() {
		if ( $this->is_settings_page() ) {
			$min = yikes_sto_maybe_minified();
			wp_enqueue_style( 'select2.min.css', plugin_dir_url( __FILE__ ) . 'css/select2.min.css', array(), YIKES_STO_VERSION, 'all' );
			wp_enqueue_script( 'select2.min.js', plugin_dir_url( __FILE__ ) . 'js/select2.full.min.js', array( 'jquery' ), YIKES_STO_VERSION, true );
			wp_enqueue_script( 'init-select2', plugin_dir_url( __FILE__ ) . "js/init-select2{$min}.js", array( 'select2.min.js' ), YIKES_STO_VERSION, true );
		}
	}


	/**
	 * Add options page.
	 */
	public function define_options_page() {
		// This page will be under "Settings."
		add_submenu_page(
			'options-general.php',
			__( 'Simple Tax. Ordering', 'simple-taxonomy-ordering' ),
			__( 'Simple Tax. Ordering', 'simple-taxonomy-ordering' ),
			apply_filters( 'simple_taxonomy_ordering_capabilities', 'manage_options' ),
			'simple-taxonomy-ordering',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback.
	 */
	public function create_admin_page() {
		// Set class property.
		$this->options = get_option( YIKES_STO_OPTION_NAME, array() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Maven Taxonomy Ordering', 'simple-taxonomy-ordering' ); ?></h1>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields.
				settings_fields( 'yikes_sto_option_group' );
				do_settings_sections( 'simple-taxonomy-ordering' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings.
	 */
	public function init_options() {
		register_setting( 'yikes_sto_option_group', YIKES_STO_OPTION_NAME );

		add_settings_section(
			'yikes_sto_setting_section',
			'',
			array( $this, 'options_header_text' ),
			'simple-taxonomy-ordering'
		);

		add_settings_field(
			'enabled_taxonomies',
			__( 'Enabled Taxonomies', 'simple-taxonomy-ordering' ),
			array( $this, 'enabled_taxonomies_callback' ),
			'simple-taxonomy-ordering',
			'yikes_sto_setting_section'
		);
	}

	/**
	 * Print the Options Page Description.
	 */
	public function options_header_text() {
		esc_html_e( 'Enable or disable taxonomies from being orderable by using the dropdown.', 'simple-taxonomy-ordering' );
	}

	/**
	 * Get the settings option array and print one of its values.
	 */
	public function enabled_taxonomies_callback() {
		$taxonomies = $this->get_taxonomies();
		$enabled    = isset( $this->options['enabled_taxonomies'] ) ? array_flip( $this->options['enabled_taxonomies'] ) : array();
		if ( $taxonomies ) {
			?>
			<select id="yikes-sto-select2" style="display: none;" multiple="multiple" name="<?php echo esc_attr( YIKES_STO_OPTION_NAME ); ?>[enabled_taxonomies][]">
			<?php
			foreach ( $taxonomies as $taxonomy ) {
				$tax_object       = get_taxonomy( $taxonomy );
				$tax_name         = $tax_object && isset( $tax_object->labels ) ? $tax_object->labels->name : $taxonomy;
				$post_type        = $tax_object && isset( $tax_object->object_type ) && isset( $tax_object->object_type[0] ) ? $tax_object->object_type[0] : 'post';
				$post_type_object = get_post_type_object( $post_type );
				$post_type_label  = $post_type_object->labels->name;
				$selected         = isset( $this->options['enabled_taxonomies'] ) && isset( $enabled[ $taxonomy ] ) ? 'selected="selected"' : '';
				?>
					<option value="<?php echo esc_attr( $taxonomy ); ?>" <?php echo esc_attr( $selected ); ?>>
						<?php echo esc_html( $tax_name ) . ' <small>(' . esc_html( $post_type_label ) . ')</small>'; ?>
					</option>
				<?php
                
			}
            
			?>
			</select>
			<p class="description"><?php esc_html_e( 'Select which taxonomies you would like to sort posts by.', 'simple-taxonomy-ordering' ); ?></p>
			<?php

		} else {
			esc_html_e( 'No Taxonomies Found.', 'simple-taxonomy-ordering' );
		}

            /*Maven added to assign sorting based on selected taxonomies
            *
            */
            if($enabled){ 
                global $wpdb;
                $taxes = array_keys($enabled);
				
				//get terms for each taxonomy enabled. Get the last terms order for sorting. If taxonomy not valid, unset taxonomy.
				foreach($taxes as $ind=>$tax){ 
					$categories = get_terms($tax);
					$highestOrder = end($categories);
					$counter = $highestOrder->order;
					if($categories->errors){
						unset($taxes[$ind]);
						continue;
					}
					
					//for custom posts types
					$tr= get_taxonomy( $tax);
					$assocPostsTypes=$tr->object_type;
					
					foreach($assocPostsTypes as $assocPostsType){
						$postTypes[$assocPostsType][] = ($tax);
					}

					//check to see if taxonomy has an order set, if not set them in Database
					foreach($categories as $category){
						if(($category->order)==0){
							$counter++;
							$table = 'wp_term_taxonomy';
							$data = array('order'=>$counter);
							$where = array('term_id'=>($category->term_id));
							$wpdb->update( $table, $data, $where);
							clean_term_cache( ($category->term_id), ($category->taxonomy) );
						}
					}
				}
                
				//loop through all post types and update menu order based on sorting
				foreach($postTypes as $postType=>$postTaxes){
					$posts = get_posts(array(
						'post_type' => $postType,
						'nopaging' => true
					));
					$fullSort=array();
					$numPosts = (count($posts));
					foreach($posts as $post){
						foreach($postTaxes as $tax){
							$tempTerms = get_the_terms($post->ID, $tax);
					
							if($tempTerms){
								$child = end($tempTerms);
								$fullSort[$post->ID][] = $child->order;
							} else{
								$fullSort[$post->ID][] = $numPosts;
							}
						}

						//if post has no sort by info, set menu position to max (no of posts)
						global $wpdb;
						$table = 'wp_posts';
						$data = array('menu_order'=>$numPosts);
						$where = array('ID'=>$post->ID);
						$wpdb->update( $table, $data, $where);
						clean_post_cache( $post->ID );
					}

					//actual sorting of posts to prepare for db update
					uasort($fullSort, function($a, $b) {
						return $a <=> $b;
					});
					$counter=1;
					
					foreach($fullSort as $pId => $sortedPost){
						$table = 'wp_posts';
						$data = array('menu_order'=>$counter);
						$where = array('ID'=>$pId);
						$wpdb->update( $table, $data, $where);
						clean_post_cache( $pId );
						$counter++;
					}
				}
            }
	}


	/**
	 * Fetch all the taxonomies that should be available for ordering.
	 *
	 * By default, we exclude some WordPress, WooCommerce, and EDD taxonomy terms. To add or remove a taxonomy that we're excluding, use the filter `yikes_simple_taxonomy_ordering_excluded_taxonomies`.
	 *
	 * @return array An array of taxonomies.
	 */
	private function get_taxonomies() {
		$taxonomies = get_taxonomies();

		// Array of taxonomies we want to exclude from being displayed in our options.
		$excluded_taxonomies = array(
			'nav_menu',
			'link_category',
			'post_format',
			'product_shipping_class',
			'product_cat',
			'edd_log_type',
		);

		/**
		 * Filter yikes_simple_taxonomy_ordering_ignored_taxonomies.
		 *
		 * Add or remove taxonomies that should not be available for ordering.
		 *
		 * @param array $excluded_taxonomies The array of ignored taxonomy slugs.
		 * @param array $taxonomies          The array of included taxonomy slugs.
		 *
		 * @return array $excluded_taxonomies The array of taxonomies that should be excluded from ordering.
		 */
		$excluded_taxonomies = apply_filters( 'yikes_simple_taxonomy_ordering_excluded_taxonomies', $excluded_taxonomies, $taxonomies );

		// Remove excluded taxonomies.
		$taxonomies = array_diff( $taxonomies, $excluded_taxonomies );

		/**
		 * Filter yikes_simple_taxonomy_ordering_included_taxonomies.
		 *
		 * Add or remove taxonomies that are available for ordering.
		 *
		 * @param array $taxonomies          The array of included taxonomy slugs.
		 * @param array $excluded_taxonomies The array of ignored taxonomy slugs.
		 *
		 * @return array $excluded_taxonomies The array of taxonomies that should be excluded from ordering.
		 */
		$taxonomies = apply_filters( 'yikes_simple_taxonomy_ordering_included_taxonomies', $taxonomies, $excluded_taxonomies );

		// Return the taxonomies.
		return $taxonomies;
	}
}

$yikes_sto_settings = new Simple_Taxonomy_Options();
