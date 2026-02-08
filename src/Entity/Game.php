<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    private ?User $player1 = null;

    #[ORM\ManyToOne]
    private ?User $player2 = null;

    #[ORM\ManyToOne]
    private ?User $player3 = null;

    #[ORM\ManyToOne]
    private ?User $player4 = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $court = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phase = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $score = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): static
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getPlayer1(): ?User
    {
        return $this->player1;
    }

    public function setPlayer1(?User $player1): static
    {
        $this->player1 = $player1;

        return $this;
    }

    public function getPlayer2(): ?User
    {
        return $this->player2;
    }

    public function setPlayer2(?User $player2): static
    {
        $this->player2 = $player2;

        return $this;
    }

    public function getPlayer3(): ?User
    {
        return $this->player3;
    }

    public function setPlayer3(?User $player3): static
    {
        $this->player3 = $player3;

        return $this;
    }

    public function getPlayer4(): ?User
    {
        return $this->player4;
    }

    public function setPlayer4(?User $player4): static
    {
        $this->player4 = $player4;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCourt(): ?string
    {
        return $this->court;
    }

    public function setCourt(?string $court): static
    {
        $this->court = $court;

        return $this;
    }

    public function getPhase(): ?string
    {
        return $this->phase;
    }

    public function setPhase(?string $phase): static
    {
        $this->phase = $phase;

        return $this;
    }

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(?string $score): static
    {
        $this->score = $score;

        return $this;
    }
}
