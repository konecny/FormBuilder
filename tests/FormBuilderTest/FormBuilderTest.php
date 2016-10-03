<?php

namespace FormBuilderTest;


use PHPUnit\Framework\TestCase,
	Nette,
	Nette\Forms\Form,
    Konecny\FormBuilder\FormBuilder,
	Konecny\FormBuilder\Exceptions\FormBuilderException,
    FormBuilderTest\Model\User,
	FormBuilderTest\Model\Car,
	FormBuilderTest\Model\TestEntity,
	FormBuilderTest\Model\Translator;


class FormBuilderTest extends TestCase
{
    public function testComponentName()
    {
		$builder = new FormBuilder("test", User::class, new Translator());
		
		$this->assertSame($builder->control("name")->getHtmlName(), "user_name");
    }
	
	
	public function testComponentComposedName()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		
		$this->assertSame($builder->control("testAttribute")->getHtmlName(), "user_testAttribute");
	}
	
	
	public function testComponentNameComposedEntityName()
	{
		$builder = new FormBuilder("test", TestEntity::class, new Translator());
		
		$this->assertSame($builder->control("testAttribute")->getHtmlName(), "testEntity_testAttribute");
	}

	
	public function testMissingTranslator()
	{
		$this->expectException(FormBuilderException::class);
		
		$builder = new FormBuilder("test", User::class);
		$builder->create();
	}
	
	
	public function testDisableLabels()
	{
		$builder = new FormBuilder("test", User::class);
		$builder->setCreateLabels(FALSE);
		$builder->create();
	}
	
	
	public function testWithoutControl()
	{
		$this->expectException(Nette\InvalidArgumentException::class);
		
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->without("name");
		$builder->control("name");
	}
    
	
	public function testCsrfProtectionControl()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->control(Form::PROTECTOR_ID, TRUE);
	}
	
	
	public function testCsrfProtectionControlMissing()
	{
		$this->expectException(Nette\InvalidArgumentException::class);
		
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->enableCsrfProtection(FALSE);
		$builder->control(Form::PROTECTOR_ID, TRUE);
	}
	
	
	public function testMainEntityData()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		
		$this->assertInternalType("array", $builder->getMainEntityData());
	}
	
	
	public function testMainEntityClass()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		
		$this->assertInstanceOf(User::class, $builder->getMainEntityData()["entity"]);
	}
	
	
	public function testMainEntityDataFromControl()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		
		$this->assertInternalType("array", $builder->getEntityDataByControlName("user_name"));
	}
	
	
	public function testMainEntityDataFromControlFailure()
	{
		$this->expectException(\InvalidArgumentException::class);
		
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->getEntityDataByControlName("wrong_control_name");
	}
	
	
	public function testSubEntity()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->addSubEntity("car");
	}
	
	
	public function testSubEntityPropertyNotExists()
	{
		$this->expectException(\InvalidArgumentException::class);
		
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->addSubEntity("wrong_property_name");
	}
	
	
	public function testEntitiesCount()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->addSubEntity("car");
		
		$this->assertEquals($builder->getEntitiesCount(), 2);
	}
	
	
	public function testSubEntityControl()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->addSubEntity("car");
		
		$this->assertSame($builder->control("car.color")->getHtmlName(), "user_car_color");
	}
	
	
	public function testSubEntityDataFromControl()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->addSubEntity("car");
		
		$this->assertInternalType("array", $builder->getEntityDataByControlName("user_car_color"));
	}
	
	
	public function testSubEntityFromControl()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->addSubEntity("car");
		
		$this->assertInstanceOf(Car::class, $builder->getEntityDataByControlName("user_car_color")["entity"]);
	}
	
	
	public function testFormInstance()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		
		$this->assertInstanceOf(Form::class, $builder->form);
	}
	
	
	public function testMainEntityInstance()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		
		$this->assertInstanceOf(User::class, $builder->entity);
	}
	
	
	public function testSubEntityInstance()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->addSubEntity("car");
		
		$this->assertInstanceOf(Car::class, $builder->getSubEntity("car"));
	}
	
	
	public function testSubEntityInstanceFailure()
	{
		$this->expectException(\InvalidArgumentException::class);
		
		$builder = new FormBuilder("test", User::class, new Translator());
		$builder->getSubEntity("car");
	}
	
	
	/* TODO
	public function testSubmitNewEntity()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		// $builder->control("name")->setValue("user name");
		$builder->setMethod(Form::GET);
		$form = $builder->form;
		$_GET = array(Form::TRACKER_ID => "test", "user_name" => "testUser");
		$_SERVER["REQUEST_URI"] = "/?" . http_build_query($_GET);
		
		echo $_SERVER["REQUEST_URI"];
		exit;

		$builder->form->fireEvents();
		$entity = $builder->entity;
		
		if ($builder->form->isSubmitted()) {
			echo "ok";
			exit;
		}
		
		echo $builder->control("name")->value;
		exit;
		
		$this->assertEquals($entity->name, "user name");
	}
	*/
	
	
	public function testManualMapping()
	{
		$builder = new FormBuilder("test", User::class, new Translator());
		$form = $builder->form;
		
		$form->addSelect("testSelect");
		$builder->setMapping("testSelect", "name");
	}
	
	
	public function testDefaultControlValue()
	{
		$user = new User();
		$user->id = 123;
		$user->name = "testUser";
		
		$builder = new FormBuilder("test", $user, new Translator());
		$builder->create();
		
		$this->assertSame($builder->control("name")->value, $user->name);
	}
	
	
	public function testIdInputElement()
	{
		$user = new User();
		$user->id = 123;
		$user->name = "testUser";
		
		$builder = new FormBuilder("test", $user, new Translator());
		$builder->allowId();
		
		$this->assertInstanceOf(Nette\Forms\Controls\HiddenField::class, $builder->control("id"));
	}
	
	
}