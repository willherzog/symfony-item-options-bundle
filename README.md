# WHItemOptionsBundle
 A bundle to provide "item option" functionality for Doctrine entities within the Symfony framework.
 These are functionally similar to Wordpress's "meta" tables: allowing additional data for an entity to be persisted without the need to add a dedicated database column for each "option".

 To add item options, implement the `ItemWithOptions` interface with a Doctrine entity class and have it reference another entity class—this one implementing the `ItemOption` interface (I recommend using `WHDoctrine\Entity\KeyValueTrait` to facilitate this)—to be used for each of its options.

 The option definitions themselves can just be an array of arrays (which you then use as the constructor argument for an instance of `ItemOptionDefinitionBag`); reference the `ItemOptionDefinition` class for the config options available for use in each inner array (or if sticking with the defaults, simply specify the name of the item option as a non-associative string value in the outer array).

 To update option values automatically within a Symfony form, use the `item_option` form option on each applicable field to specify the name of a defined item option with which it should be kept in sync (and make sure one of the field ancestors—usually the root form—has a Doctrine entity implementing `ItemWithOptions` as its underlying data).


Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require willherzog/symfony-item-options-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require willherzog/symfony-item-options-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    WHSymfony\WHFormBundle\WHItemOptionsBundle::class => ['all' => true],
];
```
