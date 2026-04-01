<?php
// src/Twig/SidebarExtension.php

namespace App\Twig;

use App\Repository\AssociationRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SidebarExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly AssociationRepository $repo,
    ) {}

    /** @return array<string, mixed> */
    public function getGlobals(): array
    {
        return [
            'sidebar_associations' => $this->repo->findAllActive(),
        ];
    }
}
