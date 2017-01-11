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
                        $dom = new DOMDocument();
                        $h1 = $dom->createElement( 'h1', "Upcoming Events" );
                        $dom->appendChild( $h1 );
                        foreach ( $events as $i => $event ) {
                                $epoch = $event['time'] / 1000;
                                $datetime = date("j M, Y, g:i A - T", $epoch);
                                $link = $event['link'];
                                $title = $event['name'];
                                $description = $event['description'];
                                $group = $event['group']['name'];

                                $h3 = $dom->createElement( 'h3' , htmlentities( $title ) );
                                $ul = $dom->createElement( 'ul' );
                                $span1 = $dom->createElement( 'span', $datetime );
                                $span2 = $dom->createElement( 'span', 'Hosted by ' . $group );

                                $a = $dom->createElement( 'a' , 'Meetup Link');
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
                }
                $content .= $dom->saveHTML();
                return $content;
        }
}

?>
