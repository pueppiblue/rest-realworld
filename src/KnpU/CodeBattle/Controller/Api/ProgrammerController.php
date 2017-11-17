<?php

namespace KnpU\CodeBattle\Controller\Api;

use KnpU\CodeBattle\Controller\BaseController;
use KnpU\CodeBattle\Model\Programmer;
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
    }

    public function newAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $programmer = new Programmer($data['nickname'], $data['avatarNumber']);
        $programmer->tagLine = $data['tagLine'];
        $programmer->userId = $this->findUserByUsername('weaverryan')->id;

        try {
            $this->save($programmer);
        } catch (\Exception $e) {
            return 'Error when saving programmer resource: ' . $e->getMessage();
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

    /**
     * @param Programmer $programmer
     * @return array
     */
    private function serializeProgrammer(Programmer $programmer)
    {
        return [
            'nickname' => $programmer->nickname,
            'avatarNumber' => $programmer->avatarNumber,
            'powerLevel' => $programmer->powerLevel,
            'tagLine' => $programmer->tagLine,
        ];
    }


}
