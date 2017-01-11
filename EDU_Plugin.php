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

        // Github options
        $github_options = get_option('github');
        $user = isset( $github_options['user'] ) ? $github_options['user'] : "";
        $token = isset( $github_options['token'] ) ? $github_options['token'] : "";
        $owner = isset( $github_options['readme_owner'] ) ? $github_options['readme_owner'] : "";
        $repo = isset( $github_options['readme_repo'] ) ? $github_options['readme_repo'] : "";
        $github_page_ids = isset( $github_options['page_ids'] ) ?
                           explode( ',', $github_options['page_ids'] ) : array();
        foreach( $github_page_ids as $id ) {
                $id = intval( trim($id) );
        }
        
        // GithubReadmePlugin
        $gh = new Github( $user, $token );
        $ghplugin = new GithubReadmePlugin( $gh, $owner, $repo, $github_page_ids, "html" );
        $EDUPlugin->add_filter( 'the_content',
                                array( $ghplugin, 'callback_github_readme' ) );

        // Meetup options
        $meetup_options = get_option('meetup');
        $key = isset( $meetup_options['key'] ) ? $meetup_options['key'] : "";
        $groups = isset( $meetup_options['groups'] ) ?
                  explode( ',', $meetup_options['groups'] ) : array();
        foreach( $groups as $group ) {
                $group = trim($group);
        }
        $meetup_page_ids = isset( $meetup_options['page_ids'] ) ?
                           explode( ',', $meetup_options['page_ids'] ) : array();
        foreach( $meetup_page_ids as $id ) {
                $id = intval( trim($id) );
        }

        // MeetupEventPlugin
        $mu = new Meetup( '36a2b15417c636034647713a656d64' );
        $muplugin = new MeetupEventPlugin( $mu, $meetup_page_ids, $groups );
        $EDUPlugin->add_filter( 'the_content',
                                array( $muplugin, 'callback_meetup_event' ) );

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
