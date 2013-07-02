<style>
	body {
		background-color: #333;
		color: #fff;
	}
</style>

<?php

include 'upload.class.php';


$upload = new Upload;

$upload->upload(); // this will do all the work

$upload->upload('img', array('isImage' => 1)); // this will do all the work


if($upload->errors) {
	foreach ($upload->errors as $error) {
		echo "Error: ".$error[0]." - ".$error[1]. "<br/>";
	}
}

if($upload->success) {
	foreach ($upload->success as $success) {
		echo "Success: ".$success[0]." - ".$success[1]. "<br/>";
	}
}


echo "Upload one file: ";
echo $upload->generateInput(1, "file", 1);

echo "Upload multiple files: ";	
echo $upload->generateInput(3, "file", 1, '?', array('class' => 'btn'), array('class' => 'frm'));


echo "Upload image file: ";
echo $upload->generateInput(1, "img", 1);