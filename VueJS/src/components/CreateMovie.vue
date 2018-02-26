<template>
  <div class="container">
      <h2>Create New Movie</h2>
      <div class="alert alert-success" v-if="success">
          Movie has been added
      </div>
      <form v-on:submit="createTheMovie"> 
          <div class="form-group">
              <label for="">Movie title:</label>
              <br>
              <input type="text" name="title" v-model="title" class="form-control">
          </div>
          <div class="form-group">
              <label for="">Movie body:</label>
              <br>
              <textarea v-model="body" name="" class="form-control"></textarea>
          </div>
          <button type="submit" class="btn btn-success">Add Movie</button>
      </form>
      <br>
  </div>
</template>

<script>
import axios from 'axios'

export default {
    data() {
        return {
            title: this.title,
            body: this.body,
            success: false
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
        createTheMovie: function(e) {
            
            e.preventDefault();
            let data = JSON.stringify({
                _links : {
                    type: {
                        href : "http://drupal8vue.dev.loc/rest/type/node/movies"
                    },
                },
                type: {
                    target_id: 'movies'
                },
                title: {
                    value : this.title
                },
                body: {
                    value: this.body
                }
            }) 
            this.$http.post('http://drupal8vue.dev.loc/entity/node', data);
            this.success = true;
            this.title = '';
            this.body = '';
            console.log('Created movie')
        }
    }
}
</script>

<style lang="scss">

</style>
