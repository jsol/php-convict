<?php
namespace Convict\Test;
use Convict\Convict;

class NewFormat implements \Convict\Validator\Validator {
  public function validate($key, $value) {

  }

  public function coerce($value) {
    return "+$value+";
  }
}

class AddFormatTest extends \PHPUnit_Framework_TestCase {

  public function testLoadFile()
  {
    $scheme = '{
      "a": {
        "doc": "Dummy",
        "format": "newformat"
      }
    }';

    $c = new Convict($scheme);
    $c->addFormat(new NewFormat());

    $c->loadFile(dirname(__FILE__) . '/configs/testLoadFile.json');
    $c->validate();
    $this->assertEquals('+value+', $c->get('a'));
  }

}
