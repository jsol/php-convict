<?php
namespace Convict\Validator;

class ValidationException extends \Exception {
  public function __construct($key, $value, $validator)
  {
    $name = explode('\\', get_class($validator));
    $name = end($name);
    parent::__construct(sprintf('\'%s\' in \'%s\' is not a valid %s.', $value, $key, $name));
  }
}
