<?php

namespace KnpU\CodeBattle\Model;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Programmer
 * @package KnpU\CodeBattle\Model
 * @Serializer\ExclusionPolicy("all")
 */
class Programmer
{

    /* All public properties are persisted */
    public $id;

    /**
     * @Assert\NotBlank(message="Clever or/and nerdy nickname required when creating a programmer.")
     * @Serializer\Expose()
     */
    public $nickname;

    /**
     * Number of an avatar, from 1-6
     *
     * @var integer
     * @Serializer\Expose()
     */
    public $avatarNumber;

    /**
     * @Serializer\Expose()
     */
    public $tagLine;

    public $userId;

    /**
     * @var int
     * @Serializer\Expose()
     */
    public $powerLevel = 0;

    public function __construct($nickname = null, $avatarNumber = null)
    {
        $this->nickname = $nickname;
        $this->avatarNumber = $avatarNumber;
    }


}
