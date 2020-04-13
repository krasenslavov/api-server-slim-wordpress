<?php

  // Load up WordPress.
  require_once '../wp-load.php';
  // Autoloader for Composer with (all packages and dependencies).
  require_once 'vendor/autoload.php';

  // Get all WordPress users with role subscriber.
  $users = array();
  $subscribers = get_users(['role__in' => ['subscriber']]);

  foreach ($subscribers as $subscriber) {
    $users[$subscriber->user_login]['password'] = $subscriber->password;
    $users[$subscriber->user_login]['display_name'] = $subscriber->display_name;
  }

  // Instantiate Slim PHP framerwork.
  $app = new Slim\App();

  // Basic authentication.
  $app->add(new \Slim\Middleware\HttpBasicAuthentication(array(
    'realm'  => 'Protected',
    // Restricted folder.
    // For your setup may need to add `/api` as your restricted folder.
    'path'   => '/', 
    // Working over HTTPS for improved security.
    'secure' => true,
    // All register users (out of WordPress).
    'authenticator' => function ($arguments) use($users) {
      if (password_verify($arguments['password'], $users[$arguments['user']]['password'])) {
        return true;
      }
      return false;
    },
    // Use to return an error message if the auth fails.
    'error'  => function ($request, $response, $arguments) {
      $data = [];
      $data['status'] = 'error';
      $data['message'] = $arguments['message'];
      $body = $response->getBody();
      $body->write(json_encode($data, JSON_UNESCAPED_SLASHES));
      return $response->withBody($body);
    }
  )));

  // Define app routes.
  $app->get('/hello', function ($request, $response, $arguments) use($users) {
    $header = $req->getHeaders();
    $http_auth_user = $header['PHP_AUTH_USER'][0];
    return $res->withJson([
      'greeting' => 'Hello, ' . $users[$http_auth_user]['display_name']
    ]);
  });

  // Run the app.
  $app->run();
  