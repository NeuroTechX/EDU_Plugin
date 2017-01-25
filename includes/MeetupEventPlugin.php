<?php

class MeetupEventPlugin
{
        
        /**
         * @mu          Meetup object (see utils.php)
         * @groups      Array of Meetup groups (strings)
         */
        function __construct( $mu, $groups ) {
                $this->mu = $mu;
                $this->groups = $groups;
        }

        /**
         * Generate the output as html
         *
         * @data:       Array of meetup events objects
         * @atts        Array of attributes set by shortcodes
         */
        function generate_html( $data, $atts=array() ) {
                $dom = new DOMDocument();
                $h1 = $dom->createElement( 'h1', "Upcoming Meetup Events" );
                $dom->appendChild( $h1 );
                $class = isset( $atts['class'] ) ? $atts['class'] : '';
                foreach ( $data as $i => $event ) {
                        $epoch = $event['time'] / 1000;
                        $datetime = date("j,M,Y,g:i A,T", $epoch);
                        $link = $event['link'];
                        $title = $event['name'];
                        $description = strip_tags( $event['description'] );
                        $group = $event['group']['name'];

                        $content = HTMLUtils::event_domdoc( $title, $description, $datetime, $group, $link, 'meetup-event-item' );
                        HTMLUtils::append( $dom, $content );
                }
                HTMLUtils::div_wrap( $dom, $class );
                return $dom->saveHTML();
        }

        /**
         * Generate the shortcodes' output
         *
         * @atts        Array of attributes set by shortcodes
         */
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
                return $content;
        }
}

?>
