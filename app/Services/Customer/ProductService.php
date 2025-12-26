<?php

namespace App\Services\Customer;

use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;

class ProductService
{
    public function getProducts($request)
    {
        $page        = (int) $request->get('page', 1);
        $listPerPage = (int) $request->get('list_per_page', 10);
        $offset      = ($page - 1) * $listPerPage;

        $productQuery = $request->get('product_query')
            ? '%' . $request->get('product_query') . '%'
            : '%';

        $customerType = strtoupper($request->get('customer_type', ''));
        $regionParam  = $request->get('region');
        $zipCode      = $request->get('zip_code');

        // =============================
        // GET REGION FROM ZIP
        // =============================
        $area = null;

        if ($zipCode) {
            $getRegion = DB::table('tis_master.master_kodepos')
                ->where('ZipCode', $zipCode)
                ->first();

            if ($getRegion) {
                $area = $getRegion->Regional;
            }
        }

        // =============================
        // MAIN QUERY (MATCH EXPRESS)
        // =============================
        $query = DB::table('tis_master.produk')
            ->select(
                'ID',
                'Product_Group',
                'Product_Category',
                'Product_Code',
                'Product_Name',
                'Region',
                'Price'
            )
            ->where(function ($q) use ($productQuery) {
                $q->where('Product_Name', 'LIKE', $productQuery)
                    ->orWhere('Product_Code', 'LIKE', $productQuery)
                    ->orWhere('Product_Group', 'LIKE', $productQuery);
            })
            ->where('Price', '>', 0)
            ->where('Product_Mobile', 1);

        // === SAMA DENGAN EXPRESS ===
        if (in_array($customerType, ['RETAIL'])) {
            $query->where('Product_Category', 'LIKE', '%Retail%');
        }

        if (!empty($area)) {
            $query->where('Region', 'LIKE', $area);
        }

        $data = $query
            ->orderBy('Price', 'ASC')
            ->offset($offset)
            ->limit($listPerPage)
            ->get();

        return [
            'status' => 'success',
            'meta' => [
                'count' => $data->count()
            ],
            'data' => $data
        ];
    }
}
