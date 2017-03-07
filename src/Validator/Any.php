<?php
namespace Convict\Validator;

class Any implements Validator {

  public function validate($key, $value)
  {

  }

  public function coerce($value)
  {
    return $value;
  }
}
