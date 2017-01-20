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
                wp_cache_set( $key, $content, $group );
                $json = json_decode( $content, true );
                return HttpRequest::get( $json['download_url'] )['content'];
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
                $r = HttpRequest::get( $request_url )['content'];
                $json = json_decode( $r, true );
                return $json;
        }
}


/**
 * Wrapper class for Eventbrite's API specific requests.
 */
class Eventbrite
{

        static $url = 'https://www.eventbriteapi.com/v3';
        
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
                $endpoint = "/events/search";
                $endpoint .= "?token=$this->token";
                $endpoint .= "&organizer.id=$organizer_id";
                $request_url = Eventbrite::$url . $endpoint;
                $r = HttpRequest::get( $request_url )['content'];
                $json = json_decode( $r, true );
                $events = array_key_exists( 'events', $json ) ? $json['events'] : false;

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
                $r = HttpRequest::get( $request_url )['content'];
                return $r;
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
         * Return the html representation of an event (meetup, eventbrite, ...)
         *
         * @title               Title of the event
         * @description         Description of the event
         * @datetime            Date and time of the event (string)
         * @organizer           Organizer of the event (for Meetup, we use groups...)
         * @link                Link to external event page (meetup, eventbrite, ...)
         */
        static function print_event_html( $title, $description, $datetime, $organizer, $link, $class="" ) {
                $dom = new DOMDocument();
                // Wrapper
                $div = $dom->createElement( 'div' );
                $div->setAttribute( 'class', $class );
                // Title
                $h3 = $dom->createElement( 'h3', htmlentities( $title ) );
                // Date
                $span1 = $dom->createElement( 'span', $datetime );
                $span2 = $dom->createElement( 'span', 'Hosted by ' . $group );

                // External link
                $a = $dom->createElement( 'a' , 'Meetup Link');
                $a->setAttribute( 'href', $link );
                $a->setAttribute( 'target', '_blank' );

                // Description
                $p = $dom->createElement( 'p' , $description );

                $ul = $dom->createElement( 'ul' );
                $li_1 = $dom->createElement( 'li' );
                $li_1->appendChild( $span1 );
                $li_2 = $dom->createElement( 'li' );
                $li_2->appendChild( $a );
                $li_3 = $dom->createElement( 'li' );
                $li_3->appendChild( $span2 );
                $ul->appendChild( $li_1 );
                $ul->appendChild( $li_2 );
                $ul->appendChild( $li_3 );
                
                $div->appendChild( $h3 );
                $div->appendChild( $ul );
                $div->appendChild( $p );
                $dom->appendChild( $div );

                return $dom->saveHTML();
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
