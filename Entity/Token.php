<?php

namespace Wini\TokenBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Serializable;
use Wini\TokenBundle\Exception\InvalidTokenException;

/**
 * Token
 *
 * @ORM\Table(indexes={ @ORM\Index(name="type_idx", columns={"type"}) })
 * @ORM\Entity
 */
class Token implements Serializable
{
    const ONE_MINUTE = 60;
    const ONE_HOUR   = self::ONE_MINUTE * 60;
    const ONE_DAY    = self::ONE_HOUR * 24;
    const ONE_WEEK   = self::ONE_DAY * 7;
    const ONE_MONTH  = self::ONE_WEEK * 4;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", unique=true, length=255)
     */
    private $token;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", nullable=true, type="integer")
     */
    private $duration;

    /**
     * @var \DateTime
     * @ORM\Column(name="start", type="datetime")
     * */
    private $start;
    
    /**
     * @var string
     * @ORM\Column(name="data", type="object", nullable=true)
     */
    private $data;

    /**
     * @var integer
     * @ORM\Column(name="type", type="integer", nullable = true)
     */
    private $type;

    public function __construct()
    {
        $this->start = new DateTime();
    }

    /**
     * @param integer $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Data lié a la token
     * @param object $data
     * @return \Wini\TokenBundle\Entity\Token
     */
    public function setData($data = null) 
    {
        $this->data = $data;
        return $this;
    }
    
    /**
     * @return object
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Va hasher une token
     * @param string $seed Donnée initiales
     * @param boolean $raw si on retourne le hash en buffer binaire
     * @return string 
     */
    private function hashToken($seed = null, $raw = false) 
    {
        return hash('sha512', uniqid(null, true) . mt_rand(0, time(NULL)) . ($seed ? $seed : ''), $raw);
    }
    
    /**
     * Va remplir la token
     * @param integer $rounds
     * @param string $seed
     * @return \Wini\TokenBundle\Entity\Token
     */
    public function fillToken($rounds = 32, $seed = null)
    {
        $token = $this->hashToken($seed, false);
        
        for ($i = 0; $i < $rounds; $i++) {
            $token = $this->hashToken($token, false); 
        }
        
        $this->setToken(base64_encode($this->hashToken($token, true)));
        return $this;
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Token
     */
    public function setToken($token) {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * Définit la durée de la token
     * @param integer $duration Durée de la clée en secondes
     * @return \Wini\TokenBundle\Entity\Token
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * Récupère la durée de la token
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }
    
    /**
     * Récupère la date d'expiration
     * @return \DateTime
     */
    public function getExpireDate()
    {
        $duration = $this->getDuration();
        if ($duration === null) {
            return null;
        } 
        
        $date = clone $this->getStart();   
        return $date->modify('+' . $duration . 'seconds');
    }
    
    /**
     * @param DateTime $start
     * @return \Wini\TokenBundle\Entity\Token
     */
    public function setStart(DateTime $start = null) {
        $this->start = $start;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStart() {
        return $this->start;
    }

    /**
     * @return boolean
     */
    public function isExpired() {
        $now = new \DateTime();
        if ($this->getExpireDate() === null ||
                $this->getStart() <= $now && $this->getExpireDate() >= $now)
            return false;
        return true;
    }

    /**
     * @throws InvalidTokenException
     */
    public function assert($throw = true) {
        if ($this->isExpired()) {
            if ($throw) {
                throw new InvalidTokenException('Token expired');
            }
            return false;
        }
        return true;
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize([
            $this->id
        ]);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
        ) = unserialize($serialized);
    }
}
