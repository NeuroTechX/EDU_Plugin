<?php
/**
 * Callback to set the content of a post to a repo's readme.
 */
class GithubReadmePlugin
{

        /**
         * @gh                  Github object (see utils.php)
         * @owner               Repo's owner
         * @repo                Repo Name
         * @post_title          Array of post titles to apply this plugin to
         * @post_type           Array of post types to apply this plugin to
         * @output_type         Output type of the content
         */
        function __construct( $gh, $owner, $repo, $post_ids, $output_type="html" ) {
                $this->gh = $gh;
                $this->owner = $owner;
                $this->repo = $repo;
                $this->post_ids = $post_ids;
                $this->output_type = $output_type;
        }

        /**
         * Generate the output as html
         *
         * @data:       The markdown formatted readme
         *
         */
        function generate_html( $data ) {
                $Parsedown = new Parsedown();
                $Parsedown->setMarkupEscaped( true );
                $html = $Parsedown->text( $data );
                
                $repo_url = $this->gh->get_repo_url( $this->owner, $this->repo );

                // Create link to repo
                $dom = new DOMDocument();
                $p = $dom->createElement( 'p' );
                $p->textContent = "To contribute to this list, go to ";
                $a = $dom->createElement( 'a' );
                $a->setAttribute( 'href', $repo_url );
                $a->textContent = 'Github';
                $p->appendChild( $a );
                $dom->appendChild( $p );

                $content = $dom->saveHTML();
                $content .= HTMLUtils::add_target_blank_to_links(
                        HTMLUtils::add_anchors_to_headings( $html )
                );
                return $content;
        }

        function generate_shortcode( $atts ) {
                $r = $this->gh->get_readme( $this->owner, $this->repo );
                return $this->generate_html( $r );
        }

        /**
         * The wordpress callback.
         */
        function callback_github_readme($content) {
                $p = get_post();
                if ( in_array( $p->ID, $this->post_ids ) )  {
                        $r = $this->gh->get_readme( $this->owner, $this->repo );
                        if ( $this->output_type == "html" ) {
                                //$content = $dom->saveHTML();
                                $content .= $this->generate_html( $r );
                        } else {
                                $content = $r;
                        }
                }
                return $content;
        }
}

?>
