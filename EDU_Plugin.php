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

require_once( 'utils.php' );


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

/**
 * Callback to set the content of a post to a repo's readme.
 */
class GithubReadmePlugin
{

	/**
	 * @gh			Github object (see utils.php)
	 * @owner		Repo's owner
	 * @repo		Repo Name
	 * @post_title		Array of post titles to apply this plugin to
	 * @post_type		Array of post types to apply this plugin to
	 * @output_type		Output type of the content
	 */
	function __construct( $gh, $owner, $repo, $post_title, $post_type, $output_type="html" ) {
		$this->gh = $gh;
		$this->owner = $owner;
		$this->repo = $repo;
		$this->post_title = $post_title;
		$this->post_type = $post_type;
		$this->output_type = $output_type;
	}

	function callback_github_readme($content) {
		$p = get_post();
		if ( in_array( $p->post_title, $this->post_title ) && in_array( $p->post_type, $this->post_type ) ) {
			$r = $this->gh->get_readme( $this->owner, $this->repo );
			if ( $this->output_type == "html" ) {
				$Parsedown = new Parsedown();
				$Parsedown->setMarkupEscaped( true );
				$html = $Parsedown->text( $r );
				$content = Github::add_anchors_to_headings( $html );
			} else {
				$content = $r;
			}
			return $content;
		}
	}
}

/* Create the loader */
$EDUPlugin = new EDUPlugin();

/* Set up the plugins */
$gh = new Github( 'NeuroTechX', 'Access Code' );
$ghplugin = new GithubReadmePlugin( $gh, "NeuroTechX", "awesome-bci" ,
				    array("Resources"), array("page"), "html" );

/* Load the hooks (actions and filters) */
$EDUPlugin->add_filter( 'the_content',
			array( $ghplugin, 'callback_github_readme' )
);

/* Start the plugin */
$EDUPlugin->run();


?>
