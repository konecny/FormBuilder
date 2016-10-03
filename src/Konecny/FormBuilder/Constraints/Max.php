<?php

namespace Konecny\FormBuilder\Constraints;

use Doctrine\Common\Annotations\Annotation,
	Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 * @author Martin Konečný
 */
class Max extends Constraint
{
	
	public $value;
	public $message = "The number is too high.";
	
}