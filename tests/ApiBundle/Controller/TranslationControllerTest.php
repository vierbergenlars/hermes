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

class TranslationControllerTest extends WebTestCase
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
        $em->persist((new LocalizedEmailTemplate($accessibleTemplate))
            ->setLocale('nl')
            ->setSubject('Toegankelijke template onderwerp')
            ->setBody('Toegankelijke template body'));
        $em->persist($accessibleTemplate);
        $em->flush();
        self::addApiUserAcl($accessibleTemplate, MaskBuilder::MASK_VIEW|MaskBuilder::MASK_USE|MaskBuilder::MASK_EDIT);

        $inaccessibleTemplate = new EmailTemplate();
        $inaccessibleTemplate->setName('inaccessible_template');
        self::forceEntityId($inaccessibleTemplate, 2);
        $em->persist((new LocalizedEmailTemplate($inaccessibleTemplate))
            ->setLocale('en')
            ->setSubject('Inaccessible template subject')
            ->setBody('Inaccessible template body'));
        $em->persist($inaccessibleTemplate);
        $em->flush();

        $readOnlyTemplate = new EmailTemplate();
        $readOnlyTemplate->setName('read_only');
        self::forceEntityId($readOnlyTemplate, 3);
        $em->persist((new LocalizedEmailTemplate($readOnlyTemplate))
            ->setLocale('en')
            ->setSubject('Read only subject')
            ->setBody('Read only body'));
        $em->persist($readOnlyTemplate);
        $em->flush();
        self::addApiUserAcl($readOnlyTemplate, MaskBuilder::MASK_VIEW|MaskBuilder::MASK_USE);
    }

    public function testGet()
    {
        $this->client->request('GET', '/api/templates/1/translations/en');

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'locale' => 'en',
            'body' => 'Accessible template body {{ var }}',
            'subject' => 'Accessible template subject',
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/1/translations/en']
            ]
        ]), $this->client->getResponse()->getContent());

        $this->client->request('GET', '/api/templates/1/translations/nl');

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'locale' => 'nl',
            'body' => 'Toegankelijke template body',
            'subject' => 'Toegankelijke template onderwerp',
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/1/translations/nl']
            ]
        ]), $this->client->getResponse()->getContent());
    }

    public function testGetInaccessible()
    {
        $this->client->request('GET', '/api/templates/2/translations/en');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testGetNonExisting()
    {
        $this->client->request('GET', '/api/templates/1/translations/fr');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/templates/5/translations/en');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testPost()
    {
        $this->client->request('POST', '/api/templates/1/translations', [], [], [],
            json_encode([
                'locale' => 'fr',
                'subject' => 'Sujet modèle accessible',
                'body' => 'Corps modèle accessible'
            ]));

        self::assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        self::assertEquals('http://localhost/api/templates/1/translations/fr', $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'locale' => 'fr',
            'subject' => 'Sujet modèle accessible',
            'body' => 'Corps modèle accessible',
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/1/translations/fr']
            ]
        ]), $this->client->getResponse()->getContent());
    }

    public function testPostExistingTranslation()
    {
        $this->client->request('POST', '/api/templates/1/translations', [], [], [],
            json_encode([
                'locale' => 'en',
                'subject' => 'Accessible template subject',
                'body' => 'Accessible template body'
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

    }

    public function testPostInaccessibleTemplate()
    {
        $this->client->request('POST', '/api/templates/2/translations', [], [], [],
            json_encode([
                'locale' => 'fr',
                'subject' => 'Sujet modèle accessible',
                'body' => 'Corps modèle accessible'
            ]));

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/templates/3/translations', [], [], [],
            json_encode([
                'locale' => 'fr',
                'subject' => 'Sujet modèle accessible',
                'body' => 'Corps modèle accessible'
            ]));

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPut()
    {
        $this->client->request('PUT', '/api/templates/1/translations/en', [], [], [],
            json_encode([
                'subject' => 'New subject',
                'body' => 'New body',
            ]));

        self::assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/templates/1/translations/en');

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertJsonStringEqualsJsonString(json_encode([
            'locale' => 'en',
            'subject' => 'New subject',
            'body' => 'New body',
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/1/translations/en']
            ]
        ]), $this->client->getResponse()->getContent());
    }

    public function testPutChangeLocale()
    {
        $this->client->request('PUT', '/api/templates/1/translations/en', [], [], [],
            json_encode([
                'locale' => 'fr',
                'subject' => 'New subject',
                'body' => 'New body',
            ]));

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/templates/1/translations/en');

        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        self::assertJsonStringEqualsJsonString(json_encode([
            'locale' => 'en',
            'body' => 'Accessible template body {{ var }}',
            'subject' => 'Accessible template subject',
            '_links' => [
                'self' => ['href' => 'http://localhost/api/templates/1/translations/en']
            ]
        ]), $this->client->getResponse()->getContent());

    }

    public function testPutInaccessibleTemplate()
    {
        $this->client->request('PUT', '/api/templates/2/translations/en', [], [], [],
            json_encode([
                'locale' => 'en',
                'body' => 'Accessible template body {{ var }}',
                'subject' => 'Accessible template subject',
            ]));

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('PUT', '/api/templates/3/translations/en', [], [], [],
            json_encode([
                'locale' => 'fr',
                'subject' => 'Sujet modèle accessible',
                'body' => 'Corps modèle accessible'
            ]));

        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $this->client->request('DELETE', '/api/templates/1/translations/en');
        self::assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/templates/1/translations/en');
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }


    public function testDeleteInaccessible()
    {
        $this->client->request('DELETE', '/api/templates/2/translations/en');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/templates/2/translations/en');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());


        $this->client->request('DELETE', '/api/templates/3/translations/en');
        self::assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/templates/3/translations/en');
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

}
