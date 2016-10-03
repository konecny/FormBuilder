<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add("FormBuilderTest", __DIR__ . "/FormBuilderTest/");

AnnotationRegistry::registerFile(__DIR__ . "/../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
// AnnotationRegistry::registerAutoloadNamespace("Symfony\Component\Validator\Constraint", __DIR__ . "/../vendor/symfony");

require __DIR__ . "/../vendor/symfony/validator/Constraints/NotBlank.php";
require __DIR__ . "/../vendor/symfony/validator/Constraints/Length.php";
require __DIR__ . "/../vendor/symfony/validator/Constraints/GreaterThan.php";

require "FormBuilderTest/model/User.php";
require "FormBuilderTest/model/Car.php";
require "FormBuilderTest/model/Translator.php";
require "FormBuilderTest/model/TestEntity.php";