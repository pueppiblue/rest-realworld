<?php

namespace KnpU\CodeBattle\Controller\Api;


use KnpU\CodeBattle\Controller\BaseController;
use KnpU\CodeBattle\Model\Programmer;
use KnpU\CodeBattle\Model\Project;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

class BattleController extends BaseController
{
    protected function addRoutes(ControllerCollection $controllers)
    {
        $controllers->post('/api/battles', [$this, 'newAction'])
            ->bind('api_battles_create');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|null
     * @throws \KnpU\CodeBattle\Api\ApiProblemException
     * @throws \Exception
     */
    public function newAction(Request $request)
    {
        $this->enforceUserSecurity();

        $data = $this->decodeRequestBodyIntoParameters($request);

        $battleManager = $this->getBattleManager();
        /** @var Programmer $programmer */
        $programmer = $this->getProgrammerRepository()->find($data->get('programmerId'));
        /** @var Project $project */
        $project = $this->getProjectRepository()->find($data->get('projectId'));

        $errors = [];
        if (!$programmer) {
            $errors['programmerId'] = 'Invalid or missing programmerId';
        }
        if (!$project) {
            $errors['projectId'] = 'Invalid or missing projectId';
        }

        if (!empty($errors)) {
            $this->handleValidationErrors($errors);
        }

        $battle = $battleManager->battle($programmer, $project);

//        $url = $this->generateUrl('user_tokens');

        return $this->createApiResponse($battle, 201,
            ['Location' => '']);
    }
}