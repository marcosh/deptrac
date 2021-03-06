<?php

declare(strict_types=1);

namespace Tests\SensioLabs\Deptrac\Collector;

use PHPUnit\Framework\TestCase;
use SensioLabs\Deptrac\AstRunner\AstMap;
use SensioLabs\Deptrac\AstRunner\AstMap\AstClassReference;
use SensioLabs\Deptrac\Collector\BoolCollector;
use SensioLabs\Deptrac\Collector\CollectorInterface;
use SensioLabs\Deptrac\Collector\Registry;

final class BoolCollectorTest extends TestCase
{
    public function testSatisfy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"bool" collector must have a "must" or a "must_not" attribute.');

        (new BoolCollector())->satisfy(
            [],
            $this->createMock(AstClassReference::class),
            $this->createMock(AstMap::class),
            $this->createMock(Registry::class)
        );
    }

    public function testType(): void
    {
        self::assertEquals('bool', (new BoolCollector())->getType());
    }

    private function getCalculatorMock(bool $returns)
    {
        $collector = $this->createMock(CollectorInterface::class);
        $collector
            ->method('satisfy')
            ->with(
                ['type' => $returns ? 'true' : 'false', 'foo' => 'bar'],
                self::isInstanceOf(AstClassReference::class),
                self::isInstanceOf(AstMap::class),
                self::isInstanceOf(Registry::class)
            )
            ->willReturn($returns);

        return $collector;
    }

    public function provideSatisfyBasic(): iterable
    {
        // must
        yield [
            [
                'must' => [
                    ['type' => 'true'],
                ],
            ],
            true,
        ];

        yield [
            [
                'must' => [
                    ['type' => 'true'],
                    ['type' => 'true'],
                ],
            ],
            true,
        ];

        yield [
            [
                'must' => [
                    ['type' => 'true'],
                    ['type' => 'false'],
                ],
            ],
            false,
        ];

        yield [
            [
                'must' => [
                    ['type' => 'false'],
                    ['type' => 'true'],
                ],
            ],
            false,
        ];

        yield [
            [
                'must' => [
                    ['type' => 'false'],
                ],
            ],
            false,
        ];

        // must not
        yield [
            [
                'must_not' => [
                    ['type' => 'false'],
                ],
            ],
            true,
        ];

        yield [
            [
                'must_not' => [
                    ['type' => 'true'],
                ],
            ],
            false,
        ];

        yield [
            [
                'must_not' => [
                    ['type' => 'true'],
                    ['type' => 'false'],
                ],
            ],
            false,
        ];

        yield [
            [
                'must_not' => [
                    ['type' => 'false'],
                    ['type' => 'false'],
                ],
            ],
            true,
        ];

        yield [
            [
                'must_not' => [
                    ['type' => 'true'],
                    ['type' => 'true'],
                ],
            ],
            false,
        ];
    }

    /**
     * @dataProvider provideSatisfyBasic
     */
    public function testSatisfyBasicTest(array $configuration, bool $expected): void
    {
        $collectorFactory = $this->prophesize(Registry::class);
        $collectorFactory->getCollector('true')->willReturn(
            $this->getCalculatorMock(true)
        );
        $collectorFactory->getCollector('false')->willReturn(
            $this->getCalculatorMock(false)
        );

        if (isset($configuration['must'])) {
            foreach ($configuration['must'] as &$v) {
                $v['foo'] = 'bar';
            }
        }
        if (isset($configuration['must_not'])) {
            foreach ($configuration['must_not'] as &$v) {
                $v['foo'] = 'bar';
            }
        }

        $stat = (new BoolCollector())->satisfy(
            $configuration,
            $this->prophesize(AstClassReference::class)->reveal(),
            $this->prophesize(AstMap::class)->reveal(),
            $collectorFactory->reveal()
        );

        self::assertEquals($expected, $stat);
    }
}
