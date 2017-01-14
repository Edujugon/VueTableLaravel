<template>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" v-on:click="closeModal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Esta editando <b>{{ column }}</b> </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <textarea class="form-control" name="" v-model="textAreaData" cols="30" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" v-on:click="closeModal">{{closeLabel}}</button>
                    <button type="button" class="btn btn-warning" v-on:click="saveData">{{saveDataLabel}}</button>
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
            saveDataLabel:{}
        },
        data(){
            return{
                column:{},
                textAreaData:{},
                id:{},
                table:{}
            }
        },
        methods: {
            closeModal() {
                document.getElementById('myModal').className = 'modal fade';
                setTimeout(function() {
                    document.getElementById('myModal').style.display = 'none';
                }, 300);
            },
            saveData(){
                this.$http.post(this.baseUrl+'/products/update/'+this.id,{
                    'column' : this.column,
                    'data' : this.textAreaData,
                    'table' : this.table
                }).then(function (response) {
                    if(response.data.ok){
                        this.$bus.$emit('fieldsChangedByUser',{'column':this.column});
                        this.closeModal();

                        //notify client
                        notify('el nuevo valor de ' + this.column + ' es: ', this.textAreaData,'success');
                    }else{
                        this.closeModal();

                        //notify client
                        notify('No ha habido cambios', 'Sin cambios a realizar','warning');

                    }
                })
            },
            fillData(data){
                this.column = data.column;
                this.textAreaData = data.value;
                this.id = data.id;
                this.table = data.table;
            }
        },
        mounted() {
            let self = this;
            this.$bus.$on('fillData',this.fillData);
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