<?php

namespace FormBuilderTest\Model;


use Kdyby,
	Doctrine\ORM\Mapping as ORM,
	Symfony\Component\Validator\Constraints as Assert,
    Konecny\FormBuilder\Annotations\Password;


/**
 * @ORM\Entity
 * @ORM\Table(name="test_entity")
 */
class TestEntity
{
	
	use Kdyby\Doctrine\Entities\MagicAccessors;
	

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $testAttribute;
	
}