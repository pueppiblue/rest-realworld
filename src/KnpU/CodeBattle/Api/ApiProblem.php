<?php

namespace KnpU\CodeBattle\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
    public function __construct(int $statusCode, string $type = null)
    {
        $this->statusCode = $statusCode;

        if ($type === null) {
            $this->type = 'about:blank';
            $this->title = Response::$statusTexts[$statusCode] ?? 'Unknown status code.';
        } else {
            $this->type = $type;
            $this->setTitle($type);
        }
    }

    /**
     * @param string $urlPrefix
     * @return JsonResponse
     */
    public function createApiProblemResponse(string $urlPrefix = null): JsonResponse
    {
        $data = $this->toArray();
        if ($data['type'] !== 'about:blank') {
            $data['type'] = $urlPrefix . $data['type'];
        }

        return new JsonResponse(
            $data,
            $this->getStatusCode(),
            ['Content-Type' => 'application/problem+json']
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

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }


}