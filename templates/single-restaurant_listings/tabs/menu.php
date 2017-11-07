<?php
/**
 * Restaurant tab
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $post;

$menu_images = explode( ',', $post->_restaurant_menu );

?>
<div id="restaurant-menu" class="restaurant-menu">
<?php foreach ( $menu_images as $menu_item ): ?>
<div class="menu-page-wrapper">
    <img data-lazy="<?php echo wp_get_attachment_url( $menu_item, 'full' ); ?>" alt="<?php the_title() ?>" class="img-menu-page"/>
</div>
<?php endforeach; ?>
</div>
