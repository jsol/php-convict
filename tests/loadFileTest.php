<?php
namespace Convict\Test;
use Convict\Convict;

class LoadFileTest extends \PHPUnit_Framework_TestCase {

  public function testLoadFile()
  {
    $scheme = '{
      "a": {
        "doc": "Dummy",
        "format": "*"
      }
    }';

    $c = new Convict($scheme);

    $c->loadFile(dirname(__FILE__) . '/configs/testLoadFile.json');

    $this->assertEquals('value', $c->get('a'));
  }

  public function testLoadSecondFile()
  {
    $scheme = '{
      "a": {
        "doc": "Dummy",
        "format": "*"
      },
      "b": {
        "doc": "Dummy",
        "format": "*"
      }
    }';

    $c = new Convict($scheme);

    $c->loadFile([
      dirname(__FILE__) . '/configs/testLoadSecondFile.json',
      dirname(__FILE__) . '/configs/testLoadFile.json'
    ]);

    $this->assertEquals('value', $c->get('a'));
    $this->assertEquals('untouched', $c->get('b'));
  }
}
