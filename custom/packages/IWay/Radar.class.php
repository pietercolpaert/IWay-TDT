<?php
/* Copyright (C) 2011 by iRail vzw/asbl
 *
 * Author: Quentin Kaiser <kaiserquentin@gmail.com>
 * License: AGPLv3
 *
 * This method of IWay will get all the radars of belgium territory
 */
include_once 'Geocoder.php';
class Radar extends AResource{

     private $lang;
     private $region;
     private $from;
     private $area;
     private $max;

     public static function getParameters(){
	  return array("lang" => "Language in which the newsfeed should be returned", 
		       "region" => "region that you want data from",
		       "max" => "Maximum of radars you want to retrieve",
		       "from" => "",
		       "area" => "Area around from parameter where you want to retrieve radars"			
		);
     }

     public static function getRequiredParameters(){
	  return array();
     }

     public function setParameter($key,$val){
	  if($key == "lang"){
	       $this->lang = $val;
	  }
	  else if($key == "region"){
		$this->region = $val;
	  }
	  else if($key == "max"){
		$this->max = $val;
	  }
	  else if($key == "from"){
		$this->from = explode(",",$val);
	  }
	  else if($key == "area"){
		$this->area = $val;
	  }
     }

	private function getData(){
	 	R::setup(Config::$DB, Config::$DB_USER, Config::$DB_PASSWORD);
		$radars = R::find("radars", "region = '".$this->region."'");
		$result = new stdClass();
		for($i=0; $i < count($radars); $i++){

			$result->item[$i] = new stdClass();
			$result->item[$i]->name = $radars[$i]->name;
			$result->item[$i]->highway = $radars[$i]->highway;
			$result->item[$i]->lat = $radars[$i]->lat;
			$result->item[$i]->lng = $radars[$i]->lon;
		}
		return $result;
     }
     
     public function call(){
     
      $c = Cache::getInstance();
	  $element = $c->get("radar". $this->region);
	  if(is_null($element)){
			$element = $this->getData();
			
			//TODO fix good timeout value
			$c->set("radar". $this->region, $element, 600);
	  }
	  
      /* From, area and proximity */
	  if($this->from != "" && $this->area > 0){
	      $items = array();
	   	  
		  for($i = 0; $i < count($element->item); $i++){
		  		
				$distance = Geocoder::distance(array("lat"=>$this->from[0], "lng"=>$this->from[1]),array("lat"=>$element->item[$i]->lat, "lng"=>$element->item[$i]->lng));
				if($distance < $this->area){
					$element->item[$i]->distance = $distance;				
					array_push($items, $element->item[$i]);
				}
		 }
		 usort($items, 'Geocoder::cmpDistances');
		 $element->item = $items;
	  }
	  
	  /* Max parameter */
	  //As elements are stored in cache, if a user request items with max parameter there will be missing items for next requests
	  // so I use array_slice, that's NOT lazy :)
	  if($this->max > 0 && $this->max < count($element->item))
			$element->item = array_slice($element->item, 0, $this->max);

		
	  return $element;
     }

    
     
      
 
     public static function getAllowedPrintMethods(){
	  return array("json","xml", "jsonp", "php", "html");
     }

     public static function getDoc(){
	  return "This is a function which will return all belgium radars";
     }

}

?>
