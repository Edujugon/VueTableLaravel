# Vue Table Laravel

Powerful Vue Table to handle Laravel DB Tables Data.

## Usage

### Controller

Create the following method in your controller.

```
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

        if($request->has('term'))
            $products = $products->where($column,'like','%'.$request->input('term').'%');

        $products = $products->orderBy($field,$dir)->paginate($request->input('rows'));


        if($products){
            $columns = Schema::getColumnListing($request->input('table'));

            return response()->json(['ok' => true,'products' => $products,'columns' => $columns],200);
        }


        return response()->json(['ok' => false,'message' => 'No items found.'],404);
    }
```

### Vue component

Copy the VueTable component to your resources/assets/js/components folder.

### App.js

Add the following line to your app.js file:

```
Vue.component('edujugon-table', require('./components/VueTable.vue'));
```

### View

Add the following tag in your View:

> Ensure you change the table name with the name of your table to be showed.

```
<edujugon-table table="YOUR-TABLE-NAME"></edujugon-table>
```
