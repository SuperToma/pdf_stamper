<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit (300);
@ob_end_clean();

$folderOriginals = 'originals';
$folderStamped = 'stamped';

$originalPDFs = glob($folderOriginals.'/*.pdf');

if(empty($originalPDFs)) {
	die('No PDFs found in folder "'.$folderOriginals.'". Please add yours inside.');
}

if(!is_dir($folderStamped)) {
	die('Folder "'.$folderStamped.'" does not exists. Please create.');
}

if(!is_writable($folderStamped)) {
	die('Folder "'.$folderStamped.'" is not writable. Please check rights.');
}

//Clean old files
foreach(glob($folderStamped.'/*.pdf') as $oldPDF) {
	unlink($oldPDF);
}

foreach(glob($folderStamped.'/*.jpg') as $oldJPG) {
	unlink($oldJPG);
}

// Form is sent
if(isset($_POST['pdf'])) {
	$startTime = $tmpTime = microtime(true);

	$pdf = new Imagick();
	$pdf->setResolution(200, 200);

	echo 'Starting opening file: "'.$_POST['pdf'].'" ...<br />';
	flush();

	$pdf->readImage($_POST['pdf']);
	$nbPages = $pdf->getNumberImages();

	echo $nbPages.' pages found ('.round(microtime(true) - $startTime, 2).' sec.)<br />';
	flush();

	$pdf->setImageCompressionQuality(100);
	
	// Stamp each page of the PDF
	for ($i = 0; $i < $nbPages; $i++) {
		$tmpTime = microtime(true);
        $pdf->setIteratorIndex($i);

        $stamp = new ImagickDraw();
	    $stamp->setFillColor('orange');
	    $stamp->setFillOpacity(.4);
	    $stamp->setFontSize(120);
	    $stamp->setFontWeight(800);
	    $stamp->setGravity(Imagick::GRAVITY_CENTER);
 	    $pdf->annotateImage($stamp, 0, 0, -56, $_POST['name']);

 	    $pdf->writeImage($folderStamped.'/'.$i.'.jpg');

        echo "Page ".($i + 1)." stamped (".round(microtime(true) - $tmpTime, 2)." sec)<br />";
		flush();
	}

	// Make final PDF with all images
	$filename = ltrim($_POST['pdf'], $folderOriginals.'/');
	$filename = rtrim($filename, '.pdf');
	$filename .= '_'.$_POST['name'].'.pdf';

	$images = [];
	for ($i = 0; $i < $nbPages; $i++) {
		$images[] = $folderStamped.'/'.$i.'.jpg';
	} 

	echo 'Exporting PDF file...<br />';
	flush();
	
	$tmpTime = microtime(true);
	$pdf = new Imagick($images);
	$pdf->setImageFormat('pdf');
	$pdf->writeImages($folderStamped.'/'.$filename, true);

	echo 'Exported ! ('.round(microtime(true) - $tmpTime, 2).' sec)<br /><br />';

	echo 'Finished. (total: '.round(microtime(true) - $startTime, 2).' sec)<br /><br />';

	echo '
	<center>
		<a target="_blank" href="./'.$folderStamped.'/'.$filename.'">'.$filename.'</a>
	</center>
	<hr />';
}
?>

<center>
	<form action="" method="POST">
		<table>
			<tr>
				<td>Text to stamp: </td>
				<td><input type="text" name="name" width="120" value="Sold to " /></td>
			</tr>
			<tr>
				<td>pdf: </td>
				<td>
					<select name="pdf">
						<option value="">-- Choose a pdf --</option>
						<?php
							foreach (array_reverse($originalPDFs) as $file) {
								$filename = ltrim($file, $folderOriginals.'/');
								echo '<option value="'.$file.'">'.$filename.'</option>';
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center"><br /><br /><input type="submit" /></td>
			</tr>
	</form>
</center>