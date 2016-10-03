<?php

namespace Konecny\FormBuilder\Constraints;

use Doctrine\Common\Annotations\Annotation,
	Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 * @author Martin Konečný
 */
class Range extends Constraint
{
	
	public $min;
	public $max;
	public $message = "The number is not withing required range.";
	
}