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
use AppBundle\Entity\ApiUser;
use AppBundle\Entity\EmailAddress;
use AppBundle\Entity\EmailTemplate;
use AppBundle\Entity\LocalizedEmailTemplate;
use AppBundle\Entity\User;
use AppBundle\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;

class SenderControllerTest extends WebTestCase
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

        self::addApiUserAcl($accessibleEmailAddress, MaskBuilder::MASK_VIEW|MaskBuilder::MASK_USE);

        $inaccessibleEmailAddress = new EmailAddress();
        $inaccessibleEmailAddress->setEmail('inaccessible@example.com');
        $inaccessibleEmailAddress->setName('Inaccessible');
        $inaccessibleEmailAddress->confirmAuthCode($inaccessibleEmailAddress->getAuthCode());
        self::forceEntityId($inaccessibleEmailAddress, 2);
        $em->persist($inaccessibleEmailAddress);
        $em->flush();
    }

    public function testCget()
    {
        $this->client->request('GET', '/api/senders');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'page' => 1,
            'items' => [
                [
                    'name' => 'Test',
                    'email' => 'test@example.com',
                    '_links' => [
                        'self' => ['href' => 'http://localhost/api/senders/1']
                    ]
                ]
            ],
            'total' => 1,
        ]), $this->client->getResponse()->getContent());
    }

    public function testGet()
    {
        $this->client->request('GET', '/api/senders/1');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'name' => 'Test',
            'email' => 'test@example.com',
            '_links' => [
                'self' => ['href' => 'http://localhost/api/senders/1']
            ]
        ]), $this->client->getResponse()->getContent());
    }

    public function testGetInaccessible()
    {
        $this->client->request('GET', '/api/senders/2');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
