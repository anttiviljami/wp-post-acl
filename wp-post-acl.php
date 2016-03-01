<?php
/**
 * Plugin name: WP Post ACL
 * Plugin URI: https://github.com/anttiviljami/wp-libre-form
 * Description: A simple way to control who can edit posts or pages
 * Version: 1.0
 * Author: @anttiviljami
 * Author URI: https://github.com/anttiviljami/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.html
 * Text Domain: wp-post-acl
 *
 * A simple way to control who can edit posts or pages
 */

/** Copyright 2016 Antti Kuosmanen

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 3, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! class_exists( 'WP_Post_ACL' ) ) :

class WP_Post_ACL {
  public static $instance;
  public $post_types;

  public static function init() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new WP_Post_ACL();
    }
    return self::$instance;
  }

  private function __construct() {
    $this->post_types = defined('ACL_POST_TYPES') ? unserialize( ACL_POST_TYPES ) : [ 'post', 'page' ];

    add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
    add_action( 'save_post', array( $this, 'save_permissions' ) );

    add_action( 'plugins_loaded', array( $this, 'load_our_textdomain' ) );
  }

  public function add_meta_box() {
    // Shortcode meta box
    add_meta_box(
      'post-acl',
      __( 'Edit Permissions', 'wp-post-acl' ),
      array( $this, 'metabox_acl' ),
      $this->post_types,
      'side',
      'default'
    );
  }

  public function metabox_acl( $post ) {
    $editors = self::get_editors();
    $permissions = get_post_meta( $post->ID, '_acl_edit_permissions', true );
?>
<p style=""><?php _e("You may deselect any users of the role <em>editor</em> who aren't allowed to edit this post."); ?></p>
<ul class="acl-list">
<?php foreach( $editors as $editor ) : ?>
  <li>
    <?php $checked = $this->has_edit_permissions( $post->ID, $editor ); ?>
    <label><input value="<?php echo $editor->user_nicename; ?>" type="checkbox" name="acl_users[]" <?php echo $checked ? 'checked' : ''; ?>> <?php echo $editor->display_name; ?></label>
  </li>
<?php endforeach; ?>
</ul>
<?php
    wp_nonce_field( 'wp_post_acl_meta', 'wp_post_acl_meta_nonce' );
  }

  public function has_edit_permissions( $post_id, $user ) {
    $permissions = get_post_meta( $post_id, '_acl_edit_permissions', true );

    // convert $user to WP_User if not yet an instance
    if(! $user instanceof WP_User ) {
      if( is_numeric( $user ) ) {
        $user = get_user_by( $user, 'slug' );
      }
      else {
        $user = get_user_by( $user, 'id' );
      }
    }

    return isset( $permissions[ $user->user_nicename ] ) && $permissions[ $user->user_nicename ] === false ? false : true;
  }

  /**
   * Save ACL options for post
   */
  public function save_permissions( $post_id ) {
    // verify nonce
    if ( ! isset( $_POST['wp_post_acl_meta_nonce'] ) ) {
      return;
    }
    else if ( ! wp_verify_nonce( $_POST['wp_post_acl_meta_nonce'], 'wp_post_acl_meta' ) ) {
      return;
    }

    $permissions = array();
    $editors = self::get_editors();
    foreach( $editors as $editor ) {
      if( isset( $_POST['acl_users'] ) && is_array( $_POST['acl_users'] )) {
        $permissions[ $editor->user_nicename ] = in_array( $editor->user_nicename, $_POST['acl_users'] );
      }
      else {
        $permissions[ $editor->user_nicename ] = false;
      }
    }
    update_post_meta( $post_id, '_acl_edit_permissions', $permissions );
  }

  /**
   * Get list of users acl applies to
   */
  private static function get_editors() {
    return apply_filters( 'acl_get_editors', get_users([ 'role' => 'editor' ]) );
  }

  /**
   * Load our plugin textdomain
   */
  public static function load_our_textdomain() {
    load_plugin_textdomain( 'wp-post-acl', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
  }
}

endif;

// init the plugin
WP_Post_ACL::init();
