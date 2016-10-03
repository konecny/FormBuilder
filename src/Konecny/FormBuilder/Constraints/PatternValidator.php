<?php

namespace Konecny\FormBuilder\Constraints;

use Symfony\Component\Validator\Constraint,
	Symfony\Component\Validator\ConstraintValidator,
	Nette\Utils\Validators as NetteValidators,
	Nette\Utils\Strings;


/**
 * @author Martin Konečný
 */
class PatternValidator extends ConstraintValidator
{
	
	public function validate($value, Constraint $constraint)
	{
		$pattern = $constraint->value;
		
		// @see https://api.nette.org/2.3/source-Forms.Validator.php.html#246
		if (!(bool) Strings::match($value, "\x01^(?:$pattern)\\z\x01u")) {
			$this->context->buildViolation($constraint->message)
				->addViolation();
		}
	}
	
}