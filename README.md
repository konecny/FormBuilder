# FormBuilder

Generování Nette formulářů na základě Doctrine entit.

Pro správné fungování je třeba využít knihoven `Kdyby\Translation`, `Kdyby\Doctrine` a `Kdyby\Validator`.

#### Využití

 * vytváření inputů dle atributů entit
 * přidání validační pravidel dle anotací atributů
 * předvyplnění defaultních hodnot
 * nastavení překladatelných labelů
 * uložení nových hodnot do entity

#### Instalace

Nastavení repozitáře

```
"repositories": [
	{
		"type": "vcs",
		"url": "ssh://git@gitlab.irvekon.cz:16301/konecny/form-builder.git"
	}
]
```

Přidání závislosti

```
"require": {
	"konecny/form-builder": "verze"
}
```

#### Konfigurace

Nejprve je třeba zaregistrovat továrnu. Ve výchozím nastavení Nette automaticky předá objekt třídy implementující rozhraní `Nette\Localization\ITranslator`.

```
# config.neon

- Konecny\FormBuilder\FormBuilderFactory
```

V případě změny stačí závislost manuálně předat.

```
# config.neon

- Konecny\FormBuilder\FormBuilderFactory(@translatorService)
-
```


#### Použití

Nejčastější využití je ve formulářových továrních třídách, ve kterých stačí předat závislost. Při vytváření formuláře stačí builderu předat název a entitu, pomocí které celý formulář automtaicky sestaví.

```php

use Konecny\FormBuilder\FormBuilderFactory,
	Konecny\FormBuilder\FormBuilder\FormBuilder;

class OurFormFactory extends Nette\Object
{
	
    /** @var FormBuilderFactory */
    private $formBuilderFactory;
    
    /** @var FormBuilder */
    private $builder;
    
    
    /**
     * @param FormBuilderFactory
     */
 	public function __construct(FormBuilderFactory $factory)
  	{
    	$this->formBuilderFactory = $factory;
    }
    
    
    /**
     * @return Nette\Application\UI\Form
     */
  	public function create()
  	{
    	$this->builder = $this->formBuilderFactory->create("name", App\Model\Entities\Entity::class);
        $form = $this->builder->form;
        
        $form->onSuccess[] = array($this, "formSubmitted");
        
        return $form;
    }
    
    
    /**
     * @param Nette\Application\UI\Form
     * @param Nette\Utils\ArrayHash
     */
   	public function formSubmitted($form, $values)
   	{
    	$entity = $this->builder->entity;
        
        // your logic
    }
    
}
```

#### Sub entity

Builder ve výchozím stavu pracuje pouze s hlavní entitou, která byla předána při vytváření objektu builderu. V případě, že chceme do formuláře přidat i další entity, která hlavní entita obsahuje, použijeme k tomu metodu `addSubEntity($entityPropertyName)`.

V příkladu předpokládejme, že má entita `User` atribut `$address` namapovaný pomocí vazby `OneToOne`.

```php
$builder = $formBuilderFactory->create("name", Model\User::class);
$builder->addSubEntity("address");
```

Automaticky se poté vytvoří inputy včetně validace i pro sub entitu.

*Pozn.: Builder umí pracovat pouze s vazbami OneToOne.*


#### Formulářové inputy

Každý input je generován podle atributů jednotlivých entit. Typ je určen dle anotace `@Doctrine\ORM\Mapping\Column` s parametrem 'type':
* string, date, datetime - text
* integer, float - text (number)
* text - textarea
* boolean - checkbox

Název inputu je odvozen od názvu entity a atributu oddělenými podtržítkem. Pokud bude mít entita `User` atribut `$name`, název inputu (atribut 'name') poté bude `user_name`.

V případě sub entity se pouze její název předá za název hlavní entity, tzn. pokud entita `User` bude mít sub entitu `Address` (pomocí atributu `$address`), která má atribut `$city`, název inputu poté bude `user_address_city`.

Ve výchozím stavu builder přidá i odesílací tlačítko, jehož název je složen z názvu builderu a suffixu `_submit`.

```
$formBuilderFactory->create("addUser", Model\User::class);  // vytvoří submit button s názvem 'addUser_submit'
```

Pokud přidáme k atributu entity anotaci `Konecny\FormBuilder\Annotations\Password`, input se změní na heslo.


#### Překlad labelů

Labely se vytváří automaticky podle názvu entity a atributu. Výchozí soubor pro překlad se jmenuje `forms` (př. `forms.cs_CZ.neon`), lze jej upravit pomocí atributu

```php
FormBuilder::$translationFileName = "název souboru";
```

Celý zástupný řetězec pro překlad se skládá z názvu souboru, názvu builderu (předaný prvním parametrem), názvu sub entity a atributu oddělenými tečkami.

Pro příklad

```php
$builder = $formBuilderFactory->create("addUser", Model\User::class);
// zástupný řetězec poté bude např. 'forms.addUser.name'

$builder->addSubEntity("address");
// pro sub entitu poté bude např. 'forms.addUser.address.city'
```

Pokud nechceme labely vytvářet, použijeme metodu `setCreateLabels($bool = TRUE)`.

```php
$builder->setCreateLabels(FALSE);
```

Překladový soubor pro formuláře může vypadat například takto:

```
# app/lang/forms.cs_CZ.neon

addUser:  # název formuláře
	firstName: "Jméno"  # název atributu
	lastName: "Příjmení"
	loginName: "Přihlašovací jméno"
	password: "Heslo"
	
    address:  # sub entita
    	city: "Město"
		postalCode: "PSČ"
   
  	submit: "Zaregistrovat"  # odesílací tlačítko

editUser:
	firstName: "Jméno"
	lastName: "Příjmení"
	loginName: "Přihlašovací jméno"
    
    submit: "Upravit informace"
```



#### Validační anotace pro entity

Builder definuje několik validačních anotací, které jsou kompatibilní s validačními pravidly přímo v Nette formulářích. Pomocí nich se automaticky vytvoří validační pravidla pro samotný formulář.

Všechny anotace jsou ve jmenném prostoru `Konecny\FormBuilder\Constraints`.

* Email(message) - musí být platná e-mailová adresa
* Filled(message) - hodnota musí být vyplněna
* Float(message) - musí být číselná
* Integer(message) - musí být celočíselná
* Length(value, message) - řetězec o přesné délce
* LengthRange(min, max, message) - řetězec o délce od-do
* MinLength(value, message) - řetězec o minimální délce
* MaxLength(value, message) - řetězec o maximální délce
* Min(value, message) - minimální číselná hodnota
* Max(value, message) - maximální číselná hodnota
* Pattern(value, message) - regulární výraz
* Range(min, max, message) - číselná hodnota v rozsahu od-do
* Url(message) - musí být platná URL adresa

Parametrem `message` určíme text chybové zprávy, který se automaticky překládá a vkládá i jako text do validačních podmínek formuláře.

Entita pak může vypadat třeba takto:

```php

use Doctrine\ORM\Mapping as ORM,
	Konecny\FormBuilder\Constraints as FormBuilderAssert;

class User
{

	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
  	protected $id;
    
    /**
     * @ORM\Column(name="login_name", type="string")
     * @FormBuilderAssert\Filled(message="user.name.notFilled")
     * @FormBuilderAssert\LengthRange(min=3, max=15, message="user.name.badLength")
     */
  	protected $loginName;
    
    /**
     * @ORM\Column(type="string")
     * @FormBuilderAssert\Filled(message="user.email.notFilled")
     * @FormBuilderAssert\Email(message="user.email.invalidEmail")
     */
  	protected $email;
    
    /**
     * @ORM\Column(type="integer")
     * @FormBuilderAssert\Filled(message="user.age.notFilled")
     * @FormBuilderAssert\Integer(message="user.age.invalidAge")
     * @FormBuilderAssert\Min(value=18, message="user.age.invalidAge")
     */
   	protected $age;

}
```


V překladových souborech lze využít několika zástupných řetězců

* %value - vloží hodnotu zadanou do inputu (zde je procento jen na začátku, hodnotu vkládá Nette)
* %requiredValue% - vloží požadovanou hodnotu, která je dána anotací v entitě
* %requiredMin%, %requiredMax% - v případě číselného rozsahu vloží minimální a maximální povolenou hodnotu


Překladový soubor pak může vypadat třeba takto:

```
# app/lang/entities/user.cs_CZ.neon

name:
	notFilled: "Nebylo vyplněno jméno"
    badLength: "Jméno musí obsahovat %requiredMin% - %requiredMax% znaků"
  
email:
	notFilled: "Nebyl vyplněn e-mail"
    invalidEmail: "E-mail '%value' není platnou e-mailovou adresou"
   
age:
	notFilled: "Nebyl vyplněn věk"
  	invalidAge: "Minimální věk je %requiredValue% let"
```


#### Editace entit

V případě, že entitu nevytváříme, ale máme již vytvořený objekt, předáme builderu samotnou instanci.

```php
$user = new User();
$formBuilderFactory->create("name", $user);
```

V tomto případě se veškeré inputy automaticky předvyplní výchozími hodnotami (včetně inputů pro sub entity).

Zároveň je třeba vytvořit hidden input pro ID, k tomu slouží metoda `allowId()`.

```php
$builder->allowId();
```

Input se pojmenuje stejným způsobem, tzn. `user_id` (v případě, že atribut se jmenuje `$id`).

#### Omezení inputů

Builder defaultně vytváří inputy pro všechny atributy, které jsou namapované na databázi (pomocí anotace `Doctrine\ORM\Mapping\Column`). V případě, že některé inputy vytvářet nechceme, napíšeme je jako seznam atributů do metody `without()`.

```php
$builer = $formBuilderFactory->create("name", Model\User::class);
$builder->without("password", "phone", "dateRegistered");
```

V případě sub entit oddělíme název entity a atributu tečkou.

```php
$builer = $formBuilderFactory->create("name", Model\User::class);
$builder->addSubEntity("address");
$builder->without("password", "phone", "address.city", "address.postalCode");
```

#### Přístup k objektům inputů

Pomocí metody `control($name, $exactName = FALSE)` se dostaneme k dané komponentě formuláře. Ve výchozím stavu se ke komponentě dostaneme přes název atributu v entitě.

```php
$builder = $formBuilderFactory->create("name", Model\User::class);
$builder->addSubEntity("address");
$control = $builder->control("name");  // vrátí input s názvem 'user_name'
$control = $builder->control("address.city");  // vrátí input s názvem 'user_address_city'
```

Druhým parametrem můžeme builderu říct, že má název využít striktně a nikoliv jej převádět dle atributu.

```php
$control = $builder->control("user_address_city", TRUE);
```


#### Manuální mapování

V případě, že potřebujeme nějaký input vytvořit ručně, můžeme jej namapovat na entitu pomocí metody `setMapping($controlName, $property = NULL, $callback = NULL)`.

```php
$builder = $formBuilderFactory->create("name", Model\User::class);
$form = $builder->form;
$form->addSelect("userNames", "label", array(
	"Name" => "Name",
    "Name 2" => "Name 2"
));

$builder->setMapping("userNames", "name");  // namapuje selectbox na atribut $name
```

Pokud se název inputu shoduje s názvem atributu, není třeba vyplňovat druhý parametr (pokud by se v předchozím případě selectbox jmenoval 'name').

V případě sub entity opět použijeme tečkový zápis.

```php
$builder->setMapping("cities", "address.city");
```

Pokud chceme hodnotu z inputu před uložením do entity ještě nějak upravit, využijeme třetího parametru.

```php
$builder->setMapping("userNames", "name", function($value) {
	return ucFirst($value);
});
```

*Pozn.: Úprava hodnoty se vztahuje až na uložení do entity, nikoliv před validací formuláře.*
