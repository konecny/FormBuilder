<?php

namespace Konecny\FormBuilder;

use Nette,
	Nette\Application\UI\Form,
	\ReflectionClass,
	\ReflectionProperty,
	Doctrine\Common\Annotations\AnnotationReader,
	Doctrine\ORM\Mapping as ORM,
	Symfony\Component\Validator\Constraints as Assert,
	Konecny\FormBuilder\Annotations,
	Konecny\FormBuilder\Constraints as FormBuilderAssert,
	Konecny\FormBuilder\Exceptions;


/**
 * @author Martin Konečný
 */
class FormBuilder
{
	
	/** @var string */
	private $name;
    
	/** @var Form */
	private $form;
	
	/**
	 * Metadata of all added entities and its' attributes
	 *
	 * @var array
	 */
    private $metadata = array();
	
	/**
	 * control name => entity attribute (eg. entity.name, entity.subEntity.name)
	 *
	 * @var array
	 */
	private $mappings = array();
	
	/**
	 * If inputs will be created automatically
	 *
	 * @var bool
	 */
	private $autoMode = TRUE;
	
	/** @var AnnotationReader */
	private $annotationReader;
	
	/** @var bool */
	private $createLabels = TRUE;
	
	/**
	 * If submitted values are automatically set to entities
	 *
	 * @var bool
	 */
	private $autoDataSetting = TRUE;
	
	/** @var bool */
	private $controlsCreated = FALSE;
	
	/** @var bool */
	private $useCsrfProtection = TRUE;

	/** @var bool */
	private $setDefaultValues = TRUE;
	
	/**
	 * If a hidden input with entity's ID will be created
	 *
	 * @var bool
	 */
	private $allowId = FALSE;
	
	/** @var string */
	private $defaultDatetimeFormat = "d.m.Y H:i:s";
	
	/** @var Nette\Localization\ITranslator */
	private $translator;
	
	/** @var string */
	public static $translationFileName = "form";
	
	
	
	/**
	 * @param string
	 * @param Nette\Localization\ITranslator
	 */
	public function __construct($name, Nette\Localization\ITranslator $translator = NULL)
	{
		$this->name = $name;
		$this->annotationReader = new AnnotationReader();
		$this->form = new Form();
		
		$this->setTranslator($translator);
	}
	
	
	/**
	 * @param string
	 */
	public function setDateTimeFormat($format)
	{
		$this->defaultDatetimeFormat = $format;
	}
	
	
	/**
	 * @param string|object
	 */
	public function setEntity($entity)
	{
		$this->createMetadataForEntity($entity, NULL);
		
		return $this;
	}
	

	/**
	 * @param string
	 */
	public function addSubEntity($propertyName)
	{
		$mainEntityData = $this->getMainEntityData();
		if (!$mainEntityData["rc"]->hasProperty($propertyName)) {
			throw new \InvalidArgumentException("Entity '{$mainEntityData["rc"]->getName()}' has no sub-entity property '{$entityPropertyName}'.");
		}
		
		$entity = $mainEntityData["entity"]->$propertyName;
		if ($entity === NULL) {
			foreach ($mainEntityData["rc"]->getProperties() as $propertyRc) {
				if ($propertyRc->getName() === $propertyName) {
					foreach ($this->annotationReader->getPropertyAnnotations($propertyRc) as $annotation) {
						if ($annotation instanceof ORM\OneToOne) {
							$className = $annotation->targetEntity;
							$fullName = $mainEntityData["rc"]->getNamespaceName() . "\\" . $className;
							$entityRc = new ReflectionClass($fullName);
							$entity = $entityRc->newInstance();
							
							break 2;
						}
					}
				}
			}
		}
		
		if ($entity === NULL) {
			throw new \InvalidArgumentException("There's no One-To-One relation via property '{$property}'.");
		}
		
		$this->createMetadataForEntity($entity, $propertyName);
		
		return $this;
	}
	
	
	public function create()
	{
		$this->createControls();
	
		if ($this->autoDataSetting) {
			$this->createAutoDataSetting();
		}
		
		return $this;
	}
	
	
	/**
	 * @param string
	 * @param string|NULL
	 * @param callable|NULL
	 */
	public function setMapping($controlName, $property = NULL, callable $callback = NULL)
	{
		if ($property === NULL) {
			$property = $controlName;
		}
		
		$this->mappings[$controlName] = array($property, $callback);
		
		return $this;
	}
	
	
	/**
	 * @param bool
	 */
	public function enableCsrfProtection($enable = TRUE)
	{
		$this->useCsrfProtection = (bool) $enable;
		
		return $this;
	}
	
	
	/**
	 * @param Nette\Localization\ITranslator
	 */
	public function setTranslator(Nette\Localization\ITranslator $translator)
	{
		$this->translator = $translator;
		
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
			
			$mainEntityName = $this->getEntityClassName($this->getMainEntityData()["rc"]);
			$name = "{$mainEntityName}_{$name}";
		}

		if (!$this->controlsCreated) {
			$this->createControls();
		}
		
		return $this->form[$name];
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
	public function allowId($bool = TRUE)
	{
		$this->allowId = (bool) $bool;
		
		return $this;
	}
	
	
	/**
	 * @param array
	 */
	public function setLabels(array $labels = array())
	{
		foreach ($labels as $property => $label) {
			$this->control($property)->setLabel($label);
		}
		
		return $this;
	}
	
	
	/**
	 * @param bool
	 */
	public function setAutoMode($bool)
	{
		$this->autoMode = (bool) $bool;
		
		return $this;
	}
	
	
	/**
	 * @param bool
	 */
	public function setAutoDataSetting($bool)
	{
		$this->autoDataSetting = (bool) $bool;
		
		return $this;
	}
	
	
	/**
	 * @param string (list of arguments)
	 */
	public function with()
	{
		$attributes = func_get_args();
		foreach ($attributes as $attribute) {
			$property = $this->getPropertyByAttribute($attribute);
			
			$entityData = $this->getEntityDataByAttribute($attribute);
			$propertyRc = new ReflectionProperty($entityData["entity"], $property);
			$this->createMetadataForProperty($propertyRc, $entityData["rc"], $entityData["entity"]);
		}
		
		return $this;
	}
	
	
	/**
	 * May be able only to set validation scope or set omitted.
	 *
	 * @param string (list of arguments)
	 */
	public function without()
	{
		$attributes = func_get_args();
		
		foreach ($attributes as $attribute) {
			
			foreach ($this->mappings as $controlName => $mapping) {
				if ($mapping[0] === $attribute) {
					unset($this->mappings[$controlName]);
				}
			}
			
			$entity = $this->getEntityDataByAttribute($attribute)["entity"];
			$hash = spl_object_hash($entity);
			$property = $this->getPropertyByAttribute($attribute);
			unset($this->metadata[$hash]["attributes"][$property]);
			
			if ($this->controlsCreated) {
				$this->form->removeComponent($this->control($attribute));
			}
		}
		
		return $this;
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
			$this->create();
			
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
	 * @return bool
	 */
	public function hasTranslator()
	{
		return $this->translator !== NULL;
	}
	
	
	/**
	 * @param string
	 */
	public function setMethod($method)
	{
		$this->form->setMethod($method);
		
		return $this;
	}
	
	
	
	/**
	 * @return array
	 */
	private function getMainEntityData()
	{
		foreach ($this->metadata as $data) {
			if ($data["property"] === NULL) {
				return $data;
			}
		}
		
		throw new Exceptions\FormBuilderException("No main entity set");
	}
	
	
	/**
	 * @param string|object
	 * @param string
	 */
	private function createMetadataForEntity($entity, $property)
	{
		$entityRc = new \ReflectionClass($entity);

		if (!is_object($entity)) {
			$entity = $entityRc->newInstance();
		}
		
		$entityHash = spl_object_hash($entity);
		
		$this->metadata[$entityHash] = array("property" => $property, "entity" => $entity, "rc" => $entityRc, "attributes" => array());

		if (!$this->autoMode && $property !== NULL) {
			return;
		}
		
		
		// allow only ID hidden input to be created while editing and set to manual mapping
		if (!$this->autoMode) {
			$idProperty = $this->getMainEntityIdProperty();
			$this->createMetadataForProperty($idProperty, $entityRc, $entity);
			
			return;
		}
		
		foreach ($entityRc->getProperties() as $property) {
			$this->createMetadataForProperty($property, $entityRc, $entity);
		}
	}
	
	
	/**
	 * @param ReflectionProperty
	 * @param ReflectionClass
	 * @param object
	 */
	private function createMetadataForProperty($propertyRc, $entityRc, $entity)
	{
		$entityHash = spl_object_hash($entity);
		$annotations = $this->annotationReader->getPropertyAnnotations($propertyRc);
		$name = $propertyRc->getName();
		
		$data = array(
			"id" => FALSE,
			"rc" => $propertyRc,
			"name" => $name,
			"rules" => array(),
			"ignore" => FALSE,
			"password" => FALSE,
			"controlCreated" => FALSE
		);
		
		$isColumn = FALSE;
		foreach ($annotations as $annotation) {
			
			// table column information
			if ($annotation instanceof ORM\Column) {
				$data["type"] = $annotation->type;
				$isColumn = TRUE;
				
				if (in_array($annotation->type, array("string", "integer", "float", "date", "datetime"))) {
					$data["inputType"] = "text";
				} elseif ($annotation->type === "text") {
					$data["inputType"] = "textArea";
				} elseif ($annotation->type === "boolean") {
					$data["inputType"] = "checkbox";
				}
			} elseif ($annotation instanceof ORM\Id) {
				$data["id"] = TRUE;
			} elseif ($annotation instanceof Annotations\Ignore) {
				$data["ignore"] = TRUE;
			} elseif ($annotation instanceof Annotations\Password) {
				$data["password"] = TRUE;

				// validation annotations
			} elseif ($annotation instanceof FormBuilderAssert\Filled || $annotation instanceof Assert\NotBlank) {
				$data["rules"][] = array("type" => Form::REQUIRED, "value" => TRUE, "message" => $annotation->message);
			} elseif ($annotation instanceof FormBuilderAssert\MinLength) {
				$data["rules"][] = array("type" => Form::MIN_LENGTH, "value" => $annotation->value, "message" => $annotation->message);
			} elseif ($annotation instanceof FormBuilderAssert\Length) {
				$data["rules"][] = array(
					"type" => Form::LENGTH,
					"message" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\MinLength) {
				$data["rules"][] = array(
					"type" => Form::MIN_LENGTH,
					"message" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\MaxLength) {
				$data["rules"][] = array(
					"type" => Form::MAX_LENGTH,
					"message" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\Range) {
				$data["rules"][] = array(
					"type" => Form::RANGE,
					"min" => $annotation->min,
					"max" => $annotation->max,
					"message" => $annotation->message,
				);
			} elseif ($annotation instanceof FormBuilderAssert\Email) {
				$data["rules"][] = array(
					"type" => Form::EMAIL,
					"message" => $annotation->message
				);
			} elseif ($annotation instanceof FormBuilderAssert\Max) {
				$data["rules"][] = array(
					"type" => Form::MAX,
					"message" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\Min) {
				$data["rules"][] = array(
					"type" => Form::MIN,
					"message" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\Pattern) {
				$data["rules"][] = array(
					"type" => Form::PATTERN,
					"message" => $annotation->message,
					"value" => $annotation->value
				);
			} elseif ($annotation instanceof FormBuilderAssert\Url) {
				$data["rules"][] = array(
					"type" => Form::URL,
					"message" => $annotation->message
				);
			} elseif ($annotation instanceof FormBuilderAssert\Integer) {
				$data["rules"][] = array(
					"type" => Form::INTEGER,
					"message" => $annotation->message
				);
			} elseif ($annotation instanceof FormBuilderAssert\Float) {
				$data["rules"][] = array(
					"type" => Form::FLOAT,
					"message" => $annotation->message
				);
			} elseif ($annotation instanceof FormBuilderAssert\LengthRange) {
				$data["rules"][] = array(
					"type" => Form::LENGTH,
					"message" => $annotation->message,
					"min" => $annotation->min,
					"max" => $annotation->max
				);
			}
			
			// others
			
		}
		
		if ($isColumn && !$data["ignore"]) {
			$data["value"] = $entity->$name;
			$this->metadata[$entityHash]["attributes"][$name] = $data;
			
			$controlName = $this->getEntityClassName($this->getMainEntityData()["rc"]);
			$propertyName = $name;
			if ($this->metadata[$entityHash]["property"] !== NULL) {
				$controlName .= "_" . $this->getEntityClassName($entityRc);
				$propertyName = $this->metadata[$entityHash]["property"] . "." . $propertyName;
			}
			
			$controlName .= "_{$name}";
			
			$this->mappings[$controlName] = array($propertyName, NULL);
		}
	}
	
	
	private function createControls()
	{
		if ($this->controlsCreated) {
			return;
		}
		
		foreach ($this->metadata as $data) {
			$attributes = $data["attributes"];
			
			foreach ($attributes as $name => $attributeData) {
				if ($attributeData["ignore"]) {
					continue;
				}

				if ($attributeData["id"]) {
					if (!$this->allowId || $data["property"] !== NULL) {  // no ID hidden inputs for sub entities
						continue;
					}
					
					$method = "addHidden";
				} elseif ($attributeData["password"]) {
					$method = "addPassword";
				} else {
					$method = "add" . ucFirst($attributeData["inputType"]);
				}
				
				$inputName = $this->getInputName($name, $data);
				$label = $this->getLabel($attributeData, $data);
				
				$control = $this->form->$method($inputName, $label);
				$attributes["controlCreated"] = TRUE;
				
				$isRequired = FALSE;
				
				foreach ($attributeData["rules"] as $rule) {
					if ($rule["type"] === Form::REQUIRED) {
						$isRequired = TRUE;
						$control->setRequired($rule["message"]);
					} elseif (isset($rule["value"])) {
						$control->addRule($rule["type"], $rule["message"], $rule["value"]);
					} elseif (isset($rule["min"]) && isset($rule["max"])) {
						$control->addRule($rule["type"], $rule["message"], array($rule["min"], $rule["max"]));
					} else {
						$control->addRule($rule["type"], $rule["message"]);
					}
				}
				
				// due to new version of Nette calling this method is necessary
				if (!$isRequired) {
					$control->setRequired(FALSE);
				}
				
				if ($this->setDefaultValues || $attributeData["id"]) {
					$control->setDefaultValue($attributeData["value"]);
				}
				
			}
		}
		
		if ($this->useCsrfProtection) {
			$this->form->addProtection();
		}
		
		$submitButtonName = $this->getSubmitButtonName();
		$translateKey = self::$translationFileName . ".{$this->name}.submit";
		$label = $this->hasTranslator() ? $this->translator->translate($translateKey) : NULL;
		$this->form->addSubmit($submitButtonName, $label);
		
		$this->controlsCreated = TRUE;
	}
	
	
	/**
	 * @return string
	 */
	private function getSubmitButtonName()
	{
		return "{$this->name}_submit";
	}
	
	
	/**
	 * @param string
	 * @param array
	 * @return string
	 */
	private function getInputName($propertyName, $metadata)
	{
		$mainEntityName = $this->getEntityClassName($this->getMainEntityData()["rc"]);
		
		$name = "{$mainEntityName}_";
		if ($metadata["property"] !== NULL) {
			$name .= "{$metadata["property"]}_";
		}
		
		$name .= $propertyName;
		
		return $name;
	}
	
	
	/**
	 * @param array
	 * @param array
	 * @return string
	 */
	private function getLabel($attributeData, $entityData)
	{
		$label = NULL;
		if ($this->createLabels && $attributeData["id"] === FALSE) {
			if (!$this->hasTranslator()) {
				throw new Exceptions\FormBuilderException("Translator for FormBuilder has not been set.");
			}
				
			$subEntityPropertyKey = "";
			if ($entityData["property"] !== NULL) {
				$subEntityPropertyKey = ".{$entityData["property"]}";
			}
					
			$translateKey = self::$translationFileName . ".{$this->name}{$subEntityPropertyKey}.{$attributeData["name"]}";
			$label = $this->translator->translate($translateKey);
		}
		
		return $label;
	}
	
	
	/**
	 * Automatically sets entity's values after submitting form
	 */
	private function createAutoDataSetting()
	{
		$this->form->onSuccess[] = function($form) {
			foreach ($form->getControls() as $control) {
				
				if ($control instanceof Nette\Forms\Controls\SubmitButton || $control->name === Form::TRACKER_ID || $control->name === Form::PROTECTOR_ID) {
					continue;
				}
				
				$name = $control->getName();
				list($attribute, $callback) = $this->mappings[$name];
				$entity = $this->getEntityDataByAttribute($attribute)["entity"];
				$property = $this->getPropertyByAttribute($attribute);
				
				$value = $control->getValue();
				if ($callback !== NULL) {
					$value = $callback($value);
				}
				
				$entity->$property = $value;
			}
		};
	}
	
	
	/**
	 * @param ReflectionClass
	 * @return string
	 */
	private function getEntityClassName($entityRc)
	{
		$name = $entityRc->getName();
		$nameParts = explode("\\", $name);
		$name = lcFirst(end($nameParts));
	
		return $name;
	}
	
	
	/**
	 * @param string
	 * @throws \Exception
	 * @return array
	 */
	private function getEntityDataByAttribute($attribute)
	{
		$parts = explode(".", $attribute);
		
		if (count($parts) === 1) {  // main entity
			return $this->getMainEntityData();
		} else {  // sub entity
			$property = reset($parts);
			
			foreach ($this->metadata as $data) {
				if ($data["property"] === $property) {
					return $data;
				}
			}
			
			throw new Exceptions\FormBuilderException("Entity in this property doesn't exist");
		}
	}
	
	
	/**
	 * @param string
	 * @return string
	 */
	private function getPropertyByAttribute($attribute)
	{
		$parts = explode(".", $attribute);
		
		return end($parts);
	}
	
	
	/**
	 * @return ReflectionProperty
	 */
	private function getMainEntityIdProperty()
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
	 * @return string
	 */
	public function __toString()
	{
		try {
			if (!$this->controlsCreated) {
				$this->create();
			}
			
			return $this->form->__toString();
			
		} catch (Exception $e) {
			echo "<pre>";
			print_r($e);
			exit;
		}
	}
	
}