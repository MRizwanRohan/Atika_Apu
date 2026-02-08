<?php

namespace App\Contracts;

interface ProductVideoServiceInterface
{
    public function getAllVideos();
    public function getVideoById($id);
    public function storeVideo(array $data);
    public function updateVideo($id, array $data);
    public function deleteVideo($id);
    public function getVideosByProduct($productId);
}
