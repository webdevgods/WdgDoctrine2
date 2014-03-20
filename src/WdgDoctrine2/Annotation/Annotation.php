<?php
namespace WdgDoctrine2\Annotation;

// Initialize the Annotation reader, set the correct namespace  
Annotation::$reader = new \Doctrine\Common\Annotations\AnnotationReader();  
//Annotation::$reader->setDefaultAnnotationNamespace( __NAMESPACE__ . "\\" );  
require_once '../Discriminator/DiscriminatorEntry.php';
/**
 * @link http://thoughtsofthree.com/2011/04/defining-discriminator-maps-at-child-level-in-doctrine-2-0/ 
 */
class Annotation 
{  
    public static $reader;  

    public static function getAnnotationsForClass( $className ) 
    {  
        // Get the reflection class and return the annotations  
        $class = new \ReflectionClass( $className );  
        return Annotation::$reader->getClassAnnotations( $class );  
    }  
}  