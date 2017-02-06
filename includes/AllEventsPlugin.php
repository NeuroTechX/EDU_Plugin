<?php

// {
//         'epoch' => $epoch,
//         'datetime' => $datetime,
//         'link' => $link,
//         'title' => $title,
//         'description' => $description,
//         'organizer_name' => $organizer_name,
//         'organizer_link' => $organizer_link,
//         'lat' => $lat,
//         'lng' => $lng,
//         'address' => $address
// }

$map_script_str = <<<'JAVASCRIPT'
var map;

function createMap(lat, lng) {
    if (typeof(lat) === "undefined") lat = 0;
    if (typeof(lng) === "undefined") lng = 0;

    map = new google.maps.Map(document.getElementById("event-map"), {
        center: {lat: lat, lng: lng},
        zoom: 1,
        minZoom: 1
    });

    var data = %s;

    data.forEach(function(entry) {
        var title = entry['title'];
        var link = entry['link'];
        var datetime = entry['datetime'];
        var organizer_name = entry['organizer_name'];
        var organizer_link = entry['organizer_link'];
        var address = entry['address'];

        var contentString = '<div class="content">' +
            '<h3><a href="' + link + '">' + title + '</a></h3>' +
            '<p>' + datetime.replace(/,/g, ' ') + '</p>' +
            '<p>' + address + '</p>' +
            '<p>Organized by <a href="' + organizer_link + '">' + organizer_name + '</a></p>' +
            '</div>';


        var infowindow = new google.maps.InfoWindow({
            content: contentString
        });

        var point1 = new google.maps.LatLng(
            entry['lat'], entry['lng']
        );

        var marker = new google.maps.Marker({
            map: map,
            position: point1
        });

        marker.addListener('click', function() {
            infowindow.open(map, marker);
        });
    });
}

function initMap() {
    createMap();
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var defaultLat = position.coords.latitude;
            var defaultLng = position.coords.longitude;
            map.setCenter({lat: defaultLat, lng:defaultLng});
            map.setZoom(7);
        });
    }
}

JAVASCRIPT;

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
                wp_cache_set( "events_all", $events_all, "events", 300 );
                return $this->generate_html( $events_all, $atts );
        }

        
        function generate_maps_html( $data, $atts ) {
                global $map_script_str;

                $dom = new DOMDocument();
                
                $div = $dom->createElement( 'div' );
                $div->setAttribute( 'id', 'event-map' );
                
                $script1 = $dom->createElement( 'script' );
                $script1->setAttribute( 'type', 'text/javascript' );
                // $script1->setAttribute( 'async', '1' );
                // $script1->setAttribute( 'defer', '1' );

                $script2 = $dom->createElement( 'script' );
                $script2->setAttribute( 'type', 'text/javascript' );
                $script2->setAttribute( 'src', "https://maps.googleapis.com/maps/api/js?key=AIzaSyBTNMsmPf7CUhFpzkeCUS6JC43A7FEURi0&callback=initMap" );
                // $script2->setAttribute( 'async', '1' );
                // $script2->setAttribute( 'defer', '1' );

                $script1->textContent = sprintf( $map_script_str, json_encode( $data ) );
                $dom->appendChild( $div );
                $dom->appendChild( $script1 );
                $dom->appendChild( $script2 );

                return $dom->saveHTML();
        }

        function generate_map_shortcode( $atts ) {
                $data = wp_cache_get( "events_all", "events" );
                if ( ! $data ) {
                        $events_all = array();

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
                        $data = $events_all;
                        wp_cache_set( "events_all", $events_all, "events", 300 );
                }
                
                // return $this->generate_maps_html( $data, $atts );
                return $this->generate_maps_html( $data, $atts );
        }
}


?>
