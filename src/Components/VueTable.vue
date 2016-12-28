<template>
    <div class="container-fluid">
        <div class="card" id="accordion">
            <div class="card-header" role="tab" id="headingOne">
                <h5 class="mb-0">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <button>Columnas</button>
                    </a>
                </h5>
            </div>
            <div id="collapseOne" class="collapse" role="tabpanel" aria-labelledby="headingOne">
                <div class="card-block">
                    <input type="checkbox" id="allCollumns" checked="checked" v-on:click="updateAllFilterColumns">Todas
                    <template v-for="column in columns">
                        <input checked="checked" type="checkbox" :id="'checkbox-'+column" v-on:click="visible">{{column}}
                    </template>
                </div>
            </div>
        </div>
        <div class="pull-right">
            <button>Export</button>
        </div>
        <div>
            <select v-model="pagination.list">
                <option v-bind:value="{ number:15 }">15</option>
                <option v-bind:value="{ number:30 }">30</option>
                <option v-bind:value="{ number:50 }">50</option>
                <option v-bind:value="{ number:100 }">100</option>
                <option v-bind:value="{ number:300 }">300</option>
                <option v-bind:value="{ number:1000 }">1000</option>
            </select>
            Filas

        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <template v-for="(value,column) in filter.columns">
                        <th :name="column" role="button" v-on:click="orderBy(column)">
                            {{column}}
                        </th>
                    </template>
                </tr>
                <tr>
                    <template v-for="(value,column) in filter.columns">
                        <th :name="column"><input v-on:keyup="search" name="searchInputs" v-on:click="searchForThis" :id="'search-'+column" type="text" :placeholder="searchLabel"></th>
                    </template>
                </tr>
                </thead>

                <tbody id="grid">
                <tr v-for="item in items">
                    <template v-for="(value,column) in filter.columns">
                        <td :name="column" v-if="filter.columns[column]" v-html="highlight(item[column],column)"></td>
                    </template>

                </tr>
                </tbody>
            </table>
        </div>
        <ul class="pager" v-show="items">
            <li class="previous" v-show="pagination.previous">
                <a role="button" class="page-scroll" v-on:click="fechRecords('previous')">Previous</a>
            </li>
            <li class="next" v-show="pagination.next">
                <a role="button" class="page-scroll" v-on:click="fechRecords('next')">Next</a>
            </li>
        </ul>
    </div>
</template>
<script>
    export default {
        props:{
            table:{}
        },
        data: function () {
            return {
                items:null,
                columns:null,
                filter:{
                    columns:{}
                },
                pagination: {
                    page: 1,
                    previous: false,
                    next: false,
                    list:{number:15}
                },
                orderByField:'created_at',
                orderByDir:'DESC',
                searchTerm:'',
                searchColumn:'',
                searchLabel:'Buscar',
            }
        },
        watch: {
            //When items are updatedm fire hiddeAndShowElements method.
            items(val){
                this.hiddeAndShowElements();
            },
            'pagination.list'(val){
                //Set pagination to first page
                this.pagination.page = 1;

                this.fechRecords();
            }
        },
        computed:{
        },
        methods: {
            isNumber(n) {
                return !isNaN(parseFloat(n)) && isFinite(n);
            },
            highlight:function(value,column){

                if(column == this.searchColumn && this.searchTerm != '' && value != null)
                {
                    if(this.isNumber(value))
                        return value;

                    return value.toUpperCase().replace(this.searchTerm.toUpperCase(),'<span style="background-color: yellow;">' + this.searchTerm.toUpperCase() + '</span>');
                }else return value;

            },
            fechRecords: function (direction) {

                var self = this;
                if (direction === 'previous'){
                    --this.pagination.page;
                }
                else if (direction === 'next'){
                    ++this.pagination.page;
                }

                this.$http.post('product/fetch/'+this.orderByField+'/'+this.orderByDir+'/'+this.searchColumn,{
                    'selectColumns' :  self.selectColumns(),
                    'table': this.table,
                    'term' : this.searchTerm,
                    'page' : this.pagination.page,
                    'rows' : this.pagination.list.number,
                }).then(function(response){

                    if(response.data.ok){
                        this.items =  response.data.products.data;
                        this.columns = response.data.columns;

                        //Pagination
                        this.pagination.next = response.data.products.next_page_url;
                        this.pagination.previous = response.data.products.prev_page_url;

                        if(jQuery.isEmptyObject(this.filter.columns)) this.fillFilterColumns();

                    }else {
                        console.log(response.data);
                    }
                }, function(response){
                    console.log(response);
                });

            },
            orderBy(field){
                this.orderByField = field;

                if(this.orderByDir == 'DESC')
                    this.orderByDir = 'ASC';
                else this.orderByDir = 'DESC';

                //Set pagination to first page
                this.pagination.page = 1;

                this.fechRecords();
            },
            search(event){
                this.searchTerm = event.target.value;
                this.searchColumn = event.target.id.split('-')[1];

                //Set pagination to first page
                this.pagination.page = 1;

                this.fechRecords();
            },
            searchForThis(event){
                if(event.target.value != '')
                    this.search(event);
            },
            fillFilterColumns()
            {
                var self = this;

                this.columns.forEach(function (item) {
                    self.filter.columns[item] = true;
                });

            },
            visible(event)
            {
                var self = this;

                var columnName = event.target.id.split('-')[1];

                this.filter.columns[columnName] = !this.filter.columns[columnName];

                if(this.checkNoVisibleColumns()){
                    this.items = null;
                }else{
                    this.cleanTerms(columnName);

                    //Set pagination to first page
                    this.pagination.page = 1;

                    this.fechRecords();
                }


            },
            updateAllFilterColumns(event)
            {
                var checked = event.target.checked;
                var self = this;

                this.columns.forEach(function (item) {
                    document.getElementById('checkbox-'+item).checked = checked;
                    self.filter.columns[item] = checked;

                });

                if(checked){
                    //Set pagination to first page
                    this.pagination.page = 1;

                    this.fechRecords();
                }else{
                    this.items = null;
                }


            },
            hiddeAndShowElements(){
                var self = this;

                this.columns.forEach(function (item) {

                    document.getElementsByName(item).forEach(function (el) {

                        if(self.filter.columns[item] == false) el.className = 'hidden';
                        else el.className = 'visible';
                    });
                });
            },
            checkNoVisibleColumns(){
                var any = true;
                var self = this;

                this.columns.forEach(function (el) {

                    if(self.filter.columns[el] == true)
                        any = false;
                });

                return any;
            },
            selectColumns(){
                var columnsToShow = [];
                var self = this;

                if(this.columns)
                {
                    this.columns.forEach(function (el) {

                        if(self.filter.columns[el] == true){
                            columnsToShow.push(el);
                        }

                    });
                }else columnsToShow = '*';

                return columnsToShow;
            },
            cleanTerms(column){
                if(this.searchColumn == column){
                    this.searchColumn = '';
                    this.searchTerm = '';
                    document.getElementById('search-'+column).value = '';
                }
            }
        },
        mounted() {
            console.log('Product Table ready.');
            this.fechRecords();
        }
    }
</script>