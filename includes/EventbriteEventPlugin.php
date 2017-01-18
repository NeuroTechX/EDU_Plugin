<?php

class EventbriteEventPlugin
{

        /**
         * @eb          Eventbrite object (see utils.php)
         * @post_ids    Posts' ids to apply the plugin on
         */
        function __construct( $eb, $organizer_ids, $post_ids ) {
                $this->eb = $eb;
                $this->post_ids = $post_ids;
                $this->organizer_ids = $organizer_ids;
        }

        /**
         * Generate the output as html
         *
         * @data:       Array of meetup events objects
         *
         */
        function generate_html( $data ) {
                $dom = new DOMDocument();
                $h1 = $dom->createElement( 'h1', "Upcoming Eventbrite Events" );
                $dom->appendChild( $h1 );
                foreach ( $data as $i => $event ) {
                        $epoch = strtotime($event['start']['utc']);
                        $datetime = date("j M, Y, g:i A - T", $epoch);
                        $link = $event['url'];
                        $title = $event['name']['text'];
                        $description = $event['description']['text'];
                        $organizer = $event['organizer.name'];

                        $div = $dom->createElement('div');

                        $fragment = $dom->createDocumentFragment();
                        $fragment->appendXML(
                                HTMLUtils::print_event_html( $title, $description, $datetime, $organizer, $link )
                        );
                        $dom->appendChild( $fragment );
                }
                return $dom->saveHTML();
        }

        function generate_shortcode( $atts ) {
                $events = array();
                foreach ( $this->organizer_ids as $id ) {
                        $organizer_name = $this->eb->get_organizer( $id )['name'];
                        $r = $this->eb->get_events( $id );
                        if ( $r ) {
                                foreach ( $r as $e ) {
                                        $e['organizer.name'] = $organizer_name;
                                }
                                $events = array_merge( $events, $r );
                        }
                }
                // TODO: sort
                return $this->generate_html( $events );
        }

        function callback_eventbrite_event( $content ) {
                $p = get_post();
                $events = array();
                if ( in_array( $p->ID, $this->post_ids ) )  {
                        foreach ( $this->organizer_ids as $id ) {
                                $r = $this->eb->get_events( $id );
                                if ( $r ) {
                                        $events = array_merge( $events, $r );
                                }
                        }
                        // TODO: sort
                        $content .= $this->generate_html( $events );
                }
                return $content;
        }
        
}

?>
