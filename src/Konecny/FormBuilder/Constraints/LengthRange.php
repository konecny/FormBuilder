<?php

namespace Konecny\FormBuilder\Constraints;

use Doctrine\Common\Annotations\Annotation,
	Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 * @author Martin Konečný
 */
class LengthRange extends Constraint
{
	
	public $min;
	public $max;
	public $message = "The value is not within required length range.";
	
}