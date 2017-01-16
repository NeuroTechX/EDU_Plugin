<?php
/**
 * @package EDU_Plugin
 * @version 1.0
 */
/*
   Plugin Name: NeuroTechEdu Plugin
   Plugin URI: https://github.com/NeuroTechX/EDU_Plugin
   Description: NeuroTechEdu Plugin
   Author: Kenny Lee Sin Cheong
   Version: 1.0
   Author URI: ""
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/utils.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/EDUPluginAdmin.php';

// Load the plugins (files should end with *Plugin.php)
foreach ( scandir( plugin_dir_path( __FILE__ ) . 'includes/' ) as $filename ) {
        $path = plugin_dir_path( __FILE__ ) . 'includes/' . $filename;
        if ( is_file( $path ) && StringUtils::endsWith( $path, 'Plugin.php' ) ) {
                require_once( $path );
        }
}


/**
 * The main class responsible for loading the hooks.
 */
class EDUPlugin
{
        
        function __construct() {
                $this->actions = array();
                $this->filters = array();
        }

        function add_filter( $hook, $callback ) {
                $this->filters = $this->add( $this->filters, $hook, $callback );
        }

        function add_action( $hook, $callback ) {
                $this->actions = $this->add( $this->actions, $hook, $callback );
        }

        function add( $hooks, $hook, $callback ) {
                $hooks[] = array(
                        'hook'          => $hook,
                        'callback'      => $callback,
                );
                return $hooks;
        }

        function run() {
                foreach( $this->filters as $hook ) {
                        add_filter( $hook['hook'], $hook['callback'] );
                }
                
                foreach( $this->actions as $hook ) {
                        add_action( $hook['hook'], $hook['callback'] );
                }
        }
}


/**
 * Plugins callback are wrapped in class to allow passing arguments.
 * Another way to pass arguments to callback is to use closures (anonymous functions).
 */

function run_EDUPlugin() {      
        /* 
         * Create the loader 
         */
        $EDUPlugin = new EDUPlugin();


        /*
         * Set up the plugins and load the hooks.
         */
        $EDUPlugin_options = get_option( 'EDUPlugin' );

        // Github options
        $github_option_name = 'github';
        $user = isset( $EDUPlugin_options[$github_option_name]['user'] ) ?
                $EDUPlugin_options[$github_option_name]['user'] : "";
        $token = isset( $EDUPlugin_options[$github_option_name]['token'] ) ?
                 $EDUPlugin_options[$github_option_name]['token'] : "";
        $owner = isset( $EDUPlugin_options[$github_option_name]['readme_owner'] ) ?
                 $EDUPlugin_options[$github_option_name]['readme_owner'] : "";
        $repo = isset( $EDUPlugin_options[$github_option_name]['readme_repo'] ) ?
                $EDUPlugin_options[$github_option_name]['readme_repo'] : "";
        $github_page_ids = isset( $EDUPlugin_options[$github_option_name]['page_ids'] ) ?
                           explode( ',', $EDUPlugin_options[$github_option_name]['page_ids'] ) : array();
        foreach( $github_page_ids as $id ) {
                $id = intval( trim($id) );
        }
        
        // GithubReadmePlugin
        $gh = new Github( $user, $token );
        $ghplugin = new GithubReadmePlugin( $gh, $owner, $repo, $github_page_ids, "html" );
        $EDUPlugin->add_filter( 'the_content',
                                array( $ghplugin, 'callback_github_readme' ) );
        add_shortcode( 'github_readme', array( $ghplugin, 'generate_shortcode' ) );

        // Meetup options
        $meetup_option_name = 'meetup';
        $meetup_key = isset( $EDUPlugin_options[$meetup_option_name]['key'] ) ?
                      $EDUPlugin_options[$meetup_option_name]['key'] : "";
        $groups = isset( $EDUPlugin_options[$meetup_option_name]['groups'] ) ?
                  explode( ',', $EDUPlugin_options[$meetup_option_name]['groups'] ) : array();
        foreach( $groups as $group ) {
                $group = trim($group);
        }
        $meetup_page_ids = isset( $EDUPlugin_options[$meetup_option_name]['page_ids'] ) ?
                           explode( ',', $EDUPlugin_options[$meetup_option_name]['page_ids'] ) : array();
        foreach( $meetup_page_ids as $id ) {
                $id = intval( trim($id) );
        }

        // MeetupEventPlugin
        $mu = new Meetup( $meetup_key );
        $muplugin = new MeetupEventPlugin( $mu, $meetup_page_ids, $groups );
        $EDUPlugin->add_filter( 'the_content',
                                array( $muplugin, 'callback_meetup_event' ) );
        add_shortcode( 'meetup_events', array( $muplugin, 'generate_shortcode' ) );

        // Eventbrite options
        $eventbrite_option_name = 'eventbrite';
        $eventbrite_key = isset( $EDUPlugin_options[$eventbrite_option_name]['key'] ) ?
                          $EDUPlugin_options[$eventbrite_option_name]['key'] : "";
        $eventbrite_organizer_ids = isset( $EDUPlugin_options[$eventbrite_option_name]['organizer_ids'] ) ?
                                    explode( ',', $EDUPlugin_options[$eventbrite_option_name]['organizer_ids'] ) : array();
        foreach( $eventbrite_organizer_ids as $id ) {
                $id = intval( trim($id) );
        }
        $eventbrite_page_ids = isset( $EDUPlugin_options[$eventbrite_option_name]['page_ids'] ) ?
                               explode( ',', $EDUPlugin_options[$eventbrite_option_name]['page_ids'] ) : array();
        foreach( $eventbrite_page_ids as $id ) {
                $id = intval( trim($id) );
        }
        
        // EventbriteEventPlugin
        $eb = new Eventbrite( $eventbrite_key );
        $ebplugin = new EventbriteEventPlugin( $eb, $eventbrite_organizer_ids, $eventbrite_page_ids );
        $EDUPlugin->add_filter( 'the_content',
                                array( $ebplugin, 'callback_eventbrite_event' ) );
        add_shortcode( 'eventbrite_events', array( $ebplugin, 'generate_shortcode' ) );

        // EDUPluginAdmin
        $adminplugin = new EDUPluginAdmin();
        $EDUPlugin->add_action( 'admin_menu',
                                array( $adminplugin, 'EDUPlugin_add_plugin_page' ) );
        $EDUPlugin->add_action( 'admin_init',
                                array( $adminplugin, 'EDUPlugin_page_init' ) );
        $EDUPlugin->add_filter( 'plugin_action_links_' . plugin_basename(__FILE__),
                                array( $adminplugin, 'EDUPlugin_action_links') );

        /*
         * Start the plugin 
         */
        $EDUPlugin->run();
}

run_EDUPlugin();

?>
