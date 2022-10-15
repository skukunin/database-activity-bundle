<?php

namespace SKukunin\DatabaseActivityBundle;

use SKukunin\DatabaseActivityBundle\DependencyInjection\SKukuninDatabaseActivityExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SKukuninDatabaseActivityBundle extends Bundle
{
    /**
     * Overridden to allow for the custom extension alias.
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SKukuninDatabaseActivityExtension();
        }
        return $this->extension;
    }
}