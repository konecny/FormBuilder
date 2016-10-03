<?php

namespace Konecny\FormBuilder;

use Nette,
	Konecny\FormBuilder\FormBuilder;


/**
 * @author Martin Konečný
 */
class FormBuilderFactory extends Nette\Object
{
	
	/** @var Nette\Localization\ITranslator */
	private $translator;
	
	/** @var string */
	private $defaultDatetimeFormat;
	
	
	/**
	 * @param Nette\Localization\ITranslator
	 * @param string
	 */
	public function __construct(Nette\Localization\ITranslator $translator = NULL, $defaultDatetimeFormat = "d.m.Y H:i:s")
	{
		$this->translator = $translator;
		$this->defaultDatetimeFormat = $defaultDatetimeFormat;
	}
	
	
	/**
	 * @param string
	 * @param string|object
	 * @param string|NULL
	 * @return FormBuilder
	 */
	public function create($name, $entity, $datetimeFormat = NULL)
	{
		if ($datetimeFormat === NULL) {
			$datetimeFormat = $this->defaultDatetimeFormat;
		}
		
		return new FormBuilder($name, $entity, $this->translator, $datetimeFormat);
	}
	
}