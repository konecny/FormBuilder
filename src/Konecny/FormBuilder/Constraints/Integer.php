<?php

namespace Konecny\FormBuilder\Constraints;

use Doctrine\Common\Annotations\Annotation,
	Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 * @author Martin Konečný
 */
class Integer extends Constraint
{
	
	public $message = "The value %value% is not a valid integer.";
	
}