<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionStatus;
use Nkamuo\AssetBundle\Domain\ValueObject\ProvisionType;
use Symfony\Component\Uid\Ulid;

/**
 * Asset Provision entity representing contracts and agreements
 * for asset usage between partners.
 *
 * Handles the relationship between asset providers and recipients,
 * including terms, rates, and billing arrangements.
 */
#[ORM\Entity]
#[ORM\Table(name: 'nkamuo_asset_provision')]
#[ORM\Index(columns: ['asset_id'], name: 'idx_provision_asset')]
#[ORM\Index(columns: ['provider_id'], name: 'idx_provision_provider')]
#[ORM\Index(columns: ['recipient_id'], name: 'idx_provision_recipient')]
#[ORM\Index(columns: ['status'], name: 'idx_provision_status')]
class AssetProvision
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Asset::class, inversedBy: 'provisions')]
    #[ORM\JoinColumn(nullable: false)]
    private Asset $asset;

    #[ORM\Column(type: 'ulid')]
    private Ulid $providerId; // Who provides the asset

    #[ORM\Column(type: 'ulid')]
    private Ulid $recipientId; // Who receives/uses the asset

    #[ORM\Column(type: 'string', enumType: ProvisionType::class)]
    private ProvisionType $type;

    #[ORM\Column(type: 'string', enumType: ProvisionStatus::class)]
    private ProvisionStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'json')]
    private array $terms = []; // Contract terms as JSON

    #[ORM\Column(type: 'json')]
    private array $metadata = []; // Additional flexible data

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'provision', targetEntity: AssetRateCard::class, cascade: ['persist', 'remove'])]
    private Collection $rateCards;

    #[ORM\OneToMany(mappedBy: 'provision', targetEntity: AssetBillingEvent::class)]
    private Collection $billingEvents;

    public function __construct(
        Asset $asset,
        Ulid $providerId,
        Ulid $recipientId,
        ProvisionType $type,
        \DateTimeImmutable $startDate,
        ?\DateTimeImmutable $endDate = null,
        array $terms = [],
        array $metadata = [],
        ProvisionStatus $status = ProvisionStatus::DRAFT
    ) {
        $this->id = new Ulid();
        $this->asset = $asset;
        $this->providerId = $providerId;
        $this->recipientId = $recipientId;
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->terms = $terms;
        $this->metadata = $metadata;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->rateCards = new ArrayCollection();
        $this->billingEvents = new ArrayCollection();
    }

    // Getters
    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getProviderId(): Ulid
    {
        return $this->providerId;
    }

    public function getRecipientId(): Ulid
    {
        return $this->recipientId;
    }

    public function getType(): ProvisionType
    {
        return $this->type;
    }

    public function getStatus(): ProvisionStatus
    {
        return $this->status;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getTerms(): array
    {
        return $this->terms;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getRateCards(): Collection
    {
        return $this->rateCards;
    }

    public function getBillingEvents(): Collection
    {
        return $this->billingEvents;
    }

    // Business methods
    public function activate(): void
    {
        if (!$this->status->canTransitionTo(ProvisionStatus::ACTIVE)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot activate provision from status %s', $this->status->value)
            );
        }

        $this->status = ProvisionStatus::ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function suspend(): void
    {
        if (!$this->status->canTransitionTo(ProvisionStatus::SUSPENDED)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot suspend provision from status %s', $this->status->value)
            );
        }

        $this->status = ProvisionStatus::SUSPENDED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function terminate(): void
    {
        if (!$this->status->canTransitionTo(ProvisionStatus::TERMINATED)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot terminate provision from status %s', $this->status->value)
            );
        }

        $this->status = ProvisionStatus::TERMINATED;
        $this->endDate = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function expire(): void
    {
        $this->status = ProvisionStatus::EXPIRED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateTerms(array $terms): void
    {
        $this->terms = $terms;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addTerm(string $key, mixed $value): void
    {
        $this->terms[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function extendEndDate(\DateTimeImmutable $newEndDate): void
    {
        if ($this->endDate && $newEndDate <= $this->endDate) {
            throw new \InvalidArgumentException('New end date must be after current end date');
        }

        $this->endDate = $newEndDate;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addRateCard(AssetRateCard $rateCard): void
    {
        if (!$this->rateCards->contains($rateCard)) {
            $this->rateCards->add($rateCard);
        }
    }

    public function removeRateCard(AssetRateCard $rateCard): void
    {
        $this->rateCards->removeElement($rateCard);
    }

    public function isActiveAt(\DateTimeImmutable $date): bool
    {
        if ($this->status !== ProvisionStatus::ACTIVE) {
            return false;
        }

        if ($date < $this->startDate) {
            return false;
        }

        if ($this->endDate && $date > $this->endDate) {
            return false;
        }

        return true;
    }

    public function isActive(): bool
    {
        return $this->isActiveAt(new \DateTimeImmutable());
    }

    public function getDuration(): ?\DateInterval
    {
        if (!$this->endDate) {
            return null;
        }

        return $this->startDate->diff($this->endDate);
    }

    public function getRemainingDuration(\DateTimeImmutable $at = null): ?\DateInterval
    {
        $at = $at ?? new \DateTimeImmutable();

        if (!$this->endDate || $at > $this->endDate) {
            return null;
        }

        if ($at < $this->startDate) {
            return $this->startDate->diff($this->endDate);
        }

        return $at->diff($this->endDate);
    }

    public function getActiveRateCards(\DateTimeImmutable $at = null): array
    {
        $at = $at ?? new \DateTimeImmutable();

        return $this->rateCards->filter(
            fn (AssetRateCard $rateCard) => $rateCard->isActiveAt($at)
        )->toArray();
    }

    public function getTerm(string $key): mixed
    {
        return $this->terms[$key] ?? null;
    }

    public function hasTerm(string $key): bool
    {
        return array_key_exists($key, $this->terms);
    }

    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }
}
