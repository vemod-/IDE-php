<?php
	function CreateBlankPNG($w, $h)
	{
		$im = imagecreatetruecolor($w, $h);
		imagesavealpha($im, true);
		$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
		imagefill($im, 0, 0, $transparent);
		return $im;
	}
	$image = isset($_GET['image']) ? $_GET['image'] : null;
	if (!$image)
	{
		exit;
	}
	$desth=$_GET['desth']?$_GET['desth']:0;
	$destw=$_GET['destw']?$_GET['destw']:0;
	if ($desth*$destw==0)
	{
		exit;
	}
	$srcx=$_GET['srcx']?$_GET['srcx']:0;
	$srcy=$_GET['srcy']?$_GET['srcy']:0;
	$srch=$_GET['srch']?$_GET['srch']:0;
	$srcw=$_GET['srcw']?$_GET['srcw']:0;
	$desth=$_GET['desth']?$_GET['desth']:0;
	$destw=$_GET['destw']?$_GET['destw']:0;
	//create images
	$src=imagecreatefrompng($image);
	$dest=CreateBlankPNG($destw,$desth);
	imagecopyresampled($dest, $src,0,0,$srcx,$srcy,$destw,$desth,$srcw,$srch);
	header('Content-type: image/png');
	imagepng($dest);
	imagedestroy($dest);
	imagedestroy($src);
?>