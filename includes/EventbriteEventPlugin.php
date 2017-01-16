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
                $h1 = $dom->createElement( 'h1', "Upcoming Events" );
                $dom->appendChild( $h1 );
                foreach ( $data as $i => $event ) {
                        $epoch = strtotime($event['start']['utc']);
                        $datetime = date("j M, Y, g:i A - T", $epoch);
                        $link = $event['url'];
                        $title = $event['name']['text'];
                        $description = $event['description']['text'];
                        $organizer = $event['organizer.name'];

                        $h3 = $dom->createElement( 'h3' , htmlentities( $title ) );
                        $ul = $dom->createElement( 'ul' );
                        $span1 = $dom->createElement( 'span', $datetime );
                        $span2 = $dom->createElement( 'span', 'Hosted by ' . $organizer );

                        $a = $dom->createElement( 'a' , 'Eventbrite Link');
                        $a->setAttribute( 'href', $link );
                        $a->setAttribute( 'target', '_blank' );

                        $p = $dom->createElement( 'p' , $description );

                        $li_1 = $dom->createElement( 'li' );
                        $li_1->appendChild( $span1 );
                        $li_2 = $dom->createElement( 'li' );
                        $li_2->appendChild( $a );
                        $li_3 = $dom->createElement( 'li' );
                        $li_3->appendChild( $span2 );
                        $ul->appendChild( $li_1 );
                        $ul->appendChild( $li_2 );
                        $ul->appendChild( $li_3 );

                        $dom->appendChild( $p );
                        $dom->appendChild( $h3 );
                        $dom->appendChild( $ul );
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
