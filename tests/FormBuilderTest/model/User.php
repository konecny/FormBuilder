<?php

namespace FormBuilderTest\Model;


use Kdyby,
	Doctrine\ORM\Mapping as ORM,
	Symfony\Component\Validator\Constraints as Assert,
    Konecny\FormBuilder\Annotations\Password;


/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User
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
	 * @Assert\NotBlank(message="user.nameNotFilled")
	 * @Assert\Length(min=3, exactMessage="user.nameTooShort")
	 */
	protected $name;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $testAttribute;
	
	/**
	 * @ORM\OneToOne(targetEntity="Car", fetch="LAZY", cascade={"persist"})
	 */
	protected $car;
	
}