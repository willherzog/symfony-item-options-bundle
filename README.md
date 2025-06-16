# WHItemOptionsBundle
 A bundle to provide “item option” functionality for Doctrine entities within the Symfony framework.
 These are functionally similar to Wordpress’s “meta” tables: allowing additional data for an entity (the “item”) to be persisted without the need to add a dedicated database column for each “option”.

 To add item options, implement the `ItemWithOptions` interface with a Doctrine entity class and have it reference another entity class—this one implementing the `ItemOption` interface (I recommend using `WHDoctrine\Entity\KeyValueTrait` to facilitate this)—to be used for each of its options.

 The option definitions themselves can just be an array of arrays (which you then use as the constructor argument for an instance of `ItemOptionDefinitionBag`); reference the `ItemOptionDefinition` class for the config options available for use in each inner array (or if sticking with the defaults, simply specify the name of the item option as a non-associative string value in the outer array).

 To update option values automatically within a Symfony form, use the `item_option` form option on each applicable field to specify the name of a defined item option with which it should be kept in sync (and make sure one of the field ancestors—usually the root form—has a Doctrine entity implementing `ItemWithOptions` as its underlying data).

 Note: My recommended best practice (for code maintainability) is to reference public constants on your `ItemOption` entity class(es) for each of the option keys.

 ## Example Code

 ### The Item Option Class

 ```php
 // src/Entity/ExampleItemOption.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use WHDoctrine\Entity\KeyValueTrait;

use WHSymfony\WHItemOptionsBundle\Entity\ItemOption;

class ExampleItemOption implements ItemOption
{
    use KeyValueTrait; // Using this trait adds the "key" and "value" properties required by the ItemOption interface

    // These constants will be referenced in the subsequent classes; the value of each constant will be used for the option's "key" property
    public const OPTION_EXAMPLE_A = 'example_a';
    public const OPTION_EXAMPLE_B = 'example_b';
    public const OPTION_EXAMPLE_C = 'example_c';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ExampleItem::class, inversedBy: 'options')]
    private ?ExampleItem $item = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): ?ExampleItem
    {
        return $this->item;
    }

    public function setItem(?ExampleItem $item): static
    {
        $this->item = $item;

        return $this;
    }
}
 ```

 ### (For Clarity) An Example PHP Enumerator

 ```php
 // src/Config/ExampleEnum.php

 // Note: This is only here to demonstrate using an item option with an enumerator.

 namespace App\Config;

 enum ExampleEnum: string
 {
    case ExampleCase1 = 'Example Value 1';
    case ExampleCase2 = 'Example Value 2';
 }
 ```

 ### The Item Class

 ```php
 // src/Entity/ExampleItem.php

namespace App\Entity;

use Doctrine\Common\Collections\{ArrayCollection,Collection};
use Doctrine\ORM\Mapping as ORM;

use WHPHP\Exception\InvalidArgumentTypeException;

use WHSymfony\WHItemOptionsBundle\Config\ItemOptionDefinitionBag;
use WHSymfony\WHItemOptionsBundle\Entity\ItemOption;
use WHSymfony\WHItemOptionsBundle\Entity\ItemWithOptions;
use WHSymfony\WHItemOptionsBundle\Entity\OptionsIndexTrait;

use App\Config\ExampleEnum;

class ExampleItem implements ItemWithOptions
{
    use OptionsIndexTrait; // Using this trait fulfills the hasOption(), getOption() and getOptionValue() methods required by ItemWithOptions

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /* (additional properties...) */

    #[ORM\OneToMany(targetEntity: ExampleItemOption::class, mappedBy: 'item', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]
    private Collection $options;

    static public function getOptionClass(): string
    {
        return ExampleItemOption::class; // This is the item option class defined above
    }

    static public function getOptionDefinitions(): ItemOptionDefinitionBag
    {
        // These are the actual item option definitions
        return new ItemOptionDefinitionBag([
            ExampleItemOption::OPTION_EXAMPLE_A,
            ExampleItemOption::OPTION_EXAMPLE_B => [
                'default' => 'An example default value.'
            ],
            ExampleItemOption::OPTION_EXAMPLE_C => [
                'requirement_callback' => fn(self $item) => $item->id !== null,
                'enum_type' => ExampleEnum::class
            ]
        ]);
    }

    public function __construct()
    {
        // Doing this is standard practice for OneToMany or ManyToMany associations of Doctrine entity classes
        // See e.g. <https://symfony.com/doc/current/doctrine/associations.html>
        $this->options = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /* (additional getter/setter methods...) */

    /**
     * @return Collection<int, ExampleItemOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(ItemOption $option): void
    {
        // Including a sanity check like this is not technically required but is recommended
        if( !($option instanceof ExampleItemOption) ) {
            throw new InvalidArgumentTypeException($option, ExampleItemOption::class);
        }

        $option->setItem($this);

        $this->options->add($option);

        $this->resetOptionsIndex(); // This method of OptionsIndexTrait should always be called after the $options property has been modified
    }

    public function removeOption(ItemOption $option): void
    {
        // Including a sanity check like this is not technically required but is recommended
        if( !($option instanceof ExampleItemOption) ) {
            throw new InvalidArgumentTypeException($option, ExampleItemOption::class);
        }

        $option->setItem(null); // Doing this will trigger Doctrine's orphan removal (since the $options property has orphanRemoval: true)

        $this->options->removeElement($option);

        $this->resetOptionsIndex(); // This method of OptionsIndexTrait should always be called after the $options property has been modified
    }
}
 ```

 ### A Possible Form Class for the Item

 ```php
// src/Form/Type/ExampleItemType.php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{EnumType,TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Config\ExampleEnum;
use App\Entity\{ExampleItem,ExampleItemOption};

class ExampleItemType extends AbstractForm
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /* (other form fields...) */

            ->add('option_example_a', TextType::class, [
                'item_option' => ExampleItemOption::OPTION_EXAMPLE_A,
                'label' => 'Example Option A'
            ])
            ->add('option_example_b', TextType::class, [
                'item_option' => ExampleItemOption::OPTION_EXAMPLE_B,
                'required' => false, // Since this field is not required, if the user does not submit a value, the above "default" value will be returned by calls to OptionsIndexTrait::getOptionValue()
                'label' => 'Example Option B'
            ])
            ->add('option_example_c', EnumType::class, [
                'item_option' => ExampleItemOption::OPTION_EXAMPLE_C,
                'class' => ExampleEnum::class, // The same enumerator specified for "enum_type" above
                'label' => 'Example Option C'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', ExampleItem::class);
    }
}
```

### Accessing Item Options

```php
use App\Entity\{ExampleItem,ExampleItemOption};

// ...

/** @var ExampleItem $item */

if( $item->hasOption(ExampleItemOption::OPTION_EXAMPLE_B) ) {
    /** @var ExampleItemOption */
    $optionEntity = $item->getOption(ExampleItemOption::OPTION_EXAMPLE_B);
    // ->getOption() returns NULL if the option has not been persisted
}

$optionValue = $item->getOptionValue(ExampleItemOption::OPTION_EXAMPLE_B);
// When OptionsIndexTrait is used and a "default" value has been set (as is the case for this option in the item class above), ->getOptionValue() will return that default if the option has not been persisted
// Alternatively, you can use the second argument of ->getOptionValue() to set a different default value
```

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require willherzog/symfony-item-options-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require willherzog/symfony-item-options-bundle
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    WHSymfony\WHItemOptionsBundle\WHItemOptionsBundle::class => ['all' => true],
];
```
