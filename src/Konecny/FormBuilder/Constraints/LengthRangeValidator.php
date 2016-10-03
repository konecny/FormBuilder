<?php

namespace Konecny\FormBuilder\Constraints;

use Symfony\Component\Validator\Constraint,
	Symfony\Component\Validator\ConstraintValidator,
	Nette\Utils\Validators as NetteValidators,
	Nette\Utils\Strings;


/**
 * @author Martin Konečný
 */
class LengthRangeValidator extends ConstraintValidator
{
	
	public function validate($value, Constraint $constraint)
	{
		$length = Strings::length($value);
		
		// @see https://api.nette.org/2.3/source-Forms.Validator.php.html#175
		if (!NetteValidators::isInRange($length, array($constraint->min, $constraint->max))) {
			$this->context->buildViolation($constraint->message)
				->addViolation();
		}
	}
	
}