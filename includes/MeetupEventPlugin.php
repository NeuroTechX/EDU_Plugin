<?php

class MeetupEventPlugin
{
        
        /**
         * @mu          Meetup object (see utils.php)
         */
        function __construct( $mu, $post_ids, $groups ) {
                $this->mu = $mu;
                $this->post_ids = $post_ids;
                $this->groups = $groups;
        }

        /**
         * Generate the output as html
         *
         * @data:       Array of meetup events objects
         *
         */
        function generate_html( $data, $atts ) {
                $dom = new DOMDocument();
                $h1 = $dom->createElement( 'h1', "Upcoming Meetup Events" );
                $dom->appendChild( $h1 );
                $content = '';
                foreach ( $data as $i => $event ) {
                        $epoch = $event['time'] / 1000;
                        $datetime = date("j M, Y, g:i A - T", $epoch);
                        $link = $event['link'];
                        $title = $event['name'];
                        $description = $event['description'];
                        $group = $event['group']['name'];

                        $class = isset( $atts['class'] ) ? $atts['class'] : '';
                        $content .= HTMLUtils::print_event_html( $title, $description, $datetime, $group, $link, $class );
                }
                return $dom->saveHTML() . $content;
        }

        function generate_shortcode( $atts ) {
                $events = array();
                $a = shortcode_atts(
                        array(
                                'class' => ''
                        ),
                        $atts
                );
                foreach ( $this->groups as $group ) {
                        $r = $this->mu->get_events( $group );
                        $events = array_merge( $events, $r );
                }
                usort($events, function( $a, $b ) {
                        if ( $a['time'] == $b['time'] ) {
                                return 0;
                        }
                        return ( $a['time'] < $b['time'] ) ? -1 : 1;
                } );
                return $this->generate_html( $events, $atts );
        }

        function callback_meetup_event( $content ) {
                $p = get_post();
                $events = array();
                if ( in_array( $p->ID, $this->post_ids ) )  {
                        foreach ( $this->groups as $group ) {
                                $r = $this->mu->get_events( $group );
                                $events = array_merge( $events, $r );
                        }
                        usort($events, function( $a, $b ) {
                                if ( $a['time'] == $b['time'] ) {
                                        return 0;
                                }
                                return ( $a['time'] < $b['time'] ) ? -1 : 1;
                        } );
                        $content .= $this->generate_html( $events );
                }
                return $content;
        }
}

?>
