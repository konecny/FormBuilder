<?php

namespace Konecny\FormBuilder\Constraints;

use Doctrine\Common\Annotations\Annotation,
	Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 * @author Martin Konečný
 */
class Email extends Constraint
{
	
	public $value;
	public $message = "The value is not a valid e-mail address.";
	
}