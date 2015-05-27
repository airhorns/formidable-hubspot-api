<?php
if(!defined('ABSPATH')) die('You are not allowed to call this page directly.');

class FrmHubAPISettingsController{

    public static function load_hooks() {
        if ( is_admin() ) {
            add_action('admin_menu', 'FrmHubAPISettingsController::menu', 45);
            add_filter('admin_head-post.php', 'FrmHubAPISettingsController::highlight_menu');
            add_filter('admin_head-post-new.php', 'FrmHubAPISettingsController::highlight_menu');

            add_action('frm_add_settings_section', 'FrmHubAPISettingsController::add_settings_section');
            add_action('frm_add_form_settings_section', 'FrmHubAPISettingsController::add_form_settings', 10);

            add_filter('manage_edit-frm_api_columns', 'FrmHubAPISettingsController::manage_columns');
            add_filter('manage_edit-frm_api_sortable_columns', 'FrmHubAPISettingsController::sortable_columns');
            add_action('manage_frm_api_posts_custom_column', 'FrmHubAPISettingsController::manage_custom_columns', 10, 2);
            add_action('add_meta_boxes', 'FrmHubAPISettingsController::add_meta_boxes', 10, 2);

            add_action('wp_ajax_frmapi_insert_json', 'FrmHubAPISettingsController::default_json');
            add_action('wp_ajax_frmapi_test_connection', 'FrmHubAPISettingsController::test_connection');
        }

        add_action('init', 'FrmHubAPISettingsController::register_post_types', 0);
        add_action('save_post', 'FrmHubAPISettingsController::save_post');

        add_action('frm_after_create_entry', 'FrmHubAPISettingsController::send_new_entry', 41, 2);
		add_action('frm_after_update_entry', 'FrmHubAPISettingsController::send_updated_entry', 41, 2);
        add_action('frm_before_destroy_entry', 'FrmHubAPISettingsController::send_deleted_entry', 20, 2);
    }

    public static function register_post_types(){
        register_post_type('frm_api', array(
            'label' => __('Formidable WebHooks', 'frmapi'),
            'description' => '',
            'public' => false,
            'show_ui' => true,
            'show_in_nav_menus' => false,
            'show_in_menu' => false,
            'capability_type' => 'page',
            'supports' => array(
                'revisions',
            ),
            'labels' => array(
                'name' => __('WebHooks', 'frmapi'),
                'singular_name' => __('WebHook', 'frmapi'),
                'menu_name' => __('WebHooks', 'frmapi'),
                'edit' => __('Edit'),
                'search_items' => __('Search', 'formidable'),
                'not_found' => __('No WebHooks Found.', 'frmapi'),
                'add_new_item' => __('Add New WebHook', 'frmapi'),
                'edit_item' => __('Edit WebHook', 'frmapi'),
            )
        ) );
    }

    public static function menu() {
        add_submenu_page('formidable', 'Formidable | '. __('WebHooks', 'formidable'), __('WebHooks', 'formidable'), 'frm_change_settings', 'edit.php?post_type=frm_api');
    }

    public static function highlight_menu() {
        if ( !is_callable('FrmAppHelper::maybe_highlight_menu') ) {
            return;
        }

        FrmAppHelper::maybe_highlight_menu('frm_api');
    }

    public static function add_settings_section($sections) {
        if ( !isset($sections['api']) ){
            $sections['api'] = array('class' => 'FrmHubAPISettingsController', 'function' => 'show_api_key');
        }
        return $sections;
    }

    public static function add_form_settings($sections) {
        $sections['webhooks'] = array('class' => 'FrmHubAPISettingsController', 'function' => 'form_options_tab');
        return $sections;
    }

    public static function form_options_tab($values) {
        $hooks = get_posts( array(
            'meta_key'      => 'frm_form_id',
        	'meta_value'    => $values['id'],
        	'post_type'     => 'frm_api',
        ) );

        include( FrmHubAPIAppController::path() . '/views/settings/form_options.php' );
    }

    public static function show_api_key() {
        $api_key = get_site_option('frm_api_key');
        if ( !$api_key ) {
            $api_key = FrmHubAPIAppHelper::generate( 4, 4 );
            update_site_option('frm_api_key', $api_key);
        }
        require_once(FrmHubAPIAppController::path() . '/views/settings/api_key.php');
    }

    public static function manage_columns($columns) {
        self::load_scripts();

        //unset first so order will be correct
        unset($columns['date']);

        $offset = 1;
        $columns = array_slice($columns, 0, $offset, true) +
                    array('id' => __('ID')) +
                    array_slice($columns, $offset, NULL, true);

        $columns['form_id'] = __('Form', 'formidable');
        $columns['url'] = __('URL', 'formidable');
        $columns['title'] = __('Trigger');
        $columns['date'] = __('Date', 'formidable');

        return $columns;
    }

    public static function sortable_columns() {
        return array(
            'id'    => 'ID',
            'title' => 'post_title',
            'url'   => 'post_excerpt',
            'date'  => 'post_date',
        );
    }

    public static function manage_custom_columns($column_name, $id) {
        $val = '';
        $post = get_post($id);

        switch ( $column_name ) {
			case 'id':
			    $val = $id;
			    break;
			case 'name':
			    $val = FrmAppHelper::truncate(strip_tags($post->post_name), 100);
			    break;
			case 'url':
		        $val = strip_tags($post->post_excerpt);
		        break;
		    case 'form_id':
		        $form_id = get_post_meta($id, 'frm_form_id', true);
		        $form = FrmForm::getOne($form_id);
		        $val = $form ? strip_tags($form->name) : '';
		        break;
		    default:
			    $val = $column_name;
			break;
		}

        echo $val;
    }

    public static function add_meta_boxes($post_type, $post = false) {
        if($post_type != 'frm_api')
            return;

        self::load_scripts();

        add_meta_box('frm_apicontent', __('Options', 'formidable'), 'FrmHubAPISettingsController::mb_options', 'frm_api', 'normal', 'high');
    }

    public static function load_scripts() {
        $version = FrmAppHelper::plugin_version();

        wp_enqueue_script('bootstrap_tooltip');
        wp_enqueue_style('formidable-admin', FrmAppHelper::plugin_url() .'/css/frm_admin.css', array(), $version );
        wp_enqueue_script('formidable_admin', FrmAppHelper::plugin_url() .'/js/formidable_admin.js', array('jquery', 'jquery-ui-draggable', 'bootstrap_tooltip'), $version);
        wp_enqueue_script('formidable');
        FrmAppController::localize_script('admin');
    }

    public static function mb_options($post) {
        $hooks = array(
            'frm_after_create_entry' => __('New Entry', 'formidable'),
            'frm_after_update_entry' => __('Update Entry', 'formidable'),
            'frm_before_destroy_entry'  => __('Delete Entry', 'formidable'),
        );

        $post->frm_form_id = get_post_meta($post->ID, 'frm_form_id', true);
        if ( !$post->frm_form_id && isset($_GET['form_id']) ) {
            $post->frm_form_id = $_GET['form_id'];
        }
        $post->frm_api_key = get_post_meta($post->ID, 'frm_api_key', true);

        include(FrmHubAPIAppController::path() .'/views/settings/form.php');
    }

    public static function save_post($post_id) {
        //Verify nonce
        if ( empty($_POST) || (isset($_POST['frm_save_api']) && !wp_verify_nonce($_POST['frm_save_api'], 'frm_save_api_nonce')) || !isset($_POST['post_type']) || $_POST['post_type'] != 'frm_api' || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id) ) {
            return;
        }

        $post = get_post($post_id);
        if($post->post_status == 'inherit')
            return;

        update_post_meta($post->ID, 'frm_form_id', $_POST['frm_form_id']);
        update_post_meta($post->ID, 'frm_api_key', $_POST['frm_api_key']);
    }

    public static function default_json() {
        $form_id = (int) $_POST['form_id'];

        $entry = array(
            'id' => '[id]',
            'key' => '[key]',
            'form_id' => $form_id,
            //'updated_by' => '[updated-by]',
            'post_id' => '[post_id]',
            'created_at' => '[created-at]',
            'updated_at' => '[updated-at]',
            'is_draft' => '[is_draft]',
	    );
	    $meta = FrmEntriesController::show_entry_shortcode(array(
            'format' => 'array',
            'user_info' => false, 'default_email' => true,
            'form_id' => $form_id,
        ));

        $entry_array = $entry + (array) $meta;

        if ( version_compare(phpversion(), '5.4', '>=')) {
            echo json_encode($entry_array, JSON_PRETTY_PRINT);
        } else {
            echo json_encode($entry_array, true);
        }
        die();
    }

    public static function test_connection() {
        $url = $_POST['url'];
        $api_key = $_POST['key'];

        $headers = array( 'X-Hook-Test' => 'true');
        if ( !empty($api_key) ) {
            $headers['Authorization'] = 'Basic ' . base64_encode( $api_key .':x' );
        }

        $body = json_encode( array('test' => true) );

        $arg_array = array(
            'body'      => $body,
            'timeout'   => FrmHubAPIAppController::$timeout,
            'sslverify' => false,
            'headers' => $headers,
        );

        $resp = wp_remote_post( trim($url), $arg_array );
        $body = wp_remote_retrieve_body( $resp );

        if ( is_wp_error($resp) ) {
            $message = __('You had an error communicating with that API.', 'frmapi');
            if ( is_wp_error($resp) ) {
                $message .= ' '. $resp->get_error_message();
            }
            echo $message;
        } else if ( $body == 'error' || is_wp_error($body) ) {
            echo __('You had an HTTP error connecting to that API', 'frmapi');
        } else {
            if ( null !== ($json_res = json_decode($body, true)) ) {
                if ( is_array($json_res) && isset($json_res['error']) ) {
                    if ( is_array($json_res['error']) ) {
                        foreach ( $json_res['error'] as $e ) {
                            print_r($e);
                        }
                    } else {
                        echo $json_res['error'];
                    }
                } else {
                    if ( is_array($json_res) ) {
                        foreach ( $json_res as $k => $e ) {
                            if ( is_array($e) && isset($e['code']) && isset($e['message'])) {
                                echo $e['message'];
                            } else if ( is_array($e) ) {
                                echo implode('- ', $e);
                            } else if ( $k == 'success' && $e ) {
                                _e('Good to go!', 'frmapi');
                            } else {
                                echo $e .' ';
                            }

                            unset($k, $e);
                        }
                    } else {
                        echo $json_res;
                    }
                }
            } else if ( isset($resp['response']) && isset($resp['response']['code']) ) {
                if ( strpos($resp['response']['code'], '20') === 0 ) {
                    if ( isset($resp['response']['message']) ) {
                        echo $resp['response']['message'];
                    } else {
                        _e('Good to go!', 'frmapi');
                    }
                } else {
                    echo sprintf(__('There was a %1$s error: %2$s', 'formidable'), $resp['response']['code'], $resp['response']['message']);
                }
            } else {
                _e('Good to go!', 'frmapi');
            }
        }

        die();
    }

    public static function send_new_entry($entry_id, $form_id) {
        self::get_webhooks($entry_id, $form_id, 'frm_after_create_entry');
    }

    public static function send_updated_entry($entry_id, $form_id) {
        self::get_webhooks($entry_id, $form_id, 'frm_after_update_entry');
    }

    public static function send_deleted_entry($entry_id, $entry = false) {
        if ( !$entry ) {
            $entry = FrmEntry::getOne($entry_id);
            if ( !$entry ) {
                return;
            }
        }

        $form_id = $entry->form_id;
        self::get_webhooks($entry_id, $form_id, 'frm_before_destroy_entry');
    }

    public static function get_webhooks($entry_id, $form_id, $hook) {
        $hooks = get_posts( array(
            'meta_key'      => 'frm_form_id',
        	'meta_value'    => $form_id,
        	'post_type'     => 'frm_api',
        	'post_status'   => 'publish',
        	'post_title'    => $hook,
        ) );

        if ( !$hooks ) {
            return;
        }

        foreach ( $hooks as $k => $h ) {
            if ( $h->post_title != $hook ) {
                unset($hooks[$k]);
            }

            unset($k, $h);
        }
        FrmHubAPIAppController::send_webhooks($entry_id, $hooks);
    }
}
