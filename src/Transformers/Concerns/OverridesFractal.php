<?php

namespace Omidgfx\Responder\Transformers\Concerns;

use Omidgfx\Responder\Fractal\Resource\ResourceInterface;
use Omidgfx\Responder\Fractal\Scope;

/**
 * Replaces the Fractal include getter/invoker behaviour so that relationship
 * resolution flows through the responder's own relationship & resource logic.
 */
trait OverridesFractal
{
    /**
     * List the available includes. Setting `$relations = ['*']` opts into
     * dynamically resolving includes from the requested scopes.
     *
     * @return array
     */
    public function getAvailableIncludes(): array
    {
        if ($this->relations == ['*']) {
            return $this->resolveScopedIncludes($this->getCurrentScope());
        }

        return array_keys($this->normalizeRelations($this->relations));
    }

    /**
     * List the default (autoloaded) includes.
     *
     * @return array
     */
    public function getDefaultIncludes(): array
    {
        return array_keys($this->normalizeRelations($this->load));
    }

    /**
     * Intercept the Fractal include call and route it through the responder's
     * relationship logic, passing the manager-provided include parameters.
     *
     * @param  \Omidgfx\Responder\Fractal\Scope  $scope
     * @param  string  $identifier
     * @param  mixed  $data
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    protected function callIncludeMethod(Scope $scope, $identifier, $data)
    {
        $parameters = iterator_to_array($scope->getManager()->getIncludeParams($scope->getIdentifier($identifier)));

        return $this->includeResource($identifier, $data, $parameters);
    }

    /**
     * Resolve the includes requested that apply at the current scope level.
     *
     * @param  \Omidgfx\Responder\Fractal\Scope  $scope
     * @return array
     */
    protected function resolveScopedIncludes(Scope $scope): array
    {
        $level = count($scope->getParentScopes());
        $includes = $scope->getManager()->getRequestedIncludes();

        return collect($includes)->map(function ($include) {
            return explode('.', $include);
        })->filter(function ($include) use ($level) {
            return count($include) > $level;
        })->pluck($level)->unique()->all();
    }

    /**
     * Get the current scope of the transformer.
     *
     * @return \Omidgfx\Responder\Fractal\Scope
     */
    abstract public function getCurrentScope();

    /**
     * Normalize relations to force a key/value structure.
     *
     * @param  array  $relations
     * @return array
     */
    abstract protected function normalizeRelations(array $relations): array;

    /**
     * Include a related resource.
     *
     * @param  string  $identifier
     * @param  mixed  $data
     * @param  array  $parameters
     * @return \Omidgfx\Responder\Fractal\Resource\ResourceInterface
     */
    abstract protected function includeResource(string $identifier, $data, array $parameters): ResourceInterface;
}
