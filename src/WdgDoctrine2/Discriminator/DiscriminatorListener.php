<?php
namespace WdgDoctrine2\Discriminator;

/**
 * @link http://thoughtsofthree.com/2011/04/defining-discriminator-maps-at-child-level-in-doctrine-2-0/ 
 */
class DiscriminatorListener implements \Doctrine\Common\EventSubscriber 
{  
    private $driver;    // Doctrines Metadata Driver
    private $cachedMap; // The cached map for fast lookups
    private $map;       // Our temporary map for calculations
  
    const ENTRY_ANNOTATION = 'WdgDoctrine2\Discriminator\DiscriminatorEntry';
  
    public function getSubscribedEvents() 
    {  
        // Subscribe to the correct event  
        return Array( \Doctrine\ORM\Events::loadClassMetadata );  
    }  
  
    public function __construct( \Doctrine\ORM\Configuration $Configuration ) 
    {  
        $this->driver = $Configuration->getMetadataDriverImpl();  
        $this->cachedMap = Array();  
    }  
    
    private function extractEntry( $class ) 
    {  
        $annotations = \WdgDoctrine2\Annotation\Annotation::getAnnotationsForClass( $class );
        $success = false;  
        
        foreach($annotations as $key => $annotation)
        {
            if( get_class($annotation) == self::ENTRY_ANNOTATION )
            {
                $value = $annotations[$key]->getValue();  

                if( in_array( $value, $this->map ) ) 
                {  
                    throw new Exception( "Found duplicate discriminator map entry '" . $value . "' in " . $class );  
                }  

                $this->map[$class] = $value;  
                $success =  true;  
            }  
        }

        return $success;  
    } 
    
    private function checkFamily( $class ) 
    {  
        $rc             = new \ReflectionClass( $class );  
        $is_base_class  = false;
        $annotations    = \WdgDoctrine2\Annotation\Annotation::getAnnotationsForClass( $class );
        
        
        foreach($annotations as $annotation)
        {
            if(get_class($annotation) == "Doctrine\ORM\Mapping\InheritanceType")
                $is_base_class = true;
        }
        
        if( !$is_base_class && $rc->getParentClass() !== false ) 
        {  
            $parent = $rc->getParentClass()->name;
            // Also check all the children of our parent  
            $this->checkFamily( $parent );  
        } 
        else 
        {  
            // This is the top-most parent, used in overrideMetadata  
            $this->cachedMap[$class]['isParent'] = true;  

            // Find all the children of this class  
            $this->checkChildren( $class );  
        }  
    }  

    private function checkChildren( $class ) 
    {  
        foreach( $this->driver->getAllClassNames() as $name ) 
        {  
            $cRc            = new \ReflectionClass( $name );  
            $parent_class   = $cRc->getParentClass();
            $cParent        = $parent_class ? $parent_class->name : null;  
            
            // Check if we already had this class, if its a child and if it has the annotation  
            if( ! array_key_exists( $name, $this->map )  
                && $cParent == $class && $this->extractEntry( $name ) ) 
            {  
                // This child might again have children...  
                $this->checkChildren( $name );  
            }  
        }  
    }
    
    private function overrideMetadata( \Doctrine\ORM\Event\LoadClassMetadataEventArgs $event, $class ) 
    {  
        // Set the discriminator map and value  
        $event->getClassMetadata()->discriminatorMap =  
                $this->cachedMap[$class]['map'];  
        $event->getClassMetadata()->discriminatorValue =  
                $this->cachedMap[$class]['discr'];  
      
        // If we are the top-most parent, set subclasses!  
        if( isset( $this->cachedMap[$class]['isParent'] )  
            && $this->cachedMap[$class]['isParent'] === true ) 
        {  
            // Remove yourself from the map, set this as subclasses, but only the values!  
            $subclasses = $this->cachedMap[$class]['map'];  
            unset( $subclasses[$this->cachedMap[$class]['discr']] );  
            $event->getClassMetadata()->subClasses =  
                    array_values( $subclasses );  
        }  
    }  
  
    public function loadClassMetadata( \Doctrine\ORM\Event\LoadClassMetadataEventArgs $event ) 
    {  
        // Reset the temporary calculation map and get the classname  
        $this->map  = Array();  
        $class      = $event->getClassMetadata()->name;  
      
        // Lookup whether we already calculated the map for this element  
        if( array_key_exists( $class, $this->cachedMap ) ) 
        {  
            $this->overrideMetadata( $event, $class );  
            return;  
        }  
        
        // Check whether we have to process this class  
        if( count( $event->getClassMetadata()->discriminatorMap ) == 1  
                && $this->extractEntry( $class ) ) {  
            // Now build the whole map  
            $this->checkFamily( $class );  
        } else {  
            // Nothing to do...  
            return;  
        }  
      
        // Create the lookup entries  
        $dMap = array_flip( $this->map );  
        foreach( $this->map as $cName => $discr ) {  
            $this->cachedMap[$cName]['map']     = $dMap;  
            $this->cachedMap[$cName]['discr']   = $this->map[$cName];  
        }  
      
        // Override the data for this class  
        $this->overrideMetadata( $event, $class );  
    }  
} 