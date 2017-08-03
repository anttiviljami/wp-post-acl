<?php
/**
 * Plugin name: WP Post ACL
 * Plugin URI: https://github.com/anttiviljami/wp-post-acl
 * Description: A simple way to control who can edit posts or pages
 * Version: 1.0.1
 * Author: @anttiviljami
 * Author URI: https://github.com/anttiviljami/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.html
 * Text Domain: wp-post-acl
 *
 * Adds an Edit Permissions metabox to the post edit page, where you can select
 * which users can edit the post.
 */

/** Copyright 2017 Antti Kuosmanen

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

    add_filter( 'user_has_cap', array( $this, 'check_post_edit_acl' ), 10, 3 );

    add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
    add_action( 'save_post', array( $this, 'save_permissions' ) );

    add_action( 'plugins_loaded', array( $this, 'load_our_textdomain' ) );
  }

  /**
   * check_post_edit_acl()
   *
   * Filter on the current_user_can() function.
   * Here we check if we need to remove the edit_post cap for a specific post
   *
   * @param array $allcaps All the capabilities of the user
   * @param array $cap     [0] Required capability
   * @param array $args    [0] Requested capability
   *                       [1] User ID
   *                       [2] Associated object ID
   */
  public function check_post_edit_acl( $allcaps, $cap, $args ) {
    // we do this only for edit_post
    if ( 'edit_post' != $args[0] )
      return $allcaps;

    // post id must be set
    if ( !isset( $args[2] ) ) {
      return $allcaps;
    }

    // post data
    $post = get_post( $args[2] );

    // are we concerned about this post type
    if( !in_array( $post->post_type, $this->post_types ) ) {
      return $allcaps;
    }

    // user id
    $user_id = $args[1];

    // are we concerned about this user?
    $editors = self::get_editors();
    $editor_ids = array_map( function( $user ) { return $user->ID; }, $editors );
    if( !in_array( $user_id, $editor_ids ) ) {
      return $allcaps;
    }

    // does user have permissions to edit this post
    if( $this->has_edit_permissions( $post->ID, $user_id ) ) {
      return $allcaps;
    }

    $allcaps[$cap[0]] = false;

    return $allcaps;
  }

  public function add_meta_box() {
    if( current_user_can( 'remove_users' ) ) {
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
  }

  public function metabox_acl( $post ) {
    $editors = self::get_editors();
    if( empty( $editors ) ) {
?>
<p><?php _e('No users of role <em>editor</em> found.', 'wp-post-acl'); ?></p>
<?php
      return;
    }
    $permissions = get_post_meta( $post->ID, '_acl_edit_permissions', true );
?>
<p style=""><?php _e("You may deselect any users of the role <em>editor</em> who aren't allowed to edit this post.", 'wp-post-acl'); ?></p>
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

  /**
   * An easy way to check if a user has edit permissions for a post
   */
  public function has_edit_permissions( $post_id, $user ) {
    $permissions = get_post_meta( $post_id, '_acl_edit_permissions', true );

    // convert $user to WP_User if not yet an instance
    if( ! $user instanceof WP_User ) {
      if( is_numeric( $user ) ) {
        $user = get_user_by( 'id', $user );
      }
      else {
        $user = get_user_by( 'slug', $user );
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

    // check permissions
    if( ! current_user_can( 'remove_users' ) ) {
      return;
    }

    // check valid post type
    if ( !isset( $_POST['post_type'] ) || ! in_array( $_POST['post_type'], $this->post_types ) ) {
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
