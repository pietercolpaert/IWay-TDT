<?php
	try{
	//walloonia
	for($i = 0; $i<=50; $i++){
		$file = 'http://trafiroutes.wallonie.be/images_uploaded/cameras/image'.$i.'.jpg';
		$newfile = 'images/wallonia/camera_'.$i.'.jpg';
		copy($file, $newfile);
	}
	//flanders
	$page = file_get_contents("http://www.verkeerscentrum.be/verkeersinfo/camerabeelden/antwerpen"); 
	//Recherche des liens 
	preg_match_all('#src="/camera-images/Camera_(.*?)"(.*?)>#is',$page,$resultat,PREG_PATTERN_ORDER); 
	$nbre_liens = count($resultat[1]);
	$j = 0;
	//Listage des liens trouvés 
	foreach ($resultat[1] as $liens) { 
		$file = 'http://www.verkeerscentrum.be/camera-images/Camera_'.$liens;
		$newfile = 'images/flanders/image_antwerpen_'.$j.'.jpg';
		copy($file, $newfile);
		$j++;
	}

	//Chargement du contenu de la page dans une variable
	$page = file_get_contents("http://www.verkeerscentrum.be/verkeersinfo/camerabeelden/gent"); 
	//Recherche des liens 
	preg_match_all('#src="/camera-images/Camera_(.*?)"(.*?)>#is',$page,$resultat,PREG_PATTERN_ORDER); 
	$nbre_liens = count($resultat[1]);
	$l = 0;
	//Listage des liens trouvés 
	foreach ($resultat[1] as $liens) { 
		$file = 'http://www.verkeerscentrum.be/camera-images/Camera_'.$liens;
		$newfile = 'images/flanders/image_gand_'.$l.'.jpg';
		copy($file, $newfile);
		$l++;
	}

	//Chargement du contenu de la page dans une variable
	$page = file_get_contents("http://www.verkeerscentrum.be/verkeersinfo/camerabeelden/lummen"); 
	//Recherche des liens 
	preg_match_all('#src="/camera-images/Camera_(.*?)"(.*?)>#is',$page,$resultat,PREG_PATTERN_ORDER); 
	$nbre_liens = count($resultat[1]);
	$m = 0;
	//Listage des liens trouvés 
	foreach ($resultat[1] as $liens) { 
		$file = 'http://www.verkeerscentrum.be/camera-images/Camera_'.$liens;
		$newfile = 'images/flanders/image_lummen_'.$m.'.jpg';
		copy($file, $newfile);
		$m++;
	}
	
	//brussels
	//Chargement du contenu de la page dans une variable
	$page = file_get_contents("http://www.verkeerscentrum.be/verkeersinfo/camerabeelden/brussel"); 

	//Recherche des liens 
	preg_match_all('#src="/camera-images/Camera_(.*?)"(.*?)>#is',$page,$resultat,PREG_PATTERN_ORDER); 

	$nbre_liens = count($resultat[1]);
	$k = 0;
	//Listage des liens trouvés 
	foreach ($resultat[1] as $liens) { 
		$file = 'http://www.verkeerscentrum.be/camera-images/Camera_'.$liens;
		$newfile = 'images/brussels/image_brussel_'.$k.'.jpg';
		copy($file, $newfile);
		$k++;
	}

	//Chargement du contenu de la page dans une variable
	$page = file_get_contents("http://www.bruxellesmobilite.irisnet.be/cameras/json/fr/"); 

	//Recherche des liens 
	preg_match_all('#/static/cameras/Cam(.*?)"(.*?)#is',$page,$resultat,PREG_PATTERN_ORDER); 
	$nbre_liens = count($resultat[1]);
	$n = 0;
	//Listage des liens trouvés 
	foreach ($resultat[1] as $liens) { 
		$file = 'http://www.bruxellesmobilite.irisnet.be/static/cameras/Cam'.$liens;
		$newfile = 'images/brussels/image_ringbxl_'.$n.'.jpg';
		copy($file, $newfile);
		$n++;
	}
	}catch(Exception $e){
		return false;
	}
?>
