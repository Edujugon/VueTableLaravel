<?php

namespace Edujugon\VueTableLaravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VueTableController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @param $field
     * @param $dir
     * @param null $column
     * @return \Illuminate\Http\JsonResponse
     * @internal param $rows
     */
    public function fetch(Request $request, $field, $dir, $column = null)
    {
        $products = DB::table($request->input('table'))->select($request->has('selectColumns') ? $request->input('selectColumns') : '*');

        $products = $this->addFiltersQuery($request, $products);

        if($request->has('term'))
            $products = $products->where($column,'like','%'.$request->input('term').'%');

        $products = $products->orderBy($field,$dir);

        if(!$request->has('rows'))
            $products = $products->get();
        else
            $products = $products->paginate($request->input('rows'));

        if($products){
            return response()->json(['ok' => true,'items' => $products],200);
        }


        return response()->json(['ok' => false,'message' => 'Productos no encontrados.'],404);
    }

    public function fetchColumns(Request $request)
    {
        $columns = Schema::getColumnListing($request->input('table'));

        return response()->json(['ok'=> true,'columns' =>$columns],200);
    }

    public function fetchFilters(Request $request)
    {
        $filters = $request->input('filters');
        $table = $request->input('table');

        $filtersWithData = [];
        foreach ($filters as $filter)
        {
            $filtersWithData[$filter] = DB::table($table)->select($filter)->get()->unique()->pluck($filter);
        }
        return response()->json(['ok'=> true,'filters' =>$filtersWithData],200);
    }

    /**
     * Add filter query to the main query
     * @param Request $request
     * @param $products
     * @return mixed
     */
    public function addFiltersQuery(Request $request, $products)
    {
        foreach ($request->input('filters') as $key => $values) {

            if ($values) {
                $products = $products->whereIn($key, $values);
            }

            //Look for null values.
            $pos = array_search('null', $values);
            if ($pos !== false) {
                $products = $products->orWhere(function ($query) use($key) {
                    $query->whereNull($key);
                });
            }
        }
        return $products;
    }

    /**
     * Update record in DB
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {

        $data = $request->has('data') ? $request->input('data') : null;

        $count = DB::table($request->input('table'))->where('id',$id)
            ->update([$request->input('column')=> $data]);

        if($count)
        {
            return response()->json(['ok' => true,'message' => 'Column updated successfully'],200);
        }else
            return response()->json(['ok' => false,'message' => 'Nothing to update'],200);
    }

    /**
     * Update records massively
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function massiveUpdate(Request $request)
    {
        $data = $request->has('data') ? $request->input('data') : null;

        $count = DB::table($request->input('table'))->whereIn('id', $request->input('ids'))->update([$request->input('column') => $data]);

        if ($count)
        {
            return response()->json(['ok' => true, 'message' => 'Rows updated successfully'], 200);
        }else
            return response()->json(['ok' => false,'message' => 'Nothing to update'],200);
    }

}