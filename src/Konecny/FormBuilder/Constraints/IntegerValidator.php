<?php

namespace Konecny\FormBuilder\Constraints;

use Symfony\Component\Validator\Constraint,
	Symfony\Component\Validator\ConstraintValidator,
	Nette\Utils\Validators as NetteValidators;


/**
 * @author Martin Konečný
 */
class IntegerValidator extends ConstraintValidator
{
	
	public function validate($value, Constraint $constraint)
	{
		// @see https://api.nette.org/2.3/source-Forms.Validator.php.html#256
		if (!NetteValidators::isNumericInt($value)) {
			$this->context->buildViolation($constraint->message)
				->setParameter("%value%", $value)
				->addViolation();
		}
	}
	
}