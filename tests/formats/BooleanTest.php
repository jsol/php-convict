<?php
namespace Convict\Test;

class BooleanTest extends \PHPUnit_Framework_TestCase {

  public function setUp()
  {
    $this->validator = new \Convict\Validator\Boolean();
  }


  public function dp()
  {
    $trueString = [
      'value' => 'true',
      'validated' => true,
      'coerced' => true
    ];

    $yesString = [
      'value' => 'yes',
      'validated' => true,
      'coerced' => true
    ];

    $invalidString = [
      'value' => 'yepp',
      'validated'=> false
    ];
    return get_defined_vars();
  }

  /**
   * @dataProvider dp
   * @test
   */
  public function test($value, $validated, $coerced =null) {
    if (!$validated) {
      $this->expectException(\Convict\Validator\ValidationException::class);
      $this->validator->validate('The key', $value);
    } else {
      $this->validator->validate('The key', $value);
      $this->assertEquals($coerced, $this->validator->coerce($value));
    }

  }
}
