=== WP Term Order ===
Contributors:      maven, johnjamesjacoby, stuttter, YIKES
Tags:              taxonomy, term, order, postorder
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9Q4F4EL5YJ62J
Requires at least: 4.3
Tested up to:      5.5
Stable tag:        1.0.0

== Description ==

This plugin combines and uses code from: 
Simple Taxonomy Ordering: https://wordpress.org/plugins/simple-taxonomy-ordering/
WP Term Order: https://wordpress.org/plugins/wp-term-order/

Sort taxonomy terms, your way.

WP Term Order allows users to order any visible category, tag, or taxonomy term numerically, providing a customized order for their taxonomies.
In addition, it will allow you to use taxonomy order for sorting posts (including custom posts types).
To enable this, you must add the following to wordpress queries:
'orderby'=>'menu_order',
'order'=>'ASC'

Hierarchical taxonomies MUST have their parents checked also for this to work. If parents are not selected you must do so manually or you can use the following code in functions.php:

//Used to auto assign parent categories if a child category is selected. This is to maintain the order of products on archives. Update only occurs on post save.
add_action('save_post', 'assign_parent_terms', 10, 2);
function assign_parent_terms($post_id, $post){

    if($post->post_type != 'product')
        return $post_id;

    // get all assigned terms   
    $terms = wp_get_post_terms($post_id, 'product_type' );
    foreach($terms as $term){
        while($term->parent != 0 && !has_term( $term->parent, 'product_type', $post )){
            // move upward until we get to 0 level terms
            wp_set_post_terms($post_id, array($term->parent), 'product_type', true);
            $term = get_term($term->parent, 'product_type');
        }
    }
}


== Installation ==

Download and install using the built in WordPress plugin installer.

Activate in the "Plugins" area of your admin by clicking the "Activate" link.

No further setup or configuration is necessary.

== Frequently Asked Questions ==

= Does this create new database tables? =

No. There are no new database tables with this plugin.

= Does this modify existing database tables? =

Yes. The `wp_term_taxonomy` table is altered, and an `order` column is added.


== Changelog ==

= 0.1.0 =
* Initial release
