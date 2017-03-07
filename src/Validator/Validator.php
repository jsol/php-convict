<?php
namespace Convict\Validator;

interface Validator {

  public function validate($key, $value);
  public function coerce($value);
}
