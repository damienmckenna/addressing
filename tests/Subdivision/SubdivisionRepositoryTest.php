<?php

namespace CommerceGuys\Addressing\Tests\Subdivision;

use CommerceGuys\Addressing\Subdivision\Subdivision;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CommerceGuys\Addressing\Subdivision\SubdivisionRepository
 */
final class SubdivisionRepositoryTest extends TestCase
{
    /**
     * Subdivisions.
     *
     * @var array
     */
    protected array $subdivisions = [
        'BR' => [
            'country_code' => 'BR',
            'locale' => 'pt',
            'subdivisions' => [
                'SC' => [
                    'name' => 'Santa Catarina',
                    'iso_code' => 'BR-SC',
                    'postal_code_pattern' => '8[89]',
                    'postal_code_pattern_type' => 'full',
                    'has_children' => true,
                ],
                'SP' => [
                    'name' => 'São Paulo',
                    'iso_code' => 'BR-SP',
                    'postal_code_pattern' => '[01][1-9]',
                    'has_children' => true,
                ],
            ],
        ],
        'BR-249a39f10ac434b1fcd4d51516266b8e' => [
            'country_code' => 'BR',
            'parents' => ['BR', 'SC'],
            'locale' => 'pt',
            'subdivisions' => [
                'Abelardo Luz' => [],
            ],
        ],
        'BR-8ef7a36db3f5d47d46566f851be5f610' => [
            'country_code' => 'BR',
            'parents' => ['BR', 'SP'],
            'locale' => 'pt',
            'subdivisions' => [
                'Anhumas' => [],
            ]
        ],
    ];

    /**
     * @covers ::__construct
     */
    public function testConstructor(): SubdivisionRepository
    {
        // Mock the existence of JSON definitions on the filesystem.
        $root = vfsStream::setup('resources');
        $directory = vfsStream::newDirectory('subdivision')->at($root);
        foreach ($this->subdivisions as $parent => $data) {
            $filename = $parent . '.json';
            vfsStream::newFile($filename)->at($directory)->setContent(json_encode($data));
        }

        // Instantiate the subdivision repository and confirm that the
        // definition path was properly set.
        $subdivisionRepository = new SubdivisionRepository(null, 'vfs://resources/subdivision/');

        $reflected_constraint = (new \ReflectionObject($subdivisionRepository))->getProperty('definitionPath');
        $reflected_constraint->setAccessible(true);
        $definitionPath = $reflected_constraint->getValue($subdivisionRepository);
        $this->assertEquals('vfs://resources/subdivision/', $definitionPath);

        return $subdivisionRepository;
    }

    /**
     * @covers ::get
     * @covers ::hasData
     * @covers ::loadDefinitions
     * @covers ::processDefinitions
     * @covers ::buildGroup
     * @covers ::createSubdivisionFromDefinitions
     *
     * @depends testConstructor
     */
    public function testGet($subdivisionRepository): void
    {
        $subdivision = $subdivisionRepository->get('SC', ['BR']);
        $subdivisionChild = $subdivisionRepository->get('Abelardo Luz', ['BR', 'SC']);

        $this->assertInstanceOf(Subdivision::class, $subdivision);
        $this->assertEquals(null, $subdivision->getParent());
        $this->assertEquals('BR', $subdivision->getCountryCode());
        $this->assertEquals('pt', $subdivision->getLocale());
        $this->assertEquals('SC', $subdivision->getCode());
        $this->assertEquals('Santa Catarina', $subdivision->getName());
        $this->assertEquals('BR-SC', $subdivision->getIsoCode());

        $children = $subdivision->getChildren();
        $this->assertEquals($subdivisionChild, $children['Abelardo Luz']);

        $this->assertInstanceOf(Subdivision::class, $subdivisionChild);
        $this->assertEquals('Abelardo Luz', $subdivisionChild->getCode());
        // $subdivision contains the loaded children while $parent doesn't,
        // so they can't be compared directly.
        $parent = $subdivisionChild->getParent();
        $this->assertInstanceOf(Subdivision::class, $parent);
        $this->assertEquals($subdivision->getCode(), $parent->getCode());
    }

    /**
     * @covers ::get
     * @covers ::hasData
     * @covers ::loadDefinitions
     * @covers ::processDefinitions
     * @covers ::buildGroup
     * @covers ::createSubdivisionFromDefinitions
     *
     * @depends testConstructor
     */
    public function testGetInvalidSubdivision($subdivisionRepository): void
    {
        $subdivision = $subdivisionRepository->get('FAKE', ['BR']);
        $this->assertNull($subdivision);
    }

    /**
     * @covers ::getAll
     * @covers ::hasData
     * @covers ::loadDefinitions
     * @covers ::processDefinitions
     * @covers ::buildGroup
     * @covers ::createSubdivisionFromDefinitions
     *
     * @depends testConstructor
     */
    public function testGetAll($subdivisionRepository): void
    {
        $subdivisions = $subdivisionRepository->getAll(['RS']);
        $this->assertEquals([], $subdivisions);

        $subdivisions = $subdivisionRepository->getAll(['BR']);
        $this->assertCount(2, $subdivisions);
        $this->assertArrayHasKey('SC', $subdivisions);
        $this->assertArrayHasKey('SP', $subdivisions);
        $this->assertEquals('SC', $subdivisions['SC']->getCode());
        $this->assertEquals('SP', $subdivisions['SP']->getCode());

        $subdivisions = $subdivisionRepository->getAll(['BR', 'SC']);
        $this->assertCount(1, $subdivisions);
        $this->assertArrayHasKey('Abelardo Luz', $subdivisions);
        $this->assertEquals('Abelardo Luz', $subdivisions['Abelardo Luz']->getCode());
    }

    /**
     * @covers ::getList
     * @covers ::hasData
     * @covers ::loadDefinitions
     * @covers ::processDefinitions
     * @covers ::buildGroup
     *
     * @depends testConstructor
     */
    public function testGetList($subdivisionRepository): void
    {
        $list = $subdivisionRepository->getList(['RS']);
        $this->assertEquals([], $list);

        $list = $subdivisionRepository->getList(['BR']);
        $expectedList = ['SC' => 'Santa Catarina', 'SP' => 'São Paulo'];
        $this->assertEquals($expectedList, $list);

        $list = $subdivisionRepository->getList(['BR', 'SC']);
        $expectedList = ['Abelardo Luz' => 'Abelardo Luz'];
        $this->assertEquals($expectedList, $list);
    }
}
