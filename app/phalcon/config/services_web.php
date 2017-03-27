<?php
use Phalcon\Mvc\Dispatcher;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Http\Response as HttpResponse;
use Phalcon\Http\Response\Cookies as HttpCookies;
use Phalcon\Http\Request as HttpRequest;
use Phalcon\Session\Bag as SessionBag;
use Phalcon\Session\Adapter\Database as DatabaseSession;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use Webird\Plugins\DispatcherSecurity;
use Webird\Mvc\Router as Router;
use Webird\Auth\Auth;

/**
 *
 */
$di->setShared('router', function() {
    $config = $this->getConfig();

    $router = new Router();

    //Remove trailing slashes automatically
    $router->removeExtraSlashes(true);

    if (! isset($_GET['_url'])) {
       $router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);
    }

    // Fetch routes from user
    require($config->path->configDir . '/routes.php');

    return $router;
});

/**
 *
 */
$di->setShared('dispatcher', function() {
    $security = new DispatcherSecurity();
    $security->setDI($this);

    //Listen for events produced in the dispatcher using the Security plugin
    $evManager = $this->getShared('eventsManager');
    $evManager->attach('dispatch', $security);

    $dispatcher = new Dispatcher();
    $dispatcher->setEventsManager($evManager);

    return $dispatcher;
});

/**
 *
 */
$di->setShared('response', function() {
    return new HttpResponse();
});

/**
 *
 */
$di->setShared('cookies', function() {
    $config = $this->getConfig();

    $cookies = new HttpCookies();
    $cookies->useEncryption($config->server->https);
    return $cookies;
});


/**
 *
 */
$di->setShared('request', function() {
    return new HttpRequest();
});

/**
 *
 */
$di->setShared('session', function() {
    $config = $this->getConfig();
    $session = new DatabaseSession([
        'db'          => $this->getDb(),
        'table'       => $config->session->table,
        'session_id'  => $config->session->session_id,
        'data'        => $config->session->data,
        'created_at'  => $config->session->created_at,
        'modified_at' => $config->session->modified_at,
    ]);
    $session->start();

    return $session;
});

/**
 *
 */
$di->set('sessionBag', function($arg) {
    return new SessionBag($arg);
});

/**
 *
 */
$di->set('auth', function () {
    return new Auth();
});

/**
 *
 */
$di->set('googleOauthProvider', function () {
    $configProvider = $this->getConfig()
        ->services['google'];

    return new GoogleProvider([
        'clientId'     => $configProvider->clientId,
        'clientSecret' => $configProvider->clientSecret,
        'redirectUri'  => $this->getUrl()->get('signin/oauth/google'),
        'hostedDomain' => "$proto://$domain",
    ]);
});

/**
 *
 */
$di->set('flash', function() {
    return new Flash([
        'error' => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice' => 'alert alert-info',
    ]);
});
