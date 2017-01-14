<template>
    <div class="modal fade" id="massiveModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" v-on:click="closeModal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel" v-html="headingLabel"></h4>
                    <p class="modal-title" id="myModalLabel">* {{emptyColumnsLabel}} <b>{{ countEmptyFields }}</b> </p>
                    <p class="modal-title" id="myModalLabel">* {{noEmptyColumnsLabel}} <b>{{ countNoEmptyFields }}</b> </p>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <textarea class="form-control" name="" v-model="textAreaData" cols="30" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" v-on:click="closeModal">{{closeLabel}}</button>
                    <button type="button" class="btn btn-warning" v-on:click="updateData">{{updateDataLabel}}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props:{
            baseUrl:{},
            closeLabel:{},
            updateDataLabel:{},
            emptyColumnsLabel:{
                default:'Campos vacios: '
            },
            noEmptyColumnsLabel:{
                default:'Campos con datos: '
            }
        },
        data(){
            return{
                column:{},
                textAreaData:'',
                ids:{},
                countEmptyFields:{},
                countNoEmptyFields:{},
                table:{}
            }
        },
        computed:{
            headingLabel(){
                return  'Esta editando <b>'+ this.totalRows + '</b> filas en la columna <b>' + this.column + '</b>';
            },
            totalRows(){
                return this.countEmptyFields + this.countNoEmptyFields;
            }
        },
        methods: {
            closeModal() {
                document.getElementById('massiveModal').className = 'modal fade';
                setTimeout(function() {
                    document.getElementById('massiveModal').style.display = 'none';
                }, 300);
            },
            updateData(){
                let self = this;
                this.$http.post(this.baseUrl+'/products/massive-update/',{
                    'ids' : this.ids,
                    'column' : this.column,
                    'data' : this.textAreaData,
                    'table' : this.table
                }).then(function (response) {
                    if(response.data.ok){
                        this.$bus.$emit('fieldsChangedByUser',{'column':this.column});
                        this.closeModal();

                        //notify client
                        notify('el nuevo valor de ' + this.column + ' es: ', this.textAreaData,'success');

                        //Clean value
                        self.textAreaData = '';
                    }else{
                        this.closeModal();

                        //notify client
                        notify('No ha habido cambios', 'Sin cambios a realizar','warning');

                        //Clean value
                        self.textAreaData = '';
                    }
                })
            },
            massiveUpdate(data){
                this.column = data.column;
                this.ids = data.ids;
                this.ids = data.ids;
                this.countEmptyFields = data.countEmptyFields;
                this.countNoEmptyFields = data.countNoEmptyFields;
                this.table = data.table;
            }
        },
        mounted() {
            let self = this;
            this.$bus.$on('massiveUpdate',this.massiveUpdate);
        }
    }
</script>

<style lang="sass">

    .modal {
        background-color: rgba(0, 0, 0, 0.5);
        & .form-group {
            margin-bottom: 0px;
          }
    }

    .modal.in .modal-dialog {
        top: 30vh;
    }

</style>