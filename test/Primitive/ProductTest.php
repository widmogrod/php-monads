<?php

declare(strict_types=1);

namespace test\Primitive;

use Eris\Generator;
use Eris\TestTrait;
use FunctionalPHP\FantasyLand\Helpful\MonoidLaws;
use FunctionalPHP\FantasyLand\Helpful\SetoidLaws;
use PHPUnit\Framework\TestCase;
use Widmogrod\Primitive\Product;
use Widmogrod\Primitive\Stringg;
use Widmogrod\Primitive\TypeMismatchError;

class ProductTest extends TestCase
{
    use TestTrait;

    public function test_it_should_obay_setoid_laws()
    {
        $this->forAll(
            Generator\choose(0, 1000),
            Generator\choose(1000, 4000),
            Generator\choose(4000, 100000)
        )->then(function (int $x, int $y, int $z) {
            SetoidLaws::test(
                [$this, 'assertEquals'],
                Product::of($x),
                Product::of($y),
                Product::of($z)
            );
        });
    }

    public function test_it_should_obay_monoid_laws()
    {
        $this->forAll(
            Generator\choose(0, 1000),
            Generator\choose(1000, 4000),
            Generator\choose(4000, 100000)
        )->then(function (int $x, int $y, int $z) {
            MonoidLaws::test(
                [$this, 'assertEquals'],
                Product::of($x),
                Product::of($y),
                Product::of($z)
            );
        });
    }

    public function test_it_should_reject_concat_on_different_type()
    {
        $this->expectException(TypeMismatchError::class);
        $this->expectExceptionMessage('Expected type is Widmogrod\Primitive\Product but given Widmogrod\Primitive\Stringg');
        $this->forAll(
            Generator\int(),
            Generator\string()
        )->then(function (int $x, string $y) {
            Product::of($x)->concat(Stringg::of($y));
        });
    }
}
