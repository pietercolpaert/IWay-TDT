<?php
/* Copyright (C) 2011 by iRail vzw/asbl
 *
 * Author: Quentin Kaiser <kaiserquentin@gmail.com>
 * License: AGPLv3
 *
 * This method of IWay will get the traffic events of Belgian traffic jams, accidents and works
 */
include_once "Geocoder.php";

class TrafficEvent extends AResource{

     private $lang;
     private $region;
     private $from;
     private $area;
     private $max;

     public static function getParameters(){
	  return array("lang" => "Language in which the newsfeed should be returned", 
		       "region" => "region that you want data from",
		       "max" => "Maximum of events you want to retrieve",
		       "from" => "",
		       "area" => "Area around from parameter where you want to retrieve events"			
		);
     }

     public static function getRequiredParameters(){
	 	return array("lang","region");
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

     public function call(){
      $c = Cache::getInstance();
	  $element = $c->get("traffic" . $this->region . $this->lang);
	  if(is_null($element)){
			$data = $this->getData();
			$element = $this->parseData($data);
			$c->set("traffic" . $this->region . $this->lang, $element, 600);
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

    private function getData(){
	$scrapeUrl = "";
	switch($this->region){
		case "wallonia" : 
			$scrapeUrl = 'http://trafiroutes.wallonie.be/trafiroutes/Evenements_'.strtoupper($this->lang).'.rss';

			break;
		case "flanders" : 
			$scrapeUrl = 'http://www.verkeerscentrum.be/verkeersinfo/tekstoverzicht_actueel?lastFunction=info&sortCriterionString=TYPE&sortAscending=true&autoUpdate=&cbxFILE=CHECKED&cbxINC=CHECKED&cbxRMT=CHECKED&cbxINF=CHECKED&cbxVlaanderen=CHECKED&cbxWallonie=CHECKED&cbxBrussel=CHECKED&searchString=&searchStringExactMatch=true';
			break;
		case "brussels" : 
			$scrapeUrl = 'http://www.bruxellesmobilite.irisnet.be/static/mobiris_files/'.$this->lang.'/alerts.json';	
			break;
		case "federal" : 
			if($this->lang == "fr")
				$scrapeUrl = 'http://www.inforoutes.be';
			else
				$scrapeUrl = 'http://www.wegeninfo.be/';
			break;
	}
	return utf8_encode(TDT::HttpRequest($scrapeUrl)->data);	 
     }
     
     private function parseData($data){

	 $result = new stdClass();
	 $i = 0;
	
	 switch($this->region){
		case "wallonia" : 
			
			$xml = new SimpleXMLElement($data);
			foreach($xml->channel->item as $event){
				
							
				$result->item[$i] = new StdClass();					
				$result->item[$i]->category =  $this->extractTypeFromDescription($event->description);
				$result->item[$i]->source = 'Trafiroutes';
				$result->item[$i]->time = $this->parseTime($xml->channel->pubdate);
				$result->item[$i]->message = utf8_decode(htmlspecialchars($event->description));
				$result->item[$i]->location = utf8_decode(htmlspecialchars($event->title));	
				$coordinates = Geocoder::geocodeData($event->title,$this->region, $this->lang);	    
				$result->item[$i]->lat = $coordinates["lat"];
				$result->item[$i]->lng = $coordinates["lng"];
				$i++;	
			   
			}
			break;
		case "flanders" : 
			preg_match_all('/<tr>.*?<td width="2" bgcolor="#EAF0BF"><\/td>.*?<td width="68" height="31" style="width:68px; height=31px" bgcolor="#EAF0BF" align="center" valign="middle"><img border="0" src="images\/(.*?).gif" alt="" width="31" height="31" \/>.*?<\/td>.*? class="Tekst_bericht">(.*?)<\/span>.*?class="Tekst_bericht">(.*?)\s*<\/span>.*?class="Tekst_bericht">(.*?)<\/span>/smi', $data, $matches, PREG_SET_ORDER);
			  //1 = soort
			  //2 = location
			  //3 = message
			  //4 = time
			  $result = new stdClass();
			  $i = 0;

			  foreach($matches as $match){
			       $cat = $match[1];
			       $cat = str_ireplace("ongeval_driehoek","accident",$cat);
			       $cat = str_ireplace("file_driehoek","traffic jam",$cat);
			       $cat = str_ireplace("i_bol","info",$cat);
			       $cat = str_ireplace("werkman","works",$cat);
			       
			       $location = trim(str_replace("\s\s+"," ",strip_tags($match[2])));
				   
				   $pattern = '/(\w)(\d+)/i';
				   $replacement = '$1$2 ';
				   $location =  preg_replace($pattern, $replacement, $location);
			       $result->item[$i] = new StdClass();
			       $result->item[$i]->category = trim(str_replace("\s\s+"," ",strip_tags($cat)));
			       $result->item[$i]->location = $location;
			       $result->item[$i]->message = trim(str_replace("\s\s+"," ",strip_tags($match[3])));
			       $result->item[$i]->time = Time($this->parseTime(trim(str_replace("\s\s+"," ",strip_tags($match[4])))));
   				   $result->item[$i]->source = "Verkeerscentrum";
						
					$coordinates = Geocoder::geocode("Belgium, " . $result->item[$i]->location);
					$result->item[$i]->lat = $coordinates["lat"];
					$result->item[$i]->lng = $coordinates["lng"];
		
			       $i++;
			  }
			break;
		case "brussels" : 
			$json_tab = json_decode($data);
			foreach($json_tab->{'features'} as $element) {
					
				
				$result->item[$i] = new StdClass();
				$result->item[$i]->category = $element->{'properties'}->{'category'};
				$result->item[$i]->source = 'Mobiris';
				//$result->item[$i]->time = date('Y-m-j H:i:s');
				$result->item[$i]->time = time();
				$result->item[$i]->message = $element->{'properties'}->{'cause'};
				$result->item[$i]->location = $element->{'properties'}->{'street_name'};
				$coordinates = Geocoder::geocode("Brussels, " . $result->item[$i]->location);
				$result->item[$i]->lat = $coordinates["lat"];
				$result->item[$i]->lng = $coordinates["lng"];
				
				$i++;
			}
			break;
					
			

		case "federal" : 
				include_once 'simple_html_dom.php';
				
				$html = str_get_html($data);
				$tab = $html->find('TD[class=textehome]');
				$messages = $html->find('font[class=textehome]');

				for($j=8; $j < count($tab); $j+=4) {
				    /* 3 elements by event (name, description, lastUpdate) */
				    for($k=0; $k < 4; $k++) {
						if($k==0)
							$location = $tab[$j+$k]->innertext;
						if($k==3) {
							$time = $tab[$j+$k]->innertext;
						}
						if(($j+$k) < count($messages)) {
							$message = $messages[$j+$k]->innertext;
							$source = explode(":", $message);
							$message = preg_replace('/\s\s+/', ' ',str_replace($source[0].":".$source[1].":", "", $message));
							$source = str_replace(" signale", "", $source[1]);
						}
				    }

				    if(strstr($location, "<table")!= "")
					break;
					

				    $coordinates = Geocoder::geocodeData($message, $this->region, $this->lang);					
					$result->item[$i] = new StdClass();
					$result->item[$i]->message = trim(strip_tags($message));
					$result->item[$i]->location = trim(strip_tags($location));
					$result->item[$i]->category = $this->extractTypeFromDescription($this->region, $result->item[$i]->message);
					$result->item[$i]->source = trim(utf8_decode($source));
					
					//2011-09-11 17:48:57 
					//$datetime = DateTime::createFromFormat('Y-m-d H:i:s', $time);
					$result->item[$i]->time = trim(utf8_encode(html_entity_decode(strip_tags($time))));
					
					
					$coordinates = Geocoder::geocode("Brussels, " . $result->item[$i]->location);
					$result->item[$i]->lat = $coordinates["lat"];
					$result->item[$i]->lng = $coordinates["lng"];
				
					$i++;					
				}				   
			break;				   
		}
		
		
			
		return $result;
	
     }
 
 	  /**
      * Parses the time according to Het Verkeerscentrum
      */
     private function parseTime($str){
     	  $months = array("janv"=>1, "fevr"=>2, "mars"=>3, "avri"=>4, "mai"=>5, "juin"=>6, "juil"=>7, "aout"=>8, "sept"=>9, "octo"=>10, "nove"=>11, "dece"=>12);
     	  switch($this->region){
     	  
     	  		case "wallonia" : 
     	  				//sam., 10 sept. 2011 23:57:43 +0200
     	  			  preg_match("/(\w+)., (\d+) (\w+). (\d+) ((\d\d):(\d\d):(\d\d)) +(\d+)?/",$str,$match);

					  $h = $match[6];
					  $i = $match[7];
					  
					  $d = date("d");
					  $m = date("m");
					  $y = date("y");
					  if(isset($match[3])){
						   $d = $match[2];
						   $m = $months[$match[3]];
						   $y = $match[4];
					  }
					  
					  return mktime($h,$i,0,$m,$d,$y);
					  break;
     	  		case "flanders" : 
     	  			  preg_match("/([0-2][0-9]):([0-5][0-9])( (\d\d)-(\d\d)-(\d\d))?/",$str,$match);
					  $h = $match[1];
					  $i = $match[2];
					  
					  $d = date("d");
					  $m = date("m");
					  $y = date("y");
					  if(isset($match[3])){
						   $d = $match[4];
						   $m = $match[5];
						   $y = $match[6];
					  }
					  $y = "20".$y;
					  return mktime($h,$i,0,$m,$d,$y);
					  break;
     	  
     	  }
		  
     }
     
     private function extractTypeFromDescription($description){
		$type = "OTHER";		
		switch($this->region){
			
			case "wallonia" :
				if(!(stripos($description,"travaux")===false) || !(stripos($description,"chantier")===false)) {
				    $type = "WORKS";
				}else if(!(stripos($description,"accident")===false) || !(stripos($description,"incident")===false) || !(stripos($description,"Perte")===false)
					|| !(stripos($description,"Parking fermé")===false) || !(stripos($description,"Degradation")===false)) {
				    $type = "EVENT";
				}
				break;
			 
			case "flanders" : 
				if(!(stripos($description,"Ongeval")===false) || !(stripos($description,"File")===false)) {
				    $type = "EVENTS";
				}
				elseif(!(stripos($description, "rijstrook afgesloten")==false) || !(stripos($description, "rijstroken afgesloten")==false) ||
						!(stripos($description, "Mobiele onderhoudsvoertuigen")==false)) {
				    $type = "WORKS";
				}
				break;

			case "federal" : 
				if(!(stripos($description,"travaux")===false) || !(stripos($description,"chantier")===false)) {
				    $type = "WORKS";
				}else if(!(stripos($description,"accident")===false)
					    || !(stripos($description,"incident")===false)) {
				    $type = "EVENTS";
				}
				break;

		}
		return $type;

     }

    

	
     public static function getAllowedPrintMethods(){
	  return array("json","xml", "jsonp", "php", "html");
     }

     public static function getDoc(){
	  return "This is a function which will return all the latest traffic events";
     }

}

?>
