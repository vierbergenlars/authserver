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

namespace AuthRequestBundle\Tests\Controller;

use App\Entity\EmailAddress;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\UuidGenerator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
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
            $group->setDisplayName('DisplayName '.$i);
            $group->setNoGroups(false);
            $group->setExportable($i%0x2 == 0);
            $group->setNoUsers(false);
            $group->setUserJoinable(false);
            $group->setUserLeaveable(false);
            $em->persist($group);
        }

        $groups[1]->addGroup($groups[0]);
        $groups[2]->addGroup($groups[3]);
        $groups[3]->addGroup($groups[4]);


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
            $user->getPrimaryEmailAddress()->setEmail($i.'@example.invalid');
            $user->getPrimaryEmailAddress()->setUser($user);
            if($i%0x5 == 0)
                $user->getPrimaryEmailAddress()->setVerified(true);
            if($i%0x7) {
                $e = new EmailAddress();
                $user->addEmailAddress($e);
                $e->setUser($user);
                $e->setEmail($i.'@sec.invalid');
                if($i%0xb)
                    $e->setVerified(true);
            }
            $em->persist($user);
        }

        $users[10]->addGroup($groups[1]);
        $users[20]->addGroup($groups[2])
            ->addGroup($groups[6])
            ->addGroup($groups[8]);

        $em->flush();

        copy(self::$kernel->getRootDir().'/test_db.sqlite', self::$kernel->getRootDir().'/test_db.sqlite.sav');
    }

    public function setUp()
    {
        static::bootKernel();
        copy(self::$kernel->getRootDir().'/test_db.sqlite.sav', self::$kernel->getRootDir().'/test_db.sqlite');
    }

    private function request($method, $url, $server)
    {
        $client = self::createClient();
        $client->request($method, $url, [], [], $server);
        return $client->getResponse();
    }

    public function testBasicAuthentication()
    {
        // Disabled password
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->request('GET', '/api/auth_request/basic', [
            'PHP_AUTH_USER' => 'user_0',
            'PHP_AUTH_PW' => 'password_0'
        ])->getStatusCode());

        // Locked user account
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->request('GET', '/api/auth_request/basic', [
            'PHP_AUTH_USER' => 'user_6',
            'PHP_AUTH_PW' => 'password_6'
        ])->getStatusCode());

        // Non-verified primary email address
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->request('GET', '/api/auth_request/basic', [
            'PHP_AUTH_USER' => 'user_8',
            'PHP_AUTH_PW' => 'password_8'
        ])->getStatusCode());

        // Bad password
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->request('GET', '/api/auth_request/basic', [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'invalid'
        ])->getStatusCode());

        // Correct password
        $this->assertEquals(Response::HTTP_OK, $this->request('GET', '/api/auth_request/basic', [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());
    }

    public function testBasicAuthenticationGroups() {
        // User not member of group
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?groups[]=group_10', [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of group, but group not exportable
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?groups[]=group_1', [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of one group, but not the other
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?groups[]=group_1&groups[]=group_10', [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of both group, but one not exportable
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?groups[]=group_0&groups[]=group_1', [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of group, and group is exportable
        $this->assertEquals(Response::HTTP_OK, $this->request('GET', '/api/auth_request/basic?groups[]=group_0', [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of both groups, and both exportable
        $this->assertEquals(Response::HTTP_OK, $this->request('GET', '/api/auth_request/basic?groups[]=group_2&groups[]=group_4', [
            'PHP_AUTH_USER' => 'user_20',
            'PHP_AUTH_PW' => 'password_20'
        ])->getStatusCode());
    }

    public function testBasicAuthenticationEval()
    {
        // User not member of group
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("has_group('group_10')"), [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of group, but group not exportable
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("has_group('group_1')"), [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of one group, but not the other
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("has_group('group_1') and has_group('group_10')"), [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of both group, but one not exportable
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("has_group('group_0') and has_group('group_1')"), [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of group, and group is exportable
        $this->assertEquals(Response::HTTP_OK, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("has_group('group_0')"), [
            'PHP_AUTH_USER' => 'user_10',
            'PHP_AUTH_PW' => 'password_10'
        ])->getStatusCode());

        // User member of both groups, and both exportable
        $this->assertEquals(Response::HTTP_OK, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("has_group('group_2') and has_group('group_4')"), [
            'PHP_AUTH_USER' => 'user_20',
            'PHP_AUTH_PW' => 'password_20'
        ])->getStatusCode());

        // Username check
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("user.getUsername() == 'user_10'"), [
            'PHP_AUTH_USER' => 'user_20',
            'PHP_AUTH_PW' => 'password_20'
        ])->getStatusCode());

        // Username check
        $this->assertEquals(Response::HTTP_OK, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("user.getUsername() == 'user_20'"), [
            'PHP_AUTH_USER' => 'user_20',
            'PHP_AUTH_PW' => 'password_20'
        ])->getStatusCode());

        // Broken expression (syntax error)
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("user.getUsername() == 'user_20"), [
            'PHP_AUTH_USER' => 'user_20',
            'PHP_AUTH_PW' => 'password_20'
        ])->getStatusCode());

        // Broken expression (other error)
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->request('GET', '/api/auth_request/basic?eval='.urlencode("user.badMethod()"), [
            'PHP_AUTH_USER' => 'user_20',
            'PHP_AUTH_PW' => 'password_20'
        ])->getStatusCode());
    }
}
