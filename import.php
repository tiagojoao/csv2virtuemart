<?php
/**
 * @author      Tiago Inacio
 * @copyright   Copyright (C) 2014
 * @description Import products into Virtuemart from a csv file
 */
header('Content-Type: text/html; charset=utf-8');
require_once '../csv_config.php';
// includes
if (file_exists(dirname(__FILE__) . INCLUDES_FOLDER . DIRECTORY_SEPARATOR . 'header.php')) {
    include_once dirname(__FILE__) . INCLUDES_FOLDER . DIRECTORY_SEPARATOR . 'header.php';
}
if (file_exists(dirname(__FILE__) . INCLUDES_FOLDER . DIRECTORY_SEPARATOR . 'config.php')) {
    include_once dirname(__FILE__) . INCLUDES_FOLDER . DIRECTORY_SEPARATOR . 'config.php';
}
if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'file.php')) {
    include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'file.php';
}


// read files
$_file_path = FILES_FOLDER . DIRECTORY_SEPARATOR . "products.csv";
$fields = array();
$products = array();
// instaciate the File class
$file = new FileClass($_file_path, $fields, $products);

// parse the file
$file->parse_file($file, $fields, $products);

//debug purposes only
//$file->print_results();

$file->get_product();

include_once INCLUDES_FOLDER . DIRECTORY_SEPARATOR .'footer.php';
unset($file);
?>
