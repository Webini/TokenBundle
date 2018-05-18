<?php

namespace Wini\TokenBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Wini\TokenBundle\Entity\Token;
use Wini\Manager\AbstractFlushManager;

class TokenManager extends AbstractFlushManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /**
     * Va créer une token
     * @param mixed $data
     * @param integer $duration Durée de vie de la token en secondes
     * @param integer|null $type Type de la token
     * @param DateTime $start Date de début de vie de la token
     * @return Token
     */
    public function create($data = null, $duration = null, $type = null, $start = null)
    {
        $token = new Token();
        $token->setData($data)
              ->setType($type)
              ->fillToken();
        
        if ($start) {
            $token->setStart($start);
        }
        
        if ($duration) {
            $token->setDuration($duration);
        }
        
        $this->em->persist($token);
        
        if ($this->shouldFlush()) {
            $this->em->flush();
        }
        
        return $token;
    }

    /**
     * Récupère la token grace a la string de base
     * @param string $token
     * @return null|Token
     * @throws \Exception
     */
    public function getToken($token)
    {
        /** @var Token $token */
        $token = $this->em->getRepository(Token::class)->findOneBy([ 'token' => $token ]);

        if ($token && ($data = $token->getData()) && is_object($data)) {
            $ref = new \ReflectionClass($data);
            $className = (!empty($ref->getNamespaceName()) ? '\\' . $ref->getNamespaceName() . '\\' : '');
            $className .= $ref->getShortName();
            $metadataFactory = $this->em->getMetadataFactory();

            if ($metadataFactory->isTransient($className)) {
                $identifiers = $metadataFactory->getMetadataFor($className)->getIdentifierFieldNames();
                $findParameters = [];

                foreach ($identifiers as $identifier) {
                    $fn = 'get' . $identifier;
                    if ($ref->hasMethod($fn)) {
                        $findParameters[$identifier] = $data->$fn();
                    }
                }

                if (empty($findParameters)) {
                    throw new \Exception('Cannot find identifier for entity ' . $className);
                }

                $repo = $this->em->getRepository($className);
                $data = $repo->findOneBy($findParameters);
                $token->setData($data);
            }
        }
        return $token;
    }
    
    /**
     * Supprime la token
     * @param Token $token
     */
    public function remove(Token $token)
    {
        $this->em->remove($token);
        
        if ($this->shouldFlush()) {
            $this->em->flush();
        }
    }
}
