<?php
namespace WdgDoctrine2\Doctrine2;
/**
 * @Annotation
 */
class DiscriminatorEntry 
{  
    private $value; // Will hold the discriminator value  

    public function __construct( array $data ) 
    {  
        $this->value = $data['value'];  
    }  

    public function getValue() 
    {  
        return $this->value;  
    }  
}  