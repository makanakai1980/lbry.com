<?php

# Enable PHP dev cli-server
if (php_sapi_name() === 'cli-server' && is_file(__DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI'])))
{
  return false;
}

include __DIR__ . '/../bootstrap.php';

define('IS_PRODUCTION', $_SERVER['SERVER_NAME'] == 'lbry.io');

ini_set('display_errors', IS_PRODUCTION ? 'off' : 'on');
error_reporting(IS_PRODUCTION ? 0 : (E_ALL | E_STRICT));

try
{
  i18n::register();
  Session::init();
  if (!IS_PRODUCTION)
  {
    View::compileCss();
  }
  Controller::dispatch(strtok($_SERVER['REQUEST_URI'], '?'));
}
catch(Throwable $e)
{
  if (IS_PRODUCTION)
  {
    $slackErrorNotificationUrl = Config::get('slack_error_notification_url');
    if ($slackErrorNotificationUrl)
    {
      Curl::post($slackErrorNotificationUrl, ['text' => '<!everyone> ' . $_SERVER['REQUEST_URI'] . "\n" . $e->__toString()], ['json_data' => true]);
    }
    throw $e;
  }

  http_response_code(500);
  echo '<pre>'.Debug::exceptionToString($e).'</pre>';
}