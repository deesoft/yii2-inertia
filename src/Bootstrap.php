<?php

namespace dee\inertia;

use Yii;
use yii\base\BootstrapInterface;
use yii\web\Application;
use yii\web\Cookie;
use yii\web\Response;

/**
 * Description of Bootstrap
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap($app)
    {
        if ($app instanceof Application) {
            $app->on(Application::EVENT_BEFORE_REQUEST, function () {
                $request = Yii::$app->getRequest();
                $headers = $request->headers;
                if ($headers->has(Header::INERTIA)) {
                    Yii::$app->set('errorHandler', ['class' => ErrorHandler::class]);
                }
                if (!$headers->has($request->csrfHeader) && $headers->has(Header::AXIOS_CSRF_HEADER) && !in_array($request->method, $request->csrfTokenSafeMethods, true)) {
                    $value = $headers->get(Header::AXIOS_CSRF_HEADER);
                    $data = Yii::$app->getSecurity()->validateData($value, $request->cookieValidationKey);
                    if ($data !== false) {
                        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70000) {
                            $data = @unserialize($data, ['allowed_classes' => false]);
                        } else {
                            $data = @unserialize($data);
                        }
                        if (is_array($data) && isset($data[0], $data[1]) && $data[0] === Header::AXIOS_CSRF_PARAM) {
                            $headers->add($request->csrfHeader, $data[1]);
                        }
                    }
                }

                if ($headers->has(Header::VERSION) && $request->method == 'GET') {
                    $version = $headers->get(Header::VERSION, null, true);
                    if ($version != Inertia::getVersion()) {
                        $response = Yii::$app->getResponse();
                        $response->setStatusCode(409);
                        $response->headers->set(Header::LOCATION, $request->getAbsoluteUrl());
                        Yii::$app->end();
                    }
                }
            });
            $app->getResponse()->on(Response::EVENT_BEFORE_SEND, function () {
                $request = Yii::$app->getRequest();
                $response = Yii::$app->getResponse();

                $response->cookies->add(new Cookie([
                        'name' => Header::AXIOS_CSRF_PARAM,
                        'value' => $request->getCsrfToken(),
                        'httpOnly' => false,
                ]));
                if ($request->headers->has(Header::INERTIA)) {
                    if ($response->headers->has(Header::REDIRECT)) {
                        $url = $response->headers->get(Header::REDIRECT, null, true);
                        $response->headers->set('Location', $url);
                        $response->setStatusCode(302);
                    }
                    if ($response->getStatusCode() == 302) {
                        if (in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'])) {
                            $response->setStatusCode(303);
                        }
                    }
                }
            });
        }
    }
}
