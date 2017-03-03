<?php
/**
 * Meetup and Eventbrite have different representation of an event.
 * To generate content from both, we aggregate the data from both source
 * using the following format:
 *
 *   [
 *     {
 *       'epoch': $epoch,
 *       'datetime': $datetime,
 *       'link': $link,
 *       'title': $title,
 *       'description': $description,
 *       'organizer_name': $organizer_name,
 *       'organizer_link': $organizer_url,
 *       'lat': $lat,
 *       'lng': $lng,
 *       'address': $address,
 *       'city': $city
 *     },
 *     .
 *     .
 *     .
 *   ]
 *
 * For example, when passing the data to the Javascript script string
 * by way of formatted string (sprintf), the string needs to be a
 * json encoded string representation of the above format.
 *
 * Similarly, when working in PHP, events from both Meetup and Eventbrite
 * should be in the same format (using PHP arrays and assoc).
 *
 */


/**
 * Javascript to update style element on user input.
 * Implements basic filtering based on some data attributes.
 * In this case, "data-city"
 */
$event_filter_script_str = <<<'JAVASCRIPT'

var eventFilterStyle = document.getElementById('eventFilterStyle');
document.getElementById('eventFilterTextInput').addEventListener('input', function() {
    if (!this.value) {
        eventFilterStyle.innerHTML = "";
        return;
    }
    eventFilterStyle.innerHTML = ".event-item:not([data-city*=\"" + this.value.toLowerCase() + "\"]) { display: none; }";
});

JAVASCRIPT;


/**
 * Javascript to generate map with markers of events.
 *
 * String placeholders (order matters!):
 *   - id: id of the div in which draw the map
 *   - scrollwheel: true | false (whether or not to allow scroll wheel zoom on the map
 *   - data: a json encoded string of the events.
 */
$map_script_str = <<<'JAVASCRIPT'

google.maps.InfoWindow.prototype.opened = false;

lastOpenedInfoWindow;

var map;

function createMap(lat, lng) {
    if (typeof(lat) === "undefined") lat = 0;
    if (typeof(lng) === "undefined") lng = 0;

    map = new google.maps.Map(document.getElementById("%s"), {
        center: {lat: lat, lng: lng},
        zoom: 2,
        minZoom: 1,
        scrollwheel: %s
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
            if (infowindow.opened) {
                infowindow.close();
                infowindow.opened = false;
            } else {
                if (typeof(lastOpenedInfoWindow) !== 'undefined') {
                    lastOpenedInfoWindow.opened = false;
                    lastOpenedInfoWindow.close();
                }
                infowindow.open(map, marker);
                infowindow.opened = true;
                lastOpenedInfoWindow = infowindow;
            }
        });
    });
}

function initMap() {
    createMap();

    // Only works with secure origins (Use HTTPS)
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


/**
 * Plugin to generate content from all events (Meetup, Eventbrite, ...)
 */
class AllEventsPlugin
{

        /**
         * @mu			Meetup object
         * @eb			Eventbrite object
         * @groups		Array of Meetup groups
         * @organizer_ids	Array of Eventbrite organizers' ids
         */
        function __construct( $mu, $eb, $groups, $organizer_ids ) {
                $this->eb = $eb;
                $this->organizer_ids = $organizer_ids;
                $this->mu = $mu;
                $this->groups = $groups;
        }

	/**
	 * Return an array of events from Eventbrite and Meetup.
	 * See the heading of this file for more information about
	 * the format used.
	 */
        function get_all_events() {
                $cache_key = 'events_all';
                $cache_group = 'events';

                $cached = wp_cache_get( $cache_key, $cache_group );
                if ( $cached ) {
                        return $cached;
                }
                
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
                                $lng = $e['venue']['lon']; // FLOAT
                                $address = $e['venue']['address_1'];
                                $city = $e['venue']['city'];
                                $arr = array(
                                        'epoch' => $epoch,
                                        'datetime' => $datetime,
                                        'link' => $link,
                                        'title' => $title,
                                        'description' => $description,
                                        'organizer_name' => $group,
                                        'organizer_link' => $group_link,
                                        'lat' => $lat,
                                        'lng' => $lng,
                                        'address' => $address,
                                        'city' => $city
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
                                        $city = $e['venue']['city'];
                                        $arr = array(
                                                'epoch' => $epoch,
                                                'datetime' => $datetime,
                                                'link' => $link,
                                                'title' => $title,
                                                'description' => $description,
                                                'organizer_name' => $organizer_name,
                                                'organizer_link' => $organizer_url,
                                                'lat' => $lat,
                                                'lng' => $lng,
                                                'address' => $address,
                                                'city' => $city
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
                wp_cache_set( "events_all", $events_all, "events", 3600 );
                return $events_all;
        }

        /**
         * Generate the output as html
         *
         * @data        Array of events objects
	 *              (See the heading of this file for more info about the format used).
         *
         * @atts        Array of attributes set by shortcodes
         */
        function generate_html( $data, $atts=array() ) {
                global $event_filter_script_str;

                $dom = new DOMDocument();
                $class = isset( $atts['class'] ) ? $atts['class'] : '';
                foreach ( $data as $i => $event ) {
                        $epoch = $event['epoch'];
                        if ( isset( $atts['upto'] ) && !empty( $atts['upto'] ) && $epoch > strtotime( $atts['upto'] ) ) {
                                break;
                        }
                        $datetime = $event['datetime'];
                        $link = $event['link'];
                        $title = $event['title'];
                        $description = $event['description'];
                        $organizer_name = $event['organizer_name'];
                        $organizer_link = $event['organizer_link'];
                        $city = $event['city'];

                        $dataAttributes = array(
                                'data-city' => strtolower( $city )
                        );

                        $content = HTMLUtils::event_domdoc( $title, $description, $datetime, $organizer_name, $organizer_link, $link, 'event-item', $dataAttributes );
                        HTMLUtils::append( $dom, $content );
                }

                $filterStyle = $dom->createElement( 'style' );
                $filterStyle->setAttribute( 'id', 'eventFilterStyle' );
                $filterScript = $dom->createElement( 'script' );
                $filterScript->setAttribute( 'type', 'text/javascript' );
                $filterScript->setAttribute( 'async', '1' );
                $filterScript->setAttribute( 'defer', '1' );
                $filterScript->textContent = $event_filter_script_str;

                $dom->appendChild( $filterStyle );
                $dom->appendChild( $filterScript );

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
                                'class' => '',
                                'upto' => ''
                        ),
                        $atts
                );

                $events_all = $this->get_all_events();
                return $this->generate_html( $events_all, $atts );
        }

        /**
         * Generate the html that will draw the map
	 * i.e div and script tags
         *
         * @data        Array of events objects
	 *              (See the heading of this file for more info about the format used).
         *
         * @atts        Array of attributes set by shortcodes
         */        
        function generate_maps_html( $data, $atts ) {
                global $map_script_str;

                $dom = new DOMDocument();
                
                $div = $dom->createElement( 'div' );
                $id = isset( $atts['id'] ) ? $atts['id'] : 'event-map';
                $div->setAttribute( 'id', $id );
                
                $script1 = $dom->createElement( 'script' );
                $script1->setAttribute( 'type', 'text/javascript' );
                $script1->setAttribute( 'async', '1' );
                $script1->setAttribute( 'defer', '1' );

                $script2 = $dom->createElement( 'script' );
                $script2->setAttribute( 'type', 'text/javascript' );
                $script2->setAttribute( 'src', "https://maps.googleapis.com/maps/api/js?key=AIzaSyBTNMsmPf7CUhFpzkeCUS6JC43A7FEURi0&callback=initMap" );
                $script2->setAttribute( 'async', '1' );
                $script2->setAttribute( 'defer', '1' );

                $scrollwheel = isset( $atts['scrollwheel'] ) ? $atts['scrollwheel'] : 'false';
                $script1->textContent = sprintf(
                        $map_script_str,
                        $id,
                        $scrollwheel,
                        json_encode( $data ) );
                $dom->appendChild( $div );
                $dom->appendChild( $script1 );
                $dom->appendChild( $script2 );

                return $dom->saveHTML();
        }

	/**
         * Generate the map's shortcode output
         *
         * @atts        Array of attributes set by shortcodes
         */
        function generate_map_shortcode( $atts ) {
                $data = wp_cache_get( "events_all", "events" );
                if ( ! $data ) {
                        $events_all = $this->get_all_events(); 
                        $data = $events_all;
                }
                
                // return $this->generate_maps_html( $data, $atts );
                return $this->generate_maps_html( $data, $atts );
        }
}


?>
