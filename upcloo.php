<?php
/*
Plugin Name: UpCloo WP Plugin
Plugin URI: http://www.upcloo.com/
Description: UpCloo is a cloud based and fully hosted service that helps you  to create incredible and automatic correlations between contents of your website.
Version: 1.3.1
Author: UpCloo Ltd.
Author URI: http://www.upcloo.com/
License: MIT
*/

/*
 * Copyright (C) 2012 by UpCloo Ltd.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
load_plugin_textdomain('wp_upcloo', null, basename(dirname(__FILE__)) . '/languages');

require_once dirname(__FILE__) . '/UpCloo/Widget/Partner.php';

require_once dirname(__FILE__) . '/UpCloo/SView.php';

/* Runs when plugin is activated */
register_activation_hook(WP_PLUGIN_DIR . '/upcloo/upcloo.php', 'upcloo_install');

//Only secure protocol on post/page publishing (now is beta test... no https)
define("UPCLOO_SITEKEY", "upcloo_sitekey");
define("UPCLOO_CONFIG_ID", "upcloo_config_id");

define("UPCLOO_RSS_FEED", "http://www.upcloo.com/contenuti/rss/0/news.xml");
define('UPCLOO_POSTS_TYPE', "upcloo_posts_type");

define('UPCLOO_MENU_SLUG', 'upcloo_options_menu');
define('UPCLOO_MENU_ADVANCED_SLUG', 'upcloo_menu_advanced');

define('UPCLOO_VIEW_PATH', dirname(__FILE__) . '/views');

define('UPCLOO_OPTION_CAPABILITY', 'manage_options');

define('UPCLOO_GAN_TRACKER', 'upcloo_gan_tracker');
define('UPCLOO_MANUAL_PLACEHOLDER', 'upcloo_manual_placeholder');

add_action('widgets_init', create_function( '', 'register_widget("UpCloo_Widget_Partner");'));
wp_register_sidebar_widget("upcloo_widget", __("UpCloo", "wp_upcloo"), "upcloo_direct_widget", array('description' => __('Use UpCloo as a widget instead at the end of the body', 'wp_upcloo')));
add_action('wp_dashboard_setup', 'upcloo_add_dashboard_widgets' );

add_action('admin_notices', 'upcloo_show_needs_attention');

add_filter('the_content', 'upcloo_content');
add_filter('admin_footer_text', "upcloo_admin_footer");

add_action( 'admin_menu', 'upcloo_plugin_menu' );

function upcloo_is_configured()
{
    $postTypes = get_option(UPCLOO_POSTS_TYPE);
    if (!is_array($postTypes)) {
        $postTypes = array();
    }

    if (
        trim(get_option(UPCLOO_SITEKEY)) != '' &&
        count($postTypes) > 0
    ) {
        return true;
    } else {
        return false;
    }
}

function upcloo_show_needs_attention()
{
    if (!upcloo_is_configured()) {
        echo '<div class="updated">
        <p>' . __("Remember that your have to configure UpCloo Plugin: ", "wp_upcloo") . ' <a href="admin.php?page=upcloo_options_menu">'.__("Config Page", "wp_upcloo") . '</a> - <a href="admin.php?page=upcloo_menu_advanced">'.__("Advanced Config Page", "wp_upcloo").'</a></p></div>';
    }
}

function upcloo_check_menu_capability()
{
    if ( !current_user_can(UPCLOO_OPTION_CAPABILITY) )  {
        wp_die(__( 'You do not have sufficient permissions to access this page.', "wp_upcloo"));
    }
}

//Start menu
function upcloo_plugin_menu()
{
    add_menu_page('UpCloo', __('UpCloo', "wp_upcloo"), UPCLOO_OPTION_CAPABILITY, UPCLOO_MENU_SLUG, 'upcloo_plugin_options', plugins_url()."/upcloo/assets/u.png");
    add_submenu_page(UPCLOO_MENU_SLUG, "Advanced Configs", __("Advanced Configurations", "wp_upcloo"), UPCLOO_OPTION_CAPABILITY, UPCLOO_MENU_ADVANCED_SLUG, UPCLOO_MENU_ADVANCED_SLUG);
}

function upcloo_plugin_options()
{
    upcloo_check_menu_capability();
    include realpath(dirname(__FILE__)) . "/options/app-config-options.php";
}

function upcloo_menu_advanced()
{
    upcloo_check_menu_capability();
    include realpath(dirname(__FILE__)) . '/options/app-advanced-config-options.php';
}
//End menu

// Create the function to output the contents of our Dashboard Widget
function upcloo_dashboard_widget_function()
{
    // Display whatever it is you want to show
    $xml = @simplexml_load_file(UPCLOO_RSS_FEED);

    if ($xml !== false) {
        $blogInfo = get_bloginfo();
        $blogTitle = urlencode(strtolower($blogInfo));

        $view = new UpCloo_SView();
        $view->setViewPath(UPCLOO_VIEW_PATH);

        $view->xml = $xml;
        $view->blogTitle = $blogTitle;
        $view->blogInfo = $blogInfo;

        echo $view->render("dashboard-widget.phtml");
    }
}

// Create the function use in the action hook

function upcloo_add_dashboard_widgets()
{
    wp_add_dashboard_widget('upcloo_dashboard_widget', __('UpCloo News Widget', "wp_upcloo"), 'upcloo_dashboard_widget_function');
}

function upcloo_admin_footer($text)
{
    return $text . " • <span><a target=\"_blank\" href='http://www.upcloo.com'>UpCloo Inside</a></span>";
}

/**
 * Configure options
 *
 * This method configure options for UpCLoo
 * it is called during UpCloo plugin activation
 */
function upcloo_install() {
    /* Creates new database field */
    add_option(UPCLOO_SITEKEY, "", "", "yes");
    add_option(UPCLOO_CONFIG_ID, "upcloo_1000", "", "yes");
    add_option(UPCLOO_POSTS_TYPE, array("post"), '', 'yes');
    add_option(UPCLOO_MANUAL_PLACEHOLDER, false, "", "yes");
}

function upcloo_get_default_image() {
    return plugins_url() . "/upcloo/assets/no-image.gif";
}
/**
 * Get content on public side
 *
 * Get the content on public side with UpCloo
 * related posts or other contents.
 *
 * You can disable the post body using the $noPostBody
 * parameter. This parameter is used only if you
 * call the UpCloo call back by hand.
 *
 * @param string $content The original post content
 * @param boolean $noPostBody disable original post content into response
 *
 * @return string The content rewritten using UpCloo
 */
function upcloo_content($content, $noPostBody = false)
{
    global $post;
    global $current_user;

    get_currentuserinfo();

    $postTypes = get_option(UPCLOO_POSTS_TYPE);
    if (!is_array($postTypes)) {
        $postTypes = array();
    }

    if (is_singular($post) && (in_array($post->post_type, $postTypes)) && !is_active_widget(false,false,'upcloo_widget')) {
        $view = new UpCloo_SView();
        $view->setViewPath(UPCLOO_VIEW_PATH);

        $view->permalink = get_permalink($post->ID);
        $view->sitekey = get_option(UPCLOO_SITEKEY);
        $view->configId = get_option(UPCLOO_CONFIG_ID, "upcloo_1000");

        $content .= $view->render("upcloo-js-sdk.phtml");
    }


    return $content;
}

function upcloo_direct_widget()
{
    global $post;

    $postTypes = get_option(UPCLOO_POSTS_TYPE);
    if (!is_array($postTypes)) {
        $postTypes = array();
    }

    if (is_singular($post) && (in_array($post->post_type, $postTypes))) {
        $sitekey = get_option("upcloo_sitekey");

        $title = $instance["upcloo_v_title"];
        $permalink = get_permalink($post->ID);

        $view = new UpCloo_SView();
        $view->setViewPath(UPCLOO_VIEW_PATH);

        $view->permalink = get_permalink($post->ID);
        $view->sitekey = get_option(UPCLOO_SITEKEY);
        $view->configId = get_option(UPCLOO_CONFIG_ID, "upcloo_1000");

        echo $view->render("upcloo-js-sdk.phtml");
    }
}
