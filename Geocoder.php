<?php

/* Copyright (C) 2011 by iRail vzw/asbl */
/* 
  This file is part of iWay.

  iWay is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  iWay is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with iWay.  If not, see <http://www.gnu.org/licenses/>.

  http://www.beroads.com

  Source available at http://github.com/QKaiser/IWay
 */

/**
 * All functionnalities about geolocation (get coordinates from API like 
 * GMap, Bing or OSM; compute distance between coordinates).
 */
class Geocoder {

    /*  
	static vars so when we are asking coordinates for a place that have been geocoded previously, we return coordinates
	that we have stored before
    */

    public static $from = array();
    public static $from_coordinates = array();


    public static function distance($from, $to){

	
	$earth_radius = 6371.00; // km

	$delta_lat = $to["lat"]-$from["lat"];
	$delta_lon = $to["lng"]-$from["lng"]; 

	  $alpha    = $delta_lat/2;
	  $beta     = $delta_lon/2;
	  $a        = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($from["lat"])) * cos(deg2rad($to["lat"])) * sin(deg2rad($beta)) * sin(deg2rad($beta)) ;
	  $c        = asin(min(1, sqrt($a)));
	  $distance = 2*$earth_radius * $c;
	  return round($distance);
	   
	}


    public static function cmpDistances($a, $b){

	if($a == $b)
		return 0;
	else 
		return ($a->distance < $b->distance) ? -1 : 1;
    }
    public static function sortByDistance($array){

	usort($array, Geocoder::cmpDistances);

    }
    public static function geocode($address, $tool = "gmap") {

	array_push(Geocoder::$from, $address);
        //gmap api geocoding tool
        if($tool=="gmap") {

            $base_url = "http://maps.google.com/maps/geo?output=xml&key=ABQIAAAAnfs7bKE82qgb3Zc2YyS-oBT2yXp_ZAY8_ufC3CFXhHIE1NvwkxSySz_REpPq-4WZA27OwgbtyR3VcA";
            $request_url = $base_url . "&q=" . urlencode(utf8_encode($address));
            $xml = simplexml_load_file($request_url) or die("url not loading");

            $status = $xml->Response->Status->code;

            //successful geocode
            if (strcmp($status, "200") == 0) {

                $geocode_pending = false;
                $coordinates = $xml->Response->Placemark[0]->Point->coordinates;
                $coordinates = explode(",", $coordinates);
		array_push(Geocoder::$from_coordinates, array("lng"=> $coordinates[0], "lat" => $coordinates[1]));
                

            }
            //too much requests, gmap server can't handle it
            else if (strcmp($status, "620") == 0) {
                array_push(Geocoder::$from_coordinates, array("lng" => 0,"lat" => 0));
            }
            else {
                array_push(Geocoder::$from_coordinates, array("lng" => 0,"lat" => 0));
            }
	    return Geocoder::$from_coordinates[count(Geocoder::$from_coordinates)-1];
        }
        //openstreetmap geocoding tool (Nominatim)
        else if($tool=="osm") {

            $base_url = "http://nominatim.openstreetmap.org/search?q=".utf8_encode($address)."&format=xml&polygon=0&addressdetails=0";

            $xml = simplexml_load_file($base_url) or die("url not loading");

            if(!isset($xml->place)) {
                array_push(Geocoder::$from_coordinates, array("lng" => 0,"lat" => 0));
            }
            else {
                $place = $xml->place[0]->attributes();
                array_push(Geocoder::$from_coordinates, array("lng" => (string)$place['lon'], "lat" => (string)$place['lat']));
            }
	    return Geocoder::$from_coordinates[count(Geocoder::$from_coordinates)-1];
        }
        //bing map api geocoding tool
        else if($tool=="bing") {
            throw new Exception("Not yet implemented.");
        }
        else {
            throw new Exception("Wrong tool parameter, please retry.");
        }
    }

    public static function isGeocoded($address){
	if($index = array_search($address, Geocoder::$from)){
		return Geocoder::$from_coordinates[$index];
	}else{
		return false;
	}
    } 
		

    public static function geocodeData($data, $region, $language) {

        if($region=="wallonia") {
	     $data = explode(" ", $data);
	     if($language == "EN" || $language == "NL" || $language == "DE"){
		$highway = $data[4] . " " . $data[5] ." " . $data[6] ." " . (isset($data[7]) ? $data[7] : '');
	     }
	     else{
	     	$highway = $data[3] . " " . $data[4] ." " . $data[5] ." " . $data[6];
	     }
	     //check of already geocoded
	     if($coords = Geocoder::isGeocoded($highway)){
		return $coords;
	     }else{
		return Geocoder::geocode("Belgium, " . $highway);
	     }           
        }
        else if($region=="flanders") {

	    	$data = explode("->", $data);
			return Geocoder::geocode("Belgium, " . $data[0]);
	
        }
        else{
            throw new Exception("Wrong region parameter, please retry.");
        }

    }
};
?>
