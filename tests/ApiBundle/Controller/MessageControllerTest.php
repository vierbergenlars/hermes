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

namespace ApiBundle\Tests\Controller;

use ApiBundle\Tests\WebTestCase;
use AppBundle\Entity\Email\Message;
use AppBundle\Entity\EmailAddress;
use AppBundle\Entity\EmailTemplate;
use AppBundle\Entity\LocalizedEmailTemplate;
use AppBundle\Entity\User;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;

class MessageControllerTest extends WebTestCase
{
    protected static function setUpDatabase(EntityManagerInterface $em)
    {
        $user = new User();
        $user->setUsername('admin');
        $user->setGroups(['%sysops']);
        $user->setRealname('admin');
        $user->setAuthId('00000000-0000-0000-0000-000000000000');
        $em->persist($user);

        self::$kernel->getContainer()
            ->get('security.token_storage')
            ->setToken(new PreAuthenticatedToken($user, null, 'main', ['ROLE_GROUP_%SYSOPS']));

        $accessibleEmailAddress = new EmailAddress();
        $accessibleEmailAddress->setEmail('test@example.com');
        $accessibleEmailAddress->setName('Test');
        $accessibleEmailAddress->confirmAuthCode($accessibleEmailAddress->getAuthCode());
        self::forceEntityId($accessibleEmailAddress, 1);
        $em->persist($accessibleEmailAddress);
        $em->flush();

        self::addApiUserAcl($accessibleEmailAddress, MaskBuilder::MASK_VIEW | MaskBuilder::MASK_USE);

        $inaccessibleEmailAddress = new EmailAddress();
        $inaccessibleEmailAddress->setEmail('inaccessible@example.com');
        $inaccessibleEmailAddress->setName('Inaccessible');
        $inaccessibleEmailAddress->confirmAuthCode($inaccessibleEmailAddress->getAuthCode());
        self::forceEntityId($inaccessibleEmailAddress, 2);
        $em->persist($inaccessibleEmailAddress);
        $em->flush();

        $accessibleMessage = new Message();
        $accessibleMessage->setSender($inaccessibleEmailAddress);
        $accessibleMessage->setTemplate(new EmailTemplate());
        $accessibleMessage->getTemplate()->setName('__inline__' . md5(mt_rand()));
        $accessibleMessage->setScheduledSendTime(new \DateTime('@1234567890', new \DateTimeZone('UTC')));
        self::forceEntityId($accessibleMessage, 1);
        self::forceEntityId($accessibleMessage->getTemplate(), 1);
        $em->persist($accessibleMessage);
        $em->flush();
        $em->persist((new LocalizedEmailTemplate($accessibleMessage->getTemplate()))
            ->setLocale('en')
            ->setSubject('Inline template subject')
            ->setBody('Inline template body'));
        $em->flush();

        self::addApiUserAcl($accessibleMessage, MaskBuilder::MASK_VIEW | MaskBuilder::MASK_EDIT | MaskBuilder::MASK_DELETE);

        $inaccessibleMessage = new Message();
        $inaccessibleMessage->setSender($inaccessibleEmailAddress);
        $inaccessibleMessage->setTemplate(new EmailTemplate());
        $inaccessibleMessage->getTemplate()->setName('__inline__' . md5(mt_rand()));
        self::forceEntityId($inaccessibleMessage, 2);
        self::forceEntityId($inaccessibleMessage->getTemplate(), 2);
        $em->persist($inaccessibleMessage);
        $em->flush();
        $em->persist((new LocalizedEmailTemplate($inaccessibleMessage->getTemplate()))
            ->setLocale('en')
            ->setSubject('Inline template subject')
            ->setBody('Inline template body'));
        $em->flush();

        $unscheduledMessage = new Message();
        $unscheduledMessage->setSender($inaccessibleEmailAddress);
        $unscheduledMessage->setTemplate(new EmailTemplate());
        $unscheduledMessage->getTemplate()->setName('__inline__' . md5(mt_rand()));
        self::forceEntityId($unscheduledMessage, 3);
        self::forceEntityId($unscheduledMessage->getTemplate(), 3);
        $em->persist($unscheduledMessage);
        $em->flush();
        $em->persist((new LocalizedEmailTemplate($unscheduledMessage->getTemplate()))
            ->setLocale('en')
            ->setSubject('Inline template subject')
            ->setBody('Inline template body'));
        $em->flush();

        self::addApiUserAcl($unscheduledMessage, MaskBuilder::MASK_VIEW | MaskBuilder::MASK_EDIT | MaskBuilder::MASK_DELETE);
    }

    public function testGet()
    {
        $this->client->request('GET', '/api/messages/1');

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'sender'        => [
                'name'   => 'Inaccessible',
                'email'  => 'inaccessible@example.com',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/2'],
                ],
            ],
            'template'      => [
                '_links' => [
                    'self'         => ['href' => 'http://localhost/api/templates/1'],
                    'translations' => ['href' => 'http://localhost/api/templates/1/translations'],
                ],
            ],
            'template-data' => [],
            'priority'      => 1,
            'send-at'       => '2009-02-13T23:31:30+0000',
            '_links'        => [
                'self'       => ['href' => 'http://localhost/api/messages/1'],
                'recipients' => ['href' => 'http://localhost/api/messages/1/recipients'],
            ],
        ]), $this->client->getResponse()->getContent());
    }

    public function testGetInaccessible()
    {
        $this->client->request('GET', '/api/messages/2');

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPost()
    {
        $this->client->request('POST', '/api/messages', [], [], [],
            json_encode([
                'sender'        => [
                    'email' => 'test@example.com',
                ],
                'template'      => [
                    'translations' => [
                        [
                            'locale'  => 'en',
                            'subject' => 'Inline subject',
                            'body'    => 'Inline body',
                        ],
                    ],
                ],
                'template-data' => [
                    'var' => 'ABC',
                ],
                'priority'      => 4,
                'send-at'       => '2016-09-16T12:39:33+0000',
            ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'sender'        => [
                'email'  => 'test@example.com',
                'name'   => 'Test',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/1'],
                ],
            ],
            'template'      => [
                '_links' => [
                    'self'         => ['href' => 'http://localhost/api/templates/4'],
                    'translations' => ['href' => 'http://localhost/api/templates/4/translations'],
                ],
            ],
            'template-data' => [
                'var' => 'ABC',
            ],
            'priority'      => 4,
            'send-at'       => '2016-09-16T12:39:33+0000',
            '_links'        => [
                'self'       => ['href' => 'http://localhost/api/messages/4'],
                'recipients' => ['href' => 'http://localhost/api/messages/4/recipients'],
            ],
        ]), $this->client->getResponse()->getContent());

        $this->client->request('GET', json_decode($this->client->getResponse()->getContent())->template->_links->self->href);

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'translations' => [
                [
                    'locale'  => 'en',
                    'subject' => 'Inline subject',
                    '_links'  => [
                        'self' => ['href' => 'http://localhost/api/templates/4/translations/en'],
                    ],
                ],
            ],
            '_links'       => [
                'self'         => ['href' => 'http://localhost/api/templates/4'],
                'translations' => ['href' => 'http://localhost/api/templates/4/translations'],
                'send'         => ['href' => 'http://localhost/api/templates/4/messages'],
            ],

        ]), $this->client->getResponse()->getContent());


        $this->client->request('GET', json_decode($this->client->getResponse()->getContent())->translations[0]->_links->self->href);

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'locale'  => 'en',
            'subject' => 'Inline subject',
            'body'    => 'Inline body',
            '_links'  => [
                'self' => ['href' => 'http://localhost/api/templates/4/translations/en'],
            ],
        ]), $this->client->getResponse()->getContent());


    }

    public function testPostWithoutOptionalFields()
    {
        $this->client->request('POST', '/api/messages', [], [], [],
            json_encode([
                'sender'        => [
                    'email' => 'test@example.com',
                ],
                'template'      => [
                    'translations' => [
                        [
                            'locale'  => 'en',
                            'subject' => 'Inline subject',
                            'body'    => 'Inline body',
                        ],
                    ],
                ],
            ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'sender'        => [
                'email'  => 'test@example.com',
                'name'   => 'Test',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/1'],
                ],
            ],
            'template'      => [
                '_links' => [
                    'self'         => ['href' => 'http://localhost/api/templates/4'],
                    'translations' => ['href' => 'http://localhost/api/templates/4/translations'],
                ],
            ],
            'priority'      => 1,
            '_links'        => [
                'self'       => ['href' => 'http://localhost/api/messages/4'],
                'recipients' => ['href' => 'http://localhost/api/messages/4/recipients'],
            ],
        ]), $this->client->getResponse()->getContent());

        $this->client->request('GET', json_decode($this->client->getResponse()->getContent())->template->_links->self->href);

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'translations' => [
                [
                    'locale'  => 'en',
                    'subject' => 'Inline subject',
                    '_links'  => [
                        'self' => ['href' => 'http://localhost/api/templates/4/translations/en'],
                    ],
                ],
            ],
            '_links'       => [
                'self'         => ['href' => 'http://localhost/api/templates/4'],
                'translations' => ['href' => 'http://localhost/api/templates/4/translations'],
                'send'         => ['href' => 'http://localhost/api/templates/4/messages'],
            ],

        ]), $this->client->getResponse()->getContent());

        $this->client->request('GET', json_decode($this->client->getResponse()->getContent())->translations[0]->_links->self->href);

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'locale'  => 'en',
            'subject' => 'Inline subject',
            'body'    => 'Inline body',
            '_links'  => [
                'self' => ['href' => 'http://localhost/api/templates/4/translations/en'],
            ],
        ]), $this->client->getResponse()->getContent());
    }

    public function testPostWithInaccessibleSender()
    {
        $this->client->request('POST', '/api/messages', [], [], [],
            json_encode([
                'sender'        => [
                    'email' => 'inaccessible@example.com',
                ],
                'template'      => [
                    'translations' => [
                        [
                            'locale'  => 'en',
                            'subject' => 'Inline subject',
                            'body'    => 'Inline body',
                        ],
                    ],
                ],
                'template-data' => [
                    'var' => 'ABC',
                ],
                'priority'      => 4,
                'send-at'       => '2016-09-16T12:39:33+0000',
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPostWithNonExistingSender()
    {
        $this->client->request('POST', '/api/messages', [], [], [],
            json_encode([
                'sender'        => [
                    'email' => 'non-existing@example.com',
                ],
                'template'      => [
                    'translations' => [
                        [
                            'locale'  => 'en',
                            'subject' => 'Inline subject',
                            'body'    => 'Inline body',
                        ],
                    ],
                ],
                'template-data' => [
                    'var' => 'ABC',
                ],
                'priority'      => 4,
                'send-at'       => '2016-09-16T12:39:33+0000',
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPostWithoutSender()
    {
        $this->client->request('POST', '/api/messages', [], [], [],
            json_encode([
                'template'      => [
                    'translations' => [
                        [
                            'locale'  => 'en',
                            'subject' => 'Inline subject',
                            'body'    => 'Inline body',
                        ],
                    ],
                ],
                'template-data' => [
                    'var' => 'ABC',
                ],
                'priority'      => 4,
                'send-at'       => '2016-09-16T12:39:33+0000',
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPostWithoutTemplate()
    {
        $this->client->request('POST', '/api/messages', [], [], [],
            json_encode([
                'sender'        => [
                    'email' => 'test@example.com',
                ],
                'template-data' => [
                    'var' => 'ABC',
                ],
                'priority'      => 4,
                'send-at'       => '2016-09-16T12:39:33+0000',
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPostWithoutTranslations()
    {
        $this->client->request('POST', '/api/messages', [], [], [],
            json_encode([
                'sender'        => [
                    'email' => 'test@example.com',
                ],
                'template'      => [
                ],
                'template-data' => [
                    'var' => 'ABC',
                ],
                'priority'      => 4,
                'send-at'       => '2016-09-16T12:39:33+0000',
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPatch()
    {
        $this->client->request('PATCH', '/api/messages/3', [], [], [],
            json_encode([
                'send-at' => '2016-09-19T17:06:30+0000',
                'priority' => 2,
            ]));

        self::assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/messages/3');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'sender'        => [
                'name'   => 'Inaccessible',
                'email'  => 'inaccessible@example.com',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/2'],
                ],
            ],
            'template'      => [
                '_links' => [
                    'self'         => ['href' => 'http://localhost/api/templates/3'],
                    'translations' => ['href' => 'http://localhost/api/templates/3/translations'],
                ],
            ],
            'template-data' => [],
            'priority'      => 2,
            'send-at' => '2016-09-19T17:06:30+0000',
            '_links'        => [
                'self'       => ['href' => 'http://localhost/api/messages/3'],
                'recipients' => ['href' => 'http://localhost/api/messages/3/recipients'],
            ],
        ]), $this->client->getResponse()->getContent());
    }

    public function testPatchPartially()
    {
        $this->client->request('PATCH', '/api/messages/3', [], [], [],
            json_encode([
                'priority' => 2,
            ]));

        self::assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/messages/3');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'sender'        => [
                'name'   => 'Inaccessible',
                'email'  => 'inaccessible@example.com',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/2'],
                ],
            ],
            'template'      => [
                '_links' => [
                    'self'         => ['href' => 'http://localhost/api/templates/3'],
                    'translations' => ['href' => 'http://localhost/api/templates/3/translations'],
                ],
            ],
            'template-data' => [],
            'priority'      => 2,
            '_links'        => [
                'self'       => ['href' => 'http://localhost/api/messages/3'],
                'recipients' => ['href' => 'http://localhost/api/messages/3/recipients'],
            ],
        ]), $this->client->getResponse()->getContent());

        $this->client->request('PATCH', '/api/messages/3', [], [], [],
            json_encode([
                'send-at' => '2016-09-19T17:06:30+0000',
            ]));

        self::assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/messages/3');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'sender'        => [
                'name'   => 'Inaccessible',
                'email'  => 'inaccessible@example.com',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/2'],
                ],
            ],
            'template'      => [
                '_links' => [
                    'self'         => ['href' => 'http://localhost/api/templates/3'],
                    'translations' => ['href' => 'http://localhost/api/templates/3/translations'],
                ],
            ],
            'template-data' => [],
            'priority'      => 2,
            'send-at' => '2016-09-19T17:06:30+0000',
            '_links'        => [
                'self'       => ['href' => 'http://localhost/api/messages/3'],
                'recipients' => ['href' => 'http://localhost/api/messages/3/recipients'],
            ],
        ]), $this->client->getResponse()->getContent());
    }

    public function testPatchInaccessible()
    {
        $this->client->request('PATCH', '/api/messages/2', [], [], [],
            json_encode([
                'send-at' => '2016-09-19T17:06:30+0000',
                'priority' => 2,
            ]));

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPatchScheduled()
    {
        $this->client->request('PATCH', '/api/messages/1', [], [], [],
            json_encode([
                'send-at' => '2016-09-19T17:06:30+0000',
                'priority' => 2,
            ]));

        self::assertEquals(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $this->client->request('DELETE', '/api/messages/1');
        self::assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/messages/1');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteInaccessible()
    {
        $this->client->request('DELETE', '/api/messages/2');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/messages/2');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
