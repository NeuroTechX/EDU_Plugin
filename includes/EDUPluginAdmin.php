<?php
/**
 * Options pages
 */

require_once('utils.php');


/**
 * Settings page for the EDUPlugin.
 * For Wordpress specific function reference, see:
 *
 *      https://codex.wordpress.org/Function_Reference
 */
class EDUPluginAdmin
{

        /**
         * Callback to the 'admin_menu' hook.
         */
        function EDUPlugin_add_plugin_page() {
                add_options_page(
                        'EDUPlugin Settings',
                        'EDUPlugin',
                        'manage_options',
                        'EDUPlugin-settings',
                        array( $this, 'EDUPlugin_options' )
                );
        }

        function EDUPlugin_options() {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
                }
                echo   '<div class="wrap">
                        <form method="post" action="options.php">';
                settings_fields( 'eduplugin_settings' );
                do_settings_sections( 'EDUPlugin-settings' );
                submit_button();
                echo   '</form>
                        </div>';                
        }

        /**
         * Callback to the 'admin_init' hook.
         *
         * Initialize and register the settings.
         *
         * See:
         *      https://codex.wordpress.org/Function_Reference/add_settings_section
         *      https://codex.wordpress.org/Function_Reference/add_settings_field
         */
        function EDUPlugin_page_init() {
                $option_name = 'EDUPlugin';
                $option_values = get_option( $option_name );
                
                $github_option_name = 'github';
                $github_option_values = $option_values[$github_option_name];
                $github_default_values = array (
                        'user' => '',
                        'token'  => '',
                        'readme_owner'  => '',
                        'readme_repo' => ''
                );
                $github_data = shortcode_atts( $github_default_values, $github_option_values );

                $meetup_option_name = 'meetup';
                $meetup_option_values = $option_values[$meetup_option_name];
                $meetup_default_values = array (
                        'key' => '',
                        'groups'  => ''
                );
                $meetup_data = shortcode_atts( $meetup_default_values, $meetup_option_values );

                $eventbrite_option_name = 'eventbrite';
                $eventbrite_option_values = $option_values[$eventbrite_option_name];
                $eventbrite_default_values = array (
                        'key' => '',
                        'organizer_ids'  => ''
                );
                $eventbrite_data = shortcode_atts( $eventbrite_default_values, $eventbrite_option_values );             

                register_setting(
                        'eduplugin_settings', // Option group
                        $option_name,      // Option name
                        array( $this, 'sanitize' ) // Callback
                );
                
                // General Github settings
                $print_github_auth = new PrintSection('Authorization');
                add_settings_section(
                        'github_settings_section',
                        'Github Settings',
                        array( $print_github_auth, 'print_section_info' ),
                        'EDUPlugin-settings'
                );
                add_settings_field(
                        'github_user',
                        'User',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'github_settings_section',
                        array(
                                'label_for'   => 'userInput',
                                'name'        => 'user',
                                'value'       => esc_attr( $github_data['user'] ),
                                'option_name' => $option_name,
                                'suboption_name' => $github_option_name
                        )
                );
                add_settings_field(
                        'github_token',
                        'Token',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'github_settings_section',
                        array(
                                'label_for'   => 'tokenInput',
                                'name'        => 'token',
                                'value'       => esc_attr( $github_data['token'] ),
                                'option_name' => $option_name,
                                'suboption_name' => $github_option_name
                        )
                );
                
                // Github readme plugin
                $print_github_readme = new PrintSection('Repo Info');
                add_settings_section(
                        'github_readme_plugin_settings_section',
                        'Github Readme Plugin Settings',
                        array( $print_github_readme, 'print_section_info' ),
                        'EDUPlugin-settings'
                );
                add_settings_field(
                        'github_readme_owner',
                        'Repo Owner',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'github_readme_plugin_settings_section',
                        array(
                                'label_for'   => 'ownerInput',
                                'name'        => 'readme_owner',
                                'value'       => esc_attr( $github_data['readme_owner'] ),
                                'option_name' => $option_name,
                                'suboption_name' => $github_option_name
                        )
                );
                add_settings_field(
                        'github_readme_repo',
                        'Repo',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'github_readme_plugin_settings_section',
                        array(
                                'label_for'   => 'repoInput',
                                'name'        => 'readme_repo',
                                'value'       => esc_attr( $github_data['readme_repo'] ),
                                'option_name' => $option_name,
                                'suboption_name' => $github_option_name
                        )
                );

                // Meetup Settings
                $print_meetup_auth = new PrintSection('Authorization');
                add_settings_section(
                        'meetup_settings_section',
                        'Meetup Settings',
                        array( $print_meetup_auth, 'print_section_info' ),
                        'EDUPlugin-settings'
                );
                add_settings_field(
                        'meetup_key',
                        'API Key',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'meetup_settings_section',
                        array(
                                'label_for'   => 'meetupKeyInput',
                                'name'        => 'key',
                                'value'       => esc_attr( $meetup_data['key'] ),
                                'option_name' => $option_name,
                                'suboption_name' => $meetup_option_name
                        )
                );
                add_settings_field(
                        'meetup_groups',
                        'Groups',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'meetup_settings_section',
                        array(
                                'label_for'   => 'meetupGroupsInput',
                                'name'        => 'groups',
                                'value'       => esc_attr( $meetup_data['groups'] ),
                                'option_name' => $option_name,
                                'suboption_name' => $meetup_option_name
                        )
                );

                // Meetup Settings
                $print_eventbrite_auth = new PrintSection('Authorization');
                add_settings_section(
                        'eventbrite_settings_section',
                        'Eventbrite Settings',
                        array( $print_eventbrite_auth, 'print_section_info' ),
                        'EDUPlugin-settings'
                );
                add_settings_field(
                        'evenbrite_key',
                        'Key',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'eventbrite_settings_section',
                        array(
                                'label_for'   => 'eventbriteKeyInput',
                                'name'        => 'key',
                                'value'       => esc_attr( $eventbrite_data['key'] ),
                                'option_name' => $option_name,
                                'suboption_name' => $eventbrite_option_name
                        )
                );
                add_settings_field(
                        'eventbrite_organizer_ids',
                        'Organizer ID(s)',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'eventbrite_settings_section',
                        array(
                                'label_for'   => 'eventbriteOrganizersInput',
                                'name'        => 'organizer_ids',
                                'value'       => esc_attr( $eventbrite_data['organizer_ids'] ),
                                'option_name' => $option_name,
                                'suboption_name' => $eventbrite_option_name
                        )
                );
        }

        /**
         * Outputs an <input> element of type text.
         *
         * @args        Assoc array for the <input> attributes.
         */
        function input_callback( $args )
        {
                printf(
                        '<input id="%s" type="text" value="%s" name="%s[%s][%s]" />',
                        $args['label_for'],
                        $args['value'],
                        $args['option_name'],
                        $args['suboption_name'],
                        $args['name']
                );
        }

        /**
         * Callback to add the links on the plugin page.
         * (Next to Activate/Deactivate, Edit, ...)
         */
        function EDUPlugin_action_links ( $links ) {
                $links[] = '<a href="'. 
                           esc_url( get_admin_url(null, 'options-general.php?page=EDUPlugin-settings') ) .
                           '">Settings</a>';
                return $links;
        }

        /**
         * Sanitize the use inputs.
         */
        function sanitize( $input ) {
                // $new_input = array();
                $new_input = $input;

                // Validations, if any

                return $new_input;
        }
}

?>
