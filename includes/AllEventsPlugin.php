<?php

class AllEventsPlugin
{

        /**
         *
         */
        function __construct( $mu, $eb, $groups, $organizer_ids ) {
                $this->eb = $eb;
                $this->organizer_ids = $organizer_ids;
                $this->mu = $mu;
                $this->groups = $groups;
        }

        /**
         * Generate the output as html
         *
         * @data        Array of events objects
         *              Each object must have the following attributes:
         *              - time: int
         *              - link: string
         *              - title: string
         *              - description: string
         *              - organizer_name: string
         *              - organizer_link: string
         *
         * @atts        Array of attributes set by shortcodes
         */
        function generate_html( $data, $atts=array() ) {
                $dom = new DOMDocument();
                $h1 = $dom->createElement( 'h1', "Upcoming Events" );
                $dom->appendChild( $h1 );
                $class = isset( $atts['class'] ) ? $atts['class'] : '';
                foreach ( $data as $i => $event ) {
                        $datetime = $event['datetime'];
                        $link = $event['link'];
                        $title = $event['title'];
                        $description = $event['description'];
                        $organizer_name = $event['organizer_name'];
                        $organizer_link = $event['organizer_link'];
                        
                        $content = HTMLUtils::event_domdoc( $title, $description, $datetime, $organizer_name, $organizer_link, $link, 'event-item' );
                        HTMLUtils::append( $dom, $content );

                        //
                        // TODO: Output "markers" containing the markers info (lat, lng, info) for the google maps
                        //
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
                $atts = shortcode_atts(
                        array(
                                'class' => ''
                        ),
                        $atts
                );
                
                $events_all = array();
                $events_markers = array();

                // Meetup
                foreach ( $this->groups as $group ) {
                        $r = $this->mu->get_events( $group );
                        foreach ( $r as $e ) {
                                $epoch = $e['time'] / 1000;
                                $datetime = date("j,M,Y,g:i A T", $epoch);
                                $link = $e['link'];
                                $title = $e['name'];
                                $description = strip_tags( $e['description'] );
                                $group = $e['group']['name'];
                                $group_link = $this->mu->get_group_profile_url( $e['group']['urlname'] );
                                $lat = $e['venue']['lat']; // FLOAT
                                $lat = $e['venue']['lon']; // FLOAT
                                $address = $e['venue']['address_1'];
                                $arr = array(
                                        'epoch' => $epoch,
                                        'datetime' => $datetime,
                                        'link' => $link,
                                        'title' => $title,
                                        'description' => $description,
                                        'organizer_name' => $group,
                                        'organizer_link' => $group_link,
                                        'lat' => $lat,
                                        'lng' => $lgn,
                                        'address' => $address
                                );
                                array_push(
                                        $events_all,
                                        $arr
                                );
                        }
                }                
                // Eventbrite
                foreach ( $this->organizer_ids as $id ) {
                        $organizer = $this->eb->get_organizer( $id );
                        $organizer_name = $organizer['name'];
                        $organizer_url = $organizer['url'];
                        $r = $this->eb->get_events( $id );
                        if ( $r ) {
                                foreach ( $r as &$e ) {
                                        $epoch = strtotime($e['start']['utc']);
                                        $datetime = date("j,M,Y,g:i A T", $epoch);
                                        $link = $e['url'];
                                        $title = $e['name']['text'];
                                        $description = $e['description']['text'];
                                        $lat = floatval( $e['venue']['latitude'] );
                                        $lng = floatval( $e['venue']['longitude'] );
                                        $address = $e['venue']['address']['address_1'];
                                        $arr = array(
                                                'epoch' => $epoch,
                                                'datetime' => $datetime,
                                                'link' => $link,
                                                'title' => $title,
                                                'description' => $description,
                                                'organizer_name' => $organizer_name,
                                                'organizer_link' => $organizer_link,
                                                'lat' => $lat,
                                                'lng' => $lng,
                                                'address' => $address
                                        );
                                        array_push(
                                                $events_all,
                                                $arr
                                        );
                                }
                        }
                }
                // Sort all
                usort( $events_all, function( $a, $b ) {
                        if ( $a['epoch'] == $b['epoch'] ) {
                                return 0;
                        }
                        return ( $a['epoch'] < $b['epoch'] ) ? -1 : 1;
                } );
                return $this->generate_html( $events_all, $atts );
        }

        function generate_map_shortcode( $atts ) {
                
        }
}


/*
 * @api_key     Google Maps API key
 */
function generate_map_script_string( $api_key ) {
        $script_string =  'function initMap() {';
        $script_string .= '';
        
        $dom = new DOMDocument();
        $script = $dom->createElement( 'script' );
        $script->setAttribute( 'type', 'text/javascript' );
        $script->textContent = $script_string;
}

?>
