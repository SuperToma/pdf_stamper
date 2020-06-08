<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit (300);
@ob_end_clean();

const FOLDER_ORIGINALS = 'originals';
const FOLDER_STAMPED = 'stamped';
const FILES_FILTERS = ['- FR', '- EN'];

$originalPDFs = glob(FOLDER_ORIGINALS.'/*.pdf');

if(empty($originalPDFs)) {
    die('No PDFs found in folder "'.FOLDER_ORIGINALS.'". Please add yours inside.');
}

if(!is_dir(FOLDER_STAMPED)) {
    die('Folder "'.FOLDER_STAMPED.'" does not exists. Please create.');
}

if(!is_writable(FOLDER_STAMPED)) {
    die('Folder "'.FOLDER_STAMPED.'" is not writable. Please check rights.');
}

//Clean old files
foreach(glob(FOLDER_STAMPED.'/*.pdf') as $oldPDF) {
    unlink($oldPDF);
}

foreach(glob(FOLDER_STAMPED.'/*.jpg') as $oldJPG) {
    unlink($oldJPG);
}

// Form is sent
if(isset($_POST['pdfs'])) {
    $startTime = microtime(true);

    $filenames = [];
    foreach($_POST['pdfs'] as $pdfPath) {
        $filenames[] = generatePdf($pdfPath);
    }

    echo 'Finished. (total: '.round(microtime(true) - $startTime, 2).' sec)<br /><br />';

    echo '<center>';
    foreach($filenames as $filename) {
        echo '<a href="./'.FOLDER_STAMPED.'/'.$filename.'">'.$filename.'</a><br />';
    }

    echo '</center><hr />';
}

function generatePdf($pdfPath) {
    $startTime = microtime(true);

    $pdf = new Imagick();
    $pdf->setResolution(200, 200);

    echo 'Starting opening file: "'.$pdfPath.'" ...<br />';
    flush();

    $pdf->readImage($pdfPath);
    $nbPages = $pdf->getNumberImages();

    echo $nbPages.' pages found ('.round(microtime(true) - $startTime, 2).' sec.)<br />';
    echo 'Stamping pages: ';
    flush();

    $pdf->setImageCompressionQuality(100);

    // Stamp each page of the PDF
    for ($i = 0; $i < $nbPages; $i++) {
        $startTime = microtime(true);
        $pdf->setIteratorIndex($i);

        $stamp = new ImagickDraw();
        $stamp->setFillColor('orange');
        $stamp->setFillOpacity(.4);
        $stamp->setFontSize(120);
        $stamp->setFontWeight(800);
        $stamp->setGravity(Imagick::GRAVITY_CENTER);
        $pdf->annotateImage($stamp, 0, 0, -56, $_POST['name']);

        $pdf->writeImage(FOLDER_STAMPED.'/'.$i.'.jpg');

        echo ($i + 1)." ".($i == $nbPages - 1 ? '!' : '');
        flush();
    }

    // Make final PDF with all images
    $filename = ltrim($pdfPath, FOLDER_ORIGINALS.'/');
    $filename = rtrim($filename, '.pdf');
    $filename .= '_'.$_POST['name'].'.pdf';

    $images = [];
    for ($i = 0; $i < $nbPages; $i++) {
        $images[] = FOLDER_STAMPED.'/'.$i.'.jpg';
    }

    echo '<br />Creating PDF file... ';
    flush();

    $startTime = microtime(true);
    $pdf = new Imagick($images);
    $pdf->setImageFormat('pdf');
    $pdf->writeImages(FOLDER_STAMPED.'/'.$filename, true);

    echo 'done ('.round(microtime(true) - $startTime, 2).' sec)<br /><br />';

    return $filename;
}
?>

<html>
<center>
    <form action="" method="POST">
        <table>
            <tr>
                <td>Text to stamp: </td>
                <td><input type="text" name="name" width="120" value="Sold to " /></td>
            </tr>
            <tr>
                <td>Filter: </td>
                <td>
                    <select id="filterSelector" onchange="doFilter(this)">
                        <?php
                        foreach(FILES_FILTERS as $filter) {
                            echo '<option value="'.$filter.'" class="pdf">'.$filter.'</option>';
                        }
                        ?>
                    </select>
                <td>
            </tr>
            <tr>
                <td colspan="2">
                    <?php
                    foreach (array_reverse($originalPDFs) as $file) {
                        $filename = ltrim($file, FOLDER_ORIGINALS.'/');
                        echo '
                            <div class="pdfs">
                                <input type="checkbox" name="pdfs[]" value="'.$file.'" />'.$filename.'
                            </div>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" align="center"><br /><br /><input type="submit" /></td>
            </tr>
    </form>
</center>

<script type="text/javascript">
  function doFilter(selector) {
    const elements = document.getElementsByClassName("pdfs");

    Array.prototype.forEach.call(elements, function(el) {
      if(el.childNodes[1].value.includes(selector.value)) {
        el.style.display = "block";
      } else {
        el.style.display = "none";
      }
    });
  }

  doFilter(document.getElementById("filterSelector")); // Startup filter
</script>
</html>