<?php
declare(strict_types=1);

namespace Tests\Hal\Violation\Package;

use Generator;
use Hal\Metric\Metric;
use Hal\Metric\PackageMetric;
use Hal\Violation\Package\StableAbstractionsPrinciple;
use Hal\Violation\Violation;
use Hal\Violation\ViolationsHandlerInterface;
use Phake;
use Phake\IMock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function sqrt;

final class StableAbstractionsPrincipleTest extends TestCase
{
    public function testViolationLevel(): void
    {
        self::assertSame(Violation::WARNING, (new StableAbstractionsPrinciple())->getLevel());
    }

    public function testViolationName(): void
    {
        self::assertSame('Stable Abstractions Principle', (new StableAbstractionsPrinciple())->getName());
    }

    /**
     * @return Generator<string, array{IMock&Metric, IMock&ViolationsHandlerInterface, bool}>
     */
    public static function provideMetricToCheckIfViolationApplies(): Generator
    {
        yield 'Invalid metric' => [Phake::mock(Metric::class), Phake::mock(ViolationsHandlerInterface::class), false];

        $violationsHandler = Phake::mock(ViolationsHandlerInterface::class);
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn(null);
        yield 'Distance is null' => [$packageMetric, $violationsHandler, false];

        $violationsHandler = Phake::mock(ViolationsHandlerInterface::class);
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('get', ['violations'])->thenReturn($violationsHandler);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn(0);
        yield 'Distance is 0' => [$packageMetric, $violationsHandler, false];

        $violationsHandler = Phake::mock(ViolationsHandlerInterface::class);
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('get', ['violations'])->thenReturn($violationsHandler);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn(sqrt(2) / 4);
        yield 'Distance is positive and not too far away' => [$packageMetric, $violationsHandler, false];

        $violationsHandler = Phake::mock(ViolationsHandlerInterface::class);
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('get', ['violations'])->thenReturn($violationsHandler);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn(-sqrt(2) / 4);
        yield 'Distance is negative and not too far away' => [$packageMetric, $violationsHandler, false];

        $violationsHandler = Phake::mock(ViolationsHandlerInterface::class);
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('get', ['violations'])->thenReturn($violationsHandler);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn(sqrt(2.1) / 4);
        yield 'Distance is positive but too far away' => [$packageMetric, $violationsHandler, true];

        $violationsHandler = Phake::mock(ViolationsHandlerInterface::class);
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('get', ['violations'])->thenReturn($violationsHandler);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn(-sqrt(2.1) / 4);
        yield 'Distance is negative but too far away' => [$packageMetric, $violationsHandler, true];

        $violationsHandler = Phake::mock(ViolationsHandlerInterface::class);
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('get', ['violations'])->thenReturn($violationsHandler);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn(1);
        yield 'Maximum distance' => [$packageMetric, $violationsHandler, true];

        $violationsHandler = Phake::mock(ViolationsHandlerInterface::class);
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('get', ['violations'])->thenReturn($violationsHandler);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn(-1);
        yield 'Minimum distance' => [$packageMetric, $violationsHandler, true];
    }

    /**
     * @param IMock&Metric $metric
     * @param ViolationsHandlerInterface&IMock $violationsHandler
     * @param bool $violate
     * @return void
     */
    #[DataProvider('provideMetricToCheckIfViolationApplies')]
    public function testViolationApplies(
        IMock&Metric $metric,
        IMock&ViolationsHandlerInterface $violationsHandler,
        bool $violate
    ): void {
        $violation = new StableAbstractionsPrinciple();
        $violation->apply($metric);

        if (false === $violate) {
            Phake::verifyNoInteraction($violationsHandler);
            return;
        }

        /** @var IMock&PackageMetric $metric */
        Phake::verify($metric)->__call('get', ['violations']);
        Phake::verify($violationsHandler)->__call('add', [$violation]);
        Phake::verifyNoOtherInteractions($violationsHandler);
        self::assertSame($this->getExpectedDescription($metric), $violation->getDescription());
    }

    /**
     * @return Generator<string, array{float, string}>
     */
    public static function provideGetDescription(): Generator
    {
        $unstableAndAbstractDescription = <<<'DESC'
        Packages should be either abstract and stable or concrete and unstable.
        
        This package is unstable and abstract.
        DESC;
        $stableAndConcreteDescription = <<<'DESC'
        Packages should be either abstract and stable or concrete and unstable.
        
        This package is stable and concrete.
        DESC;

        yield 'Positive distance' => [0.001, $unstableAndAbstractDescription];
        yield 'Equal distance' => [0, $stableAndConcreteDescription];
        yield 'Negative distance' => [-0.001, $stableAndConcreteDescription];
    }

    /**
     * @param float $distance
     * @param string $expectedDescription
     */
    #[DataProvider('provideGetDescription')]
    public function testGetDescription(float $distance, string $expectedDescription): void
    {
        $packageMetric = Phake::mock(PackageMetric::class);
        Phake::when($packageMetric)->__call('getDistance', [])->thenReturn($distance);

        $violation = new StableAbstractionsPrinciple();
        $violation->apply($packageMetric);
        self::assertSame($expectedDescription, $violation->getDescription());

        Phake::verify($packageMetric, Phake::times(2))->__call('getDistance', []);
        Phake::verifyNoOtherInteractions($packageMetric);
    }

    /**
     * Returns the expected description of the current violation based on the values stored in the given metrics.
     *
     * @param PackageMetric $metric
     * @return string
     */
    private function getExpectedDescription(PackageMetric $metric): string
    {
        $violation = $metric->getDistance() > 0 ? 'unstable and abstract' : 'stable and concrete';
        return <<<EOT
Packages should be either abstract and stable or concrete and unstable.

This package is $violation.
EOT;
    }
}
