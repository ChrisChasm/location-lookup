<?php
/*
   Plugin Name: Location Lookup
   Plugin URI: http://wordpress.org/extend/plugins/location-lookup/
   Version: 0.1
   Author: Chris Chasm
   Description: Looks up a geo location from an address using U.S. census api
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

    /**
     * Load Admin menu into Settings
     */
    public function load_admin_menu_item () {
        add_submenu_page( 'options-general.php', __( 'Location Lookup', 'disciple_tools' ), __( 'Location Lookup', 'disciple_tools' ), 'manage_options', 'location_lookup', array( $this, 'page_content' ) );
    }

    /**
     * Builds the tab bar
     * @since 0.1
     */
    public function page_content() {


        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        /**
         * Begin Header & Tab Bar
         */
        if (isset($_GET["tab"])) {$tab = $_GET["tab"];} else {$tab = 'address_tract';}

        $tab_link_pre = '<a href="tools.php?page=location_lookup&tab=';
        $tab_link_post = '" class="nav-tab ';

        $html = '<div class="wrap">
            <h2>LOCATION LOOKUP</h2>
            <h2 class="nav-tab-wrapper">';

        $html .= $tab_link_pre . 'address_tract' . $tab_link_post;
        if ($tab == 'address_tract' || !isset($tab) ) {$html .= 'nav-tab-active';}
        $html .= '">Address to Tract</a>';

//        $html .= $tab_link_pre . 'settings' . $tab_link_post;
//        if ($tab == 'settings') {$html .= 'nav-tab-active';}
//        $html .= '">Settings</a>';

        $html .= '</h2>';

        echo $html;

        $html = '';
        // End Tab Bar

        /**
         * Begin Page Content
         */
        switch ($tab) {

            case "settings":
                break;
            default:
                $html .= ''.$this->address_to_tract () ;
                break;
        }

        $html .= '</div>'; // end div class wrap

        echo $html;
    }

    /**
     * Address to Track Lookup
     */
    public function address_to_tract ()
    {
        print '<h3>Address to Tract Lookup (U.S. only)</h3>';
        print '<div class="wrap">
                        <div id="poststuff">
                            <div id="post-body" class="metabox-holder columns-2">';
        /*
        * Main left column
        */
        print '<div id="post-body-content">';

        print '<form action="" method="POST">
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
                    </tbody></table></form><br><br>';


        if (!empty($_POST['google_lookup'])) {

            /*************************************************************
             * Collect Post Data
             */
            $post_address = str_replace('   ', ' ', $_POST['address']);
            $post_address = str_replace('  ', ' ', $post_address);
            $post_address = urlencode(trim($post_address));


            /*************************************************************
             * Get Google lat/lng from API
             */
            $address = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$post_address .'&key=AIzaSyBxUvKYE0LMTbz0VOtPxfRqHXWFyVqlF2I';
            $details = json_decode(file_get_contents($address));

            if($details->results[0]) {
                $g_lat = $details->results[0]->geometry->location->lat; //TODO add if then to check if record match exists
                $g_lng = $details->results[0]->geometry->location->lng;
                $g_formatted_address = $details->results[0]->formatted_address;

                print 'You gave us this address: <strong>' . $g_formatted_address. '</strong>';
            } else {
                wp_die('Yikes. Are you searching for an address on earth? Might be our bad. Can you try it again?');

                $g_lat = ''; // complete declarations
                $g_lng = '';
            }


            /*************************************************************
             * Get tract number from Geocoding Census api
             */
            $tract_address = 'https://geocoding.geo.census.gov/geocoder/geographies/coordinates?x='.$g_lng.'&y='.$g_lat.'&benchmark=4&vintage=4&format=json';
            $tract_details = json_decode(file_get_contents($tract_address));

            if($tract_details->result->geographies->{'Census Tracts'}[0]) {
                $tract_size = $tract_details->result->geographies->{'Census Tracts'}[0]->AREALAND;
                $tract_lng = $tract_details->result->geographies->{'Census Tracts'}[0]->CENTLON;
                $tract_lat = $tract_details->result->geographies->{'Census Tracts'}[0]->CENTLAT;
                $tract_geoid = $tract_details->result->geographies->{'Census Tracts'}[0]->GEOID;
                $state_code = $tract_details->result->geographies->{'Census Tracts'}[0]->STATE;

                if($tract_size > 1000000000) {
                    $zoom = 8;
                } elseif ($tract_size > 100000000) {
                    $zoom = 10;
                } elseif ($tract_size > 50000000) {
                    $zoom = 11;
                } elseif ($tract_size > 10000000) {
                    $zoom = 12;
                } elseif ($tract_size > 5000000) {
                    $zoom = 13;
                } else {
                    $zoom = 14;
                }
//                print '<pre>'; print_r($tract_details); print '</pre>';
                print ', so we found that <strong>' . $tract_geoid . '</strong> is your tract number.<br><br>';
            } else {
                wp_die('Yikes. Couldn\'t find that address. Might be our bad. Can you try it again?');

                $tract_lng = ''; // complete declarations
                $tract_lat = '';
                $tract_geoid = '';
                $state_code = '';
                $zoom = '';
            }



            /*************************************************************
             * Load KML file with colorado census info. Extract and format coordinates.
             */

            $kml_object = simplexml_load_file( plugin_dir_path(__FILE__). 'census-data/cb_2016_'.$state_code.'_tract_500k.kml');
//            print '<pre>'; print_r($kml_object); print '</pre>';
            $value = '';

            foreach ($kml_object->Document->Folder->Placemark as $mark) {
                $geoid = $mark->ExtendedData->SchemaData->SimpleData[4];

                if($geoid == $tract_geoid) { // FILTER RETURN TO TRACT NUMBER

                    if($mark->Polygon) {
                        $value .= $mark->Polygon->outerBoundaryIs->LinearRing->coordinates;
                    } elseif ($mark->MultiGeometry) {
                        foreach($mark->MultiGeometry->Polygon as $polygon) {
                            $value .= $polygon->outerBoundaryIs->LinearRing->coordinates;
                        }
                    }
                }
            }

            $value_array = substr(trim($value), 0, -2); // remove trailing ,0 so as not to create an empty array
            $value_array = explode(',0.0 ', $value_array); // create array from coordinates string

            /*************************************************************
             * Create JSON format coordinates. Display in Google Map
             */
            $coordinates = '';
            foreach ($value_array as $va) {
                if(!empty($va)) {
                    $coord = explode(',', $va);
                    $coordinates .= '{lat: '.$coord[1]. ', lng: ' . $coord[0] . '},';
                }
            }
            $coordinates = substr(trim($coordinates), 0, -1);

            ?>
            <style>
                /* Always set the map height explicitly to define the size of the div
            * element that contains the map. */
                #map {
                    height: 600px;
                    width: 100%;
                }
                /* Optional: Makes the sample page fill the window. */
                html, body {
                    height: 100%;
                    margin: 0;
                    padding: 0;
                }
            </style>
            <div id="map"></div>

            <script>
                // This example creates a simple polygon representing the Bermuda Triangle.

                function initMap() {
                    var map = new google.maps.Map(document.getElementById('map'), {
                        zoom: <?php print $zoom; ?>,
                        center: {lng: <?php print $g_lng; ?>, lat: <?php print $g_lat; ?>},
                        mapTypeId: 'terrain'
                    });

                    // Define the LatLng coordinates for the polygon's path.
                    <?php
                        print "var coords = [[";
                            print $coordinates;
                        print "]];";
                    ?>

                    var tracts = [];

                    for (i = 0; i < coords.length; i++) {
                        tracts.push(new google.maps.Polygon({
                            paths: coords[i],
                            strokeColor: '#FF0000',
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            fillColor: '',
                            fillOpacity: 0.2
                        }));

                        tracts[i].setMap(map);

                    }

                }
            </script>
            <script async defer
                    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCcddCscCo-Uyfa3HJQVe0JdBaMCORA9eY&callback=initMap">
            </script>

            <?php

            print '</div><!-- end post-body-content -->';

            print '<div id="postbox-container-1" class="postbox-container">
                    </div><!-- postbox-container 1 -->

                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->

                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->';

        } /* end if statement*/
    }



}


