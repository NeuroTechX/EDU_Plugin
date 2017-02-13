<?php
/**
 * Utilities classes and functions.
 */

require_once 'library/Parsedown.php';


/**
 * Wrapper class for the PHP curl API.
 */
class HttpRequest
{

        /**
         * @url         The request url
         * @headers     Array (non-associative) string ("key: values")
         * @redirect    Follow redirects
         * @maxredirs   Max number of redirects if redirect is set
         * @timeout     Connection timeout
         */
        static function get( $url="", $headers=array(),
                             $redirect=true, $maxredirs=10,
                             $timeout=120 )
        {
                $options = array(
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HEADER         => false,
                        CURLOPT_FOLLOWLOCATION => $redirect,
                        CURLOPT_MAXREDIRS      => $maxredirs,
                        CURLOPT_ENCODING       => "",
                        CURLOPT_AUTOREFERER    => true,
                        CURLOPT_CONNECTTIMEOUT => $timeout,
                        CURLOPT_TIMEOUT        => $timeout,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLINFO_HEADER_OUT => true
                );
                $ch = curl_init( $url );
                curl_setopt_array( $ch, $options );
                
                $content = curl_exec( $ch );
                $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                curl_close( $ch );
                
                return array(
                        'content' => $content,
                        'status_code' => $status_code
                );
        }

        static function debug( $url="", $headers=array(),
                               $redirect=true, $maxredirs=10,
                               $timeout=120 )
        {
                $options = array(
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HEADER         => false,
                        CURLOPT_FOLLOWLOCATION => $redirect,
                        CURLOPT_MAXREDIRS      => $maxredirs,
                        CURLOPT_ENCODING       => "",
                        CURLOPT_AUTOREFERER    => true,
                        CURLOPT_CONNECTTIMEOUT => $timeout,
                        CURLOPT_TIMEOUT        => $timeout,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLINFO_HEADER_OUT => true
                );
                $ch = curl_init( $url );
                curl_setopt_array( $ch, $options );
                
                $content = curl_exec( $ch );
                $info = curl_getinfo( $ch );
                print_r( $content );
                print_r( $info['request_header'] );
        }
}


/**
 * Wrapper class for Github's API specific requests.
 */
class Github
{
        
        static $url = 'https://api.github.com';
        static $url_web = 'https://github.com';

        /**
         * @user        User (needed to set User-Agent header)
         * @token       Access token
         */
        function __construct( $user, $token ) {
                $this->user = $user;
                $this->token = $token;
                $this->headers = array(
                        "User-Agent: " . $user,
                        "Authorization: " . "token " . $token
                );
        }

        /**
         * Get the readme content of the given repo.
         *
         * The return value is an array containing the response.
         * See HttpRequest.
         *
         * @owner       User owning the repo
         * @repo        The repo's name
         */
        function get_readme( $owner, $repo ) {
                // Cache
                $key = $owner . ':' . $repo;
                $group = 'github_readme';

                $cached = wp_cache_get( $key, $group );
                if ( $cached ) {
                        return $cached;
                }

                $endpoint = '/repos/' . $owner . '/' . $repo . '/readme';
                $request_url = Github::$url . $endpoint;
                $r = HttpRequest::get( $request_url, $this->headers );
                
                if ( $r['status_code'] != 200 &&
                     $r['status_code'] != 301 &&
                     $r['status_code'] != 302 &&
                     $r['status_code'] != 307 ) {
                        $readme = wp_cache_get( $key, $group );
                        return $readme ? $readme : "";
                }
                
                $content = $r['content'];
                $json = json_decode( $content, true );
                $readme = HttpRequest::get( $json['download_url'] )['content'];
                wp_cache_set( $key, $readme, $group );
                return $readme;
        }

        /**
         * Get the repo's web url.
         * e.g. https://github.com/NeuroTechX/EDU_Plugin
         *
         * @owner       User owning the repo
         * @repo        The repo's name
         */
        function get_repo_url( $owner, $repo ) {
                $endpoint = '/' . $owner . '/' . $repo;
                $request_url = Github::$url_web . $endpoint;
                $status_code = HttpRequest::get(
                        $request_url,
                        $this->headers
                )['status_code'];
                if ( $status_code == 404 ) {
                        return false;
                } else {
                        return $request_url;
                }
        }
}


/**
 * Wrapper class for Meetup's API specific requests.
 */
class Meetup
{
        static $url = 'https://api.meetup.com';
        static $url_web = 'https://meetup.com';

        /**
         * @key         API Key
         */
        function __construct( $key ) {
                $this->key = $key;
                $this->sign = 'true';
                $this->params = array(
                        "key=$this->key",
                        "sign=$this->sign"
                );
        }

        /**
         * Return an assoc array representation of the json from Meetup's API.
         *
         * @group       Meetup group to get event from
         * @status      Status filter (defaults to "upcoming")
         */
        function get_events( $group, $status="upcoming" ) {
                // Cache
                $cache_key = 'meetup_events';
                $cache_group = 'meetup_' . $group;

                $cached = wp_cache_get( $cache_key, $cache_group );
                if ( $cached ) {
                        return $cached;
                }
                
                $endpoint = "/$group/events";
                if ( $status == "upcoming" ||
                     $status == "past" ||
                     $status == "cancelled" ) {
                        $endpoint .= "?status=$status";
                } else {
                        $endpoint .= "?status=upcoming";
                }
                foreach ( $this->params as $key => $value ) {
                        $endpoint .= "&$key=$value";
                }
                $request_url = Meetup::$url . $endpoint;
                $r = HttpRequest::get( $request_url );
                
                if ( $r['status_code'] != 200 &&
                     $r['status_code'] != 301 &&
                     $r['status_code'] != 302 &&
                     $r['status_code'] != 307 ) {
                        $json = wp_cache_get( $cache_key, $cache_group );
                        return $json ? $json : array();
                }
                
                $content = $r['content'];
                $json = json_decode( $content, true );
                wp_cache_set( $cache_key, $json, $cache_group, 300 );
                return $json;
        }

        function get_group_profile_url( $urlname ) {
                $endpoint = "/$urlname";
                $request_url = Meetup::$url_web . $endpoint;
                return $request_url;
        }
}


/**
 * Wrapper class for Eventbrite's API specific requests.
 */
class Eventbrite
{

        static $url = 'https://www.eventbriteapi.com/v3';
        static $url_web = 'https://www.eventbrite.com';
        
        /**
         * @tokens         Array of API tokens
         */
        function __construct( $token ) {
                $this->token = $token;
        }

        /**
         * TODO: GET /events/search/
         */
        function get_events( $organizer_id ) {
                // Cache
                $cache_key = 'eventbrite_events';
                $cache_group = 'eventbrite_' . $organizer_id;

                $cached = wp_cache_get( $cache_key, $cache_group );
                if ( $cached ) {
                        return $cached;
                }
                
                $endpoint = "/events/search";
                $endpoint .= "?token=$this->token";
                $endpoint .= "&organizer.id=$organizer_id";
                $endpoint .= "&sort_by=-date";
                $endpoint .= "&expand=venue";
                $request_url = Eventbrite::$url . $endpoint;
                $r = HttpRequest::get( $request_url );

                if ( $r['status_code'] != 200 &&
                     $r['status_code'] != 301 &&
                     $r['status_code'] != 302 &&
                     $r['status_code'] != 307 ) {
                        $json = wp_cache_get( $cache_key, $cache_group );
                        return $json ? $json : array();
                }

                $content = $r['content'];
                $json = json_decode( $content, true );
                $events = array_key_exists( 'events', $json ) ? $json['events'] : false;
                wp_cache_set( $cache_key, $events, $cache_group, 300 );
                return $events;
        }

        /**
         * Return the organizer object of the given organizer's id
         *
         * @id          Organizer's id
         *
         */
        function get_organizer( $id ) {
                $endpoint = "/organizers/$id";
                $endpoint .= "?token=$this->token";
                $request_url = Eventbrite::$url . $endpoint;
                $r = HttpRequest::get( $request_url )['content'];
                $json = json_decode( $r, true );
                return $json;
        }
}


/**
 * Various utility functions for HTML processing.
 */
class HTMLUtils
{
        /**
         * Add anchors before headings similar to Github flavored markdown.
         * e.g. <h1>Heading Value</h1> 
         *
         *      becomes 
         *
         *      <a name="heading-value" style="display:none;"></a><h1></h1>
         *
         * This is to allow the anchors to link to fragments.
         *
         * @html        Html string
         */
        static function add_anchors_to_headings( $html ) {
                $doc = new DOMDocument();
                $doc->loadHTML( $html );
                // <h1> to <h6>
                for ( $i = 1; $i <= 6; $i++ ) {
                        $tagname = "h" . $i;
                        $h = $doc->getElementsByTagName( $tagname );
                        foreach ( $h as $hh ) {         
                                $name = strtolower(
                                        preg_replace( '/\s+/', '-', $hh->nodeValue )
                                );
                                $anchor = $doc->createElement( "a" );
                                $anchor->setAttribute( "name", $name );
                                $anchor->setAttribute( "style", "display:hidden;" );
                                // $hh->appendChild( $anchor );
                                $hh->parentNode->insertBefore( $anchor, $hh );
                        }
                }
                return $doc->saveHTML();
        }
        
        /**
         * Adds the attribute target="_blank" to hyperlinks (links that does not start with "#").
         * i.e. Open links in new tabs.
         *
         * @html        Html string
         */
        static function add_target_blank_to_links( $html ) {
                $doc = new DOMDocument();
                $doc->loadHTML( $html );

                $anchors = $doc->getElementsByTagName( 'a' );
                foreach ( $anchors as $a ) {
                        if ( $a->hasAttribute( 'href' ) &&
                             ! StringUtils::startsWith( $a->getAttribute( 'href' ), '#' ) ) {
                                $a->setAttribute( 'target', '_blank' );
                        }
                }
                return $doc->saveHTML();
        }

        /**
         * Return the DOMDocument representation of an event (meetup, eventbrite, ...)
         *
         * @title               Title of the event
         * @description         Description of the event
         * @datetime            Date and time of the event (string)
         *                      "j,M,Y,g:i A,T"  (day,month,year,time,timezone)
         * @organizer           Organizer of the event (for Meetup, we use groups...)
         * @link                Link to external event page (meetup, eventbrite, ...)
         */
        static function event_domdoc( $title, $description, $datetime, $organizer, $organizer_link, $link, $class="" ) {
                $dom = new DOMDocument();
                // divs
                $div_date = $dom->createElement( 'div' );
                $div_date->setAttribute( 'class', 'date' );
                $div_details = $dom->createElement( 'div' );
                $div_details->setAttribute( 'class', 'details' );

                // Date
                $datetime_arr = explode( ',', $datetime );
                $day_span = $dom->createElement( 'span', $datetime_arr[0] );
                $day_span->setAttribute( 'class', 'day' );
                $month_span = $dom->createElement( 'span', $datetime_arr[1] );
                $month_span->setAttribute( 'class', 'month' );
                $year_span = $dom->createElement( 'span', $datetime_arr[2] );
                $year_span->setAttribute( 'class', 'year' );
                $time_span = $dom->createElement( 'span', $datetime_arr[3] );
                $time_span->setAttribute( 'class', 'time' );
                $div_date->appendChild( $month_span );
                $div_date->appendChild( $day_span );
                $div_date->appendChild( $year_span );
                $div_date->appendChild( $time_span );

                // Details
                // Title
                $h3 = $dom->createElement( 'h3' );
                $a = $dom->createElement( 'a' , htmlentities( $title ) );
                $a->setAttribute( 'href', $link );
                $a->setAttribute( 'target', '_blank' );
                $h3->appendChild( $a );
                // Organizer
                $span_organizer = $dom->createElement( 'span', 'Organized by: ');
                $organizer_a = $dom->createElement( 'a' , $organizer );
                $organizer_a->setAttribute( 'href', $organizer_link );
                $organizer_a->setAttribute( 'target', '_blank' );
                $span_organizer->appendChild( $organizer_a );
                $div_details->appendChild( $h3 );
                $div_details->appendChild( $span_organizer );
                // TODO: location span
                
                // Description
                $p = $dom->createElement( 'p' , $description );
                $div_details->appendChild( $p );
                $dom->appendChild( $div_date );
                $dom->appendChild( $div_details );

                if ( !empty ( $class ) ) {
                        HTMLUtils::div_wrap( $dom, $class );
                }
                return $dom;
        }

        /**
         * Wraps the given DOMDocument in a div
         */
        static function div_wrap( $dom, $class="" ) {
                $div = $dom->createElement( 'div' );
                if ( !empty( $class ) ) {
                        $div->setAttribute( 'class', $class );
                }
                while ( $dom->childNodes->length > 0 ) {
                        $child = $dom->childNodes->item(0);
                        $dom->removeChild( $child );
                        $div->appendChild( $child );
                }
                $dom->appendChild( $div );
        }

        /**
         * Append DOMDocument $doc2 to DOMDocument $dom1
         *
         * @dom1        Dest DOMDocument
         * @dom2        Src DOMDocument
         */
        static function append( $dom1, $dom2 ) {
                foreach ( $dom2->childNodes as $child ) {
                        $node = $dom1->importNode( $child, true );
                        $dom1->appendChild( $node );
                }
        }
}


/**
 * Wrapper to print a section's info in the admin page.
 */
class PrintSection
{

        /**
         * @text        Text to be printed
         */
        function __construct( $text ) {
                $this->text = $text;
        }

        /**
         * The callback
         */
        function print_section_info() {
                echo $this->text;
        }
}


/**
 * Various strings utilitiy functions
 */
class StringUtils
{
        /**
         * Checks if @haystack starts with @needle
         *
         * @haystack:   The "complete" string
         * @needle:     The "substring"
         */
        static function startsWith( $haystack, $needle ) {
                $length = strlen( $needle );
                return ( substr( $haystack, 0, $length ) === $needle );
        }
        
        /**
         * Checks if @haystack ends with @needle
         *
         * @haystack:   The "complete" string
         * @needle:     The "substring"
         */
        static function endsWith( $haystack, $needle ) {
                $length = strlen( $needle );
                if ( $length == 0 ) {
                        return true;
                }
                return ( substr( $haystack, -$length ) === $needle );
        }
}

?>
