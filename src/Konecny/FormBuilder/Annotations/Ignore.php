<?php

namespace Konecny\FormBuilder\Annotations;

use Doctrine\Common\Annotations\Annotation;


/**
 * Prevents a control input for this property to be created
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Ignore extends Annotation
{
	
}