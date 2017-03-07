<?php
namespace Convict\Validator;

class Path implements Validator {

  public function validate($key, $value)
  {

  }

  public function coerce($value)
  {
    return $value;
  }
}
