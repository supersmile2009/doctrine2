<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Cache
 * @ORM\Entity
 * @ORM\Table("cache_traveler")
 */
class Traveler
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column
     */
    protected $name;

    /**
     * @ORM\Cache("NONSTRICT_READ_WRITE")
     * @ORM\OneToMany(targetEntity=Travel::class, mappedBy="traveler", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var \Doctrine\Common\Collections\Collection
     */
    public $travels;

    /**
     * @ORM\Cache
     * @ORM\OneToOne(targetEntity=TravelerProfile::class)
     */
    protected $profile;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name     = $name;
        $this->travels  = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return \Doctrine\Tests\Models\Cache\TravelerProfile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\TravelerProfile $profile
     */
    public function setProfile(TravelerProfile $profile)
    {
        $this->profile = $profile;
    }

    public function getTravels()
    {
        return $this->travels;
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\Travel $item
     */
    public function addTravel(Travel $item)
    {
        if ( ! $this->travels->contains($item)) {
            $this->travels->add($item);
        }

        if ($item->getTraveler() !== $this) {
            $item->setTraveler($this);
        }
    }

    /**
     * @param \Doctrine\Tests\Models\Cache\Travel $item
     */
    public function removeTravel(Travel $item)
    {
        $this->travels->removeElement($item);
    }
}
