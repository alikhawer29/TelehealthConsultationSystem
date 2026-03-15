<?php

namespace App\Repositories\Product;

use App\Models\Option;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Attribute;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Models\Media;
use App\Repositories\Product\ProductRepositoryContract;
use Illuminate\Support\Facades\Storage;

class ProductRepository extends BaseRepository implements ProductRepositoryContract
{

    protected $model;
    protected $product;
    protected $variant;
    protected $attributes;
    protected $options;

    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->product = new Product();
        $this->variant = new ProductVariant();
        $this->attributes = new Attribute;
        $this->options = new Option();
    }

    public function create(array $params)
    {

        \DB::beginTransaction();
        try {
            $product =  $this->model->create($params);
            foreach ($params['variation'] as $variant) {
                $this->variant->create(
                    [
                        'product_id' => $product->id,
                        'color' => $variant['color'] ?? null,
                        'size' => $variant['size'] ?? null,
                        'qty' => $variant['qty'],
                        'price' => $variant['price'],
                        'status' => $params['status'],
                        'type' => $params['type'],
                    ]
                );
            }
            \DB::commit();
            return true;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }

    public function updateStatus($id, $status)
    {
        try {
            $this->model->update(
                ['id' => $id],
                ['status' =>  $status],
            );
            return true;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateProduct($id, array $params)
    {
        try {

            $this->model->where('id', $id)->update([
                'title' => $params['title'],
                'description' => $params['description'],
                'status' => $params['status'],
            ]);
            //in variation only update qty and price not color or size
            //color or size only added only new
            if ($params['variation']) {
                foreach ($params['variation'] as $variation) {
                    $updateData = [
                        'product_id' => $id,
                        'qty' => $variation['qty'],
                        'price' => $variation['price'],
                        'status' => 1,
                        'type' => $params['type'],
                    ];
                    $variantConditions = ['product_id' => $id];
                    if ($params['type'] == 'variation') {
                        $variantConditions = array_intersect_key($variation, array_flip(['size', 'color']));
                    }

                    //for update variations
                    $this->variant->updateOrCreate(
                        $variantConditions,
                        $updateData
                    );
                }

                if ($params['type'] == 'variation') {

                    // Delete variations not included in the request but have in database
                    $requestedVariationKeys = collect($params['variation'])->map(function ($variation) {
                        return $variation['color'] . '_' . $variation['size'];
                    });
                    //get all exisiting product variations
                    $existingVariations = $this->variant::where('product_id', $id)->get();
                    $existingVariations->each(function ($existingVariation) use ($requestedVariationKeys) {
                        $variationKey = $existingVariation->color . '_' . $existingVariation->size;
                        if (!$requestedVariationKeys->contains($variationKey)) {
                            $existingVariation->delete();
                        }
                    });
                }


                if (isset($params['images'])) {
                    foreach ($params['images'] as $images) {
                        $path = $this->uploadFile($images);
                        $file = $images;
                        $type = 'product';
                        $path = $this->storeFile($path, $file, $id, $type);
                    }
                }
                //delete all old files
                if (isset($params['file_id']) && !is_null($params['file_id']) && is_array($params['file_id'])) {
                    Media::where('fileable_id', $id)->where('fileable_type', 'product')
                        ->whereIn('id', $params['file_id'])
                        ->delete();
                }
            }


            return true;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    protected function uploadFile($file)
    {
        return Storage::putFile('public/media', $file);
    }

    protected function storeFile($path, $file, $data, $type)
    {
        return Media::create([
            'path' => basename($path),
            'field_name' => 'images',
            'name' => $file->getClientOriginalName(),
            'fileable_type' => $type,
            'fileable_id' => $data,
        ]);
    }

    public function attributes()
    {
        try {
            $attributes = $this->attributes->all()->makeHidden(['created_at', 'updated_at']);
            return $attributes;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function options(int $id)
    {

        try {
            $options = $this->options->where('attribute_id', $id)->get()
                ->makeHidden(['created_at', 'updated_at']);
            return $options;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
