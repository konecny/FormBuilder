<?php

namespace Konecny\FormBuilder\Constraints;

use Symfony\Component\Validator\Constraint,
	Symfony\Component\Validator\ConstraintValidator,
	Nette\Utils\Validators as NetteValidators;


/**
 * @author Martin Konečný
 */
class FloatValidator extends ConstraintValidator
{
	
	public function validate($value, Constraint $constraint)
	{
		// @see https://api.nette.org/2.3/source-Forms.Validator.php.html#272
		if (!NetteValidators::isNumeric($value)) {
			$this->context->buildViolation($constraint->message)
				->setParameter("%value%", $value)
				->addViolation();
		}
	}
	
}