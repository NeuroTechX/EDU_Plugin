<?php

class EventbriteEventPlugin
{

        /**
         * @eb                  Eventbrite object (see utils.php)
         * @organizer_ids       Array of Eventbrite organizers ids (strings)
         */
        function __construct( $eb, $organizer_ids ) {
                $this->eb = $eb;
                $this->organizer_ids = $organizer_ids;
        }

        /**
         * Generate the output as html
         *
         * @data        Array of meetup events objects
         * @atts        Array of attributes set by shortcodes
         */
        function generate_html( $data, $atts=array() ) {
                $dom = new DOMDocument();
                $h1 = $dom->createElement( 'h1', "Upcoming Eventbrite Events" );
                $dom->appendChild( $h1 );
                $class = isset( $atts['class'] ) ? $atts['class'] : '';
                foreach ( $data as $i => $event ) {
                        $epoch = strtotime($event['start']['utc']);
                        $datetime = date("j,M,Y,g:i A,T", $epoch);
                        $link = $event['url'];
                        $title = $event['name']['text'];
                        $description = $event['description']['text'];
                        $organizer = $event['organizer.name'];

                        $content = HTMLUtils::event_domdoc( $title, $description, $datetime, $organizer, $link, 'eventbrite-event-item' );
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
                foreach ( $this->organizer_ids as $id ) {
                        $organizer_name = $this->eb->get_organizer( $id )['name'];
                        $r = $this->eb->get_events( $id );
                        if ( $r ) {
                                foreach ( $r as &$e ) {
                                        $e['organizer.name'] = $organizer_name;
                                }
                                $events = array_merge( $events, $r );
                        }
                }
                // TODO: sort
                return $this->generate_html( $events, $atts );
        }

        function callback_eventbrite_event( $content ) {
                $p = get_post();
                $events = array();
                foreach ( $this->organizer_ids as $id ) {
                        $r = $this->eb->get_events( $id );
                        if ( $r ) {
                                $events = array_merge( $events, $r );
                        }
                }
                // TODO: sort
                $content .= $this->generate_html( $events );
                return $content;
        }
        
}

?>
