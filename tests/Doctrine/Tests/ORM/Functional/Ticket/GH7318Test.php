<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\EntityPersisterMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\OrmTestCase;

final class GH7318Test extends OrmTestCase
{
    /**
     * SUT
     *
     * @var UnitOfWorkMock
     */
    private $_unitOfWork;

    /**
     * The EntityManager mock that provides the mock persisters
     *
     * @var EntityManagerMock
     */
    private $_emMock;

    protected function setUp()
    {
        parent::setUp();
        $connectionMock = new ConnectionMock([], new DriverMock());
        $eventManager = $this->getMockBuilder(EventManager::class)->getMock();
        $this->_emMock = EntityManagerMock::create($connectionMock, null, $eventManager);
        $this->_unitOfWork = new UnitOfWorkMock($this->_emMock);
        $this->_emMock->setUnitOfWork($this->_unitOfWork);
    }

    /**
     * This test covers the bug where computing entity change set of an already managed object with an auto-generated id
     * stored incorrect original entity data, which was missing that id.
     */
    public function testComputeEntityChangesetPreservesOriginalIdOnSubsequentCalls()
    {
        // Setup fake persister and id generator for identity generation
        $persister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata(GH7318Entity::class));
        $this->_unitOfWork->setEntityPersister(GH7318Entity::class, $persister);
        $persister->setMockIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);

        // Create and persist new object
        $object = new GH7318Entity();
        $object->data = 'foo';
        $this->_unitOfWork->persist($object);
        // this will call UnitOfWork::computeChangeSet() internally
        $this->_unitOfWork->commit();
        // should have an id
        $this->assertInternalType('numeric', $object->id);

        // preserve original entity data
        $originalEntityData = $this->_unitOfWork->getOriginalEntityData($object);

        // make identical change to the object and preserved original data
        $object->data = 'bar';
        $originalEntityData['data'] = 'bar';

        // Flush object again
        // this will call UnitOfWork::computeChangeSet() internally
        $this->_unitOfWork->commit();

        $newOriginalEntityData = $this->_unitOfWork->getOriginalEntityData($object);

        // $newOriginalEntityData after second persisting should match the original one
        $this->assertSame($originalEntityData, $newOriginalEntityData, 'Original entity data of a managed entity doesn\'t match expected value.');
    }

    /**
     * This test covers the bug where re-computing entity change set of an already managed object
     * with an auto-generated id stored incorrect original entity data, which was missing that id.
     * In practice it caused issues when calling `recomputeSingleEntityChangeSet` in an onFlush event listener.
     */
    public function testRecomputeEntityChangesetPreservesOriginalIdOnSubsequentCalls()
    {
        // Setup fake persister and id generator for identity generation
        $persister = new EntityPersisterMock($this->_emMock, $this->_emMock->getClassMetadata(GH7318Entity::class));
        $this->_unitOfWork->setEntityPersister(GH7318Entity::class, $persister);
        $persister->setMockIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);

        // Create and persist new object
        $object = new GH7318Entity();
        $object->data = 'foo';
        $this->_unitOfWork->persist($object);
        // this will call UnitOfWork::computeChangeSet() internally
        $this->_unitOfWork->commit();
        // should have an id
        $this->assertInternalType('numeric', $object->id);

        // preserve original entity data
        $originalEntityData = $this->_unitOfWork->getOriginalEntityData($object);

        // make identical change to the object and preserved original data
        $object->data = 'bar';
        $originalEntityData['data'] = 'bar';

        // recompute change set
        $metadata = $this->_emMock->getClassMetadata(GH7318Entity::class);
        $this->_unitOfWork->recomputeSingleEntityChangeSet($metadata, $object);
        $newOriginalEntityData = $this->_unitOfWork->getOriginalEntityData($object);

        // $newOriginalEntityData after second persisting should match the original one
        $this->assertSame($originalEntityData, $newOriginalEntityData, 'Original entity data of a managed entity doesn\'t match expected value.');
    }
}

/**
 * @Entity
 */
class GH7318Entity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @Column(type="string", length=50)
     * @var string
     */
    public $data;
}