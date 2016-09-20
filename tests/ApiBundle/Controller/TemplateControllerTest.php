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
use AppBundle\Entity\EmailAddress;
use AppBundle\Entity\EmailTemplate;
use AppBundle\Entity\LocalizedEmailTemplate;
use AppBundle\Entity\User;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;

class TemplateControllerTest extends WebTestCase
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

        $accessibleTemplate = new EmailTemplate();
        $accessibleTemplate->setName('accessible_template');
        self::forceEntityId($accessibleTemplate, 1);
        $em->persist((new LocalizedEmailTemplate($accessibleTemplate))
            ->setLocale('en')
            ->setSubject('Accessible template subject')
            ->setBody('Accessible template body {{ var }}'));
        $em->persist($accessibleTemplate);
        $em->flush();
        self::addApiUserAcl($accessibleTemplate, MaskBuilder::MASK_VIEW|MaskBuilder::MASK_USE|MaskBuilder::MASK_EDIT|MaskBuilder::MASK_DELETE);

        $inaccessibleTemplate = new EmailTemplate();
        $inaccessibleTemplate->setName('inaccessible_template');
        self::forceEntityId($inaccessibleTemplate, 2);
        $em->persist((new LocalizedEmailTemplate($inaccessibleTemplate))
            ->setLocale('en')
            ->setSubject('Inaccessible template subject')
            ->setBody('Inaccessible template body'));
        $em->persist($inaccessibleTemplate);
        $em->flush();

        $accessibleEmailAddress = new EmailAddress();
        $accessibleEmailAddress->setEmail('test@example.com');
        $accessibleEmailAddress->setName('Test');
        $accessibleEmailAddress->confirmAuthCode($accessibleEmailAddress->getAuthCode());
        self::forceEntityId($accessibleEmailAddress, 1);
        $em->persist($accessibleEmailAddress);
        $em->flush();

        self::addApiUserAcl($accessibleEmailAddress, MaskBuilder::MASK_VIEW|MaskBuilder::MASK_USE);

        $inaccessibleEmailAddress = new EmailAddress();
        $inaccessibleEmailAddress->setEmail('inaccessible@example.com');
        $inaccessibleEmailAddress->setName('Inaccessible');
        $inaccessibleEmailAddress->confirmAuthCode($inaccessibleEmailAddress->getAuthCode());
        self::forceEntityId($inaccessibleEmailAddress, 2);
        $em->persist($inaccessibleEmailAddress);
        $em->flush();

        $emailLinkedTemplate = new EmailTemplate();
        $emailLinkedTemplate->setSender($inaccessibleEmailAddress);
        $emailLinkedTemplate->setName('email_linked');
        self::forceEntityId($emailLinkedTemplate, 3);
        $em->persist((new LocalizedEmailTemplate($emailLinkedTemplate))
            ->setLocale('en')
            ->setSubject('Email linked subject')
            ->setBody('Email linked body'));
        $em->persist($emailLinkedTemplate);
        $em->flush();
        self::addApiUserAcl($emailLinkedTemplate, MaskBuilder::MASK_VIEW|MaskBuilder::MASK_USE|MaskBuilder::MASK_EDIT);
    }

    public function testCget()
    {
        $this->client->request('GET', '/api/templates');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());


        self::assertJsonStringEqualsJsonString(json_encode([
            'page' => 1,
            'items' => [
                [
                    'name' => 'accessible_template',
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/templates/1'],
                        'translations' => ['href' => 'http://localhost/api/templates/1/translations'],
                        'send' => ['href' => 'http://localhost/api/templates/1/messages']
                    ]
                ],
                [
                    'name' => 'email_linked',
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/templates/3'],
                        'translations' => ['href' => 'http://localhost/api/templates/3/translations'],
                        'send' => ['href' => 'http://localhost/api/templates/3/messages']
                    ]
                ]
            ],
            'total' => 2
        ]), $this->client->getResponse()->getContent());
    }

    public function testGet()
    {
        $this->client->request('GET', '/api/templates');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $templates = json_decode($this->client->getResponse()->getContent())->items;

        $accessibleTemplate = array_values(array_filter($templates, function($template) {
            return $template->name === 'accessible_template';
        }))[0];

        $this->client->request('GET', $accessibleTemplate->_links->self->href);

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'name' => 'accessible_template',
            'translations' => [
                [
                    'locale' => 'en',
                    'subject' => 'Accessible template subject',
                    'body' => 'Accessible template body {{ var }}',
                    '_links' => [
                        'self' => [
                            'href' => 'http://localhost/api/templates/1/translations/en',
                        ]
                    ]
                ]
            ],
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/1'],
                'translations' => ['href' => 'http://localhost/api/templates/1/translations'],
                'send' => ['href' => 'http://localhost/api/templates/1/messages']
            ]
        ]), $this->client->getResponse()->getContent());
    }

    public function testGetEmailLinked()
    {
        $this->client->request('GET', '/api/templates/3');

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'name' => 'email_linked',
            'sender' => [
                'name' => 'Inaccessible',
                'email' => 'inaccessible@example.com',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/2']
                ]
            ],
            'translations' => [
                [
                    'locale' => 'en',
                    'subject' => 'Email linked subject',
                    'body' => 'Email linked body',
                    '_links' => [
                        'self' => [
                            'href' => 'http://localhost/api/templates/3/translations/en',
                        ]
                    ]
                ]
            ],
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/3'],
                'translations' => ['href' => 'http://localhost/api/templates/3/translations'],
                'send' => ['href' => 'http://localhost/api/templates/3/messages']
            ]

        ]), $this->client->getResponse()->getContent());
    }

    public function testGetInaccessible()
    {
        $this->client->request('GET', '/api/templates/2');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        self::assertObjectNotHasAttribute('name', json_decode($this->client->getResponse()->getContent()));
    }

    public function testPostWithoutTranslations()
    {
        $this->client->request('POST', '/api/templates', [], [], [],
            json_encode([
                'name' => 'new_template',
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPostWithTranslations()
    {
        $this->client->request('POST', '/api/templates', [], [], [],
            json_encode([
                'name' => 'new_template',
                'translations' => [
                    [
                        'locale' => 'en',
                        'subject' => 'New template subject',
                        'body' => 'New template body'
                    ],
                    [
                        'locale' => 'nl',
                        'subject' => 'Nieuwe template onderwerp',
                        'body' => 'Nieuwe template body'
                    ]
                ]
            ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'name' => 'new_template',
            'translations' => [
                [
                    'locale' => 'en',
                    'subject' => 'New template subject',
                    'body' => 'New template body',
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/templates/4/translations/en' ]
                    ]
                ],
                [
                    'locale' => 'nl',
                    'subject' => 'Nieuwe template onderwerp',
                    'body' => 'Nieuwe template body',
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/templates/4/translations/nl' ]
                    ]
                ]
            ],
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/4'],
                'translations' => ['href' => 'http://localhost/api/templates/4/translations'],
                'send' => ['href' => 'http://localhost/api/templates/4/messages']
            ]

        ]), $this->client->getResponse()->getContent());

    }

    public function testPostWithSender()
    {
        $this->client->request('POST', '/api/templates', [], [], [],
            json_encode([
                'name' => 'new_template',
                'sender' => [
                    'email' => 'test@example.com'
                ],
                'translations' => [
                    [
                        'locale' => 'en',
                        'subject' => 'New template subject',
                        'body' => 'New template body'
                    ],
                    [
                        'locale' => 'nl',
                        'subject' => 'Nieuwe template onderwerp',
                        'body' => 'Nieuwe template body'
                    ]
                ]
            ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'name' => 'new_template',
            'sender' => [
                'name' => 'Test',
                'email' => 'test@example.com',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/1']
                ]
            ],
            'translations' => [
                [
                    'locale' => 'en',
                    'subject' => 'New template subject',
                    'body' => 'New template body',
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/templates/4/translations/en' ]
                    ]
                ],
                [
                    'locale' => 'nl',
                    'subject' => 'Nieuwe template onderwerp',
                    'body' => 'Nieuwe template body',
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/templates/4/translations/nl' ]
                    ]
                ]
            ],
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/4'],
                'translations' => ['href' => 'http://localhost/api/templates/4/translations'],
                'send' => ['href' => 'http://localhost/api/templates/4/messages']
            ]

        ]), $this->client->getResponse()->getContent());
    }

    public function testPostWithInaccessibleSender()
    {
        $this->client->request('POST', '/api/templates', [], [], [],
            json_encode([
                'name' => 'new_template',
                'sender' => [
                    'email' => 'inaccessible@example.com'
                ],
                'translations' => [
                    [
                        'locale' => 'en',
                        'subject' => 'New template subject',
                        'body' => 'New template body'
                    ],
                    [
                        'locale' => 'nl',
                        'subject' => 'Nieuwe template onderwerp',
                        'body' => 'Nieuwe template body'
                    ]
                ]
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPostWithNonExistingSender()
    {
        $this->client->request('POST', '/api/templates', [], [], [],
            json_encode([
                'name' => 'new_template',
                'sender' => [
                    'email' => 'non-existing@example.com'
                ],
                'translations' => [
                    [
                        'locale' => 'en',
                        'subject' => 'New template subject',
                        'body' => 'New template body'
                    ],
                    [
                        'locale' => 'nl',
                        'subject' => 'Nieuwe template onderwerp',
                        'body' => 'Nieuwe template body'
                    ]
                ]
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $this->client->request('DELETE', '/api/templates/1');
        self::assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('GET', '/api/templates/1');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteInaccessible()
    {
        $this->client->request('DELETE', '/api/templates/2');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/templates/2');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('DELETE', '/api/templates/3');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/templates/3');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }


    public function testPostMessage()
    {
        $this->client->request('POST', '/api/templates/1/messages', [], [], [],
        json_encode([
            'sender' => [
                'email' => 'test@example.com'
            ],
            'priority' => 3,
            'template-data' => [
                'var' => 'abcdef',
            ]
        ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $messageUrl = $this->client->getResponse()->headers->get('Location');

        $this->client->request('GET', $messageUrl);

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'sender' => [
                'name' => 'Test',
                'email' => 'test@example.com',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/1']
                ]
            ],
            'template' => [
                'name' => 'accessible_template',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/templates/1'],
                    'translations' => ['href' => 'http://localhost/api/templates/1/translations'],
                ]
            ],
            'priority' => 3,
            '_links' => [
                'self' => ['href' => 'http://localhost/api/messages/1'],
                'recipients' => ['href' => 'http://localhost/api/messages/1/recipients'],
            ]
        ]), $this->client->getResponse()->getContent());

    }

    public function testPostMessagePredefinedSender()
    {
        $this->client->request('POST', '/api/templates/3/messages', [], [], [],
            json_encode([
                'template-data' => [
                    'var' => 'abcdef',
                ]
            ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $messageUrl = $this->client->getResponse()->headers->get('Location');

        $this->client->request('GET', $messageUrl);

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'sender' => [
                'name' => 'Inaccessible',
                'email' => 'inaccessible@example.com',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/senders/2']
                ]
            ],
            'template' => [
                'name' => 'email_linked',
                '_links' => [
                    'self' => ['href' => 'http://localhost/api/templates/3'],
                    'translations' => ['href' => 'http://localhost/api/templates/3/translations'],
                ]
            ],
            'priority' => 1,
            '_links' => [
                'self' => ['href' => 'http://localhost/api/messages/1'],
                'recipients' => ['href' => 'http://localhost/api/messages/1/recipients'],
            ]
        ]), $this->client->getResponse()->getContent());
    }

    public function testPostMessageInaccessibleSender()
    {
        $this->client->request('POST', '/api/templates/1/messages', [], [], [],
        json_encode([
            'sender' => [
                'email' => 'inaccessible@example.com'
            ],
            'template-data' => [
                'var' => 'abcdef',
            ]
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPostMessageNonExistingSender()
    {

        $this->client->request('POST', '/api/templates/1/messages', [], [], [],
        json_encode([
            'sender' => [
                'email' => 'non-existing@example.com'
            ],
            'template-data' => [
                'var' => 'abcdef',
            ]
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testPostMessageInaccessibleTemplate()
    {

        $this->client->request('POST', '/api/templates/2/messages', [], [], [],
        json_encode([
            'sender' => [
                'email' => 'accessible@example.com'
            ],
            'template-data' => [
                'var' => 'abcdef',
            ]
        ]));

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPostMessageNoSender()
    {
        $this->client->request('POST', '/api/templates/1/messages', [], [], [],
        json_encode([
            'template-data' => [
                'var' => 'abcdef',
            ]
        ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
}
