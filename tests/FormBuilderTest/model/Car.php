<?php

namespace FormBuilderTest\Model;


use Kdyby,
	Doctrine\ORM\Mapping as ORM,
	Symfony\Component\Validator\Constraints as Assert,
    Konecny\FormBuilder\Annotations\Password;


/**
 * @ORM\Entity
 * @ORM\Table(name="car")
 */
class Car
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
	 * @Assert\NotBlank(message="car.colorNotFilled")
	 */
	protected $color;

	
	/**
	 * @ORM\Column(type="integer")
	 * @Assert\GreaterThan(value=0, message="car.badWeight")
	 */
	protected $weight;
	
}