<?php

namespace Konecny\FormBuilder;

use Nette,
	Nette\Application\UI\Form,
	\ReflectionClass,
	\ReflectionProperty,
	Doctrine\Common\Annotations\AnnotationReader,
	Doctrine\ORM\Mapping as ORM,
	Symfony\Component\Validator\Constraints as Assert,
	Konecny\FormBuilder\Annotations\Password,
	Konecny\FormBuilder\Annotations,
	Konecny\FormBuilder\Constraints as FormBuilderAssert,
	Konecny\FormBuilder\Exceptions;



/**
 * @author Martin Konečný
 */
class FormBuilder /* implements ArrayAccess */
{

	/** @var string */
	private $name;
	
	/** @var Form */
	private $form;
	
	/** @var Nette\Localization\ITranslator|NULL */
	private $translator = NULL;
	
	/** @var array|NULL */
	private $submitButton = NULL;
	
	/**
	 * Determines if hidden input for ID is created
	 *
	 * @var bool
	 */
	private $allowId = FALSE;
	
	/** @var bool */
	private $setDefaultValues = TRUE;
	
	/** @var bool */
	private $controlsCreated = FALSE;
	
	/** @var string   name of entity's ID property */
	private $idProperty;
	
	/** @var bool */
	private $createLabels = TRUE;
	
	/** @var array */
	private $mappings = array();
	
	/** @var array */
	private $propertiesData = array();
	
	/** @var AnnotationReader */
	private $annotationReader;
	
	/** @var array */
	private $entities = array();
	
    /** @var array */
    private $inputTypes = array();
    
    /** @var array */
    private $validators = array();
    
	/** @var bool */
	private $useCsrfProtection = TRUE;
	
	/** @var array */
	private $withoutControls = array();
	
	/** @var bool */
	private $withoutAllControls = FALSE;
	
	/** @var array */
	private $entitiesInputNames = array();
	
	/** @var string */
	public static $translationFileName = "forms";
	
	/** @var string */
	private $defaultDatetimeFormat;
	
	
	/**
	 * @param string|NULL
	 * @param string|object
	 * @param Nette\Localization\ITranslator
	 * @param string
	 */
	public function __construct($name = NULL, $entity, Nette\Localization\ITranslator $translator = NULL, $defaultDatetimeFormat = "d.m.Y H:i:s")
	{
		$this->name = $name;
		$this->form = new Form();
		$this->translator = $translator;
		$this->defaultDatetimeFormat = $defaultDatetimeFormat;
		
		$this->setEntity($entity);
		$this->annotationReader = new AnnotationReader();
	}
	
	
	/**
	 * @param string|object
	 */
	public function setEntity($entity)
	{
		$entityRc = new ReflectionClass($entity);
		
		if (!is_object($entity)) {
			$entity = $entityRc->newInstance();
		}
		
		$this->entities[0] = array(
			"entity" => $entity,
			"rc" => $entityRc,
			"hash" => spl_object_hash($entity),
			"class" => get_class($entity),
			"property" => NULL
		);

		return $this;
	}
	
	
	/**
	 * Creates controls with rules depending on entity's properties.
	 */
	public function createControls()
	{	
		if ($this->controlsCreated) {
			return;
		}
		if ($this->getMainEntityData() === NULL) {
			throw new Exceptions\FormBuilderException("Entity has not been set.");
		}
		
		foreach ($this->entities as $entityData) {
			$this->createControlsForEntity($entityData);
		}
        
		// try to automatically add a submit button
		/*
		if ($this->submitButton !== NULL) {
			$this->form->addSubmit($this->submitButton[0], $this->submitButton[1]);
		}
		*/
		
		if ($this->useCsrfProtection) {
			$this->form->addProtection();
		}
		
		if ($this->hasTranslator()) {
			$submitButtonName = $this->getSubmitButtonName();
			$translateKey = self::$translationFileName . ".{$this->name}.submit";
			$label = $this->translator->translate($translateKey);
			$this->form->addSubmit($submitButtonName, $label);
		}
		
		
		$this->createValidationCallback();
		$this->controlsCreated = TRUE;
 	}
	
    
	/**
	 * @param array
	 */
	private function createControlsForEntity($entityData)
	{
		$entity = $entityData["entity"];
		$entityRc = $entityData["rc"];
		$entityHash = $entityData["hash"];
		$entityPropertyName = $entityData["property"];
		
		foreach ($entityRc->getProperties() as $property) {
			if (!$this->withoutAllControls) {
				$this->createControlByProperty($property, $entityData);
			}
		}
		
		if ($this->withoutAllControls) {
			
			// when we want to remove all components (eg. because of manual mapping) and an entity is being edited
			if ($this->allowId) {
				$entityData = $this->getMainEntityData();
				$property = $this->getMainEntityIdProperty();
				$this->createControlByProperty($property, $entityData);
			}
		}
		
		if (!isset($this->inputTypes[$entityHash])) {
			return;
		}
		
		foreach ($this->inputTypes[$entityHash] as $property => $type) {
			if (!$this->propertiesData[$entityHash][$property]["isColumn"]) {
				continue;
			}
			
			$method = "add" . ucFirst($this->inputTypes[$entityHash][$property]);
			
			$label = NULL;
			if ($this->createLabels && $property !== $this->idProperty) {
				if (!$this->hasTranslator()) {
					throw new Exceptions\FormBuilderException("Translator for FormBuilder has not been set.");
				}
				
				$subEntityPropertyKey = "";
				if ($entityPropertyName !== NULL) {
					$subEntityPropertyKey = ".{$entityPropertyName}";
				}
					
				$translateKey = self::$translationFileName . ".{$this->name}{$subEntityPropertyKey}.{$property}";
				$label = $this->translator->translate($translateKey);
			}
			
			$mainEntityName = $this->getMainEntityData()["rc"]->name;
			$mainEntityNameParts = explode("\\", $mainEntityName);
			$mainEntityName = lcFirst(end($mainEntityNameParts));
			
			if ($this->isMainEntity($entityHash)) {
				$inputName =  "{$mainEntityName}_{$property}";
			} else {
				$inputName = "{$mainEntityName}_{$entityPropertyName}_{$property}";
			}
			
			$control = $this->form->$method($inputName, $label);
			$this->entitiesInputNames[] = $inputName;
			
			if ($property === $this->idProperty || $this->setDefaultValues) {
				$defaultValue = $entity->$property;
				if (is_object($defaultValue) && $defaultValue instanceof \DateTime) {
					$defaultValue = $defaultValue->format($this->defaultDatetimeFormat);
				}
				
				$control->setDefaultValue($defaultValue);
			}
			
			
			// rules
			$this->createRulesForProperty($entityHash, $property, $control);
		}
    }
	
	
	
	private function createRulesForProperty($entityHash, $property, $control)
	{
		if (!isset($this->validators[$entityHash][$property])) {
			return;
		}
			
		$rules = $this->validators[$entityHash][$property];

		foreach ($rules as $rule) {
			$ruleMsg = $rule["msg"];
			$translateArg = NULL;
			$ruleArg = NULL;
				
			if ($rule["type"] === Form::LENGTH || $rule["type"] === Form::RANGE) {
				$translateArg = array(
					"%requiredMin%" => $rule["min"],
					"%requiredMax%" => $rule["max"]
				);
					
				$ruleArg = array($rule["min"], $rule["max"]);
			} elseif (in_array($rule["type"], array(Form::FILLED, Form::EMAIL, Form::URL, Form::INTEGER, Form::FLOAT))) {
				// seems nothing is needed to be here
					
			} elseif (in_array($rule["type"], array(Form::MIN, Form::MAX, Form::PATTERN, Form::MIN_LENGTH, Form::MAX_LENGTH))) {
				$translateArg = array("%requiredValue%" => $rule["value"]);
				$ruleArg = $rule["value"];
			}
				
			if ($this->hasTranslator()) {

				// control's value is automatically replaced using %value modifier
				// @see https://doc.nette.org/cs/2.4/forms#toc-validace
				$ruleMsg = $this->translator->translate($ruleMsg, $translateArg);
			}
				
			$control->addRule($rule["type"], $ruleMsg, $ruleArg);
		}
	}
	
	
	
	
	/**
	 * @param ReflectionProperty
	 * @param array
	 */
	private function createControlByProperty($property, $entityData)
	{
		$entity = $entityData["entity"];
		$entityHash = $entityData["hash"];
		$entityPropertyName = $entityData["property"];
	
		$propertyName = $property->name;
		if (!$this->isMainEntity($entityHash)) {
			$propertyName = "{$entityPropertyName}.{$property->name}";
		}
           
		if (in_array($propertyName, $this->withoutControls)) {
			return;
		}
		
		$this->createValidationInfoForProperty($property, $entityData);
	}
    
	
	
	/**
	 * Tries to save all submitted values into entity during validation process.
	 */
	private function createValidationCallback()
	{
		$this->form->onValidate[] = function($form) {
			foreach ($form->getControls() as $control) {
				
				if ($control instanceof Nette\Forms\Controls\SubmitButton || $control->name === Form::TRACKER_ID || $control->name === Form::PROTECTOR_ID) {
					continue;
				}

				// if there's an individual mapping for this property
				if (isset($this->mappings[$control->name])) {
					list($propertyInfo, $callback) = $this->mappings[$control->name];
					$property = explode(".", $propertyInfo);
					
					if (is_callable($callback)) {
						$value = $callback($control->value, $control);
					} else {
						$value = $control->value;
					}
					
					$mainEntityData = $this->getMainEntityData();
					$mainEntity = $mainEntityData["entity"];
					
					if (count($property) > 1) {  // entity.property
						list($subEntityName, $subProperty) = $property;
						$subEntity = $mainEntity->$subEntityName;
						
						if ($subEntity !== NULL) {  // what if yes?
							$subEntity->$subProperty = $value;
						}
						
					} else {
						$mainEntity->$property[0] = $value;
					}
				} else {  // automatical mapping
				
					if (!in_array($control->name, $this->entitiesInputNames)) {
						continue;
					}
					
					$data = explode("_", $control->name);
					$property = end($data);
					$entityData = $this->getEntityDataByControlName($control->name);
					if ($this->isMainEntity($entityData["hash"])) {
						$entityData["entity"]->$property = $control->value;
					} else {
						$subEntity = $data[1];

						$subEntityObject = $this->getMainEntityData()["entity"]->$subEntity;
						$subEntityObject->$property = $control->value;
					}
					
				}	
			}
		};
	}
	
	
	/**
	 * @param string
	 * @return mixed
	 */
	public function __get($key)
	{
		if ($key === "form") {
			
			// is it a good idea to let the controls to be created here?
			// in __toString() method it doesn't work
			$this->createControls();
			
			return $this->form;
		}
		if ($key === "entity") {
			return $this->getMainEntityData()["entity"];
		}
		if ($key === "translator") {
			return $this->translator;
		}
		
		return $this->form[$key];
	}
	
	
	/**
	 * May be able only to set validation scope or set omitted.
	 *
	 * @param string (list of arguments)
	 */
	public function without()
	{
		$controls = func_get_args();
		$this->withoutControls = array_merge($this->withoutControls, $controls);
		
		if ($this->controlsCreated) {
			foreach ($controls as $control) {
				$this->form->removeComponent($this->control($control));
			}
		}
		
		return $this;
	}
	
	
	public function withoutAll()
	{
		$this->withoutAllControls = TRUE;
		
		return $this;
	}
	

	/**
	 * TODO: label keys should be entity properties
	 *
	 * @param array
	 */
	public function setLabels(array $labels = array())
	{
		foreach ($labels as $control => $label) {
			$this->form[$control]->setLabel($label);
		}
		
		return $this;
	}
	
	
	/**
	 * @param bool
	 */
	public function allowId($bool = TRUE)
	{
		$this->allowId = (bool) $bool;
		
		return $this;
	}
	
	
	/**
	 * @param bool
	 */
	public function setDefaultValues($bool = TRUE)
	{
		$this->setDefaultValues = (bool) $bool;
		
		return $this;
	}
	
	
	/**
	 * @param bool
	 */
	public function setCreateLabels($bool = TRUE)
	{
		$this->createLabels = (bool) $bool;
		
		return $this;
	}
	
	
	/**
	 * @param string
	 * @param bool
	 * @return Nette\Forms\IControl
	 */
	public function control($name, $exactName = FALSE)
	{
		if (!$exactName) {
			if (strpos($name, ".") !== FALSE) {
				$name = str_replace(".", "_", $name);
			}
			
			$mainEntityName = $this->getMainEntityData()["rc"]->name;
			$mainEntityNameParts = explode("\\", $mainEntityName);
			$mainEntityName = lcFirst(end($mainEntityNameParts));
			$name = "{$mainEntityName}_{$name}";
		}

		if (!$this->controlsCreated) {
			$this->createControls();
		}
		
		return $this->form[$name];
	}
	
	
	/**
	 * @param string
	 * @param string
	 */
	public function addSubmit($name, $label)
	{
		$this->submitButton = array($name, $label);
	}
	
	
	/**
	 * @return bool
	 */
	public function hasTranslator()
	{
		return $this->translator !== NULL;
	}
	
	
	/**
	 * @param Nette\Localization\ITranslator
	 */
	public function setTranslator(Nette\Localization\ITranslator $translator)
	{
		$this->translator = $translator;
	}
	
	
	public function create()
	{
		$this->createControls();
	}
	
	
	/**
	 * @return string
	 */
	public function getSubmitButtonName()
	{
		return "{$this->name}_submit";
	}
	
	
	/**
	 * @param string
	 * @param string|NULL
	 * @param callable
	 * @throws \InvalidArgumentException
	 */
	public function setMapping($controlName, $property = NULL, $callback = NULL)
	{
		if ($property === NULL) {
			$property = $controlName;
		}
		
		$this->mappings[$controlName] = array($property, $callback);
		
		// need to set rules for new created input
		if ($this->controlsCreated) {
			$control = $this->form[$controlName];
			$data = explode(".", $property);
			$mainEntity = $this->getMainEntityData();
			
			if (count($data) === 1) {  // main entity
				$entityData = $this->getMainEntityData();
				$propertyRc = $entityData["rc"]->getProperty($property);
			} else {  // sub entity
				$entityData = $this->getSubEntityData($data[0]);
				$propertyRc = $entityData["rc"]->getProperty($data[1]);
			}
			
			$this->createValidationInfoForProperty($propertyRc, $entityData);
			$this->createRulesForProperty($entityData["hash"], end($data), $control);
		}
		
		return $this;
	}
	
	
	
	/**
	 * @param ReflectionProperty
	 * @param array
	 */
	private function createValidationInfoForProperty($property, $entityData)
	{
		$entity = $entityData["entity"];
		$entityHash = $entityData["hash"];
		$annotations = $this->annotationReader->getPropertyAnnotations($property);
		$isColumn = FALSE;
			
		$this->propertiesData[$entityHash][$property->name] = array();
			
		foreach ($annotations as $annotation) {
			if ($annotation instanceof ORM\Column) {
				$isColumn = TRUE;
				
				if (isset($this->inputTypes[$entityHash][$property->name])) {
					continue;
				}
					
				if (in_array($annotation->type, array("string", "integer", "float", "date", "datetime"))) {
					$this->inputTypes[$entityHash][$property->name] = "text";
				} elseif ($annotation->type === "text") {
					$this->inputTypes[$entityHash][$property->name] = "textArea";
				} elseif ($annotation->type === "boolean") {
					$this->inputTypes[$entityHash][$property->name] = "checkbox";
				}
				
			} elseif ($annotation instanceof ORM\Id) {
				$this->idProperty = $property->name;
				
				if (!$this->allowId || $entity !== $this->getMainEntityData()["entity"]) {
					if (isset($this->validators[$entityHash][$property->name])) {
						unset($this->validators[$entityHash][$property->name]);
					}
					
					$this->propertiesData[$entityHash][$property->name]["isColumn"] = FALSE;
					return;
				}
				
				$this->inputTypes[$entityHash][$property->name] = "hidden";
				
				// asserts
			} elseif ($annotation instanceof Assert\NotBlank || $annotation instanceof FormBuilderAssert\Filled) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::REQUIRED,
					"msg" => $annotation->message
				);
			} elseif ($annotation instanceof FormBuilderAssert\Length) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::LENGTH,
					"msg" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\MinLength) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::MIN_LENGTH,
					"msg" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\MaxLength) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::MAX_LENGTH,
					"msg" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\Range) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::RANGE,
					"min" => $annotation->min,
					"max" => $annotation->max,
					"msg" => $annotation->message,
				);
			} elseif ($annotation instanceof FormBuilderAssert\Email) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::EMAIL,
					"msg" => $annotation->message
				);
			} elseif ($annotation instanceof Password) {
				$this->inputTypes[$entityHash][$property->name] = "password";
			} elseif ($annotation instanceof FormBuilderAssert\Max) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::MAX,
					"msg" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\Min) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::MIN,
					"msg" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\Pattern) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::PATTERN,
					"msg" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\Url) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::URL,
					"msg" => $annotation->message
				);
			} elseif ($annotation instanceof FormBuilderAssert\Integer) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::INTEGER,
					"msg" => $annotation->message
				);
			} elseif ($annotation instanceof FormBuilderAssert\Float) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::FLOAT,
					"msg" => $annotation->message
				);
			} elseif ($annotation instanceof FormBuilderAssert\LengthRange) {
				$this->validators[$entityHash][$property->name][] = array(
					"type" => Form::LENGTH,
					"msg" => $annotation->message,
					"min" => $annotation->min,
					"max" => $annotation->max
				);
			}
			
			//  TODO: others
			
		}
			
		$this->propertiesData[$entityHash][$property->name]["isColumn"] = $isColumn;
	}
	
	
	
	
	/**
	 * @param string
	 * @throws new BadMethodCallException
	 * @throws InvalidArgumentException
	 */
	public function addSubEntity($entityPropertyName)
	{
		$mainEntityData = $this->getMainEntityData();
		if ($mainEntityData === NULL) {
			throw new \BadMethodCallException("Cannot add sub-entity when there's no main entity set.");
		}
		
		if (!$mainEntityData["rc"]->hasProperty($entityPropertyName)) {
			throw new \InvalidArgumentException("Entity '{$mainEntityData["rc"]->getName()}' has no sub-entity property '{$entityPropertyName}'.");
		}
		
		$entityObject = $mainEntityData["entity"]->$entityPropertyName;
		$mainEntity = $mainEntityData["entity"];
		$mainEntityRc = $mainEntityData["rc"];
		
		$exists = FALSE;
			
		foreach ($mainEntityRc->getProperties() as $property) {
			if ($property->name === $entityPropertyName) {
				$exists = TRUE;
				break;
			}
		}
			
		if (!$exists) {
			throw new \InvalidArgumentException("Entity-like property '{$entityPropertyName}' doesn't exist in " . get_class($this->getMainEntityData()["entity"]) . ".");
		}
			
		$entityObject = $mainEntity->$entityPropertyName;
		if ($entityObject === NULL) {
			$entityData = $this->createEntityFromPropertyName($entityPropertyName);
			$mainEntity->$entityPropertyName = $entityData["entity"];
			$entity = $entityData["entity"];
		} else {
			// $entity = get_class($entityObject);
			$entity = $entityObject;
		}
		
		$entityRc = new ReflectionClass($entity);
		if (!is_object($entity)) {
			$entity = $entityRc->newInstance();
		}

		$this->entities[] = array(
			"entity" => $entity,
			"rc" => $entityRc,
			"hash" => spl_object_hash($entity),
			"class" => $entityRc->getName(),
			"property" => $entityPropertyName
		);
		
		return $this;
	}
	
	
	/**
	 * @param bool
	 */
	public function enableCsrfProtection($enable = TRUE)
	{
		$this->useCsrfProtection = (bool) $enable;
	}
	
	
	/**
	 * @return array|NULL
	 */
	public function getMainEntityData()
	{
		return isset($this->entities[0]) ? $this->entities[0] : NULL;
	}
	
	
	/**
	 * @param string
	 * @return bool
	 */
	private function isMainEntity($hash)
	{
		foreach ($this->entities as $entityData) {
			if ($entityData["hash"] === $hash) {
				return $entityData["property"] === NULL;
			}
		}
	}

	
	/**
	 * @param string
	 * @return array
	 * @throws InvalidArgumentException
	 */
	private function createEntityFromPropertyName($property)
	{
		$mainEntity = $this->getMainEntityData();
		foreach ($mainEntity["rc"]->getProperties() as $propertyRc) {
			if ($propertyRc->name === $property) {
				$annotations = $this->annotationReader->getPropertyAnnotations($propertyRc);
				foreach ($annotations as $annotation) {
					if ($annotation instanceof ORM\OneToOne) {
						$className = $annotation->targetEntity;
						break 2;
					}
				}
				
				throw new \InvalidArgumentException("There's no One-To-One relation via property '{$property}'.");
			}
		}
		
		$fullName = $this->getMainEntityData()["rc"]->getNamespaceName() . "\\" . $className;
		$entityRc = new ReflectionClass($fullName);
		$entity = $entityRc->newInstance();
		
		return array(
			"entity" => $entity,
			"rc" => $entityRc,
			"hash" => spl_object_hash($entity),
			"class" => $fullName,
			"property" => $property
		);
	}
	
	
	/**
	 * @param string
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getEntityDataByControlName($name)
	{
		$data = explode("_", $name);
		
		if (count($data) === 2) {  // main entity (entity_property)
			return $this->entities[0];
		} elseif (count($data) === 0) {  // manual mapping
			
		} else {  // sub entity (mainEntity_subEntity_property)
			$property = $data[1];

			foreach ($this->entities as $entityData) {
				if ($entityData["property"] === $property) {
					return $entityData;
				}
			}
		}
		
		throw new \InvalidArgumentException("There's no entity matching control name '{$name}'.");
	}
	
	
	/**
	 * @return ReflectionProperty
	 */
	public function getMainEntityIdProperty()
	{
		$rc = $this->getMainEntityData()["rc"];
		foreach ($rc->getProperties() as $property) {
			$annotations = $this->annotationReader->getPropertyAnnotations($property);
			foreach ($annotations as $annotation) {
				if ($annotation instanceof ORM\Id) {
					return $property;
				}
			}
		}
	}
	
	
	/**
	 * @return int
	 */
	public function getEntitiesCount()
	{
		return count($this->entities);
	}
	
	
	/**
	 * Gets sub-entity data by main entity's property name.
	 *
	 * @param string
	 * @return object
	 * @throws \InvalidArgumentException
	 */
	public function getSubEntityData($property)
	{
		foreach ($this->entities as $entityData) {
			if ($entityData["property"] === $property) {
				return $entityData;
			}
		}
		
		throw new \InvalidArgumentException("Sub-entity '{$property}' does not exist or wasn't imported.");
	}
	
	
	/**
	 * @param string
	 * @return object
	 * @throws \InvalidArgumentException
	 */
	public function getSubEntity($property)
	{
		$data = $this->getSubEntityData($property);
		
		return $data["entity"];
	}
	
	
	/**
	 * @param string
	 */
	public function setMethod($method)
	{
		$this->form->setMethod($method);
	}
	
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		try {
			if (!$this->controlsCreated) {
				$this->createControls();
			}
			
			return $this->form->__toString();
			
		} catch (Exception $e) {
			echo "<pre>";
			print_r($e);
			exit;
		}
	}
	
}