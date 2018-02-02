<template>
<div >
    <h1>This is my single movies page</h1>
     <div v-for="movie in movies" :key="movie.title" class="single-movie">
        <p><strong>Title:</strong><br> {{movie.title}}</p>
        <img v-bind:src="'http://drupal8vue.dev.loc' + movie.field_movie_poster" />
        <p><strong>Description : </strong></p>
        <p>{{movie.body}}</p>
        <br>
    </div>
</div>
</template>

<script>
const url = "http://drupal8vue.dev.loc/api/movies/";

export default {
    
    data: function() {
      return {
          movies: [],
      }
    },
    mounted() {
      fetch(url+'3')
        .then(response => response.json())
        .then((data) => {
          this.movies = data;
        })
    },

}
</script>

<style lang="scss" scoped>

</style>
