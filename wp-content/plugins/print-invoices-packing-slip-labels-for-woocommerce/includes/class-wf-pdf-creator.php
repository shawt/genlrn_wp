<?php

require_once 'vendor/dompdf/lib/html5lib/Parser.php';
require_once 'vendor/dompdf/lib/php-font-lib/src/FontLib/Autoloader.php';
require_once 'vendor/dompdf/lib/php-svg-lib/src/autoload.php';
require_once 'vendor/dompdf/src/Autoloader.php';
Dompdf\Autoloader::register();
// reference the Dompdf namespace
use Dompdf\Dompdf;

class wf_pdf_obj extends Dompdf{

}
