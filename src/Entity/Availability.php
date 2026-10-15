<?php

namespace App\Entity;

use App\Repository\AvailabilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $team = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $endTime = null;

    public function getId(): ?int { return $this->id; }

    public function getTeam(): ?Team { return $this->team; }
    public function setTeam(Team $team): static { $this->team = $team; return $this; }

    public function getTournament(): ?Tournament { return $this->tournament; }
    public function setTournament(Tournament $tournament): static { $this->tournament = $tournament; return $this; }

    public function getDate(): ?\DateTime { return $this->date; }
    public function setDate(\DateTime $date): static { $this->date = $date; return $this; }

    public function getStartTime(): ?\DateTime { return $this->startTime; }
    public function setStartTime(\DateTime $startTime): static { $this->startTime = $startTime; return $this; }

    public function getEndTime(): ?\DateTime { return $this->endTime; }
    public function setEndTime(?\DateTime $endTime): static { $this->endTime = $endTime; return $this; }
}
