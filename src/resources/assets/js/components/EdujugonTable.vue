<template>
    <div class="container-fluid">
        <field-modal :base-url="baseUrl" :save-data-label="saveDataLabel" close-label="Cerrar"></field-modal>
        <massive-modal :base-url="baseUrl" :update-data-label="updateDataLabel" close-label="Cerrar"></massive-modal>
        <div class="row filter-row">
            <div class="col-md-12 column-filters well">
                <div class="accordion" id="myAccordion">
                    <div class="panel">
                        <button type="button" class="btn btn-info" data-toggle="collapse" data-target="#collapsible-1"
                                data-parent="#myAccordion"><b>{{this.columnsLabel}}</b>
                        </button>
                        <button v-show="totalFilters" type="button" class="btn btn-info" data-toggle="collapse" data-target="#collapsible-2"
                                data-parent="#myAccordion"><b>{{this.filtersLabel}}</b>
                        </button>
                        <div class="pull-right">
                            <b>{{methodLabel}}</b>
                            <br>
                            <span v-show="items">{{itemsShownLabel}} <div class="label label-info">{{items.length}}</div></span>
                        </div>
                        <div id="collapsible-1" class="panel-collapse collapse">
                            <ul class="column-columns">
                                <li>
                                    <input type="checkbox" id="allCollumns"
                                           checked="checked" v-on:click="AllVisibleColumnsClicked"
                                    >{{allLabel}}
                                </li>
                                <li v-for="column in columns">
                                    <input :disabled="inArray(mandatoryColumns,column) ? 'disabled' : null" type="checkbox" :id="'checkbox-'+column"
                                           :value="column" v-model="extra.visibleColumns">{{column}}
                                </li>
                            </ul>
                        </div>
                        <div v-show="totalFilters" id="collapsible-2" class="panel-collapse collapse">
                            <ul v-for="(value,key) in extra.filters.total" class="filter">
                                <li>
                                    <div role="button" class="col-md-12 fname" data-toggle="collapse" :data-target="'#collapsible-'+key"><b>{{key}}</b></div>
                                    <div :id="'collapsible-'+key" class="col-md-12 panel-collapse collapse">
                                        <ul class="filter-columns">
                                            <li v-for="subvalue in extra.filters.total[key]">
                                                <input :class="key" v-on:click="appliedFilterClicked"
                                                       :id="key + '-' + subvalue" type="checkbox"
                                                       v-model="extra.filters.applied[key]"
                                                       :value="subvalue ? subvalue : 'null'"
                                                >
                                                {{subvalue ? subvalue : 'null'}}
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="well less-padded pull-right">
                <div class="btn btn-default" v-on:click="exportData">Export</div>
                <select id="export-extension">
                    <option vallue="xls">xls</option>
                    <option vallue="xlsx">xlsx</option>
                    <option vallue="csv">csv</option>
                </select>
            </div>
            <div class="rows-quantity pull-left">
                <div class="form-inline">
                    <div class="form-group">
                        <b>Rows</b>
                        <select class="form-control" v-model="pagination.list">
                            <option v-bind:value="{ number:15 }">15</option>
                            <option v-bind:value="{ number:50 }">50</option>
                            <option v-bind:value="{ number:100 }">100</option>
                            <option v-bind:value="{ number:all }">All</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-striped table-hover vue-table">
                        <thead>
                            <tr>
                                <th v-for="column in extra.visibleColumns" :name="column" role="button">
                                    <div v-on:click="orderBy(column)" class="pull-left">{{column}}</div> <div class="pull-left mass-update" data-toggle="tooltip" :title="massiveUpdateLabel" data-placement="bottom"><i v-show="canBeEdited(column)" @click="massiveUpdate" :id="'massupdate-'+column" class="material-icons">backup</i></div>
                                </th>
                            </tr>
                            <!--search boxes-->
                            <tr>
                                <th v-for="column in extra.visibleColumns"
                                    :name="column"
                                >
                                    <div class="form-group">
                                        <input v-on:keyup="search" name="searchInputs"
                                               v-on:click="searchForThis"
                                               :id="'search-'+column" type="text"
                                               :placeholder="searchLabel" class="form-control"
                                        >
                                    </div>
                                </th>
                            </tr>
                            <!--search boxes END-->
                        </thead>
                        <tbody id="grid">
                            <tr v-for="item in items">
                            <template v-for="column in extra.visibleColumns">
                                <td :role="canBeEdited(column) ? 'button' : null" v-on:dblclick="updateValue(item.id,column,item[column])" :name="column"
                                    v-html="highlight(item[column],column)"></td>
                            </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row">
            <ul class="pager" v-show="items">
                <li class="previous" v-show="pagination.previous">
                    <a role="button" class="page-scroll" v-on:click="fetchRecords('previous')">{{previousLabel}}</a>
                </li>
                <li class="next" v-show="pagination.next">
                    <a role="button" class="page-scroll" v-on:click="fetchRecords('next')">{{nextLabel}}</a>
                </li>
            </ul>
        </div>
    </div>
</template>
<script>
    import EditFieldModal from './EditFieldModal.vue';
    import MassiveUpdateModal from './MassiveUpdateModal.vue';

    export default {
        components:{
            'field-modal': EditFieldModal,
            'massive-modal': MassiveUpdateModal
        },
        props:{
            baseUrl:{},
            table:{},
            filters:{},
            dataFilters:{},
            methodLabel:{default:''},
            autoDbFilters:{
                type:Boolean,
                default:false
            },
            mandatoryCols:{},
            noEditableColumns:{},
            primaryKey:{
                default:'id'
            }
        },
        data: function () {
            return {
                items:[],
                columns:null,
                mandatoryColumns:this.mandatoryCols,
                extra:{
                    visibleColumns:[],
                    columnsOrdered: false,
                    filters:{
                        total:{},
                        applied:{}
                    }
                },
                pagination: {
                    page: 1,
                    previous: false,
                    next: false,
                    list:{number:15}
                },
                orderByField: this.primaryKey,
                orderByDir:'DESC',
                searchTerm:'',
                searchColumn:'',
                //Labels
                previousLabel:'Previous',
                nextLabel:'Next',
                searchLabel:'Search',
                columnsLabel:'Columns',
                filtersLabel:'Filters',
                allLabel:'All',
                saveDataLabel: 'Save Changes',
                updateDataLabel: 'Update Values',
                massiveUpdateLabel: 'Update all values of the column',
                itemsShownLabel:'Items',
            }
        },
        watch: {
            columns(val,oldVal){
                //Update mandatoryColumns based on the table columns.
                //If no exists any it is removed from the mandatoryColumn array.
              if(!oldVal && this.mandatoryColumns){
                  let finalColumns = [];
                  this.mandatoryColumns.forEach(function (col) {
                      if (val.indexOf(col) > -1)
                          finalColumns.push(col);
                  });
                  this.mandatoryColumns = finalColumns;
              }
            },
            'pagination.list'(val){
                //Set pagination to first page
                this.pagination.page = 1;

                this.fetchRecords();
            },
            totalFilters(val){
                this.fetchFilters()
            },
            'extra.visibleColumns'(){

                // Order the columns if it's not ordered yet
                // Also stop method with return because it will change the data
                // and the method will be called again since it's a watch
                if(!this.extra.columnsOrdered){
                    this.orderColumns();
                    this.extra.columnsOrdered = true;

                    return true;
                }

                if(this.extra.visibleColumns.length == 0){
                    this.items = null;
                }else{
                    //Set pagination to first page
                    this.pagination.page = 1;

                    this.fetchRecords();
                }

                this.cleanTerms();
            }
        },
        computed:{
            totalFilters(){
                //If auto db filters is true, take all columns to load the filters.
                if(this.autoDbFilters){
                    return this.columns;
                }
                return this.filters;
            },
            columnsToShow(){
                if(this.extra.visibleColumns.length != 0)
                    return this.extra.visibleColumns;
                return '*';
            }
        },
        methods: {
            isNumber(n) {
                return !isNaN(parseFloat(n)) && isFinite(n);
            },
            inArray(array,item){
              if(array && array.indexOf(item) > -1)
                  return true;

              return false;
            },
            highlight:function(value,column){

                if(column == 'descripcion' && value)
                    value = value.substring(0,50) + '...';

                if(column == this.searchColumn && this.searchTerm != '' && value != null)
                {
                    if(this.isNumber(value))
                        return value;

                    return value.toUpperCase().replace(this.searchTerm.toUpperCase(),'<span style="background-color: yellow;">' + this.searchTerm.toUpperCase() + '</span>');
                }else return value;

            },
            AddSpinner() {
                var spinner = '<div class="overlay-spinner"><div class="loader"></div></div>';
                $('#app').prepend(spinner).fadeIn('slow');
            },
            RemoveSpinner() {
                $('.overlay-spinner').fadeOut('slow', function () { $(this).remove() });
            },
            fetchRecords: function (direction) {

                this.AddSpinner();

                const self = this;
                if (direction === 'previous'){
                    --this.pagination.page;
                }
                else if (direction === 'next'){
                    ++this.pagination.page;
                }

                this.$http.post(this.baseUrl+'/edujugon/fetch/'+this.orderByField+'/'+this.orderByDir+'/'+this.searchColumn,{
                    'selectColumns' :  self.columnsToShow,
                    'table': this.table,
                    'term' : this.searchTerm,
                    'page' : this.pagination.page,
                    'rows' : this.pagination.list.number,
                    'filters' : this.extra.filters.applied
                }).then(function(response){

                    if(response.data.ok){
                        //Depends on it's a collection or a pagination object
                        this.items =  response.data.items.data ? response.data.items.data : response.data.items;

                        //Pagination
                        this.pagination.next = response.data.items.next_page_url;
                        this.pagination.previous = response.data.items.prev_page_url;


                    }else {
                        console.log(response.data);
                        this.items = [];
                        this.columns = []
                    }

                    this.RemoveSpinner();

                }, function(response){
                    this.RemoveSpinner();
                    console.log(response);
                });

            },

            ////// Columns

            fetchColumns(){
                this.AddSpinner();

                let self = this;
                this.$http.post(this.baseUrl+'/edujugon/fetch/columns',{
                    'table' : this.table,
                }).then(function (response) {
                    if (response.data.ok)
                    {
                        self.columns = response.data.columns;
                        self.extra.visibleColumns = response.data.columns;

                    }

                    this.RemoveSpinner();
                }, function (response) {
                    this.RemoveSpinner();
                });


            },
            orderColumns(){
                if(this.mandatoryColumns)
                {
                    const self = this;
                    const array = this.mandatoryColumns;
                    for (let i = array.length -1; i >= 0; i--) {
                        let column = array[i];
                        let pos = self.extra.visibleColumns.indexOf(column);

                        if(pos != -1) {
                            self.extra.visibleColumns.splice(pos, 1);
                            self.extra.visibleColumns.unshift(column);
                        }
                    }

                    // If no changes, the fetchRecords.
                }else this.fetchRecords();
            },
            AllVisibleColumnsClicked(event)
            {
                const checked = event.target.checked;

                if(checked){
                    this.extra.visibleColumns = this.columns;
                }else{
                    this.extra.visibleColumns = this.mandatoryColumns ? this.mandatoryColumns : [];
                }

            },

            ////// Columns END

            ////// Filters

            fetchFilters(){
                this.AddSpinner();

                let self = this;
                this.$http.post(this.baseUrl+'/edujugon/fetch/filters',{
                    'table' : this.table,
                    'filters' : this.totalFilters,
                }).then(function (response) {
                    if (response.data.ok)
                    {
                        self.extra.filters.total = response.data.filters;
                        this.initializeAppliedFilters();

                    }

                    this.RemoveSpinner();

                }, function(response) {
                    //error
                    this.RemoveSpinner();
                });


            },
            initializeAppliedFilters()
            {

                for (let filter in this.extra.filters.total) {
                    if (this.extra.filters.total.hasOwnProperty(filter)) {
                        this.extra.filters.applied[filter] = [];
                    }
                }

                //If there is dataFilters Prop then set the values to applied filters.
                if(this.dataFilters)
                    this.setAppliedFiltersAsPerPropData();

            },
            setAppliedFiltersAsPerPropData()
            {
                let applied = false;
                // The dataFilters keys must exist in total filters.
                for (let filter in this.extra.filters.applied){
                    if(this.dataFilters[filter])

                        for(let i = 0; i < this.dataFilters[filter].length; i++)
                        {

                            if(this.extra.filters.total[filter].includes(this.dataFilters[filter][i])){
                                this.extra.filters.applied[filter] = this.dataFilters[filter];
                                applied = true;
                            }else if(this.dataFilters[filter][i] == 'null' && this.extra.filters.total[filter][i] == null){
                                this.extra.filters.applied[filter].push('null');
                                applied = true;
                            }

                        }

                }

                if(applied) this.fetchRecords();
            },
            appliedFilterClicked(event)
            {
                let item = event.target.id.split('-');

                // Find and remove item from an array
                //If exists in array, remove
                var i = this.extra.filters.applied[item[0]].indexOf(item[1]);
                if(i != -1) {
                    this.extra.filters.applied[item[0]].splice(i, 1);
                }else //Otherwise, add new one
                    this.extra.filters.applied[item[0]].push(item[1]);

                //Load data
                this.fetchRecords();
            },

            ///// Filters END

            ///// Export

            exportData(){
                const self = this;
                let e = document.getElementById("export-extension");
                let extension = e.options[e.selectedIndex].value;

                this.$http.post(this.baseUrl+'/export/',{
                    data: self.items,
                    extension: extension
                }).then(function (response) {
                    if(response.data.ok)
                        window.open(response.data.url,'_blank');
                });
            },

            ///// Export END

            orderBy(field){
                this.orderByField = field;

                if(this.orderByDir == 'DESC')
                    this.orderByDir = 'ASC';
                else this.orderByDir = 'DESC';

                //Set pagination to first page
                this.pagination.page = 1;

                this.fetchRecords();
            },
            search(event){
                this.searchTerm = event.target.value;
                this.searchColumn = event.target.id.split('-')[1];

                //Set pagination to first page
                this.pagination.page = 1;

                this.fetchRecords();
            },
            searchForThis(event){
                if(event.target.value != '')
                    this.search(event);
            },

            cleanTerms(){

                if(this.searchColumn != '' && this.extra.visibleColumns.indexOf(this.searchColumn) == -1){
                    document.getElementById('search-'+this.searchColumn).value = '';
                    this.searchColumn = '';
                    this.searchTerm = '';

                }

            },
            updateValue(id,column,value){

                //check if can be edited.
                if(!this.canBeEdited(column)) return false;

                document.getElementById('myModal').style.display = 'block';
                setTimeout(function() {
                    document.getElementById('myModal').className = 'modal fade in';
                }, 120);
                this.$bus.$emit('fillData',{
                    'id':id,
                    'column':column,
                    'value':value,
                    'table':this.table
                });
            },
            massiveUpdate(event){
                //const value = event.target.value;
                const columnName = event.target.id.split('-')[1];

                //check if can be edited.
                if(!this.canBeEdited(columnName)) return false;

                let ids = [];
                let emptyValues = 0;
                let noEmptyValues = 0;

                for(let i = 0; i <= this.items.length -1;i++){
                    ids.push(this.items[i][this.primaryKey]);

                    if(this.items[i][columnName] == '' || this.items[i][columnName] == null)
                        emptyValues ++;
                    else noEmptyValues++;
                }

                document.getElementById('massiveModal').style.display = 'block';
                setTimeout(function() {
                    document.getElementById('massiveModal').className = 'modal fade in';
                }, 120);

                //emit data to massiveUpdateModal Component
                this.$bus.$emit('massiveUpdate',{
                    'ids':ids,
                    'column':columnName,
                    'countEmptyFields': emptyValues,
                    'countNoEmptyFields': noEmptyValues,
                    //'value':value,
                    'table':this.table
                });
            },
            fieldsChangedByUser(data){
                // Find and remove item from an array
                //If exists in array, remove
                if(this.extra.filters.applied.hasOwnProperty(data.column)){
                    this.extra.filters.applied[data.column] = [];
                    let elements = document.getElementsByClassName('iva');
                    for (let i = 0; i < elements.length; i++) {
                        elements[i].checked=false;
                    }
                }

                // Reload the filters and records.
                this.fetchFilters();
                this.fetchRecords();

            },
            canBeEdited(column){
                //check if can be edited.
                if(this.noEditableColumns && this.noEditableColumns.indexOf(column) > -1)
                    return false;

                return true;
            },
            //Laravel Echo
            echo()
            {
                Echo.channel('db-data-updated')
                    .listen('DbDataUpdated', (e) => {
                        // Notify client
                        notify(e.user.name + ' ' + e.data.message + ' ' + e.data.column, e.data.data,'success');

                        // Load records.
                        this.fetchRecords();
                    });
            }
        },
        mounted() {
            console.log('Product Table ready.');

            this.$bus.$on('fieldsChangedByUser',this.fieldsChangedByUser);

            this.fetchColumns();
            if(this.filters) this.fetchFilters();

            this.echo();
        }
    }
</script>

<style lang="sass">
    .filter-row {
        margin-bottom: 30px;
    }

    .column-filters {
        background-color: #f1f1f1;
        border-radius: 4px;
        padding: 0px 15px;
        margin-bottom: 15px;
        & .panel {
            background-color: transparent !important;
            box-shadow: none;
            padding: 20px 10px 0px 10px;
        }
        & .panel-collapse {
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px solid lightgray
        }
        & .label {
              border-radius: 100px;
              font-size: 1.4rem;
              margin-right: 2rem;
          }
    }

    .column-columns {
        list-style: none;
        columns: 4;
        -webkit-columns: 4;
        -moz-columns: 4;
    }

    .filter-columns {
        list-style: none;
        background-color: #fafafa;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 5px;
        columns: 6;
        -webkit-columns: 6;
        -moz-columns: 6;
    }

    .filter {
        list-style: none;
        & li {
            & .panel-collapse {
                  padding-top: 0px !important;
                  margin-top: 0px !important;
                  border-top: none !important;
              }
          }
        & .fname {
              text-transform: capitalize;
              border-bottom: 1px solid lightblue;
              padding-bottom: 10px;
              margin-top: 20px;
              margin-bottom: 10px;

          }
    }
    .table > thead > tr > th {
        min-width: 200px !important;
    }

    .mass-update {
        padding: 0px 10px;
    }

</style>