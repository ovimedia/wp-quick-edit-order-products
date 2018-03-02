<?php
/*
Plugin Name: WP Quick Edit Order Products
Description: Edit the order of woocommerce product in the quick edit.
Author: Ovi García - ovimedia.es
Author URI: http://www.ovimedia.es/
Text Domain: wp-quick-edit-order-produts
Version: 0.1
Plugin URI: http://www.ovimedia.es/
*/


function wqeop_quickedit_custom_posts_columns( $posts_columns ) 
{
    $posts_columns['qupproductorder'] = __( 'Order', 'wqeop' );
    return $posts_columns;
}

add_filter( 'manage_product_posts_columns', 'wqeop_quickedit_custom_posts_columns' );

function wqeop_quickedit_custom_column_display( $column_name, $post_id ) 
{
    if ( 'qupproductorder' == $column_name ) {

        $post = get_post( $post_id );
        $product_order = $post->menu_order;

        if ( $product_order ) {
            echo esc_html( $product_order );
        } else {
            echo '0';
        }
    }
}

add_action( 'manage_product_posts_custom_column', 'wqeop_quickedit_custom_column_display', 10, 2 );

function wqeop_quickedit_fields( $column_name, $post_type ) 
{
    if ( 'qupproductorder' != $column_name )
        return;
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <span class="title"><?php esc_html_e( 'Order', 'wqeop' ); ?></span>
                <span class="input-text-wrap">
                    <input type="number" name="qupproductorder" class="qupproductorder" value="">
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}

add_action( 'quick_edit_custom_box', 'wqeop_quickedit_fields', 10, 2 );
add_action( 'bulk_edit_custom_box', 'wqeop_quickedit_fields', 10, 2 );

function wqeop_quickedit_save_post( $post_id, $post ) 
{
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( $post->post_type != 'product' )
        return;

     if ( ! current_user_can( 'edit_post', $post_id ) )
         return;

    remove_action('save_post', 'wqeop_quickedit_save_post');

    wp_update_post(array('ID' => $post_id, 'menu_order' => $_REQUEST["qupproductorder"]));

    add_action('save_post', 'wqeop_quickedit_save_post');
}

add_action( 'save_post', 'wqeop_quickedit_save_post', 10, 2 );

function wqeop_quickedit_javascript() 
{
    $current_screen = get_current_screen();

    if ( $current_screen->id != 'edit-product' || $current_screen->post_type != 'product' )
        return;

    wp_enqueue_script( 'jquery' );

    ?>

    <script type="text/javascript">

        jQuery( function( $ ) {
            $( '#the-list' ).on( 'click', 'a.editinline', function( e ) {
                e.preventDefault();
                var order = $(this).data( 'product-order' );
                inlineEditPost.revert();
                $( '.qupproductorder' ).val( order ? order : '0' );
            });

            $( '#bulk_edit' ).live( 'click', function() {

                var $bulk_row = $( '#bulk-edit' );

                var $post_ids = new Array();

                $bulk_row.find( '#bulk-titles' ).children().each( function() 
                {
                    $post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
                });

                var $product_order = $bulk_row.find( 'input[name="qupproductorder"]' ).val();

                $.ajax({
                url: ajaxurl, 
                type: 'POST',
                async: false,
                cache: false,
                data: {
                    action: 'product_order_save_bulk_edit', 
                    post_ids: $post_ids, 
                    qupproductorder: $product_order
                }
                });

            });

        });
    </script>

    <?php
}

add_action( 'admin_print_footer_scripts-edit.php', 'wqeop_quickedit_javascript' );

function product_order_save_bulk_edit() 
{
   $post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();

   $qupproductorder = ( isset( $_POST[ 'qupproductorder' ] ) && !empty( $_POST[ 'qupproductorder' ] ) ) ? $_POST[ 'qupproductorder' ] : NULL;

   if ( !empty( $post_ids ) && is_array( $post_ids ) && !empty( $qupproductorder ) ) 
   {
        foreach( $post_ids as $post_id ) 
        {
            wp_update_post(array('ID' => $post_id, 'menu_order' => $_REQUEST["qupproductorder"]));
        }
   }
}

add_action( 'wp_ajax_product_order_save_bulk_edit', 'product_order_save_bulk_edit' );

function wqeop_quickedit_set_data( $actions, $post ) {

    $post = get_post( $post_id );
    $product_order = $post->menu_order;

    if ( $product_order ) {
        if ( isset( $actions['inline hide-if-no-js'] ) ) {
            $new_attribute = sprintf( 'data-product-order="%s"', esc_attr( $product_order ) );
            $actions['inline hide-if-no-js'] = str_replace( 'class=', "$new_attribute class=", $actions['inline hide-if-no-js'] );
        }
    }

    return $actions;
}

add_filter('post_row_actions', 'wqeop_quickedit_set_data', 10, 2);

function wpex_order_category( $query ) 
{
    $query->set( 'orderby', 'menu_order'); 
    $query->set( 'order', 'asc');     
    return;
}

add_action( 'pre_get_posts', 'wpex_order_category', 1 );
add_filter('parse_query', 'wpex_order_category' );

?>