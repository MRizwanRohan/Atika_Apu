<?php

namespace App\Repository;

use App\Contracts\ProductVideoRepositoryInterface;
use App\Models\ProductVideo;

class ProductVideoRepository implements ProductVideoRepositoryInterface
{
    public function all()
    {
        return ProductVideo::with('product')->get();
    }

    public function find($id)
    {
        return ProductVideo::findOrFail($id);
    }

    public function create(array $data)
    {
        return ProductVideo::create($data);
    }

    public function update($id, array $data)
    {
        $video = $this->find($id);
        $video->update($data);
        return $video;
    }

    public function delete($id)
    {
        $video = $this->find($id);
        return $video->delete();
    }

    public function getByProductId($productId)
    {
        return ProductVideo::where('product_id', $productId)->get();
    }
}
