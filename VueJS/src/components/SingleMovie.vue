<template>
<div >
    <h1>This is my single movies page</h1>
     <div class="movie" v-if="movie[0]">
    <h1 class="movie__title">{{ movie[0].title }}</h1>
    <p class="movie__body">{{ movie[0].body }}</p>
    <img v-bind:src="'http://drupal8vue.dev.loc' + movie[0].field_movie_poster" />
  </div>
</div>
</template>

<script>
  import axios from 'axios';

  export default {
    props: ['id'],

    data() {
      return {
        movie: null,
        endpoint: 'http://drupal8vue.dev.loc/api/movies/',
      }
    },

    methods: {
      getMovie(id) {
        axios(this.endpoint + id)
          .then(response => {
            this.movie = response.data
          })
          .catch( error => {
            console.log('-----error-------');
            console.log(error)
          })
      }
    },
    
    created() {
      this.getMovie(this.id);
    },

    watch: {
      '$route'() {
        this.getMovie(this.id);
      }
    }
  }
</script>
<style lang="scss" scoped>

</style>
