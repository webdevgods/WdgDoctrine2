<?php
namespace WdgDoctrine2\Doctrine2;

use Doctrine\ORM\Mapping as ORM;

abstract class Entity
{
    /** 
     * @ORM\Column(type="datetime") 
     * @var \DateTime $created
     */
    protected $created;

    /** 
     * @ORM\Column(type="datetime") 
     * @var \DateTime $updated
     */
    protected $updated;
    
    public function __construct()
    {
        $this->created	= $this->updated = new \DateTime("now");
    }
    
    /**
     * @PreUpdate
     */
    public function updated()
    {
        $this->updated = new \DateTime("now");
    }
    
    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
    
    public function populate(array $array)
    {
        foreach ($array as $key => $value) 
        {
            if(property_exists($this, $key))
            {
                $this->$key = $value;
            }
        }
    }    
    
    public function toArray()
    {
        $reflection = new \ReflectionClass($this);
        $details    = array();
	
        foreach($reflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) 
        {
            if(!$property->isStatic()) 
            {
                $details[$property->getName()] = $this->{$property->getName()};
            }
        }
        
        return $details;
    }
}