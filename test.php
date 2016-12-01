<?php include_once('ImageOperations.php');
$folderPath = '/var/www/html/xyz.com/newFolder';
$waterMarkImage = '/var/www/html/xyz.com/images/watermark_logo.png';
if (!empty($_FILES))
 {
   $imageObj = new ImageOperations($_FILES['userfile']);
   if($imageObj->checkStatus()){
		if(isset($_POST['watermark']) && $_POST['watermark']){
			if(!$imageObj->watermarkAndSaveImage('XyzWatermark', $folderPath, $waterMarkImage)){
			   echo $imageObj->getError();	
			}
		}else{
			if(!$imageObj->saveImage('XyzwithoutWatermark',$folderPath,1)){
			   echo $imageObj->getError();	
			}
		}
	}else{
		echo $imageObj->getError();	
	}
 }
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title> Image Operations By VinayKant</title>
</head>

<body>
<form enctype="multipart/form-data" method="POST">
     <!-- MAX_FILE_SIZE must precede the file input field -->
     <input type="hidden" name="MAX_FILE_SIZE" value="300000" />
     <!-- Name of input element determines name in $_FILES array -->
     Upload main file: <input name="userfile" type="file" />*<br>
	 Apply Watermark: <input name="watermark" type="checkbox" value='1' /><br>
     <input type="submit" value="Send File" />
 </form> 
</body>
</html>