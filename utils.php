<?php
/**
 * Utilities classes and functions.
 */
require_once('Parsedown.php');


/**
 * Wrapper class for the PHP curl API.
 */
class HttpRequest
{

	/**
	 * @url		The request url
	 * @headers	Array (non-associative) string ("key: values")
	 * @redirect	Follow redirects
	 * @maxredirs	Max number of redirects if redirect is set
	 * @timeout	Connection timeout
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
		curl_close( $ch );
		
		return $content;
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
 * Wrapper class for HttpRequest for Github's API specific requests.
 */
class Github
{
	
	static $url = 'https://api.github.com';

	/**
	 * @user	User (needed to set User-Agent header)
	 * @token	Access token
	 */
	function __construct( $user, $token )
	{
		$this->user = $user;
		$this->token = $token;
		$this->headers = array(
			"User-Agent: " . $user,
			"Authorization: " . "token " . $token
		);
	}

	/**
	 * @owner	User owning the repo
	 * @repo	The repo's name
	 */
	function get_readme( $owner, $repo )
	{
		$endpoint = '/repos/' . $owner . '/' . $repo . '/readme';
		$request_url = Github::$url . $endpoint;
		$r = HttpRequest::get( $request_url, $this->headers );
		$json = json_decode( $r, true );
		return HttpRequest::get( $json['download_url'] );
	}

	/**
	 * Add anchors before headings similar to Github flavored markdown.
	 * e.g. <h1>Heading Value</h1> => 
	 *      <a name="heading-value" style="display:none;"></a><h1></h1>
	 *
	 * This to allow the anchors to link to fragments.
	 *
	 * @html	Html string.
	 */
	static function add_anchors_to_headings( $html )
	{
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
}

?>
