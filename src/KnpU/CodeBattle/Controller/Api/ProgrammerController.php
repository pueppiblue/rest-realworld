<?php

namespace KnpU\CodeBattle\Controller\Api;

use Exception;
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
        $controllers->put('/api/programmers/{nickname}', array($this, 'updateAction'))
            ->bind('api_programmers_update');
    }

    public function newAction(Request $request)
    {
        $programmer = new Programmer();

        try {
            $this->handleRequest($request, $programmer);
        } catch (Exception $e) {
            return 'Error when handling request: ' . $e->getMessage();
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

        $response = new JsonResponse($data, 200);

        return $response;
    }

    public function showAction($nickname)
    {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);

        if (!$programmer) {
            throw new NotFoundHttpException('Programmer ' . $nickname . ' not found in api database.');
        }

        $data = $this->serializeProgrammer($programmer);

        $response = new JsonResponse($data, 200);

        return $response;
    }

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

        return new JsonResponse(
            $this->serializeProgrammer($programmer),
            200,
            ['Location' => $request->getRequestUri()]
        );
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

        $programmer->userId = $this->findUserByUsername('weaverryan')->id;

        foreach ($data as $key => $value) {
            if (property_exists($programmer, $key)) {
                $programmer->$key = $value;
            }
        }

        try {
            $this->save($programmer);
        } catch (Exception $e) {
            throw new Exception('Error saving programmer resource: '.$e->getMessage());
        }

    }


}
