<?php

namespace KnpU\CodeBattle;

use Doctrine\Common\Annotations\AnnotationReader;
use Hateoas\HateoasBuilder;
use Hateoas\UrlGenerator\SymfonyUrlGenerator;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;
use KnpU\CodeBattle\Api\ApiProblem;
use KnpU\CodeBattle\Api\ApiProblemException;
use KnpU\CodeBattle\Battle\BattleManager;
use KnpU\CodeBattle\Battle\PowerManager;
use KnpU\CodeBattle\DataFixtures\FixturesManager;
use KnpU\CodeBattle\Repository\BattleRepository;
use KnpU\CodeBattle\Repository\ProgrammerRepository;
use KnpU\CodeBattle\Repository\ProjectRepository;
use KnpU\CodeBattle\Repository\RepositoryContainer;
use KnpU\CodeBattle\Repository\UserRepository;
use KnpU\CodeBattle\Security\Authentication\ApiEntryPoint;
use KnpU\CodeBattle\Security\Authentication\ApiTokenListener;
use KnpU\CodeBattle\Security\Authentication\ApiTokenProvider;
use KnpU\CodeBattle\Security\Token\ApiTokenRepository;
use KnpU\CodeBattle\Twig\BattleExtension;
use KnpU\CodeBattle\Validator\ApiValidator;
use Silex\Application as SilexApplication;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

class Application extends SilexApplication
{
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->configureParameters();
        $this->configureProviders();
        $this->configureServices();
        $this->configureSecurity();
        $this->configureListeners();
    }

    /**
     * Dynamically finds all *Controller.php files in the Controller directory,
     * instantiates them, and mounts their routes.
     *
     * This is done so we can easily create new controllers without worrying
     * about some of the Silex mechanisms to hook things together.
     */
    public function mountControllers()
    {
        $controllerPath = 'src/KnpU/CodeBattle/Controller';
        $finder = new Finder();
        $finder->in($this['root_dir'] . '/' . $controllerPath)
            ->name('*Controller.php');

        foreach ($finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            // e.g. Api/FooController.php
            $cleanedPathName = $file->getRelativePathname();
            // e.g. Api\FooController.php
            // e.g. Api\FooController
            $cleanedPathName = str_replace(array('/', '.php'), array('\\', ''), $cleanedPathName);

            $class = 'KnpU\\CodeBattle\\Controller\\' . $cleanedPathName;

            // don't instantiate the abstract base class
            $refl = new \ReflectionClass($class);
            if ($refl->isAbstract()) {
                continue;
            }

            $this->mount('/', new $class($this));
        }
    }

    private function configureProviders()
    {
        // URL generation
        $this->register(new UrlGeneratorServiceProvider());

        // Twig
        $this->register(new TwigServiceProvider(), array(
            'twig.path' => $this['root_dir'] . '/views',
        ));
        $app['twig'] = self::share($this->extend('twig', function (\Twig_Environment $twig, $app) {
            $twig->addExtension($app['twig.battle_extension']);

            return $twig;
        }));

        // Sessions
        $this->register(new SessionServiceProvider());

        // Doctrine DBAL
        $this->register(new DoctrineServiceProvider(), array(
            'db.options' => array(
                'driver' => 'pdo_sqlite',
                'path' => $this['sqlite_path']
            ),
        ));

        // Monolog
        $this->register(new MonologServiceProvider(), array(
            'monolog.logfile' => $this['root_dir'] . '/logs/development.log',
        ));

        // Validation
        $this->register(new ValidatorServiceProvider());
        // configure validation to load from a YAML file
        $this['validator.mapping.class_metadata_factory'] = self::share(function () {
            return new ClassMetadataFactory(
                new AnnotationLoader($this['annotation_reader'])
            );
        });

        // Translation
        $this->register(new TranslationServiceProvider(), array(
            'locale_fallbacks' => array('en'),
        ));
        $this['translator'] = self::share($this->extend('translator', function ($translator) {
            /** @var \Symfony\Component\Translation\Translator $translator */
            $translator->addLoader('yaml', new YamlFileLoader());

            $translator->addResource('yaml', $this['root_dir'] . '/translations/en.yml', 'en');

            return $translator;
        }));
    }

    private function configureParameters()
    {
        $this['root_dir'] = __DIR__ . '/../../..';
        $this['sqlite_path'] = $this['root_dir'] . '/data/code_battles.sqlite';
    }

    private function configureServices()
    {
        $app = $this;

        $this['repository.user'] = self::share(function () use ($app) {
            $repo = new UserRepository($app['db'], $app['repository_container']);
            $repo->setEncoderFactory($app['security.encoder_factory']);

            return $repo;
        });
        $this['repository.programmer'] = self::share(function () use ($app) {
            return new ProgrammerRepository($app['db'], $app['repository_container']);
        });
        $this['repository.project'] = self::share(function () use ($app) {
            return new ProjectRepository($app['db'], $app['repository_container']);
        });
        $this['repository.battle'] = self::share(function () use ($app) {
            return new BattleRepository($app['db'], $app['repository_container']);
        });
        $this['repository.api_token'] = self::share(function () use ($app) {
            return new ApiTokenRepository($app['db'], $app['repository_container']);
        });
        $this['repository_container'] = self::share(function () use ($app) {
            return new RepositoryContainer($app, array(
                'user' => 'repository.user',
                'programmer' => 'repository.programmer',
                'project' => 'repository.project',
                'battle' => 'repository.battle',
                'api_token' => 'repository.api_token',
            ));
        });

        $this['battle.battle_manager'] = self::share(function () use ($app) {
            return new BattleManager(
                $app['repository.battle'],
                $app['repository.programmer']
            );
        });
        $this['battle.power_manager'] = self::share(function () use ($app) {
            return new PowerManager(
                $app['repository.programmer']
            );
        });

        $this['fixtures_manager'] = self::share(function () use ($app) {
            return new FixturesManager($app);
        });

        $this['twig.battle_extension'] = self::share(function () use ($app) {
            return new BattleExtension(
                $app['request_stack'],
                $app['repository.programmer'],
                $app['repository.project']
            );
        });

        $this['annotation_reader'] = self::share(function () {
            return new AnnotationReader();
        });
        // you could use a cache with annotations if you want
        //$this['annotations.cache'] = new PhpFileCache($this['root_dir'].'/cache');
        //$this['annotation_reader'] = new CachedReader($this['annotations_reader'], $this['annotations.cache'], $this['debug']);

        $this['api.validator'] = self::share(function () use ($app) {
            return new ApiValidator($app['validator']);
        });

        $this['serializer'] = self::share(function () use ($app) {
            $serializerBuilder = SerializerBuilder::create()
                ->setCacheDir($app['root_dir'] . '/cache/serializer')
                ->setDebug($app['debug'])
                ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy());
            return HateoasBuilder::create($serializerBuilder)
                ->setUrlGenerator('sf_url_generator', new SymfonyUrlGenerator($app['url_generator']))
                ->build();
        });
    }

    private function configureSecurity()
    {
        $app = $this;

        $this->register(new SecurityServiceProvider(), array(
            'security.firewalls' => array(
                'api' => array(
                    'pattern' => '^/api',
                    'users' => self::share(function () use ($app) {
                        return $app['repository.user'];
                    }),
                    'stateless' => true,
                    'http' => true,
                    'anonymous' => true,
                    'api_token' => true,
                ),
                'main' => array(
                    'pattern' => '^/',
                    'form' => true,
                    'users' => self::share(function () use ($app) {
                        return $app['repository.user'];
                    }),
                    'anonymous' => true,
                    'logout' => true,
                ),
            )
        ));

        // require login for application management
        $this['security.access_rules'] = array(
            // placeholder access control for now
            array('^/register', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/login', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            // allow anonymous API - if auth is needed, it's handled in the controller
            array('^/api', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/', 'IS_AUTHENTICATED_FULLY'),
        );

        // setup our custom API token authentication
        $app['security.authentication_listener.factory.api_token'] = self::protect(function ($name) use ($app) {

            // the class that reads the token string off of the Authorization header
            $app['security.authentication_listener.' . $name . '.api_token'] = self::share(function () use ($app) {
                return new ApiTokenListener($app['security.token_storage'], $app['security.authentication_manager']);
            });

            // the class that looks up the ApiToken object in the database for the given token string
            // and authenticates the user if it's found
            $app['security.authentication_provider.' . $name . '.api_token'] = self::share(function () use ($app) {
                return new ApiTokenProvider($app['repository.user'], $app['repository.api_token']);
            });

            // the class that decides what should happen if no authentication credentials are passed
            $this['security.entry_point.' . $name . '.api_token'] = self::share(function () use ($app) {
                return new ApiEntryPoint($app['translator']);
            });

            return array(
                // the authentication provider id
                'security.authentication_provider.' . $name . '.api_token',
                // the authentication listener id
                'security.authentication_listener.' . $name . '.api_token',
                // the entry point id
                'security.entry_point.' . $name . '.api_token',
                // the position of the listener in the stack
                'pre_auth'
            );
        });

        // expose a fake "user" service
        $this['user'] = self::share(function () use ($app) {
            $user = $app['security']->getToken()->getUser();

            return \is_object($user) ? $user : null;
        });
    }

    private function configureListeners()
    {

        $app = $this;

        $this->error(function (\Exception $e, $statusCode) use ($app) {
            /** @var Request $request */
            $request = $app['request'];
            // path must start with /api
            if (strpos($request->getPathInfo(), '/api') !== 0) {
                return null;
            }

            // allow 500 errors to be thrown in Debug mode
            if (($statusCode === 500) && $app['debug']) {
                return null;
            }

            if ($e instanceof ApiProblemException) {
                $apiProblem = $e->getApiProblem();
            } else {
                $apiProblem = new ApiProblem($statusCode);

                /*
                 * If it's an HttpException (e.g. 404)
                 * we state as a rule that the exception message is safe
                 * to be seen by the client. Otherwise in a low-level exception
                 * there could be some sensitive information in the message, that
                 * should not be exposed.
                 */
                if ($e instanceof HttpException) {
                    $apiProblem->setDetail($e->getMessage());
                }
            }

            return $apiProblem->createApiProblemResponse(
                $request->getHost() . '/docs/errors#'
            );

        });
    }
} 