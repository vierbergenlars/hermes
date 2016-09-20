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

namespace ApiBundle\Tests;

use AppBundle\Entity\ApiUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

abstract class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Client
     */
    protected $client;

    public static function setUpBeforeClass()
    {
        static::bootKernel();
        @unlink(self::$kernel->getRootDir().'/test_db.sqlite');
        $cliApp = new Application(static::$kernel);
        $cliApp->setAutoExit(false);
        $output = new BufferedOutput();
        if($cliApp->run(new StringInput('doctrine:migrations:migrate --no-interaction'), $output))
            throw new \RuntimeException('Could not run the migrations: '."\n".$output->fetch());

        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');

        $apiUser = new ApiUser();
        $apiUser->setUsername('test');

        $em->persist($apiUser);
        $em->flush();
        static::setUpDatabase($em);
        $em->flush();

        copy(self::$kernel->getRootDir().'/test_db.sqlite', self::$kernel->getRootDir().'/test_db.sqlite.sav');
        self::ensureKernelShutdown();
    }

    protected static function setUpDatabase(EntityManagerInterface $em)
    {
        throw new \RuntimeException(__METHOD__.' must be overridden.');
    }

    public function setUp()
    {
        static::bootKernel();
        copy(self::$kernel->getRootDir().'/test_db.sqlite.sav', self::$kernel->getRootDir().'/test_db.sqlite');
        $this->em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');

        $apiUser = $this->em->getRepository(ApiUser::class)->findOneBy(['username' => 'test']);
        if(!$apiUser)
            throw new NoResultException('Cannot find test API user');
        /* @var $apiUser ApiUser */

        $this->client = self::createClient([], [
            'PHP_AUTH_USER' => $apiUser->getUsername(),
            'PHP_AUTH_PW' => $apiUser->getPassword(),
            'HTTP_ACCEPT' => 'application/json',
        ]);
    }

    public static function tearDownAfterClass()
    {
        static::bootKernel();
        @unlink(self::$kernel->getRootDir().'/test_db.sqlite');
        @unlink(self::$kernel->getRootDir().'/test_db.sqlite.sav');
        static::ensureKernelShutdown();
    }

    protected static function addApiUserAcl($object, $mask)
    {
        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $apiUserSid = UserSecurityIdentity::fromAccount($em->getRepository(ApiUser::class)->findOneBy(['username'=>'test']));
        $aclProvider = self::$kernel->getContainer()->get('security.acl.provider');
        /* @var $aclProvider \Symfony\Component\Security\Acl\Model\MutableAclProviderInterface */
        $acl = $aclProvider->findAcl(ObjectIdentity::fromDomainObject($object));
        /* @var $acl \Symfony\Component\Security\Acl\Model\MutableAclInterface */
        $acl->insertObjectAce($apiUserSid, $mask);
        $aclProvider->updateAcl($acl);
    }

    protected static function forceEntityId($object, $id)
    {
        $refl = new \ReflectionProperty(get_class($object), 'id');
        $refl->setAccessible(true);
        $refl->setValue($object, $id);
        $refl->setAccessible(false);
    }
}
