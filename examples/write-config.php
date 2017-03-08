<?php
$path = dirname(__FILE__);
require_once($path . '/../vendor/autoload.php');

$c = new Convict\Convict($path . '/scheme.json');

//$c->loadFile(dirname(__FILE__) . '/../test.json');
$c->validate();
$c->writeFile($c->get('config'));
echo "Wrote config file to " . $c->get('config') . "\n";
