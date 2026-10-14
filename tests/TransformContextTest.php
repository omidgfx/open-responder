<?php

namespace Omidgfx\Responder\Tests;

use Illuminate\Contracts\Container\Container;
use Omidgfx\Responder\Fractal\Manager;
use Omidgfx\Responder\Fractal\Resource\Item;
use Omidgfx\Responder\Fractal\Serializer\ArraySerializer;
use Omidgfx\Responder\FractalTransformFactory;
use Omidgfx\Responder\Support\MissingValue;
use Omidgfx\Responder\TransformContext;
use Omidgfx\Responder\Transformers\Transformer;
use PHPUnit\Framework\TestCase;

class ContextBookTransformer extends Transformer
{
    protected $relations = [];

    protected $load = [];

    public function transform($book): array
    {
        return [
            'title'             => $book['title'],
            'viewer'            => $this->get('viewer'),
            'viewer_default'    => $this->get('missing', 'fallback'),
            'has_viewer'        => $this->has('viewer'),
            'has_missing'       => $this->has('missing'),
            'when_truthy'       => $this->when('viewer', static function ($viewer) {
                return 'hello '.$viewer;
            }),
            'when_falsy'        => $this->when('flag', static function () {
                return 'flag-on';
            }),
            'when_default'      => $this->when('flag', static function () {
                return 'flag-on';
            }, 'flag-off'),
            'whenhas_falsy'     => $this->whenHas('flag', static function ($flag) {
                return $flag ? 'truthy' : 'falsy';
            }),
            'whenhas_missing'   => $this->whenHas('missing', static function () {
                return 'here';
            }),
        ];
    }

    protected function resolveContainer(): Container
    {
        throw new \LogicException('The context tests never build nested resources.');
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

final class TransformContextTest extends TestCase
{
    /**
     * The full factory path: context set through the options reaches the
     * transformer, where get / has read it.
     */
    public function test_context_reaches_the_transformer(): void
    {
        $result = $this->transformBook(['viewer' => 'pejman', 'flag' => false]);

        $this->assertSame('Hamvarz', $result['title']);
        $this->assertSame('pejman', $result['viewer']);
        $this->assertSame('fallback', $result['viewer_default']);
        $this->assertTrue($result['has_viewer']);
        $this->assertFalse($result['has_missing']);
    }

    /**
     * [when] keeps the field for a truthy value and omits it for a falsy
     * one, unless a fallback was given.
     */
    public function test_when_omits_falsy_fields_and_keeps_truthy_ones(): void
    {
        $result = $this->transformBook(['viewer' => 'pejman', 'flag' => false]);

        $this->assertSame('hello pejman', $result['when_truthy']);
        $this->assertArrayNotHasKey('when_falsy', $result);
        $this->assertSame('flag-off', $result['when_default']);
    }

    /**
     * [whenHas] acts on presence, not truthiness: a falsy value still
     * reaches the closure, a missing key omits the field.
     */
    public function test_when_has_acts_on_presence_not_truthiness(): void
    {
        $result = $this->transformBook(['flag' => false]);

        $this->assertSame('falsy', $result['whenhas_falsy']);
        $this->assertArrayNotHasKey('whenhas_missing', $result);
    }

    /**
     * A transformer without any context gets empty answers, never errors.
     */
    public function test_a_bare_transformation_defaults_to_an_empty_context(): void
    {
        $result = $this->transformBook([]);

        $this->assertNull($result['viewer']);
        $this->assertFalse($result['has_viewer']);
        $this->assertArrayNotHasKey('when_truthy', $result);
    }

    /**
     * Child scopes inherit their parent's context, so the situation flows
     * down the whole resource tree.
     */
    public function test_child_scopes_inherit_their_parents_context(): void
    {
        $manager = (new Manager)->setContext(['viewer' => 'pejman']);

        $root = $manager->createData(new Item(['title' => 'root'], new ContextBookTransformer));
        $child = $root->embedChildScope('child', new Item(['title' => 'child'], new ContextBookTransformer));

        $this->assertSame('pejman', $root->getContext()->get('viewer'));
        $this->assertSame('pejman', $child->getContext()->get('viewer'));
    }

    /**
     * The bag itself: merge, has, get with default.
     */
    public function test_the_bag_merges_and_defaults(): void
    {
        $context = (new TransformContext(['a' => 1]))->merge(['b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $context->all());
        $this->assertTrue($context->has('a'));
        $this->assertFalse($context->has('zzz'));
        $this->assertNull($context->get('zzz'));
        $this->assertSame('dflt', $context->get('zzz', 'dflt'));
    }

    /**
     * MissingValue stays a plain marker the scope can filter on.
     */
    public function test_missing_value_is_a_plain_marker(): void
    {
        $this->assertInstanceOf(MissingValue::class, new MissingValue);
    }

    /**
     * @param array $context
     * @return array
     */
    private function transformBook(array $context): array
    {
        $factory = new FractalTransformFactory(new Manager);
        $resource = new Item(['title' => 'Hamvarz'], new ContextBookTransformer, 'book');

        return $factory->make($resource, new ArraySerializer, [
            'includes' => [],
            'excludes' => [],
            'fieldsets' => [],
            'context' => $context,
        ]);
    }
}
