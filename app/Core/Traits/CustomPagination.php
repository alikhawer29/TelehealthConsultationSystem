<?php

namespace App\Core\Traits;


trait CustomPagination
{

    public function pagination($message = null, $data, $others = null, $othersTwo = null)
    {
        $paginationData = [
            'status' => true,
            'message' => $message,
            'detail' => [
                'current_page' => $data->currentPage(),
                'data' => $others ?? $data->values(),
                'first_page_url' => $data->url(1),
                'from' => $data->firstItem(),
                'last_page' => $data->lastPage(),
                'last_page_url' => $data->url($data->lastPage()),
                'links' => [
                    'prev_page_url' => $data->previousPageUrl(),
                    'next_page_url' => $data->nextPageUrl(),
                ],
                'path' => $data->url($data->currentPage()),
                'per_page' => $data->perPage(),
                'prev_page_url' => $data->previousPageUrl(),
                'to' => $data->lastItem() - 1,
                'total' => $data->total() - 1,
            ],
        ];
        if ($othersTwo !== null) {
            $paginationData['detail']['overall_late_order_ratio'] = $othersTwo;
        }

        return $paginationData;
    }
}
