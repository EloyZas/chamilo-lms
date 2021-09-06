<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ResourceNodeRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        /** @var ResourceNodeRepository $repo */
        $repo = self::getContainer()->get(ResourceNodeRepository::class);

        $repoType = $em->getRepository(ResourceType::class);
        $user = $this->createUser('julio');

        $defaultCount = $repo->count([]);

        $type = $repoType->findOneBy(['name' => 'illustrations']);

        $node = (new ResourceNode())
            ->setContent('test')
            ->setTitle('test')
            ->setSlug('test')
            ->setResourceType($type)
            ->setCreator($user)
            ->setParent($user->getResourceNode())
        ;
        $this->assertHasNoEntityViolations($node);

        $em->persist($node);
        $em->flush();

        $this->assertSame($defaultCount + 1, $repo->count([]));
    }
}