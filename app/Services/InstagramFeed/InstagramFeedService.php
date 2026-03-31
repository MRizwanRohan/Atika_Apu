<?php

namespace App\Services\InstagramFeed;

use App\Repository\InstagramFeed\InstagramFeedRepository;
use App\Helpers\ImageHelper;
use Illuminate\Http\UploadedFile;

class InstagramFeedService
{
    protected $repository;

    public function __construct(InstagramFeedRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all instagram feeds
     */
    public function list($onlyActive = false)
    {
        return $this->repository->getAll($onlyActive);
    }

    /**
     * Store a new instagram feed
     */
    public function store(array $data)
    {
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = ImageHelper::uploadImage($data['image'], 'uploads/instagram');
        } else {
            unset($data['image']);
        }

        return $this->repository->create($data);
    }

    /**
     * Get instance of instagram feed
     */
    public function getById($id)
    {
        return $this->repository->findById($id);
    }

    /**
     * Update an instagram feed
     */
    public function update($id, array $data)
    {
        $feed = $this->repository->findById($id);

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            // Delete old image if exists
            if ($feed->image) {
                ImageHelper::deleteImage($feed->image);
            }
            $data['image'] = ImageHelper::uploadImage($data['image'], 'uploads/instagram');
        } else {
            // 🚨 Only update if file is present, otherwise keep old image
            unset($data['image']);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Toggle the status of an instagram feed
     */
    public function toggleStatus($id)
    {
        $feed = $this->repository->findById($id);
        return $this->repository->update($id, ['status' => !$feed->status]);
    }

    /**
     * Delete an instagram feed
     */
    public function destroy($id)
    {
        $feed = $this->repository->findById($id);
        
        if ($feed->image) {
            ImageHelper::deleteImage($feed->image);
        }

        return $this->repository->delete($id);
    }

    /**
     * Reorder instagram feeds
     */
    public function reorder(array $ids)
    {
        foreach ($ids as $index => $id) {
            $this->repository->update($id, [
                'sort_order' => $index
            ]);
        }
    }
}
