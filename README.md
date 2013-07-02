PHP UPLOAD CLASS
=================  

This is an easy to use upload class for your projects.  
  
Example:  
```php
<?php

include 'upload.class.php';  
  
$upload = new Upload;  

$upload->upload(); // this will do all the work  

echo "Upload one file: ";  
echo $upload->generateInput(1, "file", 1);  
?>  
```  

it also has support for multiple files

```php
<?php  
echo "Upload multiple files: ";  
echo $upload->generateInput(3, "file", 1, '?', array('class' => 'btn'), array('class' => 'frm'));  
?>  
```

Default settings:
```php
$settings = array(  
		'folder' => '.', // the folder where the images will be placed  
		'isImage' => 0, // if true it will treat files as images  
		'maxSize' => 2, // the max allowed size in MB  
		'allowed_extensions' => array(), // an array of lowercase allowed extensions, (!) IF EMPTY ALL ARE ALLOWED (!)  
		'overwrite' => 0 // if true it will overwrite the file on the server in case it has the same name  
		);  
```
Errors and success messages;
	var $errors = array(); // will hold the errors
	var $success = array(); // will hold the success messages

