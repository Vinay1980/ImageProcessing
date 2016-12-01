<?php
/*
 * Class Name: ImageOperations
 * Author: VINAY KANT SAHU
 * Email: vinaykant.sahu@gmail.com
 * Purpose: Handel all the common operations related to upload and crop Image Safely 
 * Created Date: Mar 23, 2012
 * */
class ImageOperations 
{
	private $_uploadedFile;
	private $_img; // image object
	private	$_imageWidth;
	private	$_imageHeight;
	private	$_imageType;
	private	$_imageStatus;
	private $_errorMsg = null;
	
	private $_requiredWidth;	// this will be max possible width for the new image, if allowed by current ratio of Image 
	private $_requiredHeight;	// this will be max possible height for the new image, if allowed by current ratio of Image 
	//private $_allowedExtArray = array('jpg', 'jpeg', 'gif','png', 'bmp'); // not required
	
	/*
	 * Function Name: __construct
	 * Purpose: constructortructor 
	 * In Param: $uploadedFile
	 * Out Param: return integer  
	 * */
	 //uploadedFile : $_FILES uploaded file object, in case of multiple images uploaded please use $_FILES[0] 
	function __construct ($uploadedFile)
    {
    	$this->uploadedFile = $uploadedFile;
		//print_r($this->uploadedFile);
		try{
			$this->img = @imagecreatefromjpeg($this->uploadedFile["tmp_name"]);
			$this->imageType = 1;
			if(!$this->img){
				$this->img = @imagecreatefrompng($this->uploadedFile["tmp_name"]);
				$this->imageType = 2;
				if(!$this->img){
					$this->img = @imagecreatefromgif($this->uploadedFile["tmp_name"]);
					$this->imageType = 3;
					if(!$this->img){
						$this->img = @imagecreatefromwbmp($this->uploadedFile["tmp_name"]);
						$this->imageType = 4;
					}
				}
			}
			//var_dump($this->img);
			if($this->img){
				$this->imageStatus =1;
				$this->imageWidth = imagesx($this->img);
				$this->imageHeight = imagesy($this->img);
				$this->requiredWidth = $this->imageWidth;
				$this->requiredHeight = $this->imageHeight;
				
			}else{
				$this->imageStatus =0;
				$this->errorMsg = 'Invalid type of Image';
			}
		}catch(Exception $error ){
			$this->imageStatus =0;
			//print_r($error);
			$this->errorMsg = $error->getMessage();
		}
    }
	/*
	 * Function Name: saveImage
	 * Purpose: save a image 
	 * In Param: $fileName, $folderPath, $type, $width, $height, $quality, $actionIfExist
	 * Out Param: return integer  
	 * */
	 //fileName : 'newfile' a string without ext
	 //folderPath : '/var/www/html/xyz.com/images/newFolder' without succeeding end /
	 //Type : 1 for JPEG, 2 For PNG, 3 for GIF, 4 For BMP
	 //width : required with
	 //height : required height
	 //quality : quality of image in case of jpeg
	 //actionIfExist : 0/1 , in case of 0 if file exist then it will not overwrite, in case of 1, file will be overwrite	 	
	public function saveImage($fileName, $folderPath, $type = 1, $width = null, $height= null, $quality =90, $actionIfExist = 1){
		$newFilePath = $folderPath.'/'.$fileName;
		switch($type){
			case 1:
				$newFilePath .= '.jpg'; 
				break;
			case 2:
				$newFilePath .= '.png'; 
				break;
			case 3:
				$newFilePath .= '.gif'; 
				break;
			case 4:
				$newFilePath .= '.bmp'; 
				break;	
			default :
				$newFilePath .= '.jpg'; 
				break;
		}
		$commonCheck = $this->commonActions($newFilePath, $width, $height, $actionIfExist);
		if($commonCheck){
			$newFilePath = $folderPath.'/'.$fileName;
			try{
				$image_New = imagecreatetruecolor($this->requiredWidth, $this->requiredHeight);
				imagecopyresampled($image_New, $this->img, 0, 0, 0, 0, $this->requiredWidth, $this->requiredHeight, $this->imageWidth, $this->imageHeight);
				$this->saveImageToGivenFilePath($type, $image_New, $newFilePath, $quality )	;
			}catch(Exception $error ){
				//print_r($error);
				$this->errorMsg = $error->getMessage();
				return 0;
			}
		}else{
			return $commonCheck;
		}
		return 1;
	}
	/*
	 * Function Name: watermarkAndSaveImage
	 * Purpose: save a image with watermark 
	 * In Param: $fileName, $folderPath, $waterMarkImage, $type, $width, $height, $quality, $actionIfExist
	 * Out Param: return integer  
	 * */
	 //fileName : 'newfile' a string without ext
	 //folderPath : '/var/www/html/xyz.com/images/newFolder' without succeeding end /
	 //waterMarkImage : '/var/www/html/xyz.com/images/watermark_logo.png' file name with full path of watermark logo
	 //Type : 1 for JPEG, 2 For PNG, 3 for GIF, 4 For BMP
	 //width : required with
	 //height : required height
	 //quality : quality of image in case of jpeg
	 //actionIfExist : 0/1 , in case of 0 if file exist then it will not overwrite, in case of 1, file will be overwrite	 	 
	public function watermarkAndSaveImage($fileName, $folderPath, $waterMarkImage, $type = 1, $width = null, $height= null, $quality =90, $actionIfExist = 1){
		$waterMarkRatio = 3000;
		$newFilePath = $folderPath.'/'.$fileName;
		switch($type){
			case 1:
				$newFilePath .= '.jpg'; 
				break;
			case 2:
				$newFilePath .= '.png'; 
				break;
			case 3:
				$newFilePath .= '.gif'; 
				break;
			case 4:
				$newFilePath .= '.bmp'; 
				break;	
			default :
				$newFilePath .= '.jpg'; 
				break;
		}
		$commonCheck = $this->commonActions($newFilePath, $width, $height, $actionIfExist);
		if($commonCheck){
			if(file_exists($waterMarkImage)){
				try{
					list($src_w, $src_h) = getimagesize($waterMarkImage);
					// resize logo image based on uploaded image
					$src_wOrg = $src_w;
					$src_hOrg  = $src_h;
		
					$ratio = $width/$waterMarkRatio;
					
					$src_w = $src_w*$ratio;
					$src_h = $src_h* $ratio;
					
					// identify x/y position on uploaded image to start watermarking, this is based on new size of logo and uploaded image centre point
					$dst_x = floor(($width/2)- ($src_w/2));
					$dst_y = floor(($height/2)- ($src_h/2));
		
					$imWm = imagecreatefrompng($waterMarkImage);
					//create new 
					$info  = getimagesize($waterMarkImage);
					$image_pNormalWM = imagecreatetruecolor ($src_w, $src_h); 
					$transparency = imagecolortransparent($imWm);
							
					if ($transparency >= 0) {
						$transparent_color = imagecolorsforindex($imWm, $transparency);
						$transparency = imagecolorallocate($image_pNormalWM, $transparent_color['red'], $transparent_color['green'],$transparent_color['blue']);
						imagefill($image_pNormalWM, 0, 0, $transparency);
						imagecolortransparent($image_pNormalWM, $transparency);
					}
					elseif ($info[2] == IMAGETYPE_PNG) {
						imagealphablending($image_pNormalWM, false);
						$color = imagecolorallocatealpha($image_pNormalWM, 0, 0, 0, 127);
						imagefill($image_pNormalWM, 0, 0, $color);
						imagesavealpha($image_pNormalWM, true);
					}
					imagecopyresampled($image_pNormalWM, $imWm, 0, 0, 0, 0, $src_w, $src_h, $src_wOrg, $src_hOrg);
					
					$image_New = imagecreatetruecolor($this->requiredWidth, $this->requiredHeight);
					imagecopyresampled($image_New, $this->img, 0, 0, 0, 0, $this->requiredWidth, $this->requiredHeight, $this->imageWidth, $this->imageHeight);
					imagecopy( $image_New, $image_pNormalWM, $dst_x, $dst_y, 0, 0, $src_w, $src_h);
					$this->saveImageToGivenFilePath($type, $image_New, $newFilePath, $quality )	;
				}catch(Exception $error ){
					//print_r($error);
					$this->errorMsg = $error->getMessage();
					return 0;
				}
			}else{
				$this->errorMsg = 'Watermark does not exist';
				return 0;
			}
			$newFilePath = $folderPath.'/'.$fileName;
			try{
				$this->saveImageToGivenFilePath($type, $this->img, $newFilePath, $quality )	;
			}catch(Exception $error ){
				//print_r($error);
				$this->errorMsg = $error->getMessage();
				return 0;
			}
		}else{
			return $commonCheck;
		}
		return 1;
	}
	/***********************Common Public Functions******************/
	public function checkStatus(){
		return $this->imageStatus;
	}
	public function getError(){
		return $this->errorMsg;
	}
	public function setImageWidth($width){
		$this->requiredWidth = $width;
	}
	public function getImageWidth(){
		return $this->requiredWidth;
	}
	public function setImageHeight($height){
		$this->requiredHeight = $height;
	}
	public function getImageHeight(){
		return $this->requiredHeight;
	}
	
	/*************************Private Function************************/
	/*
	 * Function Name: commonActions
	 * Purpose: common actions 
	 * In Param: $newFilePath, $width = null, $height= null, $actionIfExist = 1
	 * Out Param: return integer  
	 * */
	private function commonActions($newFilePath, $width = null, $height= null, $actionIfExist = 1){
		if($this->checkStatus()){
			if($width){
				$this->setImageWidth($width);
			}
			if($height){
				$this->setImageHeight($height);
			}
			if(!$actionIfExist){
				if(file_exists($newFilePath))	
				{
					$this->errorMsg = 'File already exist at given path.';
					return 0;
				}
			}else{
				if(file_exists($newFilePath))  unlink($newFilePath); // delete file to avoid update error on linux server
			}
			
		}else{
			return $this->checkStatus();
		}
		return 1;
	}
	/*
	 * Function Name: getNewSizeForImage
	 * Purpose: To get new width / height for image based on the current ratio of Image
	 * In Param: none
	 * Out Param: $sizeArray  
	 * */
	private function getNewSizeForImage(){
    	$sizeArray = array("Width"=>0, "Height"=>0);
		$baseWidth = $this->requiredWidth;
		$baseHeight = $this->requiredHeight;
    	
    	$orgWidth = $this->imageWidth;
    	$orgHeight = $this->imageHeight;
    	
    	$orgRatio = $orgWidth / $orgHeight;
    	
    	$newWidth = $baseWidth;
    	$newHeight = $newWidth/$orgRatio;
    	
    	if($newHeight > $baseHeight){
    		$newHeight = $baseHeight;
    		$newWidth = $newHeight* $orgRatio;
    	}
    	$sizeArray = array("Width"=>$newWidth, "Height"=>$newHeight);
    	
    	return $sizeArray;
    }
	/*
	 * Function Name: saveImageToGivenFilePath
	 * Purpose: To save image on server based on image type
	 * In Param: $imageType, $image, $imagePath, $quality=90 only used for jpeg
	 * Out Param: None  
	 * */
    private function saveImageToGivenFilePath($imageType, $image, $imagePath, $quality = 90){
    	switch($imageType){
    		case 1:
    			imagejpeg($image, $imagePath, $quality);
    			break;
    		case 2:
    			imagepng($image, $imagePath);
    			break;
    		case 3:
    			imagegif($image, $imagePath); 
    			break;	
    		case 4:
    			imagewbmp($image, $imagePath); 
    			break;
    	}
			
    }
	/*
	 * Function Name: __destruct
	 * Purpose:  Destructor  
	 * In Param: None
	 * Out Param: None
	 * */
    function __destruct(){
    }
	
}

