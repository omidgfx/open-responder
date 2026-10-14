<?php

/*
 * This file is part of the Omidgfx\Responder\Fractal package.
 *
 * (c) Phil Sturgeon <me@philsturgeon.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omidgfx\Responder\Fractal;

use Omidgfx\Responder\Fractal\Resource\ResourceInterface;

class ScopeFactory implements ScopeFactoryInterface
{
    public function createScopeFor(
        Manager $manager,
        ResourceInterface $resource,
        ?string $scopeIdentifier = null
    ): Scope {
        $scopeInstance = new Scope($manager, $resource, $scopeIdentifier);

        // root scopes run in the context the caller handed the manager
        $scopeInstance->setContext($manager->getContext());

        return $scopeInstance;
    }

    public function createChildScopeFor(
        Manager $manager,
        Scope $parentScope,
        ResourceInterface $resource,
        ?string $scopeIdentifier = null
    ): Scope {
        $scopeInstance = $this->createScopeFor($manager, $resource, $scopeIdentifier);

        // This will be the new children list of parents (parents parents, plus the parent)
        $scopeArray = $parentScope->getParentScopes();
        $scopeArray[] = $parentScope->getScopeIdentifier();

        $scopeInstance->setParentScopes($scopeArray);

        // the situation flows down the tree: children inherit their parent's context
        $scopeInstance->setContext($parentScope->getContext());

        return $scopeInstance;
    }
}
