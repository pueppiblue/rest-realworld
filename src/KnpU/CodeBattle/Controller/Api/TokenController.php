<?php

namespace KnpU\CodeBattle\Controller\Api;


use KnpU\CodeBattle\Controller\BaseController;
use KnpU\CodeBattle\Security\Token\ApiToken;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

class TokenController extends BaseController
{


    protected function addRoutes(ControllerCollection $controllers)
    {
        $controllers->post('/api/tokens', [$this, 'newAction'])
            ->bind('api_tokens_create');
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

        $token = new ApiToken($this->getLoggedInUser()->id);
        $data = json_decode($request->getContent(), true);
        $token->notes = $data['notes'];

        try {
            $this->getApiTokenRepository()->save($token);
        } catch (\Exception $e) {
            throw new \Exception('Error saving token: ' . $e->getMessage());
        }

        $url = $this->generateUrl('user_tokens');

        return $this->createApiResponse($token, 201,
            ['Location' => $url]);
    }
}