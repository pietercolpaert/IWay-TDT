<?php
/* Copyright (C) 2011 by iRail vzw/asbl
 *
 * Author: Quentin Kaiser <kaiserquentin@gmail.com>
 * License: AGPLv3
 *
 * This method of IWay will get the live highway cameras
 */

class Camera extends AResource{

     private $lang;
     private $region;
     private $from;
     private $area;
     private $max;

     
     public static function getParameters(){
	  return array("lang" => "Language in which the cameras should be returned", 
		       "region" => "region that you want data from",
		       "max" => "Maximum of cameras you want to retrieve",
		       "from" => "",
		       "area" => "Area around from parameter where you want to retrieve cameras"			
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
		$cameras = R::find("cameras", "region = '".$this->region."'");

		$result = new stdClass();
		for($i=0; $i < count($cameras); $i++){
			
			$result->item[$i] = new stdClass();
			$result->item[$i]->highway = $cameras[$i]->highway;
			$result->item[$i]->img = $cameras[$i]->img;
			$result->item[$i]->lat = $cameras[$i]->lat;
			$result->item[$i]->lng = $cameras[$i]->lng;
		}
		return $result;
     }
   public function call(){
	  	return $this->getData();
   }

    
 
     public static function getAllowedPrintMethods(){
	  return array("json","xml", "jsonp", "php", "html");
     }

     public static function getDoc(){
	  return "This is a function which will return all the live highway cameras";
     }

}

?>
