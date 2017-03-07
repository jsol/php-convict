<?php
namespace Convict\Validator;

class Nat implements Validator {

  public function validate($key, $value)
  {
    if (!is_numeric($value) || round($value) != $value) {
      throw new ValidationException($key, $value, $this);
    }

  }

  public function coerce($value)
  {
    return $value * 1;
  }
}
