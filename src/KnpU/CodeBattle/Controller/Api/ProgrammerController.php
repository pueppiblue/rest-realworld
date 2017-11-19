<?php

namespace KnpU\CodeBattle\Controller\Api;

use Exception;
use KnpU\CodeBattle\Api\ApiProblem;
use KnpU\CodeBattle\Controller\BaseController;
use KnpU\CodeBattle\Model\Programmer;
use ReflectionClass;
use ReflectionProperty;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProgrammerController extends BaseController
{
    protected function addRoutes(ControllerCollection $controllers)
    {
        $controllers->post('/api/programmers', array($this, 'newAction'));
        $controllers->get('/api/programmers', array($this, 'listAction'))
            ->bind('api_programmers_list');
        $controllers->get('/api/programmers/{nickname}', array($this, 'showAction'))
            ->bind('api_programmers_show');
        $controllers->put('/api/programmers/{nickname}', array($this, 'updateAction'));
        $controllers->patch('/api/programmers/{nickname}', array($this, 'updateAction'));
        $controllers->delete('/api/programmers/{nickname}', array($this, 'deleteAction'))
            ->bind('api_programmers_delete');
    }

    /**
     * @param Request $request
     * @return string|JsonResponse
     * @throws Exception
     */
    public function newAction(Request $request)
    {
        $programmer = new Programmer();

        try {
            $this->handleRequest($request, $programmer);
        } catch (Exception $e) {
            return 'Error when handling request: ' . $e->getMessage();
        }

        $errors = $this->validate($programmer);
        if (!empty($errors)) {
            return $this->handleValidationResponse($errors);
        }

        try {
            $this->save($programmer);
        } catch (Exception $e) {
            throw new Exception('Error saving programmer resource: ' . $e->getMessage());
        }

        $url = $this->generateUrl('api_programmers_show', [
            'nickname' => $programmer->nickname,
        ]);

        $data = $this->serializeProgrammer($programmer);

        $response = new JsonResponse($data, 201);
        $response->headers->set('Location', $url);

        return $response;

    }

    public function listAction()
    {
        $programmers = $this->getProgrammerRepository()->findAll();

        $data = ['programmers' => []];
        foreach ($programmers as $programmer) {
            $data['programmers'][] = $this->serializeProgrammer($programmer);
        }

        return new JsonResponse($data, 200);
    }

    public function showAction($nickname)
    {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);

        if (!$programmer) {
            throw new NotFoundHttpException('Programmer ' . $nickname . ' not found in api database.');
        }

        $data = $this->serializeProgrammer($programmer);

        return new JsonResponse($data, 200);
    }

    /**
     * @param $nickname
     * @param Request $request
     * @return string|JsonResponse
     * @throws Exception
     */
    public function updateAction($nickname, Request $request)
    {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);
        if (!$programmer) {
            throw new NotFoundHttpException('Programmer ' . $nickname . ' not found in api database.');
        }

        try {
            $this->handleRequest($request, $programmer);
        } catch (Exception $e) {
            return 'Error when handling request: ' . $e->getMessage();
        }

        $errors = $this->validate($programmer);
        if (!empty($errors)) {
            return $this->handleValidationResponse($errors);
        }

        try {
            $this->save($programmer);
        } catch (Exception $e) {
            throw new Exception('Error saving programmer resource: ' . $e->getMessage());
        }


        return new JsonResponse(
            $this->serializeProgrammer($programmer),
            200,
            ['Location' => $request->getRequestUri()]
        );
    }


    /**
     * @param $nickname
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteAction($nickname)
    {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);
        try {
            $this->delete($programmer);
        } catch (Exception $e) {
            throw new Exception('Error deleting Programmer ' . $nickname . '. ' . $e->getMessage());
        }

        return new JsonResponse(null, 204);

    }

    /**
     * @param Programmer $programmer
     * @return array
     */
    private function serializeProgrammer(Programmer $programmer)
    {
        $reflection = new ReflectionClass(Programmer::class);
        $props = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $data = [];
        foreach ($props as $prop) {
            $data[$prop->getName()] = $prop->getValue($programmer);
        }

        return $data;
    }

    /**
     * @param Request $request
     * @param Programmer $programmer
     * @return void
     * @throws Exception
     */
    private function handleRequest(Request $request, Programmer $programmer)
    {

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            throw new Exception('Invalid JSON in Request: ' . $request->getContent());
        }

        $isNew = ($programmer->id === null);

        // define properties managed by the api
        $apiProperties = ['avatarNumber', 'tagLine'];
        if ($isNew) {
            $apiProperties[] = 'nickname';
        }

        foreach ($apiProperties as $property) {
            if (!isset($data[$property]) && $request->isMethod('PATCH')) {
                continue;
            }
            if (property_exists($programmer, $property)) {
                $programmer->$property = isset($data[$property]) ? $data[$property] : null;
            }
        }
        $programmer->userId = $this->findUserByUsername('weaverryan')->id;
    }

    /**
     * @param $errors
     * @return JsonResponse with StatusCode 422
     */
    private function handleValidationResponse($errors)
    {
        $apiProblem = new ApiProblem(
            422,
            ApiProblem::TYPE_VALIDATION_ERROR
        );
        $apiProblem->setExtraData('errors',$errors);

        return new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode(),
            ['Content-Type' => 'application/problem+json']
        );
    }
}

