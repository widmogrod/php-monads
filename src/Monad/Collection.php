<?php
namespace Monad;

use Common;
use Exception;
use FantasyLand;
use Functional as f;

class Collection implements
    FantasyLand\MonadInterface,
    Common\ValueOfInterface,
    Common\ConcatInterface
{
    use Common\CreateTrait;

    const create = 'Monad\Collection::create';

    public static function of(callable $b)
    {
        return self::create($b);
    }

    /**
     * @param array $value
     */
    public function __construct($value)
    {
        $this->value = is_array($value) || $value instanceof \Traversable
            ? $value
            : [$value];
    }

    /**
     * Transforms one category into another category.
     *
     * @param callable $transformation
     * @return mixed
     */
    public function map(callable $transformation)
    {
        $result = [];
        foreach ($this->value as $key => $value) {
            $result[$key] = call_user_func($transformation, $value);
        }

        return self::create($result);
    }


    /**
     * @inheritdoc
     */
    public function ap(FantasyLand\ApplyInterface $applicative)
    {
        // Sine in php List comprehension is not available, then I doing it like this
        $result = [];
        $isCollection = $applicative instanceof Collection;

        foreach ($this->valueOf() as $value) {
            $partial = $applicative->map($value)->valueOf();
            if ($isCollection) {
                $result = \Functional\push($result, $partial);
            } else {
                $result[] = $partial;
            }
        }

        return $applicative::create($result);
    }

    /**
     * @inheritdoc
     */
    public function bind(callable $transformation)
    {
        $result = [];
        foreach ($this->value as $index => $value) {
            $result = f\concat(
                $result,
                $value instanceof FantasyLand\MonadInterface
                    ? $value->bind($transformation)
                    : call_user_func($transformation, $value, $index)
            );
        }

        return static::create($result);
    }

    /**
     * Return value wrapped by Monad
     *
     * @return mixed
     */
    public function valueOf()
    {
        return array_map(function ($value) {
            return $value instanceof Common\ValueOfInterface
                ? $value->valueOf()
                : $value;
        }, $this->value);
    }

    public function concat($value)
    {
        if ($value instanceof self) {
            return $value->concat($this->value);
        }

        return f\concat($value, $this->value);
    }
}
