<?php
namespace Convict\Validator;

class Port implements Validator {

  public function validate($key, $value)
  {
    if (!is_numeric($value) || round($value) != $value || $value <= 0 || $value > 65535) {
      throw new ValidationException($key, $value, $this);
    }
  }

  public function coerce($value)
  {
    return $value * 1;
  }
}
