<template>
<div class="movies">
        <h2>create movie</h2>
    </div>
</template>

<script>
import axios from 'axios'


export default {
  
    data () {
      return {
        movies: [],
        endpoint: 'http://drupal8vue.dev.loc/entity/node/',
      }
    },
    created() {
      this.createTheMovie();
    },
    methods: {
      createTheMovie() {
          
        axios.post(this.endpoint, {
            "title": "POST METHOD FROM VUE JS"
        })
            .then(function (response) {
                console.log(response);
            })
            .catch(function (error) {
                console.log(error.response);
            });
      }
    }

}
</script>
