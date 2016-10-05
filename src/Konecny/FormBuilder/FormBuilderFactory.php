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
	
	
	/**
	 * @param Nette\Localization\ITranslator
	 */
	public function __construct(Nette\Localization\ITranslator $translator = NULL)
	{
		$this->translator = $translator;
	}
	
	
	/**
	 * @param string
	 * @param string|object
	 * @param bool|NULL
	 * @param bool|NULL
	 * @param string|NULL
	 * @return FormBuilder
	 */
	public function create($name, $entity, $autoMode = NULL, $autoDataSetting = NULL, $defaultDateTimeFormat = NULL)
	{
		$builder = new FormBuilder($name, $this->translator);
		
		if ($autoMode !== NULL) {
			$builder->setAutoMode($autoMode);
		}
		
		if ($autoDataSetting !== NULL) {
			$builder->setAutoDataSetting($autoDataSetting);
		}
		
		if ($defaultDateTimeFormat !== NULL) {
			$builder->setDateTimeFormat($defaultDateTimeFormat);
		}
		
		$builder->setEntity($entity);
	
		return $builder;
	}
	
}