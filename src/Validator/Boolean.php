<?php
namespace Convict\Validator;

class Boolean implements Validator {

  private $true = [
    'yes',
    'y',
    'on',
    '1',
    'true'
  ];

  private $false = [
    'no',
    'n',
    'off',
    '0',
    'false'
  ];


  public function validate($key, $value)
  {

    if (is_bool($value)) {
      return;
    }

    $value = strtolower($value);
    $value = '' . $value;
    if (in_array($value, $this->true) || in_array($value, $this->false)) {
      return;
    }

    throw new ValidationException($key, $value, $this);
  }

  public function coerce($value)
  {
    if (is_bool($value)) {
      return $value;
    }
    return in_array($value, $this->true);
  }
}
