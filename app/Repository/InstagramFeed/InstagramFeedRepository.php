<?php

namespace App\Repository\InstagramFeed;

use App\Models\InstagramFeed;

class InstagramFeedRepository
{
    /**
     * Get all instagram feeds ordered by sort_order
     */
    public function getAll($onlyActive = false)
    {
        $query = InstagramFeed::query();
        
        if ($onlyActive) {
            $query->active();
        }

        return $query->orderBy('sort_order', 'asc')->get();
    }

    /**
     * Get a single instagram feed by ID
     */
    public function findById($id)
    {
        return InstagramFeed::findOrFail($id);
    }

    /**
     * Create a new instagram feed
     */
    public function create(array $data)
    {
        return InstagramFeed::create($data);
    }

    /**
     * Update an instagram feed
     */
    public function update($id, array $data)
    {
        $feed = $this->findById($id);
        $feed->update($data);
        return $feed;
    }

    /**
     * Delete an instagram feed
     */
    public function delete($id)
    {
        $feed = $this->findById($id);
        return $feed->delete();
    }
}
