<?php


/**
* This will allow easy handling of a php upload
* @author: Mihai Ionut Vilcu (ionutvmi@gmail.com)
* 2-July-2013
*/
class Upload
{

	var $settings = array(
		'folder' => '.', // the folder where the images will be placed
		'isImage' => 0, // if true it will treat files as images
		'maxSize' => 20, // the max allowed size in MB
		'allowed_extensions' => array(), // an array of lowercase allowed extensions, (!) IF EMPTY ALL ARE ALLOWED (!)
		'overwrite' => 1, // if true it will overwrite the file on the server in case it has the same name
		'custom_names' => false //  an array of custom names, it will be handeled circullary, if the array ends but there are files to be uploaded it will start from the top

		);

	var $errors = array(); // will hold the errors
	var $success = array(); // will hold the success messages
	var $allowed_chars = "a-z0-9_.-"; // allowed chars in a file name, case insensitive

	function __construct($settings = array()) {
		// we update the settings
		$this->updateSettings($settings);
	}

	/**
	 * Will process the files
	 * @param  string $inputName the name of the input to be checked
	 * @param  array  $settings  settings
	 * @return array/string            uploaded file(s) name
	 */
	function upload($inputName = 'file', $settings = array()) {
		// we update the settings
		$this->updateSettings($settings);

		if(!isset($_FILES[$inputName])) // if we have no file we have nothing to do
			return false;



		if(is_array($inputName) || is_object($inputName)) { // multiple input names
			$result = array();

			foreach ($inputName as $file)
				$result[] = $this->handleFiles($file);

			return $result;
		} else { // single input name
			return $this->handleFiles($_FILES[$inputName]);
		}

	}

	/**
	 * Will handle the files and perform validations
	 * @param  array $files the array of the files from $_FILES
	 * @return array        array with the uploaded files
	 */
	function handleFiles($files) {
		$files = $this->reArrayFiles($files);

		if(!is_writable($this->settings['folder'])) {
			$this->errors[] = array($this->settings['folder'], " This folder is not writable !");
			return false;
		}

		$result = array();
		$i = 0;
		foreach ($files as $file) {
			$file['name'] = $this->filterFilename($file['name']);

			// if no filename nothing to do
			if(trim($file['name']) == '')
				continue;

			if($file['error'] > 0) {
				$this->errors[] = array($file['name'], $this->codeToMessage($file['error']));
				continue;
			}
			// we check the file size
			if($file['size'] > $this->settings['maxSize'] * 1024 * 1024) {
				$this->errors[] = array($file['name'], "The size of the file exceeds the allowed limit (".$this->settings['maxSize']."MB).");
				continue;
			}

			// check the extension, remember if settings allowed_extensions is empty it will return allow all of them
			$info = pathinfo($file['name']);
			$info['extension'] = isset($info['extension']) ? $info['extension'] : ''; // in case the file name has no extension

			if(!empty($this->settings['allowed_extensions']) && !in_array(strtolower($info['extension']), $this->settings['allowed_extensions'])) {
				$this->errors[] = array($file['name'], "This extension is not allowed !");
				continue;
			}

			// we build the path for upload
			if(!empty($this->settings['custom_names'])) {
				// keep it circular
				if($i == count($this->settings['custom_names']))
					$i = 0;

				$upload_path = rtrim($this->settings['folder'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$this->settings['custom_names'][$i++];
			}
			else
				$upload_path = rtrim($this->settings['folder'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file['name'];

			// check if the file exists on the server
			if(!$this->settings['overwrite'] && file_exists($upload_path)){
				$this->errors[] = array($file['name'], "This file already exists, rename it !");
				continue;
			}


			if($this->settings['isImage']) { // we need to handle it as an image

				if($img = $this->imagecreatefromfile($file['tmp_name'], $function)) {
					if($function($img, $upload_path)) { // we pass the image through a filter
						$this->success[] = array($file['name'], "It was uploaded successfully !");
						$result[] = $file['name'];
					}

					// remove uploaded file
					@unlink($file['tmp_name']);
				} else
					$this->errors[] = array($file['name'], "This file is not a valid image !");


			} else { // we treat it as a normal file
				if(move_uploaded_file($file['tmp_name'], $upload_path)) {
					$this->success[] = array($file['name'], "It was uploaded successfully !");
					$result[] = $file['name'];
				}
			}

		}
		return $result;
	}

	/**
	 * it will rearrange the array with the info about the files generated in $_FILES
	 * @author: http://www.php.net/manual/en/features.file-upload.multiple.php#53240
	 * @edited: Mihai Ionut Vilcu (it will handle one file also)
	 * @param  array $file_post the $_FILES array
	 * @return array the new array
	 */
	function reArrayFiles(&$file_post) {

	    $file_ary = array();

		if(!is_array($file_post['name']))
			return array($file_post);

	    $file_count = count($file_post['name']);
	    $file_keys = array_keys($file_post);

	    for ($i=0; $i<$file_count; $i++) {
	        foreach ($file_keys as $key) {
	            $file_ary[$i][$key] = $file_post[$key][$i];
	        }
	    }

	    return $file_ary;
	}

	/**
	 * generates the html code for a basic upload form, it can generate the input fields only or the compleate form
	 * @param  integer $number        the number of inputs
	 * @param  string  $name          the name of the input(s)
	 * @param  integer $complete_form if true it will generate the compleate forms insetead of just input fields
	 * @param  string  $location      location where the form will send the data(in case the form is compleate)
	 * @param  array   $extra         extra attributes for input(s)
	 * @param  array   $extra_form    extra attributes for form
	 * @return string                 html code generated
	 */
	function generateInput($number = 1, $name = 'file', $complete_form = 0, $location = '?', $extra = array(), $extra_form = array()) {

		$html = $attr = $attr_form = "";

		foreach ($extra as $key => $value)
			$attr .= " $key = '$value' ";
		foreach ($extra_form as $key => $value)
			$attr_form .= " $key = '$value' ";


		for($i = 0; $i < $number; $i++)
			$html .= "File ".($i+1)."
			<input type='file' name='$name".($number > 1 ? "[]" : "")."'$attr>
			<br/>
			";

		if($complete_form == 1)
			$html = "<form action='$location' method='post' enctype='multipart/form-data' $attr_form>
			".$html."
			<input type='submit' value='Upload'>\n</form>";

		return $html;

	}


	/**
	 * gets the max file size for upload allowed on the server in MB
	 * @return integer the max size in MB
	 */
	function getMaxUpload() {
		$max_upload = (int)(ini_get('upload_max_filesize'));
		$max_post = (int)(ini_get('post_max_size'));
		$memory_limit = (int)(ini_get('memory_limit'));
		return min($max_upload, $max_post, $memory_limit);
	}

	/**
	 * makes sure that the file name only contains allowed chars
	 * @param  string $filename file name
	 * @return string           filtered file name
	 */
	function filterFilename($filename) {
		return preg_replace("/[^$this->allowed_chars]/i", "_", $filename);
	}
	/**
	 * it will interpret the file upload error codes
	 * @param  integer $code the error code
	 * @return string       error message
	 */
	function codeToMessage($code) {
	    switch ($code) {
	        case UPLOAD_ERR_INI_SIZE:
	            $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
	            break;
	        case UPLOAD_ERR_FORM_SIZE:
	            $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
	            break;
	        case UPLOAD_ERR_PARTIAL:
	            $message = "The uploaded file was only partially uploaded";
	            break;
	        case UPLOAD_ERR_NO_FILE:
	            $message = "No file was uploaded";
	            break;
	        case UPLOAD_ERR_NO_TMP_DIR:
	            $message = "Missing a temporary folder";
	            break;
	        case UPLOAD_ERR_CANT_WRITE:
	            $message = "Failed to write file to disk";
	            break;
	        case UPLOAD_ERR_EXTENSION:
	            $message = "File upload stopped by extension";
	            break;

	        default:
	            $message = "Unknown upload error";
	            break;
	    }
	    return $message;
	}

	/**
	 * makes sure that the settings are updated and correct
	 * @param  array $settings new settings
	 * @return void
	 */
	function updateSettings($settings) {
		$this->settings = array_merge($this->settings, $settings);
		$this->settings['maxSize'] = min($this->settings['maxSize'], $this->getMaxUpload());
	}


	/**
	 * will create an image from a file
	 * @credits: http://www.php.net/manual/en/function.imagecreate.php#81831
	 * @edited: Mihai Ionut Vilcu (ionutvmi@gmail.com) - added $fun
	 * @param  string  $path           path to the file
	 * @param  string $fun it will hold the function required for adding image data in the file
	 * @param  boolean $user_functions if true you need to have defined a function imagecreatefrombmp you can find one http://www.php.net/manual/en/function.imagecreatefromwbmp.php#86214
	 * @return resource/false                  false if it fails
	 */
	function imagecreatefromfile($path, &$fun, $user_functions = false)
	{
	    $info = @getimagesize($path);

	    if(!$info)
	    {
	        return false;
	    }

	    $functions = array(
	        IMAGETYPE_GIF => 'imagecreatefromgif',
	        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
	        IMAGETYPE_PNG => 'imagecreatefrompng',
	        IMAGETYPE_WBMP => 'imagecreatefromwbmp',
	        IMAGETYPE_XBM => 'imagecreatefromwxbm',
	        );

	    if($user_functions)
	    {
	        $functions[IMAGETYPE_BMP] = 'imagecreatefrombmp';
	    }

	    if(!$functions[$info[2]])
	    {
	        return false;
	    }

	    if(!function_exists($functions[$info[2]]))
	    {
	        return false;
	    }
	    $fun = str_replace("createfrom", "", $functions[$info[2]]);

	    $targetImage = $functions[$info[2]]($path);
	    // fix for png transparency
		imagealphablending( $targetImage, false );
		imagesavealpha( $targetImage, true );
		return $targetImage;
	}

}

