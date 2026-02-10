<?php

namespace App\Repositories\Interfaces;

interface ArtistRepositoryInterface
{
    public function findBySlug(string $slug);
    public function getTopArtists(int $limit);
    public function updateMonthlyListeners(string $artistId);
}