<?php
/*
   Plugin Name: Location Lookup
   Plugin URI: http://wordpress.org/extend/plugins/location-lookup/
   Version: 0.1
   Author: Chris Chasm
   Description: Looks up a geo location from an address using api
   Text Domain: location-lookup
   License: GPLv3
  */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of Location_Lookup to prevent the need to use globals.
 *
 * @since  0.1
 * @return object Location_Lookup
 */

// Adds the Location_Lookup after plugins load
add_action( 'plugins_loaded', 'Location_Lookup' );

// Creates the instance
function Location_Lookup() {
    return Location_Lookup::instance();
}

class Location_Lookup {

    /**
     * Location_Lookup The single instance of Location_Lookup.
     * @var 	object
     * @access  private
     * @since 	0.1
     */
    private static $_instance = null;

    /**
     * Google API Key: AIzaSyCrVLjAlOfzur7qVV_DkvtV56Wc2-tC0bU
     */

    /**
     * Main Location_Lookup Instance
     *
     * Ensures only one instance of Location_Lookup is loaded or can be loaded.
     *
     * @since 0.1
     * @static
     * @return Location_Lookup instance
     */
    public static function instance () {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     * @access  public
     * @since   0.1
     */
    public function __construct () {
        add_action( 'admin_menu', array( $this, 'load_admin_menu_item' ) );

    } // End __construct()

    public function load_admin_menu_item () {
        add_submenu_page( 'options-general.php', __( 'Location Lookup', 'disciple_tools' ), __( 'Location Lookup', 'disciple_tools' ), 'manage_options', 'location_lookup', array( $this, 'page_content' ) );
    }

    public function page_content() {
        echo '<h1>Location Lookup</h1><hr>';
//        echo ''.$this->ipinfo_example();
//        echo ''.$this->census_gov_example();
//        echo ''.$this->google_coordinate_conversion();
        echo ''.$this->kml_parse_example();
    }

    public function ipinfo_example () {
        // This is a sample lookup off an ipaddress using the ipinfo.io service

        echo '<h3>IpInfo.io Example</h3>Set IP Address:<br>';
        $ip = '67.177.209.111';//$_SERVER['REMOTE_ADDR']; //
        echo $ip . '<br>';

        echo '<br>';

        echo 'Retrieve JSON data from <a href="http://ipinfo.io">ipinfo.io</a><br>';
        $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
        print '<pre>'; print_r($details); print '</pre>';

        echo 'Location Information: ' . $details->loc . '<br>';
        echo 'Location Information as lookup link: <a href="https://www.google.com/maps/place/'.$details->loc.'">' . $details->loc . '</a>';

        echo '<hr>';
    }

    public function census_gov_example () {
        $html = '<hr><h3>Census Gov Geocoding</h3>';

        $html .= '<div class="wrap">
                        <div id="poststuff">
                            <div id="post-body" class="metabox-holder columns-2">';

        /*
        * Main left column
        *
        */
        $html .= '<div id="post-body-content">';

        $html .= '<form action="" method="POST">
                    <table class="widefat striped">
                    
                    <input type="hidden" name="lookup" value="true" />
                    
                     <tbody>
                        <tr>
                            <td>Address</td>
                            <td><input type="text" name="address" value="" /> </td>
                        </tr>
                        <tr>
                            <td>City</td>
                            <td><input type="text" name="city" /></td>
                        </tr>
                        <tr>
                            <td>State</td>
                            <td><input type="text" name="state" /></td>
                        </tr>
                        
                        <tr>
                            <td></td>
                            <td><button class="button" type="submit" value="submit">Lookup</button> </td>
                        </tr>
                    </tbody>';

        $html .= '</form>';

        $html .= '</div><!-- end post-body-content -->';

        $html .=   '<div id="postbox-container-1" class="postbox-container">
                    </div><!-- postbox-container 1 -->

                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->

                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->';

        echo $html;

        if(!empty($_POST['lookup'])) {

            $post_address = str_replace('   ', ' ', $_POST['address']);
            $post_address = str_replace('  ', ' ', $post_address );
            $post_address = str_replace('.', '', $post_address );
            $post_address = str_replace('\'', '', $post_address );
            $post_address = urlencode(htmlspecialchars(trim( $post_address )));

            $post_city = $_POST['city'];
            $post_state = $_POST['state'];

            $address = 'https://geocoding.geo.census.gov/geocoder/geographies/address?street='.$post_address.'&city='.$post_city.'&state='.$post_state.'&benchmark=Public_AR_Census2010&vintage=Census2010_Census2010&layers=14&format=json';


            echo 'Final Address ' . $address . '<br><br>';

            $details = json_decode(file_get_contents($address));

//            print '<pre>'; print_r($details); print '</pre>';

            if(empty($details->result->addressMatches)) {
                echo 'No matching address. Can you try again?';
            } else {
                print '<pre>The Tract is ... ';
                if(!empty($details->result->addressMatches[0]->geographies->{'Census Blocks'}[0]->TRACT)) {print_r($details->result->addressMatches[0]->geographies->{'Census Blocks'}[0]->TRACT); }; print '</pre>';
                print '<pre>The Geo Location is ... ';
                if(!empty($details->result->addressMatches[0]->coordinates)) {print_r($details->result->addressMatches[0]->coordinates);}; print '</pre>';

                print '<a target="_blank" href="https://www.google.com/maps/place/';
                print_r($details->result->addressMatches[0]->coordinates->y);
                print ',';
                print_r($details->result->addressMatches[0]->coordinates->x);
                print '"><strong>Google Map Link</strong></a><br><br>';
            }

        }

    }

    public function google_coordinate_conversion ()
    {

        /* Google API Key: AIzaSyBxUvKYE0LMTbz0VOtPxfRqHXWFyVqlF2I */

        // https://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA&key=YOUR_API_KEY

        $html = '<hr><h3>Census Gov Geocoding</h3>';

        $html .= '<div class="wrap">
                        <div id="poststuff">
                            <div id="post-body" class="metabox-holder columns-2">';

        /*
        * Main left column
        *
        */
        $html .= '<div id="post-body-content">';

        $html .= '<form action="" method="POST">
                    <table class="widefat striped">
                    
                    <input type="hidden" name="google_lookup" value="true" />
                    
                     <tbody>
                        <tr>
                            <td>Address</td>
                            <td><input type="text" name="address" value="" /> </td>
                        </tr>
                       
                        <tr>
                            <td></td>
                            <td><button class="button" type="submit" value="submit">Lookup</button> </td>
                        </tr>
                    </tbody>';

        $html .= '</form>';

        $html .= '</div><!-- end post-body-content -->';

        $html .= '<div id="postbox-container-1" class="postbox-container">
                    </div><!-- postbox-container 1 -->

                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->

                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->';

        echo $html;

        if (!empty($_POST['google_lookup'])) {

            $post_address = str_replace('   ', ' ', $_POST['address']);
            $post_address = str_replace('  ', ' ', $post_address);
            $post_address = urlencode(trim($post_address));

            $address = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$post_address .'&key=AIzaSyBxUvKYE0LMTbz0VOtPxfRqHXWFyVqlF2I';

            echo 'Final Address ' . $address . '<br><br>';

            $details = json_decode(file_get_contents($address));

            print '<pre>';
            print_r($details);
            print '</pre>';

            print '<a target="_blank" href="https://www.google.com/maps/place/';
            print_r($details->results[0]->geometry->location->lat);
            print ',';
            print_r($details->results[0]->geometry->location->lng);
            print '"><strong>Google Map Link</strong></a><br><br>';

            $tract_address = 'https://geocoding.geo.census.gov/geocoder/geographies/coordinates?x='.$details->results[0]->geometry->location->lng.'&y='.$details->results[0]->geometry->location->lat.'&benchmark=4&vintage=4&format=json';

            echo $tract_address . '<br>';

            $details = json_decode(file_get_contents($tract_address));

            print '<pre>';
            print_r($details);
            print '</pre>';

            print 'This is the tract number : ' . $details->result->geographies->{'Census Tracts'}[0]->TRACT;

//        include 'map-view.php';

        }
    }

    public function kml_parse_example () {
        echo '<h3>KML Parse Example</h3>';

        echo 'Loads File from Directory: ' . plugin_dir_path(__FILE__). 'cb_2015_08_tract_500k.kml <br><br><br>';

        // Loads the XML file
        if (file_exists(plugin_dir_path(__FILE__). 'cb_2015_08_tract_500k.kml')) {

            $kml_object = simplexml_load_file( plugin_dir_path(__FILE__). 'cb_2015_08_tract_500k.kml');

//            print '<pre>'; print_r($kml_object->Document->Folder->Placemark[34]); print '</pre>';
//            print '<pre>'; print_r($kml_object); print '</pre>';

            foreach ($kml_object->Document->Folder->Placemark as $mark) {
                print $mark->ExtendedData->SchemaData->SimpleData[2] . '<br>';
                print $mark->Polygon->outerBoundaryIs->LinearRing->coordinates . '<br><br>';
            }

        } else {

            print plugin_dir_path(__FILE__). 'cb_2015_08_tract_500k.kml';
            echo 'File does not exist';

        }



    }

}


