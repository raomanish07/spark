<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");

function write_log($log_msg) {
	$log_filename = "logs";
	if (!file_exists($log_filename)) {
		mkdir($log_filename, 0777, true);
	}

	$log_file_data = $log_filename.'/debug.log';
	file_put_contents($log_file_data, json_encode($log_msg) . "\n", FILE_APPEND);
}
	
$dir = "./client/units/";
function findUnit($str){
	$arr = [];
	$logoImg = "";
	if($tdh = opendir($GLOBALS['dir'] . '/' . $str)){
		while (($tfile = readdir($tdh)) !== false){
			if($tfile != "." && $tfile != "..") {
				
				if(strpos($tfile, 'jpg') || strpos($tfile, 'png') || strpos($tfile, 'svg')){
					$logoImg = $tfile;
				} else{
					$obj = new stdClass();
					$obj->type = $tfile;
					$obj->units = [];
					if($dh = opendir($GLOBALS['dir'].'/'.$str.'/'.$tfile)){
						while(($file = readdir($dh)) !== false){
							if($file != "." && $file != "..") {
								$tobj = new stdClass();
								$tobj->size = $file;
								$tabs = [];
								if($dth = opendir($GLOBALS['dir'].'/'.$str.'/'.$tfile.'/'.$file)){
									while(($ifile = readdir($dth)) !== false){
										if($ifile != "." && $ifile != "..") {
											if(strpos($ifile, 'jpg') || strpos($ifile, 'png')){
												$tindex = (int) explode('_', $ifile)[1] - 1;
												$timgindex = (int) explode('_', $ifile)[2] - 1;
												
												if(empty($tabs[$tindex])){
													$tabs[$tindex] = [];
												}
												
												$tabs[$tindex][$timgindex] = $ifile;
											}
										}
									}
									closedir($dth);
								}
								
								$tobj->tabs = $tabs;
								array_push($obj->units, $tobj);
							}
						}
						closedir($dh);
					}
					array_push($arr, $obj);
				}
			}
		}
		closedir($tdh);
	}
	
	$finalObj = new stdClass();
	$finalObj->logo = $logoImg;
	$finalObj->name = $str;
	$finalObj->path = getcwd() .'/client';
	$finalObj->products = $arr;
	return [$finalObj];
}

function uploadFolder($path, $location){
	$res = array();
	mkdir($path, 0777, true);
	
	$res["status"]=201;
	$res["message"]="initilize";
	
	if(move_uploaded_file($_FILES['file']['tmp_name'], $location)){  
		$zip = new ZipArchive();
		write_log($path);
		if($zip->open($location)){  
			$zip->extractTo($path);  
			$zip->close();  
		} 
		$files = scandir($path);  
		//$name is extract folder from zip file

		write_log($files);
		foreach($files as $file){
			write_log($file);
			$tmp_ref = explode(".", $file);
			$file_ext = end($tmp_ref);
			$allowed_ext = array('jpg', 'png');
 
			if(in_array($file_ext, $allowed_ext)){  
				$new_name = md5(rand()).'.' . $file_ext;
				copy($path.$name.'/'.$file, $path . $new_name);  
				unlink($path.$name.'/'.$file);  
			}  
		
		}  
		unlink($location);  
		$res["status"]=200;
		$res["message"]="sucess!";
		//rmdir($path);
	}
	
	return $res;
}

if(isset($_GET["unit"])) {
	$uarr = [];
	$tunit = "";
	if($_GET["unit"] == "latest"){
		if($tdh = opendir($GLOBALS['dir'])){
			while (($tfile = readdir($tdh)) !== false){
				if(strlen($tfile) > 2) {
					if(strlen($tunit) == 0){
						$tunit = $tfile;
					}else if(filectime($GLOBALS['dir'] . '/' .$tfile) > filectime($GLOBALS['dir'] . '/' .$tunit)){
						$tunit = $tfile;
					}
				}
			}
		}
		
		$uarr = findUnit($tunit);
	}else {
		$uarr = findUnit($_GET['unit']);
	}
	
	echo json_encode($uarr);
}

if(isset($_GET["allUnits"])) {
	$arrAllUnits = [];
	if($tdh = opendir($GLOBALS['dir'])){
		while (($tfile = readdir($tdh)) !== false){
			if(strlen($tfile) > 2) {
				array_push($arrAllUnits, $tfile);
			}
		}
	}
	
	echo json_encode($arrAllUnits);
}


if($_SERVER['REQUEST_METHOD']=="POST") {
	$result=array();
	
	$info = $_POST["info"];
	$override = $_POST["override"];
	
	write_log("************Logs for uploading files*****************");
	
	if($info == "upload"){
		write_log($info);
		write_log($_FILES['file']['name']);
		
		if($_FILES['file']['name']){ 
			$file_name = $_FILES['file']['name'];  
			$array = explode(".", $file_name);  
			$name = $array[0];
			$ext = $array[1];
			
			if($ext == 'zip'){
				$path = $GLOBALS['dir'] . $name;  
				$location = $path . $file_name;
				
				write_log($override);
				if(file_exists($path) && $override=="false"){
					write_log("folder allready there. ask for replace");
					$result["status"]=2;
					$result["message"]="Folder already exist! do you want to replace it?";
				}else{
					$result = uploadFolder($path, $location);
				}
				
			}else{
				$result["status"]=0;
				$result["message"]="upload only zip files";
			}
		}else{
			$result["status"]=0;
			$result["message"]="something went wrong!!!! try again later.";
		}
	}
	
	echo json_encode($result);
}

?>