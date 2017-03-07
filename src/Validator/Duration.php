<?php
namespace Convict\Validator;

class Duration implements Validator {
  private $time = [
    'ms' => 1,
    's'  => 1000,
    'm'  => 60 * 1000,
    'h'  => 60 * 60 * 1000,
    'd'  => 24 * 60 * 60 * 1000,
    'w'  => 7 * 24 * 60 * 60 * 1000
  ];


  public function validate($key, $value)
  {
    if (is_numeric($value) && round($value) == $value && $value > 0) {
      return ;
    }
    if (preg_match('/^([0-9\.,]*)(?:\s*)?(.*)$/', $value, $matches)) {
      if (in_array(strtolower($matches[2]), array_keys($this->time))) {
        return;
      }
    }

    throw new ValidationException($key, $value, $this);

  }

  public function coerce($value)
  {
    if (is_numeric($value) && round($value) == $value && $value > 0) {
      return $value * 1;
    }
    if (preg_match('/^([0-9\.,]*)(?:\s*)?(.*)$/', $value, $matches)) {
      $matches[1] = str_replace(',', '.', ''. $matches[1]);
      return $this->time[strtolower($matches[2])] * $matches[1];
    }
    return $value * 1;
  }
}
