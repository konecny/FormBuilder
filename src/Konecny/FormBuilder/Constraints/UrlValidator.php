<?php

namespace Konecny\FormBuilder\Constraints;

use Symfony\Component\Validator\Constraint,
	Symfony\Component\Validator\ConstraintValidator,
	Nette\Utils\Validators as NetteValidators,
	Nette\Utils\Strings;


/**
 * @author Martin Konečný
 */
class UrlValidator extends ConstraintValidator
{
	
	public function validate($value, Constraint $constraint)
	{
		// @see https://api.nette.org/2.3/source-Forms.Validator.php.html#229
		if (!NetteValidators::isUrl($value) && !NetteValidators::isUrl("http://" . $value)) {
			$this->context->buildViolation($constraint->message)
				->addViolation();
		}
	}
	
}