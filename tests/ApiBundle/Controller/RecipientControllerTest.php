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
use AppBundle\Entity\Email\AuthserverRecipient;
use AppBundle\Entity\Email\GroupRecipient;
use AppBundle\Entity\Email\Message;
use AppBundle\Entity\Email\StandardRecipient;
use AppBundle\Entity\EmailAddress;
use AppBundle\Entity\EmailTemplate;
use AppBundle\Entity\LocalizedEmailTemplate;
use AppBundle\Entity\User;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use vierbergenlars\Authserver\Client\Model\Group;

class RecipientControllerTest extends WebTestCase
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

        $accessibleMessage = new Message();
        $accessibleMessage->setSender($accessibleEmailAddress);
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

        $em->persist((new StandardRecipient($accessibleMessage))
            ->setEmailaddress('recipient@example.com'));
        $em->flush();
        $em->persist((new StandardRecipient($accessibleMessage))
            ->setEmailaddress('recipient2@example.com')
            ->setName('Recipient 2'));
        $em->flush();
        $em->persist((new AuthserverRecipient($accessibleMessage))
            ->setUserId('00000000-0000-0000-0000-000000000000'));
        $em->flush();
        $em->persist($groupRecipient = (new GroupRecipient($accessibleMessage))
            ->setGroupName('%sysops'));
        $em->flush();
        $em->persist((new AuthserverRecipient($accessibleMessage))
            ->setUserId('00000000-0000-0000-0000-000000000001')
            ->setOriginatingRecipient($groupRecipient));



        $inaccessibleMessage = new Message();
        $inaccessibleMessage->setSender($accessibleEmailAddress);
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
        $em->persist((new GroupRecipient($inaccessibleMessage))
            ->setGroupName('%sysops'));
        $em->flush();
    }

    public function testCget()
    {
        $this->client->request('GET', '/api/messages/1/recipients');

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'page' => 1,
            'items' => [
                [
                    'type' => 'standard',
                    'email' => 'recipient@example.com',
                    'is-queued' => false,
                    'is-failed' => false,
                    'is-sent' => false,
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/messages/1/recipients/1'],
                        'children' => ['href' => 'http://localhost/api/messages/1/recipients/1/children']
                    ]
                ],
                [
                    'type' => 'standard',
                    'email' => 'recipient2@example.com',
                    'name' => 'Recipient 2',
                    'is-queued' => false,
                    'is-failed' => false,
                    'is-sent' => false,
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/messages/1/recipients/2'],
                        'children' => ['href' => 'http://localhost/api/messages/1/recipients/2/children']
                    ]
                ],
                [
                    'type' => 'authserver',
                    'user-guid' => '00000000-0000-0000-0000-000000000000',
                    'is-queued' => false,
                    'is-failed' => false,
                    'is-sent' => false,
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/messages/1/recipients/3'],
                        'children' => ['href' => 'http://localhost/api/messages/1/recipients/3/children']
                    ]
                ],
                [
                    'type' => 'group',
                    'group-name' => '%sysops',
                    'is-queued' => false,
                    'is-failed' => false,
                    'is-sent' => false,
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/messages/1/recipients/4'],
                        'children' => ['href' => 'http://localhost/api/messages/1/recipients/4/children']
                    ]
                ]
            ],
            'total' => 4,
        ]), $this->client->getResponse()->getContent());
    }

    public function testCgetInaccessible()
    {
        $this->client->request('GET', '/api/messages/2/recipients');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testGet()
    {
        $this->client->request('GET', '/api/messages/1/recipients/1');

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(json_encode([
            'type' => 'standard',
            'email' => 'recipient@example.com',
            'is-queued' => false,
            'is-failed' => false,
            'is-sent' => false,
            '_links' => [
                'self' => ['href' => 'http://localhost/api/messages/1/recipients/1'],
                'children' => ['href' => 'http://localhost/api/messages/1/recipients/1/children']
            ]
        ]), $this->client->getResponse()->getContent());
    }

    public function testGetInaccessible()
    {
        $this->client->request('GET', '/api/messages/2/recipients/6');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testGetMismatchedRecipient()
    {
        $this->client->request('GET', '/api/messages/1/recipients/6');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testChildren()
    {
        $this->client->request('GET', '/api/messages/1/recipients/4/children');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'page' => 1,
            'items' => [
                [
                    'type' => 'authserver',
                    'user-guid' => '00000000-0000-0000-0000-000000000001',
                    'is-queued' => false,
                    'is-failed' => false,
                    'is-sent' => false,
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/messages/1/recipients/5'],
                        'children' => ['href' => 'http://localhost/api/messages/1/recipients/5/children']
                    ]
                ],
            ],
            'total' => 1,
        ]), $this->client->getResponse()->getContent());
    }

    public function testChildrenInaccessible()
    {
        $this->client->request('GET', '/api/messages/2/recipients/6/children');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testChildrenMismatchedRecipient()
    {
        $this->client->request('GET', '/api/messages/1/recipients/6/children');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $this->client->request('DELETE', '/api/messages/1/recipients/3');
        self::assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/messages/1/recipients/3');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteInaccessible()
    {
        $this->client->request('DELETE', '/api/messages/2/recipients/6');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/messages/2/recipients/6');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteMismatchedRecipient()
    {
        $this->client->request('DELETE', '/api/messages/1/recipients/6');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testPost()
    {
        $this->client->request('POST', '/api/messages/1/recipients', [], [], [], json_encode([
            'type' => 'standard',
            'email' => 'test@example.com',
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertEquals($this->client->getResponse()->headers->get('Location'), 'http://localhost/api/messages/1/recipients/7');

        $this->client->request('GET', '/api/messages/1/recipients/7');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'type' => 'standard',
            'email' => 'test@example.com',
            'is-queued' => false,
            'is-failed' => false,
            'is-sent' => false,
            '_links' => [
                'self' => ['href' => 'http://localhost/api/messages/1/recipients/7'],
                'children' => ['href' => 'http://localhost/api/messages/1/recipients/7/children']
            ]
        ]), $this->client->getResponse()->getContent());


        $this->client->request('POST', '/api/messages/1/recipients', [], [], [], json_encode([
            'type' => 'group',
            'group-name' => 'testers',
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertEquals($this->client->getResponse()->headers->get('Location'), 'http://localhost/api/messages/1/recipients/8');

        $this->client->request('GET', '/api/messages/1/recipients/8');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'type' => 'group',
            'group-name' => 'testers',
            'is-queued' => false,
            'is-failed' => false,
            'is-sent' => false,
            '_links' => [
                'self' => ['href' => 'http://localhost/api/messages/1/recipients/8'],
                'children' => ['href' => 'http://localhost/api/messages/1/recipients/8/children']
            ]
        ]), $this->client->getResponse()->getContent());
    }

    public function testPostInaccessible()
    {
        $this->client->request('POST', '/api/messages/2/recipients', [], [], [], json_encode([
            'type' => 'standard',
            'email' => 'test@example.com',
        ]));

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
