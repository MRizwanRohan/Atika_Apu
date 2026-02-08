<?php

namespace App\Services;

use App\Contracts\ProductVideoRepositoryInterface;
use App\Contracts\ProductVideoServiceInterface;
use App\Helpers\VideoHelper;

class ProductVideoService implements ProductVideoServiceInterface
{
    protected $repository;

    public function __construct(ProductVideoRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllVideos()
    {
        return $this->repository->all();
    }

    public function getVideoById($id)
    {
        return $this->repository->find($id);
    }

    public function storeVideo(array $data)
    {
        if (isset($data['video_file'])) {
            $data['video'] = VideoHelper::uploadVideo($data['video_file'], 'uploads/product_videos');
        }
        return $this->repository->create($data);
    }

    public function updateVideo($id, array $data)
    {
        $video = $this->repository->find($id);

        if (isset($data['video_file'])) {
            if ($video->video) {
                VideoHelper::deleteVideo($video->video);
            }
            $data['video'] = VideoHelper::uploadVideo($data['video_file'], 'uploads/product_videos');
        }

        return $this->repository->update($id, $data);
    }

    public function deleteVideo($id)
    {
        $video = $this->repository->find($id);
        if ($video->video) {
            VideoHelper::deleteVideo($video->video);
        }
        return $this->repository->delete($id);
    }

    public function getVideosByProduct($productId)
    {
        return $this->repository->getByProductId($productId);
    }
}
