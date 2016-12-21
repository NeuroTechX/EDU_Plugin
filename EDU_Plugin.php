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
require_once plugin_dir_path( __FILE__ ) . 'includes/GithubReadmePlugin.php';


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

        // GithubReadmePlugin
        $gh = new Github( 'NeuroTechX', 'Access Code' );
        $ghplugin = new GithubReadmePlugin( $gh, "NeuroTechX", "awesome-bci" ,
                                            array("Resources"), array("page"), "html" );
        $EDUPlugin->add_filter( 'the_content',
                                array( $ghplugin, 'callback_github_readme' ) );

        // EDUPluginAdmin
        $adminplugin = new EDUPluginAdmin();
	$EDUPlugin->add_action( 'admin_menu',
                                array( $adminplugin, 'EDUPlugin_add_plugin_page' ) );
	$EDUPlugin->add_action( 'admin_init',
                                array( $adminplugin, 'EDUPlugin_page_init' ) );

        /*
         * Start the plugin 
         */
        $EDUPlugin->run();
}

run_EDUPlugin();

?>
