<?php

namespace Konecny\FormBuilder\Constraints;

use Doctrine\Common\Annotations\Annotation,
	Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 * @author Martin Konečný
 */
class Pattern extends Constraint
{
	
	public $value;
	public $message = "The value does not match required pattern.";
	
}