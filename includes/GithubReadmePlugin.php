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

?>
