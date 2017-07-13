<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Created by PhpStorm.
 * User: lars
 * Date: 10/07/17
 * Time: 10:36
 */

namespace EmailRulesBundle\Tests;

use App\Entity\EmailAddress;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\UuidGenerator;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;

class EmailRulesTest extends WebTestCase
{
    public static function setUpBeforeClass()
    {
        static::bootKernel();
        @unlink(self::$kernel->getRootDir().'/test_db.sqlite');
        $cliApp = new Application(static::$kernel);
        $cliApp->setAutoExit(false);
        if($cliApp->run(new StringInput('doctrine:migrations:migrate --no-interaction'), new NullOutput()))
            throw new \RuntimeException('Could not run the migrations.');

        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em EntityManager */

        $groups = array();
        /* @var $groups Group[] */
        for($i=0;$i<0x10;$i++) {
            $group = new Group();
            $groups[] = $group;
            $group->setName('group_'.$i);
            $group->setDisplayName('Group '.$i);
            $group->setNoGroups(false);
            $group->setExportable($i%0x2 == 0);
            $group->setNoUsers(false);
            $group->setUserJoinable(false);
            $group->setUserLeaveable(false);
            $em->persist($group);
        }

        $users = array();
        /* @var $users User[] */
        $guidGenerator = new UuidGenerator();
        $encoderFactory = self::$kernel->getContainer()->get('security.encoder_factory');
        $encoder = $encoderFactory->getEncoder(User::class);

        for($i=0;$i<0x20;$i++) {
            $user = new User();
            $users[] = $user;
            $user->setGuid('00000000-0000-0000-0000-'.str_pad((string)$i, 12, '0', STR_PAD_LEFT));
            $user->setUsername('user_'.$i);
            $user->setDisplayName('User '.$i);
            $user->setEnabled($i%0x2 == 0);
            $user->setRole('ROLE_USER');
            $user->setPasswordEnabled($i%0x3 == 0?0:1);
            $user->setPassword($encoder->encodePassword('password_'.$i, $user->getSalt()));
            $user->addEmailAddress(new EmailAddress());
            $user->getPrimaryEmailAddress()->setEmail($i.'@example.org');
            $user->getPrimaryEmailAddress()->setUser($user);
            if($i%0x5 == 0)
                $user->getPrimaryEmailAddress()->setVerified(true);
            $em->persist($user);
        }

        $users[20]->setRole('ROLE_ADMIN');

        $em->flush();

        copy(self::$kernel->getRootDir().'/test_db.sqlite', self::$kernel->getRootDir().'/test_db.sqlite.sav');
    }

    public function setUp()
    {
        static::bootKernel();
        copy(self::$kernel->getRootDir().'/test_db.sqlite.sav', self::$kernel->getRootDir().'/test_db.sqlite');
    }

    /**
     * Adds a new email address to a user
     *
     * @param Client $client
     * @param int $userNum
     * @param string $emailAddress
     * @return MessageDataCollector
     */
    private function addEmailAddress($client, $userNum, $emailAddress)
    {
        $client->followRedirects();

        // Log in
        $loginCrawler = $client->request('GET', '/usr/profile');
        $loginForm = $loginCrawler->filter('.btn.btn-primary')->form();

        // Profile
        $profileCrawler = $client->submit($loginForm, ['_username' => 'user_'.$userNum, '_password'=>'password_'.$userNum]);
        $addEmailAddressForm = $profileCrawler->filter('#add_email_address_form form')->form();

        // Add email address
        $client->enableProfiler();
        $client->followRedirects(false);
        $client->submit($addEmailAddressForm, ['email_address[email]' => $emailAddress]);

        return $client->getProfile()->getCollector('swiftmailer');
    }


    /**
     * Verifies the email address
     * @param Client $client
     * @param MessageDataCollector $swiftMailerProfiler
     */
    private function verifyEmailAddress($client, $swiftMailerProfiler)
    {
        $this->assertEquals(1, $swiftMailerProfiler->getMessageCount());
        $message = $swiftMailerProfiler->getMessages()[0];
        /* @var $message \Swift_Message */
        $verifyUrl = current(preg_grep('@http://localhost/pub/email/verify/\d+/[a-z0-9]+$@', explode("\n", $message->getBody())));
        $this->assertNotNull($verifyUrl);


        // Then visit the email address validation URL
        $emailVerifyCrawler = $client->request('GET', $verifyUrl);
        $this->assertContains('email address has been verified', $emailVerifyCrawler->filter('.panel-success .panel-body')->text());
    }

    /**
     * Fetches groups present on the profile
     * @param Client $client
     * @return array
     */
    private function getProfileGroups($client)
    {
        // Check profile groups
        $profileCrawler = $client->request('GET', '/usr/profile');
        $groups = array_map(function($group) {
            return trim($group);
        }, explode("\n", trim($profileCrawler
                ->filter('.list-groups')
                ->text()))
        );

        sort($groups);
        return $groups;
    }

    /**
     * Fetches role present on the profile
     * @param Client $client
     * @return string|null
     */
    private function getProfileRole($client)
    {
        // Check profile groups
        $profileCrawler = $client->request('GET', '/usr/profile');
        $dlElements = $profileCrawler->filter('dl.dl-horizontal');

        if(trim($dlElements->filter('dt')->last()->text()) !== 'Permissions')
            return null;

        return trim($dlElements->filter('dd')->last()->text());
    }

    public function testAddEmailAddressNormal()
    {
        $client = self::createClient();

        $swiftmailerProfiler = $this->addEmailAddress($client, '10','abc@example.org');

        // And continue to profile
        $addedCrawler = $client->followRedirect();
        $this->assertContains('verification email', $addedCrawler
            ->filter('.alert-success')
            ->eq(0)
            ->text()
        );

        $this->assertEquals(['(None)'], $this->getProfileGroups($client));

        $this->verifyEmailAddress($client, $swiftmailerProfiler);

        $this->assertEquals(['(None)'], $this->getProfileGroups($client));
    }


    public function testAddEmailAddressAddGroups()
    {
        $client = self::createClient();

        $swiftMailerProfiler = $this->addEmailAddress($client, '10','abc@example.com');

        // And continue to profile
        $addedCrawler = $client->followRedirect();
        $this->assertContains('verification email', $addedCrawler
            ->filter('.alert-success')
            ->eq(0)
            ->text()
        );
        $this->assertEquals(['(None)'], $this->getProfileGroups($client));

        $this->verifyEmailAddress($client, $swiftMailerProfiler);

        $this->assertEquals(['Group 1', 'Group 3'], $this->getProfileGroups($client));
    }

    public function testAddEmailAddressAddRole()
    {
        $client = self::createClient();

        $swiftMailerProfiler = $this->addEmailAddress($client, '10','abc@example.be');

        // And continue to profile
        $addedCrawler = $client->followRedirect();
        $this->assertContains('verification email', $addedCrawler
            ->filter('.alert-success')
            ->eq(0)
            ->text()
        );
        $this->assertEquals(['(None)'], $this->getProfileGroups($client));
        $this->assertNull($this->getProfileRole($client));

        $this->verifyEmailAddress($client, $swiftMailerProfiler);

        $this->assertEquals(['Group 1'], $this->getProfileGroups($client));
        $this->assertEquals('ROLE_AUDIT', $this->getProfileRole($client));
    }

    public function testAddEmailAddressAddRoleNoDowngrade()
    {
        $client = self::createClient();

        $swiftMailerProfiler = $this->addEmailAddress($client, '20','abc@example.be');

        // And continue to profile
        $addedCrawler = $client->followRedirect();
        $this->assertContains('verification email', $addedCrawler
            ->filter('.alert-success')
            ->eq(0)
            ->text()
        );
        $this->assertEquals(['(None)'], $this->getProfileGroups($client));
        $this->assertEquals('Admin', $this->getProfileRole($client));

        $this->verifyEmailAddress($client, $swiftMailerProfiler);

        $this->assertEquals(['Group 1'], $this->getProfileGroups($client));
        $this->assertEquals('Admin', $this->getProfileRole($client));
    }

    public function testAddEmailAddressReject()
    {
        $client = self::createClient();

        $swiftMailerProfiler = $this->addEmailAddress($client, '10', 'abc@xyz.be');

        $this->assertEquals(0, $swiftMailerProfiler->getMessageCount());

        // And continue to profile
        $addedCrawler = $client->followRedirect();

        $this->assertContains('rejected', $addedCrawler
            ->filter('.alert-danger')
            ->eq(0)
            ->text()
        );
    }

}
