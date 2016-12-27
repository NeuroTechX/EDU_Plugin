<?php
/**
 * Options pages
 */

require_once('utils.php');


class EDUPluginAdmin
{

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
                settings_fields( 'github_settings' );
                do_settings_sections( 'EDUPlugin-settings' );
                submit_button();
                echo   '</form>
                        </div>';                
        }

        function EDUPlugin_page_init() {
                $option_name = 'github';

                $option_values = get_option( $option_name );

                $default_values = array (
                        'user' => '',
                        'token'  => '',
                        'readme_owner'  => '',
                        'readme_repo' => '',
                        'page_ids' => ''
                );

                $data = shortcode_atts( $default_values, $option_values );

                register_setting(
                        'github_settings', // Option group
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
                                'value'       => esc_attr( $data['user'] ),
                                'option_name' => $option_name,
                                'option_values' => $option_values
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
                                'value'       => esc_attr( $data['token'] ),
                                'option_name' => $option_name
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
                                'value'       => esc_attr( $data['readme_owner'] ),
                                'option_name' => $option_name
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
                                'value'       => esc_attr( $data['readme_repo'] ),
                                'option_name' => $option_name
                        )
                );
                add_settings_field(
                        'github_readme_pages',
                        'Page ID(s)',
                        array( $this, 'input_callback' ),
                        'EDUPlugin-settings',
                        'github_readme_plugin_settings_section',
                        array(
                                'label_for'   => 'pagesInput',
                                'name'        => 'page_ids',
                                'value'       => esc_attr( $data['page_ids'] ),
                                'option_name' => $option_name
                        )
                );
        }

        function input_callback( $args )
        {
                printf(
                        '<input id="%s" type="text" value="%s" name="%s[%s]" />',
                        $args['label_for'],
                        $args['value'],
                        $args['option_name'],
                        $args['name']
                );
        }

        function sanitize( $input ) {
                // $new_input = array();
                $new_input = $input;

                // Validations, if any

                return $new_input;
        }
}

?>
