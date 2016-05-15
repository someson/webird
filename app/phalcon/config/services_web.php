<?php
use Phalcon\Mvc\Dispatcher,
    Phalcon\Http\Response\Cookies as Cookies,
    Phalcon\Flash\Direct as Flash,
    Webird\Plugins\DispatcherSecurity,
    Webird\Mvc\Router as Router,
    Webird\Auth\Auth,
    Webird\DatabaseSession;

/**
 *
 */
$di->setShared('session', function() {
    $config = $this->getConfig();
    $connection = $this->getDb();

    $session = new DatabaseSession([
        'db'          => $connection,
        'db_table'    => $config->session->db_table,
        'db_id_col'   => $config->session->db_id_col,
        'db_data_col' => $config->session->db_data_col,
        'db_time_col' => $config->session->db_time_col,
        'uniqueId'    => $config->session->unique_id
    ]);

    $session->start();
    return $session;
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
$di->setShared('cookies', function() {
    $config = $this->getConfig();

    $cookies = new Cookies();
    $cookies->useEncryption($config->server->https);
    return $cookies;
});

/**
 *
 */
$di->set('flash', function() {
    return new Flash([
        'error' => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice' => 'alert alert-info'
    ]);
});
