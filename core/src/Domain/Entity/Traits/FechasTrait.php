<?php

namespace App\Domain\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait FechasTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $fechaCreacion = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $fechaActualizacion = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaEliminacion = null;

    #[ORM\PrePersist]
    public function establecerFechasCreacion(): void
    {
        $ahora = new \DateTimeImmutable();

        $this->fechaCreacion = $ahora;
        $this->fechaActualizacion = $ahora;
    }

    #[ORM\PreUpdate]
    public function establecerFechaActualizacion(): void
    {
        $this->fechaActualizacion = new \DateTimeImmutable();
    }

    public function eliminarLogicamente(): void
    {
        $this->fechaEliminacion = new \DateTimeImmutable();
    }

    public function restaurar(): void
    {
        $this->fechaEliminacion = null;
    }

    public function estaEliminado(): bool
    {
        return $this->fechaEliminacion !== null;
    }

    // Getters
    public function getFechaCreacion(): ?\DateTimeImmutable
    {
        return $this->fechaCreacion;
    }

    public function getFechaActualizacion(): ?\DateTimeImmutable
    {
        return $this->fechaActualizacion;
    }

    public function getFechaEliminacion(): ?\DateTimeImmutable
    {
        return $this->fechaEliminacion;
    }
}
