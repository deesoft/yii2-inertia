<?php
namespace dee\inertia;

interface Onceable
{
    /**
     * Mark the prop to be resolved only once.
     */
    public function once(bool $value = true): static;

    /**
     * Determine if the prop should be resolved only once.
     */
    public function shouldResolveOnce(): bool;

    /**
     * Determine if the prop was marked as fresh.
     */
    public function shouldBeRefreshed(): bool;

    /**
     * Get the custom key for resolving the once prop.
     */
    public function getKey(): ?string;

    /**
     * Set a custom key for resolving the once prop.
     */
    public function as(string $key): static;

    /**
     * Set the expiration for the once prop.
     */
    public function until(int $delay): static;

    /**
     * Get the expiration timestamp in milliseconds for the once prop.
     */
    public function expiresAt(): ?int;
}
