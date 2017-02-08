# EDU Plugin

Contributors: Kenny Lee Sin Cheong

Requires at least: 1.0

Stable tag: 1.0

Tested up to: 1.0


## Description
The EDU Plugin used by the NeuroTechEDU website to integrate different services from third-party APIs into wordpress.
It is managed as if it was a single plugin but is really multiple plugins providing different types of integration with wordpress (Github, Meetup, ...).


## Installation
##### Method 1: From the Admin Page
To install the plugin:
1. ZIP the content of this repository.
2. From the Admin page, go to the plugin tab and select *Add New*.
3. Click on the *Upload Plugin* button and select the ZIP file.
4. Activate the plugin.

##### Alternative Method
1. On your host, create a new folder (ideally named after the plugin) in the     `wp-content/plugins` directory.
2. Manually copy the files of this repository to the new folder. e.g. Through `ftp`, `scp`, ...
3. Activate the plugin via the Admin page.


## Extending the EDUPlugin with new plugins

The EDUPlugin is just a loader used to load the plugins in the includes/ directory, including the admin settings page.
By convention, the plugins in `includes/` are named **_<plugin_name>Plugin.php_** (such files are "php included" automatically).

To write additional plugins, the callbacks are wrapped in a class to allow different configurations through the class's member attributes.

The class contains two methods:
- **The constructor**
- **The callback**
- **The shortcode method**
    
The class constructor is used to set the states of the plugin.
The callback is the function that gets called when the wordpress hook gets triggered.
The shortcode method is the function that generates the page's content.
See `includes/GithubReadmePlugin.php` as an example.

The plugin is then loaded in `EDU_Plugin.php` .
To load a plugin, instantiate an instance of that plugin in the *`run_EDUPlugin()`* function of the `EDU_Plugin.php` file, along with the necessary parameters that the class constructor requires.

Depending on the plugin's use case, call *`add_action`* or *`add_filter`* member method of the EDUPlugin instance, passing the hook, and an array containing an instance of the plugin and the name of the callback.

For plugins involving generating content in a page or post, it is preferable and more flexible to use the shortcode api.
In this case, instead of a callback function in the plugin class, we use a function to generate the desired output and use the add_shortcode() interface.
See (https://codex.wordpress.org/Shortcode_API)[https://codex.wordpress.org/Shortcode_API]

e.g. 
```php
$ghplugin = new GithubReadmePlugin( $gh, $owner, $repo, $page_ids, "html" );
$EDUPlugin->add_filter( 'the_content',
                        array( $ghplugin, 'callback_github_readme' ) );
add_shortcode( 'github_readme',
               array( $ghplugin, 'generate_shortcode' ) );                       
```

To add additional options to the plugin's admin page, add the settings' section and field in the
EDUPlugin_page_init callback of the EDUPluginAdmin class in EDUPluginAdmin.php.

See:
- https://codex.wordpress.org/Function_Reference/add_settings_section
- https://codex.wordpress.org/Function_Reference/add_settings_field


## Plugins
### GithubReadmePlugin

The GithubReadmePlugin set the content of a post (post or page) to the content of a Github Repo's readme.

The parameters that must be set from the settings page are:
- User accessing Github's API
- User token
- Repo owner
- Repo name

To use the plugin, add the content to a post using the following shortcode: `[github_readme class="foo"]`
where class will set the class of the div that wraps the content of the readme.

### MeetupEventPlugin

The MeetupEventPlugin set the content of a post (post or page) to a list of Meetup events.

The parameters that must be set from the settings page are:
- API key
- Event groups

To use the plugin, add the content to a post using the following shortcode: `[meetup_events class="foo"]`
where class will set the class of the div that wraps each items of the list of events.

### EventbriteEventPlugin

The EventbriteEventPlugin set the content of a post (post or page) to a list of Eventbrite events.

The parameters that must be set from the settings page are:
- API key
- A list of comma separated organizer ids to get the events from

To use the plugin, add the content to a post using the following shortcode: `[eventbrite_events class="foo"]`
where class will set the class of the div that wraps each items of the list of events.

### AllEventsPlugin

The AllEventsPlugin set the content of a post (post or page) to a list of all events (Meetup U Evenbrite).

This plugin can also be used to draw a map (Google Maps) with markers.

To use the plugin, add the content to a post using the following shortcode: `[all_events class="foo"]`
where class will set the class of the div that wraps each items of the list of events.

To add a map of events:
`[event_map id="foo"]`
where id is the id of the div where the map will be drawn.

# Resources
Some useful resources to write plugins:
- [Introduction to Plugin Development](https://developer.wordpress.org/plugins/intro/)
- [Writing a Plugin](https://codex.wordpress.org/Writing_a_Plugin)
- [Plugin API](https://codex.wordpress.org/Plugin_API)
- [Shortcode API](https://codex.wordpress.org/Shortcode_API)
- [Creating Option Pages](https://codex.wordpress.org/Creating_Options_Pages)
- Useful references:
    - [Filter Reference](https://codex.wordpress.org/Plugin_API/Filter_Reference)
    - [Action Reference](https://codex.wordpress.org/Plugin_API/Action_Reference)
    - [Function Reference](https://codex.wordpress.org/Function_Reference)