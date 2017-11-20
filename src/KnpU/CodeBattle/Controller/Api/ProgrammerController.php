<?php

namespace KnpU\CodeBattle\Controller\Api;

use Exception;
use JMS\Serializer\Serializer;
use KnpU\CodeBattle\Api\ApiProblem;
use KnpU\CodeBattle\Api\ApiProblemException;
use KnpU\CodeBattle\Controller\BaseController;
use KnpU\CodeBattle\Model\Programmer;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @return string|Response
     * @throws Exception
     */
    public function newAction(Request $request)
    {
        $programmer = new Programmer();

        $this->handleRequest($request, $programmer);

        $errors = $this->validate($programmer);
        if (!empty($errors)) {
            return $this->handleValidationErrors($errors);
        }

        try {
            $this->save($programmer);
        } catch (Exception $e) {
            throw new Exception('Error saving programmer resource: ' . $e->getMessage());
        }

        $url = $this->generateUrl('api_programmers_show', [
            'nickname' => $programmer->nickname,
        ]);

        $json = $this->serialize($programmer);

        $response = new Response($json, 201);
        $response->headers->set('Location', $url);

        return $response;

    }

    public function listAction()
    {
        $programmers = $this->getProgrammerRepository()->findAll();

        $data = ['programmers' => $programmers];
        $json = $this->serialize($data);

        return new Response($json, 200);
    }

    public function showAction($nickname)
    {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);

        if (!$programmer) {
            throw new NotFoundHttpException('Programmer ' . $nickname . ' not found in database.');
        }

        $json = $this->serialize($programmer);

        return new Response($json, 200);
    }

    /**
     * @param $nickname
     * @param Request $request
     * @return string|Response
     * @throws Exception
     */
    public function updateAction($nickname, Request $request)
    {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);
        if (!$programmer) {
            throw new NotFoundHttpException('Programmer ' . $nickname . ' not found in api database.');
        }

        $this->handleRequest($request, $programmer);

        $errors = $this->validate($programmer);
        if (!empty($errors)) {
            return $this->handleValidationErrors($errors);
        }

        try {
            $this->save($programmer);
        } catch (Exception $e) {
            throw new Exception('Error saving programmer resource: ' . $e->getMessage());
        }


        return new Response(
            $this->serialize($programmer),
            200,
            ['Location' => $request->getRequestUri()]
        );
    }


    /**
     * @param $nickname
     * @return Response
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

        return new Response(null, 204);

    }

    /**
     * @param $data
     * @return array
     */
    private function serialize($data)
    {
        /** @var Serializer $serializer */
        $serializer = $this->container['serializer'];

        return $serializer->serialize($data, 'json');
    }

    /**
     * @param Request $request
     * @param Programmer $programmer
     * @return void
     * @throws \KnpU\CodeBattle\Api\ApiProblemException
     */
    private function handleRequest(Request $request, Programmer $programmer)
    {

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            $apiProblem = new ApiProblem(
                400,
                ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT
            );
            throw new ApiProblemException(
                $apiProblem
            );
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
     * @throws \KnpU\CodeBattle\Api\ApiProblemException
     */
    private function handleValidationErrors($errors)
    {
        $apiProblem = new ApiProblem(
            422,
            ApiProblem::TYPE_VALIDATION_ERROR
        );
        $apiProblem->setExtraData('errors', $errors);

        throw new ApiProblemException($apiProblem);
    }
}

