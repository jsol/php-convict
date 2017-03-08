<?php
namespace Convict\Test;

use Convict\Convict;

class GetSetTest extends \PHPUnit_Framework_TestCase {

  public function testSetOneValue()
  {
    $c = new Convict('{}');

    $value = 'hello';
    $key = 'world';

    $c->set($key, $value);

    $this->assertEquals($value, $c->get($key));
  }

  public function testSetMultipleValues()
  {
    $values = [
      'a.b.c' => 'a',
      'a.b.d' => 'b',
      'a.e.f' => 'c',
      'a.e.g' => 'd',
      'a.e.h' => 'e',
      'x' => true,
      'x.y' => false
    ];

    $c = new Convict('{}');

    foreach ($values as $key => $value) {
      $c->set($key, $value);
      $this->assertEquals($value, $c->get($key));
    }

    $this->assertEquals([ 'c' => 'a', 'd' => 'b' ], $c->get('a.b'));
    $this->assertEquals([ 'y' => false ], $c->get('x'));
    $this->assertNull($c->get('a.b.e'));
    $this->assertEquals([
      'b' => [
        'c' => 'a', 'd' => 'b'
      ],
      'e' => [
        'f' => 'c',
        'g' => 'd',
        'h' => 'e'
        ]
      ], $c->get('a'));
  }

}
