<?php

namespace Omidgfx\Responder\Tests;

use Illuminate\Contracts\Container\Container;
use Omidgfx\Responder\Fractal\Manager;
use Omidgfx\Responder\Fractal\Resource\Item;
use Omidgfx\Responder\Fractal\Serializer\ArraySerializer;
use Omidgfx\Responder\FractalTransformFactory;
use Omidgfx\Responder\Pagination\PaginatorFactory;
use Omidgfx\Responder\Resources\DataNormalizer;
use Omidgfx\Responder\Resources\ResourceFactory;
use Omidgfx\Responder\Resources\ResourceKeyResolver;
use Omidgfx\Responder\Serializers\SuccessSerializer;
use Omidgfx\Responder\TransformBuilder;
use Omidgfx\Responder\Transformers\ArrayTransformer;
use Omidgfx\Responder\Transformers\Transformer;
use Omidgfx\Responder\Transformers\TransformerResolver;
use PHPUnit\Framework\TestCase;

class ViaInnerTransformer extends Transformer
{
    protected $relations = [];

    protected $load = [];

    public function transform($book): array
    {
        return [
            'title'  => $book['title'],
            'viewer' => $this->get('viewer'),
        ];
    }

    protected function resolveContainer(): Container
    {
        throw new \LogicException('The inner transformer builds no nested resources.');
    }

    protected function resolveRelation(\Illuminate\Database\Eloquent\Model $model, string $identifier)
    {
        return null;
    }

    protected function mappedTransformerClass(string $identifier): string
    {
        return '';
    }
}

class ViaOuterTransformer extends Transformer
{
    /** @var Container|null */
    public static $container;

    protected $relations = [];

    protected $load = [];

    public function transform($shelf): array
    {
        return [
            'name'      => $shelf['name'],
            'book'      => $this->via(['title' => 'Nested'], new ViaInnerTransformer, 'book'),
            'no_book'   => $this->via(null, new ViaInnerTransformer),
        ];
    }

    public function exposeAssured($data): array
    {
        return $this->viaAssured($data, new ViaInnerTransformer, 'book');
    }

    protected function resolveContainer(): Container
    {
        if (static::$container === null) {
            throw new \LogicException('The via tests need a container.');
        }

        return static::$container;
    }

    protected function resolveRelation(\Illuminate\Database\Eloquent\Model $model, string $identifier)
    {
        return null;
    }

    protected function mappedTransformerClass(string $identifier): string
    {
        return '';
    }
}

final class ViaTest extends TestCase
{
    protected function setUp(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('make')->willReturnCallback(static function () use ($container) {
            return (new TransformBuilder(
                new ResourceFactory(
                    new DataNormalizer,
                    new TransformerResolver($container, ArrayTransformer::class),
                    new ResourceKeyResolver
                ),
                new FractalTransformFactory(new Manager),
                new PaginatorFactory([])
            ))->serializer(new SuccessSerializer);
        });

        ViaOuterTransformer::$container = $container;
    }

    /**
     * via() transforms nested data inline and the nested transformer
     * reads the same context the outer one received.
     */
    public function test_via_transforms_nested_data_and_carries_the_context(): void
    {
        $factory = new FractalTransformFactory(new Manager);
        $result = $factory->make(
            new Item(['name' => 'Shelf'], new ViaOuterTransformer, 'shelf'),
            new ArraySerializer,
            ['includes' => [], 'excludes' => [], 'fieldsets' => [], 'context' => ['viewer' => 'pejman']]
        );

        $this->assertSame('Shelf', $result['name']);
        $this->assertSame('Nested', $result['book']['title']);
        $this->assertSame('pejman', $result['book']['viewer']);
        $this->assertNull($result['no_book']);
    }

    /**
     * viaAssured() returns the nested array for guaranteed data and
     * fails visibly when the data resolves to null.
     */
    public function test_via_assured_returns_or_throws(): void
    {
        $outer = new ViaOuterTransformer;

        $this->assertSame(['title' => 'Nested', 'viewer' => null], $outer->exposeAssured(['title' => 'Nested']));

        $this->expectException(\LogicException::class);
        $outer->exposeAssured(null);
    }
}
