<?php
/**
 * Hermes, an HTTP-based templated mail sender for transactional and mass mailing.
 *
 * Copyright (C) 2016  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ApiBundle\EventListener;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\Exception\ValidationFailedException;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * ValidationExceptionListener constructor.
     *
     * @param ViewHandlerInterface $viewHandler
     */
    public function __construct(ViewHandlerInterface $viewHandler)
    {
        $this->viewHandler = $viewHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onException', 0],
        ];
    }

    public function onException(GetResponseForExceptionEvent $event)
    {
        if($event->hasResponse())
            return;
        if(!in_array($event->getRequest()->getRequestFormat(), ['xml', 'json']))
            return;
        $exception = $event->getException();
        $code = 500;
        if($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        } elseif($exception instanceof ValidationFailedException) {
            $code = 400;
        }

        $view = View::create($exception, $code);
        $event->setResponse($this->viewHandler->handle($view));
        $event->stopPropagation();
    }
}
