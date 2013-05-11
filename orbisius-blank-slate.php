<?php
/*
Plugin Name: Orbisius Blank Slate
Plugin URI: http://club.orbisius.com/products/wordpress-plugins/orbisius-blank-slate/
Description: This plugin allows you to delete content from your WordPress site/blog. To use it go to <strong>Tools &rarr; Orbisius Blank Slate</strong>
Version: 1.0.1
Author: Svetoslav Marinov (Slavi)
Author URI: http://orbisius.com
*/

/*  Copyright 2012 Svetoslav Marinov (Slavi) <slavi@orbisius.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Set up plugin
add_action('admin_init', 'orbisius_blank_slate_admin_init');
add_action('admin_menu', 'orbisius_blank_slate_setup_admin');
add_action('wp_footer', 'orbisius_blank_slate_add_plugin_credits', 1000); // be the last in the footer

/**
 * @package Orbisius Blank Slate
 * @since 1.0
 *
 * Searches through posts to see if any matches the REQUEST_URI.
 * Also searches tags
 */
function orbisius_blank_slate_admin_init() {
    wp_register_style(dirname(__FILE__), plugins_url('/assets/main.css', __FILE__), false);
    wp_enqueue_style(dirname(__FILE__));
}

/**
 * Set up administration
 *
 * @package Orbisius Blank Slate
 * @since 0.1
 */
function orbisius_blank_slate_setup_admin() {
	add_submenu_page( 'tools.php', 'Orbisius Blank Slate', 'Orbisius Blank Slate', 'manage_options', __FILE__, 'orbisius_blank_slate_tools_action');
	
	// when plugins are show add a settings link near my plugin for a quick access to the settings page.
	add_filter('plugin_action_links', 'orbisius_blank_slate_add_plugin_settings_link', 10, 2);
}

// Add the ? settings link in Plugins page very good
function orbisius_blank_slate_add_plugin_settings_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $prefix = 'tools.php?page=' . plugin_basename(__FILE__);
        $dashboard_link = "<a href=\"{$prefix}\">" . 'Clean' . '</a>';
        array_unshift($links, $dashboard_link);
    }

    return $links;
}

/**
 * Upload page.
 * Ask the user to upload a file
 * Preview
 * Process
 *
 * @package Permalinks to Category/Permalinks
 * @since 1.0
 */
function orbisius_blank_slate_tools_action() {
    $msg = '';
    $errors = $success = array();
    $delete_pages = empty($_REQUEST['delete_pages']) ? 0 : 1;
    $delete_posts = empty($_REQUEST['delete_posts']) ? 0 : 1;
    $delete_comments = empty($_REQUEST['delete_comments']) ? 0 : 1;
    $delete_attachments = empty($_REQUEST['delete_attachments']) ? 0 : 1;
    $orbisius_blank_slate_nonce = empty($_REQUEST['orbisius_blank_slate_nonce']) ? '' : $_REQUEST['orbisius_blank_slate_nonce'];

    if (!empty($_POST)) {
        if (!wp_verify_nonce($orbisius_blank_slate_nonce, basename(__FILE__) . '-action')) {
            $errors[] = "Invalid action";
        } elseif (!$delete_pages && !$delete_posts && !$delete_comments && !$delete_attachments) {
            $errors[] = "Nothing was selected";
        }

        if (empty($errors)) {
            if ($delete_pages) {
                $cnt = orbisius_blank_slate_cleaner::remove_post('page');

                $success[] = "Deleted page(s): $cnt";
            }

            if ($delete_posts) {
                $cnt = orbisius_blank_slate_cleaner::remove_post('post');
                $success[] = "Deleted post(s): $cnt";
            }

            if ($delete_comments) {
                $cnt = orbisius_blank_slate_cleaner::remove_post('comment');
                $success[] = "Deleted comments(s): $cnt";
            }

            if ($delete_attachments) {
                $cnt = orbisius_blank_slate_cleaner::remove_post('attachment');
                $success[] = "Deleted attachment(s): $cnt";
            }
        }
    }

    // get cnt
    $post_cnt = orbisius_blank_slate_cleaner::remove_post('post', 1);
    $page_cnt = orbisius_blank_slate_cleaner::remove_post('page', 1);
    $comment_cnt = orbisius_blank_slate_cleaner::remove_post('comment', 1);
    $attachment_cnt = orbisius_blank_slate_cleaner::remove_post('attachment', 1);

    if (!empty($errors)) {
        $msg = orbisius_blank_slate_util::msg($errors);
        $msg .= "<div class='app-center app-button-container'><a href='{$_SERVER['REQUEST_URI']}'>Try again</a></div>";
    } elseif (!empty($success)) {
        $msg = orbisius_blank_slate_util::msg($success, 1);
        $msg .= "<div class='app-center app-button-container'><a href='{$_SERVER['REQUEST_URI']}'>Delete products from another store</a></div>";
    }

    ?>
    <div class="wrap orbisius-blank-slate-container">
        <h2>Orbisius Blank Slate</h2>
		<div class="app-alert-error">
            <h3> !!! Danger Zone !!!</h3>
            <p>This tool allows you to delete content from your site. It must be used with caution as there is NO undelete.</p>
        </div>

        <?php echo $msg; ?>

        <form id="destroyer_form" method="post">
            <?php wp_nonce_field( basename(__FILE__) . '-action', 'orbisius_blank_slate_nonce' ); ?>
            <div>
                <h3 class="app-step-heading">Mass Deletion</h3>
                <p>Select what you want to delete.</p>
            </div>

            <input type="checkbox" value="1" id="delete_pages" name="delete_pages" />
            <label for="delete_pages">Delete All Pages (<?php echo $page_cnt; ?>)</label>
            <br />

            <input type="checkbox" value="1" id="delete_posts" name="delete_posts" />
            <label for="delete_posts">Delete All Posts (<?php echo $post_cnt; ?>)</label>
            <br />

            <input type="checkbox" value="1" id="delete_comments" name="delete_comments" />
            <label for="delete_comments">Delete All Comments (<?php echo $comment_cnt; ?>)</label>
            <br />

            <input type="checkbox" value="1" id="delete_attachments" name="delete_attachments" />
            <label for="delete_attachments">Delete All Attachments (<?php echo $attachment_cnt; ?>)</label>
            <br />

            <div class="app-center app-button-container ">
                <input type="submit" name="submit" value="Continue" class="app-button-negative button-primary " 
                       onclick="return confirm('Are you 110% sure? There is NO undelete!', '');" />
            </div>
        </form>

        <hr />

        <h2>Want to hear about future plugins? Join our mailing List! (no spam)</h2>
            <p>
                Get the latest news and updates about this and future cool <a href="http://profiles.wordpress.org/lordspace/"
                                                                                target="_blank" title="Opens a page with the pugins we developed. [New Window/Tab]">plugins we develop</a>.
            </p>

            <p>
                <!-- // MAILCHIMP SUBSCRIBE CODE \\ -->
                1) Subscribe by going to <a href="http://eepurl.com/guNzr" target="_blank">http://eepurl.com/guNzr</a>
                <!-- \\ MAILCHIMP SUBSCRIBE CODE // -->
             OR
                2) by using our QR code. [Scan it with your mobile device].<br/>
                <img src="<?php echo plugin_dir_url(__FILE__); ?>/i/guNzr.qr.2.png" alt="" />
            </p>

            <?php if (1) : ?>
            <?php
                $plugin_data = get_plugin_data(__FILE__);

                $app_link = urlencode($plugin_data['PluginURI']);
                $app_title = urlencode($plugin_data['Name']);
                $app_descr = urlencode($plugin_data['Description']);
            ?>
            <h2>Share with friends</h2>
            <p>
                <!-- AddThis Button BEGIN -->
                <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                    <a class="addthis_button_facebook" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_twitter" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_google_plusone" g:plusone:count="false" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_linkedin" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_email" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_myspace" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_google" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_digg" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_delicious" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_stumbleupon" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_tumblr" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_favorites" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                    <a class="addthis_button_compact"></a>
                </div>
                <!-- The JS code is in the footer -->

                <script type="text/javascript">
                var addthis_config = {"data_track_clickback":true};
                var addthis_share = {
                  templates: { twitter: 'Check out {{title}} #wordpress #plugin at {{lurl}} (via @orbisius)' }
                }
                </script>
                <!-- AddThis Button START part2 -->
                <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js"></script>
                <!-- AddThis Button END part2 -->
            </p>
            <?php endif ?>

            <h2>Support &amp; Premium Plugins</h2>
            <div class="app-alert-notice">
                <p>
                ** NOTE: ** We have launched our Club Orbisius site: <a href="http://club.orbisius.com/" target="_blank" title="[new window]">http://club.orbisius.com/</a>
                which offers lots of free and premium plugins, video tutorials and more. The support is handled there as well.
                <br/>Please do NOT use the WordPress forums or other places to seek support.
                </p>
            </div>
        </div>
    <?php
}

/**
* adds some HTML comments in the page so people would know that this plugin powers their site.
 * @see http://wpseek.com/get_plugin_data/#source
*/
function orbisius_blank_slate_add_plugin_credits() {
    // pull only these vars
    $default_headers = array(
		'Name' => 'Plugin Name',
		'PluginURI' => 'Plugin URI',
	);

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $url = $plugin_data['PluginURI'];
    $name = $plugin_data['Name'];
    
    printf(PHP_EOL . PHP_EOL . '<!-- ' . "Powered by $name | URL: $url " . '-->' . PHP_EOL . PHP_EOL);
}

/**
 * Usage: orbisius_blank_slate_cleaner::remove_post('attachment');
 * Usage: orbisius_blank_slate_cleaner::remove_post('page');
 */
class orbisius_blank_slate_cleaner {
    /**
     * @see http://wordpress.org/support/topic/how-can-i-bulk-delete-all-posts-in-a-custom-post-type
     * @see http://codex.wordpress.org/Class_Reference/WP_Query#Properties
     */
    public static function remove_post($type, $just_count = 0) {
        $cnt = 0;
        
        $args = array(
            'post_type' => $type,
            'posts_per_page' => -1,
        );

        // inherit is necessary for attachments.
        if ($type == 'attachment') {
            $args['post_status'] = 'inherit';
        } elseif ($type == 'comment') {
            $comment_args = array();

            if ($just_count) {
               $comment_args['count'] = true;
               $comments = get_comments($comment_args);

               return $comments;
            } else { // del
                $comments_obj = get_comments($comment_args);
                
                foreach ($comments_obj as $comment_obj) {
                    $status = wp_delete_comment($comment_obj->comment_ID, true); // skip trash
                }

                $cnt = count($comments_obj);

                return $cnt;
            }
        }

        $loop = new WP_Query($args);

        if ($loop->have_posts()) {
            if ($just_count) {
               return $loop->post_count;
            }

            while ($loop->have_posts()) {
                $loop->the_post();
                
                wp_delete_post(get_the_ID(), true); // true -> skips the trash

                $cnt++;
            }
        }

        wp_reset_postdata();

        return $cnt;
    }

    /**
     *
     * @param type $taxonomy
     */
    public static function remove_terms($taxonomy) {
        $depts_objs = get_terms($taxonomy, 'orderby=name&hide_empty=0&hierarchical=0');

        foreach ($depts_objs as $term) {
           wp_delete_term($term->term_id, $taxonomy);
        }
    }
}

class orbisius_blank_slate_util {
    /**
     * Outputs a message (adds some paragraphs).
     */
    function msg($msg, $status = 0) {
        $msg = join("<br/>\n", (array) $msg);

        if (empty($status)) {
            $cls = 'app-alert-error';
        } elseif ($status == 1) {
            $cls = 'app-alert-success';
        } else {
            $cls = 'app-alert-notice';
        }

        $str = "<div class='$cls'><p>$msg</p></div>";

        return $str;
    }
}
