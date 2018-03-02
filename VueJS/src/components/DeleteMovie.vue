<template>
  <div id="delete-movie">
    <div>
        <h2>Delete Movie</h2>
        <p>Are you sure ?</p>
        <button @click="deleteTheMovie" class="btn btn-danger">Delete</button>
    </div>
    
  </div>
</template>

<script>
export default {
    props: ['id'],
    data() {
        return {
               
        }
    },
    http: {
        headers: {
            'Accept' : 'json',
            'Content-Type' : 'application/hal+json',
            'Authorization' : 'Basic YWRtaW46YWRtaW4='
        }
    },
    methods: {
        deleteTheMovie() {
            let data = 'Nemanja';
            this.$http.delete('http://drupal8vue.dev.loc/node/' + this.id);
            this.$router.push('/movies');
        }
    }

}
</script>

<style>

</style>
