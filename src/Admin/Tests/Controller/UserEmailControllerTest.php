<?php

namespace Admin\Tests\Controller;

use Admin\Entity\ApiKey;
use App\Entity\EmailAddress;
use App\Entity\Group;
use App\Entity\Property;
use App\Entity\User;
use App\Entity\UserProperty;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\UuidGenerator;
use FOS\RestBundle\Util\Codes;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class UserEmailControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        static::bootKernel();
        unlink(self::$kernel->getRootDir().'/test_db.sqlite');
        $cliApp = new Application(static::$kernel);
        $cliApp->setAutoExit(false);
        if($cliApp->run(new StringInput('doctrine:migrations:migrate --no-interaction'), new NullOutput()))
            throw new \RuntimeException('Could not run the migrations.');

        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em EntityManager */

        $users = array();
        /* @var $users User[] */

        for($i=0;$i<0xf;$i++) {
            $user = new User();
            $users[] = $user;
            $user->setGuid('00000000-0000-0000-0000-'.str_pad((string)$i, 12, '0', STR_PAD_LEFT));
            $user->setUsername('user_'.$i);
            $user->setDisplayName('User '.$i);
            $user->setEnabled(true);
            $user->setPasswordEnabled(0);
            $user->setRole('ROLE_USER');
            $user->addEmailAddress(new EmailAddress());
            $user->getPrimaryEmailAddress()->setEmail($i.'@example.invalid');
            $user->getPrimaryEmailAddress()->setUser($user);
            if($i%0x2 == 0)
                $user->getPrimaryEmailAddress()->setVerified(true);
            if($i%0x4) {
                $e = new EmailAddress();
                $user->addEmailAddress($e);
                $e->setUser($user);
                $e->setEmail($i.'@sec.invalid');
                if($i%0x8 == 0)
                    $e->setVerified(true);
            }
            $em->persist($user);
        }

        for($i=0;$i<25;$i++) {
            $e = new EmailAddress();
            $users[3]->addEmailAddress($e);
            $e->setUser($users[3]);
            $e->setEmail($i.'@xxyyzz.invalid');
            $em->persist($e);
        }


        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setScopes(array('r_profile_email', 'w_profile_email'));
        $em->persist($apiKey);
        $em->flush();

        copy(self::$kernel->getRootDir().'/test_db.sqlite', self::$kernel->getRootDir().'/test_db.sqlite.sav');
    }


    public function setUp()
    {
        static::bootKernel();
        copy(self::$kernel->getRootDir().'/test_db.sqlite.sav', self::$kernel->getRootDir().'/test_db.sqlite');
        $this->em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $apiKey = $this->em->find('AdminBundle:ApiKey', 1);
        $this->client = self::createClient(array(), array(
            'PHP_AUTH_USER' => '-apikey-'.$apiKey->getId(),
            'PHP_AUTH_PW' => $apiKey->getSecret(),
            'HTTP_ACCEPT' => 'application/json',
        ));
    }

    
    public function testCget()
    {
        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000/emails');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(1, $data->total);
        $this->assertArrayNotHasKey('_links', (array)$data);

        $this->assertEquals('0@example.invalid', $data->items[0]->addr);
        $this->assertTrue($data->items[0]->verified);
        $this->assertTrue($data->items[0]->primary);
        $this->assertRegExp('#/admin/users/00000000-0000-0000-0000-000000000000/emails/\\d+#', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000001/emails');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(2, $data->total);
        $this->assertArrayNotHasKey('_links', (array)$data);

        $this->assertEquals('1@example.invalid', $data->items[0]->addr);
        $this->assertFalse($data->items[0]->verified);
        $this->assertTrue($data->items[0]->primary);
        $this->assertRegExp('#/admin/users/00000000-0000-0000-0000-000000000001/emails/\\d+#', $data->items[0]->_links->self->href);

        $this->assertEquals('1@sec.invalid', $data->items[1]->addr);
        $this->assertFalse($data->items[1]->verified);
        $this->assertFalse($data->items[1]->primary);
        $this->assertRegExp('#/admin/users/00000000-0000-0000-0000-000000000001/emails/\\d+#', $data->items[1]->_links->self->href);


        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000003/emails');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(27, $data->total);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000003/emails?page=2', $data->_links->next->href);


        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000003/emails?page=2');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(2, $data->page);
        $this->assertEquals(27, $data->total);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000003/emails?page=1', $data->_links->prev->href);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000003/emails?page=3', $data->_links->next->href);


        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000003/emails?page=3');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(7, $data->items);
        $this->assertEquals(3, $data->page);
        $this->assertEquals(27, $data->total);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000003/emails?page=2', $data->_links->prev->href);
        $this->assertArrayNotHasKey('next', (array)$data->_links);

    }

    public function testPost()
    {
        $this->client->request('POST', '/admin/users/00000000-0000-0000-0000-000000000000/emails', array(), array(), array(), 'x@y.invalid');
        $this->assertEquals(Codes::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('#http://localhost/admin/users/00000000-0000-0000-0000-000000000000/emails/\\d+#', $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('x@y.invalid', $data->addr);
        $this->assertFalse($data->verified);
        $this->assertFalse($data->primary);
        $this->assertEquals('00000000-0000-0000-0000-000000000000', $data->user->guid);

        $this->client->request('POST', '/admin/users/00000000-0000-0000-0000-000000000000/emails', array(), array(), array(), 'x@!');
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testGet()
    {
        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000/emails');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $emailUrl = json_decode($this->client->getResponse()->getContent())->items[0]->_links->self->href;

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('0@example.invalid', $data->addr);
        $this->assertTrue($data->verified);
        $this->assertTrue($data->primary);
        $this->assertEquals($emailUrl, $data->_links->self->href);
        $this->assertEquals('user_0', $data->user->username);
        $this->assertEquals('User 0', $data->user->display_name);
        $this->assertEquals('00000000-0000-0000-0000-000000000000', $data->user->guid);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000000', $data->user->_links->self->href);

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000/emails/999');
        $this->assertEquals(Codes::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000009/emails/1');
        $this->assertEquals(Codes::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    private function createNewEmail()
    {
        $this->client->request('POST', '/admin/users/00000000-0000-0000-0000-000000000000/emails', array(), array(), array(), mt_rand().'@y.invalid');
        $this->assertEquals(Codes::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        return $this->client->getResponse()->headers->get('Location');
    }

    public function testPatchVerify()
    {
        $emailUrl = $this->createNewEmail();

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertFalse($data->verified);

        $this->client->request('PATCH', $emailUrl.'/verify');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($data->verified);
    }

    public function testPostVerify()
    {
        $emailUrl = $this->createNewEmail();
        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertFalse($data->verified);

        $this->client->enableProfiler();

        $this->client->request('POST', $emailUrl.'/verify');
        $this->assertEquals(Codes::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

        /* @var $mailCollector MessageDataCollector */

        $this->assertEquals(1, $mailCollector->getMessageCount());

        $message = $mailCollector->getMessages()[0];
        /* @var $message \Swift_Message */
        $this->assertRegExp('/^[0-9]+@y.invalid$/',key($message->getTo()));
        $this->assertContains('/pub/email/verify/', $message->getBody());

        // Locate the verification url
        if(!preg_match('#(/pub/email/verify/\\d+/[0-9a-z]+)#', $message->getBody(), $matches))
            $this->fail('Failed to locate the verification link');
        $verifyLink = $matches[1];

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertFalse($data->verified);

        // Touch the verification url
        $this->client->request('GET', $verifyLink);
        $this->assertFalse($this->client->getResponse()->isServerError());
        $this->assertFalse($this->client->getResponse()->isClientError());

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($data->verified);

        $this->client->request('POST', $emailUrl.'/verify');
        $this->assertEquals(Codes::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    public function testPatchPrimary()
    {
        $emailUrl = $this->createNewEmail();

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertFalse($data->verified);
        $this->assertFalse($data->primary);

        $this->client->request('PATCH', $emailUrl.'/primary');
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertFalse($data->verified);
        $this->assertFalse($data->primary);

        $this->client->request('PATCH', $emailUrl.'/verify');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('PATCH', $emailUrl.'/primary');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($data->verified);
        $this->assertTrue($data->primary);

        // Test switching primary email address
        $emailUrl2 = $this->createNewEmail();

        $this->client->request('PATCH', $emailUrl2.'/verify');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('PATCH', $emailUrl2.'/primary');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $emailUrl2);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($data->verified);
        $this->assertTrue($data->primary);

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($data->verified);
        $this->assertFalse($data->primary);
    }

    public function testDelete()
    {
        $emailUrl = $this->createNewEmail();

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->client->request('DELETE', $emailUrl);
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        // Test deleting when primary
        $emailUrl = $this->createNewEmail();

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->client->request('PATCH', $emailUrl.'/verify');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('PATCH', $emailUrl.'/primary');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('DELETE', $emailUrl);
        $this->assertEquals(Codes::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $emailUrl);
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
