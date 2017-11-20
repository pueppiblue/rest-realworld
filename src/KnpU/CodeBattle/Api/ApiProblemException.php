<?php

namespace KnpU\CodeBattle\Api;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiProblemException extends HttpException
{
    /**
     * @var ApiProblem
     */
    private $apiProblem;

    public function __construct(ApiProblem $apiProblem, \Exception $previous = null, array $headers = array(), int $code = 0)
    {
        parent::__construct(
            $apiProblem->getStatusCode(),
            $apiProblem->getTitle(),
            $previous, $headers, $code);

        $this->apiProblem = $apiProblem;
        if ($previous instanceof HttpException) {
            $this->apiProblem->setDetail($previous->getMessage());
        }
    }

    /**
     * @return ApiProblem
     */
    public function getApiProblem(): ApiProblem
    {
        return $this->apiProblem;
    }


}