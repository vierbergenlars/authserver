<?php
/*
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2018 Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Admin\EventListener\Audit;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Admin\AuditEvents;
use Admin\Event\Audit\PropertyDetailsEvent;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Admin\Event\Audit\ActionEvent;
use Admin\Event\Audit\TargetDetailsEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Admin\Event\Audit\ClassEventInterface;
use Psr\Log\LoggerInterface;

class AuditEventListener implements EventSubscriberInterface
{

    /**
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var string
     */
    private $class;

    /**
     *
     * @var string[]
     */
    private $templatedProperties;

    public static function getSubscribedEvents()
    {
        return [
            AuditEvents::PROPERTY_DETAILS => [
                'onPropertyDetails',
                -512
            ],
            AuditEvents::TARGET_DETAILS => [
                'onTargetDetails',
                -512
            ]
        ];
    }

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, $class, array $templatedProperties)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->class = $class;
        $this->templatedProperties = $templatedProperties;
    }

    protected function getClass()
    {
        return $this->class;
    }

    protected function getTemplatedProperties()
    {
        return $this->templatedProperties;
    }

    private function isApplicable(ClassEventInterface $event)
    {
        return is_a($event->getClass(), $this->getClass(), true);
    }

    public function onPropertyDetails(PropertyDetailsEvent $event)
    {
        if (!$this->isApplicable($event)) {
            $this->logger->debug('Event does not apply: event is not for {class}', [
                'event' => $event,
                'class' => $this->getClass()
            ]);
            return;
        }
        if (!in_array($event->getProperty(), $this->getTemplatedProperties(), true)) {
            $this->logger->debug('Event does not apply: property is not templated specially', [
                'event' => $event,
                'class' => $this->getClass()
            ]);
            return;
        }
        $this->logger->debug('Event applies: adding template for {class}#{property}', [
            'class' => $event->getClass(),
            'property' => $event->getProperty()
        ]);
        $event->setTemplate(new TemplateReference('AdminBundle', 'Audit', 'property/' . str_replace('\\', '.', $event->getClass()) . '/' . $event->getProperty(), 'html', 'twig'));
    }

    public function onTargetDetails(TargetDetailsEvent $event)
    {
        if (!$this->isApplicable($event)) {
            $this->logger->debug('Event does not apply: event is not for {class}', [
                'event' => $event,
                'class' => $this->getClass()
            ]);
            return;
        }

        if ($event->getId() === null) {
            $this->logger->debug('Event does not apply: ID is null', [
                'event' => $event,
                'class' => $this->getClass()
            ]);
            return;
        }

        $this->logger->debug('Event applies, looking up {class}@{id}', [
            'class' => $event->getClass(),
            'id' => $event->getId()
        ]);

        try {
            $item = $this->em->find($event->getClass(), $event->getId());
            $event->setTemplate(new TemplateReference('AdminBundle', 'Audit', 'target/' . str_replace('\\', '.', $event->getClass()), 'html', 'twig'), [
                'item' => $item
            ]);
        } catch (\Doctrine\Common\Persistence\Mapping\MappingException $ex) {
            $this->logger->error('Mapping error for ' . $event->getClass() . ', event does not apply.', [
                'class' => $event->getClass(),
                'exception' => $ex
            ]);
        }
    }
}