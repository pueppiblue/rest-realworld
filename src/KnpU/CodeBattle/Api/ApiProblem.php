<?php

namespace KnpU\CodeBattle\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiProblem
{
    const TYPE_VALIDATION_ERROR = 'validation_error';
    const TYPE_INVALID_REQUEST_BODY_FORMAT = 'invalid_body_format';
    /**
     * @var string
     */

    /**@var array $titles */
    private static $titles = [
        self::TYPE_VALIDATION_ERROR => 'Validation error occurred.',
        self::TYPE_INVALID_REQUEST_BODY_FORMAT => 'Invalid JSON in the request body.',
    ];

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
     * @throws \Exception
     */
    public function __construct(int $statusCode, string $type)
    {
        $this->type = $type;
        $this->statusCode = $statusCode;
        $this->setTitle($type);
    }

    /**
     * @return JsonResponse
     */
    public function createApiProblemResponse(): JsonResponse
    {
        return new JsonResponse(
            $this->toArray(),
            $this->getStatusCode(),
            ['Content-Type' =>'application/problem+json']
        );
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

    /**
     * @param string $type
     * @throws \Exception
     */
    private function setTitle(string $type)
    {
        if (!isset(self::$titles[$type])) {
            throw new \Exception(
                sprintf('Title for type: %s not found in ApiProblem Class.', $type)
            );
        }

        $this->title = self::$titles[$type];
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }




}