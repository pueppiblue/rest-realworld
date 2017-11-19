<?php

namespace KnpU\CodeBattle\Api;

class ApiProblem
{
    /**
     * @var string
     */
    private $type;
    /**
     * @var int
     */
    private $statusCode;
    /**
     * @var string
     */
    private $title;

    private $extraData = [];

    /**
     * ApiProblem constructor.
     * @param string $type
     * @param int $statusCode
     * @param string $title
     */
    public function __construct(int $statusCode, string $type, string $title)
    {
        $this->type = $type;
        $this->statusCode = $statusCode;
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function setExtraData(string $key, $value)
    {
        $this->extraData[$key] = $value;
    }

    public function toArray()
    {
        return array_merge(
            $this->extraData,
            [
                'statusCode' => $this->getStatusCode(),
                'type' => $this->type,
                'title' => $this->title,
            ]
        );
    }


}